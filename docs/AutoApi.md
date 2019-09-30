# The AutoApi Trait

The autoApi trait aims to make creating HTTP API's for modifying data models created with miBadger.ActiveRecord as simple as possible. It provides a set of functions that help with validating input, and appropriate error messenging, with the aim to reduce the number of lines of code that need to be written while implementing an API.

```php
use miBadger\Query\QueryExpression

public function apiRead($id, $readWhitelist): Array;

public function apiSearch($queryParams, $readWhitelist, QueryExpression $whereClause = null): Array;

public function apiCreate($input, $createWhitelist, $readWhitelist): [Array $errors, Array $result];

public function apiUpdate($input, $updateWhitelist, $readWhitelist): [Array $errors, Array $result];
```

In these cases, the ```$createWhitelist``` and ```$updateWhitelist``` arrays specify database columns which are allowed to be modified. The ```$readwhitelist``` specifies the columns that get returned by the method, and the ```$input``` array specifies an associative array of user input values.

For ```apiSearch``` There are two unique input fields:
- The ```$queryParams``` is an associative array for which the following keys can be specified to modify the search results
	- ```search_order_by```
	- ```search_order_direction```
	- ```search_limit```
	- ```search_offset``` 
- ```$whereClause``` is an optional parameter that allows the user to add a custom search condition.

## Example route
This following example illustrates the little amount of code that's required to create a REST API from a data model. This example uses functions from the ```miwebb/JSend``` and ```mibadger/router``` packages for clarity, but these are not required components.

Note that there are two different ways to provide the validation function. One can either provide the method directly as an anonymous function, or can provide a the name to a class function that provides the validation functionality together with the instance (in the form of ```[$this, 'validationFunctionName']```.

```php

use miBadger\ActiveRecord\Traits\AutoApi;
use miBadger\ActiveRecord\Traits\SoftDelete;

class Employee extends AbstractActiveRecord
{
	use AutoApi;
	use SoftDelete;

	private $birthday;

	private $name;

	public function __construct($pdo)
	{
		parent::__construct($pdo);
		$this->initSoftDelete();
	}

	public function getTableDefinition()
	{
		return [
			'name' => 
			[
				'value' => &$this->name,
				'validate' => [$this, 'validateName'],
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => ColumnProperty::NOT_NULL | ColumnProperty::UNIQUE
			],
			'birthday' => 
			[
				'value' => &$this->birthday,
				'validate' => function ($value) {
					try {
						$date = new \DateTime($value);
						return [true, ''];
					} catch (\Exception $e) {
						return [false, 'provided value is not a valid date'];
					}
				},
				'type' => 'DATETIME',
				'properties' => ColumnProperty::IMMUTABLE
			]
		];
	}

	public function getTableName() 
	{
		return 'employee';
	}

	private function validateName($value)
	{
		return [is_string($value), "Given name must be a string"];
	}
}

// Example Create
$router->addRoute("POST", "/entity/", function () {
	
	$employee = new Entity($pdo);
	[$errors, $data] = $entity->apiCreate($_POST, ['name', 'birthday'], ['id', 'name', 'birthday']);
	if (!empty($errors)) {
		return JSend::Fail($errors);
	}
	return JSend::Success($data);

});

// Example Read
$router->addRoute("GET", "/entity/{id}/", function ($id) {

	$employee = new Entity($pdo);
	$data = $entity->apiRead($id, ['name', 'birthday']);
	return JSend::Success($data);

});

// Example Update
$router->addRoute("PUT", "/entity/{id}/", function ($id) {
	
	$employee = new Entity($pdo);
	// Pay attention to the required read
	[$errors, $data] = $entity->read($id)->apiUpdate($_POST, ['name', 'birthday'], ['id', 'name', 'birthday']);
	if (!empty($errors)) {
		return JSend::Fail($errors);
	}
	return JSend::Success($data);
	
});
```
