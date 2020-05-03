<?php

namespace Services;

use Exception;
use Lib\FilterBuilder;
use Lib\MongoDbWrapper;

class CollectionService
{
  const PER_PAGE = 30;
  const SORT = 'id';
  /**
   * @var MondgoDbWrapper
   */
  private $wrapper;

  public function __construct(MongoDbWrapper $mondgoDbWrapper)
  {
    $this->wrapper = $mondgoDbWrapper;
  }

  /**
   * Get the count of documents for a given collection in the current database
   *
   * @param  string $collectionName
   * @return int
   * @throws Exception
   */
  public function getCollectionCount(string $collectionName): int
  {
    if (!$this->wrapper->connect()) {
      throw new Exception('Unable to connect to MongoDB Server');
    }
    return $this->wrapper->getCollectionCount($collectionName);
  }

  /**
   * Get a list of documents for a given collection in the current database with pagination and filters
   *
   * @param  string $collectionName
   * @param  array $pagination [page, per_page, sort]
   * @param  array $filter field/value pairs to filter the collection
   * @return array
   * @throws Exception
   */
  public function getCollection(string $collectionName, array $pagination = [], array $filter = []): array
  {
    if (!$this->wrapper->connect()) {
      throw new Exception('Unable to connect to MongoDB Server');
    }
    $pagination = $this->validatePagination($pagination);
    return $this->wrapper->getCollection($collectionName, $pagination, $filter);
  }

  /**
   * Get a list of documents for a given Id list
   *
   * @param  string $collectionName
   * @param  array $ids
   * @return array
   * @throws Exception
   */
  public function getCollectionByIds(string $collectionName, array $ids): array
  {
    if (!$this->wrapper->connect()) {
      throw new Exception('Unable to connect to MongoDB Server');
    }
    $pagination = $this->validatePagination([]);
    $idList = [];
    foreach($ids as $id) {
      $idList[] = new \MongoDB\BSON\ObjectId($id);
    }
    $filter = (new FilterBuilder())->in('_id', $idList)->getFilters();
    return $this->wrapper->getCollection($collectionName, $pagination, $filter);
  }

  /**
   * Get the headers for a given collection in the current database
   *
   * @param  string $collectionName
   * @return array
   * @throws Exception
   */
  public function getCollectionHeaders(string $collectionName): array
  {
    if (!$this->wrapper->connect()) {
      throw new Exception('Unable to connect to MongoDB Server');
    }
    $doc = $this->wrapper->get($collectionName, []);
    return array_keys($doc);
  }


  public function removeAllCollection(string $collectionName, $jobName): bool {
    if (!$this->wrapper->connect()) {
      throw new Exception('Unable to connect to MongoDB Server');
    }
    $this->wrapper->removeAllCollection($collectionName, $jobName);
    return true;
  }

  /**
   * @param array $_id
   * @param array $fields
   * @param string $collectionName
   * @return mixed
   * @throws Exception
   */
  public function updateOne(array $_id, array $fields, string $collectionName) {
    if (!$this->wrapper->connect()) {
      throw new Exception('Unable to connect to MongoDB Server');
    }
    $execute = ['$set' => $fields ];
    return $this->wrapper->updateOne($_id, $execute, $collectionName);
  }

  /**
   * Update a lot pages using bach operation
   * @param array $filters
   * @param array $fields
   * @param string $collectionName
   * @return mixed
   * @throws Exception
   */
  public function updateMany(array $filters, array $fields, string $collectionName) {
    if (!$this->wrapper->connect()) {
      throw new Exception('Unable to connect to MongoDB Server');
    }
    $execute = ['$set' => $fields ];
    return $this->wrapper->updateMany($filters, $execute, $collectionName);
  }
  /**
   * validate and set pagination
   *
   * @param  array $pagination
   * @return array
   */
  private function validatePagination(array $pagination): array
  {
    $validPagination = [
      'page' => 1,
      'per_page' => self::PER_PAGE,
      'sort' => self::SORT
    ];
    if (isset($pagination['page'])) {
      $validPagination['page'] = $pagination['page'];
    }
    if (isset($pagination['per_page'])) {
      $validPagination['per_page'] = $pagination['per_page'];
    }
    if (isset($pagination['sort'])) {
      $validPagination['sort'] = $pagination['sort'];
    }
    return $validPagination;
  }
}