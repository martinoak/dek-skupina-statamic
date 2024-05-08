<?php

namespace Statamic\Addons\DekDev;

use PDO;
use Statamic\Data\Users\User;

class LoginManager
{
	/** @var string */
	private static $table = 'dek_dev';

	/* @vcar PDO */
	private $pdo;

	public function __construct()
	{
		$this->pdo = new PDO('mysql:host='.env('DB_HOST').';dbname='.env('DB_DATABASE'), env('DB_USERNAME'), env('DB_PASSWORD'));
	}

	/**
	 * All logins
	 * @param string $order Column name + ACS/DESC
	 * @return LoginCollection
	 * @throws LoginException
	 */
	public function getLogins($order = null)
	{
		$o = $order ?: 'action DESC';
		$st = $this->pdo->prepare('SELECT * FROM '.self::$table.' ORDER BY '.$o);
		if (!$st->execute()) {
			throw new LoginException($st);
		}
		return new LoginCollection($st);
	}

	/**
	 * Unique login
	 * @param User $user
	 * @param string $ip
	 * @param string $token
	 * @return Login | false
	 * @throws LoginException
	 */
	public function getLogin(User $user, $ip, $token)
	{
		$st = $this->pdo->prepare('SELECT * FROM '.self::$table.' WHERE user_id = :user_id AND ip = :ip AND token = :token');
		$result = $st->execute([':user_id' => $user->id(), ':ip' => $ip, ':token' => $token]);  // user muze byt prihlasen z vice IP i z nekolika browseru, unikatni login je tedy user_id + ip + token
		if (!$result) {
			throw new LoginException($st);
		}
		$row = $st->fetch(PDO::FETCH_ASSOC);
		return $row ? Login::fromDb($row) : false;
	}

	/**
	 * @param Login $login
	 * @throws LoginException
	 */
	public function save(Login $login)
	{
		$set = $columns = $params = [];
		foreach ($login->toArray() as $key => $val) {
			if ($val !== null && $key !== 'id') {
				$set[] = "$key = :$key";
				$columns[] = "$key";
				$params[":$key"] = $val;
			}
		}

		if ($login->getId() === null) {
			// Insert
			$st = $this->pdo->prepare('INSERT INTO '.self::$table.' ('.implode(', ', $columns).') VALUES (:'.implode(', :', $columns).')');
		} else {
			// Update
			$st = $this->pdo->prepare('UPDATE '.self::$table.' SET '.implode(', ', $set).' WHERE id = :id');
			$params[':id'] = $login->getId();
		}
		
		if (!$st->execute($params)) {
			throw new LoginException($st);
		}
	}

	/**
	 * @param int $days
	 * @throws LoginException
	 */
	public function deleteOld($days)
	{
		$st = $this->pdo->prepare('DELETE FROM '.self::$table.' WHERE action < :action');
		$result = $st->execute([':action' => date('Y-m-d H:i:s', time() - $days * 86400)]);
		if (!$result) {
			throw new LoginException($st);
		}
	}

}