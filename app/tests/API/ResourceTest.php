<?php namespace Tests\API;
use \Illuminate\Http\JsonResponse as JsonResponse;

abstract class ResourceTestCase extends TestCase {
  static protected $model_class = '...';
  protected $data = [];
  protected $model = null;

  public function setup() {
    parent::setup();
    $this->data = $this->constructData($this->data);
    $this->model = $this->createModel($this->data);
  }

  protected function constructData($data) {
    $data['lrs'] = $this->lrs->_id;
    return $data;
  }

  protected function createModel($data) {
    $model = new static::$model_class($data);
    $model->save();
    return $model;
  }

  public function testIndex() {
    $response = $this->requestAPI('GET', static::$endpoint);
    $content = $this->getContentFromResponse($response);
    $model = $this->getModelFromContent($content[0]);

    // Checks that the response is correct.
    $this->assertEquals(1, count($content), 'Incorrect number of models returned.');
    $this->assertEquals($this->data, $model, 'Incorrect model data returned.');
    $this->assertEquals(200, $response->getStatusCode(), 'Incorrect status code.');
  }

  public function testStore() {
    $response = $this->requestAPI('POST', static::$endpoint, json_encode($this->data));
    $model = $this->getModelFromResponse($response);

    // Checks that the response is correct.
    $this->assertEquals($this->data, $model, 'Incorrect model data returned.');
    $this->assertEquals(200, $response->getStatusCode(), 'Incorrect status code.');
  }

  public function testUpdate() {
    $data = array_merge($this->data, $this->update);
    $response = $this->requestAPI('PUT', static::$endpoint.'/'.$this->model->_id, json_encode($data));
    $model = $this->getModelFromResponse($response);

    // Checks that the response is correct.
    $this->assertEquals($data, $model, 'Incorrect model data returned.');
    $this->assertEquals(200, $response->getStatusCode(), 'Incorrect status code.');
  }

  public function testShow() {
    $response = $this->requestAPI('GET', static::$endpoint.'/'.$this->model->_id);
    $model = $this->getModelFromResponse($response);

    // Checks that the response is correct.
    $this->assertEquals($this->data, $model, 'Incorrect model data returned.');
    $this->assertEquals(200, $response->getStatusCode(), 'Incorrect status code.');
  }

  public function testDestroy() {
    $response = $this->requestAPI('DELETE', static::$endpoint.'/'.$this->model->_id);
    $content = $this->getContentFromResponse($response);

    // Checks that the response is correct.
    $this->assertEquals(null, $content, 'Incorrect data returned.');
    $this->assertEquals(204, $response->getStatusCode(), 'Incorrect status code.');
  }

  private function getModelFromResponse(JsonResponse $response) {
    return $this->getModelFromContent($this->getContentFromResponse($response));
  }

  protected function getModelFromContent(array $content) {
    unset($content['_id']);
    unset($content['created_at']);
    unset($content['updated_at']);
    return $content;
  }

  protected function getContentFromResponse(JsonResponse $response) {
    return json_decode($response->getContent(), true);
  }

  public function tearDown() {
    //$this->model->delete();
    parent::tearDown();
  }
}
