<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\Query\Query;
use miBadger\ActiveRecord\ColumnProperty;

const TRAIT_SOFT_DELETE_FIELD_KEY = "soft_delete";

trait SoftDelete
{
	/** @var boolean the soft delete status for the entity this trait is embedded into. */
	protected $softDelete;

	/**
	 * this method is required to be called in the constructor for each class that uses this trait. 
	 * It adds the required fields to the table definition and registers hooks
	 */
	protected function initSoftDelete()
	{
		$this->softDelete = false;

		$this->extendTableDefinition(TRAIT_SOFT_DELETE_FIELD_KEY, [
			'value' => &$this->softDelete,
			'validate' => null,
			'default' => 0,
			'type' => 'INT',
			'length' => 1,
			'properties' => ColumnProperty::NOT_NULL
		]);

		$this->registerSearchHook(TRAIT_SOFT_DELETE_FIELD_KEY, 'softDeleteSearchHook');
		$this->registerReadHook(TRAIT_SOFT_DELETE_FIELD_KEY, 'softDeleteReadHook');
	}

	/**
	 * The hook that gets called whenever a query is made
	 */
	protected function softDeleteSearchHook()
	{
		return Query::Equal(TRAIT_SOFT_DELETE_FIELD_KEY, 0);
	}

	protected function softDeleteReadHook()
	{
		return Query::Equal(TRAIT_SOFT_DELETE_FIELD_KEY, 0);
	}

	/**
	 * returns the name for the soft delete field in the database
	 * @return string
	 */
	public function getSoftDeleteFieldName()
	{
		return TRAIT_SOFT_DELETE_FIELD_KEY;
	}
	
	/**
	 * Mark the current record as soft deleted
	 * @return $this
	 */
	public function softDelete()
	{
		$this->softDelete = true;
		$this->update();
		return $this;
	}

	/**
	 * Undo the current soft deletion status (mark it as non-soft deleted)
	 * @return $this
	 */
	public function softRestore()
	{
		$this->softDelete = false;
		$this->update();
		return $this;
	}

	/**
	 * returns the current soft deletion status
	 * @return $this
	 */
	public function getDeletionStatus() 
	{
		return $this->softDelete;
	}

	/**
	 * @return void
	 */
	abstract protected function extendTableDefinition(string $columnName, $definition);
	
	/**
	 * @return void
	 */
	abstract protected function registerSearchHook(string $columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerDeleteHook(string $columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerUpdateHook(string $columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerReadHook(string $columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerCreateHook(string $columnName, $fn);
	
}