<?php namespace Controllers\API;

use \LockerRequest as LockerRequest;
use \IlluminateResponse as IlluminateResponse;
use \Repos\Statement\EloquentRepository as StatementRepository;

class StatementController extends BaseController {
  /**
   * Aggregates statements via the Mongo aggregation.
   * @return \Illuminate\Http\JsonResponse Aggregated statements.
   */
  public function aggregate() {
    // Constructs the pipeline.
    $pipeline = json_decode(
      LockerRequest::getParam('pipeline'),
      true
    ) ?: [['$match' => []]];

    // Returns the aggregation.
    return IlluminateResponse::json(
      (new StatementRepository)->aggregate($this->getAuthority(), $pipeline)
    );
  }

  /**
   * Aggregates statements via the Mongo aggregation.
   * @return \Illuminate\Http\JsonResponse Aggregated statements.
   */
  public function analytics() {
    return IlluminateResponse::json(
      (new StatementRepository)->query($this->getAuthority(), LockerRequest::getParams())
    );
  }

}
