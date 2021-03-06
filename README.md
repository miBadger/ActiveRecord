# ActiveRecord

[![Build Status](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/badges/build.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.ActiveRecord/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a413f3f7-2937-470b-968b-a1ab3abe2670/mini.png)](https://insight.sensiolabs.com/projects/a413f3f7-2937-470b-968b-a1ab3abe2670)

The Active Record Component.

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
	public function getActiveRecordTable()
	{
		return 'example';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getActiveRecordColumns()
	{
		return [
			'name' => &$name
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
