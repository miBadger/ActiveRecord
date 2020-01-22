<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\ActiveRecord\ColumnProperty;
use miBadger\ActiveRecord\ActiveRecordException;
use miBadger\Query\Query;
use miBadger\Query\QueryExpression;

trait AutoApi
{
	/* =======================================================================
	 * ===================== Automatic API Support ===========================
	 * ======================================================================= */

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
	 * @param Array $queryparams associative array of query params. Reserved options are
	 *                             "search_order_by", "search_order_direction", "search_limit", "search_offset"
	 *                             or column names corresponding to an instance of miBadger\Query\QueryExpression
	 * @param Array $fieldWhitelist names of the columns that will appear in the output results
	 * 
	 * @return Array an associative array containing the query parameters, and a data field containing an array of search results (associative arrays indexed by the keys in $fieldWhitelist)
	 */
	public function apiSearch(Array $queryParams, Array $fieldWhitelist, ?QueryExpression $whereClause = null, int $maxResultLimit = 100): Array
	{
		$query = $this->search();

		// Build query
		$orderColumn = $queryParams['search_order_by'] ?? null;
		if (!in_array($orderColumn, $fieldWhitelist)) {
			$orderColumn = null;
		}

		$orderDirection = $queryParams['search_order_direction'] ?? null;
		if ($orderColumn !== null) {
			$query->orderBy($orderColumn, $orderDirection);
		}
		
		if ($whereClause !== null) {
			$query->where($whereClause);
		}

		$limit = min((int) ($queryParams['search_limit'] ?? $maxResultLimit), $maxResultLimit);
		$query->limit($limit);

		$offset = $queryParams['search_offset'] ?? 0;
		$query->offset($offset);

		$numPages = $query->getNumberOfPages();
		$currentPage = $query->getCurrentPage();

		// Fetch results
		$results = $query->fetchAll();
		$resultsArray = [];
		foreach ($results as $result) {
			$resultsArray[] = $result->toArray($fieldWhitelist);
		}

		return [
			'search_offset' => $offset,
			'search_limit' => $limit,
			'search_order_by' => $orderColumn,
			'search_order_direction' => $orderDirection,
			'search_pages' => $numPages,
			'search_current' => $currentPage,
			'data' => $resultsArray
		];
	}

	/**
	 * Performs a read call on the entity (modifying the current object) 
	 * 	and returns a result array ($error, $data), with an optional error, and the results array (as filtered by the whitelist)
	 * 	containing the loaded data.
	 * 
	 * @param string|int $id the id of the current entity
	 * @param Array $fieldWhitelist an array of fields that are allowed to appear in the output
	 * 
	 * @param Array [$error, $result]
	 * 				Where $error contains the error message (@TODO: & Error type?)
	 * 				Where result is an associative array containing the data for this record, and the keys are a subset of $fieldWhitelist
	 * 				
	 */
	public function apiRead($id, Array $fieldWhitelist = []): Array
	{
		try {
			$this->read($id);	
		} catch (ActiveRecordException $e) {
			if ($e->getCode() === ActiveRecordException::NOT_FOUND) {
				$err = [
					'type' => 'invalid',
					'message' => $e->getMessage()
				];
				return [$err, null];
			}
			throw $e;
		}
		return [null, $this->toArray($fieldWhitelist)];
	}

	/* =============================================================
	 * ===================== Constraint validation =================
	 * ============================================================= */

	/**
	 * Copy all table variables between two instances
	 */
	public function syncInstanceFrom($from)
	{
		foreach ($this->tableDefinition as $colName => $definition) {
			$this->tableDefinition[$colName]['value'] = $from->tableDefinition[$colName]['value'];
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
				$errors[$colName] = [
					'type' => 'unknown_field',
					'message' => 'Unknown input field'
				];
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
				$errors[$colName] = [
					'type' => 'immutable',
					'message' => 'Value cannot be changed'
				];
			}
		}
		return $errors;
	}

	/**
	 * Checks whether input values are correct:
	 * 1. Checks whether a value passes the validation function for that column
	 * 2. Checks whether a value supplied to a relationship column is a valid value
	 */
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
					$errors[$colName] = [
						'type' => 'invalid',
						'message' => $message
					];
				}	
			}

			// Validation check 2: If relation column, check whether entity exists
			$properties = $definition['properties'] ?? null;
			if (isset($definition['relation'])
				&& ($properties & ColumnProperty::NOT_NULL)) {
				$instance = clone $definition['relation'];
				try {
					$instance->read($input[$colName] ?? $definition['value'] ?? null);
				} catch (ActiveRecordException $e) {
					$errors[$colName] = [
						'type' => 'invalid',
						'message' => 'Entry for this value does not exist'
					];
				}
			}
		}
		return $errors;
	}

	/**
	 * This function is only used for API Update calls (direct getter/setter functions are unconstrained)
	 * Determines whether there are required columns for which no data is provided
	 */
	private function validateMissingKeys($input)
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
			// => if not nullable and default null and value not set (or null) => error message in this method
			if ($properties & ColumnProperty::NOT_NULL
				&& $default === null
				&& !($properties & ColumnProperty::AUTO_INCREMENT)
				&& (!array_key_exists($colName, $input) 
					|| $input[$colName] === null 
					|| (is_string($input[$colName]) && $input[$colName] === '') )
				&& ($value === null
					|| (is_string($value) && $value === ''))) {
				$errors[$colName] = [
					'type' => 'missing',
					'message' => sprintf("The required field \"%s\" is missing", $colName)
				];
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
			// Skip if this table column does not appear in the input
			if (!array_key_exists($colName, $input)) {
				continue;
			}

			// Use setter if known, otherwise set value directly
			$fn = $definition['setter'] ?? null;
			if (is_callable($fn)) {
				$fn($input[$colName]);
			} else {
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
	public function apiCreate(Array $input, Array $createWhitelist, Array $readWhitelist)
	{
		// Clone $this to new instance (for restoring if validation goes wrong)
		$transaction = $this->newInstance();
		$errors = [];

		// Filter out all non-whitelisted input values
		$input = $this->filterInputColumns($input, $createWhitelist);

		// Validate excess keys
		$errors += $transaction->validateExcessKeys($input);

		// Validate input values (using validation function)
		$errors += $transaction->validateInputValues($input);

		// "Copy" data into transaction
		$transaction->loadData($input);

		// Run create hooks
		foreach ($transaction->createHooks as $colName => $fn) {
			$fn();
		}

		// Validate missing keys
		$errors += $transaction->validateMissingKeys($input);

		// If no errors, commit the pending data
		if (empty($errors)) {
			$this->syncInstanceFrom($transaction);

			// Insert default values for not-null fields
			$this->insertDefaults();

			try {
				(new Query($this->getPdo(), $this->getTableName()))
					->insert($this->getActiveRecordColumns())
					->execute();

				$this->setId(intval($this->getPdo()->lastInsertId()));
			} catch (\PDOException $e) {
				// @TODO: Potentially filter and store mysql messages (where possible) in error messages
				throw new ActiveRecordException($e->getMessage(), 0, $e);
			}

			return [null, $this->toArray($readWhitelist)];
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
	public function apiUpdate(Array $input, Array $updateWhitelist, Array $readWhitelist)
	{
		$transaction = $this->newInstance();
		$transaction->syncInstanceFrom($this);
		$errors = [];

		// Filter out all non-whitelisted input values
		$input = $this->filterInputColumns($input, $updateWhitelist);

		// Check for excess keys
		$errors += $transaction->validateExcessKeys($input);

		// Check for immutable keys
		$errors += $transaction->validateImmutableColumns($input);

		// Validate input values (using validation function)
		$errors += $transaction->validateInputValues($input);

		// "Copy" data into transaction
		$transaction->loadData($input);

		// Run create hooks
		foreach ($transaction->updateHooks as $colName => $fn) {
			$fn();
		}

		// Validate missing keys
		$errors += $transaction->validateMissingKeys($input);

		// Update database
		if (empty($errors)) {
			$this->syncInstanceFrom($transaction);

			try {
				(new Query($this->getPdo(), $this->getTableName()))
					->update($this->getActiveRecordColumns())
					->where(Query::Equal('id', $this->getId()))
					->execute();
			} catch (\PDOException $e) {
				throw new ActiveRecordException($e->getMessage(), 0, $e);
			}

			return [null, $this->toArray($readWhitelist)];
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
	 * Returns the serialized form of the specified columns
	 * 
	 * @return Array
	 */
	abstract public function toArray(Array $fieldWhitelist);

	/**
	 * Returns the active record table.
	 *
	 * @return string the active record table name.
	 */
	abstract public function getTableName();

	/**
	 * Returns the name -> variable mapping for the table definition.
	 * @return Array The mapping
	 */
	abstract protected function getActiveRecordColumns();
}
