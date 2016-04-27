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
	 * Returns the records with the given where, order by, limit and offset clauses.
	 *
	 * @param array $where = []
	 * @param array $orderBy = []
	 * @param int $limit = -1
	 * @param int $offset = 0
	 * @return static[] the records with the given where, order by, limit and offset clauses.
	 * @throws ActiveRecordException on failure.
	 */
	public function search($where = [], $orderBy = [], $limit = -1, $offset = 0);
}
