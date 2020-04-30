<?php

namespace Services;

use Lib\MongoDbWrapper;
use MongoDB\Operation\BulkWrite;
use PHPUnit\Exception;
use function MongoDB\BSON\fromJSON;
use function MongoDB\BSON\toPHP;

class CrudService
{
  /**
   * @var string
   */
  public $wrapper;

  public function __construct(MongoDbWrapper $mondgoDbWrapper) {
    $this->wrapper = $mondgoDbWrapper;
  }

  /**
   * @param $collectionName
   * @param $csv
   * @return int|null
   * @throws \Exception
   */
  public function addRows($collectionName, $csv) {
    try {
      return $this->wrapper->bulkWrite($csv, $collectionName);
    } catch (Exception $e) {
      throw new \Exception("Cannot insert log row, please contact with your administrator");
    }
  }

}