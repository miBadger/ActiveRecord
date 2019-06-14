# ActiveRecordInterface

The active record interface.

## Example(s)

```php
<?php

use miBadger\ActiveRecord\ActiveRecordInterface;
use mibadger\Query\Query;

/**
 * Returns this active record after creating an entry with the records attributes.
 */
$activeRecord->create();

/**
 * Returns this active record after reading the attributes from the entry with the given identifier.
 */
$activeRecord->read($id);

/**
 * Returns this active record after updating the attributes to the corresponding entry.
 */
$activeRecord->update();

/**
 * Returns this record after deleting the corresponding entry.
 */
$activeRecord->delete();

/**
 * Returns this record after synchronizing it with the corresponding entry.
 * A new entry is created if this active record does not have a corresponding entry.
 */
$activeRecord->sync();

/**
 * Returns true if this active record has a corresponding entry.
 */
$activeRecord->exists();

/**
 * Returns this record after filling it with the given attributes.
 */
$activeRecord->fill();

/**
 * Returns this record after filling it with the attributes of the first entry with the given where and order by clauses.
 */
$activeRecord->search()->where(Query::Equal('id', 1))->fetchOne();

/**
 * Returns the records with the given where, order by, limit and offset clauses.
 */
$activeRecord->search()->where(Query::Equal('id', 1))->fetchAll();
```
