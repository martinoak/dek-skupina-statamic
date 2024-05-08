<?php

namespace Statamic\Addons\Forms;

use Statamic\Contracts\Forms\Submission;
use Statamic\Addons\CustomForms\Response;

interface IHandler
{
    
    public const LANG_CZ = 'cz';
    public const LANG_SK = 'sk';
    public const LANG_EN = 'en';

    /**
     * @param \Statamic\Contracts\Forms\Submission $submission
     * @return bool
     */
    public function handle(Submission $submission): bool;

    public function getData(): array;
    
}