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
			'properties' => ColumnProperty::NOT_NULL | ColumnProperty::IMMUTABLE
		]);

		$this->extendTableDefinition(TRAIT_DATEFIELDS_LAST_MODIFIED, [
			'value' => &$this->lastModified,
			'validate' => null,
			'type' => 'DATETIME',
			'properties' => ColumnProperty::NOT_NULL | ColumnProperty::IMMUTABLE
		]);
		
		$this->registerCreateHook(TRAIT_DATEFIELDS_CREATED, 'DatefieldsLastModifiedCreateHook');
		$this->registerCreateHook(TRAIT_DATEFIELDS_LAST_MODIFIED, 'DatefieldsCreatedCreateHook');
		$this->registerUpdateHook(TRAIT_DATEFIELDS_LAST_MODIFIED, 'DatefieldsUpdateHook');

		$this->created = null;
		$this->lastModified = null;
	}

	/**
	 * The hook that gets called to set the last modified timestamp whenever a new record is created
	 */
	protected function DatefieldsLastModifiedCreateHook()
	{
		$this->lastModified = (new \DateTime('now'))->format('Y-m-d H:i:s');
	}

	/**
	 * The hook that gets called to set the created timestamp whenever a new record is created
	 */
	protected function DatefieldsCreatedCreateHook()
	{
		$this->created = (new \DateTime('now'))->format('Y-m-d H:i:s');
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

	