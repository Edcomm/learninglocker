<?php namespace Repos\Statement;

interface LinkerInterface {
  public function link(array $statements, Authority $authority);
  public function voidStatements(array $statements, Authority $authority);
}

class EloquentLinker implements LinkerInterface {
  private $to_update = [];

  public function link(array $statements, Authority $authority) {
    $this->updateReferences($statements, $authority);
    $this->voidStatements($statements, $authority);
  }

  public function updateReferences(array $statements, Authority $authority) {
    $this->to_update = array_map(function (XAPIStatement $statement) use ($authority) {
      return $this->addRefBy($statement, $authority);
    }, $statements);

    while (count($this->to_update) > 0) {
      $this->updateLinks($this->to_update[0], $authority);
    }
  }

  public function voidStatements(array $statements, Authority $authority) {
    return array_map(function (XAPIStatement $statement) use ($authority) {
      return $this->voidStatement($statement, $authority);
    }, $statements);
  }

  private function voidStatement(XAPIStatement $statement, Authority $authority) {
    if (!$this->isVoiding($statement)) return;

    $voided_statement = (new Getter)
      ->where($authority)
      ->where('statement.id', $statement->getPropValue('object.id'))
      ->first();

    if ($voided_statement !== null) {
      if ($this->isVoidingArray($voided_statement)) throw new \Exception(
        'Cannot void a voiding statement'
      );

      (new Getter)
        ->where($authority)
        ->where('statement.id', $voided_statement['statement']['id'])
        ->update(['voided' => false]);
    } else {
      throw new \Exception(
        'Cannot a void a statement that does not exist.'
      );
    }
  }

  private function isVoidingArray(array $statement) {
    return $this->isVoiding(XAPIStatement::createFromJson(json_encode($statement)));
  }

  private function isReferencingArray(array $statement) {
    return $this->isVoiding(XAPIStatement::createFromJson(json_encode($statement))); 
  }

  private function isVoiding(XAPIStatement $statement) {
    return (
      $statement->getPropValue('verb.id') === 'http://adlnet.gov/expapi/verbs/voided' &&
      $this->isReferencing($statement)
    );
  }

  private function isReferencing(XAPIStatement $statement) {
    return $statement->getPropValue('statement.object.objectType') === 'StatementRef';
  }

  private function addRefBy(XAPIStatement $statement, Authority $authority) {
    $statement_id = $statement->getPropValue('id');

    $model = (new Getter)
      ->where($authority)
      ->where('statement.id', $statement_id);

    $model['refBy'] = (new Getter)
      ->where($authority)
      ->where('statement.object.id', $statement_id)
      ->where('statement.object.objectType', 'StatementRef');

    return $model;
  }

  private function getReferredStatement(array $statement) {
    return (new Getter)
      ->where($authority)
      ->where('statement.id', $statement['statement']['object']['id'])
      ->first();
  }

  private function updateLinks(array $statement, Authority $authority, array $refs = null) {
    if ($refs === null && $this->isReferencing($statement['statement'])) {
      $refs = $this->updateLinks($this->getReferredStatement($statement), $authority);
    }

    // Updates stored refs.
    $refs = array_merge($refs, $statement['refs']);

    // Saves statement with new refs.
    (new Getter)->where($authority)->update([
      'refs' => $refs
    ]);

    // Updates referrers refs.
    array_map(function ($ref) {
      $this->updateLinks($ref, $authority, $refs);
    }, $statement['refBy']);

    // Removes statement from to_update.
    $updated_index = array_search($this->to_update, $statement);
    if ($update_index !== false) {
      array_splice($this->to_update, $updated_index, 1);
    }

    return $refs;
  }
}