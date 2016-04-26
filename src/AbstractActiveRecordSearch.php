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
	public function search($options = [])
	{
		try {
			$pdoStatement = $this->getPdo()->prepare($this->getSearchQuery($options));
			array_walk_recursive($options, function(&$value) use ($pdoStatement) {
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
	 * Returns the search query with the given options.
	 *
	 * @param array $options = []
	 * @return string the search query with the given options.
	 */
	private function getSearchQuery($options = [])
	{
		$columns = array_keys($this->getActiveRecordData());
		$columns[] = 'id';
		$values = [];

		foreach ($options as $key => $value) {
			if (!in_array($key, $columns)) {
				throw new ActiveRecordException(sprintf('Search option key `%s` does not exists.', $key));
			}

			if (is_numeric($value)) {
				$values[] = $key . ' = ?';
			} elseif (is_string($value)) {
				$values[] = $key . ' LIKE ?';
			} elseif (is_array($value) && !empty($value)) {
				$values[] = $key . ' IN(' . implode(',', array_fill(0, count($value), '?')) . ')';
			} else {
				throw new ActiveRecordException(sprintf('Search option value of key `%s` is not supported.', $this->getActiveRecordName()));
			}
		}

		return sprintf('SELECT * FROM %s %s %s', $this->getActiveRecordName(), empty($values) ? '' : 'WHERE', implode(' AND ', $values));
	}
}
