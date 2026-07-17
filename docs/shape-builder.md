# The Shape Builder

`Shape` is the fluent builder you use in `schema()` methods and `hints()`. Every
method returns a new `Shape` instance, so chaining is always safe and each call is
independent of the others.

---

## Primitive types

```php
Shape::string()
Shape::integer()
Shape::number()   // floating point numbers
Shape::boolean()
Shape::array()    // untyped array; use arrayOf() for typed arrays
```

---

## Format shortcuts

These are strings under the hood, with a format already set for you:

```php
Shape::uuid()        // string, format: uuid
Shape::email()       // string, format: email
Shape::url()         // string, format: uri
Shape::dateTime()    // string, format: date-time
Shape::date()        // string, format: date
Shape::time()        // string, format: time
Shape::password()    // string, format: password
Shape::binary()      // string, format: binary (for file upload fields)
```

---

## Complex types

### Typed array

```php
// Array of scalars
Shape::arrayOf(Shape::uuid())
// Produces: { type: array, items: { type: string, format: uuid } }

Shape::arrayOf(Shape::string())
// Produces: { type: array, items: { type: string } }
```

### Array of another resource

```php
Shape::arrayOf(Shape::ref(OrderItemResource::class))
// Produces: { type: array, items: { $ref: '#/components/schemas/OrderItemResource' } }
```

### Inline object

```php
Shape::object([
    'street'  => Shape::string()->maxLength(255),
    'city'    => Shape::string()->maxLength(100),
    'country' => Shape::string()->minLength(2)->maxLength(2),
])
```

### Reference to another resource or schema

```php
Shape::ref(CustomerResource::class)
// Produces: { $ref: '#/components/schemas/Customer' }

Shape::ref(CustomerResource::class)->nullable()
// Produces: { oneOf: [ { $ref: '...' }, { type: null } ] }
```

### PHP backed enum

```php
Shape::enum(PaymentStatus::class)
// Registers PaymentStatus in components/schemas and produces a $ref to it.
// All enum case values are included automatically.
```

### Polymorphic composition

Use these when a field or response can be one of several different shapes.

```php
// oneOf: the value must match exactly one of the listed schemas
Shape::oneOf([
    Shape::ref(CardPaymentResource::class),
    Shape::ref(BankTransferResource::class),
])

// anyOf: the value must match at least one of the listed schemas
Shape::anyOf([
    Shape::ref(PendingResource::class),
    Shape::ref(ProcessingResource::class),
])

// allOf: the value must match all of the listed schemas (used for schema extension)
Shape::allOf([
    Shape::ref(BasePaymentResource::class),
    Shape::object(['fee' => Shape::integer()]),
])
```

`oneOf` is the most common choice for polymorphic responses where each case is mutually
exclusive. Use `anyOf` when multiple schemas can apply at once. Use `allOf` to combine
a base schema with additional fields.

---

## Modifiers

Every modifier returns a new `Shape` instance. Chain them in any order.

### Common modifiers

```php
Shape::string()
    ->description('Human-readable label for this field')
    ->example('My payment')
    ->nullable()      // value can be null; keeps field in required
    ->optional()      // field may be absent from the response entirely
    ->readOnly()      // returned in responses, not accepted in requests
    ->writeOnly()     // accepted in requests, not returned in responses
    ->deprecated()    // field is still present but marked as deprecated
```

### String constraints

```php
Shape::string()
    ->minLength(2)
    ->maxLength(255)
    ->pattern('^[A-Z]{2}$')
    ->values(['active', 'inactive'])   // fixed set of allowed string or integer values
```

### Number and integer constraints

```php
Shape::integer()
    ->min(1)        // sets minimum
    ->max(1000000)  // sets maximum
    ->example(10000)

Shape::number()
    ->min(0.01)
    ->max(9999.99)
```

### Array constraints

```php
Shape::arrayOf(Shape::ref(ItemResource::class))
    ->minItems(1)
    ->maxItems(100)
    ->description('At least one item is required')
```

---

## How `min()` and `max()` work

`min()` and `max()` look at the type and apply the correct OpenAPI constraint
automatically:

| Type | `min()` produces | `max()` produces |
|------|-----------------|-----------------|
| `integer` or `number` | `minimum` | `maximum` |
| `string` | `minLength` | `maxLength` |
| `array` | `minItems` | `maxItems` |

So `Shape::integer()->min(1)` produces `minimum: 1` and
`Shape::string()->min(2)` produces `minLength: 2`. You never have to remember which
constraint name to use.

---

## Nullable vs optional

These two modifiers do different things:

```php
Shape::dateTime()->nullable()
// The key is always present. The value can be null.
// Produces: type: ["string", "null"], format: date-time

Shape::integer()->optional()
// The key might not exist in the response at all.
// Removes the field from the required array.
```

You can combine them:

```php
Shape::string()->nullable()->optional()
// The key might not exist. If it does exist, its value can be null.
```

---

## Practical examples

### A payment response schema

```php
public static function schema(): Shape
{
    return Shape::object([
        'id'          => Shape::uuid()->readOnly(),
        'status'      => Shape::enum(PaymentStatus::class)->readOnly(),
        'amount'      => Shape::integer()->readOnly()
                             ->description('Amount in minor units. 10000 = $100.00')
                             ->example(10000),
        'currency'    => Shape::string()->readOnly()->example('USD'),
        'description' => Shape::string()->readOnly()->nullable()
                             ->description('Merchant-provided description'),
        'created_at'  => Shape::dateTime()->readOnly(),
        'settled_at'  => Shape::dateTime()->readOnly()->nullable()
                             ->description('Null until the payment settles'),
        'metadata'    => Shape::object([])->readOnly()->optional()
                             ->description('Arbitrary key-value pairs'),
    ]);
}
```

### A nested address object

```php
'billing_address' => Shape::object([
    'street'   => Shape::string()->maxLength(255),
    'city'     => Shape::string()->maxLength(100),
    'country'  => Shape::string()->minLength(2)->maxLength(2)->example('US'),
    'postcode' => Shape::string()->maxLength(20)->optional(),
]),
```

### A hints() method using Shape

```php
public function hints(): array
{
    return [
        'amount'   => Shape::integer()
                          ->description('Amount in minor units. 10000 = $100.00')
                          ->example(10000),
        'currency' => Shape::string()
                          ->description('ISO 4217 currency code')
                          ->example('USD'),
    ];
}
```

