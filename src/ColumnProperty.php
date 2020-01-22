<?php

namespace miBadger\ActiveRecord;

use miBadger\Enum\Enum;

class ColumnProperty extends Enum
{
	const NONE = 0;
	const UNIQUE = 1;
	const NOT_NULL = 2;
	const IMMUTABLE = 4;
	const AUTO_INCREMENT = 8;
	const PRIMARY_KEY = 16;
}