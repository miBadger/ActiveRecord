<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\Query\Query;
use miBadger\ActiveRecord\ColumnProperty;

const TRAIT_DATEFIELDS_LAST_MODIFIED = "last_modified";
const TRAIT_DATEFIELDS_CREATED = "created";

trait Datefields
{
	/** @var string The timestamp representing the moment this record was created */
	protected $created;

	/** @var string The timestamp representing the moment this record was last updated */
	protected $lastModified;

	protected function initDatefields()
	{
		$this->extendTableDefinition(TRAIT_DATEFIELDS_CREATED, [
			'value' => &$this->created,
			'validate' => null,
			'type' => 'DATETIME',
			'default' => 'CURRENT_TIMESTAMP',
			'properties' => ColumnProperty::NOT_NULL | ColumnProperty::IMMUTABLE
		]);

		$this->extendTableDefinition(TRAIT_DATEFIELDS_LAST_MODIFIED, [
			'value' => &$this->lastModified,
			'validate' => null,
			'type' => 'DATETIME',
			'default' => 'CURRENT_TIMESTAMP',
			// 'on_update' => 'CURRENT_TIMESTAMP',		// @TODO(Discuss): Should we support this? (would not sync with object)
			'properties' => ColumnProperty::NOT_NULL | ColumnProperty::IMMUTABLE
		]);
		
		$this->registerUpdateHook(TRAIT_DATEFIELDS_LAST_MODIFIED, 'DatefieldsUpdateHook');
		$this->registerCreateHook(TRAIT_DATEFIELDS_LAST_MODIFIED, 'DatefieldsCreateHook');

		$this->created = null;
		$this->lastModified = null;
	}

	protected function DatefieldsCreateHook()
	{
		// Should this be split up to seperate hooks for "last_modified" and "created" for consistency?
		$this->created = (new \DateTime('now'))->format('Y-m-d H:i:s');
		$this->lastModified = (new \DateTime('now'))->format('Y-m-d H:i:s');
	}

	protected function DatefieldsUpdateHook()
	{
		$this->lastModified = (new \DateTime('now'))->format('Y-m-d H:i:s');
	}

	public function getLastModifiedDate()
	{
		return new \DateTime($this->lastModified);
	}

	public function getCreationDate()
	{
		return new \DateTime($this->created);
	}
}

	