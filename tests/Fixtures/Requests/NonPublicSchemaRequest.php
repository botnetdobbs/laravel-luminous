<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Requests;

use Botnetdobbs\Luminous\Attributes\ApiShape;
use Botnetdobbs\Luminous\Support\Shape;
use Illuminate\Foundation\Http\FormRequest;

#[ApiShape]
class NonPublicSchemaRequest extends FormRequest
{
    protected static function schema(): Shape
    {
        return Shape::object(['name' => Shape::string()]);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string']];
    }
}
