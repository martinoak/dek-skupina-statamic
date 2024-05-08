<?php

namespace Statamic\Addons\DekDev;

use Statamic\API\User;
use Statamic\Data\Users\User as DataUser;
use Carbon\Carbon;

class Login
{
    /** @var int */
	private $id = null;

	/** @var DataUser */
	private $user;

	/** @var string */
	private $ip;
	
	/** @var string */
	private $token;

	/** @var Carbon */
	private $action = null;

	/** @var Carbon */
	private $login = null;

	/** @var Carbon */
	private $logout = null;

	/**
	 * @param array $row
	 * @return \static
	 */
	public static function fromDb(array $row)
	{
		$e = new static;
		$e->id = $row['id'];
		$e->user = User::find($row['user_id']) ?: null;
		$e->ip = $row['ip'];
		$e->token = $row['token'];
		$e->action = $row['action'] ? new Carbon($row['action']) : null;
		$e->login = $row['login'] ? new Carbon($row['login']) : null;
		$e->logout = $row['logout'] ? new Carbon($row['logout']) : null;
		return $e;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'user_id' => $this->user->id(),
			'ip' => $this->ip,
			'token' => $this->token,
			'action' => $this->action ? $this->action->toDateTimeString() : null,
			'login' => $this->login ? $this->login->toDateTimeString() : null,
			'logout' => $this->logout ? $this->logout->toDateTimeString() : null
		];
	}

	/**
	 * @param DataUser $user
	 */
	public function setUser(DataUser $user)
	{
		$this->user = $user;
	}

	/**
	 * @param string $ip
	 */
	public function setIp($ip)
	{
		$this->ip = $ip;
	}

	/**
	 * @param string $token
	 */
	public function setToken($token)
	{
		$this->token = $token;
	}

	/**
	 * @return Carbon
	 */
	public function online()
	{
		$this->action = Carbon::now();
		return $this->action;
	}

	/**
	 * @return Carbon
	 */
	public function login()
	{
		$now = $this->online();
		$this->login = $now;
		return $now;
	}

	/**
	 * @return Carbon
	 */
	public function logout()
	{
		$this->logout = Carbon::now();
		return $this->logout;
	}

	/**
	 * @return int | null
	 */
	public function getId()  //: ?int
	{
		return $this->id;
	}

	/**
	 * @return DataUser
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @return string
	 */
	public function getIp()
	{
		return (string) $this->ip;
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		return (string) $this->token;
	}

	/**
	 * @return Carbon | null
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @return Carbon | null
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * @return Carbon | null
	 */
	public function getLogout()
	{
		return $this->logout;
	}

	/**
	 * @return bool
	 */
	public function isLoggedOut()
	{
		return $this->logout > $this->action;
	}

	/**
	 * @return bool
	 */
	public function isIddle($time)
	{
		return time() > $this->action->timestamp + $time;
	}

	/**
	 * Iddle time in sec.
	 * @return int
	 */
	public function getIddleTime()
	{
		return time() - $this->action->timestamp;
	}

}
