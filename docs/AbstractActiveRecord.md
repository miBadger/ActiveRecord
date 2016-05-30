# AbstractActiveRecord

The abstract active record class implements the active record interface.

## Example(s)

```php
// Create a new active record.
$activeRecord = new AbstractActiveRecord($pdo);

// Get the PDO object.
$activeRecord->getPdo();

// Get the ID.
$activeRecord->getId();
```
