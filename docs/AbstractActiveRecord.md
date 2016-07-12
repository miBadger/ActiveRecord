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
