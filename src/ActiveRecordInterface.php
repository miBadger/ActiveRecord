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
	 * Returns this active record after creating an entry with the records attributes.
	 *
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function create();

	/**
	 * Returns this active record after reading the attributes from the entry with the given identifier.
	 *
	 * @param mixed $id
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function read($id);

	/**
	 * Returns this active record after updating the attributes to the corresponding entry.
	 *
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function update();

	/**
	 * Returns this record after deleting the corresponding entry.
	 *
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function delete();

	/**
	 * Returns this record after synchronizing it with the corresponding entry.
	 * A new entry is created if this active record does not have a corresponding entry.
	 *
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function sync();

	/**
	 * Returns true if this active record has a corresponding entry.
	 *
	 * @return bool true if this active record has a corresponding entry.
	 */
	public function exists();

	/**
	 * Returns this record after filling it with the given attributes.
	 *
	 * @param array $attributes = []
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function fill(array $attributes);

	/**
	 * Returns this record after filling it with the attributes of the first entry with the given where and order by clauses.
	 *
	 * @param array $where = []
	 * @param array $orderBy = []
	 * @return $this
	 * @throws ActiveRecordException on failure.
	 */
	public function searchOne(array $where = [], array $orderBy = []);

	/**
	 * Returns the records with the given where, order by, limit and offset clauses.
	 *
	 * @param array $where = []
	 * @param array $orderBy = []
	 * @param int $limit = -1
	 * @param int $offset = 0
	 * @return static[] the records with the given where, order by, limit and offset clauses.
	 * @throws ActiveRecordException on failure.
	 */
	public function search(array $where = [], array $orderBy = [], $limit = -1, $offset = 0);
}
