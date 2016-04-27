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
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class AbstractActiveRecordTest extends \PHPUnit_Framework_TestCase
{
	/** @var \PDO The PDO. */
	private $pdo;

	public function setUp()
	{
		$this->pdo = new \PDO('sqlite::memory:');
		$this->pdo->query('CREATE TABLE IF NOT EXISTS name (id INTEGER PRIMARY KEY, field VARCHAR(255))');
		$this->pdo->query('INSERT INTO name (field) VALUES ("test")');
	}

	public function tearDown()
	{
		$this->pdo->query('DROP TABLE name');
	}

	public function testCreate()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->setField('test');
		$abstractActiveRecord->create();

		$pdoStatement = $this->pdo->query('SELECT * FROM name WHERE `id` = 2');
		$this->assertEquals(['id' => '2', 'field' => 'test'], $pdoStatement->fetch());
	}

	/**
	 * @depends testCreate
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not create a new active record entry in the `name2` table.
	 */
	public function testCreateNameException()
	{
		$abstractActiveRecord = new AbstractActiveRecordNameExceptionTestMock($this->pdo);
		$abstractActiveRecord->create();
	}

	/**
	 * @depends testCreate
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not create a new active record entry in the `name` table.
	 */
	public function testCreateDataException()
	{
		$abstractActiveRecord = new AbstractActiveRecordDataExceptionTestMock($this->pdo);
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
	 * @expectedExceptionMessage Can not read active record entry 1 from the `name2` table.
	 */
	public function testReadNameException()
	{
		$abstractActiveRecord = new AbstractActiveRecordNameExceptionTestMock($this->pdo);
		$abstractActiveRecord->read(1);
	}

	/**
	 * @depends testRead
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not read the expected column `field2`. It's not returnd by the `name` table
	 */
	public function testReadDataException()
	{
		$abstractActiveRecord = new AbstractActiveRecordDataExceptionTestMock($this->pdo);
		$abstractActiveRecord->read(1);
	}

	public function testUpdate()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->read(1);
		$abstractActiveRecord->setField('test2');
		$abstractActiveRecord->update();

		$pdoStatement = $this->pdo->query('SELECT * FROM name WHERE `id` = 1');
		$this->assertEquals(['id' => '1', 'field' => 'test2'], $pdoStatement->fetch());
	}

	/**
	 * @depends testUpdate
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not update a non-existent active record entry to the `name` table.
	 */
	public function testUpdateIdException()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->update();
	}

	/**
	 * @depends testUpdate
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not update active record entry 1 to the `name2` table.
	 */
	public function testUpdateNameException()
	{
		$abstractActiveRecord = new AbstractActiveRecordNameExceptionTestMock($this->pdo);
		$abstractActiveRecord->update();
	}

	/**
	 * @depends testUpdate
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not update active record entry 1 to the `name` table.
	 */
	public function testUpdateDataException()
	{
		$abstractActiveRecord = new AbstractActiveRecordDataExceptionTestMock($this->pdo);
		$abstractActiveRecord->update();
	}

	public function testDelete()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->read(1);
		$abstractActiveRecord->delete();

		$pdoStatement = $this->pdo->query('SELECT * FROM name WHERE `id` = 1');
		$this->assertFalse($pdoStatement->fetch());
	}

	/**
	 * @depends testDelete
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not delete a non-existent active record entry from the `name` table.
	 */
	public function testDeleteIdException()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$abstractActiveRecord->delete();
	}

	/**
	 * @depends testDelete
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not delete active record entry 1 from the `name2` table.
	 */
	public function testDeleteNameException()
	{
		$abstractActiveRecord = new AbstractActiveRecordNameExceptionTestMock($this->pdo);
		$abstractActiveRecord->delete();
	}

	public function testExists()
	{
		$abstractActiveRecord = new AbstractActiveRecordTestMock($this->pdo);
		$this->assertFalse($abstractActiveRecord->exists());

		$abstractActiveRecord->read(1);
		$this->assertTrue($abstractActiveRecord->exists());
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
	protected function getActiveRecordName()
	{
		return 'name';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getActiveRecordData()
	{
		return [
			'field' => &$this->field
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
 * The abstract active record name exception test mock class.
 */
class AbstractActiveRecordNameExceptionTestMock extends AbstractActiveRecordTestMock
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
	protected function getActiveRecordName()
	{
		return 'name2';
	}
}

/**
 * The abstract active record data exception test mock class.
 */
class AbstractActiveRecordDataExceptionTestMock extends AbstractActiveRecordTestMock
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
	protected function getActiveRecordData()
	{
		return [
			'field' => &$this->field,
			'field2' => &$this->field
		];
	}
}
