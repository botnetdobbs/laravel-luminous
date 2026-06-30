# Documenting Controllers

---

## Basic operation

Every annotation in Luminous is optional. Routes appear in the docs whether you add
attributes or not. Unannotated routes get an auto-generated summary from the
controller and method name. Attributes just make things accurate and readable.

That said, `#[ApiOperation]` is the first thing you will add to a method. It sets the
summary that appears in the Swagger UI sidebar.

```php
use Botnetdobbs\Luminous\Attributes\ApiOperation;

#[ApiOperation('List all payments')]
public function index(Request $request): JsonResponse {}

#[ApiOperation('Get a payment')]
public function show(string $id): JsonResponse {}

#[ApiOperation('Create a payment', 'Initiates a payment. Requires an Idempotency-Key header.')]
public function store(CreatePaymentRequest $request): JsonResponse {}
```

The second argument is a longer description. Use it when the summary alone does not
give the reader enough context.

The third argument is an `operationId`. Luminous generates one automatically, but you
can set your own if you need a specific identifier for SDK generation or API clients:

```php
#[ApiOperation('Create a payment', operationId: 'payments.create')]
public function store(CreatePaymentRequest $request): JsonResponse {}
```

---

## Grouping with tags

Tags group related endpoints together in the Swagger UI sidebar. Add `#[ApiTag]` to
a controller class and every method in that controller gets that tag automatically.

```php
use Botnetdobbs\Luminous\Attributes\ApiTag;

#[ApiTag('Payments', 'Payment creation, retrieval, and lifecycle management')]
class PaymentController extends Controller
{
    #[ApiOperation('List payments')]
    public function index() {}

    #[ApiOperation('Create a payment')]
    public function store() {}
}
```

To put a single method under a different tag, add `#[ApiTag]` directly on the method.
It overrides the class-level tag for that method only.

```php
#[ApiTag('Payments')]
class PaymentController extends Controller
{
    #[ApiTag('Webhooks')]
    #[ApiOperation('Handle payment webhook')]
    public function webhook() {}

    // Everything else gets the "Payments" tag
    public function show() {}
}
```

---

## Documenting responses

`#[ApiResponse]` is repeatable. Add one for each HTTP status code your endpoint can
return.

```php
use Botnetdobbs\Luminous\Attributes\ApiResponse;

#[ApiResponse(200, PaymentResource::class, 'Payment retrieved')]
#[ApiResponse(404, ErrorResource::class, 'Payment not found')]
public function show(string $id): JsonResponse {}
```

The second argument is the class that describes the response body. Luminous reads
that class and generates the schema automatically. See
[Documenting API Resources](resources.md) for how to set that up.

For a response with no body:

```php
#[ApiResponse(204, description: 'Payment cancelled')]
public function cancel(string $id): JsonResponse {}
```

For a response that returns a collection:

```php
// A plain array of PaymentResource objects
#[ApiResponse(200, PaymentResource::class, 'Payments list', collection: true)]
public function index(): JsonResponse {}

// A paginated collection with cursor, has_more, and total in the envelope
#[ApiResponse(200, PaymentResource::class, 'Payments list', paginated: true)]
public function index(): JsonResponse {}
```

`collection: true` documents the response as an array of the resource.
`paginated: true` does the same but also adds the pagination envelope
(`cursor`, `has_more`, `total`) to the documented response.

---

## Documenting path parameters

Luminous detects `{id}` style path parameters from your route automatically. You do
not need to declare them unless you want to add a description, type, or example.

```php
// Luminous already knows about {id}. No attribute needed.
Route::get('/payments/{id}', [PaymentController::class, 'show']);

// Add #[ApiParam] only to enrich the auto-detected parameter
use Botnetdobbs\Luminous\Attributes\ApiParam;

#[ApiParam('id', 'Payment UUID', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
public function show(string $id): JsonResponse {}
```

### Supported types

`type` accepts any OpenAPI primitive: `string` (default), `integer`, `number`, or
`boolean`. Use `format` to narrow the type further.

```php
// Integer primary key
Route::get('/posts/{post}', [PostController::class, 'show']);

#[ApiParam('post', 'Post ID', type: 'integer', example: 42)]
public function show(int $post): JsonResponse {}

// String with a specific format
#[ApiParam('id', 'Payment UUID', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
public function show(string $id): JsonResponse {}

// Enum: only the listed values are valid
#[ApiParam('status', 'Payment status filter', type: 'string', enum: ['pending', 'succeeded', 'failed'])]
public function byStatus(string $status): JsonResponse {}
```

### Route model binding

When your method uses route model binding, the URL still has a `{user}` or
`{payment}` placeholder, but the method receives the full model instead of a string.
Use `#[ApiParam]` to tell Luminous what that parameter actually represents.

```php
Route::get('/users/{user}', [UserController::class, 'show']);

#[ApiParam('user', 'The user ID', type: 'integer', example: 42)]
public function show(User $user): JsonResponse {}
```

If your model uses a UUID as its primary key instead of an auto-increment integer:

```php
#[ApiParam('user', 'User UUID', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
public function show(User $user): JsonResponse {}
```

This also works with custom route keys:

```php
// Route bound by UUID instead of the default primary key
Route::get('/orders/{order:uuid}', [OrderController::class, 'show']);

#[ApiParam('order', 'Order UUID', type: 'string', format: 'uuid', example: '6ba7b810-9dad-11d1-80b4-00c04fd430c8')]
public function show(Order $order): JsonResponse {}
```

The rule is simple: if the URL has `{something}`, you document `something`. What the
PHP method receives (a string, an integer, a model, or anything else) does not affect
the path parameter definition.

---

## Documenting query parameters

```php
use Botnetdobbs\Luminous\Attributes\ApiQuery;

#[ApiQuery('status', 'Filter by payment status', enum: ['pending', 'succeeded', 'failed'])]
#[ApiQuery('limit', 'Results per page', type: 'integer', example: 20)]
#[ApiQuery('cursor', 'Pagination cursor from the previous response')]
#[ApiQuery('include', 'Comma-separated relationships to sideload', example: 'customer,items')]
public function index(Request $request): JsonResponse {}
```

---

## Documenting request headers

```php
use Botnetdobbs\Luminous\Attributes\ApiHeader;

#[ApiHeader('Idempotency-Key', 'UUID v4. Same key returns the cached response without re-executing.', required: true, format: 'uuid')]
#[ApiHeader('X-Tenant-ID', 'Your organisation identifier')]
public function store(CreatePaymentRequest $request): JsonResponse {}
```

---

## Providing request and response examples

Give Swagger UI realistic examples so developers can try the API right away without
filling in every field manually.

**Request body example:**

```php
use Botnetdobbs\Luminous\Attributes\ApiExample;

#[ApiExample('usd-payment', 'Standard USD payment', [
    'amount'                 => 10000,
    'currency'               => 'USD',
    'source_account_id'      => '550e8400-e29b-41d4-a716-446655440000',
    'destination_account_id' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
    'description'            => 'Order #ORD-2024-1234',
])]
public function store(CreatePaymentRequest $request): JsonResponse {}
```

**Response example** (use `type: 'response'` and `status:` to point to a specific
`#[ApiResponse]`):

```php
#[ApiResponse(201, PaymentResource::class, 'Payment created')]
#[ApiExample('created-payment', 'Successful payment', [
    'id'         => '550e8400-e29b-41d4-a716-446655440000',
    'status'     => 'succeeded',
    'amount'     => 10000,
    'currency'   => 'USD',
    'created_at' => '2024-01-15T09:30:00Z',
    'settled_at' => '2024-01-15T09:30:05Z',
], type: 'response', status: 201)]
public function store(CreatePaymentRequest $request): JsonResponse {}
```

The `status` argument tells Luminous which `#[ApiResponse]` this example belongs to.
If you have multiple response status codes, add a separate `#[ApiExample]` for each
one you want to illustrate.

---

## Specifying the request body explicitly

Luminous detects the request body from the FormRequest type hint automatically. You
do not need `#[ApiBody]` in the common case. Reach for it when you want to:

- Add a description to the entire request body
- Force a specific media type instead of the auto-detected one
- Document a request that does not use a FormRequest type hint

```php
use Botnetdobbs\Luminous\Attributes\ApiBody;

// Adding a description
#[ApiBody(CreatePaymentRequest::class, description: 'Payment initiation payload')]
public function store(CreatePaymentRequest $request): JsonResponse {}

// Forcing a specific media type
#[ApiBody(UploadRequest::class, mediaType: 'multipart/form-data')]
public function upload(UploadRequest $request): JsonResponse {}
```

When you use `#[ApiBody]` without setting a `mediaType`, Luminous still auto-detects
the correct media type from the request's `rules()`. So a `FileUploadRequest` that
has `file` rules produces `multipart/form-data` even when you are using the explicit
attribute.

---

## Marking an endpoint deprecated

```php
use Botnetdobbs\Luminous\Attributes\ApiDeprecated;

#[ApiDeprecated('Use POST /v2/payments instead', replacement: 'POST /v2/payments')]
#[ApiOperation('Create payment (legacy)')]
public function store(Request $request): JsonResponse {}
```

The endpoint still appears in the docs but is visually crossed out. The description
automatically includes the reason and the replacement path.

---

## Hiding an endpoint

Some routes should not appear in the docs at all: internal health checks, webhook
receivers, anything only your infrastructure calls.

```php
use Botnetdobbs\Luminous\Attributes\ApiIgnore;

#[ApiIgnore]
public function internalHealthProbe(): JsonResponse {}
```

Hide an entire controller the same way:

```php
#[ApiIgnore]
class InternalController extends Controller {}
```

---

## Polymorphic responses

When a single endpoint can return different shapes depending on the result, use
`#[ApiComposedOf]`. The first argument is the composition type:

- `'oneOf'` — the response matches exactly one of the listed schemas (mutually exclusive)
- `'anyOf'` — the response matches at least one of the listed schemas
- `'allOf'` — the response matches all of the listed schemas (used for schema extension)

```php
use Botnetdobbs\Luminous\Attributes\ApiComposedOf;

#[ApiResponse(200, description: 'Payment details')]
#[ApiComposedOf('oneOf', refs: [CardPaymentResource::class, BankTransferResource::class])]
public function show(string $id): JsonResponse {}
```

`oneOf` is the right choice when each possible response shape is mutually exclusive,
for example when a payment is either a card payment or a bank transfer but never both.

When you have multiple response status codes and want to compose different schemas for
different statuses, use the `forStatus` argument to point each `#[ApiComposedOf]` at
the right `#[ApiResponse]`:

```php
#[ApiResponse(200, description: 'Transfer details')]
#[ApiResponse(202, description: 'Transfer accepted but not yet settled')]
#[ApiComposedOf('oneOf', refs: [CardPaymentResource::class, BankTransferResource::class], forStatus: 200)]
#[ApiComposedOf('anyOf', refs: [PendingResource::class, ProcessingResource::class], forStatus: 202)]
public function show(string $id): JsonResponse {}
```
