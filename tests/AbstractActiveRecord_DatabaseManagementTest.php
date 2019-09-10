<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord\DatabaseManagementTest;

use PHPUnit\Framework\TestCase;
use miBadger\ActiveRecord\AbstractActiveRecord;

/**
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class AbstractActiveRecord_DatabaseManagementTest extends TestCase
{
	/** @var \PDO The PDO. */
	private $pdo;

	public function setUp()
	{
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME), DB_USER, DB_PASS);
	}

	public function tearDown()
	{
		$this->pdo->query('DROP TABLE IF EXISTS `test_mock_blogpost`');
		$this->pdo->query('DROP TABLE IF EXISTS `test_mock_user`');
		$this->pdo->query('DROP TABLE IF EXISTS `test_constraint_exception`');
	}

	public function testCreateUserTable()
	{
		$user = new UserRecordTestMock($this->pdo);
		$user->createTable();

		$key = 'Tables_in_'.DB_NAME;
		$pdoStatement = $this->pdo->query('show tables;');
		$this->assertEquals([$key => 'test_mock_user'], $pdoStatement->fetch());
	}

	public function testCreateBlogPostTable()
	{
		$blogpost = new BlogPostRecordTestMock($this->pdo);
		$blogpost->createTable();

		$key = 'Tables_in_'.DB_NAME;
		$pdoStatement = $this->pdo->query('show tables;');
		$this->assertEquals([$key => 'test_mock_blogpost'], $pdoStatement->fetch());
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Column field has invalid type "NULL"
	 */
	public function testCreateNullField()
	{
		$entity = new NullTypeFieldRecordMock($this->pdo);
		$entity->createTable();
	}
	
	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Can not read the non-existent active record entry 1 from the `test_mock_blogpost` table.
	 */
	public function testCreateConstraint()
	{
		$user = new UserRecordTestMock($this->pdo);
		$user->createTable();	

		$blogpost = new BlogPostRecordTestMock($this->pdo);
		$blogpost->createTable();

		$blogpost->createTableConstraints();

		// Create new user & blogpost
		$user->setUsername("Badger");
		$user->create();

		$blogpost->setAuthor($user);
		$blogpost->create();

		$savedPostId = $blogpost->getId();

		$user->delete();

		(new BlogPostRecordTestMock($this->pdo))->read($savedPostId);
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordException
	 * @expectedExceptionMessage Relation constraint on column "author" of table "test_constraint_exception" does not contain a valid ActiveRecord instance
	 */
	public function testInvalidConstraint()
	{
		$mock = new ConstraintExceptionTestMock($this->pdo);
		$mock->createTable();
		$mock->createTableConstraints();
	}

}

class UserRecordTestMock extends AbstractActiveRecord
{
	/** @var string|null The field. */
	protected $username;

	/**
	 * {@inheritdoc}
	 */
	public function getTableName()
	{
		return 'test_mock_user';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTableDefinition()
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

class BlogPostRecordTestMock extends AbstractActiveRecord
{
	/** @var string|null The field. */
	protected $author;

	/**
	 * {@inheritdoc}
	 */
	public function getTableName()
	{
		return 'test_mock_blogpost';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTableDefinition()
	{
		return [
			'author' => 
			[
				'value' => &$this->author,
				'relation' => new UserRecordTestMock($this->pdo)
			]
		];
	}

	public function setAuthor(UserRecordTestMock $user)
	{
		$this->author = $user->getId();
	}

	public function getAuthor() 
	{
		return ($this->author !== null) ? (new UserRecordTestMock($this->pdo))->read($this->author) : null;
	}
}

/**
 * The abstract active record columns exception test mock class.
 */
class NullTypeFieldRecordMock extends AbstractActiveRecord
{
	private $field;

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
				'type' => null,
				'length' => 256,
				'properties' => null
			]
		];
	}

	public function getTableName()
	{
		return 'nulltype_field_record_mock_test';
	}
}


class ConstraintExceptionTestMock extends AbstractActiveRecord
{
	/** @var string|null The field. */
	protected $author;

	/**
	 * {@inheritdoc}
	 */
	public function getTableName()
	{
		return 'test_constraint_exception';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getTableDefinition()
	{
		return [
			'author' => 
			[
				'value' => &$this->author,
				'relation' => "bla"
			]
		];
	}
}
