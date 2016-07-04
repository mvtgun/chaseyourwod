<?php

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use Tracy\Debugger;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	const
		TABLE_NAME = 'users',
		COLUMN_ID = 'id',
		COLUMN_MAIL = 'mail',
		COLUMN_PASSWORD_HASH = 'password',
		COLUMN_ROLE = 'role',

		COLUMN_PASSWORD_CONFIRM_USER = 'potvrzeni_uzivatele';

	public $infoUsers;

	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($mail, $password) = $credentials;

		$message = "";

		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_MAIL, $mail)->fetch();

		if (!$row) {
		//	throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);
			$message = "Uživatel s tímto e-mailem u nás neexistuje.";
			return $message;

		} elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
		//	throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
			$message = "Zadané heslo není správné";
			return $message;

		} elseif ($row[self::COLUMN_PASSWORD_CONFIRM_USER]!='A') {
			//	throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
			$message = "Tento uživatel čeká na potvrzení e-mailu";
			return $message;

		} elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update(array(
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			));
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);

		$identity = new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);

		return $identity;
	}
}



class DuplicateNameException extends \Exception
{}
