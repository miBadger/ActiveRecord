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

/*
 * TODO: Test validation function
 */

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
		$this->pdo->query('CREATE TABLE IF NOT EXISTS `name` (`id` INTEGER AUTO_INCREMENT PRIMARY KEY, `field` VARCHAR(255))');
		$this->pdo->query('INSERT INTO `name` (`id`, `field`) VALUES (1, "test")');
		$this->pdo->query('INSERT INTO `name` (`id`, `field`) VALUES (2, "test2")');
		$this->pdo->query('INSERT INTO `name` (`id`, `field`) VALUES (3, NULL)');
	}

	public function tearDown()
	{
		$this->pdo->query('DROP TABLE `name`');
	}

	public function testCreate()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->setField('new');
		$abstractActiveRecord->create();

		$pdoStatement = $this->pdo->query('SELECT * FROM name WHERE `id` = 4');
		$this->assertEquals(['id' => '4', 'field' => 'new'], $pdoStatement->fetch());
	}

	/**
	 * @depends testCreate
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage SQLSTATE[42S02]: Base table or view not found: 1146 Table 'mibadger_activerecord_phpunit_tests.name2' doesn't exist
	 */
	public function testCreateTableException()
	{
		$abstractActiveRecord = new AbstractActiveRecordTableExceptionTestMock($this->pdo);
		$abstractActiveRecord->create();
	}

	/**
	 * @depends testCreate
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'field2' in 'field list'
	 */
	public function testCreateColumnsException()
	{
		$abstractActiveRecord = new AbstractActiveRecordColumnsExceptionTestMock($this->pdo);
		$abstractActiveRecord->create();
	}

	public function testRead()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->read(1);

		$this->assertEquals(1, $abstractActiveRecord->getId());
		$this->assertEquals('test', $abstractActiveRecord->getField());
	}

	/**
	 * @depends testRead
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not read the non-existent active record entry 4 from the `name` table
	 */
	public function testReadNonExistentId()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->read(4);
	}

	/**
	 * @depends testRead
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage SQLSTATE[42S02]: Base table or view not found: 1146 Table 'mibadger_activerecord_phpunit_tests.name2' doesn't exist
	 */
	public function testReadTableException()
	{
		$abstractActiveRecord = new AbstractActiveRecordTableExceptionTestMock($this->pdo);
		$abstractActiveRecord->read(1);
	}

	public function testUpdate()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->read(1);
		$abstractActiveRecord->setField('test2');
		$abstractActiveRecord->update();

		$pdoStatement = $this->pdo->query('SELECT * FROM `name` WHERE `id` = 1');
		$this->assertEquals(['id' => '1', 'field' => 'test2'], $pdoStatement->fetch());
	}

	/**
	 * @depends testUpdate
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage SQLSTATE[42S02]: Base table or view not found: 1146 Table 'mibadger_activerecord_phpunit_tests.name2' doesn't exist
	 */
	public function testUpdateTableException()
	{
		$abstractActiveRecord = new AbstractActiveRecordTableExceptionTestMock($this->pdo);
		$abstractActiveRecord->update();
	}

	/**
	 * @depends testUpdate
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'field2' in 'field list'
	 */
	public function testUpdateColumnsException()
	{
		$abstractActiveRecord = new AbstractActiveRecordColumnsExceptionTestMock($this->pdo);
		$abstractActiveRecord->update();
	}

	public function testDelete()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->read(1);
		$abstractActiveRecord->delete();

		$pdoStatement = $this->pdo->query('SELECT * FROM `name` WHERE `id` = 1');
		$this->assertFalse($pdoStatement->fetch());
	}

	/**
	 * @depends testDelete
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage SQLSTATE[42S02]: Base table or view not found: 1146 Table 'mibadger_activerecord_phpunit_tests.name2' doesn't exist
	 */
	public function testDeleteTableException()
	{
		$abstractActiveRecord = new AbstractActiveRecordTableExceptionTestMock($this->pdo);
		$abstractActiveRecord->delete();
	}

	public function testSyncCreate()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->read(1);
		$abstractActiveRecord->delete();
		$abstractActiveRecord->sync();

		$pdoStatement = $this->pdo->query('SELECT * FROM `name` ORDER BY `id` DESC');
		$this->assertEquals(['id' => '4', 'field' => 'test'], $pdoStatement->fetch());
	}

	public function testSyncUpdate()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->read(1);
		$abstractActiveRecord->setField('test2');
		$abstractActiveRecord->sync();

		$pdoStatement = $this->pdo->query('SELECT * FROM `name` WHERE `id` = 1');
		$this->assertEquals(['id' => '1', 'field' => 'test2'], $pdoStatement->fetch());
	}

	public function testExists()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$this->assertFalse($abstractActiveRecord->exists());

		$abstractActiveRecord->read(1);
		$this->assertTrue($abstractActiveRecord->exists());
	}

	public function testFill()
	{
		$attributesActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$attributesActiveRecord->fill(['field' => 'new']);

		$this->assertEquals('new', $attributesActiveRecord->getField());
	}

	public function testSearchOne()
	{
		$attributesActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$searchResult = $attributesActiveRecord->search()->where(Query::Like('field', 'Test'))->fetch();

		$this->assertEquals(1, $searchResult->getId());
	}

	/**
	 * @depends testRead
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not search one non-existent entry from the `name` table.
	 */
	public function testSearchOneNonExistentId()
	{
		$attributesActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$attributesActiveRecord->search()->where(Query::Equal('id', 4))->fetch();
	}

	/**
	 * @depends testSearchOne
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage SQLSTATE[42S02]: Base table or view not found: 1146 Table 'mibadger_activerecord_phpunit_tests.name2' doesn't exist
	 */
	public function testSearchOneException()
	{
		$abstractActiveRecord = new AbstractActiveRecordTableExceptionTestMock($this->pdo);
		$abstractActiveRecord->search()->fetch();
	}

	public function testSearch()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$result = $abstractActiveRecord->search()->fetchAll();

		$this->assertCount(3, $result);
	}

	/**
	 * @depends testSearch
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage SQLSTATE[42S02]: Base table or view not found: 1146 Table 'mibadger_activerecord_phpunit_tests.name2' doesn't exist
	 */
	public function testSearchException()
	{
		$abstractActiveRecord = new AbstractActiveRecordTableExceptionTestMock($this->pdo);
		$abstractActiveRecord->search()->fetchAll();
	}

	/**
	 * @depends testSearch
	 */
	public function testSearchWhereKeyFunction()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$result = $abstractActiveRecord->search()->where(Query::Like('UPPER(field)', 'TEST'))->fetchAll();

		$this->assertCount(1, $result);
	}

	/**
	 * @depends testSearch
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage SQLSTATE[42S22]: Column not found: 1054 Unknown column 'field2' in 'where clause'
	 */
	public function testSearchWhereKeyException()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->search()->where(Query::Like('field2', 'test'))->fetchAll();
	}

	/**
	 * @depends testSearch
	 */
	public function testSearchWhereValueNumeric()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$result = $abstractActiveRecord->search()->where(Query::Equal('id', 1))->fetchAll();

		$this->assertCount(1, $result);
	}

	/**
	 * @depends testSearch
	 */
	public function testSearchWhereValueString()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$result = $abstractActiveRecord->search()->where(Query::Like('field', 'test'))->fetchAll();

		$this->assertCount(1, $result);
	}

	/**
	 * @depends testSearch
	 */
	public function testSearchWhereValueInSingle()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$result = $abstractActiveRecord->search()->where(Query::In('field', 'test'))->fetchAll();

		$this->assertCount(1, $result);
	}

	/**
	 * @depends testSearch
	 */
	public function testSearchWhereValueInArray()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$result = $abstractActiveRecord->search()->where(Query::In('field', ['test', 'test2']))->fetchAll();

		$this->assertCount(2, $result);
	}

	/**
	 * @depends testSearch
	 */
	public function testSearchWhereValueNull()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$result = $abstractActiveRecord->search()->where(Query::Is('field', NULL))->fetchAll();

		$this->assertCount(1, $result);
	}

	/**
	 * @depends testSearch
	 */
	public function testSearchOrderBy()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$result = $abstractActiveRecord->search()->orderBy('id', 'DESC')->fetchAll();

		$this->assertCount(3, $result);
	}

	/**
	 * @depends testSearch
	 */
	public function testSearchLimit()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$result = $abstractActiveRecord->search()->limit(1)->fetchAll();

		$this->assertCount(1, $result);
	}

	/**
	 * @depends testSearch
	 */
	public function testSearchOffset()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$result = $abstractActiveRecord->search()->limit(10)->offset(1)->fetchAll();

		$this->assertCount(2, $result);
	}
}

/**
 * The abstract active record test mock class.
 */
class AbstractActiveRecordTestMock extends AbstractActiveRecord
{
	/** @var string|null The field. */
	protected $field;

	/**
	 * {@inheritdoc}
	 */
	protected function getTableName()
	{
		return 'name';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTableDefinition()
	{
		return [
			'field' => 
			[
				'value' => &$this->field,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => null
			]
		];
	}

	/**
	 * Returns the field.
	 *
	 * @return string|null the field.
	 */
	public function getField()
	{
		return $this->field;
	}

	/**
	 * Set the field.
	 *
	 * @param string $field
	 * @return null
	 */
	public function setField($field)
	{
		$this->field = $field;
	}
}

/**
 * The abstract active record table exception test mock class.
 */
class AbstractActiveRecordTableExceptionTestMock extends AbstractActiveRecordTestMock
{
	/**
	 * {@inheritdoc}
	 */
	public function getId()
	{
		return 1;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTableName()
	{
		return 'name2';
	}
}

/**
 * The abstract active record columns exception test mock class.
 */
class AbstractActiveRecordColumnsExceptionTestMock extends AbstractActiveRecordTestMock
{
	/**
	 * {@inheritdoc}
	 */
	public function getId()
	{
		return 1;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTableDefinition()
	{
		return [
			'field' => 
			[
				'value' => &$this->field,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => null
			],
			'field2' => 
			[
				'value' => &$this->field,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => null
			]
		];
	}
}
