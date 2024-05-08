<?php

declare(strict_types=1);

namespace DekApps\MssqlProcedure;

class Procedure
{

    /** @var mssql link resource */
    private $db;

    /** @var string */
    private $name;

    /** @var array */
    private $inputsConfig = [];

    /** @var bool */
    private $trimming = true;

    /** @var mssql result resource */
    private $result;

    /** @var string */
    private $dbname;

    /** @var int */
    private $pointer = 0;

    /**
     * @param string $name
     * @param mssql link resource $db
     * @param string $dbname
     */
    public function __construct($name, $db, $dbname = null)
    {
        $this->name = $name;
        $this->db = $db;
        $this->dbname = $dbname;
        Event\Event::instance()->trigger(Event\Event::ON_PROC_CREATED, $this);
    }

    /**
     * Prida vstupni parametr
     * @param string $param_name
     * @param mixed $var
     * @return self
     */
    public function setInput(string $param_name, $var): self
    {
        $this->inputsConfig[] = array(
            'param_name' => $param_name,
            'var' => $var,
            'var_name' => str_replace('@', '', $param_name),
            'type' => SQLSRV_PARAM_IN
        );
        return $this;
    }

    /**
     * Prida vstupne-vystupni parametr
     * @param string $param_name
     * @param mixed $var
     * @param string $var_name
     * @return self
     */
    public function setInOutput(string $param_name, $var, ?string $var_name = null): self
    {

        $this->inputsConfig[] = array(
            'param_name' => $param_name,
            'var' => $var,
            'var_name' => $var_name ? $var_name : str_replace('@', '', $param_name),
            'type' => SQLSRV_PARAM_INOUT
        );

        return $this;
    }

    /**
     * Prida vystupni parametr
     * @param string $param_name
     * @param string $var_name
     * @param ?int $type
     * @return self
     */
    public function setOutput(string $param_name, string $var_name = null, ?int $type = null): self
    {
        $this->inputsConfig[] = array(
            'param_name' => $param_name,
            'var' => $type,
            'var_name' => $var_name ? $var_name : str_replace('@', '', $param_name),
            'type' => SQLSRV_PARAM_OUT
        );
        return $this;
    }

    private function getSqlParSubstr($param): string
    {
        return sprintf(" %s = ?", $param);
    }

    private function normalizeProcName(string $name): string
    {
        $name = str_replace('[', '', str_replace(']', '', $name));
        $aname = explode('.', $name);
        $wrapped = array_map(function ($el)
        {
            return "[$el]";
        }, $aname
        );
        return join('.', $wrapped);
    }

    /**
     * Provede dotaz do DB
     * @return self
     * @throws MssqlException
     */
    public function execute(): self
    {
        $params = $hms = $pom = [];
        foreach ($this->inputsConfig as &$input) {
            if ($input['type'] === SQLSRV_PARAM_OUT || $input['type'] === SQLSRV_PARAM_INOUT) {
                $input[$input['var_name']] = null;
                $params[] = [&$input[$input['var_name']], $input['type'], null, $input['var']];
            } else {
                $input[$input['var_name']] = $input['var'];
                $params[] = [$input[$input['var_name']], $input['type']];
            }
            $hms[] = $this->getSqlParSubstr($input['param_name']);
        }
        $sql = sprintf("SET NOCOUNT ON; EXEC %s %s ", $this->normalizeProcName($this->name), join(',', $hms));
        Event\Event::instance()->trigger(Event\Event::ON_BEFORE_PROC_EXECUTE, $this, $sql, $params);
        $this->result = $stmt = sqlsrv_query($this->db, $sql, $params, ["Scrollable" => SQLSRV_CURSOR_CLIENT_BUFFERED]);

        if (!$stmt && !Event\Event::instance()->trigger(Event\Event::ON_ERROR_PROC_EXECUTE, $this, sqlsrv_errors())) {
            $error = sqlsrv_errors();
            throw new MssqlException('Stored procedure execution failed' . (!empty($error) ? ": " . print_r($error, true) : ''), MssqlException::EXECUTION_FAILED);
        }
        if ($stmt) {
            Event\Event::instance()->trigger(Event\Event::ON_AFTER_PROC_EXECUTE, $this, $stmt);
        }
        return $this;
    }

    /**
     * Vrati vsechny vystupni parametry, nebo jen konkretni
     * @param  string  $param_name
     * @return mixed
     */
    public function getOutputs(string $param_name = null)
    {
        $ret = [];
        foreach ($this->inputsConfig as $inp) {
            if ($inp['type'] === SQLSRV_PARAM_OUT || $inp['type'] === SQLSRV_PARAM_INOUT) {
                $ret[$inp['var_name']] = $inp[$inp['var_name']];
                if ($param_name && ($inp['param_name'] === $param_name || $inp['var_name'] === $param_name)) {
                    return $inp[$inp['var_name']];
                }
            }
        }
        return $ret;
    }

    /**
     * Vrati result
     * @return bool|mssql result resource
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Vytvori pole z mssql result resource a oreze texty
     * @param  string  $id  Nazev sloupce, ktery bude slouzit jako index
     * @result ?array
     */
    public function fetchArray($id = null): ?array
    {
        return $this->fetchIterator(false, $id);
    }

    /**
     * Vrati 1 radek a oreze texty
     * @return array|null
     */
    public function fetch(): ?array
    {
        $result = $this->getResult();

        if (!is_resource($result)) {
            return null;
        }
        if (!$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_ABSOLUTE, $this->pointer)) {
            return null;
        }

        array_walk($row, function(&$val, $column)
        {
            if (is_string($val)) {
                $this->sanitizeValue($val, $column);
            }
        });

        $this->pointer++;

        return $row;
    }

    /**
     * Vrati jen radky splnujici $callback a oreze texty
     * @param  callable  $callback
     * @param  string  $id  Nazev sloupce, ktery bude slouzit jako index
     * @result array|null kdyz result neni mssql result resource
     */
    public function searchArray($callback, $id = null)
    {
        return $this->fetchIterator($callback, false, $id);
    }

    /**
     * Vrati prvni radek splnujici $callback a oreze texty
     * @param  callable  $callback
     * @result ?array
     */
    public function searchFirst($callback): ?array
    {
        $result = $this->fetchIterator($callback, true, null);
        if (!empty($result)) {
            return array_shift($result);
        } else {
            return null;
        }
    }

    /**
     * @void
     * @param string $val
     * @param string $column
     */
    private function sanitizeValue(string &$val, string $column): void
    {
        if ($this->trimming) {
            $val = trim($val);
        }
    }

    /**
     * Zakladni funkce na iteraci vysledkem
     * @param  callable  $callback  Fetchne jen radky splnujici podminku
     * @param  bool  $stopIfFind  Skonci po prvnim uspesne provedenem $callbacku (vrati jen 1 vysledek)
     * @param  string  $id  Nazev sloupce, ktery bude slouzit jako index
     * @result ?array
     */
    private function fetchIterator(bool $stopIfFind = false, $id = null): ?array
    {
        $result = $this->getResult();
        if (!$result) {
            return null;
        }
        $array = [];
        if (is_resource($result) && sqlsrv_num_rows($result) > 0) {
//            mssql_data_seek($result, 0);
//            sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_FIRST);
            $c = 0;
            while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_ABSOLUTE, $c)) {
                array_walk($row, function(&$val, $column)
                {
                    if (is_string($val)) {
                        $this->sanitizeValue($val, $column);
                    }
                });
                if (!is_null($id) && isset($row[$id])) {
                    $array[$row[$id]] = $row;
                } else {
                    $array[] = $row;
                }

                if ($stopIfFind) {
                    break;
                }
                ++$c;
            }
        }

        return $array;
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInputsConfig()
    {
        return $this->inputsConfig;
    }

    public function getTrimming()
    {
        return $this->trimming;
    }

    public function getDbname(): string
    {
        return $this->dbname;
    }

    public function getPointer()
    {
        return $this->pointer;
    }

}
