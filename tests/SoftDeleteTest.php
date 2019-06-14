<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord;

use PHPUnit\Framework\TestCase;
use miBadger\ActiveRecord\Traits\SoftDelete;

/**
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class SoftDeleteTest extends TestCase
{
	private $pdo;

	public function setUp()
	{
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME), DB_USER, DB_PASS);
		$this->pdo->query('CREATE TABLE IF NOT EXISTS `soft_delete_test_mock` (`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `value` VARCHAR(255), `soft_delete` INT(1) )');
	}

	public function tearDown()
	{
		$this->pdo->query('DROP TABLE IF EXISTS `soft_delete_test_mock`');
	}

	public function testInstall()
	{
		$this->pdo->query('DROP TABLE IF EXISTS `soft_delete_test_mock`');
		// Create table in database from ActiveRecord definition
		$entity = new SoftDeleteRecordTestMock($this->pdo);
		$entity->createTable();

		$pdoStatement = $this->pdo->query('describe soft_delete_test_mock;');

		$results = $pdoStatement->fetchAll();

		$this->assertEquals($results[0]['Field'], 'id');
		$this->assertEquals($results[1]['Field'], 'soft_delete');
		$this->assertEquals($results[2]['Field'], 'value');
	}

	public function testSearch()
	{
		$entity = new SoftDeleteRecordTestMock($this->pdo);
		
		$reflection = new \ReflectionClass($entity);
		$reflection_property = $reflection->getProperty('registeredSearchHooks');
		$reflection_property->setAccessible(true);

		$this->assertArrayHasKey($entity->getSoftDeleteFieldName(), $reflection_property->getValue($entity));

		$entity = new SoftDeleteRecordTestMock($this->pdo);
		$entity->setValue("Deleted");
		$entity->softDelete();
		$entity->create();
		
		$entity2 = new SoftDeleteRecordTestMock($this->pdo);
		$entity2->setValue("NotDeleted");
		$entity2->create();

		$results = $entity->search()->fetchAll();

		$this->assertEquals(1, count($results));
	}

	public function testSoftDelete()
	{
		$entity = new SoftDeleteRecordTestMock($this->pdo);
		$entity->setValue('SomeValue');
		$entity->create();	

		$this->assertEquals($entity->getDeletionStatus(), false);

		$entity->softDelete();
		$entity->update();

		$this->assertEquals($entity->getDeletionStatus(), true);

		$entity->softRestore();
		$entity->update();

		$this->assertEquals($entity->getDeletionStatus(), false);
	}
}

class SoftDeleteRecordTestMock extends AbstractActiveRecord
{
	use SoftDelete;

	private $value;

	public function __construct($pdo)
	{
		parent::__construct($pdo);
		$this->initSoftDelete();
	}

	public function getActiveRecordTableDefinition()
	{
		return [
			'value' => 
			[
				'value' => &$this->value,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => null
			]
		];
	}

	public function getActiveRecordTable() 
	{
		return 'soft_delete_test_mock';
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}
}