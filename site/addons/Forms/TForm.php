<?php

namespace Statamic\Addons\Forms;


trait TForm
{
    private function desanitize(array $data)
    {
        foreach ($data as &$val) {
            if (is_array($val)) {
                $val = $this->desanitize($val);
            } else {
                $val = html_entity_decode($val);
            }
        }
        return $data;
    }
}
