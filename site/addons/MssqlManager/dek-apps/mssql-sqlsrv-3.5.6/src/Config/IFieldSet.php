<?php

namespace DekApps\MssqlProcedure\Config;

interface IFieldSet
{

    public function getIndex(): int;

    public function getProperty(): string;

    public function getType(): int;
}
