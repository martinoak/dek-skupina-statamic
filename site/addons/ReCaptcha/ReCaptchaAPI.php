<?php

namespace Statamic\Addons\ReCaptcha;

use Statamic\Extend\API;
use Statamic\Forms\Submission as FormSubmission;
use Statamic\Addons\ReCaptcha\Validator;
use Statamic\Addons\ReCaptcha\Logger;

class ReCaptchaAPI extends API
{

    /** @var Statamic\Addons\ReCaptcha\Validator */
    private $validator;

    /** @var Statamic\Addons\ReCaptcha\Logger */
    private $logger;

    const INPUT_NAME = 'captcha';

    /**
     * @param Statamic\Addons\ReCaptcha\Validator
     * @param Statamic\Addons\ReCaptcha\Logger
     */
    public function __construct(Validator $validator, Logger $logger)
    {
        parent::__construct();

        $this->validator = $validator;
        $this->logger = $logger;
    }


    public function reCaptchaError(FormSubmission $submission)
    {
        $token = request()->input(self::INPUT_NAME);
        $action = $submission->formset()->name();  // formname
        $ip = request()->ip();
        
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

            return $error;
        }

        return null;
    }

}
