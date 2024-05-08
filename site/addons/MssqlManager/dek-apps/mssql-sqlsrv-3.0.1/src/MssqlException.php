<?php

namespace DekApps\MssqlProcedure;

class MssqlException extends \Exception
{
	const CONNECTION_FAILED = 1;
	const DATABASE_SELECTION_FAILED = 2;
	const EXECUTION_FAILED = 3;
}