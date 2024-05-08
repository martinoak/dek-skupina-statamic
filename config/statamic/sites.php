<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sites
    |--------------------------------------------------------------------------
    |
    | Each site should have root URL that is either relative or absolute. Sites
    | are typically used for localization (eg. English/French) but may also
    | be used for related content (eg. different franchise locations).
    |
    */

    'sites' => [

        'default' => [
            'name' => 'Čeština',
            'locale' => 'cs.utf8',
            'url' => env('SETTINGS_SYSTEM_LOCALES_CS_URL'),
        ],

        'en' => [
            'name' => 'English Language',
            'locale' => 'en_US',
            'url' => env('SETTINGS_SYSTEM_LOCALES_EN_URL'),
        ],

        'sk' => [
            'name' => 'Slovenština',
            'locale' => 'sk_SK',
            'url' => env('SETTINGS_SYSTEM_LOCALES_SK_URL'),
        ],

    ],
];
