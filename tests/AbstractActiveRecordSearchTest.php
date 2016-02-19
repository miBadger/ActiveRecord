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
 * The abstract active record search test class.
 *
 * @since 1.0.0
 */
class AbstractActiveRecordSearchTest extends \PHPUnit_Framework_TestCase
{
	/** @var \PDO The PDO. */
	private $pdo;

	public function setUp()
	{
		$this->pdo = new \PDO('sqlite::memory:');
		$this->pdo->query('CREATE TABLE name (id PRIMARY KEY, field)');
		$this->pdo->query('INSERT INTO name (field) VALUES ("test")');
		$this->pdo->query('INSERT INTO name (field) VALUES ("test2")');
	}

	public function testSearch()
	{
		$abstractActiveRecord = new AbstractActiveRecordSearchTestMock($this->pdo);
		$abstractActiveRecord->search();
	}

	/**
	 * @depends testSearch
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can't search the record.
	 */
	public function testSearchException()
	{
		$abstractActiveRecord = new AbstractActiveRecordSearchExceptionTestMock($this->pdo);
		$abstractActiveRecord->search();
	}

	public function testSearchOptionNumeric()
	{
		$abstractActiveRecord = new AbstractActiveRecordSearchTestMock($this->pdo);
		$abstractActiveRecord->search(['id' => 1]);
	}

	public function testSearchOptionString()
	{
		$abstractActiveRecord = new AbstractActiveRecordSearchTestMock($this->pdo);
		$abstractActiveRecord->search(['field' => 'test']);
	}

	public function testSearchOptionArray()
	{
		$abstractActiveRecord = new AbstractActiveRecordSearchTestMock($this->pdo);
		$abstractActiveRecord->search(['field' => ['test', 'test2']]);
	}

	/**
	 * @depends testSearch
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Option key "field2" doesn't exists.
	 */
	public function testSearchOptionKeyException()
	{
		$abstractActiveRecord = new AbstractActiveRecordSearchTestMock($this->pdo);
		$abstractActiveRecord->search(['field2' => 'test']);
	}

	/**
	 * @depends testSearch
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Option value not supported.
	 */
	public function testSearchOptionValueException()
	{
		$abstractActiveRecord = new AbstractActiveRecordSearchTestMock($this->pdo);
		$abstractActiveRecord->search(['field' => new \stdClass()]);
	}
}

/**
 * The abstract active record search test mock class.
 */
class AbstractActiveRecordSearchTestMock extends AbstractActiveRecordSearch
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
 * The abstract active record search exception test mock class.
 */
class AbstractActiveRecordSearchExceptionTestMock extends AbstractActiveRecordSearchTestMock
{
	/**
	 * {@inheritdoc}
	 */
	protected function getActiveRecordName()
	{
		return 'name2';
	}
}
