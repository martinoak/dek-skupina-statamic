<?php

declare(strict_types=1);

namespace DekApps\MssqlProcedure;

use DekApps\MssqlProcedure\Cache\TCacheFilesystem;
use Defuse\Crypto\Crypto;
use DekApps\MssqlProcedure\Config\IFieldSet;

class Procedure implements IProcedure
{

    use TCacheFilesystem;

    /** @var resource mssql link resource */
    private $db;

    /** @var string */
    private $name;

    /**
     * cahe directory 
     * @var string */
    private $safeProcName = '';

    /**
     * cahe directory 
     * @var string */
    private $subdir = '';

    /** @var array */
    private $inputsConfig = [];

    /** @var bool */
    private $trimming = true;

    /** @var resource mssql result resource */
    private $result;

    /** @var string */
    private $dbname;

    /** @var int */
    private $pointer = 0;

    /** @var IProcedure */
    public $cacheResult;

    /** @var array */
    private $resurrectConfig = [];

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

    /**
     * additional execution connector options
     * @var array 
     */
    private $additExecuteOptions = [];

    /**
     *
     * @var Connection 
     */
    private $conn;

    /**
     *
     * @var Array 
     */
    private $connInfo = [];

    /**
     * execute procedure always, if procedure failed then get results from cache
     * @var bool
     * 
     */
    private $ignoreCache = false;

    /**
     *
     * array of Config\IFieldSet
     * @var []
     */
    private $fieldSet = [];

    /**
     * @param Connection  $conn
     * @param string $name
     * @param string $charset
     */
    public function __construct(Connection $conn, string $name, string $charset = 'UTF-8')
    {
        $this->conn = $conn;
        $this->conn->setCharset($charset);
        $this->name = $name;
        $this->dbname = $conn->getDbname();
        $this->connInfo = $conn->getConnInfo();
        $this->connInfo['CharacterSet'] = $this->connInfo['connInfo']['CharacterSet'] = $charset;
        $this->setResurrectConfig($this->connInfo)->setBypassCache($conn->getBypassCache())->setAlertRecipients($conn->getAlertRecipients());
        $that = &$this;
        Event\Event::instance()->trigger(Event\Event::ON_PROC_CREATED, $that);
    }

    /**
     * Prida vstupni parametr
     * @param string $param_name
     * @param mixed $var
     * @return self
     */
    public function setInput(string $param_name, $var, $phptype = null, $sqltype = null): self
    {
        $this->inputsConfig[] = array(
            'param_name' => $param_name,
            'var' => $var,
            'var_name' => str_replace('@', '', $param_name),
            'type' => SQLSRV_PARAM_IN,
            'phptype' => $phptype,
            'sqltype' => $sqltype,
        );
        return $this;
    }

    /**
     * Prida vstupne-vystupni parametr
     * @param string $param_name
     * @param mixed $var
     * @param string|null $var_name
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
     * @param int|null $type
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

    public function setInputsConfig(array $inputsConfig): self
    {
        $this->inputsConfig = $inputsConfig;
        return $this;
    }

    private function getSqlParSubstr(string $param): string
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

    private function setCacheResult(string $key, bool $isCache, string $safeProcName, $scrollable, bool $secure = false, int $refreshTimeout = 4, string $object = '-', bool $ignoreRefresh = false): bool
    {
        $res = false;
        if ($this->fileExists($key, $safeProcName . '/' . IProcedure::RESULTS)) {
            $dfkey = $this->getDefuseKey();
            $path = $this->getCachePath();
            $this->cacheResult = new ProcedureResult($key, $isCache, $path, $safeProcName, $scrollable, $secure, $object);
            $this->cacheResult->setDefuseKey($dfkey);
            $toRefresh = false;
            if (!(Event\Event::instance()->trigger(Event\Event::ON_AFTER_PROC_CACHE_GET, $this, $key, $path, $dfkey, $safeProcName, $scrollable, $secure, $object, $ignoreRefresh, $toRefresh))) {
                $mdatetime = $this->cacheResult->getCacheFileModified();
                if ($mdatetime) {
                    // now - lastupdate in sec
                    $secdiff = time() - $mdatetime;
                    $toRefresh = $secdiff > ($refreshTimeout * 60);
                }
                if ($toRefresh && !(Event\Event::instance()->trigger(Event\Event::ON_AFTER_PROC_CACHE_NEED_REFRESH, $this, $key, $path, $dfkey, $safeProcName, $scrollable, $secure, $object, $ignoreRefresh))) {
                    //default behavior
                    //do nothing
                }
            }
            $res = !$this->getIgnoreCache() && ($ignoreRefresh || !$toRefresh);
        }
        return $res;
    }

    /**
     * Procedure call
     * @param bool $needCache - control cache, but now cache implements only on fetchArray, fetch and getOutputs methods
     * @param string $scrollable - see scrollable sqlsrv_query
     * @param bool $secure - true, means that cache files will be encrypted
     * @param int $refreshTimeout - time in minutes, $refreshTimeout = 0 ... refresh cache immediately (on second thread)  
     * @param int $object - define object before fetchObject, default: "-"
     * @return IProcedure
     * @throws MssqlException if not bind event
     */
    public function execute(bool $needCache = false, $scrollable = SQLSRV_CURSOR_CLIENT_BUFFERED, bool $secure = false, int $refreshTimeout = 4, string $object = '-'): IProcedure
    {
        // getBypassCache === true means generally cache off 
        $isCache = !$this->getBypassCache() && $needCache;
        $uploadStream = false;
        $params = $hms = $pom = [];
        $object = $object ?? '-';
        $object = $object ? $object : '-';
        $rsConfig = $this->getResurrectConfig();
        $procName = $this->normalizeProcName($this->name);
        $options = array_merge($this->getAdditExecuteOptions(), ["Scrollable" => $scrollable]);
        $this->setSafeProcName($this->filterFilename(filter_var($this->name, FILTER_SANITIZE_URL)));
        foreach ($this->inputsConfig as &$input) {
            if ($input['type'] === SQLSRV_PARAM_OUT || $input['type'] === SQLSRV_PARAM_INOUT) {
                $input[$input['var_name']] = null;
                $params[] = [&$input[$input['var_name']], $input['type'], null, $input['var']];
            } else {
                $input[$input['var_name']] = $input['var'];
                $uploadStream = $uploadStream || (is_resource($input[$input['var_name']]) && get_resource_type($input[$input['var_name']]) === 'stream');
                $param = [$input[$input['var_name']], $input['type']];
                if ($input['phptype'] !== null) {
                    $param[] = $input['phptype'];
                    if ($input['sqltype'] !== null) {
                        $param[] = $input['sqltype'];
                    }
                }
                $params[] = $param;
            }
            $hms[] = $this->getSqlParSubstr($input['param_name']);
        }
        if ($this->conn->getWrapTransaction()) {
            $sql = sprintf("EXEC %s %s ", $procName, join(',', $hms));
        } else {
            $sql = sprintf("SET NOCOUNT ON; EXEC %s %s ", $procName, join(',', $hms));
        }
        // key is cache primary identificator
        $key = sha1($sql . crc32(serialize($params)) . crc32(var_export($rsConfig, true)) . ($object === '-' ? ('') : ($object)));

        Event\Event::instance()->trigger(Event\Event::ON_BEFORE_PROC_GET_CACHE, $this, $sql, $params, $key, $this->getSafeProcName(), $isCache, $this->inputsConfig, $object);

        // fake result for development and testing
        if (Event\Event::instance()->trigger(Event\Event::ON_FAKE_RESULT, $this, $sql, $params, $key, $this->getSafeProcName(), $object)) {
            return $this->cacheResult;
        }

        if ($isCache && $this->setCacheResult($key, $isCache, $this->getSafeProcName(), $scrollable, $secure, $refreshTimeout, $object)) {
            // if exists valid data in cache, we get it and finish execute method 
            // The method setCacheResult will ensure, that the cacheResult gets from cache
            return $this->cacheResult;
        }
        // create connection to db
        $db = $this->getDb();

        Event\Event::instance()->trigger(Event\Event::ON_BEFORE_PROC_EXECUTE, $this, $sql, $params, $key, $this->getSafeProcName(), $isCache, $db, $scrollable, $secure, $refreshTimeout, $object);

        $startTime = microtime(true);
        // exec proc query
        if ($uploadStream) {
            // The sqlsrv_send_stream data function is used to send data up to 8KB of stream data to the server at a time. 
            // Turning off the default behavior of sending all stream data to the server at once and using sqlsrv_send_stream_data to send stream data allows for flexibility in application design.
//            $options['SendStreamParamsAtExec'] = 0;
//            $params[0][] = SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY);
//            $params[0][] = SQLSRV_SQLTYPE_VARBINARY('max');
            do {
                $binUpload = $this->conn->prepare($sql, $params, $options);
                if ($binUpload === false) {
                    $this->result = $stmt = false;
                    break;
                }
                $this->result = $stmt = sqlsrv_execute($binUpload);
                if ($stmt === false) {
//                    die( print_r( sqlsrv_errors(),true) ); 
                    break;
                }
            } while (false);
        } else {
            $this->result = $stmt = $this->conn->exec($sql, $params, $options);
        }
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
//        $t="SET NOCOUNT ON; exec [proc].czES_XML_Kosik_Seznam @IN_Hash = 'bfacb6586ac62c22cd2c513362533d7c61a04529d2c10e4b47f167d7e0e2368fcc7206b8d74a305c37485b87d346fb662796494140a715a10f063f1b9a3cb0a4', @IN_PobockaID = 'P100'";
//        $t = "SET NOCOUNT ON; exec [proc].[czEshopKosikSeznamProduct] @IN_Hash = 'bfacb6586ac62c22cd2c513362533d7c61a04529d2c10e4b47f167d7e0e2368fcc7206b8d74a305c37485b87d346fb662796494140a715a10f063f1b9a3cb0a4', @IN_PobockaID = 'P100'";
//        $this->result = $stmt = sqlsrv_query($db, $t, [], ["Scrollable" => SQLSRV_CURSOR_DYNAMIC]);

        if (!$stmt) {
            $ignoreRefresh = true;
            if ($this->setCacheResult($key, $isCache, $this->getSafeProcName(), $scrollable, $secure, $refreshTimeout, $object, $ignoreRefresh)) {
                //error && exists cache
                Event\Event::instance()->trigger(Event\Event::ON_ERROR_PROC_EXECUTE_WITH_CACHE, $this, $this->cacheResult, $key, $this->getSafeProcName(), $object);
                return $this->cacheResult;
            } else if (!Event\Event::instance()->trigger(Event\Event::ON_ERROR_PROC_EXECUTE, $this, sqlsrv_errors(), $key, $this->getSafeProcName(), $this->getAlertRecipients())) {
                $error = sqlsrv_errors();
                throw new MssqlException('Stored procedure execution failed' . (!empty($error) ? ": " . print_r($error, true) : ''), MssqlException::EXECUTION_FAILED);
            }
            // it's very important: if exists some problem with result, then no caching must be set, because not valid result remove last valid data and the condom doesn't work
            $isCache = false;
        } else {
            //db response is correct
            Event\Event::instance()->trigger(Event\Event::ON_AFTER_PROC_EXECUTE, $this, $stmt, $key, $this->getSafeProcName(), $executionTime);
        }
        $dfkey = $this->getDefuseKey();
        $path = $this->getCachePath();
        // ProcedureResult is like messanger over mssql result
        // Cause mssql result was written in C language, we can not cache it in PHP. We cache the ProcedureResult instead mssql result.
        $this->cacheResult = new ProcedureResult($key, $isCache, $path, $this->getSafeProcName(), $scrollable, $secure);
        $this->cacheResult->setDefuseKey($dfkey);
        $this->cacheResult->setObject($object);
        if (!Event\Event::instance()->trigger(Event\Event::ON_RESULT_BIND, $this, $stmt, $this->cacheResult, $key, $this->getSafeProcName(), $object)) {
            if ($object !== '-') {
                //object
                $this->cacheResult->setResults($this->fetchObject($object, $scrollable));
            } else {
                //array
                $this->cacheResult->setResults($this->fetchArray($scrollable));
            }
            $this->cacheResult->setOutputs($this->getOutputs());
        }
        Event\Event::instance()->trigger(Event\Event::ON_RESULT_CREATED, $this, $this->cacheResult, $sql, $params, $key, $this->getSafeProcName(), $object);
        return $this->cacheResult;
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
     * @return bool|resource mssql result resource
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Vytvori pole objektu z mssql result resource a oreze texty
     * @param  string  $object  object name
     * @result array
     */
    public function fetchObject(string $object, $cursor_type = SQLSRV_CURSOR_CLIENT_BUFFERED): array
    {
        $result = $this->getResult();
        //return
        $a = [];
        if (!($result && is_resource($result))) {
            return $a;
        }
        //row index
        $c = 0;
        //if defined fieldSets
        if (count($this->fieldSet) > 0) {
            while (sqlsrv_fetch($result)) {
                $a[$c] = new $object;
                for ($i = 0; $i < count($this->fieldSet); $i++) {
                    $fd = $this->fieldSet[$i];
                    $fieldType = $fd->getType();
                    if ($fieldType) {
                        $field = sqlsrv_get_field($result, $fd->getIndex(), $fieldType);
                        //if field type is stream, then create tmpfile and copy stream to file
                        if ($fieldType === SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY) || $fieldType === SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR)) {
                            $file = tmpfile();
                            stream_copy_to_stream($field, $file);
                            rewind($file);
                            $field = $file;
                        }
                    } else {
                        $field = sqlsrv_get_field($result, $fd->getIndex());
                    }
                    $a[$c]->{$fd->getProperty()} = $field;
                }
                ++$c;
            }
        } else if (($cursor_type !== SQLSRV_CURSOR_CLIENT_BUFFERED || sqlsrv_num_rows($result) > 0)) {
            while ($instance = sqlsrv_fetch_object($result, $object)) {
                $a[] = $instance;
                $c++;
            }
        }
        return $a;
    }

    /**
     * Vytvori pole z mssql result resource a oreze texty
     * @param  string  $cursor_type  
     * @result ?array
     */
    public function fetchArray($cursor_type = SQLSRV_CURSOR_CLIENT_BUFFERED): ?array
    {
        return $this->fetchIterator($cursor_type);
    }

    /**
     * @smazat
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
     * @param  string  $cursor_type 
     * @return array|null
     */
    private function fetchIterator($cursor_type = SQLSRV_CURSOR_CLIENT_BUFFERED): ?array
    {
        $result = $this->getResult();
        if (!($result && is_resource($result))) {
            return null;
        }
        //return
        $array = [];
        //row index
        $c = 0;
        //if defined fieldSets
        if (count($this->fieldSet) > 0) {
            while (sqlsrv_fetch($result)) {
                for ($i = 0; $i < count($this->fieldSet); $i++) {
                    $fd = $this->fieldSet[$i];
                    $fieldType = $fd->getType();
                    if ($fieldType) {
                        $field = sqlsrv_get_field($result, $fd->getIndex(), $fieldType);
                        //if field type is stream, then create tmpfile and copy stream to file
                        if ($fieldType === SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY) || $fieldType === SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR)) {
                            $file = tmpfile();
                            stream_copy_to_stream($field, $file);
                            rewind($file);
                            $field = $file;
                        }
                    } else {
                        $field = sqlsrv_get_field($result, $fd->getIndex());
                    }
                    $array[$c][$fd->getProperty()] = $field;
                }
                ++$c;
            }
        } else if ($cursor_type !== SQLSRV_CURSOR_CLIENT_BUFFERED || sqlsrv_num_rows($result) > 0) {
            while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_ABSOLUTE, $c)) {
                if ($this->getTrimming()) {
                    array_walk($row, function(&$val, $column)
                    {
                        if (is_string($val)) {
                            $this->sanitizeValue($val, $column);
                        }
                    });
                }

                $array[] = $row;

                ++$c;
            }
        }
        return $array;
    }

    /**
     * @return resource
     */
    public function getDb()
    {
        if (!is_resource($this->db)) {
            $this->db = $this->conn->getDb($this->conn->getCharset());
        }
        return $this->db;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInputsConfig(): array
    {
        return $this->inputsConfig;
    }

    public function getTrimming(): bool
    {
        return $this->trimming;
    }

    public function getDbname(): string
    {
        return $this->dbname;
    }

    public function getPointer(): int
    {
        return $this->pointer;
    }

    public function getCacheResult(): IProcedure
    {
        return $this->cacheResult;
    }

    /**
     * return connector config for assync "resurrection" - refresh cache
     * @return array
     */
    private function getResurrectConfig(): array
    {
        $resurrect_config = $this->resurrectConfig;
        $resurrect_config['procName'] = $this->name;
        $resurrect_config['procInputConfig'] = $this->inputsConfig;
        $resurrect_config['additExecOptions'] = $this->additExecuteOptions;
        return $resurrect_config;
    }

    public function getDbConfig(): string
    {
        $secret_data = json_encode($this->getResurrectConfig());
        $ciphertext = Crypto::encrypt($secret_data, $this->getCKey());
        return $ciphertext;
    }

    public function setResurrectConfig(array $resurrectConfig): self
    {
        $this->resurrectConfig = $resurrectConfig;
        return $this;
    }

    public function getBypassCache(): bool
    {
        return $this->bypassCache;
    }

    public function setBypassCache(bool $bypassCache): self
    {
        $this->bypassCache = $bypassCache;
        return $this;
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

    public function getConn(): Connection
    {
        return $this->conn;
    }

    public function getAdditExecuteOptions(): array
    {
        return $this->additExecuteOptions;
    }

    public function setAdditExecuteOptions(array $additExecuteOptions): self
    {
        $this->additExecuteOptions = $additExecuteOptions;
        return $this;
    }

    public function addAdditExecuteOptions(string $key, $value): self
    {
        $this->additExecuteOptions[$key] = $value;
        return $this;
    }

    public function getSafeProcName(): string
    {
        $res = '';
        if ($this->getSubdir() === '') {
            $res = $this->safeProcName;
        } else {
            $res = $this->getSubdir();
        }
        return $res;
    }

    public function setSafeProcName(string $safeProcName): self
    {
        $this->safeProcName = $safeProcName;
        return $this;
    }

    public function getSubdir(): string
    {
        return $this->subdir;
    }

    public function setSubdir(string $subdir): self
    {
        $this->subdir = $subdir;
        return $this;
    }

    public function getIgnoreCache()
    {
        return $this->ignoreCache;
    }

    public function setIgnoreCache($ignoreCache)
    {
        $this->ignoreCache = $ignoreCache;
        return $this;
    }

    public function setTrimming(bool $trimming)
    {
        $this->trimming = $trimming;
        return $this;
    }

    public function addFieldSet(IFieldSet $fieldSet)
    {
        $this->fieldSet[$fieldSet->getIndex()] = $fieldSet;
        return $this;
    }

    public function clearFieldSet()
    {
        $this->fieldSet = [];
        return $this;
    }

}
