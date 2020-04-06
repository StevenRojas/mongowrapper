<?php

namespace Lib;
use \MongoDB\BSON\Regex;

/**
 * Class to build filters for MongoDB collection
 */
class FilterBuilder
{
  private $filters = [];
  
  /**
   * Get created filters
   *
   * @return array
   */
  public function getFilters(): array
  {
    return $this->filters;
  }
    
  /**
   * Clear current filters
   *
   * @return void
   */
  public function clear(): FilterBuilder
  {
    $this->filters = [];
    return $this;
  }
  
  /**
   * Equal operator
   *
   * @param  string $field
   * @param  string $value
   * @return FilterBuilder
   */
  public function equal(string $field, string $value): FilterBuilder
  {
    $this->filters = array_merge($this->filters, [$field => $value]);
    return $this;
  }

  /**
   * Not Equal operator
   *
   * @param  string $field
   * @param  string $value
   * @return FilterBuilder
   */
  public function notEqual(string $field, string $value): FilterBuilder
  {
    $this->setFilterOperator($field, $value, '$ne');
    return $this;
  }

  /**
   * Between operator for string values
   *
   * @param  string $field
   * @param  string $startValue
   * @param  string $endValue
   * @return FilterBuilder
   */
  public function between(string $field, string $startValue, string $endValue): FilterBuilder
  {
    $this->filters = array_merge($this->filters, 
      [$field => ['$gte' => $startValue, '$lte' => $endValue]]);
    return $this;
  }

  /**
   * In operator
   *
   * @param  string $field
   * @param  array $values
   * @return FilterBuilder
   */
  public function in(string $field, array $values): FilterBuilder
  {
    $this->filters = array_merge($this->filters, [$field => ['$in' => $values]]);
    return $this;
  }

  /**
   * Not-in operator
   *
   * @param  string $field
   * @param  array $values
   * @return FilterBuilder
   */
  public function notIn(string $field, array $values): FilterBuilder
  {
    $this->filters = array_merge($this->filters, [$field => ['$nin' => $values]]);
    return $this;
  }

  /**
   * Greater Than operator
   *
   * @param  string $field
   * @param  string $value
   * @return FilterBuilder
   */
  public function greaterThan(string $field, string $value): FilterBuilder
  {
    $this->setFilterOperator($field, $value, '$gt');
    return $this;
  }

  /**
   * Greater Equal Than operator
   *
   * @param  string $field
   * @param  string $value
   * @return FilterBuilder
   */
  public function greaterEqualThan(string $field, string $value): FilterBuilder
  {
    $this->setFilterOperator($field, $value, '$gte');
    return $this;
  }

  /**
   * Less Than operator
   *
   * @param  string $field
   * @param  string $value
   * @return FilterBuilder
   */
  public function lessThan(string $field, string $value): FilterBuilder
  {
    $this->setFilterOperator($field, $value, '$lt');
    return $this;
  }

  /**
   * Less Equal Than operator
   *
   * @param  string $field
   * @param  string $value
   * @return FilterBuilder
   */
  public function lessEqualThan(string $field, string $value): FilterBuilder
  {
    $this->setFilterOperator($field, $value, '$lte');
    return $this;
  }

  /**
   * Like operator
   *
   * @param  string $field
   * @param  string $value
   * @return FilterBuilder
   */
  public function like(string $field, string $value): FilterBuilder
  {
    if (strlen($value) == 0) {
      return $this;
    }
    $regex = $this->getRegex($value);
    $this->filters = array_merge($this->filters, [$field => $regex]);
    return $this;
  }
  
  /**
   * Add operator
   *
   * @param  array $filters List of FilterBuilder instances that are joined by 'and' operator
   * @return FilterBuilder
   */
  public function and(array $filters): FilterBuilder
  {
    $andFilter = $this->glueFilters($filters, '$and');
    $this->filters = array_merge($this->filters, $andFilter);
    return $this;
  }

  /**
   * Or operator
   *
   * @param  array $filters List of FilterBuilder instances that are joined by 'and' operator
   * @return FilterBuilder
   */
  public function or(array $filters): FilterBuilder
  {
    $orFilter = $this->glueFilters($filters, '$or');
    $this->filters = array_merge($this->filters, $orFilter);
    return $this;
  }

  /**
   * Quick search. Apply 'like' style filter to all headers
   *
   * @param  array $headers
   * @param  string $value
   * @return FilterBuilder
   */
  public function quickSearch(array $headers, string $value): FilterBuilder
  {
    if (strlen($value) == 0 || count($headers) == 0) {
      return $this;
    }
    $regex = $this->getRegex($value);
    $filter = [];
    foreach($headers as $header) {
      $filter['$or'][] = [$header => $regex];
    }
    $this->filters = array_merge($this->filters, $filter);
    return $this;
  }
  
  /**
   * Get regex expression
   *
   * @param  string $value
   * @return Regex
   */
  private function getRegex(string $value): Regex
  {
    $value = $this->getStartDelimiter($value);
    $value = $this->getEndDelimiter($value);
    $value = str_replace('%', '.*', $value);
    return new Regex($value);
  }
  
  /**
   * Set a comparision operator to the filter
   *
   * @param  string $field
   * @param  string $value
   * @param  string $operator
   */
  private function setFilterOperator(string $field, string $value, string $operator): void
  {
    $this->filters = array_merge($this->filters, [$field => [$operator => $value]]);
  }

  /**
   * Get start delimiter for regex expression
   *
   * @param  string $value
   * @return string
   */
  private function getStartDelimiter(string $value): string
  {
    if ($value[0] === '%') {
      $value = ltrim($value, '%');
      $value = '.*' . $value;
    }
    else {
      $value = '^' . $value;
    }
    return $value;
  }

  /**
   * Get end delimiter for regex expression
   *
   * @param  string $value
   * @return string
   */
  private function getEndDelimiter(string $value): string
  {
    if ($value[strlen($value) - 1] === '%') {
      $value = rtrim($value, '%');
      $value .= '.*';
    }
    else {
      $value = $value . '$';
    }
    return $value;
  }
  
  /**
   * Glue filters by an operator ($and, $or)
   *
   * @param  array $filters List of FilterBuilder instances that are joined by an operator
   * @param  string $glue Glue operator
   * @return array
   */
  private function glueFilters(array $filters, string $glue): array
  {
    $gluedFilter = [$glue => []];
    foreach($filters as $filter) {
      if (!$filter instanceof FilterBuilder) {
        continue;
      }
      $gluedFilter[$glue][] = $filter->getFilters();
    }
    return $gluedFilter;
  }
}