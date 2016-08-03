<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 * @version 1.0.0
 */

namespace miBadger\ActiveRecord;

use miBadger\Query\Query;

/**
 * The abstract active record class.
 *
 * @since 1.0.0
 */
abstract class AbstractActiveRecord implements ActiveRecordInterface
{
	/** @var \PDO The PDO object. */
	private $pdo;

	/** @var null|int The ID. */
	private $id;

	/**
	 * Construct an abstract active record with the given PDO.
	 *
	 * @param \PDO $pdo
	 */
	public function __construct(\PDO $pdo)
	{
		$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$this->setPdo($pdo);
	}

	/**
	 * {@inheritdoc}
	 */
	public function create()
	{
		try {
			(new Query($this->getPdo(), $this->getActiveRecordTable()))
				->insert($this->getActiveRecordColumns())
				->execute();

			$this->setId(intval($this->getPdo()->lastInsertId()));
		} catch (\PDOException $e) {
			throw new ActiveRecordException($e->getMessage(), 0, $e);
		}

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function read($id)
	{
		try {
			$result = (new Query($this->getPdo(), $this->getActiveRecordTable()))
				->select()
				->where('id', '=', $id)
				->execute()
				->fetch();

			if ($result === false) {
				throw new ActiveRecordException(sprintf('Can not read the non-existent active record entry %d from the `%s` table.', $id, $this->getActiveRecordTable()));
			}

			$this->fill($result);
		} catch (\PDOException $e) {
			throw new ActiveRecordException($e->getMessage(), 0, $e);
		}

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function update()
	{
		if (!$this->exists()) {
			throw new ActiveRecordException(sprintf('Can not update a non-existent active record entry to the `%s` table.', $this->getActiveRecordTable()));
		}

		try {
			(new Query($this->getPdo(), $this->getActiveRecordTable()))
				->update($this->getActiveRecordColumns())
				->where('id', '=', $this->getId())
				->execute();
		} catch (\PDOException $e) {
			throw new ActiveRecordException($e->getMessage(), 0, $e);
		}

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete()
	{
		if (!$this->exists()) {
			throw new ActiveRecordException(sprintf('Can not delete a non-existent active record entry from the `%s` table.', $this->getActiveRecordTable()));
		}

		try {
			(new Query($this->getPdo(), $this->getActiveRecordTable()))
				->delete()
				->where('id', '=', $this->getId())
				->execute();

			$this->setId(null);
		} catch (\PDOException $e) {
			throw new ActiveRecordException($e->getMessage(), 0, $e);
		}

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function sync()
	{
		if (!$this->exists()) {
			return $this->create();
		}

		return $this->update();
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists()
	{
		return $this->getId() !== null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function fill(array $attributes)
	{
		if (isset($attributes['id'])) {
			$this->setId($attributes['id']);
		}

		foreach ($this->getActiveRecordColumns() as $key => &$value) {
			if (!array_key_exists($key, $attributes)) {
				throw new ActiveRecordException(sprintf('Can not read the expected column `%s`. It\'s not returnd by the `%s` table', $key, $this->getActiveRecordTable()));
			}

			$value = $attributes[$key];
		}

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function searchOne(array $where = [], array $orderBy = [])
	{
		try {
			$result = $this->getSearchQueryResult($where, $orderBy, 1)->fetch();

			if ($result === false) {
				throw new ActiveRecordException(sprintf('Can not search one non-existent entry from the `%s` table.', $this->getActiveRecordTable()));
			}

			return $this->fill($result);
		} catch (\PDOException $e) {
			throw new ActiveRecordException($e->getMessage(), 0, $e);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function search(array $where = [], array $orderBy = [], $limit = -1, $offset = 0)
	{
		try {
			$queryResult = $this->getSearchQueryResult($where, $orderBy, $limit, $offset);
			$result = [];

			foreach ($queryResult as $fetch) {
				$new = clone $this;

				$result[] = $new->fill($fetch);
			}

			return $result;
		} catch (\PDOException $e) {
			throw new ActiveRecordException($e->getMessage(), 0, $e);
		}
	}

	/**
	 * Returns the search query result with the given where, order by, limit and offset clauses.
	 *
	 * @param array $where = []
	 * @param array $orderBy = []
	 * @param int $limit = -1
	 * @param int $offset = 0
	 * @return \miBadger\Query\QueryResult the search query result with the given where, order by, limit and offset clauses.
	 */
	private function getSearchQueryResult(array $where = [], array $orderBy = [], $limit = -1, $offset = 0)
	{
		$query = (new Query($this->getPdo(), $this->getActiveRecordTable()))
			->select();

		$this->getSearchQueryWhere($query, $where);
		$this->getSearchQueryOrderBy($query, $orderBy);
		$this->getSearchQueryLimit($query, $limit, $offset);

		return $query->execute();
	}

	/**
	 * Returns the given query after adding the given where conditions.
	 *
	 * @param \miBadger\Query\Query $query
	 * @param array $where
	 * @return \miBadger\Query\Query the given query after adding the given where conditions.
	 */
	private function getSearchQueryWhere($query, $where)
	{
		foreach ($where as $key => $value) {
			$query->where($value[0], $value[1], $value[2]);
		}

		return $query;
	}

	/**
	 * Returns the given query after adding the given order by conditions.
	 *
	 * @param \miBadger\Query\Query $query
	 * @param array $orderBy
	 * @return \miBadger\Query\Query the given query after adding the given order by conditions.
	 */
	private function getSearchQueryOrderBy($query, $orderBy)
	{
		foreach ($orderBy as $key => $value) {
			$query->orderBy($key, $value);
		}

		return $query;
	}

	/**
	 * Returns the given query after adding the given limit and offset conditions.
	 *
	 * @param \miBadger\Query\Query $query
	 * @param int $limit
	 * @param int $offset
	 * @return \miBadger\Query\Query the given query after adding the given limit and offset conditions.
	 */
	private function getSearchQueryLimit($query, $limit, $offset)
	{
		if ($limit > -1) {
			$query->limit($limit);
			$query->offset($offset);
		}

		return $query;
	}

	/**
	 * Returns the PDO.
	 *
	 * @return \PDO the PDO.
	 */
	public function getPdo()
	{
		return $this->pdo;
	}

	/**
	 * Set the PDO.
	 *
	 * @param \PDO $pdo
	 * @return $this
	 */
	protected function setPdo($pdo)
	{
		$this->pdo = $pdo;

		return $this;
	}

	/**
	 * Returns the ID.
	 *
	 * @return null|int The ID.
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set the ID.
	 *
	 * @param int $id
	 * @return $this
	 */
	protected function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Returns the active record table.
	 *
	 * @return string the active record table.
	 */
	abstract protected function getActiveRecordTable();

	/**
	 * Returns the active record columns.
	 *
	 * @return array the active record columns.
	 */
	abstract protected function getActiveRecordColumns();
}
