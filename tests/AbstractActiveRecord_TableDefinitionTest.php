<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord\OperationsTest;

use miBadger\Query\Query;
use PHPUnit\Framework\TestCase;
use miBadger\ActiveRecord\AbstractActiveRecord;
use miBadger\ActiveRecord\ColumnProperty;
use miBadger\ActiveRecord\ActiveRecordException;

/**
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class AbstractActiveRecord_TableDefinitionTest extends TestCase
{

	public function setUp(): void {
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME), DB_USER, DB_PASS);
	}

	public function testGetter() {
		$foo = new Foo($this->pdo);

		[$result, $message] = $foo->validateColumn('field_validate_null', 'bar');
		$this->assertTrue($result);
		[$result, $message] = $foo->validateColumn('field_validate_fn', 'bar');
		$this->assertTrue($result);
		[$result, $message] = $foo->validateColumn('field_validate_fn', 'foo');
		$this->assertFalse($result);

		$this->assertTrue($foo->hasColumn('field_validate_null'));
		$this->assertEquals($foo->getColumnType('field_validate_null'), 'VARCHAR');
		$this->assertEquals($foo->getColumnLength('field_validate_null'), 256);
		$this->assertEquals($foo->getDefault('field_validate_null'), 'foobar');
	}

	public function testNonExistingColumn() {
		$foo = new Foo($this->pdo);
		$this->assertFalse($foo->hasColumn('field_nonexistent'));
	}

	public function testGetColumnTypeException() {
		$this->expectException(ActiveRecordException::class);
		$this->expectExceptionMessage("Provided column \"field_nonexistent\" does not exist in table definition");

		$foo = new Foo($this->pdo);
		$foo->getColumnType('field_nonexistent', 'VARCHAR');
	}

	public function testHasRelationException() {
		$this->expectException(ActiveRecordException::class);
		$this->expectExceptionMessage("Provided column \"field_nonexistent\" does not exist in table definition");

		$foo = new Foo($this->pdo);
		$foo->hasRelation('field_nonexistent', new Bar($this->pdo));
	}

	public function testHasRelation() {
		$foo = new Foo($this->pdo);
		$this->assertTrue($foo->hasRelation('field_relation', new Bar($this->pdo)));
		$this->assertFalse($foo->hasRelation('field_validate_null', new Bar($this->pdo)));
	}

	public function testHasPropertyException() {
		$this->expectException(ActiveRecordException::class);
		$this->expectExceptionMessage("Provided column \"field_nonexistent\" does not exist in table definition");

		$foo = new Foo($this->pdo);
		$foo->hasRelation('field_nonexistent', new Bar($this->pdo));
	}

	public function testHasProperty() {
		$foo = new Foo($this->pdo);
		$this->assertTrue($foo->hasProperty('field_validate_null', 'NOT_NULL'));
		$this->assertFalse($foo->hasProperty('field_validate_null', 'IMMUTABLE'));
	}

	public function testInvalidProperty() {
		$foo = new Foo($this->pdo);
		$this->assertFalse($foo->hasProperty('field_validate_fn', 'NOT_NULL'));
	}
}


class Bar extends AbstractActiveRecord {
	/**
	 * {@inheritdoc}
	 */
	public function getTableName(): string
	{
		return 'Bar';
	}

	protected function getTableDefinition(): Array
	{
		return [
		];
	}

}

class Foo extends AbstractActiveRecord {
	/**
	 * {@inheritdoc}
	 */
	public function getTableName(): string
	{
		return 'Foo';
	}

	protected function getTableDefinition(): Array
	{
		return [
			'field_validate_null' => 
			[
				'value' => &$this->field_validate_null,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'default' => 'foobar',
				'properties' => ColumnProperty::NOT_NULL
			],
			'field_validate_fn' => 
			[
				'value' => &$this->field_validate_fn,
				'validate' => function($value) {
					if ($value === "bar") {
						return [true, ''];
					}
					return [false, 'It\'s not bar!'];
				},
				'type' => 'VARCHAR',
				'length' => 512,
				'properties' => null,
			],
			'field_relation' => 
			[
				'value' => &$this->field_relation,
				'relation' => Bar::class
			]
		];
	}

}