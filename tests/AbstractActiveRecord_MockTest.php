<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord\MockTest;

use PHPUnit\Framework\TestCase;
use miBadger\Query\Query;
use miBadger\ActiveRecord\AbstractActiveRecord;
use miBadger\ActiveRecord\ActiveRecordInterface;
use miBadger\ActiveRecord\ColumnProperty;
use miBadger\ActiveRecord\ActiveRecordException;

/**
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class AbstractActiveRecord_MockTest extends TestCase
{
	/** @var \PDO The PDO. */
	private $pdo;

	public function setUp(): void
	{
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME), DB_USER, DB_PASS);
		$this->pdo->query('CREATE TABLE IF NOT EXISTS `abstractactiverecord_mocktest_foo` (`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `related_entity` INT UNSIGNED);');
	}

	public function testInjectRelationAndValidateMock() {
		// validate function should pass like a normal object would
		$obj = new Foo($this->pdo);

		$mock = $this->createMock('miBadger\ActiveRecord\AbstractActiveRecord');
		$obj->injectInstanceOnRelation('related_entity', $mock);
		$this->assertTrue($obj->hasRelation('related_entity', $mock));
	}

	public function testInjectRelationAndCreateTableDefinitionFail() {
		$obj = new Foo($this->pdo);
		$mock = $this->createMock('miBadger\ActiveRecord\AbstractActiveRecord');

		$this->expectException(ActiveRecordException::class);
		$this->expectExceptionMessage(sprintf("Relation constraint on column \"related_entity\" of table \"abstractactiverecord_mocktest_foo\" can not be built from relation instance, use %s::class in table definition instead", get_class($mock)));

		$obj->injectInstanceOnRelation('related_entity', $mock);
		$obj->createTableConstraints();
	}

	public function testInjectRelationAndApiCreateObject() {
		// @TODO: This should pass and call the appropriate methods in the mock object.
		$obj = new Foo($this->pdo);

		$mock = $this->createMock('miBadger\ActiveRecord\AbstractActiveRecord');

		$obj->relatedEntity = 500;
		$obj->injectInstanceOnRelation('related_entity', $mock);
		$obj->create();

		$id = $obj->getId();

		$obj2 = new Foo($this->pdo);
		$obj2->read($id);

		$this->assertEquals(500, $obj2->relatedEntity);
	}
}

class Foo extends AbstractActiveRecord {

	public $relatedEntity;

	protected function getTableDefinition(): Array
	{
		return [
			'related_entity' => 
			[
				'value' => &$this->relatedEntity,
				'validate' => null,
				'type' => 'INT',
				'properties' => ColumnProperty::NOT_NULL
			]
		];
	}

	public function getTableName(): string
	{
		return 'abstractactiverecord_mocktest_foo';
	}
}
