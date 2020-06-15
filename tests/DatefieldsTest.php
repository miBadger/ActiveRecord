<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord;

use PHPUnit\Framework\TestCase;
use miBadger\ActiveRecord\Traits\Datefields;

/**
 * The abstract active record test class.
 *
 * @since 1.0.0
 */
class DatefieldsTraitTest extends TestCase
{
	private $pdo;

	public function setUp(): void
	{
		$this->pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME), DB_USER, DB_PASS);
		$this->pdo->query('CREATE TABLE IF NOT EXISTS `datefields_test_mock` (`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `last_modified` DATETIME, `created` DATETIME, `value` VARCHAR(256) )');
	}

	public function tearDown(): void
	{
		$this->pdo->query('DROP TABLE IF EXISTS `datefields_test_mock`');
	}

	public function testInstall()
	{
		$this->pdo->query('DROP TABLE IF EXISTS `datefields_test_mock`');
		// Create table in database from ActiveRecord definition
		$entity = new DatefieldsRecordTestMock($this->pdo);
		$entity->createTable();

		$pdoStatement = $this->pdo->query('describe datefields_test_mock;');

		$results = $pdoStatement->fetchAll();

		$this->assertEquals($results[0]['Field'], 'id');
		$this->assertEquals($results[1]['Field'], 'created');
		$this->assertEquals($results[2]['Field'], 'last_modified');
		$this->assertEquals($results[3]['Field'], 'value');
	}

	public function testCreate()
	{
		$entity = new DatefieldsRecordTestMock($this->pdo);
		$entity->create();

		$this->assertInstanceOf('\DateTime', $entity->getCreationDate());
		$this->assertEquals((new \DateTime('now'))->format('Y-m-d H:i:s'), $entity->getCreationDate()->format('Y-m-d H:i:s'));
	}

	public function testUpdate()
	{
		$entity = new DatefieldsRecordTestMock($this->pdo);
		$entity->create();

		sleep(1);
		$entity->update();

		$this->assertGreaterThan($entity->getCreationDate(), $entity->getLastModifiedDate());
	}
}

class DatefieldsRecordTestMock extends AbstractActiveRecord
{
	use Datefields;

	private $value;

	public function __construct(\PDO $pdo)
	{
		parent::__construct($pdo);
		$this->initDatefields();
	}

	public function getTableDefinition(): Array
	{
		return [
			'value' => 
			[
				'value' => &$this->value,
				'validate' => null,
				'type' => 'VARCHAR',
				'length' => 256,
				'properties' => null
			]
		];
	}

	public function getTableName(): string
	{
		return 'datefields_test_mock';
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}
}