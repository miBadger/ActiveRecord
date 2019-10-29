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

	const CREATE = 'CREATE';
	const READ = 'READ';
	const UPDATE = 'UPDATE';
	const DELETE = 'DELETE';
	const SEARCH = 'SEARCH';

	/** @var \PDO The PDO object. */
	protected $pdo;

	/** @var null|int The ID. */
	private $id;

	/** @var array A map of column name to functions that hook the insert function */
	protected $createHooks;

	/** @var array A map of column name to functions that hook the read function */
	protected $readHooks;

	/** @var array A map of column name to functions that hook the update function */
	protected $updateHooks;

	/** @var array A map of column name to functions that hook the update function */
	protected $deleteHooks;	

	/** @var array A map of column name to functions that hook the search function */
	protected $searchHooks;

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

		$this->createHooks = [];
		$this->readHooks = [];
		$this->updateHooks = [];
		$this->deleteHooks = [];
		$this->searchHooks = [];
		$this->tableDefinition = $this->getTableDefinition();

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
			'properties' =>
				ColumnProperty::NOT_NULL
				| ColumnProperty::IMMUTABLE
				| ColumnProperty::AUTO_INCREMENT
				| ColumnProperty::PRIMARY_KEY
		];
	}

	private function checkHookConstraints($columnName, $hookMap)
	{
		// Check whether column exists
		if (!array_key_exists($columnName, $this->tableDefinition)) 
		{
			throw new ActiveRecordException("Hook is trying to register on non-existing column \"$columnName\"", 0);
		}

		// Enforcing 1 hook per table column
		if (array_key_exists($columnName, $hookMap)) {
			$message = "Hook is trying to register on an already registered column \"$columnName\", ";
			$message .= "do you have conflicting traits?";
			throw new ActiveRecordException($message, 0);
		}
	}

	public function registerHookOnAction($actionName, $columnName, $fn)
	{
		if (is_string($fn) && is_callable([$this, $fn])) {
			$fn = [$this, $fn];
		}

		if (!is_callable($fn)) { 
			throw new ActiveRecordException("Provided hook on column \"$columnName\" is not callable", 0);
		}

		switch ($actionName) {
			case self::CREATE:
				$this->checkHookConstraints($columnName, $this->createHooks);
				$this->createHooks[$columnName] = $fn;
				break;
			case self::READ:
				$this->checkHookConstraints($columnName, $this->readHooks);
				$this->readHooks[$columnName] = $fn;
				break;
			case self::UPDATE:
				$this->checkHookConstraints($columnName, $this->updateHooks);
				$this->updateHooks[$columnName] = $fn;
				break;
			case self::DELETE:
				$this->checkHookConstraints($columnName, $this->deleteHooks);
				$this->deleteHooks[$columnName] = $fn;
				break;
			case self::SEARCH:
				$this->checkHookConstraints($columnName, $this->searchHooks);
				$this->searchHooks[$columnName] = $fn;
				break;
			default:
				throw new ActiveRecordException("Invalid action: Can not register hook on non-existing action");
		}
	}

	/**
	 * Register a new hook for a specific column that gets called before execution of the create() method
	 * Only one hook per column can be registered at a time
	 * @param string $columnName The name of the column that is registered.
	 * @param string|callable $fn Either a callable, or the name of a method on the inheriting object.
	 */
	public function registerCreateHook($columnName, $fn)
	{
		$this->registerHookOnAction(self::CREATE, $columnName, $fn);
	}

	/**
	 * Register a new hook for a specific column that gets called before execution of the read() method
	 * Only one hook per column can be registered at a time
	 * @param string $columnName The name of the column that is registered.
	 * @param string|callable $fn Either a callable, or the name of a method on the inheriting object.
	 */
	public function registerReadHook($columnName, $fn)
	{
		$this->registerHookOnAction(self::READ, $columnName, $fn);
	}

	/**
	 * Register a new hook for a specific column that gets called before execution of the update() method
	 * Only one hook per column can be registered at a time
	 * @param string $columnName The name of the column that is registered.
	 * @param string|callable $fn Either a callable, or the name of a method on the inheriting object.
	 */
	public function registerUpdateHook($columnName, $fn)
	{
		$this->registerHookOnAction(self::UPDATE, $columnName, $fn);
	}

	/**
	 * Register a new hook for a specific column that gets called before execution of the delete() method
	 * Only one hook per column can be registered at a time
	 * @param string $columnName The name of the column that is registered.
	 * @param string|callable $fn Either a callable, or the name of a method on the inheriting object.
	 */
	public function registerDeleteHook($columnName, $fn)
	{
		$this->registerHookOnAction(self::DELETE, $columnName, $fn);
	}

	/**
	 * Register a new hook for a specific column that gets called before execution of the search() method
	 * Only one hook per column can be registered at a time
	 * @param string $columnName The name of the column that is registered.
	 * @param string|callable $fn Either a callable, or the name of a method on the inheriting object. The callable is required to take one argument: an instance of miBadger\Query\Query; 
	 */
	public function registerSearchHook($columnName, $fn)
	{
		$this->registerHookOnAction(self::SEARCH, $columnName, $fn);
	}

	/**
	 * Adds a new column definition to the table.
	 * @param string $columnName The name of the column that is registered.
	 * @param Array $definition The definition of that column.
	 */
	public function extendTableDefinition($columnName, $definition)
	{
		if ($this->tableDefinition === null) {
			throw new ActiveRecordException("tableDefinition is null, has parent been initialized in constructor?");
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
	 * Creates the entity as a table in the database
	 */
	public function createTable()
	{
		$this->pdo->query(SchemaBuilder::buildCreateTableSQL($this->getTableName(), $this->tableDefinition));
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
				$properties = $definition['properties'] ?? 0;

				if ($properties & ColumnProperty::NOT_NULL) {
					$constraintSql = SchemaBuilder::buildConstraintOnDeleteCascade($target->getTableName(), 'id', $this->getTableName(), $colName);
				} else {
					$constraintSql = SchemaBuilder::buildConstraintOnDeleteSetNull($target->getTableName(), 'id', $this->getTableName(), $colName);
				}

				$this->pdo->query($constraintSql);
			} else if (isset($definition['relation'])) {
				$msg = sprintf("Relation constraint on column \"%s\" of table \"%s\" does not contain a valid ActiveRecord instance", 
					$colName,
					$this->getTableName());
				throw new ActiveRecordException($msg);
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

	protected function insertDefaults()
	{
		// Insert default values for not-null fields
		foreach ($this->tableDefinition as $colName => $colDef) {
			if ($colDef['value'] === null
				&& ($colDef['properties'] ?? 0) & ColumnProperty::NOT_NULL
				&& isset($colDef['default'])) {
				$this->tableDefinition[$colName]['value'] = $colDef['default'];
			}
		}		
	}

	/**
	 * {@inheritdoc}
	 */
	public function create()
	{
		foreach ($this->createHooks as $colName => $fn) {
			$fn();
		}

		$this->insertDefaults();

		try {
			(new Query($this->getPdo(), $this->getTableName()))
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
		$whereConditions = [
			Query::Equal('id', $id)
		];
		foreach ($this->readHooks as $colName => $fn) {
			$cond = $fn();
			if ($cond !== null) {
				$whereConditions[] = $cond;
			}
		}

		try {
			$row = (new Query($this->getPdo(), $this->getTableName()))
				->select()
				->where(Query::AndArray($whereConditions))
				->execute()
				->fetch();
			
			if ($row === false) {
				throw new ActiveRecordException(sprintf('Can not read the non-existent active record entry %d from the `%s` table.', $id, $this->getTableName()));	
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
		foreach ($this->updateHooks as $colName => $fn) {
			$fn();
		}

		try {
			(new Query($this->getPdo(), $this->getTableName()))
				->update($this->getActiveRecordColumns())
				->where(Query::Equal('id', $this->getId()))
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
		foreach ($this->deleteHooks as $colName => $fn) {
			$fn();
		}

		try {
			(new Query($this->getPdo(), $this->getTableName()))
				->delete()
				->where(Query::Equal('id', $this->getId()))
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
	 * Returns the serialized form of the specified columns
	 * 
	 * @return Array
	 */
	public function toArray(Array $fieldWhitelist)
	{
		$output = [];
		foreach ($this->tableDefinition as $colName => $definition) {
			if (in_array($colName, $fieldWhitelist)) {
				$output[$colName] = $definition['value'];
			}
		}

		return $output;
	}

	/**
	 * {@inheritdoc}
	 */
	public function search(array $ignoredTraits = [])
	{
		$clauses = [];
		foreach ($this->searchHooks as $column => $fn) {
			if (!in_array($column, $ignoredTraits)) {
				$clauses[] = $fn();
			}
		}

		return new ActiveRecordQuery($this, $clauses);
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

	public function getFinalTableDefinition()
	{
		return $this->tableDefinition;
	}

	public function newInstance()
	{
		return new static($this->pdo);
	}

	/**
	 * Returns the active record table.
	 *
	 * @return string the active record table name.
	 */
	abstract public function getTableName(): string;

	/**
	 * Returns the active record columns.
	 *
	 * @return array the active record columns.
	 */
	abstract protected function getTableDefinition(): Array;
}
