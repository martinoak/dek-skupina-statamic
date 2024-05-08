<?php

namespace Statamic\Addons\ReCaptcha;

use Statamic\Extend\ServiceProvider;
use Statamic\Addons\ReCaptcha\Validator;
use Statamic\Addons\ReCaptcha\Logger;

class ReCaptchaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Statamic\Addons\ReCaptcha\Validator
        $this->app->bind('Statamic\Addons\ReCaptcha\Validator', function ($app) {
            $siteKey = array_get($this->getConfig(), 'site_key');
            $secretKey = array_get($this->getConfig(), 'secret_key');
            $score_treshold = array_get($this->getConfig(), 'score_treshold');
            return new Validator($siteKey, $secretKey, $score_treshold);
        });

        // Statamic\Addons\ReCaptcha\Logger
        $this->app->bind('Statamic\Addons\ReCaptcha\Logger', function ($app) {
            return new Logger(array_get($this->getConfig(), 'log_dir'));
        });
    }
}
