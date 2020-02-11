<?php


namespace miBadger\ActiveRecord;

use PHPUnit\Framework\TestCase;
use miBadger\ActiveRecord\Traits\Address;

/**
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class AddressTraitTest extends TestCase
{
	private $pdo;

	public function setUp(): void
	{
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME), DB_USER, DB_PASS);

		$addressInstance = new AddressMock($this->pdo);
		$addressInstance->createTable();
	}

	public function tearDown(): void
	{
		$this->pdo->query('DROP TABLE IF EXISTS `address_test_mock`');
	}

	public function testCreate()
	{
		$addressInstance = new AddressMock($this->pdo);
		$addressInstance->create();

		$this->assertNotNull($addressInstance->getId());
	}

	public function testSetValues()
	{
		$addressInstance = new AddressMock($this->pdo);
		$addressInstance->setAddress("myAddress");
		$addressInstance->setZipcode("myZipcode");
		$addressInstance->setCity("myCity");
		$addressInstance->setCountry("myCountry");
		$addressInstance->create();

		$this->assertEquals($addressInstance->getAddress(), "myAddress");
		$this->assertEquals($addressInstance->getZipcode(), "myZipcode");
		$this->assertEquals($addressInstance->getCity(), "myCity");
		$this->assertEquals($addressInstance->getCountry(), "myCountry");
	}

}

class AddressMock extends AbstractActiveRecord
{
	use Address;

	protected $username;

	public function __construct(\PDO $pdo) {
		parent::__construct($pdo);
		$this->initAddress();
	}

	public function getTableDefinition(): Array
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

	public function getTableName(): string
	{
		return 'address_test_mock';
	}
}
