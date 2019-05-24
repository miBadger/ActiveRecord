<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord;

use PHPUnit\Framework\TestCase;
use miBadger\ActiveRecord\Traits\ManyToManyRelation;

/**
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class ManyToManyRelationTest extends TestCase
{
	private $pdo;

	public function setUp()
	{
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', 'localhost', DB_NAME), DB_USER, DB_PASS);

		$person = new PersonMock($this->pdo);
		$person->createTable();

		$friend = new FriendMock($this->pdo);
		$friend->createTable();
		$friend->createTableConstraints();
	}

	public function tearDown()
	{
		$this->pdo->query('DROP TABLE IF EXISTS `friend_test_mock`');
		$this->pdo->query('DROP TABLE IF EXISTS `person_test_mock`');
	}

	public function testCreateTable()
	{
		$this->pdo->query('DROP TABLE IF EXISTS `friend_test_mock`');

		$friend = new FriendMock($this->pdo);
		$friend->createTable();
		$friend->createTableConstraints();

		$key = 'Tables_in_'.DB_NAME;
		$pdoStatement = $this->pdo->query('show tables;');


		$tables = [];
		foreach ($pdoStatement->fetchAll() as $row) {
			$tables[] = $row[$key];
		}

		$this->assertTrue(in_array('friend_test_mock', $tables));
	}


	public function testCreate()
	{
		$alice = new PersonMock($this->pdo);
		$alice->setName("alice");
		$alice->create();

		$bob = new PersonMock($this->pdo);
		$bob->setName("bob");
		$bob->create();

		$friend = new FriendMock($this->pdo);
		$friend->setLeftFriend($alice);
		$friend->setRightFriend($bob);
		$friend->create();

		$id = $friend->getId();

		$checkFriend = new FriendMock($this->pdo);
		$checkFriend->read($id);

		$this->assertEquals($checkFriend->getLeftFriend()->getId(), $alice->getId());
		$this->assertEquals($checkFriend->getRightFriend()->getId(), $bob->getId());
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not read the non-existent active record entry 1 from the `friend_test_mock` table.
	 */
	public function testTableConstraints()
	{
		$alice = new PersonMock($this->pdo);
		$alice->setName("alice");
		$alice->create();

		$bob = new PersonMock($this->pdo);
		$bob->setName("bob");
		$bob->create();		

		$friend = new FriendMock($this->pdo);
		$friend->setLeftFriend($alice);
		$friend->setRightFriend($bob);
		$friend->create();

		$id = $friend->getId();

		$bob->delete();

		$checkFriend = new FriendMock($this->pdo);
		$checkFriend->read($id);
	}

}



class PersonMock extends AbstractActiveRecord
{
	protected $name;

	public function getActiveRecordTableDefinition()
	{
		return [
			'name' => 
			[
				'value' => &$this->name,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => null
			]
		];
	}

	public function getActiveRecordTable() 
	{
		return 'person_test_mock';
	}

	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}

}


class FriendMock extends AbstractActiveRecord
{
	use ManyToManyRelation;

	protected $leftFriend;

	protected $rightFriend;

	public function __construct($pdo)
	{
		parent::__construct($pdo);
		$this->initManyToManyRelation(new PersonMock($pdo), $this->leftFriend, new PersonMock($pdo), $this->rightFriend);
	}

	public function getActiveRecordTable() 
	{
		return 'friend_test_mock';
	}

	protected function getActiveRecordTableDefinition()
	{
		return [];
	}

	public function getLeftFriend()
	{
		if ($this->leftFriend === null) {
			return null;
		}
		return (new PersonMock($this->pdo))->read($this->leftFriend);
	}
	
	public function setLeftFriend(PersonMock $leftFriend)
	{
		$this->leftFriend = $leftFriend->getId();
	}

	public function getRightFriend()
	{
		if ($this->rightFriend === null) {
			return null;
		}
		return (new PersonMock($this->pdo))->read($this->rightFriend);
	}
	
	public function setRightFriend(PersonMock $rightFriend)
	{
		$this->rightFriend = $rightFriend->getId();
	}

}

