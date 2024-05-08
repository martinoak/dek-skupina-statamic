<?php

namespace Statamic\Addons\CustomForms\Exporters;

abstract class AbstractExporter
{
    /**
     * @var Statamic\Contracts\Forms\Form
     */
    private $form;

    /**
     * @var Illuminate\Database\Eloquent\Collection
     */
    private $submissions;

    /**
     * Get or set the form
     *
     * @param  Statamic\Contracts\Forms\Form|null $form
     * @return Statamic\Contracts\Forms\Form
     */
    public function form($form = null)
    {
        if (is_null($form)) {
            return $this->form;
        }

        $this->form = $form;
    }

    /**
     * Set submissions
     *
     * @param  Illuminate\Database\Eloquent\Collection $submissions
     * @return void
     */
    public function submissions($submissions = null)
    {
        if (is_null($submissions)) {
            return $this->submissions;
        }

        $this->submissions = $submissions;
    }

    /**
     * Get the content type
     *
     * @return string
     */
    public function contentType()
    {
        return 'text/plain';
    }
}
