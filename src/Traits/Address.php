<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\ActiveRecord\ColumnProperty;

const TRAIT_ADDRESS_FIELD_ADDRESS = "address_address";
const TRAIT_ADDRESS_FIELD_ZIPCODE = "address_zipcode";
const TRAIT_ADDRESS_FIELD_CITY = "address_city";
const TRAIT_ADDRESS_FIELD_COUNTRY = "address_country";

trait Address
{
	protected $address;

	protected $zipcode;

	protected $city;

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
}