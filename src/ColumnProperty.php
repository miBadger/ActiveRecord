<?php

namespace miBadger\ActiveRecord;


class ColumnProperty
{
	const NONE = 0;
	const UNIQUE = 1;
	const NOT_NULL = 2;
	const IMMUTABLE = 4;
	const AUTO_INCREMENT = 8;
	const PRIMARY_KEY = 16;
}