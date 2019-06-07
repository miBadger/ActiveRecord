<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord;

use PHPUnit\Framework\TestCase;
use miBadger\ActiveRecord\Traits\Password;

/**
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class PasswordTraitTest extends TestCase
{
	private $pdo;

	public function setUp()
	{
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', 'localhost', DB_NAME), DB_USER, DB_PASS);

		$passwordInstance = new PasswordsRecordTestMock($this->pdo);
		$passwordInstance->createTable();
	}

	public function tearDown()
	{
		$this->pdo->query('DROP TABLE IF EXISTS `password_test_mock`');
	}

	public function testCreate()
	{
		$passwordMock = new PasswordsRecordTestMock($this->pdo);
		$passwordMock->setUsername("badger");
		$passwordMock->create();

		$this->assertNotNull($passwordMock->getId());
	}

	public function testIsPassword()
	{
		$passwordMock = new PasswordsRecordTestMock($this->pdo);
		$passwordMock->setUsername("badger");
		$passwordMock->setPassword("mibadger");
		$passwordMock->create();

		$this->assertFalse($passwordMock->isPassword(null));
		$this->assertFalse($passwordMock->isPassword("wrongpassword"));

		$this->assertTrue($passwordMock->isPassword("mibadger"));
	}

	/**
	 * @expectedException miBadger\ActiveRecord\ActiveRecordTraitException
	 * @expectedExceptionMessage Password field has not been set
	 */
	public function testValidateNullPasswordException()
	{
		$passwordMock = new PasswordsRecordTestMock($this->pdo);
		$passwordMock->create();

		$passwordMock->isPassword("mibadger");
	}

	public function testCreatePasswordResetToken()
	{
		$passwordMock = new PasswordsRecordTestMock($this->pdo);
		$passwordMock->create();

		$this->assertEquals(null, $passwordMock->getPasswordResetToken());

		$passwordMock->generatePasswordResetToken();
		$passwordMock->update();

		$this->assertNotEquals(null, $passwordMock->getPasswordResetToken());
	}

	public function testClearPasswordResetToken()
	{
		$passwordMock = new PasswordsRecordTestMock($this->pdo);
		$passwordMock->generatePasswordResetToken();
		$passwordMock->create();

		$this->assertNotEquals(null, $passwordMock->getPasswordResetToken());
		$passwordMock->clearPasswordResetToken();
		$this->assertEquals(null, $passwordMock->getPasswordResetToken());
	}
}


class PasswordsRecordTestMock extends AbstractActiveRecord
{
	use Password;

	protected $username;

	public function __construct($pdo) {
		parent::__construct($pdo);
		$this->initPassword();
	}

	public function getActiveRecordTableDefinition()
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

	public function getActiveRecordTable() 
	{
		return 'password_test_mock';
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