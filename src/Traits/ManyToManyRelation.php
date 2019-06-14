<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\Query\Query;
use miBadger\ActiveRecord\ColumnProperty;
use miBadger\ActiveRecord\AbstractActiveRecord;

Trait ManyToManyRelation
{
	// These variables are relevant for internal bookkeeping (constraint generation etc)

	/** @var string The name of the left column of the relation. */
	private $_leftColumnName;

	/** @var string The name of the right column of the relation. */
	private $_rightColumnName;

	/** @var string The name of the left table of the relation. */
	private $_leftEntityTable;

	/** @var string The name of the right table of the relation. */
	private $_rightEntityTable;

	/** @var \PDO The PDO object. */
	protected $pdo;
	/**
	 * Initializes the the ManyToManyRelation trait on the included object
	 * 
	 * @param AbstractActiveRecord $leftEntity The left entity of the relation
	 * @param int $leftVariable The reference to the variable where the id for the left entity will be stored
	 * @param AbstractActiveRecord $rightEntity The left entity of the relation
	 * @param int $leftVariable The reference to the variable where the id for the right entity will be stored
	 * @return void
	 */
	protected function initManyToManyRelation(AbstractActiveRecord $leftEntity, &$leftVariable, AbstractActiveRecord $rightEntity, &$rightVariable)
	{
		$this->_leftEntityTable = $leftEntity->getActiveRecordTable();
		$this->_rightEntityTable = $rightEntity->getActiveRecordTable();

		if (get_class($leftEntity) === get_class($rightEntity)) {
			$this->_leftColumnName = sprintf("id_%s_left", $leftEntity->getActiveRecordTable());
			$this->_rightColumnName = sprintf("id_%s_right", $rightEntity->getActiveRecordTable());
		} else {
			$this->_leftColumnName = sprintf("id_%s", $leftEntity->getActiveRecordTable());
			$this->_rightColumnName = sprintf("id_%s", $rightEntity->getActiveRecordTable());
		}

		$this->extendTableDefinition($this->_leftColumnName, [
			'value' => &$leftVariable,
			'validate' => null,
			'type' => AbstractActiveRecord::COLUMN_TYPE_ID,
			'properties' => ColumnProperty::NOT_NULL
		]);

		$this->extendTableDefinition($this->_rightColumnName, [
			'value' => &$rightVariable,
			'validate' => null,
			'type' => AbstractActiveRecord::COLUMN_TYPE_ID,
			'properties' => ColumnProperty::NOT_NULL
		]);
	}

	/**
	 * Build the constraints for the many-to-many relation table
	 * @return void
	 */
	public function createTableConstraints()
	{
		$childTable = $this->getActiveRecordTable();

		$leftParentTable = $this->_leftEntityTable;
		$rightParentTable = $this->_rightEntityTable;

		$leftConstraint = $this->buildConstraint($leftParentTable, 'id', $childTable, $this->_leftColumnName);
		$rightConstraint = $this->buildConstraint($rightParentTable, 'id', $childTable, $this->_rightColumnName);

		$this->pdo->query($leftConstraint);
		$this->pdo->query($rightConstraint);
	}

	/**
	 * @return void
	 */	
	abstract function getActiveRecordTable();

	/**
	 * @return void
	 */
	abstract function buildConstraint($parentTable, $parentColumn, $childTable, $childColumn);

	/**
	 * @return void
	 */
	abstract function extendTableDefinition($columnName, $definition);
	
	/**
	 * @return void
	 */
	abstract function registerSearchHook($columnName, $fn);

	/**
	 * @return void
	 */
	abstract function registerDeleteHook($columnName, $fn);

	/**
	 * @return void
	 */
	abstract function registerUpdateHook($columnName, $fn);

	/**
	 * @return void
	 */
	abstract function registerReadHook($columnName, $fn);

	/**
	 * @return void
	 */
	abstract function registerCreateHook($columnName, $fn);

}
