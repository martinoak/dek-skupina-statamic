<?php

namespace Statamic\Addons\ReCaptcha;

use Statamic\Extend\Listener;
use Statamic\Forms\Submission as FormSubmission;
use Statamic\Addons\ReCaptcha\Validator;
use Statamic\Addons\ReCaptcha\Logger;

class ReCaptchaListener extends Listener
{
    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
    /*public $events = [
        'Form.submission.creating' => 'validate',
    ];*/
    
    /** @var Statamic\Addons\ReCaptcha\Validator */
    private $validator;

    /** @var Statamic\Addons\ReCaptcha\Logger */
    private $logger;

    const INPUT_NAME = 'captcha';

    /**
     * @param Statamic\Addons\ReCaptcha\Validator
     */
    public function __construct(Validator $validator, Logger $logger)
    {
        parent::__construct();

        $this->validator = $validator;
        $this->logger = $logger;
    }

    /*public function validate(FormSubmission $submission): array
    {
        $token = request()->input(self::INPUT_NAME);
        $action = $submission->formset()->name();  // formname
        $ip = request()->ip();

        // viz /statamic/bundles/Form/FormListener.php - create()
        // Allow addons to prevent the submission of the form, return
        // their own errors, and modify the submission.
        $response = [
            'submission' => $submission
        ];

        if (!$this->validator->validate($action, $token, $ip)) {
            
            $errors = $this->validator->getErrors();
            if (in_array(\ReCaptcha\ReCaptcha::E_CHALLENGE_TIMEOUT, $errors) || in_array('timeout-or-duplicate', $errors)) {
                $error = 'Časový limit antispamové ochrany vypršel, odešlete prosím formulář znovu.';

            } elseif (in_array(\ReCaptcha\ReCaptcha::E_SCORE_THRESHOLD_NOT_MET, $errors)) {
                $error = 'Systém vás vyhodnotil jako robota, není možné odeslat formulář.';

            } else {
                $error = 'Chyba v ověření antispamové ochrany, zkuste to prosím znovu.';
                
            }

            $this->logger->log(var_export($this->validator->getResponse()->toArray(), true), 'error');
            
            $response['errors'] = [$error];
        }

        return $response;
    }*/
}
