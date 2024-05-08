<?php

namespace Statamic\Addons\CustomForms;

use Statamic\API\YAML;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'form_submissions';

    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public static function record($submission)
    {
        self::create([
            'form_name' => $submission->form->name,
            'ip' => request()->ip(),
            'snapshot' => $submission->data(),
        ]);
    }

    public function getSnapshotAttribute()
    {
        $array = (array) json_decode(array_get($this->getAttributes(), 'snapshot'), true);

        foreach ($array as &$value) {
            $value = implode(', ', self::flatten($value));
        }
        
        return $array;
    }

    private static function flatten($value, $buffer = [])
    {
        if (is_array($value)) {
            foreach ($value as $val) {
                $buffer = self::flatten($val, $buffer);
            }
        } else {
            $buffer[] = $value;
        }
        return $buffer;
    }
}
