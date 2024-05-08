<?php

declare(strict_types=1);

namespace DekApps\MssqlProcedure\Config;

class FieldSet implements IFieldSet
{

    /**
     * @var int
     * fetching position of stream data .... first index is 0
     * select id, data ...
     * data .... index = 1 
     */
    private $index;

    /**
     * @var string
     * target object property or assoc. property
     */
    private $property;

    /**
     *  @var int
     *  @optional
     *  SQLSRV_PHPTYPE_STRING
     *  SQLSRV_PHPTYPE_DATETIME 
     *  SQLSRV_PHPTYPE_FLOAT 
     *  SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY) || SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR)
     */
    private $type;

    public function __construct(int $index, string $property, int $type = null)
    {
        $this->index = $index;
        $this->property = $property;
        $this->type = $type;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setIndex(int $index)
    {
        $this->index = $index;
        return $this;
    }

    public function setProperty(string $property)
    {
        $this->property = $property;
        return $this;
    }

    public function setType(int $type)
    {
        $this->type = $type;
        return $this;
    }

}
