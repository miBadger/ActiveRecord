<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\Query\Query;
use miBadger\ActiveRecord\ColumnProperty;

const TRAIT_SOFT_DELETE_FIELD_KEY = "soft_delete";

trait SoftDelete
{
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
	 */
	public function getSoftDeleteFieldName()
	{
		return TRAIT_SOFT_DELETE_FIELD_KEY;
	}
	
	/**
	 * Mark the current record as soft deleted
	 */
	public function softDelete()
	{
		$this->softDelete = true;
		return $this;
	}

	/**
	 * Undo the current soft deletion status (mark it as non-soft deleted)
	 */
	public function softRestore()
	{
		$this->softDelete = false;
		return $this;
	}

	/**
	 * returns the current soft deletion status
	 */
	public function getDeletionStatus() 
	{
		return $this->softDelete;
	}
}