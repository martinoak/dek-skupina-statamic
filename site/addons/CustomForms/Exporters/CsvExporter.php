<?php

namespace Statamic\Addons\CustomForms\Exporters;

use SplTempFileObject;
use League\Csv\Writer;

class CsvExporter extends AbstractExporter
{
    /** @var Writer */
    private $writer;

    /** @var string */
    private $dateFormat;

    /** @var array */
    private $fields;

    /**
     * Create a new CsvExporter
     * @param string $dateFormat see PHP date()
     * @param string $delimiter comma ',' or semicolon ';'
     * @param string $bom see League\Csv\Writer BOM_* constants
     */
    public function __construct($dateFormat = null, $delimiter = null, $bom = null)
    {
        $this->writer = Writer::createFromFileObject(new SplTempFileObject);
        $this->writer->setOutputBOM($bom === null ? Writer::BOM_UTF8 : $bom);
        $this->writer->setDelimiter($delimiter ? $delimiter : ',');

        $this->dateFormat = is_string($dateFormat) ? $dateFormat : 'r';
    }

    /**
     * Perform the export
     *
     * @return string
     */
    public function export()
    {
        $this->insertHeaders();

        $this->insertData();

        return (string) $this->writer;
    }

    /**
     * Insert the headers into the CSV
     */
    private function insertHeaders()
    {
        $fields = $this->form()->fields();
        $headers = [];

        foreach ($this->submissions() as $submission) {
            $snapshot = $submission->snapshot;
            foreach ($snapshot as $index => $value) {
                $display = isset($fields[$index]) ? $fields[$index]['display'] : $index;
                $headers[$index] = $display;
                $this->fields[$index] = $display;
            }
        }

        $headers[] = 'ip';
        $headers[] = 'date';

        $this->writer->insertOne($headers);
    }

    /**
     * Insert the submission data into the CSV
     */
    private function insertData()
    {
        $fields = $this->fields;
        foreach ($this->submissions() as $submission) {
            $row = [];
            foreach ($fields as $index => $display) {
                $snapshot = $submission->snapshot;
                $row[] = html_entity_decode(isset($snapshot[$index]) ? $snapshot[$index] : '');
            }
            $row[] = $submission->ip;
            $row[] = $submission->created_at->format($this->dateFormat);
            $this->writer->insertOne($row);
        }
    }

}
