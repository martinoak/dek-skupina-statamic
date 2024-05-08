<?php

namespace Statamic\Addons\Forms\Exceptions;

/**
 * Interni chyba. Vyhazuje se pri zpracovani formulare u chyby, kterou nemuze uzivatel ovlivnit.
 * Mela by se zalogovat a odeslat upozorneni spravci webu, ten by mel chybu napravit (ulozit rucne, data ma v logu).
 * Uzivatel by pak nemel poznat, ze doslo k chybe.
 * Pouzivat tam, kde nechceme kvuli interni chybe (napr. pripojeni do agendy) prijit o data z formulare
 * a zaroven musi byt spravci webu umozneno rucni zadani udaju.
 */
class InternalErrorException extends ErrorException
{

    /** @var array */
    private $formData;

    /** @var array */
    private $errors;


    public static function create(string $message = "", array $formData, array $errors): self
    {
        $e = new static($message);
        $e->setFormData($formData);
        $e->setErrors($errors);
        return $e;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function setFormData(array $formData): void
    {
        $this->formData = $formData;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }
    
}
