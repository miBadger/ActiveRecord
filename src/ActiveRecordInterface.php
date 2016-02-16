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
 * The active record interface.
 *
 * @see http://en.wikipedia.org/wiki/Active_record_pattern
 * @see http://en.wikipedia.org/wiki/Create,_read,_update_and_delete
 * @since 1.0.0
 */
interface ActiveRecordInterface
{
	/**
	 * Create the record.
	 *
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function create();

	/**
	 * Read the record with the given ID.
	 *
	 * @param int $id
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function read($id);

	/**
	 * Update the record.
	 *
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function update();

	/**
	 * Delete the record.
	 *
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function delete();

	/**
	 * Returns true if the record exists.
	 *
	 * @return bool true if the records exists.
	 */
	public function exists();
}
