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
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', 'localhost', DB_NAME), DB_USER, DB_PASS);
	}

	public function tearDown()
	{
		$this->pdo->query('DROP TABLE IF EXISTS `test_mock_blogpost`');
		$this->pdo->query('DROP TABLE IF EXISTS `test_mock_user`');
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


}

class UserRecordTestMock extends AbstractActiveRecord
{
	/** @var string|null The field. */
	protected $username;

	/**
	 * {@inheritdoc}
	 */
	protected function getActiveRecordTable()
	{
		return 'test_mock_user';
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

class BlogPostRecordTestMock extends AbstractActiveRecord
{
	/** @var string|null The field. */
	protected $author;

	/**
	 * {@inheritdoc}
	 */
	protected function getActiveRecordTable()
	{
		return 'test_mock_blogpost';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getActiveRecordTableDefinition()
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


