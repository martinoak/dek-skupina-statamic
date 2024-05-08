<?php

namespace Statamic\Addons\CustomForms;

use Statamic\Extend\API;
use Statamic\Forms\Submission as FormSubmission;
//use Statamic\Forms\Formset;

class CustomFormsAPI extends API
{

    /**
     * Accessed by $this->api('CustomForms')->saveSubmission($submission) from other addons
     * @param FormSubmission $submission
     */
    public function saveSubmission(FormSubmission $submission)
    {
        Submission::record($submission);
    }

    /**
     * Accessed by $this->api('CustomForms')->getFormName($submission) from other addons
     * @param FormSubmission $submission
     * @return string
     */
    public static function getFormName(FormSubmission $submission)//: string
    {
        return $submission->formset()->name();
    }

}
