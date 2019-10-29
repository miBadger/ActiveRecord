# AbstractActiveRecord
ActiveRecord is a system to aid with managing database entries in PHP code. ActiveRecord implements a so called [object-relational-mapping](https://en.wikipedia.org/wiki/Object-relational_mapping) (ORM) which allows you to treat rows of database tables as PHP objects. These objects are instantiated from classes that inherit from the AbstractActiveRecord class.

The central functionality of ActiveRecord revolves around a table definition. This table definition contains all the information that defines a table in your database, and besides providing an ORM, allows you to: Install & Manage the database, validate input against database constraints, and even generate REST APIs easily (using ```AutoAPI```).

To create a new model, create a new class that inherits from ```miBadger\ActiveRecord\AbstractActiveRecord``` and implement the following two methods:
- ```getTableName(): string``` is expected to return a static string, which will be the name of the backing table in the database.
- ```getTableDefinition(): Array``` defines the columns in the database table.

## Table Definition format
The implementation of ```getTableDefinition``` needs to return a static associative array, where the keys represent the column names in your database table, and the value is an array of configuration properties. The following properties are available:

| Field      | Required | Description                                                                                                                                           |
|------------|-----------|-------------------------------------------------------------------------------------------------------------------------------------------------------|
| ```value```      | Yes       | Takes a reference to the member variable of the class,  where data read from the table will be stored                                                 |
| ```type```       | Yes       | The string representing the mysql database type                                                                                                       |
| ```length```     | No*       | The maximum size of the data in the variable, as would be specified in mysql.  Example: a ```VARCHAR(255)``` field would get a ```length``` of 255.   |
| ```validate```   | No        | Takes an anonimous validation function,  or a reference to a class method in the form of ```[$this, 'functionName']```.  See AutoApi.md for more info |
| ```default```    | No        | The primitive literal that will be used when a value for this column isn't specified.                                                                 |
| ```properties``` | No        | A bitfield of column properties (analogous to the options available in mysql).  All options can be found in miBadger\ActiveRecord\ColumnProperty      |
| ```relation```   | No        | Takes an instance of AbstractActiveRecord (or ```$this```) to indicate this column contains a relation between models. See below for more info. |


An auto incrementing ```id``` field is automatically added to every table definition, and thus should be omitted.

For commonly used fields, code repetition can be prevented by using traits. See the sections below for more info on this.

## Example(s)
Declaration
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
	public function getTableDefinition(): Array 
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

Usage
```php
<?php

/**
 * Construct an abstract active record with the given PDO.
 */
$activeRecord = new Example($pdo);

/**
 * Reads the instance with id = 2 from the database
 */
$activeRecord->read(2);

/**
 * Returns the ID.
 */
$activeRecord->getId();

/**
 * Retrieves the name for the retrieved record
 */
$activeRecord->getName();
```

## Entity extension using traits
Since a lot of projects have similar concepts reused across data models, miBadger supports entity extension and code reuse by using Traits.
Every trait implements a set of functionality that can be incorporated into an ActiveRecord entity.
To use traits in an entity:
1. Include (use) the trait into the class, like you would do with every other trait
2. Call the init method for the trait (look at source for the trait) in the constructer of the class. 

Below follows an example of a trait that adds 2 default ```created``` & ```modified``` fields to the table definition.

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

	public function getTableName(): string
	{
		return 'example';
	}

	public function getTableDefinition(): Array
	{
		return [];
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
ActiveRecord allows for modeling database constraints between table values. In combination with database management, this allows for automatic enforcement of database constraints. This is done by specifying the 'relation' attribute on a column as shown in the ```Example``` class below.

Relation can be either cascade (if the related entity is deleted, the row is deleted), or set to null (if the related entitity is deleted, the id for the relation is set to null). this is decided by the properties entry. if ```ColumnProperty::NOT_NULL``` is set on the relation column, it will create a cascade constraint. Otherwise, it will create a set-null constraint.

### Parent-child relations
```php
<?php

use miBadger\ActiveRecord\AbstractActiveRecord;

class User extends AbstractActiveRecord 
{
	private $username;

	public function getTableDefinition(): Array
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

	public function getTableName(): string
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
While it's possible to model a many-to-many relationship table using 2 relation columns, there is a trait that provides a shorthand called ```ManyToManyRelation```
```php
<?php

use miBadger\ActiveRecord\AbstractActiveRecord;
use miBadger\ActiveRecord\Traits\ManyToManyRelation;

class User extends AbstractActiveRecord 
{
	private $username;

	public function getTableDefinition(): Array
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

	public function getTableName(): string
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

	public function getTableDefinition(): Array
	{
		return [];
	}

	public function getTableName(): string
	{
		return 'follower_relation';
	}
}
```

## Database setup
To make database setup easy, and help with consistency between data and database specification, the table spec for an entity allows you to install a table and optional constraints directly onto the database.

To make sure that your database is correctly installed, you should always install in the following order:
1. Install the tables for all the entities first.
2. Only after installing all tables: Install the table constraints.

```php
// Installs the tables (CREATE TABLE x)
(new User($pdo))->createTable();
(new FollowerRelation($pdo))->createTable();

// Installs the constraints (ALTER TABLE x ADD CONSTRAINT)
(new User($pdo))->createTableConstraints();
(new FollowerRelation($pdo))->createTableConstraints()
```

## Dev-mode: Database consistency validation
NOT IMPLEMENTED YET
At the expense of performance, miBadger can verify whether the data model as described in php is consistent with the mysql database, and help enforce this by throwing exceptions whenever an inconsistency is detected.

# Testing
```sh
composer install
./vendor/bin/phpunit --bootstrap test-bootstrap.php tests
```