<?php

namespace Statamic\Addons\Forms\Exceptions;

/**
 * Zavazna interni chyba. Vyhazuje se pri zpracovani formulare u chyby,
 * kterou nemuze uzivatel ovlivnit a zaroven nelze pokracovat ve zpracovani formulare.
 * Pouzit tam, kde na jeden proces ukladani formulare navazuje dalsi (napr. platba online).
 * Zpracovani teto chyby by se melo resit individualne dle kodu chyby (viz const)
 */
class FatalErrorException extends ErrorException
{

    /** codes */
    const E_UNKNOWN = 1;        // neznama chyba
    const E_AGENDA = 2;         // obecna chyba v agende
    const E_CONNECTION = 3;     // chyba pripojeni do agendy
    
}
