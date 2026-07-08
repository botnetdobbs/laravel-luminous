# Documenting API Resources

Your `JsonResource` classes define what your API sends back to clients. Luminous reads
them to generate response schemas. You have two strategies. Pick one and stick with
it across your project.

---

## Strategy 1: The `schema()` method (recommended)

Add `#[ApiShape]` to the class and write a `public static function schema()` method
using the `Shape` builder. The schema sits right above `toArray()` so you can read
the contract and the implementation in the same place.

```php
use Botnetdobbs\Luminous\Attributes\ApiShape;
use Botnetdobbs\Luminous\Support\Shape;

#[ApiShape]
class PaymentResource extends JsonResource
{
    public static function schema(): Shape
    {
        return Shape::object([
            'id'         => Shape::uuid()->readOnly(),
            'status'     => Shape::enum(PaymentStatus::class)->readOnly(),
            'amount'     => Shape::integer()->readOnly()->description('Amount in minor units'),
            'currency'   => Shape::string()->readOnly()->example('USD'),
            'created_at' => Shape::dateTime()->readOnly(),
            'settled_at' => Shape::dateTime()->nullable()->readOnly()
                                ->description('Null until the payment settles'),
        ]);
    }

    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'status'     => $this->status,
            'amount'     => $this->amount,
            'currency'   => $this->currency,
            'created_at' => $this->created_at->toIso8601String(),
            'settled_at' => $this->settled_at?->toIso8601String(),
        ];
    }
}
```

The `schema()` method must be `public static`. Luminous calls it statically during
spec generation.

See [The Shape Builder](shape-builder.md) for all available methods.

---

## Strategy 2: `#[ApiProperty]` on public properties

If you prefer annotation style or already have typed public properties on your
resources, annotate them directly.

```php
use Botnetdobbs\Luminous\Attributes\ApiProperty;
use Botnetdobbs\Luminous\Attributes\ApiItems;

class PaymentResource extends JsonResource
{
    #[ApiProperty('Payment UUID', format: 'uuid', readOnly: true)]
    public string $id;

    #[ApiProperty('Current status', readOnly: true)]
    public PaymentStatus $status;

    #[ApiProperty('Amount in minor units', readOnly: true, example: 10000)]
    public int $amount;

    #[ApiProperty('Settlement timestamp', format: 'date-time', nullable: true, readOnly: true)]
    public ?string $settled_at;

    // Scalar array: use #[ApiItems] to say what is inside the array
    #[ApiProperty('Applied tag names', readOnly: true)]
    #[ApiItems(type: 'string')]
    public array $tags;

    // Array of another resource: use #[ApiItems] with ref:
    #[ApiProperty('Order line items', readOnly: true)]
    #[ApiItems(ref: OrderItemResource::class)]
    public array $items;

    public function toArray(Request $request): array { /* ... */ }
}
```

### `#[ApiProperty]` arguments

| Argument | What it does |
|----------|-------------|
| `description` | Human-readable description of the field |
| `example` | Example value shown in Swagger UI |
| `format` | OpenAPI format (`uuid`, `date-time`, `email`, etc.) |
| `nullable` | Value can be `null` |
| `optional` | Key may be absent from the response entirely |
| `minimum` | Minimum value for numbers |
| `maximum` | Maximum value for numbers |
| `minLength` | Minimum character length for strings |
| `maxLength` | Maximum character length for strings |
| `enum` | Fixed set of allowed values |
| `readOnly` | Returned in responses, not accepted in requests |
| `writeOnly` | Accepted in requests, not returned in responses |
| `deprecated` | Field is deprecated but still present |
| `pattern` | Regex pattern the value must match |
| `ref` | Direct `$ref` to another schema by path |
| `itemsRef` | `$ref` for array items (alternative to `#[ApiItems]`) |
| `itemsType` | Type string for array items (alternative to `#[ApiItems]`) |

Both strategies produce identical OpenAPI output. Do not mix them on the same class.
If `#[ApiShape]` is present, Luminous uses the `schema()` method and ignores any
`#[ApiProperty]` annotations.

If a resource has neither `#[ApiShape]` nor any `#[ApiProperty]` annotations, Luminous
produces a bare `{}` object schema for it. The endpoint still appears in the docs but
the response body is undocumented. Add one of the two strategies to fix this.

---

## Nullable vs optional

These two concepts are easy to mix up:

```php
// In schema():
'settled_at' => Shape::dateTime()->nullable(), // key present, value can be null
'discount'   => Shape::integer()->optional(),  // key might not exist at all

// In #[ApiProperty]:
#[ApiProperty(nullable: true)]
public ?string $settled_at;   // key present, value can be null

#[ApiProperty(optional: true)]
public int $discount;         // key might not exist at all
```

- `nullable()` keeps the field in `required`. The key is always present. The value
  can be `null`.
- `optional()` removes the field from `required`. The key might not exist in the
  response.

---

## Enum fields

When a field's type is a PHP backed enum, Luminous reads all the cases and registers
the enum in `components/schemas`. Every reference to that enum anywhere in your spec
uses `$ref` pointing to the single definition. No duplication.

```php
// In schema():
'status' => Shape::enum(PaymentStatus::class)->readOnly(),

// In #[ApiProperty]:
#[ApiProperty(readOnly: true)]
public PaymentStatus $status;
```

If you use `PaymentStatus` in ten different resources, the enum is defined once and
referenced ten times.

---

## Relationships between resources

### Single related resource

```php
#[ApiShape]
class OrderResource extends JsonResource
{
    public static function schema(): Shape
    {
        return Shape::object([
            'id'       => Shape::uuid()->readOnly(),
            'total'    => Shape::integer()->readOnly(),

            // Always present
            'customer' => Shape::ref(CustomerResource::class),

            // Present but can be null (e.g. no payment yet)
            'payment'  => Shape::ref(PaymentResource::class)->nullable(),
        ]);
    }
}
```

`Shape::ref(CustomerResource::class)` tells Luminous this field is a
`CustomerResource`. Luminous extracts `CustomerResource`'s schema, registers it in
`components/schemas`, and the `customer` field in the spec becomes a `$ref` pointing
to it.

### Array of related resources

```php
return Shape::object([
    'id'    => Shape::uuid()->readOnly(),
    'items' => Shape::arrayOf(Shape::ref(OrderItemResource::class))
                   ->description('Line items in this order'),
]);
```

### Conditionally loaded relationships

When you use `whenLoaded()` in `toArray()`, document the relationship as `optional()`
so the spec says the key might not be there:

```php
return Shape::object([
    'id'     => Shape::uuid()->readOnly(),
    'name'   => Shape::string()->readOnly(),
    'orders' => Shape::arrayOf(Shape::ref(OrderResource::class))
                    ->optional()
                    ->description('Present only when ?include=orders is requested'),
]);
```

```php
public function toArray(Request $request): array
{
    return [
        'id'     => $this->id,
        'name'   => $this->name,
        'orders' => OrderResource::collection($this->whenLoaded('orders')),
    ];
}
```

### Deep chains

Luminous handles arbitrary nesting depth. If `OrderResource` references
`OrderItemResource` which references `ProductResource` which references
`CategoryResource`, Luminous walks the entire chain and registers every schema.

```
OrderResource
  └── items: OrderItemResource[]
        └── product: ProductResource
              └── category: CategoryResource
```

All four schemas end up in `components/schemas`. Every cross-reference uses `$ref`.
You do not need to register any of them manually.

### Circular references

If two resources reference each other, Luminous detects the cycle and returns the
existing `$ref` without looping. The spec is always valid.

---

## Inline objects

Not every nested object needs its own resource class. For small objects that only
appear in one place, define them inline in the schema:

```php
return Shape::object([
    'id'     => Shape::uuid()->readOnly(),
    'amount' => Shape::integer()->readOnly(),

    'fee_breakdown' => Shape::object([
        'processing_fee' => Shape::integer()->description('Processor fee in minor units'),
        'platform_fee'   => Shape::integer()->description('Platform fee in minor units'),
        'total'          => Shape::integer()->description('Sum of all fees'),
    ])->description('Breakdown of fees on this payment'),
]);
```

The inline object is included directly in the parent schema. It is not registered as
a separate component.

---

## Using a plain class or DTO instead of a JsonResource

`#[ApiResponse]` accepts any class name, not just `JsonResource` subclasses. If the
class has `#[ApiShape]` with a `schema()` method, or has `#[ApiProperty]` on its
public properties, Luminous extracts the schema from it the same way.

```php
use Botnetdobbs\Luminous\Attributes\ApiShape;
use Botnetdobbs\Luminous\Support\Shape;

#[ApiShape]
class PaymentSummaryDto
{
    public static function schema(): Shape
    {
        return Shape::object([
            'id'     => Shape::uuid(),
            'amount' => Shape::integer()->description('Amount in minor units'),
            'status' => Shape::enum(PaymentStatus::class),
        ]);
    }
}
```

```php
#[ApiResponse(200, PaymentSummaryDto::class, 'Summary retrieved')]
public function summary(string $id): JsonResponse {}
```

---

[← Documenting Form Requests](form-requests.md) &nbsp;&nbsp; [The Shape Builder →](shape-builder.md)
