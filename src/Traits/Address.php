<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\ActiveRecord\ColumnProperty;

const TRAIT_ADDRESS_FIELD_ADDRESS = "address_address";
const TRAIT_ADDRESS_FIELD_ZIPCODE = "address_zipcode";
const TRAIT_ADDRESS_FIELD_CITY = "address_city";
const TRAIT_ADDRESS_FIELD_COUNTRY = "address_country";

trait Address
{
	/** @var string the address line */
	protected $address;

	/** @var string the zipcode */
	protected $zipcode;

	/** @var string the city */
	protected $city;

	/** @var string the country */
	protected $country;

	protected function initAddress() 
	{
		$this->extendTableDefinition(TRAIT_ADDRESS_FIELD_ADDRESS, [
			'value' => &$this->address,
			'validate' => null,
			'type' => 'VARCHAR',
			'length' => 1024,
			'properties' => null
		]);

		$this->extendTableDefinition(TRAIT_ADDRESS_FIELD_ZIPCODE, [
			'value' => &$this->zipcode,
			'validate' => null,
			'type' => 'VARCHAR',
			'length' => 1024,
			'properties' => null
		]);

		$this->extendTableDefinition(TRAIT_ADDRESS_FIELD_CITY, [
			'value' => &$this->city,
			'validate' => null,
			'type' => 'VARCHAR',
			'length' => 1024,
			'properties' => null
		]);

		$this->extendTableDefinition(TRAIT_ADDRESS_FIELD_COUNTRY, [
			'value' => &$this->country,
			'validate' => null,
			'type' => 'VARCHAR',
			'length' => 1024,
			'properties' => null
		]);

		$this->address = null;
		$this->zipcode = null;
		$this->city = null;
		$this->country = null;
	}

	public function getAddress()
	{
		return $this->address;
	}
	
	public function setAddress($address)
	{
		$this->address = $address;
	}

	public function getZipcode()
	{
		return $this->zipcode;
	}
	
	public function setZipcode($zipcode)
	{
		$this->zipcode = $zipcode;
	}

	public function getCity()
	{
		return $this->city;
	}
	
	public function setCity($city)
	{
		$this->city = $city;
	}

	public function getCountry()
	{
		return $this->country;
	}
	
	public function setCountry($country)
	{
		$this->country = $country;
	}

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