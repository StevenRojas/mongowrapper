<?php

namespace Lib;

use Exception;
use MongoDB\Client;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\WriteConcern;

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
    $config = parse_ini_file(APPPATH . "libraries/mongowrapper/config/mongodb.ini");
    $this->database = $config['database'];
    $uri = 'mongodb://' . $config['host'] . ':' . $config['port'] . '/iviepages';
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

  /**
   * Remove the current content in participationList collection for replace later
   *
   * @param string $collectionName
   * @param $jobNumber
   * @return bool
   * @throws Exception
   */
  public function removeAllCollection(string $collectionName, $jobNumber): bool {
    try {
      $collection = $this->client->selectCollection($this->database, $collectionName);
      $deleteResult = $collection->deleteMany(['job' => $jobNumber]);
      \LyLogProcess::log("Deleted %d document(s) " . $deleteResult->getDeletedCount());
    } catch (Exception $e) {
      throw new Exception("It's not possible, truncate the database, please contact with you administrator");
    }

    return true;
  }

  /**
   * Write Bulk and execute the bulk queries
   *
   * @param $csv
   * @param $collectionName
   * @return int|null
   * @throws Exception
   */
  public function bulkWrite($csv, $collectionName) {
    try{
      $bulk = new BulkWrite(['ordered' => true]);
      foreach ($csv as $line) {
        $bulk->insert($line);
      }
      $config = parse_ini_file(APPPATH . "libraries/mongowrapper/config/mongodb.ini");
      $this->database = $config['database'];
      $uri = 'mongodb://' . $config['host'] . ':' . $config['port'] . '/iviepages';
      $options = $this->getOptions($config);
      $manager = new Manager($uri, $options);
      $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 6000);
      $result = $manager->executeBulkWrite($this->database . '.' . $collectionName, $bulk, $writeConcern);

      return $result->getInsertedCount();

    } catch (BulkWriteException $e) {
      throw new Exception($e->getWriteResult());
    } catch (\MongoDB\Driver\Exception\Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function updateOne(array $_id, $execute, $collectionName) {
    \LyLogProcess::log("Updating in :" . $this->database . " Collection:" . $collectionName );
    $collection = $this->client->selectCollection($this->database, $collectionName);
    return $collection->updateOne($_id, $execute);
  }


  private function getOptions($config) {
    return [
      'username' => $config['user'],
      'password' => $config['pass'],
      'ssl' => false,
      'readPreference' => 'primary'
    ];
  }
}
