<?php

namespace Services;

use Lib\MongoDbWrapper;
use MongoDB\Operation\BulkWrite;
use function MongoDB\BSON\fromJSON;
use function MongoDB\BSON\toPHP;

class ImporterService
{
  /**
   * @var string
   */
  private $filename;

  public function __construct(MongoDbWrapper $mondgoDbWrapper) {
    $this->wrapper = $mondgoDbWrapper;
    $this->collectionService = new CollectionService($this->wrapper);
  }

  /**
   * @param $collectionName
   * @param $csv
   * @param $file
   * @param $jobNumber
   * @return int|null
   * @throws \Exception
   */
  public function import($collectionName, $csv, $file, $jobNumber) {
    if (!empty($file)) {
      if (!$this->wrapper->connect()) {
        throw new Exception('Unable to connect to MongoDB Server');
      }
      if ($this->wrapper->removeAllCollection($collectionName, $jobNumber) ) {
        return $this->wrapper->bulkWrite($csv, $collectionName);
      }
    }
    $filename = pathinfo($file, PATHINFO_BASENAME);
    throw new \Exception("The file $filename in the server, please upload retry again");
  }

}