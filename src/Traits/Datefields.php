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

	/**
	 * this method is required to be called in the constructor for each class that uses this trait. 
	 * It adds the datefields to the table definition and registers the callback hooks
	 */
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
			'properties' => ColumnProperty::NOT_NULL | ColumnProperty::IMMUTABLE
		]);
		
		$this->registerUpdateHook(TRAIT_DATEFIELDS_LAST_MODIFIED, 'DatefieldsUpdateHook');
		$this->registerCreateHook(TRAIT_DATEFIELDS_LAST_MODIFIED, 'DatefieldsCreateHook');

		$this->created = null;
		$this->lastModified = null;
	}

	/**
	 * The hook that gets called to set the timestamp whenever a new record is created
	 */
	protected function DatefieldsCreateHook()
	{
		// @TODO: Should this be split up to seperate hooks for "last_modified" and "created" for consistency?
		$this->created = (new \DateTime('now'))->format('Y-m-d H:i:s');
		$this->lastModified = (new \DateTime('now'))->format('Y-m-d H:i:s');
	}

	/**
	 * The hook that gets called to set the timestamp whenever a record gets updated
	 */
	protected function DatefieldsUpdateHook()
	{
		$this->lastModified = (new \DateTime('now'))->format('Y-m-d H:i:s');
	}

	/**
	 * Returns the timestamp of last update for this record
	 * @return \DateTime
	 */
	public function getLastModifiedDate()
	{
		return new \DateTime($this->lastModified);
	}

	/**
	 * Returns the timestamp of when this record was created
	 * @return \DateTime
	 */
	public function getCreationDate()
	{
		return new \DateTime($this->created);
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

	