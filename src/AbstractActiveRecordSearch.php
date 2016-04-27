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
 * The abstract active record search class.
 *
 * @since 1.0.0
 */
abstract class AbstractActiveRecordSearch extends AbstractActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public function search($where = [], $orderBy = [], $limit = -1, $offset = 0)
	{
		try {
			$pdoStatement = $this->getPdo()->prepare($this->getSearchQuery($where, $orderBy, $limit, $offset));
			array_walk_recursive($where, function(&$value) use ($pdoStatement) {
				static $index = 1;

				$pdoStatement->bindParam($index++, $value);
			});

			$pdoStatement->execute();
			$result = [];

			while ($fetch = $pdoStatement->fetch()) {
				$new = new static($this->getPdo());

				$new->setId(intval($fetch['id']));
				$new->setActiveRecordData($fetch);

				$result[] = $new;
			}

			return $result;
		} catch (\PDOException $e) {
			throw new ActiveRecordException(sprintf('Can not search the record in the `%s` table.', $this->getActiveRecordName()), 0, $e);
		}
	}

	/**
	 * Returns the search query with the given where, order by, limit and offset clauses.
	 *
	 * @param array $where = []
	 * @param array $orderBy = []
	 * @param int $limit = -1
	 * @param int $offset = 0
	 * @return string the search query with the given where, order by, limit and offset clauses.
	 */
	private function getSearchQuery($where = [], $orderBy = [], $limit = -1, $offset = 0)
	{
		return sprintf(
			'SELECT * FROM `%s` %s %s LIMIT %d OFFSET %d',
			$this->getActiveRecordName(),
			$this->getSearchQueryWhereClause($where),
			$this->getSearchQueryOrderByClause($orderBy),
			$limit,
			$offset
		);
	}

	/**
	 * Returns the search query where clause.
	 *
	 * @param array $where
	 * @return string the search query where clause.
	 */
	private function getSearchQueryWhereClause($where)
	{
		$columns = array_keys($this->getActiveRecordData());
		$columns[] = 'id';
		$result = [];

		foreach ($where as $key => $value) {
			if (!in_array($key, $columns)) {
				throw new ActiveRecordException(sprintf('Search option key `%s` does not exists.', $key));
			}

			if (is_numeric($value)) {
				$result[] = sprintf('`%s` = ?', $key);
			} elseif (is_string($value)) {
				$result[] = sprintf('`%s` LIKE ?', $key);
			} elseif (is_null($value)) {
				$result[] = sprintf('`%s` IS ?', $key);
			} elseif (is_array($value) && !empty($value)) {
				$result[] = sprintf('`%s` IN (%s)', $key, implode(',', array_fill(0, count($value), '?')));
			} else {
				throw new ActiveRecordException(sprintf('Search option value of key `%s` is not supported.', $key));
			}
		}

		return empty($result) ? '' : 'WHERE ' . implode(' AND ', $result);
	}

	/**
	 * Returns the search query order by clause.
	 *
	 * @param array $orderBy
	 * @return string the search query order by clause.
	 */
	private function getSearchQueryOrderByClause($orderBy)
	{
		$result = [];

		foreach ($orderBy as $key => $value) {
			$result[] = sprintf('`%s` %s', $key, $value == 'DESC' ? 'DESC' : 'ASC');
		}

		return empty($result) ? '' : 'ORDER BY ' . implode(', ', $result);
	}
}
