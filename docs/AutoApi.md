# The AutoApi Trait

The autoApi trait aims to make creating HTTP API's for modifying data models created with miBadger.ActiveRecord as simple as possible. It provides a set of functions that help with validating input, and appropriate error messenging, with the aim to reduce the number of lines of code that need to be written while implementing an API.

The methods that are provided by the AutoApi Trait are

```php
public function apiRead($id, $fieldWhitelist);

public function apiSearch($inputs, $fieldWhitelist);

public function apiCreate($input, $fieldWhitelist);

public function apiUpdate($input, $fieldWhitelist);
```

In these cases, the ```$fieldWhitelist``` specifies an array of fieldnames which are allowed to be modified (for create & update) or returned (for read & search), and the ```$input``` array specifies an associate array of input values.

## Example route
This following example illustrates the little amount of code that's required to create a REST API from a data model. This example uses functions from the ```miwebb/JSend``` and ```mibadger/router``` packages for clarity, but these are not required components.

```php
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

	public function getActiveRecordTableDefinition()
	{
		return [
			'name' => 
			[
				'value' => &$this->name,
				'validate' => null,
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

	public function getActiveRecordTable() 
	{
		return 'employee';
	}
}



$router->addRoute("POST", "/entity/", function () {
	$employee = new Entity($pdo);
	[$errors, $data] = $entity->apiCreate($_POST, ['name', 'birthday']);
	if ($errors !== null) {
		return JSend::Fail($errors);
	} else {
		return JSend::Success($data);
	}
});
```
