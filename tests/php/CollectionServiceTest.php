<?php 

use PHPUnit\Framework\TestCase;
use Services\CollectionService;
use Lib\FilterBuilder;
use Lib\MongoDbWrapper;

class CollectionServiceTest extends TestCase
{
  private $collectionService = null;

  public function setUp(): void
  {
    $this->collectionService = new CollectionService(new MongoDbWrapper());
  }

  public function tearDown(): void
  {

  }

  public function testCollectionCount()
  {
    $count = $this->collectionService->getCollectionCount('stores');
    $this->assertTrue($count > 0);
  }

  public function testCollectionData()
  {
    $response = $this->collectionService->getCollection('stores');
    $documents = $response['documents'];
    $this->assertArrayHasKey('_id', $documents[0]);
    $this->assertEquals(count($documents), CollectionService::PER_PAGE);
  }

  public function testCollectionPaginationAndSort()
  {
    $perPage = 10;
    $pagination = [
      'page' => 2,
      'per_page' => $perPage,
      'sort' => '-store' // Sort by store desc
    ];
    $response = $this->collectionService->getCollection('stores', $pagination);
    $documents = $response['documents'];
    $this->assertArrayHasKey('_id', $documents[0]);
    $this->assertTrue($response['total_query'] > $response['doc_count']);
    $this->assertEquals($response['doc_count'], $perPage);
  }

  public function testCollectionFilter()
  {
    $perPage = 10;
    $pagination = [
      'page' => 1,
      'per_page' => $perPage
    ];

    $builder = new FilterBuilder();
    $builder
      ->equal('Inside', 'N3')
      ->equal('Store', '118');
    $storeFilter = $builder->getFilters();
    $response = $this->collectionService->getCollection('stores', $pagination, $storeFilter);
    $documents = $response['documents'];
    $this->assertArrayHasKey('_id', $documents[0]);
    $this->assertLessThan($perPage, count($documents));
  }

  public function testCollectionFilters()
  {
    $perPage = 10;
    $pagination = [
      'page' => 1,
      'per_page' => $perPage,
    ];
    $builder = new FilterBuilder();
    $builder
      ->equal('job', 'USS010350')
      ->notEqual('meat_type', 'BL')
      ->like('owner_group', '%FL%')
      ->greaterThan('region', 3)
      ->between('instore_version', 2, 7)
      ->in('store', ['101', '105', '108']);
    $participationListFilter = $builder->getFilters();
    $response = $this->collectionService->getCollection('participationList', $pagination, $participationListFilter);
    $documents = $response['documents'];
    //var_dump($documents); exit;
    $this->assertArrayHasKey('_id', $documents[0]);
  }

  public function testCollectionAndFilters()
  {
    $builder = new FilterBuilder();
    $builder
      ->equal('job', 'USS010350')
      ->and([
        (new FilterBuilder())->greaterEqualThan('store', '125'),
        (new FilterBuilder())->notIn('store', ['149', '159', '352'])
      ]);

    $participationListFilter = $builder->getFilters();
    $response = $this->collectionService->getCollection('participationList', [], $participationListFilter);
    $documents = $response['documents'];
    //var_dump($documents); exit;
    $this->assertArrayHasKey('_id', $documents[0]);
  }

  public function testCollectionOrFilters()
  {
    $pagination = [
      'page' => 1,
      'per_page' => 5,
    ];
    $builder = new FilterBuilder();
    $builder
      ->equal('job', 'USS010350')
      ->or([
        (new FilterBuilder())->in('store', ['101', '102', '105']),
        (new FilterBuilder())->equal('store', '310'),
        (new FilterBuilder())->equal('beer_wine', 'Mexican')
      ]);

    $participationListFilter = $builder->getFilters();
    $response = $this->collectionService->getCollection('participationList', $pagination, $participationListFilter);
    $documents = $response['documents'];
    //var_dump($documents); exit;
    $this->assertArrayHasKey('_id', $documents[0]);
  }

  public function testCollectionUnionFilters()
  {
    $builder1 = new FilterBuilder();
    $builder1
      ->equal('job', 'USS010350')
      ->and([
        (new FilterBuilder())->greaterEqualThan('store', '125'),
        (new FilterBuilder())->in('store', ['149', '159', '352'])
      ]);

    $builder2= new FilterBuilder();
    $builder2
      ->equal('job', 'USS010350')
      ->and([
        (new FilterBuilder())->greaterEqualThan('store', '400'),
        (new FilterBuilder())->in('store', ['786', '792', '352'])
      ]);

    $builder = new FilterBuilder();
    $builder->or([$builder1, $builder2]);
    $participationListFilter = $builder->getFilters();
    $response = $this->collectionService->getCollection('participationList', [], $participationListFilter);
    $documents = $response['documents'];
    //var_dump($documents); exit;
    $this->assertArrayHasKey('_id', $documents[0]);
  }

  // This works for export selected rows and filter selected rows
  public function testCollectionByIds()
  {
    $idList = ['5e7567cf62f8c9a8749ee19a', '5e7567cf62f8c9a8749ee19c', '5e7567cf62f8c9a8749ee1a2'];
    $response = $this->collectionService->getCollectionByIds('participationList', $idList);
    $documents = $response['documents'];
    //var_dump($documents); exit;
    $this->assertArrayHasKey('_id', $documents[0]);
  }

  public function testCollectionHeaders()
  {
    $headers = $this->collectionService->getCollectionHeaders('participationList');
    //var_dump($headers); exit;
  }

  public function testQuickSearch()
  {
    $pagination = [
      'page' => 1,
      'per_page' => 5
    ];
    $headers = $this->collectionService->getCollectionHeaders('participationList');
    $builder = new FilterBuilder();
    $builder
      ->equal('inside', 'D3')
      ->in('instore_version', ['3', '6', '9'])
      ->quickSearch($headers, 'Pepsi%');
    $quickSearchFilter = $builder->getFilters();
    $response = $this->collectionService->getCollection('participationList', $pagination, $quickSearchFilter);
    $documents = $response['documents'];
    var_dump($documents); exit;
  }

}