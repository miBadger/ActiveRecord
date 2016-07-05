# ActiveRecordException

The active record exception.

## Example(s)

```php
<?php

use miBadger\ActiveRecord\ActiveRecordException;

try {
	throw new ActiveRecordException('Error message', 1);
} catch(ActiveRecordException $e) {
	// Handle exception
}
```

```php
<?php

use miBadger/ActiveRecord/ActiveRecordException;

try {
	$activeRecord->read($id);
} catch(ActiveRecordException $e) {
	echo $e->getMessage(); // Can not read the non-existent active record entry 1234567890 from the `example` table.
}
```
