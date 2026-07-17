# Luminous

[![build](https://github.com/botnet-dobbs/laravel-luminous/actions/workflows/main.yml/badge.svg)](https://github.com/botnet-dobbs/laravel-luminous/actions/workflows/main.yml) ![Packagist Downloads](https://img.shields.io/packagist/dt/botnetdobbs/laravel-luminous)

Generate OpenAPI 3.2.0 docs from PHP 8 Attributes on your Laravel controllers.

No YAML files to maintain. No docblocks to parse. Put a few attributes on your
controllers, let your FormRequest `rules()` define the request body, document your
API Resources with a single static `schema()` method, and Luminous builds the full spec
automatically.

Request bodies follow your FormRequest `rules()`, so they stay up to date with validation.
Response docs use a separate `schema()` method (or `#[ApiProperty]`). Keep `schema()` next to
`toArray()` so the docs still match what you return.

**Docs:** [botnet-dobbs.github.io/laravel-luminous](https://botnet-dobbs.github.io/laravel-luminous/)

## Requirements

- PHP 8.2+

| Laravel Version |
|-----------------|
| Laravel 11.x    |
| Laravel 12.x    |
| Laravel 13.x    |

---

## Installation

```bash
composer require botnetdobbs/laravel-luminous
```

Publish the config file:

```bash
php artisan vendor:publish --tag=luminous-config
```

Your API docs are live at `/docs`.

### Local vs production

By default, docs work right away on your machine: no login, UI turned on. That is on purpose
so you can install the package and open `/docs` immediately.

On a production server, protect the docs or turn them off:

```bash
LUMINOUS_MIDDLEWARE=auth        # any middleware; separate several with | (not commas)
# or
LUMINOUS_ENABLED=false
```

Also build the cache when you deploy and limit which routes get documented with
`include_routes`. Details:

- [Deployment](https://botnet-dobbs.github.io/laravel-luminous/deployment): middleware, cache, generate, export
- [Security](https://botnet-dobbs.github.io/laravel-luminous/security): documenting auth schemes and scopes in the spec

---

## Quick Look

### The controller

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

### The FormRequest

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

### The API Resource

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

That is the whole picture. The individual docs below go into every detail.

---

## Documentation

Full docs: [botnet-dobbs.github.io/laravel-luminous](https://botnet-dobbs.github.io/laravel-luminous/)

- [Configuration](https://botnet-dobbs.github.io/laravel-luminous/configuration)
- [Documenting Controllers](https://botnet-dobbs.github.io/laravel-luminous/controllers)
- [Documenting Form Requests](https://botnet-dobbs.github.io/laravel-luminous/form-requests)
- [Documenting API Resources](https://botnet-dobbs.github.io/laravel-luminous/resources)
- [The Shape Builder](https://botnet-dobbs.github.io/laravel-luminous/shape-builder)
- [Security](https://botnet-dobbs.github.io/laravel-luminous/security)
- [CLI Commands and Deployment](https://botnet-dobbs.github.io/laravel-luminous/deployment)

---

## Attribute Reference

| Attribute | Where it goes | What it does |
|-----------|---------------|-------------|
| `#[ApiOperation]` | Method | Summary, description, optional operationId, and optional `externalDocsUrl` link |
| `#[ApiTag]` | Class or Method | Group endpoints in the sidebar. Supports `summary`, `parent`, `kind` for hierarchical grouping, and `externalDocsUrl` |
| `#[ApiBody]` | Method | Override the auto-detected request class or add a description |
| `#[ApiResponse]` | Method | Document a response status code (repeatable) |
| `#[ApiResponseHeader]` | Method | Document a response header on a specific status code (repeatable) |
| `#[ApiParam]` | Method | Document a path parameter, including route model bound params (repeatable). Supports `deprecated`, `style` (`simple`, `label`, `matrix`), and `explode` |
| `#[ApiQuery]` | Method | Document a query string parameter (repeatable). Supports `deprecated`, `location` for `in: querystring`, `style` (`form`, `spaceDelimited`, `pipeDelimited`, `deepObject`), and `explode` |
| `#[ApiStream]` | Method | Document a streaming endpoint (SSE, JSONL). Emits `itemSchema` instead of `schema` |
| `#[ApiHeader]` | Method | Document a request header (repeatable). Supports `style` (`simple`) and `explode` |
| `#[ApiSecurity]` | Class or Method | Declare a required security scheme with optional scopes (repeatable) |
| `#[ApiNoSecurity]` | Method | Mark an endpoint as requiring no authentication |
| `#[ApiDeprecated]` | Method | Mark an endpoint as deprecated with a reason and replacement |
| `#[ApiIgnore]` | Class or Method | Exclude from documentation entirely |
| `#[ApiExample]` | Method | Named request or response example (repeatable). Supports `externalValue`, `dataValue`, and `serializedValue` for non-JSON targets |
| `#[ApiComposedOf]` | Method | oneOf / anyOf / allOf for polymorphic responses (repeatable) |
| `#[ApiShape]` | Resource, FormRequest, or DTO class | Marks a class as using the static `schema()` method |
| `#[ApiProperty]` | Property | Documents a single property on a resource or DTO |
| `#[ApiItems]` | Property | Documents the item type inside an array property |

---

## Common Questions

**Do I need to annotate every controller method?**

No. Every route appears in the spec. Unannotated routes get an auto-generated summary
from the controller and method name. Annotations make things accurate and readable
but are never required.

**What happens if I forget `#[ApiResponse]`?**

Luminous adds a default `500 Internal Server Error` response. Other responses are
assumed to return a generic object. Add `#[ApiResponse]` to make the response schemas
accurate.

**My FormRequest has constructor dependencies. Will `rules()` work?**

For most cases yes. Luminous instantiates the request without calling the constructor.
If your `rules()` calls `$this->user()` or reads injected services, those calls fail
silently and Luminous returns a bare schema. Restructure those rules to avoid
request-time dependencies, or use `#[ApiShape]` to provide the schema explicitly.

**Can I use a plain PHP class or DTO with `#[ApiResponse]`?**

Yes. `#[ApiResponse]` accepts any class name. If the class has `#[ApiShape]` with a
`schema()` method, or has `#[ApiProperty]` on its public properties, Luminous
extracts the schema from it just like it would from a `JsonResource`.

**Can I check the exported file, or build an SDK from it?**

Yes. Export with `luminous:export`, then use normal OpenAPI tools. Redocly or Spectral
can check the file. openapi-generator or Fern can build a client library. See
[Using the exported spec](https://botnet-dobbs.github.io/laravel-luminous/deployment#using-the-exported-spec).

---

## Credits

- [Lazarus Odhiambo](https://github.com/botnetdobbs)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
