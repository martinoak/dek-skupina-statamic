<?php

namespace Statamic\Addons\CustomForms;

use Statamic\Contracts\Forms\Submission;

class Response
{
    /** @var array */
    private $errors = [];

    /** @var Statamic\Contracts\Forms\Submission */
    private $submission;

    /**
     * @param Submission $submission
     * @param array $errors
     * @return \static
     */
    public static function create(Submission $submission, array $errors = [])
    {
        $response = new static;
        $response->errors = $errors;
        $response->submission = $submission;
        return $response;
    }

    /**
     * @return array
     */
    public function getErrors()//: array
    {
        return $this->errors;
    }

    /**
     * @return Submission
     */
    public function getSubmission()//: Submission
    {
        return $this->submission;
    }

    /**
     * Array for event Form.submission.creating
     * @return array
     */
    public function toArray()//: array
    {
        return [
            'errors' => $this->errors,
            'submission' => $this->submission
        ];
    }

}