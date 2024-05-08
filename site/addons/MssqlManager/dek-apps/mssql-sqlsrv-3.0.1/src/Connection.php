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

    /** @var string */
    private $connInfo;

    /** @var array */
    private $db;

    /** @var bool */
    public static $quiet = self::OFF;

    const ON = TRUE;
    const OFF = FALSE;

    /**
     * @param string $server
     * @param string $user
     * @param string $pass
     * @param string $dbname
     * @param string $connInfo
     */
    public function __construct(string $server, string $user, string $pass, string $dbname = '', array $connInfo = [])
    {
        $this->connInfo = $connInfo;
        $this->server = $server;
        $this->user = $user;
        $this->pass = $pass;
        $this->dbname = $dbname;
        $this->connInfo['UID'] = $this->user;
        $this->connInfo['PWD'] = $this->pass;
        $this->connInfo['Database'] = $this->dbname;
        Event\Event::instance()->trigger(Event\Event::ON_INSTANCE, $this);
    }

    public function __destruct()
    {
        Event\Event::instance()->trigger(Event\Event::ON_KILL, $this);
    }

    /**
     * V ramci jednoho pripojeni je mozne menit databazi "za chodu"
     * Obcas je nazev DB nacten procedurou a v nactene DB je zavolana procedura jina
     * @param type $name
     * @return \DekApps\MssqlProcedure\Connection
     */
    public function selectDb($name)
    {
        $this->dbname = $name;
        $this->connInfo['Database'] = $this->dbname;
        return $this;
    }

    /**
     * Volat mssql_* funkce s potlacenymi chybami (E_WARNING, E_NOTICE) ?
     * @return bool
     */
    public static function isQuiet()
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
     * @return resource
     * @throws MssqlException if onError listener not set
     */
    public function getDb(string $charset = 'UTF-8')
    {
        $checksum = $this->getConfigChecksum();
        if (!isset($this->db[$checksum]) || !is_resource($this->db[$checksum])) {
            Event\Event::instance()->trigger(Event\Event::ON_BEFORE_CONN, $this);
            $db = sqlsrv_connect($this->server, $this->connInfo);
            if (!$db && !Event\Event::instance()->trigger(Event\Event::ON_ERROR_CONN, $this, sqlsrv_errors())) {
                throw new MssqlException('Unable to connect to server ' . $this->server . (!empty($error) ? ": " . print_r($error, true) : ''), MssqlException::CONNECTION_FAILED);
            }
            $this->db[$checksum] = $db;
            Event\Event::instance()->trigger(Event\Event::ON_AFTER_CONN, $this);
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
     * @return MssqlProcedure/Procedure
     */
    public function getProcedure($name, $charset = 'UTF-8')
    {
        $db = $this->getDb($charset);
        $proc = new Procedure($name, $db, $this->dbname);
        return $proc;
    }

    /**
     * Zavola SQL query
     * @param string $querystr SQL prikaz
     * @param string $charset
     * @throws MssqlException
     * @return ?array
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

    public function beginTransaction($charset = 'UTF-8')
    {

        if (sqlsrv_begin_transaction($this->getDb($charset)) === false && !Event\Event::instance()->trigger(Event\Event::ON_BEGIN_TRANS_ERROR, sqlsrv_errors())) {
            $error = sqlsrv_errors();
            throw new MssqlException('Transaction failed' . (!empty($error) ? ": " . print_r($error, true) : ''), MssqlException::EXECUTION_FAILED);
        }
        Event\Event::instance()->trigger(Event\Event::ON_BEGIN_TRANS, $this);
    }

    public function rollBack($charset = 'UTF-8')
    {

        sqlsrv_rollback($this->getDb($charset));
        Event\Event::instance()->trigger(Event\Event::ON_ROLLBACK, $this);
    }

    public function commit($charset = 'UTF-8')
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

}