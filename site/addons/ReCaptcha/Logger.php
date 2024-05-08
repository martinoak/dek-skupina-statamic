<?php

namespace Statamic\Addons\ReCaptcha;

class Logger
{
    private $logDir;

	public function __construct($logDir = null)
	{
        if ($logDir && is_dir($logDir)) {
            $this->logDir = $logDir;
        } else {
            $this->logDir = storage_path('recaptcha');
            if (!is_dir($this->logDir)) {
                mkdir($this->logDir);
            }
        }
	}

    public function log($message, $type)
    {
        file_put_contents($this->logDir.'/'.$type.'.log', date('Y-m-d-H:i:s ').$message, FILE_APPEND);
    }

}