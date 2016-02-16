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
		$this->pdo = $pdo;
		$this->id = null;

		// TODO enable PDO exceptions?
	}

	/**
	 * {@inheritdoc}
	 */
	public function create()
	{
		$pdoStatement = $this->pdo->prepare($this->getCreateQuery());
		$pdoStatement->execute($this->getActiveRecordData());

		$this->id = intval($this->pdo->lastInsertId());
		return $this;
	}

	/**
	 * Returns the create query.
	 *
	 * @return string the create query.
	 */
	private function getCreateQuery()
	{
		$columns = array_keys($this->getActiveRecordData());
		$values = [];

		foreach ($columns as $key => $value) {
			$values[] = ':' . $value;
		}

		return sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->getActiveRecordName(), implode(', ', $columns), implode(', ', $values));
	}

	/**
	 * {@inheritdoc}
	 */
	public function read($id)
	{
		$data = $this->getActiveRecordData();

		$pdoStatement = $this->pdo->prepare($this->getReadQuery());
		$pdoStatement->execute(['id' => $id]);
		$result = $pdoStatement->fetch(\PDO::FETCH_ASSOC);

		$this->id = $id;

		foreach ($data as $key => &$value) {
			$value = $result[$key];
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
		return sprintf('SELECT * FROM `%s` WHERE `id` = :id', $this->getActiveRecordName());
	}

	/**
	 * {@inheritdoc}
	 */
	public function update()
	{
		$pdoStatement = $this->pdo->prepare($this->getUpdateQuery());
		$pdoStatement->execute(['id' => $this->id] + $this->getActiveRecordData());

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

		foreach (array_keys($this->getActiveRecordData()) as $key => $value) {
			$values[] = $value . ' = :' . $value;
		}

		return sprintf('UPDATE %s SET %s WHERE `id` = :id', $this->getActiveRecordName(), implode(', ', $values));
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete()
	{
		$pdoStatement = $this->pdo->prepare($this->getDeleteQuery());
		$pdoStatement->execute(['id' => $this->id]);

		$this->id = null;
		return $this;
	}

	/**
	 * Returns the delete query.
	 *
	 * @return string the delete query.
	 */
	private function getDeleteQuery()
	{
		return sprintf('SELECT * FROM `%s` WHERE `id` = :id', $this->getActiveRecordName());
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists()
	{
		return $this->id !== null;
	}

	/**
	 * Returns the PDO.
	 *
	 * @return PDO the PDO.
	 */
	public function getPdo()
	{
		return $this->pdo;
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
	 * Returns the active record name.
	 *
	 * @return string the active record name.
	 */
	abstract protected function getActiveRecordName();

	/**
	 * Returns the active record data.
	 *
	 * @return array the active record data.
	 */
	abstract protected function getActiveRecordData();
}
