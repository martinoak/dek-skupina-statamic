<?php

namespace Statamic\Addons\DekDev;

use PDO;
use PDOStatement;

class LoginCollection implements \Iterator
{
	/** @var int */
	private $position = 0;

	/** @var array */
    private $data;

    public function __construct(PDOStatement $st)
	{
		$this->data = $st->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * @param PDOStatement $st
	 * @return \static
	 */
	public static function create(PDOStatement $st)
	{
		return new static($st);
	}

    public function rewind() {
        $this->position = 0;
    }

	/**
	 * @return Login
	 */
    public function current() {
        return Login::fromDb($this->data[$this->position]);
    }

	/**
	 * @return int
	 */
    public function key() {
        return $this->position;
    }

    public function next() {
        ++$this->position;
    }

	/**
	 * @return bool
	 */
    public function valid() {
        return isset($this->data[$this->position]);
    }

}