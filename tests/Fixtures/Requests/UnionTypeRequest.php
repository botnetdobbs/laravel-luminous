<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Requests;

use Botnetdobbs\Luminous\Attributes\ApiProperty;
use Illuminate\Foundation\Http\FormRequest;

class UnionTypeRequest extends FormRequest
{
    #[ApiProperty('A union-typed property')]
    public int|float $amount;

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric'],
        ];
    }
}
