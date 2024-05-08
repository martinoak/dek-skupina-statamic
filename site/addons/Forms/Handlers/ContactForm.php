<?php

namespace Statamic\Addons\Forms\Handlers;

use Statamic\Extend\API;
use DekApps\MssqlProcedure\MssqlException;
use InternalIterator;
use Statamic\Addons\MssqlManager\MssqlManagerApi;
use Statamic\Contracts\Forms\Submission;
use Statamic\Addons\CustomForms\Response;
use Statamic\API\Email;
use Statamic\Addons\Forms\IHandler;
use Statamic\Addons\Forms\Exceptions;

class ContactForm extends API implements IHandler
{

    use \Statamic\Addons\Forms\TForm;

    private ?string $lang;
    private array $data = [];

    
    /**
     * @param \Statamic\Contracts\Forms\Submission $submission
     * @return bool
     */
    public function handle(Submission $submission): bool
    {
        $this->data = $data = $this->desanitize($submission->data());
        $this->lang = $lang = $this->data['lang'];

        // Emaily
        $vars = [
            //'name' => array_get($data, 'name', 'nezadáno'),
            'email' => array_get($data, 'e-mail', 'nezadáno'),
            //'phone' => array_get($data, 'telefon', 'nezadáno'),
            'message' => array_get($data, 'zprava', 'nezadáno'),
        ];

        // Interni
        if ($lang === self::LANG_SK) {
            $subject = 'Skupina-dek.cz: Odpovedzte prosím na otázku zo všeobecného formulára';
        }else{
            $subject = 'Skupina-dek.cz: Dotaz z obecného formuláře';
        }
        $email = Email::create();
        //$email->to(self::DEK_EMAIL)
        $email->to($this->getMailAdress())
            ->bcc('karel.zimmermann@dek-cz.com, jan.janda@dek-cz.com')
            ->subject($subject)
            ->with($vars)
            ->template($lang . '_contact');
        $email->send();


        // Uzivatel
        if ($lang === self::LANG_CZ) {
            $subject = 'Váš dotaz jsme přijali a zařadili ho do systému ke zpracování';
        } elseif ($lang === self::LANG_SK) {
            $subject = 'Vašu otázku sme prijali a zaradili do systému na spracovanie.';
        } else {
            $subject = "We've received your query and put it in the system for processing";
        }

        $email = Email::create();
        $email->to($data['e-mail'])
            ->bcc('karel.zimmermann@dek-cz.com, jan.janda@dek-cz.com')
            ->subject($subject)
            ->with($vars)
            ->template($lang . '_contact_notification');
        $email->send();

        return true;
    }

    public function getData(): array
    {
        return $this->data;
    }

    private function getMailAdress()
    {
        if ($this->lang == self::LANG_SK) {
            return 'stavebniny@dek.sk';
        } else {
            return 'info@dek.cz';
        }
    }

}