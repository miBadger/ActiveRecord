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
			$pdoStatement->execute($this->getActiveRecordData());

			$this->setId(intval($this->getPdo()->lastInsertId()));
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not create a new active record entry in the `%s` table.', $this->getActiveRecordName()), 0, $e);
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
		try {
			$pdoStatement = $this->getPdo()->prepare($this->getReadQuery());
			$pdoStatement->execute(['id' => $id]);

			$this->setActiveRecordData($pdoStatement->fetch());
			$this->setId($id);
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not read active record entry %d from the `%s` table.', $id, $this->getActiveRecordName()), 0, $e);
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
		if (!$this->exists()) {
			throw new ActiveRecordException(sprintf('Can not update a non-existent active record entry to the `%s` table.', $this->getActiveRecordName()));
		}

		try {
			$pdoStatement = $this->getPdo()->prepare($this->getUpdateQuery());
			$pdoStatement->execute(['id' => $this->getId()] + $this->getActiveRecordData());
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not update active record entry %d to the `%s` table.', $this->getId(), $this->getActiveRecordName()), 0, $e);
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
		if (!$this->exists()) {
			throw new ActiveRecordException(sprintf('Can not delete a non-existent active record entry from the `%s` table.', $this->getActiveRecordName()));
		}

		try {
			$pdoStatement = $this->getPdo()->prepare($this->getDeleteQuery());
			$pdoStatement->execute(['id' => $this->getId()]);

			$this->setId(null);
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not delete active record entry %d from the `%s` table.', $this->getId(), $this->getActiveRecordName()), 0, $e);
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
		return sprintf('DELETE FROM %s WHERE `id` = :id', $this->getActiveRecordName());
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists()
	{
		return $this->getId() !== null;
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

	/**
	 * Set the active record data.
	 *
	 * @param array $fetch
	 * @return null
	 */
	protected function setActiveRecordData(array $fetch)
	{
		$data = $this->getActiveRecordData();

		foreach ($data as $key => &$value) {
			if (!isset($fetch[$key])) {
				throw new ActiveRecordException(sprintf('Can not read the expected column `%s`. It\'s not returnd by the `%s` table', $key, $this->getActiveRecordName()));
			}

			$value = $fetch[$key];
		}
	}
}
