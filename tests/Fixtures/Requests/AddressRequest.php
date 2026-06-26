<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'address' => ['required', 'array'],
            'address.street' => ['required', 'string'],
            'address.city' => ['required', 'string'],
            'address.country' => ['required', 'string', 'size:2'],
        ];
    }
}
