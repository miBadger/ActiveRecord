<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\ActiveRecord\ColumnProperty;

const TRAIT_PASSWORD_FIELD_PASSWORD = "password";
const TRAIT_PASSWORD_ENCRYPTION = \PASSWORD_BCRYPT;
const TRAIT_PASSWORD_STRENTH = 10;
const TRAIT_PASSWORD_FIELD_PASSWORD_RESET_TOKEN = "password_reset_token";
const TRAIT_PASSWORD_MIN_LENGTH = 8;

trait Password
{
	/** @var string The password hash. */
	protected $password;

	/** @var string|null The password reset token. */
	protected $passwordResetToken;

	protected function initPassword()
	{
		$this->extendTableDefinition(TRAIT_PASSWORD_FIELD_PASSWORD, [
			'value' => &$this->password,
			'validate' => null,
			'type' => 'VARCHAR',
			'length' => 1024,
			'properties' => ColumnProperty::NOT_NULL
		]);

		$this->extendTableDefinition(TRAIT_PASSWORD_FIELD_PASSWORD_RESET_TOKEN, [
			'value' => &$this->passwordResetToken,
			'validate' => null,
			'default' => 0,
			'type' => 'VARCHAR',
			'length' => 1024
		]);
	}

	/**
	 * Returns true if the credentials are correct.
	 *
	 * @param string $password
	 * @return boolean true if the credentials are correct
	 */
	public function isPassword($password)
	{
		if (!password_verify($password, $this->password)) {
			return false;
		}

		if (password_needs_rehash($this->password, TRAIT_PASSWORD_ENCRYPTION, ['cost' => TRAIT_PASSWORD_STRENTH])) {
			$this->setPassword($password)->sync();
		}

		return true;
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
		if (strlen($password) < TRAIT_PASSWORD_MIN_LENGTH) {
			throw new \Exception(sprintf('\'Password\' must be atleast %s characters long. %s characters provied.', self::PASSWORD_MIN_LENGTH, strlen($password)));
		}

		$passwordHash = \password_hash($password, TRAIT_PASSWORD_ENCRYPTION, ['cost' => TRAIT_PASSWORD_STRENTH]);

		if ($passwordHash === false) {
			throw new \Exception('\'Password\' hash failed.');
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
	}

	/**
	 * Clears the current password reset token
	 */
	public function clearPasswordResetToken()
	{
		$this->passwordResetToken = null;
	}
}