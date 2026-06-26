<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Requests;

use Botnetdobbs\Luminous\Attributes\ApiProperty;
use Botnetdobbs\Luminous\Tests\Fixtures\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePaymentRequest extends FormRequest
{
    #[ApiProperty('Amount in minor currency units', example: 10000, minimum: 1)]
    public int $amount;

    #[ApiProperty('ISO 4217 currency code', example: 'USD', enum: ['USD', 'EUR', 'KES', 'NGN', 'GHS'])]
    public string $currency;

    #[ApiProperty('Source account UUID', format: 'uuid')]
    public string $source_account_id;

    #[ApiProperty('Destination account UUID', format: 'uuid')]
    public string $destination_account_id;

    #[ApiProperty('Human-readable description', maxLength: 500)]
    public string $description;

    #[ApiProperty('Arbitrary metadata', nullable: true)]
    public ?array $metadata;

    #[ApiProperty('Current payment status', readOnly: true)]
    public PaymentStatus $status;

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', Rule::in(['USD', 'EUR', 'KES', 'NGN', 'GHS'])],
            'source_account_id' => ['required', 'uuid'],
            'destination_account_id' => ['required', 'uuid', 'different:source_account_id'],
            'description' => ['required', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
        ];
    }
}
