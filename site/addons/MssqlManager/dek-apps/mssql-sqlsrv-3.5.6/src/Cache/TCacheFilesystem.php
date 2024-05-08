<?php

declare(strict_types=1);

namespace DekApps\MssqlProcedure\Cache;

/**
 * @todo: redis implementation is posible in future
 */
use DekApps\MssqlProcedure\IProcedure;
use Defuse\Crypto\Key;
use Defuse\Crypto\Crypto;

trait TCacheFilesystem
{

    /** @var string */
    private $cachePath = '/tmp/';

    /** @var string */
    private $subDir = '';

    /** @var string */
    private $defuseKey = IProcedure::DEFUSE_KEY;

    /** @var string */
    private $key;

    /** @var bool */
    private $secure = false;

    /** @var int */
    private $refreshTimeout = 3;
    private $scrollable;

    /** @var string */
    private $object='-';

    public function __construct(string $key, string $cachePath, string $defuseKey, string $subdir, $scrollable, bool $secure, string $object = '-')
    {
        $this->key = $key;
        $this->setCachePath($cachePath);
        $this->setDefuseKey($defuseKey);
        $this->setSubDir($subdir);
        $this->scrollable = $scrollable;
        $this->secure = $secure;
        $this->object = $object;
    }

    private function getCKey(): Key
    {
        return Key::loadFromAsciiSafeString($this->getDefuseKey());
    }

    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    public function setCachePath(string $cachePath): void
    {
        $this->cachePath = $cachePath;
    }

    public function setDefuseKey(string $defuseKey)
    {
        $this->defuseKey = $defuseKey;
    }

    private function getDefuseKey()
    {
        return $this->defuseKey;
    }

    public function getDecryptedCache(string $key, ?string $subdir = null)
    {
        $res = null;
        $ckey = $this->getCKey();
        $ciphertext = $this->getCache($key, $subdir);
        try {
            $decoded_data = Crypto::decrypt($ciphertext, $ckey);
            $res = json_decode($decoded_data, true);
        } catch (\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
            //@todo: An attack! or filesystem corrupted
        }
        return $res;
    }

    public function getCache(string $key, ?string $subdir = null)
    {
        $subdir = ($subdir ?? $this->getSubDir());
        $path = $this->getCachePath() . "/$subdir";
        $this->checkWorkDir($path);
        @include "$path/$key";
        return isset($val) ? $val : null;
    }

    public function saveEncryptedCache(string $key, $data, ?string $subdir = null): void
    {
        $ckey = $this->getCKey();
        $secret_data = json_encode($data);
        $ciphertext = Crypto::encrypt($secret_data, $ckey);
        $this->saveCache($key, $ciphertext, $subdir);
    }

    public function saveCache(string $key, string $s, ?string $subdir = null): void
    {
        $subdir = ($subdir ?? $this->getSubDir());
        $path = $this->getCachePath() . "/$subdir";
        $this->checkWorkDir($path);
        $tmp = "$path/$key." . uniqid('', true) . '.tmp';
        file_put_contents($tmp, '<?php $val = ' . $s . ';', LOCK_EX);
        rename($tmp, $this->getCachePath() . "/$subdir/$key");
    }

    public function setCache(string $key, $data, ?string $subdir = null): void
    {
        $subdir = ($subdir ?? $this->getSubDir());
//        $val = var_export($data, true);
        $val = 'unserialize(' . var_export(serialize($data), true) . ')';
        $this->saveCache($key, $val, $subdir);
    }

    public function fileExists(string $key, ?string $subdir = null): bool
    {
        $subdir = ($subdir ?? $this->getSubDir());
        $path = $this->getCachePath() . "/$subdir";
        $this->checkWorkDir($path);
        $res = file_exists("$path/$key");
//        if(!$res){
//            print("$path/$key\n");
//        }
        return $res;
    }

    public function fileModified(string $key, ?string $subdir = null): int
    {
        $subdir = ($subdir ?? $this->getSubDir());
        $path = $this->getCachePath() . "/$subdir";
        $this->checkWorkDir($path);
        $res = filemtime("$path/$key");
        return $res !== false ? $res : -1;
    }

    public function fileDelete(string $key, ?string $subdir = null): bool
    {
        $subdir = ($subdir ?? $this->getSubDir());
        $path = $this->getCachePath() . "/$subdir";
        $this->checkWorkDir($path);
        $res = unlink("$path/$key");
        return $res;
    }

    public function getSubDir(): string
    {
        return $this->subDir;
    }

    public function setSubDir(string $subDir)
    {
        $this->subDir = $subDir;
    }

    private function checkWorkDir(string $path): void
    {
        if (!file_exists($path)) {
            @mkdir($path, 0775, true);
        }
    }

    public function filterFilename(string $filename): string
    {
        // sanitize filename
        $filename = preg_replace(
                '~
        [<>:"/\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x',
                '-', $filename);
        // avoids ".", ".." or ".hiddenFiles"
        $filename = ltrim($filename, '.-');
        // maximize filename length to 255 bytes http://serverfault.com/a/9548/44086
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');
        return $filename;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSecure(): bool
    {
        return $this->secure;
    }

    public function getRefreshTimeout(): int
    {
        return $this->refreshTimeout;
    }

    public function setRefreshTimeout(int $refreshTimeout): self
    {
        $this->refreshTimeout = $refreshTimeout;
        return $this;
    }

    public function getScrollable()
    {
        return $this->scrollable;
    }

    public function setScrollable($scrollable): self
    {
        $this->scrollable = $scrollable;
        return $this;
    }

    public function setSecure(bool $secure): self
    {
        $this->secure = $secure;
        return $this;
    }

    public function getSubSubDir(string $last)
    {
        return sprintf("%s/%s", $this->getSubDir(), $this->filterFilename($last));
    }

    public function getObject()
    {
        return $this->object;
    }

    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

}
