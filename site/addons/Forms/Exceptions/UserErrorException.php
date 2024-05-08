<?php

namespace Statamic\Addons\Forms\Exceptions;

/**
 * Uzivatelska chyba. Vyhazuje se pri zpracovani formulare u chyby, kterou zavinil uzivatel.
 * Chyba by se mela uzivateli zobrazit a formular se nezpracuje.
 * Property $key slouzi k ulozeni identifikatoru inputu (atribut name)
 */
class UserErrorException extends ErrorException
{
    /** @var string */
    private $key;

    /**
     * @param string $message
     * @param string $key Identifikator chyby, nazev inputu pro sparovani v sablone
     * @param int $code
     * @param \Throwable $previous
     * @return \Statamic\Addons\Urs\Exceptions\UserErrorException
     */
    public static function create(string $message, string $key = null, int $code = 0, \Throwable $previous = null): UserErrorException
    {
        $e = new static($message, $code, $previous);
        if ($key) {
            $e->key = $key;
        }
        return $e;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
}
