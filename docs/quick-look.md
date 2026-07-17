# Quick look

A minimal end-to-end example: controller attributes, FormRequest rules, and a resource schema.

## The controller

Tag the controller and secure it. Every method inside inherits both automatically.

```php
use Botnetdobbs\Luminous\Attributes\ApiTag;
use Botnetdobbs\Luminous\Attributes\ApiSecurity;
use Botnetdobbs\Luminous\Attributes\ApiOperation;
use Botnetdobbs\Luminous\Attributes\ApiParam;
use Botnetdobbs\Luminous\Attributes\ApiQuery;
use Botnetdobbs\Luminous\Attributes\ApiHeader;
use Botnetdobbs\Luminous\Attributes\ApiResponse;
use Botnetdobbs\Luminous\Attributes\ApiIgnore;

#[ApiTag('Payments')]
#[ApiSecurity('bearerAuth')]
class PaymentController extends Controller
{
    #[ApiOperation('List payments')]
    #[ApiQuery('status', 'Filter by status', enum: ['pending', 'succeeded', 'failed'])]
    #[ApiQuery('limit', 'Results per page', type: 'integer', example: 20)]
    #[ApiResponse(200, PaymentResource::class, 'Payments list', paginated: true)]
    public function index(Request $request): JsonResponse {}

    #[ApiOperation('Get a payment')]
    #[ApiParam('payment', 'Payment ID', type: 'integer', example: 42)]
    #[ApiResponse(200, PaymentResource::class, 'Payment retrieved')]
    #[ApiResponse(404, ErrorResource::class, 'Not found')]
    public function show(Payment $payment): JsonResponse {}

    #[ApiOperation('Create a payment', 'Initiates a payment. Requires an idempotency key.')]
    #[ApiHeader('Idempotency-Key', required: true, format: 'uuid')]
    #[ApiResponse(201, PaymentResource::class, 'Payment created')]
    #[ApiResponse(409, ErrorResource::class, 'Idempotency conflict')]
    #[ApiResponse(422, ErrorResource::class, 'Validation failed')]
    public function store(CreatePaymentRequest $request): JsonResponse {}

    #[ApiIgnore]
    public function internalReconcile(): void {}
}
```

Luminous detects `CreatePaymentRequest` from the type hint and reads its `rules()` to
build the request body schema. It detects `Payment` as a route model bound parameter
and maps it to the `{payment}` segment automatically. No wiring required.

## The FormRequest

`rules()` defines the schema. `hints()` adds descriptions and examples for fields
that need more context. You only need `hints()` when the field name alone is not
enough.

```php
use Botnetdobbs\Luminous\Support\Shape;

class CreatePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount'      => ['required', 'integer', 'min:1'],
            'currency'    => ['required', Rule::in(['USD', 'EUR', 'KES'])],
            'description' => ['sometimes', 'string', 'max:500'],
        ];
    }

    public function hints(): array
    {
        return [
            'amount' => Shape::integer()
                ->description('Amount in minor units. 10000 = $100.00')
                ->example(10000),
        ];
    }
}
```

## The API Resource

Add `#[ApiShape]` and write a `schema()` method. Luminous calls it when building the
response schema.

```php
use Botnetdobbs\Luminous\Attributes\ApiShape;
use Botnetdobbs\Luminous\Support\Shape;

#[ApiShape]
class PaymentResource extends JsonResource
{
    public static function schema(): Shape
    {
        return Shape::object([
            'id'          => Shape::integer()->readOnly(),
            'status'      => Shape::enum(PaymentStatus::class)->readOnly(),
            'amount'      => Shape::integer()->readOnly()->description('Amount in minor units'),
            'currency'    => Shape::string()->readOnly()->example('USD'),
            'description' => Shape::string()->nullable()->readOnly(),
            'created_at'  => Shape::dateTime()->readOnly(),
        ]);
    }

    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'amount'      => $this->amount,
            'currency'    => $this->currency,
            'description' => $this->description,
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
```

That is the whole picture. The guide pages go into every detail.

## Next steps

- [Configuration](/configuration)
- [Documenting controllers](/controllers)
- [Documenting form requests](/form-requests)
- [Documenting API resources](/resources)
- [The Shape builder](/shape-builder)
- [Security](/security)
- [CLI and deployment](/deployment)
- [Attribute reference](/attributes)
