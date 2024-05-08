<?php

namespace Statamic\Addons\Forms;

use Statamic\Extend\API;
use Statamic\Contracts\Forms\Submission;
use Statamic\Addons\CustomForms\Response;

class FormsAPI extends API
{

    public function saveCandidateForm(Submission $submission): Response
    {
        return $this->handleForm(new Handlers\CandidateForm, $submission);
    }

    public function saveContactForm(Submission $submission): Response
    {
        return $this->handleForm(new Handlers\ContactForm, $submission);
    }

    public function saveInternshipForm(Submission $submission): Response
    {
        return $this->handleForm(new Handlers\InternshipForm, $submission);
    }

    private function handleForm(IHandler $handler, Submission $submission): Response
    {
        try {
            $handler->handle($submission);
        } catch (\Exception $e) {
            Logger::log(get_class($e) . ': ['.$e->getCode() . '] ' . $e->getMessage() . 'Data: '.json_encode($handler->getData()));
            if ($e instanceof Exceptions\UserErrorException) {
                // Uzivatelske chyby - zobrazit error notification ve formulari
                $errors = [$e->getKey() => $e->getMessage()];
            } else {
                // Ostatni Exceptions nechat probublat
                throw $e;
            }
        }

        return Response::create($submission, $errors ?? []);
    }

}
