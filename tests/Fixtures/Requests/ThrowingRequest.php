<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ThrowingRequest extends FormRequest
{
    public function rules(): array
    {
        // Simulates a FormRequest whose rules() calls $this->input() or $this->user(),
        // which throw without a request context. The extractor must degrade gracefully.
        throw new \RuntimeException('No request context available.');
    }
}
