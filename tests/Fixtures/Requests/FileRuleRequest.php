<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'attachment' => ['required', 'file'],
        ];
    }
}
