<?php

namespace DekApps\MssqlProcedure;

use DekApps\MssqlProcedure\Cache\TCacheFilesystem;

class ProcedureResult implements IProcedure
{

    use TCacheFilesystem;

    /** @var bool */
    private $isCache = false;
    /** @var string */
    private $key;
    /** @var array */
    private $results = [];
    /** @var array */
    private $outputs = [];
    /** @var bool */
    private $isSelenium = false;
    /** @var bool */
    private $isCashedResult = false;

    function __construct(string $key = 'null', bool $cache = false, string $cachePath = '', string $subDir = '', $scrollable = SQLSRV_CURSOR_CLIENT_BUFFERED, bool $secure = false)
    {
        $this->isCache = $cache;
        $this->key = $key;
        $this->setCachePath($cachePath);
        $this->setSubDir($subDir);
        $this->scrollable = $scrollable;
        $this->secure = $secure;
    }

    public function fetch()
    {
        $res = $this->fetchArray();

        return count($res) > 0 ? reset($res) : null;
    }

    public function fetchObject()
    {
        if ($this->isCache && empty($this->results)) {
            $res = $this->getSecure() ? $this->getDecryptedCache($this->key, $this->getSubSubDir(IProcedure::RESULTS)) : $this->getCache($this->key, $this->getSubSubDir(IProcedure::RESULTS));
            $this->results = is_array($res) ? $res : $this->results;
            $this->isCashedResult = true;
        }

        return $this->results;
    }
    public function getCacheFileModified()
    {
        $res = null;
        if ($this->isCache) {
            $res = $this->fileModified($this->key, $this->getSubSubDir(IProcedure::RESULTS));
        }
        return $res;
    }

    public function fetchArray($id = null)
    {
        if ($this->isCache && empty($this->results)) {
            $res = $this->getSecure() ? $this->getDecryptedCache($this->key, $this->getSubSubDir(IProcedure::RESULTS)) : $this->getCache($this->key, $this->getSubSubDir(IProcedure::RESULTS));
            $this->results = is_array($res) ? $res : $this->results;
            $this->isCashedResult = true;
        }
        $ret = $this->results;
        if ($id !== null) {
            $ret = [];
            foreach ($this->results as $r) {
                if (isset($r[$id])) {
                    $ret[$r[$id]] = $r;
                } else {
                    $ret[] = $r;
                }
            }
        }
        return $ret;
    }

    public function getOutputs(string $param_name = null)
    {
        if ($this->isCache && empty($this->outputs)) {
            $res = $this->getSecure() ? $this->getDecryptedCache($this->key, $this->getSubSubDir(IProcedure::OUTPUTS)) : $this->getCache($this->key, $this->getSubSubDir(IProcedure::OUTPUTS));
            $this->outputs = is_array($res) ? $res : $this->outputs;
            $this->isCashedResult = true;
        }
        $ret = $this->outputs;
        if ($param_name) {
            if (isset($this->outputs[$param_name])) {
                $ret = $this->outputs[$param_name];
            } else {
                $ret = null;
            }
        }
        return $ret;
    }

    public function setResults($results)
    {
        $tocache = false;
        // if this->results is empty we cache empty array
        if ($this->isCache && (empty($this->results) || $this->results !== $results)) {
            $tocache = true;
        }
        $this->results = $results;
        if ($tocache) {
            if ($this->getSecure()) {
                $this->saveEncryptedCache($this->key, $results, $this->getSubSubDir(IProcedure::RESULTS));
            } else {
                $this->setCache($this->key, $results, $this->getSubSubDir(IProcedure::RESULTS));
            }
            Event\Event::instance()->trigger(Event\Event::ON_AFTER_PROC_CACHE_SET, $this, $results);
        }
        return $this;
    }

    public function setOutputs($outputs)
    {
        $tocache = false;
        // if output error !== NO_ERROR => no cache 
        if ($this->isCache && $this->outputs !== $outputs && (!isset($outputs['errCode']) || ($outputs['errCode'] === self::NO_ERROR))) {
            $tocache = true;
        }
        $this->outputs = $outputs;
        if ($tocache) {
            if ($this->getSecure()) {
                $this->saveEncryptedCache($this->key, $outputs, $this->getSubSubDir(IProcedure::OUTPUTS));
            } else {
                $this->setCache($this->key, $outputs, $this->getSubSubDir(IProcedure::OUTPUTS));
            }
            Event\Event::instance()->trigger(Event\Event::ON_AFTER_PROC_CACHE_OUTPUTS_SET, $this, $outputs);
        }
        return $this;
    }

    public function getIsCache()
    {
        return $this->isCache;
    }

    public function setIsCache($cache)
    {
        $this->isCache = $cache;
        return $this;
    }

    public function setSeleniumRecording()
    {
        $arr = $this->getCache(IProcedure::SELENIUM);
        $hash = md5(var_export([$this->results, $this->outputs], true));
        if (!$arr) {
            $arr = [];
        }
        $arr[] = $this->key . $hash;
        $this->setCache($this->key . $hash, [$this->results, $this->outputs]);
        $this->setCache(IProcedure::SELENIUM, $arr);
        return $this;
    }

    public function getIsSelenium()
    {
        return $this->isSelenium;
    }

    public function setIsSelenium($isSelenium)
    {
        $this->isSelenium = $isSelenium;
        return $this;
    }

    public function getIsCashedResult(): bool
    {
        return $this->isCashedResult;
    }


}
