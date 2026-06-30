# Documenting Form Requests

---

## How it works

Luminous finds your FormRequest from the method type hint and reads its `rules()`
automatically. You do not have to write your validation rules twice.

```php
class CreatePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount'   => ['required', 'integer', 'min:1'],
            'currency' => ['required', Rule::in(['USD', 'EUR', 'KES'])],
        ];
    }
}
```

Luminous reads this and produces:

```json
{
  "type": "object",
  "properties": {
    "amount":   { "type": "integer", "minimum": 1 },
    "currency": { "type": "string",  "enum": ["USD", "EUR", "KES"] }
  },
  "required": ["amount", "currency"]
}
```

That is all you need to do.

---

## What Luminous extracts automatically

### Types

| Rule | Schema type |
|------|-------------|
| `string` | `string` |
| `integer` / `int` | `integer` |
| `numeric` / `decimal` | `number` |
| `boolean` / `bool` | `boolean` |
| `array` | `array` |
| `file` / `image` | `string`, format: `binary` |

### Formats

| Rule | Format |
|------|--------|
| `email` | `email` |
| `uuid` | `uuid` |
| `url` | `uri` |
| `date` | `date` |
| `ip` / `ipv4` | `ipv4` |
| `ipv6` | `ipv6` |

### Constraints

| Rule | What it produces |
|------|-----------------|
| `min:N` on integer or number | `minimum: N` |
| `max:N` on integer or number | `maximum: N` |
| `min:N` on string | `minLength: N` |
| `max:N` on string | `maxLength: N` |
| `min:N` on array | `minItems: N` |
| `max:N` on array | `maxItems: N` |
| `between:M,N` | `minimum`/`maximum` or `minLength`/`maxLength` depending on type |
| `size:N` on string | `minLength: N, maxLength: N` |
| `digits:N` | `minLength: N, maxLength: N, pattern: ^\d{N}$` |
| `in:a,b,c` | `enum: ["a","b","c"]` |
| `Rule::in([...])` | `enum: [...]` |
| `Rule::enum(MyEnum::class)` | `enum: [all case values]` |
| `regex:/pattern/` | `pattern` (delimiters stripped) |
| `nullable` | type becomes `["<field_type>", "null"]` (e.g. `["integer", "null"]` for an integer field) |
| `sometimes` | field is removed from `required` |
| `required_if:field,value` | field is optional, description note added |

---

Rules that Luminous does not recognise (like `Rule::exists()`, `Rule::unique()`,
`Rule::prohibits()`, and custom rule objects) are silently ignored. They have no
effect on the generated schema.

---

## Required vs optional fields

A field is `required` in the schema when its rules include `'required'` and do not
include `'sometimes'`.

```php
'amount'      => ['required', 'integer'],              // required
'coupon_code' => ['sometimes', 'nullable', 'string'],  // NOT required (key can be absent)
'notes'       => ['nullable', 'string'],               // required (key must be present, value can be null)
```

The difference between `nullable` and `sometimes` trips up a lot of developers:

- `nullable` means the field must be in the request body, but its value can be `null`
- `sometimes` means the field can be left out of the request body entirely

Luminous reflects this distinction accurately in the spec.

---

## Adding descriptions and examples with `hints()`

`rules()` cannot express what a field means or show a good example value. That is
what `hints()` is for. Most FormRequests do not need it. Add it only when a developer
reading the spec would not understand a field without extra context.

```php
use Botnetdobbs\Luminous\Support\Shape;

class CreatePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount'      => ['required', 'integer', 'min:1'],
            'currency'    => ['required', Rule::in(['USD', 'EUR', 'KES'])],
            'description' => ['required', 'string', 'max:500'],
            'metadata'    => ['sometimes', 'array'],
        ];
    }

    public function hints(): array
    {
        return [
            'amount'   => Shape::integer()->description('Amount in minor units. 10000 = $100.00')->example(10000),
            'currency' => Shape::string()->description('ISO 4217 currency code'),
            'metadata' => Shape::object([])->description('Arbitrary key-value pairs attached to this payment'),
        ];
    }
}
```

`hints()` is additive only. It can add a `description` and an `example`. It cannot
change a type, remove a required field, or override a constraint. `rules()` owns the
schema contract.

> Skip `hints()` when field names are obvious and constraints are self-explanatory.
> Add it when a new developer would genuinely need the extra context.

---

## Nested objects

Dot-notation rules become nested object schemas automatically. No extra work.

```php
public function rules(): array
{
    return [
        'billing_address'          => ['required', 'array'],
        'billing_address.street'   => ['required', 'string', 'max:255'],
        'billing_address.city'     => ['required', 'string', 'max:100'],
        'billing_address.country'  => ['required', 'string', 'size:2'],
        'billing_address.postcode' => ['sometimes', 'string', 'max:20'],
    ];
}
```

Luminous produces:

```json
{
  "billing_address": {
    "type": "object",
    "properties": {
      "street":   { "type": "string", "maxLength": 255 },
      "city":     { "type": "string", "maxLength": 100 },
      "country":  { "type": "string", "minLength": 2, "maxLength": 2 },
      "postcode": { "type": "string", "maxLength": 20 }
    },
    "required": ["street", "city", "country"]
  }
}
```

`postcode` is not required because it uses `sometimes`.

---

## Arrays of objects

Wildcard `.*` notation becomes a typed array of objects. No extra work.

```php
public function rules(): array
{
    return [
        'items'              => ['required', 'array', 'min:1'],
        'items.*.product_id' => ['required', 'uuid'],
        'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:999'],
        'items.*.note'       => ['sometimes', 'nullable', 'string', 'max:200'],
    ];
}
```

Luminous produces:

```json
{
  "items": {
    "type": "array",
    "minItems": 1,
    "items": {
      "type": "object",
      "properties": {
        "product_id": { "type": "string", "format": "uuid" },
        "quantity":   { "type": "integer", "minimum": 1, "maximum": 999 },
        "note":       { "type": ["string", "null"], "maxLength": 200 }
      },
      "required": ["product_id", "quantity"]
    }
  }
}
```

---

## Arrays of scalar values

```php
'tag_ids'   => ['required', 'array'],
'tag_ids.*' => ['required', 'uuid'],
```

Produces:

```json
{
  "tag_ids": {
    "type": "array",
    "items": { "type": "string", "format": "uuid" }
  }
}
```

---

## Password confirmation

When a field has the `confirmed` rule, Luminous automatically adds the
`_confirmation` companion field to the schema so consumers know they need to send
both.

```php
'password' => ['required', 'string', 'min:8', 'confirmed'],
```

Produces both fields:

```json
{
  "password": {
    "type": "string",
    "format": "password",
    "minLength": 8,
    "writeOnly": true
  },
  "password_confirmation": {
    "type": "string",
    "minLength": 8,
    "writeOnly": true,
    "description": "Must match the password field"
  }
}
```

Both are `writeOnly` because they go into requests but never appear in responses.

---

## File uploads

Any field with a `file`, `image`, `mimes`, or `mimetypes` rule causes Luminous to
set the request body media type to `multipart/form-data` automatically.

```php
public function rules(): array
{
    return [
        'avatar'     => ['required', 'image', 'mimes:jpg,png', 'max:2048'],
        'first_name' => ['required', 'string'],
    ];
}
```

The request body is documented as `multipart/form-data`. The `avatar` field gets
`format: binary`. You do not need to tell Luminous this is a file upload.

---

## Conditional required fields

`required_if`, `required_unless`, and similar rules cannot be expressed as static
OpenAPI constraints. Luminous marks the field as optional and adds a description
note explaining the condition.

```php
'company_name' => ['required_if:account_type,business', 'string', 'max:255'],
```

Produces:

```json
{
  "company_name": {
    "type": "string",
    "maxLength": 255,
    "description": "Required when account_type is 'business'."
  }
}
```

If you add a `hints()` entry for this field with your own description, your
description replaces the auto-generated one.

---

## Using `#[ApiShape]` to bypass `rules()` entirely

If your `rules()` depends on the authenticated user or injected services, Luminous
cannot call it safely. In that case, put `#[ApiShape]` on the FormRequest class and
write a `schema()` method that returns the schema directly. Luminous uses that instead
of touching `rules()`.

```php
use Botnetdobbs\Luminous\Attributes\ApiShape;
use Botnetdobbs\Luminous\Support\Shape;

#[ApiShape]
class UpdateProfileRequest extends FormRequest
{
    public static function schema(): Shape
    {
        return Shape::object([
            'name'  => Shape::string()->maxLength(255),
            'email' => Shape::email(),
            'bio'   => Shape::string()->maxLength(1000)->optional(),
        ]);
    }

    public function rules(): array
    {
        // This may call $this->user(), which Luminous cannot do
        return [
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->user()->id)],
            'name'  => ['required', 'string', 'max:255'],
            'bio'   => ['sometimes', 'string', 'max:1000'],
        ];
    }
}
```

See [The Shape Builder](shape-builder.md) for all available methods.
