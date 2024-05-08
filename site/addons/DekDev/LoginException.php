<?php

namespace Statamic\Addons\DekDev;

use PDOStatement;

class LoginException extends \Exception
{

	public function __construct(PDOStatement $st)
	{
		$error = $st->errorInfo();
		parent::__construct('SQL Error ['.$error[0].']: '.$error[2], $error[1]);
	}

}