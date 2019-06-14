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
	}

	/**
	 * The hook that gets called whenever a query is made
	 */
	protected function softDeleteSearchHook(Query $query)
	{
		$query->where(TRAIT_SOFT_DELETE_FIELD_KEY, '=', 0);
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
		return $this;
	}

	/**
	 * Undo the current soft deletion status (mark it as non-soft deleted)
	 * @return $this
	 */
	public function softRestore()
	{
		$this->softDelete = false;
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
	abstract protected function extendTableDefinition($columnName, $definition);
	
	/**
	 * @return void
	 */
	abstract protected function registerSearchHook($columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerDeleteHook($columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerUpdateHook($columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerReadHook($columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerCreateHook($columnName, $fn);
}