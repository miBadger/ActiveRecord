<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
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
	const COLUMN_NAME_ID = 'id';
	const COLUMN_TYPE_ID = 'INT UNSIGNED';

	/** @var \PDO The PDO object. */
	protected $pdo;

	/** @var null|int The ID. */
	private $id;

	/** @var array A map of column name to functions that hook the insert function */
	protected $registeredCreateHooks;

	/** @var array A map of column name to functions that hook the read function */
	protected $registeredReadHooks;

	/** @var array A map of column name to functions that hook the update function */
	protected $registeredUpdateHooks;

	/** @var array A map of column name to functions that hook the update function */
	protected $registeredDeleteHooks;	

	/** @var array A map of column name to functions that hook the search function */
	protected $registeredSearchHooks;

	/** @var array A list of table column definitions */
	protected $tableDefinition;

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
		$this->tableDefinition = $this->getActiveRecordTableDefinition();
		$this->registeredCreateHooks = [];
		$this->registeredReadHooks = [];
		$this->registeredUpdateHooks = [];
		$this->registeredDeleteHooks = [];
		$this->registeredSearchHooks = [];

		// Extend table definition with default ID field, throw exception if field already exists
		if (array_key_exists('id', $this->tableDefinition)) {
			$message = "Table definition in record contains a field with name \"id\"";
			$message .= ", which is a reserved name by ActiveRecord";
			throw new ActiveRecordException($message, 0);
		}

		$this->tableDefinition[self::COLUMN_NAME_ID] =
		[
			'value' => &$this->id,
			'validate' => null,
			'type' => self::COLUMN_TYPE_ID,
			'properties' => ColumnProperty::NOT_NULL | ColumnProperty::IMMUTABLE | ColumnProperty::AUTO_INCREMENT | ColumnProperty::PRIMARY_KEY
		];
	}

	/**
	 * Register a new hook for a specific column that gets called before execution of the create() method
	 * Only one hook per column can be registered at a time
	 * @param string $columnName The name of the column that is registered.
	 * @param string|callable $fn Either a callable, or the name of a method on the inheriting object.
	 */
	public function registerCreateHook($columnName, $fn)
	{
		// Check whether column exists
		if (!array_key_exists($columnName, $this->tableDefinition)) 
		{
			throw new ActiveRecordException("Hook is trying to register on non-existing column \"$columnName\"", 0);
		}

		// Enforcing 1 hook per table column
		if (array_key_exists($columnName, $this->registeredCreateHooks)) {
			$message = "Hook is trying to register on an already registered column \"$columnName\", ";
			$message .= "do you have conflicting traits?";
			throw new ActiveRecordException($message, 0);
		}

		if (is_string($fn) && is_callable([$this, $fn])) {
			$this->registeredCreateHooks[$columnName] = [$this, $fn];
		} else if (is_callable($fn)) {
			$this->registeredCreateHooks[$columnName] = $fn;
		} else {
			throw new ActiveRecordException("Provided hook on column \"$columnName\" is not callable", 0);
		}
	}

	/**
	 * Register a new hook for a specific column that gets called before execution of the read() method
	 * Only one hook per column can be registered at a time
	 * @param string $columnName The name of the column that is registered.
	 * @param string|callable $fn Either a callable, or the name of a method on the inheriting object.
	 */
	public function registerReadHook($columnName, $fn)
	{
		// Check whether column exists
		if (!array_key_exists($columnName, $this->tableDefinition)) 
		{
			throw new ActiveRecordException("Hook is trying to register on non-existing column \"$columnName\"", 0);
		}

		// Enforcing 1 hook per table column
		if (array_key_exists($columnName, $this->registeredReadHooks)) {
			$message = "Hook is trying to register on an already registered column \"$columnName\", ";
			$message .= "do you have conflicting traits?";
			throw new ActiveRecordException($message, 0);
		}

		if (is_string($fn) && is_callable([$this, $fn])) {
			$this->registeredReadHooks[$columnName] = [$this, $fn];
		} else if (is_callable($fn)) {
			$this->registeredReadHooks[$columnName] = $fn;
		} else {
			throw new ActiveRecordException("Provided hook on column \"$columnName\" is not callable", 0);
		}
	}

	/**
	 * Register a new hook for a specific column that gets called before execution of the update() method
	 * Only one hook per column can be registered at a time
	 * @param string $columnName The name of the column that is registered.
	 * @param string|callable $fn Either a callable, or the name of a method on the inheriting object.
	 */
	public function registerUpdateHook($columnName, $fn)
	{
		// Check whether column exists
		if (!array_key_exists($columnName, $this->tableDefinition)) 
		{
			throw new ActiveRecordException("Hook is trying to register on non-existing column \"$columnName\"", 0);
		}

		// Enforcing 1 hook per table column
		if (array_key_exists($columnName, $this->registeredUpdateHooks)) {
			$message = "Hook is trying to register on an already registered column \"$columnName\", ";
			$message .= "do you have conflicting traits?";
			throw new ActiveRecordException($message, 0);
		}

		if (is_string($fn) && is_callable([$this, $fn])) {
			$this->registeredUpdateHooks[$columnName] = [$this, $fn];
		} else if (is_callable($fn)) {
			$this->registeredUpdateHooks[$columnName] = $fn;
		} else {
			throw new ActiveRecordException("Provided hook on column \"$columnName\" is not callable", 0);
		}
	}

	/**
	 * Register a new hook for a specific column that gets called before execution of the delete() method
	 * Only one hook per column can be registered at a time
	 * @param string $columnName The name of the column that is registered.
	 * @param string|callable $fn Either a callable, or the name of a method on the inheriting object.
	 */
	public function registerDeleteHook($columnName, $fn)
	{
		// Check whether column exists
		if (!array_key_exists($columnName, $this->tableDefinition)) 
		{
			throw new ActiveRecordException("Hook is trying to register on non-existing column \"$columnName\"", 0);
		}

		// Enforcing 1 hook per table column
		if (array_key_exists($columnName, $this->registeredDeleteHooks)) {
			$message = "Hook is trying to register on an already registered column \"$columnName\", ";
			$message .= "do you have conflicting traits?";
			throw new ActiveRecordException($message, 0);
		}

		if (is_string($fn) && is_callable([$this, $fn])) {
			$this->registeredDeleteHooks[$columnName] = [$this, $fn];
		} else if (is_callable($fn)) {
			$this->registeredDeleteHooks[$columnName] = $fn;
		} else {
			throw new ActiveRecordException("Provided hook on column \"$columnName\" is not callable", 0);
		}
	}

	/**
	 * Register a new hook for a specific column that gets called before execution of the search() method
	 * Only one hook per column can be registered at a time
	 * @param string $columnName The name of the column that is registered.
	 * @param string|callable $fn Either a callable, or the name of a method on the inheriting object. The callable is required to take one argument: an instance of miBadger\Query\Query; 
	 */
	public function registerSearchHook($columnName, $fn)
	{
		// Check whether column exists
		if (!array_key_exists($columnName, $this->tableDefinition)) 
		{
			throw new ActiveRecordException("Hook is trying to register on non-existing column \"$columnName\"", 0);
		}

		// Enforcing 1 hook per table column
		if (array_key_exists($columnName, $this->registeredSearchHooks)) {
			$message = "Hook is trying to register on an already registered column \"$columnName\", ";
			$message .= "do you have conflicting traits?";
			throw new ActiveRecordException($message, 0);
		}

		if (is_string($fn) && is_callable([$this, $fn])) {
			$this->registeredSearchHooks[$columnName] = [$this, $fn];
		} else if (is_callable($fn)) {
			$this->registeredSearchHooks[$columnName] = $fn;
		} else {
			throw new ActiveRecordException("Provided hook on column \"$columnName\" is not callable", 0);
		}
	}

	/**
	 * Adds a new column definition to the table.
	 * @param string $columnName The name of the column that is registered.
	 * @param Array $definition The definition of that column.
	 */
	public function extendTableDefinition($columnName, $definition)
	{
		if ($this->tableDefinition === null) {
			throw new ActiveRecordException("tableDefinition is null, most likely due to parent class not having been initialized in constructor");
		}

		// Enforcing table can only be extended with new columns
		if (array_key_exists($columnName, $this->tableDefinition)) {
			$message = "Table is being extended with a column that already exists, ";
			$message .= "\"$columnName\" conflicts with your table definition";
			throw new ActiveRecordException($message, 0);
		}

		$this->tableDefinition[$columnName] = $definition;
	}

	/**
	 * Returns the type string as it should appear in the mysql create table statement for the given column
	 * @return string The type string
	 */
	private function getDatabaseTypeString($colName, $type, $length)
	{
		if ($type === null) 
		{
			throw new ActiveRecordException(sprintf("Column %s has invalid type \"NULL\"", $colName));
		}

		switch (strtoupper($type)) {
			case 'DATETIME':
			case 'DATE':
			case 'TIME':
			case 'TEXT':
			case 'INT UNSIGNED':
				return $type;

			case 'VARCHAR':
				if ($length === null) {
					throw new ActiveRecordException(sprintf("field type %s requires specified column field \"LENGTH\"", $colName));
				} else {
					return sprintf('%s(%d)', $type, $length);	
				}

			case 'INT':
			case 'TINYINT':
			case 'BIGINT':
			default: 	
			// @TODO(Default): throw exception, or implicitly assume that type is correct? (For when using SQL databases with different types)
				if ($length === null) {
					return $type;
				} else {
					return sprintf('%s(%d)', $type, $length);	
				}
		}
	}

	/**
	 * Builds the part of a MySQL create table statement that corresponds to the supplied column
	 * @param string $colName 	Name of the database column
	 * @param string $type 		The type of the string
	 * @param int $properties 	The set of Column properties that apply to this column (See ColumnProperty for options)
	 * @return string
	 */
	private function buildCreateTableColumnEntry($colName, $type, $length, $properties, $default)
	{

		$stmnt = sprintf('`%s` %s ', $colName, $this->getDatabaseTypeString($colName, $type, $length));
		if ($properties & ColumnProperty::NOT_NULL) {
			$stmnt .= 'NOT NULL ';
		} else {
			$stmnt .= 'NULL ';
		}

		if ($default !== NULL) {
			$stmnt .= ' DEFAULT ' . $default . ' ';
		}

		if ($properties & ColumnProperty::AUTO_INCREMENT) {
			$stmnt .= 'AUTO_INCREMENT ';
		}

		if ($properties & ColumnProperty::UNIQUE) {
			$stmnt .= 'UNIQUE ';
		}

		if ($properties & ColumnProperty::PRIMARY_KEY) {
			$stmnt .= 'PRIMARY KEY ';
		}

		return $stmnt;
	}

	/**
	 * Sorts the column statement components in the order such that the id appears first, 
	 * 		followed by all other columns in alphabetical ascending order
	 * @param   Array $colStatements Array of column statements
	 * @return  Array
	 */
	private function sortColumnStatements($colStatements)
	{
		// Find ID statement and put it first
		$sortedStatements = [];

		$sortedStatements[] = $colStatements[self::COLUMN_NAME_ID];
		unset($colStatements[self::COLUMN_NAME_ID]);

		// Sort remaining columns in alphabetical order
		$columns = array_keys($colStatements);
		sort($columns);
		foreach ($columns as $colName) {
			$sortedStatements[] = $colStatements[$colName];
		}

		return $sortedStatements;
	}

	/**
	 * Builds the MySQL Create Table statement for the internal table definition
	 * @return string
	 */
	public function buildCreateTableSQL()
	{
		$columnStatements = [];
		foreach ($this->tableDefinition as $colName => $definition) {
			// Destructure column definition
			$type    = $definition['type'] ?? null;
			$default = $definition['default'] ?? null;
			$length  = $definition['length'] ?? null;
			$properties = $definition['properties'] ?? null;

			if (isset($definition['relation']) && $type !== null) {
				$msg = "Column \"$colName\": ";
				$msg .= "Relationship columns have an automatically inferred type, so type should be omitted";
				throw new ActiveRecordException($msg);
			} else if (isset($definition['relation'])) {
				$type = self::COLUMN_TYPE_ID;
			}

			$columnStatements[$colName] = $this->buildCreateTableColumnEntry($colName, $type, $length, $properties, $default);
		}

		// Sort table (first column is id, the remaining are alphabetically sorted)
		$columnStatements = $this->sortColumnStatements($columnStatements);

		$sql = 'CREATE TABLE ' . $this->getActiveRecordTable() . ' ';
		$sql .= "(\n";
		$sql .= join($columnStatements, ",\n");
		$sql .= "\n);";

		return $sql;
	}

	/**
	 * Creates the entity as a table in the database
	 */
	public function createTable()
	{
		$this->pdo->query($this->buildCreateTableSQL());
	}

	/**
	 * builds a MySQL constraint statement for the given parameters
	 * @param string $parentTable
	 * @param string $parentColumn
	 * @param string $childTable
	 * @param string $childColumn
	 * @return string The MySQL table constraint string
	 */
	protected function buildConstraint($parentTable, $parentColumn, $childTable, $childColumn)
	{
		$template = <<<SQL
ALTER TABLE `%s`
ADD CONSTRAINT
FOREIGN KEY (`%s`)
REFERENCES `%s`(`%s`)
ON DELETE CASCADE;
SQL;
		return sprintf($template, $childTable, $childColumn, $parentTable, $parentColumn);
	}

	/**
	 * Iterates over the specified constraints in the table definition, 
	 * 		and applies these to the database.
	 */
	public function createTableConstraints()
	{
		// Iterate over columns, check whether "relation" field exists, if so create constraint
		foreach ($this->tableDefinition as $colName => $definition) {
			if (isset($definition['relation']) && $definition['relation'] instanceof AbstractActiveRecord) {
				// Forge new relation
				$target = $definition['relation'];
				$constraintSql = $this->buildConstraint($target->getActiveRecordTable(), 'id', $this->getActiveRecordTable(), $colName);

				$this->pdo->query($constraintSql);
			}
		}
	}

	/**
	 * Returns the name -> variable mapping for the table definition.
	 * @return Array The mapping
	 */
	protected function getActiveRecordColumns()
	{
		$bindings = [];
		foreach ($this->tableDefinition as $colName => $definition) {

			// Ignore the id column (key) when inserting or updating
			if ($colName == self::COLUMN_NAME_ID) {
				continue;
			}

			$bindings[$colName] = &$definition['value'];
		}
		return $bindings;
	}

	/**
	 * {@inheritdoc}
	 */
	public function create()
	{
		foreach ($this->registeredCreateHooks as $colName => $fn) {
			// @TODO: Would it be better to pass the Query to the function?
			$fn();
		}

		try {
			$q = (new Query($this->getPdo(), $this->getActiveRecordTable()))
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
		foreach ($this->registeredReadHooks as $colName => $fn) {
			// @TODO: Would it be better to pass the Query to the function?
			$fn();
		}

		try {
			$row = (new Query($this->getPdo(), $this->getActiveRecordTable()))
				->select()
				->where('id', '=', $id)
				->execute()
				->fetch();

			if ($row === false) {
				throw new ActiveRecordException(sprintf('Can not read the non-existent active record entry %d from the `%s` table.', $id, $this->getActiveRecordTable()));
			}

			$this->fill($row)->setId($id);
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
		foreach ($this->registeredUpdateHooks as $colName => $fn) {
			// @TODO: Would it be better to pass the Query to the function?
			$fn();
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
		foreach ($this->registeredDeleteHooks as $colName => $fn) {
			// @TODO: Would it be better to pass the Query to the function?
			$fn();
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
		$columns = $this->getActiveRecordColumns();
		$columns['id'] = &$this->id;

		foreach ($attributes as $key => $value) {
			if (array_key_exists($key, $columns)) {
				$columns[$key] = $value;
			}
		}

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function searchOne(array $where = [], array $orderBy = [])
	{
		try {
			$row = $this->getSearchQueryResult($where, $orderBy, 1)->fetch();

			if ($row === false) {
				throw new ActiveRecordException(sprintf('Can not search one non-existent entry from the `%s` table.', $this->getActiveRecordTable()));
			}

			return $this->fill($row)->setId($row['id']);
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

			foreach ($queryResult as $row) {
				$new = clone $this;

				$result[] = $new->fill($row)->setId($row['id']);
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

		// Ignore all trait modifiers for which a where clause was specified
		$registeredSearchHooks = $this->registeredSearchHooks;
		foreach ($where as $index => $clause) {
			$colName = $clause[0];
			unset($registeredSearchHooks[$colName]);
		}

		// Allow traits to modify the query
		foreach ($registeredSearchHooks as $column => $searchFunction) {
			$searchFunction($query);
		}

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
	abstract protected function getActiveRecordTableDefinition();
}
