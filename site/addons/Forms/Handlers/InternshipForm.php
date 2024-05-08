<?php

namespace Statamic\Addons\Forms\Handlers;

use Statamic\Addons\Forms\TForm;
use Statamic\Extend\API;
use Statamic\Contracts\Forms\Submission;
use Statamic\API\Email;
use Statamic\Addons\Forms\IHandler;

class InternshipForm extends API implements IHandler
{
    use TForm;

    private ?string $lang = null;
    private array $data = [];

    /**
     * @param Submission $submission
     * @return bool
     */
    public function handle(Submission $submission): bool
    {
        $this->data = $data = $this->desanitize($submission->data());
        $this->lang = $lang = $this->data['lang'];

        // Emaily
        $vars = [
            'jmeno' => array_get($data, 'name', 'nezadáno'),
            'email' => array_get($data, 'e-mail', 'nezadáno'),
            'telefon' => array_get($data, 'phone', 'nezadáno'),
            'obor' => array_get($data, 'obor', 'nezadáno'),
            'spoluprace' => array_get($data, 'coop', 'nezadáno'),
            'poznamka' => array_get($data, 'note', 'nezadáno'),
        ];

        // Interni
        if ($lang === self::LANG_SK) {
            $subject = 'Skupina-dek.cz: Odpovedzte prosím na dotaz stáže';
        } else {
            $subject = 'Skupina-dek.cz: Odpovězte na dotaz stáže';
        }
        $email = Email::create();
        //$email->to(self::DEK_EMAIL)
        $email->to($this->getMailAdress())
            ->bcc('karel.zimmermann@dek-cz.com, jan.janda@dek-cz.com')
            ->subject($subject)
            ->with($vars)
            ->template($lang . '_intern');
        if (!empty($data['files'])) {
            foreach ($data['files'] as $file) {
                $email->attach(storage_path().'/'.$file);
            }
        }
        $email->send();


        // Uzivatel
        if ($lang === self::LANG_CZ) {
            $subject = 'Váš dotaz jsme přijali a zařadili ho do systému ke zpracování';
        } else {
            $subject = 'Vašu otázku sme prijali a zaradili do systému na spracovanie.';
        }

        $email = Email::create();
        $email->to($data['e-mail'])
            ->bcc('karel.zimmermann@dek-cz.com, jan.janda@dek-cz.com')
            ->subject($subject)
            ->with($vars)
            ->template($lang . '_intern_notification');
        $email->send();

        return true;
    }

    public function getData(): array
    {
        return $this->data;
    }

    private function getMailAdress(): string
    {
       return 'studenti@dek.cz';
    }

}