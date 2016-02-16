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
		$name = $this->getActiveRecordName();
		$data = $this->getActiveRecordData();

		$pdoStatement = $this->getPdo()->prepare($this->getSearchQuery($options));
		array_walk_recursive($options, function (&$value) use ($pdoStatement) {
			static $index = 1;

			$pdoStatement->bindParam($index++, $value);
		});

		$pdoStatement->execute();
		$result = [];

		while ($row = $pdoStatement->fetch()) {
			$x = new static($this->getPdo());
			$x->id = intval($row['id']);

			foreach ($x->getActiveRecordData() as $key => &$value) {
				$value = $row[$key];
			}

			$result[] = $x;
		}

		return $result;
	}

	/**
	 * Returns the search query with the given options.
	 *
	 * @param arrray $options = []
	 * @return string the search query with the given options.
	 */
	private function getSearchQuery($options = [])
	{
		$columns = array_keys($this->getActiveRecordData());
		$values = [];

		foreach ($options as $key => $value) {
			if (!in_array($key, $columns)) {
				throw new ActiveRecordException('Invalid option key.');
			}

			if (is_int($value)) {
				$values[] = $key . ' = ?';
			} elseif (is_string($value)) {
				$values[] = $key . ' LIKE ?';
			} elseif(is_array($value) && !empty($value)) {
				$values[] = $key . ' IN(' . implode(',', array_fill(0, count($value), '?')) . ')';
			} else {
				throw new ActiveRecordException('Invalid option value.');
			}
		}

		return sprintf('SELECT * FROM %s %s %s', $this->getActiveRecordName(), empty($values) ? '' : 'WHERE', implode(' AND ', $values));
	}
}
