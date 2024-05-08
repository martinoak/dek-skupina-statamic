<?php

declare(strict_types=1);

namespace DekApps\MssqlProcedure;

class Connection
{

    /** @var string */
    private $server;

    /** @var string */
    private $user;

    /** @var string */
    private $pass;

    /** @var string */
    private $dbname;

    /** @var array */
    private $connInfo;

    /** @var array */
    private $db;

    /**
     * force stop caching ... if connector or filesystem has problems ... set true
     * @var bool 
     */
    private $bypassCache;

    /**
     * Alert mail recipients
     * @var array 
     */
    private $alertRecipients = [];

    /** @var bool */
    public static $quiet = self::OFF;

    const ON = TRUE;
    const OFF = FALSE;

    /**
     *
     * @var string 
     */
    private $charset = '';

    /**
     *   SET NOCOUNT ON
     *   SET XACT_ABORT ON
     *   BEGIN TRAN
     *      ....
     *   COMMIT
     * @var bool
     */
    private $wrapTransaction = false;

    /**
     * @param string $server
     * @param string $user
     * @param string $pass
     * @param string $dbname
     * @param array $connInfo
     * @param bool $bypassCache
     */
    public function __construct(string $server, string $user, string $pass, string $dbname = '', array $connInfo = [], bool $bypassCache = false)
    {
        $this->connInfo = $connInfo;
        $this->server = $server;
        $this->user = $user;
        $this->pass = $pass;
        $this->dbname = $dbname;
        $this->connInfo['UID'] = $this->user;
        $this->connInfo['PWD'] = $this->pass;
        $this->connInfo['Database'] = $this->dbname;
        $this->bypassCache = $bypassCache;
        $this->alertRecipients = $this->connInfo['alertRecipients'] ?? [];
        unset($this->connInfo['alertRecipients']);
        //default
        $this->connInfo['CharacterSet'] = 'UTF-8';
        Event\Event::instance()->trigger(Event\Event::ON_INSTANCE, $this);
    }

    public function __destruct()
    {
        if (is_array($this->db) && count($this->db) > 0) {
            foreach ($this->db as $i => $conn) {
                if (is_resource($conn)) {
                    sqlsrv_close($conn);
                }
            }
        }
        Event\Event::instance()->trigger(Event\Event::ON_KILL, $this);
    }

    /**
     * V ramci jednoho pripojeni je mozne menit databazi "za chodu"
     * Obcas je nazev DB nacten procedurou a v nactene DB je zavolana procedura jina
     * @param string $name
     * @return $this
     */
    public function selectDb(string $name): self
    {
        $this->dbname = $name;
        $this->connInfo['Database'] = $this->dbname;
        return $this;
    }

    /**
     * Volat mssql_* funkce s potlacenymi chybami (E_WARNING, E_NOTICE) ?
     * @return bool
     */
    public static function isQuiet(): bool
    {
        return self::$quiet;
    }

    private function getConfigChecksum(string $charset = 'UTF-8'): string
    {
        $this->connInfo['CharacterSet'] = $charset;
        return md5(json_encode($this->connInfo));
    }

    /**
     * Pripojeni k databazi
     * @param string $charset
     * @return resource
     * @throws MssqlException if onError listener not set
     */
    public function getDb(string $charset = 'UTF-8')
    {
        $charset = $charset === '' ? 'UTF-8' : $charset;
        $checksum = $this->getConfigChecksum();
        if (!Event\Event::instance()->trigger(Event\Event::ON_FAKE_DB, $this, $checksum) && !(isset($this->db[$checksum]) && is_resource($this->db[$checksum]))) {
            Event\Event::instance()->trigger(Event\Event::ON_BEFORE_CONN, $this);
            $startTime = microtime(true);
            $db = sqlsrv_connect($this->server, $this->connInfo);
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            if (!$db && !Event\Event::instance()->trigger(Event\Event::ON_ERROR_CONN, $this, sqlsrv_errors(), $this->getAlertRecipients(), $executionTime)) {
                throw new MssqlException('Unable to connect to server :::TIMEOUT:::' . (string) number_format($executionTime * 1000, 10) . ' ms:::' . $this->server . (!empty($error) ? ": " . print_r($error, true) : ''), MssqlException::CONNECTION_FAILED);
            }
            $this->db[$checksum] = $db;
            Event\Event::instance()->trigger(Event\Event::ON_AFTER_CONN, $this, $db, $checksum, $executionTime);
        }
        return $this->db[$checksum];
    }

    /**
     * Zkontroluje pripojeni do databaze
     * @return bool
     */
    public function checkConnect(): bool
    {
        $success = false;
        try {
            $success = is_resource($this->getDb());
        } catch (MssqlException $e) {
            
        }

        return $success;
    }

    /**
     * Vytvori instanci tridy Procedure
     * @param string $name Name of procedure
     * @param string $charset
     * @return Procedure
     */
    public function getProcedure(string $name, string $charset = 'UTF-8'): Procedure
    {
        $proc = new Procedure($this, $name, $charset);
        return $proc;
    }

    /**
     * Zavola SQL query
     * @param string $querystr SQL prikaz
     * @param string $charset
     * @throws MssqlException
     * @return array|null
     */
    public function query($querystr, $charset = 'UTF-8'): ?array
    {
        $db = $this->getDb($charset);
        $result = null;
        Event\Event::instance()->trigger(Event\Event::ON_BEFORE_QUERY, $this, $querystr);
        $qr = sqlsrv_query($db, $querystr);
        if (!$qr && !Event\Event::instance()->trigger(Event\Event::ON_ERROR_QUERY, sqlsrv_errors())) {
            $error = sqlsrv_errors();
            throw new MssqlException('Query execution failed' . (!empty($error) ? ": " . print_r($error, true) : ''), MssqlException::EXECUTION_FAILED);
        }
        if ($qr) {
            Event\Event::instance()->trigger(Event\Event::ON_AFTER_QUERY, $this, $qr);
            $result = [];
            while ($row = sqlsrv_fetch_array($qr)) {
                $result[] = $row;
            }

            // dispose of the query
            sqlsrv_cancel($qr);
            Event\Event::instance()->trigger(Event\Event::ON_FETCH_QUERY, $this, $result);
        }
        return $result;
    }

    public function beginTransaction(string $charset = 'UTF-8')
    {

        if (sqlsrv_begin_transaction($this->getDb($charset)) === false && !Event\Event::instance()->trigger(Event\Event::ON_BEGIN_TRANS_ERROR, sqlsrv_errors())) {
            $error = sqlsrv_errors();
            throw new MssqlException('Transaction failed' . (!empty($error) ? ": " . print_r($error, true) : ''), MssqlException::EXECUTION_FAILED);
        }
        Event\Event::instance()->trigger(Event\Event::ON_BEGIN_TRANS, $this);
    }

    public function rollBack(string $charset = 'UTF-8')
    {

        sqlsrv_rollback($this->getDb($charset));
        Event\Event::instance()->trigger(Event\Event::ON_ROLLBACK, $this);
    }

    public function commit(string $charset = 'UTF-8')
    {
        sqlsrv_commit($this->getDb($charset));
        Event\Event::instance()->trigger(Event\Event::ON_COMMIT, $this);
    }

    public function configure(string $setting, $value)
    {
        if (sqlsrv_configure($setting, $value) === false && !Event\Event::instance()->trigger(Event\Event::ON_CONFIG_ERROR, sqlsrv_errors(), $setting, $value)) {
            $error = sqlsrv_errors();
            throw new MssqlException('Configuration failed' . (!empty($error) ? ": " . print_r($error, true) : ''), MssqlException::EXECUTION_FAILED);
        }
    }

    public function getConnInfo(): array
    {
        $tmp = $this->connInfo;
        $tmp['alertRecipients'] = $this->getAlertRecipients();
        return["server" => $this->server, "connInfo" => $tmp];
    }

    public function getAlertRecipients(): array
    {
        return $this->alertRecipients;
    }

    public function setAlertRecipients(array $alertRecipients): self
    {
        $this->alertRecipients = $alertRecipients;
        return $this;
    }

    public function setDb(array $db): self
    {
        $this->db = $db;
        return $this;
    }

    public function getBypassCache(): bool
    {
        return $this->bypassCache;
    }

    public function getDbname(): string
    {
        return $this->dbname;
    }

    public function convertSqlSrvDocuTypes(int $sqlSrvType): string
    {
        switch ($sqlSrvType)
        {
            case SQLSRV_SQLTYPE_BIT:
                $res = 'bool';
                break;
            case SQLSRV_SQLTYPE_CHAR:
            case SQLSRV_SQLTYPE_VARCHAR:
            case SQLSRV_SQLTYPE_NTEXT:
            case SQLSRV_SQLTYPE_NCHAR:
            case SQLSRV_SQLTYPE_NVARCHAR:
            case SQLSRV_SQLTYPE_BINARY:
            case SQLSRV_SQLTYPE_TEXT:
            case SQLSRV_SQLTYPE_VARBINARY:
            case SQLSRV_SQLTYPE_UDT:
            case SQLSRV_SQLTYPE_IMAGE:
            case SQLSRV_SQLTYPE_UNIQUEIDENTIFIER:
            case SQLSRV_SQLTYPE_XML:
                $res = 'string';
                break;

            case SQLSRV_SQLTYPE_TINYINT:
            case SQLSRV_SQLTYPE_SMALLINT:
            case SQLSRV_SQLTYPE_INT:
            case SQLSRV_SQLTYPE_BIGINT:
            case SQLSRV_SQLTYPE_TIMESTAMP:
                $res = 'int';
                break;

            case SQLSRV_SQLTYPE_MONEY:
            case SQLSRV_SQLTYPE_FLOAT:
            case SQLSRV_SQLTYPE_DECIMAL:
            case SQLSRV_SQLTYPE_REAL:
            case SQLSRV_SQLTYPE_NUMERIC:
                $res = 'float';
                break;
            // generally datetime ... I'm not sure .... maybe does'n work
            case SQLSRV_SQLTYPE_DATE:
            case SQLSRV_SQLTYPE_DATETIME:
            case SQLSRV_SQLTYPE_TIME:
            case SQLSRV_SQLTYPE_DATETIME2:
                $res = '\DateTime';
                break;

            case SQLSRV_SQLTYPE_DATETIMEOFFSET:
                $res = '\DateInterval';
                break;
            default:
                $res = 'string';
                break;
        }
        return $res;
    }

    public function convertSqlSrvDocuTypesSharp(int $sqlSrvType, bool $nullable = false): string
    {
        switch ($sqlSrvType)
        {
            case SQLSRV_SQLTYPE_BIT:
                $res = 'Boolean';
                break;
            case SQLSRV_SQLTYPE_CHAR:
                $res = 'String';
                break;
            case SQLSRV_SQLTYPE_VARCHAR:
                $res = 'String';
                break;
            case SQLSRV_SQLTYPE_NTEXT:
                $res = 'String';
                break;
            case SQLSRV_SQLTYPE_NCHAR:
                $res = 'String';
                break;
            case SQLSRV_SQLTYPE_NVARCHAR:
                $res = 'String';
                break;
            case SQLSRV_SQLTYPE_BINARY:
                $res = 'Byte[]';
                break;
            case SQLSRV_SQLTYPE_TEXT:
                $res = 'String';
                break;
            case SQLSRV_SQLTYPE_VARBINARY:
                $res = 'Byte[]';
                break;
            case SQLSRV_SQLTYPE_UDT:
                $res = '?UserDefinedClass';
                break;
            case SQLSRV_SQLTYPE_IMAGE:
                $res = 'Byte[]';
                break;
            case SQLSRV_SQLTYPE_UNIQUEIDENTIFIER:
                $res = 'Guid';
                break;
            case SQLSRV_SQLTYPE_XML:
                $res = 'Xml';
                break;

            case SQLSRV_SQLTYPE_TINYINT:
                $res = 'Byte';
                break;
            case SQLSRV_SQLTYPE_SMALLINT:
                $res = 'Int16';
                break;
            case SQLSRV_SQLTYPE_INT:
                $res = 'Int32';
                break;
            case SQLSRV_SQLTYPE_BIGINT:
                $res = 'Int64';
                break;
            case SQLSRV_SQLTYPE_TIMESTAMP:
                $res = 'Byte[]';
                break;

            case SQLSRV_SQLTYPE_MONEY:
                $res = 'Decimal';
                break;
            case SQLSRV_SQLTYPE_FLOAT:
                $res = 'Double';
                break;
            case SQLSRV_SQLTYPE_DECIMAL:
                $res = 'Decimal';
                break;
            case SQLSRV_SQLTYPE_REAL:
                $res = 'Single';
                break;
            case SQLSRV_SQLTYPE_NUMERIC:
                $res = 'Decimal';
                break;
            case SQLSRV_SQLTYPE_DATE:
                $res = 'DateTime';
                break;
            case SQLSRV_SQLTYPE_DATETIME:
                $res = 'DateTime';
                break;
            case SQLSRV_SQLTYPE_TIME:
                $res = 'TimeSpan';
                break;
            case SQLSRV_SQLTYPE_DATETIME2:
                $res = 'DateTime2';
                break;

            case SQLSRV_SQLTYPE_DATETIMEOFFSET:
                $res = 'DateTimeOffset';
                break;
            default:
                $res = 'Unknown';
                break;
        }
        if ($nullable && strtolower($res) !== 'string' && strpos($res, '[]') === false) {
            $res = sprintf("Nullable<%s>", $res);
        }
        return $res;
    }

    public function exec($sql, $params, $options)
    {
        $db = $this->getDb($this->getCharset());

        if ($this->getWrapTransaction()) {
            $sql = sprintf("
                SET NOCOUNT ON
                SET XACT_ABORT ON
                BEGIN TRAN
                    %s
                COMMIT
            ", $sql);
        }

        return sqlsrv_query($db, $sql, $params, $options);
    }

    public function prepare($sql, $params, $options)
    {
        $db = $this->getDb($this->getCharset());
        return sqlsrv_prepare($db, $sql, $params, $options);
    }

    public function getWrapTransaction(): bool
    {
        return $this->wrapTransaction;
    }

    public function setWrapTransaction(bool $wrapTransaction)
    {
        $this->wrapTransaction = $wrapTransaction;
        return $this;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function setCharset(string $charset)
    {
        $this->charset = $charset;
        return $this;
    }

}
