<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\ActiveRecord\ColumnProperty;
use miBadger\ActiveRecord\ActiveRecordTraitException;

const TRAIT_PASSWORD_FIELD_PASSWORD = "password";
const TRAIT_PASSWORD_ENCRYPTION = \PASSWORD_BCRYPT;
const TRAIT_PASSWORD_STRENTH = 10;
const TRAIT_PASSWORD_FIELD_RESET_TOKEN = "password_reset_token";
const TRAIT_PASSWORD_MIN_LENGTH = 8;
const TRAIT_PASSWORD_FIELD_RESET_TOKEN_EXPIRY = "password_reset_token_expiry";

trait Password
{
	/** @var string The password hash. */
	protected $password;

	/** @var string|null The password reset token. */
	protected $passwordResetToken;

	/** @var string|null The password expiry date */
	protected $passwordExpiryDate;

	/**
	 * this method is required to be called in the constructor for each class that uses this trait. 
	 * It adds the fields necessary for the passwords struct to the table definition
	 */
	protected function initPassword()
	{
		$this->extendTableDefinition(TRAIT_PASSWORD_FIELD_PASSWORD, [
			'value' => &$this->password,
			'validate' => [$this, 'validatePassword'],
			'setter' => [$this, 'setPassword'],
			'type' => 'VARCHAR',
			'length' => 1024,
			'properties' => null
		]);

		$this->extendTableDefinition(TRAIT_PASSWORD_FIELD_RESET_TOKEN, [
			'value' => &$this->passwordResetToken,
			'validate' => null,
			'default' => 0,
			'type' => 'VARCHAR',
			'length' => 1024
		]);

		$this->extendTableDefinition(TRAIT_PASSWORD_FIELD_RESET_TOKEN_EXPIRY, [
			'value' => &$this->passwordExpiryDate,
			'validate' => null,
			'type' => 'DATETIME',
		]);
	}


	/**
	 * Returns whether the users password has been set
	 * @return boolean true if the user has a password
	 */
	public function hasPasswordBeenSet()
	{
		return $this->password !== null;
	}

	/**
	 * Returns true if the credentials are correct.
	 *
	 * @param string $password
	 * @return boolean true if the credentials are correct
	 */
	public function isPassword($password)
	{ 
		if (!$this->hasPasswordBeenSet())
		{
			throw new ActiveRecordTraitException("Password field has not been set");
		}

		if (!password_verify($password, $this->password)) {
			return false;
		}

		if (password_needs_rehash($this->password, TRAIT_PASSWORD_ENCRYPTION, ['cost' => TRAIT_PASSWORD_STRENTH])) {
			$this->setPassword($password)->sync();
		}

		return true;
	}

	public function validatePassword($password) {
		if (strlen($password) < TRAIT_PASSWORD_MIN_LENGTH) {
			$message = sprintf('\'Password\' must be atleast %s characters long. %s characters provided.', TRAIT_PASSWORD_MIN_LENGTH, strlen($password));
			return [false, $message];
		}
		return [true, ''];
	}

	/**
	 * Set the password.
	 *
	 * @param string $password
	 * @return $this
	 * @throws \Exception
	 */
	public function setPassword($password)
	{
		[$status, $error] = $this->validatePassword($password);
		if (!$status) {
			throw new ActiveRecordTraitException($error);
		}

		$passwordHash = \password_hash($password, TRAIT_PASSWORD_ENCRYPTION, ['cost' => TRAIT_PASSWORD_STRENTH]);

		if ($passwordHash === false) {
			throw new ActiveRecordTraitException('\'Password\' hash failed.');
		}

		$this->password = $passwordHash;

		return $this;
	}

	/**
	 * @return string The Hash of the password
	 */
	public function getPasswordHash()
	{
		return $this->password;
	}

	/**
	 * Returns the currently set password token for the entity, or null if not set
	 * @return string|null The password reset token
	 */
	public function getPasswordResetToken()
	{
		return $this->passwordResetToken;
	}

	/**
	 * Generates a new password reset token for the user
	 */
	public function generatePasswordResetToken()
	{
		$this->passwordResetToken = md5(uniqid(mt_rand(), true));

		$validityDuration = new \DateInterval('PT24H');

		$this->passwordExpiryDate = (new \DateTime('now'))->add($validityDuration)->format('Y-m-d H:i:s');
		return $this;
	}

	/**
	 * Clears the current password reset token
	 */
	public function clearPasswordResetToken()
	{
		$this->passwordResetToken = null;
		$this->passwordExpiryDate = null;
		return $this;
	}

	public function validatePasswordResetToken(string $token)
	{
		return $this->passwordResetToken !== null
			&& $token === $this->passwordResetToken
			&& (new \DateTime('now')) < (new \DateTime($this->passwordExpiryDate));
	}
	
	/**
	 * @return void
	 */
	abstract protected function extendTableDefinition(string $columnName, $definition);
	
	/**
	 * @return void
	 */
	abstract protected function registerSearchHook(string $columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerDeleteHook(string $columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerUpdateHook(string $columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerReadHook(string $columnName, $fn);

	/**
	 * @return void
	 */
	abstract protected function registerCreateHook(string $columnName, $fn);

}