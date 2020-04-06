<?php

namespace Lib;

use Exception;
use MongoDB\Client;

class MongoDbWrapper
{
  /**
   * @var MongoDB\Client
   */
  protected $client = null;

  /**
   * @var string
   */
  protected $database = null;

  public function connect(): bool
  {
    $config = parse_ini_file(getcwd() . "/config/mongodb.ini");    
    $this->database = $config['database'];
    $uri = 'mongodb://' . $config['host'] . ':' . $config['port'];
    $options = [
      'username' => $config['user'],
      'password' => $config['pass'],
      'ssl' => false,
      'readPreference' => 'primary'
    ];

    try {
      $this->client = new Client($uri, $options);
      $this->client->listDatabases();
    }
    catch (Exception $e) {
      // TODO: Add logs
      var_dump($e->getMessage());
      return false;
    }
    return true;
  }

  /**
   * Get the count of documents for a given collection in the current database
   *
   * @param  string $collectionName
   * @return int
   */
  public function getCollectionCount(string $collectionName): int
  {
    $collection = $this->client->selectCollection($this->database, $collectionName);
    return $collection->count();
  }

  /**
   * Get a list of documents for a given collection in the current database with pagination and filters
   *
   * @param  string $collectionName
   * @param  array $pagination [page, per_page, sort]
   * @param  array $filter field/value pairs to filter the collection
   * @return array
   */
  public function getCollection(string $collectionName, array $pagination = [], array $filter = []): array
  {
    $skip = $pagination['per_page'] * ($pagination['page'] - 1);
    $limit = $pagination['per_page'];
    $sort = $pagination['sort'];
    if ($sort[0] == '-') {
      $sort = ltrim($sort, '-');
      $sortBy = [$sort => -1];
    }
    else {
      $sortBy = [$sort => 1];
    }

    $collection = $this->client->selectCollection($this->database, $collectionName);
    $options = [
      'skip' => $skip,
      'limit' => $limit,
      'projection' => [],
      'sort' => $sortBy
    ];
    $documents = $collection->find($filter, $options);
    $result = [];
    foreach ($documents as $document) {
      $doc = $document->getArrayCopy();
      $doc['_id'] = $doc['_id']->jsonSerialize()['$oid']; 
      $result[] = $doc;
    }
    $response = [
      'total_query' => $collection->count($filter),
      'doc_count' => count($result),
      'documents' => $result
    ];
    return $response;
  }

  /**
   * Get the first document for a given collection in the current database based on filters
   *
   * @param  string $collectionName
   * @param  array $filter field/value pairs to filter the collection
   * @return array
   */
  public function get(string $collectionName, array $filter = []): array
  {
    $collection = $this->client->selectCollection($this->database, $collectionName);
    $document = $collection->findOne($filter);
    if (!$document) {
      return [];
    }
    $doc = $document->getArrayCopy();
    $doc['_id'] = $doc['_id']->jsonSerialize()['$oid']; 
    return $doc;
  }
}