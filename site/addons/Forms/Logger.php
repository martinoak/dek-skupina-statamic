<?php

namespace Statamic\Addons\Forms;

class Logger
{
    public static function log($msg)
    {
        file_put_contents(storage_path('logs/forms.log'), '['.date('Y-m-d H:i:s').'] '.$msg.PHP_EOL, FILE_APPEND);
    }
}