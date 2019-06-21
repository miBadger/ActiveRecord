<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord\AutoAPITest;

use PHPUnit\Framework\TestCase;
use miBadger\ActiveRecord\AbstractActiveRecord;
use miBadger\ActiveRecord\Traits\AutoApi;
use miBadger\ActiveRecord\Traits\SoftDelete;
use miBadger\ActiveRecord\ColumnProperty;

/**
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class AbstractActiveRecord_OperationsTest extends TestCase
{
	/** @var \PDO The PDO. */
	private $pdo;

	public function setUp()
	{
		// Note: If using sqlite, Exception messages will differ!
		// $this->pdo = new \PDO('sqlite::memory:');
		// $this->pdo->query('CREATE TABLE IF NOT EXISTS `name` (`id` INTEGER PRIMARY KEY, `field` VARCHAR(255))');
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME), DB_USER, DB_PASS);

		$e = new TestEntity($this->pdo);
		$e->createTable();
	}

	public function tearDown()
	{
		$this->pdo->query('DROP TABLE `test_entity_mock`');
	}


	private function createMockData()
	{
		$cEntity = new TestEntity($this->pdo);
		$cEntity->setName("badger");
		$cEntity->setBirthday(new \DateTime("1990-01-01"));
		$cEntity->create();		

		$cEntity2 = new TestEntity($this->pdo);
		$cEntity2->setName("turtle");
		$cEntity2->setBirthday(new \DateTime("1990-01-01"));
		$cEntity2->create();

		return [$cEntity->getId(), $cEntity2->getId()];
	}

	public function testApiRead()
	{
		// Create sample data
		$id = $this->createMockData()[0];

		// Test read
		$entity = new TestEntity($this->pdo);
		$res = $entity->apiRead($id, ['name', 'birthday', 'soft_delete']);
		$this->assertEquals(array_keys($res), ['name', 'birthday', 'soft_delete']);
		$this->assertEquals($res['name'], 'badger');
	}

	public function testApiCreate()
	{
		$input = [
			'name' => 'badger',
			'birthday' => '1990-01-01'
		];

		$entity = new TestEntity($this->pdo);
		[$errors, $data] = $entity->apiCreate($input, ['name', 'birthday']);

		$this->assertNull($errors);
		$this->assertNotNull($data);
		$this->assertNotNull($entity->getId());
	}

	public function testAipCreateWithValidInputSubset()
	{
		$input = [
			'name' => 'badger',
		];

		$entity = new TestEntity($this->pdo);
		[$errors, $data] = $entity->apiCreate($input, ['name', 'birthday']);

		$this->assertNull($errors);
		$this->assertNotNull($data);
		$this->assertNotNull($entity->getId());
	}

	public function testApiCreateMissingKeys()
	{
		$input = [
			'birthday' => '1990-01-01'
		];

		$entity = new TestEntity($this->pdo);
		[$errors, $data] = $entity->apiCreate($input, ['name', 'birthday']);
		$this->assertNotNull($errors);
		$this->assertNull($data);
	}

	public function testApiCreateExcessFields() 
	{
		$input = [
			'name' => 'badger',
			'birthday' => '1990-01-01',
			'blablabla' => 0
		];

		$entity = new TestEntity($this->pdo);
		[$errors, $data] = $entity->apiCreate($input, ['name', 'birthday', 'blablabla']);
		$this->assertNotNull($errors);
	}

	public function testApiCreateInvalidInput()
	{
		$input = [
			'name' => 'badger',
			'birthday' => 'NOT_A_VALID_INPUT'
		];

		$entity = new TestEntity($this->pdo);
		[$errors, $data] = $entity->apiCreate($input, ['name', 'birthday']);
		$this->assertNotNull($errors);
	}

	public function testApiUpdate()
	{
		$id = $this->createMockData()[0];

		$entity = new TestEntity($this->pdo);
		$entity->read($id);

		$inputData = [
			'name' => 'bear'
		];

		[$errors, $data] = $entity->apiUpdate($inputData, ['name', 'birthday']);
		$this->assertNotNull($data);
		$this->assertNull($errors);
	}

	public function testApiUpdateImmutables()
	{
		$id = $this->createMockData()[0];

		$entity = new TestEntity($this->pdo);
		$entity->read($id);

		$inputData = [
			'birthday' => '2018-01-01'
		];

		[$errors, $data] = $entity->apiUpdate($inputData, ['name', 'birthday']);
		$this->assertNull($data);
		$this->assertNotNull($errors);
	}

	public function testApiUpdateValidationFunction()
	{
		'provided value is not a valid date';

		$id = $this->createMockData()[0];

		$entity = new TestEntity($this->pdo);
		$entity->read($id);

		$inputData = [
			'favorite_day' => 'not_a_valid_day'
		];

		[$errors, $data] = $entity->apiUpdate($inputData, ['name', 'favorite_day']);
		$this->assertNull($data);
		$this->assertNotNull($errors);
	}


	public function testApiUpdateDatabasePermissions()
	{
		'provided value is not a valid date';

		$id = $this->createMockData()[0];

		$entity = new TestEntity($this->pdo);
		$entity->read($id);

		$inputData = [
			'name' => null
		];

		[$errors, $data] = $entity->apiUpdate($inputData, ['name', 'favorite_day']);
		$this->assertNull($data);
		$this->assertNotNull($errors);
	}

	public function testApiSearch()
	{
		[$id, $id2] = $this->createMockData();

		$entity = new TestEntity($this->pdo);

		// Basic Search
		$results = $entity->apiSearch(['order_by' => 'name', 'order_direction' => 'ASC'], ['name', 'birthday']);
		$this->assertCount(2, $results);
		$this->assertEquals($results[0]['name'], 'badger');
		$this->assertEquals($results[1]['name'], 'turtle');

		// Test sorting
		$results = $entity->apiSearch(['order_by' => 'name', 'order_direction' => 'DESC'], ['name', 'birthday']);
		$this->assertCount(2, $results);
		$this->assertEquals($results[0]['name'], 'turtle');
		$this->assertEquals($results[1]['name'], 'badger');
	}	

	// testApiUpdate() -> Check keys allowed to be updated, Check validation function, check database permissions, check 
	// testApiSearch() -> Check keys returned, check search modifiers, check 
}

class TestEntity extends AbstractActiveRecord
{
	use AutoApi;
	use SoftDelete;

	private $birthday;

	private $favoriteDay;

	private $name;

	public function __construct(\PDO $pdo)
	{
		parent::__construct($pdo);
		$this->initSoftDelete();
	}

	public function getTableDefinition()
	{
		return [
			'name' => 
			[
				'value' => &$this->name,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => ColumnProperty::NOT_NULL | ColumnProperty::UNIQUE
			],
			'birthday' => 
			[
				'value' => &$this->birthday,
				'validate' => function ($value) {
					try {
						$date = new \DateTime($value);
						return [true, ''];
					} catch (\Exception $e) {
						return [false, 'provided value is not a valid date'];
					}
				},
				'type' => 'DATETIME',
				'properties' => ColumnProperty::IMMUTABLE
			],
			'favorite_day' => 
			[
				'value' => &$this->favoriteDay,
				'validate' => function ($value) {
					try {
						$date = new \DateTime($value);
						return [true, ''];
					} catch (\Exception $e) {
						return [false, 'provided value is not a valid date'];
					}
				},
				'type' => 'DATETIME'
			]
		];
	}

	public function getTableName() 
	{
		return 'test_entity_mock';
	}

	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}

	public function getBirthday()
	{
		return new \DateTime($this->birthday);
	}
	
	public function setBirthday(\DateTime $birthday)
	{
		$this->birthday = $birthday->format('Y-m-d');
	}
}