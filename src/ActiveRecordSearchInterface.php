<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 * @version 1.0.0
 */

namespace miBadger\ActiveRecord;

/**
 * The active record search interface.
 *
 * @since 1.0.0
 */
interface ActiveRecordSearchInterface extends ActiveRecordInterface
{
	/**
	 * Returns the records with the given options.
	 *
	 * @param array $options = []
	 * @return static[] the records with the given options.
	 * @throws ActiveRecordException on failure.
	 */
	public function search($options = []);
}
