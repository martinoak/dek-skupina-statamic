<?php

namespace Statamic\Addons\CustomForms;

use Statamic\API\Nav;
use Statamic\Extend\Listener;
use Statamic\API\Form;
use Statamic\Forms\Submission as FormSubmission;
use Statamic\Addons\ReCaptcha\ReCaptchaAPI;

class CustomFormsListener extends Listener
{

    public $events = [
        'cp.add_to_head' => 'addToHead',
        'cp.nav.created' => 'addNavItem',
        'Form.submission.creating' => 'callback',
        'Form.submission.created' => 'record'
    ];

    public function addToHead()
    {
        return $this->css->tag('custom-forms');
    }

    public function addNavItem($nav)
    {
        $icon = $this->getConfig('menu_icon');
        $label = $this->getConfig('menu_label');

        $item = Nav::item($label)->route('customforms.index')->icon($icon);

        if ($this->getConfig('replace')) {
            $nav->remove('tools.forms');
        }

        $item->add(function ($i) {
            $forms = Form::all();
            foreach ($forms as $form) {
                $i->add(Nav::item($form['title'])->route('customforms.submissions', $form['name']));
            }
        });

        $nav->addTo('tools', $item);
    }

    public function record(FormSubmission $submission)
    {
        $formset = $submission->formset();
        $store = $this->getConfig('store', 'default');

        if ($store === 'default') {
            // inherit formset value
            $store = $formset->get('store', true);  // undefined -> true
        } else {
            $store = (bool) $store;  // cast 0 or 1 from settings
        }
        
        if ($store === true) {
            Submission::record($submission);
        }
    }

    public function callback(FormSubmission $submission)
    {
        if ($this->getConfig('recaptcha_enabled') && $reCaptchaError = $this->api('ReCaptcha')->reCaptchaError($submission)) {
            return Response::create($submission, [ReCaptchaAPI::INPUT_NAME => $reCaptchaError])
                    ->toArray();
        }

        $formName = CustomFormsAPI::getFormName($submission);

        $callback = $this->getConfig('callbacks.'.$formName);

        if (isset($callback['api']) && isset($callback['method'])) {
            $response = call_user_func([$this->api($callback['api']), $callback['method']], $submission);
            if (! $response instanceof Response) {
                throw new \UnexpectedValueException('Callback must return instance of Statamic\Addons\CustomForms\Response');
            }
        } else {
            $response = Response::create($submission);
        }

        return $response->toArray();
    }

}
