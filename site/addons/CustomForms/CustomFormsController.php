<?php

namespace Statamic\Addons\CustomForms;

use Statamic\Extend\Controller;
use Statamic\API\Form;

class CustomFormsController extends Controller
{
    private $datetimeFormat;

    public function __construct()
    {
        $this->middleware('auth');
        $defaultDtFromat = \Statamic\API\Config::get('system.date_format', 'Y-m-d H:i:s');
        $this->datetimeFormat = $this->getConfig('datetime_format', $defaultDtFromat);
    }

    public function index()
    {
        $forms = Form::all();
        foreach ($forms as &$form) {
            $form['count'] = Submission::where('form_name', $form['name'])
                    ->count('*');
        }
        return $this->view('index', [
            'forms' => $forms
        ]);
    }

    public function submissions($name)
    {
        $form = Form::get($name);
        $formset = $form->formset();
        $submissions = Submission::where('form_name', $form->name)
                ->where('active', 1)
                ->latest()
                ->get();
        return $this->view('submissions', [
            'name' => $form->name(),
            'title' => $formset->title(),
            'columns' => $formset->columns(),
            'submissions' => $submissions,
            'datetime_format' => $this->datetimeFormat
        ]);
    }

    public function submission($name, $id)
    {
        $form = Form::get($name);
        $columns = [];
        foreach ($form->fields() as $name => $field) {
            $columns[$name] = $field['display'];
        }
        $submission = Submission::find($id);
        return $this->view('submission', [
            'name' => $form->name(),
            'title' => $form->title(),
            'columns' => $columns,
            'submission' => $submission,
            'datetime_format' => $this->datetimeFormat
        ]);
    }

    public function export($name, $type)
    {
        $type = strtolower($type);
        $form = Form::get($name);
        $submissions = Submission::where('form_name', $form->name)
                ->latest()
                ->get();

        if ($type === 'csv') {
            $delimiter = $this->getConfig('csv_delimiter');
            $bomCfg = $this->getConfig('csv_bom');
            $ref = new \ReflectionClass('League\Csv\AbstractCsv');
            $bom = $ref->getConstant($bomCfg);
            $exporter = new Exporters\CsvExporter($this->datetimeFormat, $delimiter, $bom ?: null);
        } else {
            $exporter = new Exporters\JsonExporter();
        }
        
        $exporter->form($form);
        $exporter->submissions($submissions);
        $exported = $exporter->export();

        return response($exported)
                ->header('Content-Type', $exporter->contentType())
                ->header('Content-Disposition', 'attachment; filename="'.$name.'-'.time().'.'.$type.'"');
    }

    public function delete($name, $id)
    {
        $hard = $this->getConfig('hard_del', false);
        $submission = Submission::where('form_name', $name)
                ->where('id', $id);
        
        if ($hard) {
            $result = $submission->delete();
        } else {
            $result = $submission->update(['active' => 0]);
        }

        if ($result) {
            $this->success('Submission deleted');
        }

        return redirect()->route('customforms.submissions', [$name]);
    }

}
