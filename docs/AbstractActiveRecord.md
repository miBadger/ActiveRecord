# AbstractActiveRecord

The abstract active record class implements the active record interface (miBadger\\ActiveRecord\\ActiveRecordInterface).

## Example(s)

```php
<?php

use miBadger\ActiveRecord\AbstractActiveRecord;

/**
 * The example class
 */
class Example extends AbstractActiveRecord
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
 * Construct an abstract active record with the given PDO.
 */
$activeRecord = new Example($pdo);

/**
 * Returns the PDO.
 */
$activeRecord->getPdo();

/**
 * Returns the ID.
 */
$activeRecord->getId();
```

## Entity extension using traits
Since a lot of projects have similar concepts reused across data models, miBadger supports entity extension and code reuse by using Traits.
Every trait implements a set of functionality that can be incorporated into an ActiveRecord entity.
To use traits in an entity:
1. Include (use) the trait into the class, like you would do with every other trait
2. Call the init method for the trait (look at source for the trait) in the constructer of the class. 

Example of a default ```created``` & ```modified``` field trait
```php
<?php

use miBadger\ActiveRecord\AbstractActiveRecord;
use miBadger\ActiveRecord\Traits\Datefields;

class Example extends AbstractActiveRecord
{
	use Datefields;

	public function __construct($pdo)
	{
		parent::__construct($pdo);
		$this->initDatefields();
	}
}
```

## Writing custom Traits
Custom traits can extend the behaviour of AbstractActiveRecord using hooks. The following hooks are available
- extendTableDefinition: Add new columns to the entity
- registerCreateHook: Add behaviour before inserting a new entry into the database
- registerUpdateHook: Add behaviour before updating a record in the database
- registerReadHook: Add behaviour before attempting to read a record from the database
- registerDeleteHook: Add behaviour before attempting to remove a record from the database
- registerSearchHook: Modify the default search parameters

```php
<?php

$activeRecord = new Example($pdo);

$activeRecord->createTable();
```

## Modelling relations
miBadger allows for modeling database constraints between table values. In combination with database management, this allows for automatic enforcement of constraints. This is done by specifying the 'relation' attribute on a column as shown in the following example.

### Parent-child relations
```php
<?php

use miBadger\ActiveRecord\AbstractActiveRecord;

class User extends AbstractActiveRecord 
{
	private $username;

	public function getTableDefinition() 
	{
		return [
			'username' =>
			[
				'value' => &$this->username,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => ColumnProperty::NOT_NULL | ColumnProperty::UNIQUE
			]
		];
	}

	public function getTableName() 
	{
		return 'user';
	}
}

class Example extends AbstractActiveRecord 
{

	public function getTableDefinition() 
	{
		return [
			'id_user' =>
			[
				'value' => &$this->user_id,
				'relation' => new User($this->pdo)
			]
		];
	}

	public function getTableName() 
	{
		return 'example';
	}	
}
```

### Many-to-many relations
```php
<?php

use miBadger\ActiveRecord\AbstractActiveRecord;
use miBadger\ActiveRecord\Traits\ManyToManyRelation;

class User extends AbstractActiveRecord 
{
	private $username;

	public function getTableDefinition() 
	{
		return [
			'username' =>
			[
				'value' => &$this->username,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => ColumnProperty::NOT_NULL | ColumnProperty::UNIQUE
			]
		];
	}

	public function getTableName() 
	{
		return 'user';
	}
}

class FollowerRelation extends AbstractActiveRecord 
{
	use ManyToManyRelation;

	private $follower;

	private $target;

	public function __construct($pdo)
	{
		self::__construct($pdo);
		$this->initManyToManyRelation(new User($pdo), $this->follower, new User($pdo), $this->target);
	}

	public function getTableDefinition() {
		return [];
	}

	public function getTableName() 
	{
		return 'follower_relation';
	}
}
```

## Database setup
To make database setup easy, and help with consistency between data and database specification, the table spec for an entity allows you to install a table and optional constraints directly onto the database.

To make sure that your database is correctly installed, you should always install in the following order:
1. Install the tables for all the entities first using ```(new Example($pdo))->createTable(); ```
2. Only after installing all tables:
	Install the table constraints using ```(new Example($pdo))->createTableConstraints(); ```

```php
(new User($pdo))->createTable();
(new FollowerRelation($pdo))->createTable();

(new User($pdo))->createTableConstraints();
(new FollowerRelation($pdo))->createTableConstraints()
```

## Dev-mode: Database consistency validation
NOT IMPLEMENTED
At the expense of performance, miBadger can verify whether the data model as described in php is consistent with the mysql database, and help enforce this by throwing exceptions whenever an inconsistency is detected.

# Testing
```sh
composer install
./vendor/bin/phpunit --bootstrap test-bootstrap.php tests
```