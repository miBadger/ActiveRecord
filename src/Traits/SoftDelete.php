<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\Query\Query;
use miBadger\ActiveRecord\ColumnProperty;

const TRAIT_SOFT_DELETE_FIELD_KEY = "soft_delete";

trait SoftDelete
{
	protected $softDelete;

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

	protected function softDeleteSearchHook(Query $query)
	{
		$query->where(TRAIT_SOFT_DELETE_FIELD_KEY, '=', 0);
	}

	public function getSoftDeleteFieldName()
	{
		return TRAIT_SOFT_DELETE_FIELD_KEY;
	}
	
	public function softDelete()
	{
		$this->softDelete = true;
		return $this;
	}

	public function softRestore()
	{
		$this->softDelete = false;
		return $this;
	}

	public function getDeletionStatus() 
	{
		return $this->softDelete;
	}
}