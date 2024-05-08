<?php

declare(strict_types=1);


namespace Statamic\Addons\Forms;

use Statamic\API\Email;


class ErrorMails
{

    const TO = 'kariera@dek.cz';
    const CC = 'miloslav.kostir@dek-cz.com';
    const FROM = 'noreply@skupina-dek.com';
    //const REPLY_TO = 'helpdesk@dek.cz';
    const SUBJECT = 'POZOR!! Došlo k chybě při odeslání formuláře na webu skupina-dek.cz';

    public static function errorForm(array $data, array $files, array $exceptions)
    {
        $error = '';
        foreach ($exceptions as $filename => $e) {
            $error .= substr(strrchr($filename, '/'), 1) .': '.$e->getMessage() . "\r\n";
        }

        $vars = [
            'dump' => var_export($data, true),
            'error' => $error
        ];

        try {
            $email = Email::create();
            $email->to(self::TO)
                    ->cc(self::CC)
                    //->replyTo(self::REPLY_TO)
                    ->from(self::FROM)
                    ->subject(self::SUBJECT)
                    ->with($vars)
                    ->template('error_mail')
                    ->in(__DIR__ . '/resources/templates/email');
            foreach ($files as $file) {
                $email->attach($file);
            }
            $email->send();
        } catch (\Exception $e) {
            // Kdyz selze odeslani emailu...
            self::errorFormSimple($data, $error);
        }
    }

    /**
     * Odesle se nativni PHP funkci mail()
     * @param array $data
     * @param string $error
     */
    private static function errorFormSimple(array $data, string $error)
    {
        $subject = self::SUBJECT;

        $message = 'Ahoj,'.PHP_EOL;
        $message .= 'došlo k chybě při ukládání souboru do Agendy z formuláře na stránkách skupina.dek.cz, sekce kariéra.'.PHP_EOL;
        $message .= ''.PHP_EOL;
        $message .= 'Data:'.PHP_EOL;
        $message .= var_export($data, true);
        $message .= PHP_EOL.PHP_EOL;
        $message .= '(Generovaný email, neodpovídejte)'.PHP_EOL;
        $message .= PHP_EOL.PHP_EOL;
        $message .= 'Error: '.$error.PHP_EOL;

        $headers = 'From: ' . self::FROM . PHP_EOL .
            //'Reply-To: ' . self::REPLY_TO . PHP_EOL .
            'Cc: ' . self::CC;

        mail (self::TO, $subject, $message, $headers);
    }

}
