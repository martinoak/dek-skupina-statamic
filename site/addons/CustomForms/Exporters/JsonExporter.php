<?php

namespace Statamic\Addons\CustomForms\Exporters;

class JsonExporter extends AbstractExporter
{
    /**
     * Perform the export
     *
     * @return string
     */
    public function export()
    {
        $data = [];
        foreach ($this->submissions() as $submission) {
            $row = $submission->snapshot;
            $row['id'] = $submission->id;
            $row['ip'] = $submission->ip;
            $row['date'] = (array) $submission->created_at;
            $data[] = $row;
        }
        return json_encode($data);
    }

    /**
     * Get the content type
     *
     * @return string
     */
    public function contentType()
    {
        return 'application/json';
    }
}
