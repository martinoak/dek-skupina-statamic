<?php

namespace Statamic\Addons\Forms\Handlers;

use DekApps\MssqlProcedure\MssqlException;
use Statamic\Addons\Forms\ErrorMails;
use Statamic\Extend\API;
use Statamic\Addons\MssqlManager\MssqlManagerApi;
use Statamic\Contracts\Forms\Submission;
use Statamic\API\Email;
use Statamic\Addons\Forms\IHandler;
use Statamic\Addons\Forms\Exceptions;
use Statamic\Addons\Forms\Logger;

class CandidateForm extends API implements IHandler
{

    use \Statamic\Addons\Forms\TForm;

    private ?string $lang;
    private array $data = [];
    private array $files = [];

    
    /**
     * @param \Statamic\Contracts\Forms\Submission $submission
     * @return bool
     */
    public function handle(Submission $submission): bool
    {
        $this->data = $this->desanitize($submission->data());
        $this->lang = $this->data['lang'];
       
        $files = $this->getFiles($submission);
        $this->files = $files;
                
        $this->validate();
        
        $vrId = array_get($this->data, 'vr_id');
        $vrs = $this->api('MssqlManager')->getVr(MssqlManagerApi::VR_TYPE_REGULAR | MssqlManagerApi::VR_TYPE_SPECIFIC, $this->lang);

        if ($vrs && $vrId) {
            $vr = $vrs[$vrId];
            $candidateId = $this->saveCandidate($vrId);  // Do agendy
        } else {
            $vr = ['MAIL' => $this->getMailAdress()];
            $candidateId = null;
        }

        // Emaily
        $this->sendCanditadeFormMail((string) $candidateId, $vr);

        return true;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @throws Exceptions\UserErrorException
     * @return bool
     */
    private function validate(): bool
    {
        $data = $this->data;
        if (array_key_exists('vr_id', $data)) {
            if (empty($data['vr_id'])) {
                throw Exceptions\UserErrorException::create('Výběr pozice je povinný údaj', 'vr_id-error');
            }
        }
        // if (empty($data['name'])) {
        //    throw Exceptions\UserErrorException::create('Jméno je povinný údaj', 'name');  // asi zbytecnost, validuje primo statamic
        // }
        return true;
    }

    private function getFiles(Submission $submission): array
    {
        $uploadsDir = root_path('uploads');
        $allowExt = ['doc', 'docx', 'odt', 'pdf', 'jpg', 'jpeg', 'png'];
        $allowSize = 10;
        $files = $submission->get('files') ?? [];
        $result = [];

        // Validace
        foreach ($files as $key => $file) {
            $f = "$uploadsDir/$file";
            $ext = strtolower(substr(strrchr($f, '.'), 1));
            if (!in_array($ext, $allowExt)) {
                throw Exceptions\UserErrorException::create('Je možné nahrávat jen soubory ' . implode(', ', $allowExt), 'files');
            }

            if (filesize($f) > ($allowSize * 1024 * 1024)) {
                throw Exceptions\UserErrorException::create('Nahrávaný soubor nesmí být větší jak ' . $allowSize . ' MB', 'files');
            }
            $result[$key] = $f;
        }

        return $result;
    }

    private function saveCandidate($vrId)
    {
        $data = $this->data;
        $files = $this->files;
        list($firstname, $lastname) = $this->splitName($data['name']);

        $candidateId = $this->api('MssqlManager')->insertNewCandidate($firstname, $lastname, array_get($data, 'e-mail', ''), array_get($data, 'phone', ''), array_get($data, 'advertisement', ''), array_get($data, 'note', ''), $this->lang);
        $this->api('MssqlManager')->assignCandidateToVr($vrId, $candidateId);
        foreach ($files as $file) {
            $e = $this->saveFile($file, $candidateId);
            $e ? ($exceptions[$file] = $e) : null;
        }

        if (isset($exceptions)) {
            ErrorMails::errorForm($data, $files, $exceptions);
        }
        
        return $candidateId;
    }

    private function saveFile($file, $candidateId, int $try = 0): ?MssqlException
    {
        try {
            $this->api('MssqlManager')->uploadCV($file, $candidateId);
            Logger::log('Soubor: '.$file.', candidateId: '.$candidateId.', pokus: '.$try.', SUCCESS');
            return null;
        } catch (MssqlException $e) {
            // Chyba z MSSQL: Nepodařilo se vygenerovat ID souboru.
            // Nastava jak kdy, zkusit znova (2x)
            Logger::log('Soubor: '.$file.', candidateId: '.$candidateId.', pokus: '.$try.', error: '.$e->getMessage());
            if ($try < 3) {
                return $this->saveFile($file, $candidateId, $try + 1);
            } else {
                return $e;
            }
        }
    }

    private function splitName(string $fullName): array
    {
        $splittedName = explode(' ', $fullName);
        if (count($splittedName) === 2) {
            $firstname = $splittedName[0];
            $lastname = $splittedName[1];
        } else {
            $lastname = array_pop($splittedName);
            $firstname = implode(' ', $splittedName);
        }
        return [$firstname, $lastname];
    }

    private function sendCanditadeFormMail($candidateId, array $vr)
    {
        $data = $this->data;
        $vrId = array_get($data, 'vr_id');
        $lang = $this->lang;
        $files = $this->files;
        
        $vars = [
            'candidate_id' => $candidateId,
            'name' => array_get($data, 'name', 'nezadáno'),
            'email' => array_get($data, 'e-mail', 'nezadáno'),
            'phone' => array_get($data, 'phone', 'nezadáno'),
            'vr_id' => $vrId ?? '',
            'vr_title' => $vr['POPIS'] ?? '',
            'location' => isset($vr['MESTO']) ? $vr['MESTO'] : array_get($data, 'location', 'nezadáno'),
            'note' => array_get($data, 'note', 'nezadáno'),
            'advertisement' => array_get($data, 'advertisement', 'nezadáno'),
            //'files' => implode("\r\n", $files),
        ];
        //dump($vr);die;

        // Personalni
        if ($lang === self::LANG_SK) {
            $subject = 'Nový uchádzač o zamestnanie - ';
        }else{
            $subject = 'Nový uchazeč o zaměstnání - ';
        }

        if ($vr['MAIL'] !== '') {
            $email = Email::create();
            $email->to($vr['MAIL'])
                ->bcc('karel.zimmermann@dek-cz.com, jan.janda@dek-cz.com')
                ->subject($subject . $vars['vr_title'])
                ->with($vars)
                ->template($lang . '_career');
            foreach ($files as $file) {
                $email->attach($file);
            }
            $email->send();
        }

        // Ucastnik

        if ($lang === self::LANG_CZ) {
            $subject = 'Vaši žádost o zaměstnání jsme přijali';
        } elseif ($lang === self::LANG_SK) {
            $subject = 'Vašu žiadosť o zamestnanie sme prijali';
        } else {
            $subject = 'We have received your job application.';
        }

        $email = Email::create();
        $email->to($data['e-mail'])
            ->bcc('karel.zimmermann@dek-cz.com, jan.janda@dek-cz.com')
            ->subject($subject)
            ->with($vars)
            ->template($lang . '_career_notification');
        $email->send();
    }

    private function getMailAdress()
    {
        if ($this->lang == self::LANG_SK) {
            return 'kariera@dek.sk';
        } else {
            return 'kariera@dek.cz';
        }
    }

}