<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord\ExtensionTest;

use PHPUnit\Framework\TestCase;
use miBadger\Query\Query;
use miBadger\ActiveRecord\AbstractActiveRecord;
use miBadger\ActiveRecord\ColumnProperty;

/**
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class AbstractActiveRecord_ExtensionTest extends TestCase
{
	/** @var \PDO The PDO. */
	private $pdo;

	public function setUp()
	{
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME), DB_USER, DB_PASS);
		$this->pdo->query('CREATE TABLE IF NOT EXISTS `test` (`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `username` VARCHAR(255), `extra_field` VARCHAR(255))');
		$this->pdo->query('INSERT INTO `test` (`id`, `username`, `extra_field`) VALUES (1, "badger", "something")');
	}

	public function tearDown()
	{
		$this->pdo->query('DROP TABLE IF EXISTS `test`');
	}

	public function testTableExtensionCreation()
	{
		$this->pdo->query('DROP TABLE IF EXISTS `test`');

		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->createTable();

		$pdoStatement = $this->pdo->query('describe test;');

		$results = $pdoStatement->fetchAll();

		$this->assertEquals($results[0]['Field'], 'id');
		$this->assertEquals($results[1]['Field'], 'extra_field');
		$this->assertEquals($results[2]['Field'], 'username');
	}

	public function testCreateHook()
	{
		// Test 2 cases: Object method hook, Closure hook
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->setUsername("newBadger");
		$mock->setExtraField("something else");
		$var = false;

		$mock->registerCreateHook("username", function() use (&$var) {
			$var = true;
		});
		$mock->create();

		$this->assertTrue($var);
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage tableDefinition is null, most likely due to parent class not having been initialized in constructor
	 */
	public function testInvalidInitException()
	{
		new invalidInitOrderTestMock($this->pdo);
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Provided hook on column "username" is not callable
	 */
	public function testInvalidCreateHookException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerCreateHook("username", "someNonExistingFunction");
	}


	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Hook is trying to register on non-existing column "fake_column"
	 */
	public function testCreateHookInvalidColumnException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerCreateHook("fake_column", function() {
		});
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Hook is trying to register on an already registered column "username", do you have conflicting traits?
	 */
	public function testDoubleCreateHookException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerCreateHook("username", function() {
		});
		$mock->registerCreateHook("username", function() {
		});
	}

	public function testReadHook()
	{
		// Test 2 cases: Object method hook, Closure hook
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->setUsername("newBadger");
		$mock->setExtraField("something else");
		$var = false;

		$mock->registerReadHook("username", function() use (&$var) {
			$var = true;
		});
		$mock->read(1);

		$this->assertTrue($var);
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Provided hook on column "username" is not callable
	 */
	public function testInvalidReadHookException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerReadHook("username", "someNonExistingFunction");
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Hook is trying to register on non-existing column "fake_column"
	 */
	public function testReadHookInvalidColumnException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerReadHook("fake_column", function() {
		});
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Hook is trying to register on an already registered column "username", do you have conflicting traits?
	 */
	public function testDoubleReadHookException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerReadHook("username", function() {
		});
		$mock->registerReadHook("username", function() {
		});
	}

	public function testUpdateHook()
	{
		// Test 2 cases: Object method hook, Closure hook
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->read(1);
		$mock->setUsername("newestBadger");
		$var = false;

		$mock->registerUpdateHook("username", function() use (&$var) {
			$var = true;
		});
		$mock->update();

		$this->assertTrue($var);
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Provided hook on column "username" is not callable
	 */
	public function testInvalidUpdateHookException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerUpdateHook("username", "someNonExistingFunction");
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Hook is trying to register on non-existing column "fake_column"
	 */
	public function testUpdateHookInvalidColumnException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerUpdateHook("fake_column", function() {
		});
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Hook is trying to register on an already registered column "username", do you have conflicting traits?
	 */
	public function testDoubleUpdateHookException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerUpdateHook("username", function() {
		});
		$mock->registerUpdateHook("username", function() {
		});
	}

	public function testDeleteHook()
	{
		// Test 2 cases: Object method hook, Closure hook
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->read(1);
		$var = false;

		$mock->registerDeleteHook("username", function() use (&$var) {
			$var = true;
		});
		$mock->delete();

		$this->assertTrue($var);
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Provided hook on column "username" is not callable
	 */
	public function testInvalidDeleteHookException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerDeleteHook("username", "someNonExistingFunction");
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Hook is trying to register on non-existing column "fake_column"
	 */
	public function testDeleteHookInvalidColumnException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerDeleteHook("fake_column", function() {
		});
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Hook is trying to register on an already registered column "username", do you have conflicting traits?
	 */
	public function testDoubleDeleteHookException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerDeleteHook("username", function() {
		});
		$mock->registerDeleteHook("username", function() {
		});
	}

	public function testSearchHook()
	{
		// Test 2 cases: Object method hook, Closure hook
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$var = false;

		$mock->registerSearchHook("username", function() use (&$var) {
			$var = true;
		});
		$mock->search();

		$this->assertTrue($var);
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Provided hook on column "username" is not callable
	 */
	public function testInvalidSearchHookException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerSearchHook("username", "someNonExistingFunction");
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Hook is trying to register on non-existing column "fake_column"
	 */
	public function testSearchHookInvalidColumnException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerSearchHook("fake_column", function() {
		});
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Hook is trying to register on an already registered column "username", do you have conflicting traits?
	 */
	public function testDoubleSearchHookException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$mock->registerSearchHook("username", function() {
		});
		$mock->registerSearchHook("username", function() {
		});
	}

	public function testExtendTableDefinition()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);

		$value = null;
		$mock->extendTableDefinition('extra_field2', [
			'value' => &$value,
			'validate' => null,
			'type' => 'VARCHAR',
			'length' => 256
		]);

		// @TODO: Create method to extract table definition
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Table is being extended with a column that already exists, "extra_field" conflicts with your table definition
	 */
	public function testDoubleExtendTableDefinitionException()
	{
		$mock = new AbstractActiveRecordTestMock($this->pdo);
		$value = null;
		$mock->extendTableDefinition('extra_field', [
			'value' => &$value,
			'validate' => null,
			'type' => 'VARCHAR',
			'length' => 256
		]);
	}
}

trait ExtraField
{
	protected $extraField;

	protected function initExtraField()
	{
		$this->extendTableDefinition("extra_field", [
			'value' => &$this->extraField,
			'validate' => null,
			'type' => 'VARCHAR',
			'length' => 255,
			'properties' => ColumnProperty::NOT_NULL
		]);
	}

	public function setExtraField($value)
	{
		$this->extraField = $value;
	}

	public function getExtraField()
	{
		return $this->extraField;
	}
}

class invalidInitOrderTestMock extends AbstractActiveRecord
{
	use ExtraField;

	public function __construct($pdo)
	{
		$this->initExtraField();
		parent::__construct($pdo);
	}	

	protected function getActiveRecordTable()
	{
		return 'invalid_record';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getActiveRecordTableDefinition()
	{
		return [
		];
	}
}


class AbstractActiveRecordTestMock extends AbstractActiveRecord
{
	use ExtraField;

	/** @var string|null The field. */
	protected $username;

	public function __construct($pdo)
	{
		parent::__construct($pdo);
		$this->initExtraField();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getActiveRecordTable()
	{
		return 'test';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getActiveRecordTableDefinition()
	{
		return [
			'username' => 
			[
				'value' => &$this->username,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => null
			]
		];
	}

	public function getUsername()
	{
		return $this->username;
	}
	
	public function setUsername($username)
	{
		$this->username = $username;
	}
}
