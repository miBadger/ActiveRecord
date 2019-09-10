<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord;

use miBadger\Query\Query;
use miBadger\Query\QueryInterface;
use miBadger\Query\QueryExpression;

/**
 * The active record exception class.
 *
 * @since 2.0.0
 */
class ActiveRecordQuery implements \IteratorAggregate
{
	private $instance;

	private $query;

	private $type;

	private $clauses = [];

	private $maxresultCount;

	private $results;
	
	private $whereExpression = null;

	private $limit;

	private $offset;

	private $orderBy;

	private $orderDirection;

	/**
	 * Constructs a new Active Record Query
	 */
	public function __construct(AbstractActiveRecord $instance, Array $additionalWhereClauses)
	{
		$this->instance = $instance;
		$this->query = new Query($instance->getPdo(), $instance->getTableName());
		$this->type = $instance;
		$this->clauses = $additionalWhereClauses;
		$this->maxResultCount = null;
		$this->results = null;
		$this->limit = null;
		$this->offset = null;
	}

	private function getWhereCondition()
	{
		$clauses = $this->clauses;

		// Optionally add user concatenated where expression
		if ($this->whereExpression !== null) {
			$clauses[] = $this->whereExpression;
		}

		// Construct where clause
		if (count($clauses) > 0) {
			return Query::AndArray($clauses);
		}
		return null;
	}

	/**
	 * Executes the query
	 */
	public function execute()
	{
		$whereCondition = $this->getWhereCondition();
		if ($whereCondition !== null) {
			$this->query->where($whereCondition);
		}

		$this->query->select();

		$this->results = $this->query->execute();

		return $this;
	}

	/**
	 * Returns an iterator for the result set
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->fetchAll());
	}

	/**
	 * returns the result set of ActiveRecord instances for this query
	 * @return Array
	 */
	public function fetchAll()
	{
		try {
			if ($this->results === null) {
				$this->execute();	
			}

			$entries = $this->results->fetchAll();
			if ($entries === false) {
				return [];
			}

			$typedResults = [];
			foreach ($entries as $entry) {
				$typedEntry = $this->type->newInstance();
				$typedEntry->fill($entry);
				$typedResults[] = $typedEntry;
			}

			return $typedResults;
		} catch (\PDOException $e) {
			throw new ActiveRecordException($e->getMessage(), 0, $e);
		}
	}

	public function fetchAllAsArray($readWhitelist)
	{
		$data = $this->fetchAll();
		$output = [];
		foreach ($data as $entry) {
			$output[] = $entry->toArray($readWhitelist);
		}
		return $output;
	}

	/**
	 * Fetch one record from the database
	 * @return AbstractActiveRecord 
	 */
	public function fetch()
	{
		try {
			if ($this->results === null) {
				$this->execute();
			}

			$typedResult = $this->type->newInstance();

			$entry = $this->results->fetch();
			if ($entry === false) {
				return null;
			}

			$typedResult->fill($entry);

			return $typedResult;
		} catch (\PDOException $e) {
			throw new ActiveRecordException($e->getMessage(), 0, $e);
		}
	}

	/**
	 * Fetch one record from the database and format it as an associative array, 
	 * 	 filtered by the entries in $readwhitelist
	 * @param Array $readWhitelist Array of whitelisted database column keys to be returned in the result
	 * @return Array|Null
	 */
	public function fetchAsArray($readWhitelist)
	{
		$res = $this->fetch();
		if ($res !== null) {
			return $res->toArray($readWhitelist);
		}
		return null;
	}

	public function countMaxResults()
	{
		if ($this->maxResultCount === null) {
			$query = new Query($this->instance->getPdo(), $this->instance->getTableName());
			$query->select(['count(*) as count']);

			$whereCondition = $this->getWhereCondition();
			if ($whereCondition !== null) {
				$query->where($whereCondition);
			}

			$this->maxResultCount = $query->execute()->fetch()['count'];
		}
		return $this->maxResultCount;
	}

	public function getNumberOfPages()
	{
		if ($this->limit === null) {
			return 1;
		}

		if ($this->limit === 0) {
			return 0;
		}

		$resultCount = $this->countMaxResults();
		if ($resultCount % $this->limit > 0) {
			return $resultCount / $this->limit + 1;
		}
		return $resultCount / $this->limit;
	}

	public function getCurrentPage()
	{
		if ($this->offset === null || $this->offset === 0) {
			return 1;
		}

		if ($this->limit === null || $this->limit === 0) {
			return 1;
		}

		return $this->offset / $this->limit;
	}

	/**
	 * Set the where condition
	 *
	 * @param QueryExpression $expression the query expression
	 * @return $this
	 * @see https://en.wikipedia.org/wiki/SQL#Operators
	 * @see https://en.wikipedia.org/wiki/Where_(SQL)
	 */
	public function where(QueryExpression $expression)
	{
		$this->whereExpression = $expression;
		return $this;
	}

	/**
	 * Set an additional group by.
	 *
	 * @param string $column
	 * @return $this
	 * @see https://en.wikipedia.org/wiki/SQL#Queries
	 */
	public function groupBy($column)
	{
		$this->query->groupBy($column);
		return $this;
	}

	/**
	 * Set an additional order condition.
	 *
	 * @param string $column
	 * @param string|null $order
	 * @return $this
	 * @see https://en.wikipedia.org/wiki/SQL#Queries
	 * @see https://en.wikipedia.org/wiki/Order_by
	 */
	public function orderBy($column, $order = null)
	{
		$this->query->orderBy($column, $order);	
		return $this;
	}

	/**
	 * Set the limit.
	 *
	 * @param mixed $limit
	 * @return $this
	 */
	public function limit($limit)
	{
		$this->limit = $limit;
		$this->query->limit($limit);
		return $this;
	}

	/**
	 * Set the offset.
	 *
	 * @param mixed $offset
 	 * @return $this
	 */
	public function offset($offset)
	{
		$this->offset = $offset;
		$this->query->offset($offset);
		return $this;
	}
}
