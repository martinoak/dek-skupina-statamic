<?php

namespace Statamic\Addons\ReCaptcha;

use ReCaptcha\ReCaptcha as GReCaptcha;
use ReCaptcha\Response as GResponse;
use ReCaptcha\RequestMethod;

class Validator
{

	/** @var float */
    private $scoreTreshold;

    /** @var string */
    private $siteKey;

    /** @var GReCaptcha */
    private $recaptcha;

    /** @var GResponse */
    private $response;


	public function __construct($siteKey, $secretKey, $scoreTreshold)
	{
		$this->siteKey = $siteKey;
        $this->recaptcha = new GReCaptcha($secretKey, new RequestMethod\CurlPost);
        $this->scoreTreshold = $scoreTreshold;
	}

    public function validate($action, $recaptchaResponse, $ip = NULL) {
		$this->response = $this->recaptcha->setExpectedAction($action)
                ->setScoreThreshold($this->scoreTreshold)
                ->verify($recaptchaResponse, $ip);

        if ($this->response->getErrorCodes() === [] && $this->response->getScore() !== null) {
            //$this->log($this->response);
        }

		if ($this->response->isSuccess()) {
            return true;
        } else {
            return false;
        }
	}

    public function getResponse()
    {
        return $this->response;
    }

	public function getErrors() {
        return $this->response->getErrorCodes();
	}

	public function getSiteKey()
	{
		return $this->siteKey;
	}

}