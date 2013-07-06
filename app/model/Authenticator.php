<?php

use Nette\Security,
	Nette\Utils\Strings;


/*
CREATE TABLE users (
	id int(11) NOT NULL AUTO_INCREMENT,
	username varchar(50) NOT NULL,
	password char(60) NOT NULL,
	role varchar(20) NOT NULL,
	PRIMARY KEY (id)
);
*/

/**
 * Users authenticator.
 */
class Authenticator extends Nette\Object implements Security\IAuthenticator
{
	/** @var UserFacade */
	private $userFacade;



	public function __construct(\IdeaMaker\Facades\UserFacade $userFacade)
	{
		$this->userFacade = $userFacade;
	}



	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;
		$row = $this->userFacade->findBy(array('user'=>$username, 'is_deleted'=>0))->fetch();

		if (!$row) {
			throw new Security\AuthenticationException('Nesprávne uživatelské jméno nebo heslo.', self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $this->userFacade->hashPassword($password)) {
			throw new Security\AuthenticationException('Nesprávne uživatelské jméno nebo heslo.', self::INVALID_CREDENTIAL);
		}

		$arr = $row->toArray();
		unset($arr['password']);
		return new Nette\Security\Identity($row->id, $row->role, $arr);
	}



	/**
	 * Computes salted password hash.
	 * @param  string
	 * @return string
	 */
	public static function calculateHash($password, $salt = NULL)
	{
		if ($password === Strings::upper($password)) { // perhaps caps lock is on
			$password = Strings::lower($password);
		}
		return crypt($password, $salt ?: '$2a$07$' . Strings::random(22));
	}

}
