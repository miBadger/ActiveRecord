<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord;

use miBadger\Query\Query;

/**
 * The abstract Schema builder that helps with construction mysql tables
 *
 * @since 1.0.0
 */
class SchemaBuilder
{
	/**
	 * builds a MySQL constraint statement for the given parameters
	 * @param string $parentTable
	 * @param string $parentColumn
	 * @param string $childTable
	 * @param string $childColumn
	 * @return string The MySQL table constraint string
	 */
	public static function buildConstraint($parentTable, $parentColumn, $childTable, $childColumn)
	{
		$template = <<<SQL
ALTER TABLE `%s`
ADD CONSTRAINT
FOREIGN KEY (`%s`)
REFERENCES `%s`(`%s`)
ON DELETE CASCADE;
SQL;
		return sprintf($template, $childTable, $childColumn, $parentTable, $parentColumn);
	}

	/**
	 * Returns the type string as it should appear in the mysql create table statement for the given column
	 * @return string The type string
	 */
	public static function getDatabaseTypeString($colName, $type, $length)
	{
		switch (strtoupper($type)) {
			case '':
				throw new ActiveRecordException(sprintf("Column %s has invalid type \"NULL\"", $colName));
			
			case 'BOOL';
			case 'BOOLEAN':
			case 'DATETIME':
			case 'DATE':
			case 'TIME':
			case 'TEXT':
			case 'INT UNSIGNED':
				return $type;

			case 'VARCHAR':
				if ($length === null) {
					throw new ActiveRecordException(sprintf("field type %s requires specified column field \"LENGTH\"", $colName));
				} else {
					return sprintf('%s(%d)', $type, $length);	
				}

			case 'INT':
			case 'TINYINT':
			case 'BIGINT':
			default: 	
				// Implicitly assuming that non-specified cases are correct without a length parameter
				if ($length === null) {
					return $type;
				} else {
					return sprintf('%s(%d)', $type, $length);	
				}
		}
	}

	/**
	 * Builds the part of a MySQL create table statement that corresponds to the supplied column
	 * @param string $colName 	Name of the database column
	 * @param string $type 		The type of the string
	 * @param int $properties 	The set of Column properties that apply to this column (See ColumnProperty for options)
	 * @return string
	 */
	public static function buildCreateTableColumnEntry($colName, $type, $length, $properties, $default)
	{
		$stmnt = sprintf('`%s` %s ', $colName, self::getDatabaseTypeString($colName, $type, $length));
		if ($properties & ColumnProperty::NOT_NULL) {
			$stmnt .= 'NOT NULL ';
		} else {
			$stmnt .= 'NULL ';
		}

		if ($default !== NULL) {
			$stmnt .= 'DEFAULT ' . var_export($default, true) . ' ';
		}

		if ($properties & ColumnProperty::AUTO_INCREMENT) {
			$stmnt .= 'AUTO_INCREMENT ';
		}

		if ($properties & ColumnProperty::UNIQUE) {
			$stmnt .= 'UNIQUE ';
		}

		if ($properties & ColumnProperty::PRIMARY_KEY) {
			$stmnt .= 'PRIMARY KEY ';
		}

		return $stmnt;
	}

	/**
	 * Sorts the column statement components in the order such that the id appears first, 
	 * 		followed by all other columns in alphabetical ascending order
	 * @param   Array $colStatements Array of column statements
	 * @return  Array
	 */
	private static function sortColumnStatements($colStatements)
	{
		// Find ID statement and put it first
		$sortedStatements = [];

		$sortedStatements[] = $colStatements[AbstractActiveRecord::COLUMN_NAME_ID];
		unset($colStatements[AbstractActiveRecord::COLUMN_NAME_ID]);

		// Sort remaining columns in alphabetical order
		$columns = array_keys($colStatements);
		sort($columns);
		foreach ($columns as $colName) {
			$sortedStatements[] = $colStatements[$colName];
		}

		return $sortedStatements;
	}

	/**
	 * Builds the MySQL Create Table statement for the internal table definition
	 * @return string
	 */
	public static function buildCreateTableSQL($tableName, $tableDefinition)
	{
		$columnStatements = [];
		foreach ($tableDefinition as $colName => $definition) {
			// Destructure column definition
			$type    = $definition['type'] ?? null;
			$default = $definition['default'] ?? null;
			$length  = $definition['length'] ?? null;
			$properties = $definition['properties'] ?? null;

			if (isset($definition['relation']) && $type !== null) {
				$msg = sprintf("Column \"%s\" on table \"%s\": ", $colName, $tableName);
				$msg .= "Relationship columns have an automatically inferred type, so type should be omitted";
				throw new ActiveRecordException($msg);
			} else if (isset($definition['relation'])) {
				$type = AbstractActiveRecord::COLUMN_TYPE_ID;
			}

			$columnStatements[$colName] = self::buildCreateTableColumnEntry($colName, $type, $length, $properties, $default);
		}

		// Sort table (first column is id, the remaining are alphabetically sorted)
		$columnStatements = self::sortColumnStatements($columnStatements);

		$sql = sprintf("CREATE TABLE %s (\n%s\n) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;", 
			$tableName, 
			implode(",\n", $columnStatements));

		return $sql;
	}

}
