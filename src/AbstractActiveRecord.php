<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 * @version 1.0.0
 */

namespace miBadger\ActiveRecord;

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
	 * Construct an abstract pdo active record with the given pdo.
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
			$pdoStatement = $this->getPdo()->prepare($this->getCreateQuery());
			$pdoStatement->execute($this->getActiveRecordColumns());

			$this->setId(intval($this->getPdo()->lastInsertId()));
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not create a new active record entry in the `%s` table.', $this->getActiveRecordTable()), 0, $e);
		}

		return $this;
	}

	/**
	 * Returns the create query.
	 *
	 * @return string the create query.
	 */
	private function getCreateQuery()
	{
		$columns = array_keys($this->getActiveRecordColumns());
		$values = [];

		foreach ($columns as $key => $value) {
			$values[] = sprintf(':%s', $value);
		}

		return sprintf('INSERT INTO `%s` (`%s`) VALUES (%s)', $this->getActiveRecordTable(), implode('`, `', $columns), implode(', ', $values));
	}

	/**
	 * {@inheritdoc}
	 */
	public function read($id)
	{
		try {
			$pdoStatement = $this->getPdo()->prepare($this->getReadQuery());
			$pdoStatement->execute(['id' => $id]);
			$result = $pdoStatement->fetch();

			if ($result === false) {
				throw new ActiveRecordException(sprintf('Can not read the non-existent active record entry %d from the `%s` table.', $id, $this->getActiveRecordTable()));
			}

			$this->fill($result);
			$this->setId($id);
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not read active record entry %d from the `%s` table.', $id, $this->getActiveRecordTable()), 0, $e);
		}

		return $this;
	}

	/**
	 * Returns the read query.
	 *
	 * @return string the read query.
	 */
	private function getReadQuery()
	{
		return sprintf('SELECT * FROM `%s` WHERE `id` = :id', $this->getActiveRecordTable());
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
			$pdoStatement = $this->getPdo()->prepare($this->getUpdateQuery());
			$pdoStatement->execute(['id' => $this->getId()] + $this->getActiveRecordColumns());
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not update active record entry %d to the `%s` table.', $this->getId(), $this->getActiveRecordTable()), 0, $e);
		}

		return $this;
	}

	/**
	 * Returns the update query.
	 *
	 * @return string the update query.
	 */
	private function getUpdateQuery()
	{
		$values = [];

		foreach (array_keys($this->getActiveRecordColumns()) as $key => $value) {
			$values[] = sprintf('`%s` = :%s', $value, $value);
		}

		return sprintf('UPDATE `%s` SET %s WHERE `id` = :id', $this->getActiveRecordTable(), implode(', ', $values));
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
			$pdoStatement = $this->getPdo()->prepare($this->getDeleteQuery());
			$pdoStatement->execute(['id' => $this->getId()]);

			$this->setId(null);
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not delete active record entry %d from the `%s` table.', $this->getId(), $this->getActiveRecordTable()), 0, $e);
		}

		return $this;
	}

	/**
	 * Returns the delete query.
	 *
	 * @return string the delete query.
	 */
	private function getDeleteQuery()
	{
		return sprintf('DELETE FROM `%s` WHERE `id` = :id', $this->getActiveRecordTable());
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
	 * Fill the active record
	 *
	 * @param array $fetch
	 * @return null
	 */
	public function fill(array $fetch)
	{
		$data = $this->getActiveRecordColumns();

		foreach ($data as $key => &$value) {
			if (!array_key_exists($key, $fetch)) {
				throw new ActiveRecordException(sprintf('Can not read the expected column `%s`. It\'s not returnd by the `%s` table', $key, $this->getActiveRecordTable()));
			}

			$value = $fetch[$key];
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function searchFirst(array $where = [], array $orderBy = [])
	{
		try {
			$pdoStatement = $this->getPdo()->prepare($this->getSearchQuery($where, $orderBy, 1, 0));
			array_walk_recursive($where, function(&$value) use ($pdoStatement) {
				static $index = 1;

				$pdoStatement->bindParam($index++, $value);
			});

			$pdoStatement->execute();
			$fetch = $pdoStatement->fetch();

			$this->setId(intval($fetch['id']));
			$this->fill($fetch);

			return $this;
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not search the record in the `%s` table.', $this->getActiveRecordTable()), 0, $e);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function search(array $where = [], array $orderBy = [], $limit = -1, $offset = 0)
	{
		try {
			$pdoStatement = $this->getPdo()->prepare($this->getSearchQuery($where, $orderBy, $limit, $offset));
			array_walk_recursive($where, function(&$value) use ($pdoStatement) {
				static $index = 1;

				$pdoStatement->bindParam($index++, $value);
			});

			$pdoStatement->execute();
			$result = [];

			while ($fetch = $pdoStatement->fetch()) {
				$new = new static($this->getPdo());

				$new->setId(intval($fetch['id']));
				$new->fill($fetch);

				$result[] = $new;
			}

			return $result;
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not search the record in the `%s` table.', $this->getActiveRecordTable()), 0, $e);
		}
	}

	/**
	 * Returns the search query with the given where, order by, limit and offset clauses.
	 *
	 * @param array $where = []
	 * @param array $orderBy = []
	 * @param int $limit = -1
	 * @param int $offset = 0
	 * @return string the search query with the given where, order by, limit and offset clauses.
	 */
	private function getSearchQuery($where = [], $orderBy = [], $limit = -1, $offset = 0)
	{
		return sprintf(
			'SELECT * FROM `%s` %s %s %s',
			$this->getActiveRecordTable(),
			$this->getSearchQueryWhereClauses($where),
			$this->getSearchQueryOrderByClause($orderBy),
			$this->getSearchQueryLimitClause($limit, $offset)
		);
	}

	/**
	 * Returns the search query where clauses.
	 *
	 * @param array $where
	 * @return string the search query where clauses.
	 */
	private function getSearchQueryWhereClauses($where)
	{
		$columns = array_keys($this->getActiveRecordColumns());
		$columns[] = 'id';
		$result = [];

		foreach ($where as $key => $value) {
			if (!in_array($key, $columns)) {
				throw new ActiveRecordException(sprintf('Search attribute `%s` does not exists.', $key));
			}

			$result[] = $this->getSearchQueryWhereClause($key, $value);
		}

		return empty($result) ? '' : 'WHERE ' . implode(' AND ', $result);
	}

	/**
	 * Returns the search query where clause.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return string the search query where clause.
	 */
	private function getSearchQueryWhereClause($key, $value)
	{
		if (is_numeric($value)) {
			return sprintf('`%s` = ?', $key);
		} elseif (is_string($value)) {
			return sprintf('`%s` LIKE ?', $key);
		} elseif (is_null($value)) {
			return sprintf('`%s` IS ?', $key);
		} elseif (is_array($value) && !empty($value)) {
			return sprintf('`%s` IN (%s)', $key, implode(',', array_fill(0, count($value), '?')));
		}

		throw new ActiveRecordException(sprintf('Search attribute `%s` contains an unsupported type `%s`.', $key, gettype($value)));
	}

	/**
	 * Returns the search query order by clause.
	 *
	 * @param array $orderBy
	 * @return string the search query order by clause.
	 */
	private function getSearchQueryOrderByClause($orderBy)
	{
		$result = [];

		foreach ($orderBy as $key => $value) {
			$result[] = sprintf('`%s` %s', $key, $value == 'DESC' ? 'DESC' : 'ASC');
		}

		return empty($result) ? '' : 'ORDER BY ' . implode(', ', $result);
	}

	/**
	 * Returns the search query limit and clause.
	 *
	 * @param int $limit = -1
	 * @param int $offset = 0
	 * @return string the search query limit and clause.
	 */
	private function getSearchQueryLimitClause($limit, $offset)
	{
		if ($limit == -1) {
			return '';
		}

		return sprintf('LIMIT %d OFFSET %d', $limit, $offset);
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
