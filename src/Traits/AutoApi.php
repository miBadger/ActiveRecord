<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\ActiveRecord\ColumnProperty;
use miBadger\ActiveRecord\ActiveRecordException;
use miBadger\Query\Query;

trait AutoApi
{
	/* =======================================================================
	 * ===================== Automatic API Support ===========================
	 * ======================================================================= */

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

	public function apiSearch(Array $queryparams, Array $fieldWhitelist)
	{
		// @TODO: Would it be better to not include the ignored_traits?
		$ignoredTraits = $queryparams['ignored_traits'] ?? [];
		$query = $this->search($ignoredTraits);

		$orderColumn = $queryparams['order_by'] ?? null;
		$orderDirection = $queryparams['order_direction'] ?? null;
		if ($orderColumn !== null) {
			$query->orderBy($orderColumn, $orderDirection);
		}
		
		$limit = $queryparams['limit'] ?? null;
		if ($limit !== null) {
			$query->limit($limit);
		}

		$offset = $queryparams['offset'] ?? null;
		if ($offset !== null) {
			$query->offset($offset);
		}

		$results = $query->fetchAll();

		$resultsArray = [];
		foreach ($results as $result) {
			$resultsArray[] = $result->toArray($fieldWhitelist);
		}

		return $resultsArray;
	}

	public function toArray($fieldWhitelist)
	{
		$output = [];
		foreach ($this->tableDefinition as $colName => $definition) {
			if (in_array($colName, $fieldWhitelist)) {
				$output[$colName] = $definition['value'];
			}
		}

		return $output;
	}

	public function apiRead($id, Array $fieldWhitelist)
	{
		$this->read($id);
		return $this->toArray($fieldWhitelist);
	}

	/* =============================================================
	 * ===================== Constraint validation =================
	 * ============================================================= */

	/**
	 * Copy all table variables between two instances
	 */
	private function syncInstances($to, $from)
	{
		foreach ($to->tableDefinition as $colName => $definition) {
			$definition['value'] = $from->tableDefinition[$colName]['value'];
		}
	}

	private function filterInputColumns($input, $whitelist)
	{
		$filteredInput = $input;
		foreach ($input as $colName => $value) {
			if (!in_array($colName, $whitelist)) {
				unset($filteredInput[$colName]);
			}
		}
		return $filteredInput;
	}

	private function validateExcessKeys($input)
	{
		$errors = [];
		foreach ($input as $colName => $value) {
			if (!array_key_exists($colName, $this->tableDefinition)) {
				$errors[$colName] = "Unknown input field";
				continue;
			}
		}
		return $errors;
	}

	private function validateImmutableColumns($input)
	{
		$errors = [];
		foreach ($this->tableDefinition as $colName => $definition) {
			$property = $definition['properties'] ?? null;
			if (array_key_exists($colName, $input)
				&& $property & ColumnProperty::IMMUTABLE) {
				$errors[$colName] = "Field cannot be changed";
			}
		}
		return $errors;
	}

	private function validateInputValues($input)
	{
		$errors = [];
		foreach ($this->tableDefinition as $colName => $definition) {
			// Validation check 1: If validate function is present
			if (array_key_exists($colName, $input) 
				&& is_callable($definition['validate'] ?? null)) {
				$inputValue = $input[$colName];

				// If validation function fails
				[$status, $message] = $definition['validate']($inputValue);
				if (!$status) {
					$errors[$colName] = $message;
				}	
			}

			// Validation check 2: If relation column, check whether entity exists
			$properties = $definition['properties'] ?? null;
			if (isset($definition['relation'])
				&& ($properties & ColumnProperty::NOT_NULL)) {
				$instance = clone $definition['relation'];
				try {
					$instance->read($input[$colName] ?? null);
				} catch (ActiveRecordException $e) {
					$errors[$colName] = "Entity for this value doesn't exist";
				}
			}
		}
		return $errors;
	}

	/**
	 * This function is only used for API Update calls (direct getter/setter functions are unconstrained)
	 */
	private function validateMissingKeys()
	{
		$errors = [];

		foreach ($this->tableDefinition as $colName => $colDefinition) {
			$default = $colDefinition['default'] ?? null;
			$properties = $colDefinition['properties'] ?? null;
			$value = $colDefinition['value'];

			// If nullable and default not set => null
			// If nullable and default null => default (null)
			// If nullable and default set => default (value)

			// if not nullable and default not set => error
			// if not nullable and default null => error
			// if not nullable and default st => default (value)
			// => if not nullable and default null and value not set => error message in this method
			if ($properties & ColumnProperty::NOT_NULL
				&& $default === null
				&& !($properties & ColumnProperty::AUTO_INCREMENT)
				// && !array_key_exists($colName, $input)
				&& $value === null) {
				$errors[$colName] = sprintf("The required field \"%s\" is missing", $colName);
			}
		}

		return $errors;
	}

	/**
	 * Copies the values for entries in the input with matching variable names in the record definition
	 * @param Array $input The input data to be loaded into $this record
	 */
	private function loadData($input)
	{
		foreach ($this->tableDefinition as $colName => $definition) {
			if (array_key_exists($colName, $input)) {
				$definition['value'] = $input[$colName];
			}
		}
	}

	/**
	 * @param Array $input Associative array of input values
	 * @param Array $fieldWhitelist array of column names that are allowed to be filled by the input array 
	 * @return Array Array containing the set of optional errors (associative array) and an optional array representation (associative)
	 * 					of the modified data.
	 */
	public function apiCreate($input, Array $fieldWhitelist)
	{
		// Clone $this to new instance (for restoring if validation goes wrong)
		$transaction = $this->newInstance();
		$errors = [];

		// Filter out all non-whitelisted input values
		$input = $this->filterInputColumns($input, $fieldWhitelist);

		// Validate excess keys
		$errors += $transaction->validateExcessKeys($input);

		// Validate input values (using validation function)
		$errors += $transaction->validateInputValues($input);

		// "Copy" data into transaction
		$transaction->loadData($input);

		// Run create hooks
		foreach ($this->registeredCreateHooks as $colName => $fn) {
			$fn();
		}

		// Validate missing keys
		$errors += $transaction->validateMissingKeys();

		// If no errors, commit the pending data
		if (empty($errors)) {
			$this->syncInstances($this, $transaction);

			try {
				(new Query($this->getPdo(), $this->getTableName()))
					->insert($this->getActiveRecordColumns())
					->execute();

				$this->setId(intval($this->getPdo()->lastInsertId()));
			} catch (\PDOException $e) {
				// @TODO: Potentially filter and store mysql messages (where possible) in error messages
				throw new ActiveRecordException($e->getMessage(), 0, $e);
			}

			return [null, $this->toArray($fieldWhitelist)];
		} else {
			return [$errors, null];
		}
	}

	/**
	 * @param Array $input Associative array of input values
	 * @param Array $fieldWhitelist array of column names that are allowed to be filled by the input array 
	 * @return Array Array containing the set of optional errors (associative array) and an optional array representation (associative)
	 * 					of the modified data.
	 */
	public function apiUpdate($input, Array $fieldWhitelist)
	{
		$transaction = $this->newInstance();
		$errors = [];

		// Filter out all non-whitelisted input values
		$input = $this->filterInputColumns($input, $fieldWhitelist);

		// Check for excess keys
		$errors += $transaction->validateExcessKeys($input);

		// Check for immutable keys
		$errors += $transaction->validateImmutableColumns($input);

		// Validate input values (using validation function)
		$errors += $transaction->validateInputValues($input);

		// "Copy" data into transaction
		$transaction->loadData($input);

		// Run create hooks
		foreach ($this->registeredUpdateHooks as $colName => $fn) {
			$fn();
		}

		// Validate missing keys
		$errors += $transaction->validateMissingKeys();

		// Update database
		if (empty($errors)) {
			$this->syncInstances($this, $transaction);

			try {
				(new Query($this->getPdo(), $this->getTableName()))
					->update($this->getActiveRecordColumns())
					->where(Query::Equal('id', $this->getId()))
					->execute();
			} catch (\PDOException $e) {
				throw new ActiveRecordException($e->getMessage(), 0, $e);
			}

			return [null, $this->toArray($fieldWhitelist)];
		} else {
			return [$errors, null];
		}
	}

	/**
	 * Returns this active record after reading the attributes from the entry with the given identifier.
	 *
	 * @param mixed $id
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	abstract public function read($id);

	/**
	 * Returns the PDO.
	 *
	 * @return \PDO the PDO.
	 */
	abstract public function getPdo();

	/**
	 * Set the ID.
	 *
	 * @param int $id
	 * @return $this
	 */
	abstract protected function setId($id);

	/**
	 * Returns the ID.
	 *
	 * @return null|int The ID.
	 */
	abstract protected function getId();

	/**
	 * Returns the active record table.
	 *
	 * @return string the active record table name.
	 */
	abstract protected function getTableName();

	/**
	 * Returns the name -> variable mapping for the table definition.
	 * @return Array The mapping
	 */
	abstract protected function getActiveRecordColumns();
}
