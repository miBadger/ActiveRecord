# ActiveRecord

[![Build Status](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/badges/build.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a413f3f7-2937-470b-968b-a1ab3abe2670/mini.png)](https://insight.sensiolabs.com/projects/a413f3f7-2937-470b-968b-a1ab3abe2670)

The Active Record Component.
For more in depth information, check out the docs directory.
New to ActiveRecord? Begin at [```docs/AbstractActiveRecord.md```](docs/AbstractActiveRecord.md)

## Example

```php
<?php

use miBadger\ActiveRecord\AbstractActiveRecord;

/**
 * The user class.
 */
class User extends AbstractActiveRecord
{
	/** @var string The name. */
	private $name;

	/**
	 * {@inheritdoc}
	 */
	public function getTableName()
	{
		return 'example';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTableDefinition() 
	{
		return [
			'name' =>
			[
				'value' => &$this->name,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => ColumnProperty::NOT_NULL | ColumnProperty::UNIQUE
			]
		];
	}

	/**
	 * Returns the name.
	 *
	 * @return string the name.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Sets the name.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}
}
```

```php
<?php

/**
 * Create an active record instance.
 */
$user = new User($pdo);

/**
 * Read the row with the given ID from the database.
 */
$user->read($id);

/**
 * Returns the name.
 */
$user->getName(); // John

/**
 * Set a new name.
 */
$user->setName('Jane');

/**
 * Synchronize the active record with the database.
 */
$user->sync();
```

# Requirements
ActiveRecord is tested with
- php 7.2+
- mysql (mariadb) 8.0.6.16
For more detailed information, check out the scrutinizer reports.

# Setup
The easiest way to install miBadger.ActiveRecord is using composer
```composer require mibadger/activerecord```
or by including the package in your composer.json
```json
"require": {
	"mibadger/activerecord": "^2.0"
}
```
# Limitations


# Changelog
## 2.0-dev
- New interfaces & style of declaring columns
- getTableDefinition now returns an associative array of columnEntries
- added functionality for code reuse by means of traits. Some common traits can be found in ```src/Traits/```
- added autoApi trait for handling simple CRUD operations.
