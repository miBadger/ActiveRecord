<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord;

/**
 * The active record exception class.
 *
 * @since 1.0.0
 */
class ActiveRecordException extends \RuntimeException
{
	const NOT_FOUND = 1;
	const DB_ERROR = 2;
}
