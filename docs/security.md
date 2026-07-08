# Security

---

## Defining security schemes

Security schemes are defined once in `config/luminous.php`. You can define as many
as you need. The key you give each scheme is the name you use in `#[ApiSecurity]`
attributes on your controllers.

```php
'security_schemes' => [
    // HTTP Bearer token (e.g. JWT or Laravel Sanctum)
    'bearerAuth' => [
        'type'         => 'http',
        'scheme'       => 'bearer',
        'bearerFormat' => 'JWT',
    ],

    // API key passed in a header
    'apiKey' => [
        'type' => 'apiKey',
        'in'   => 'header',
        'name' => 'X-API-Key',
    ],

    // OAuth 2.0 client credentials
    'oauth2' => [
        'type' => 'oauth2',
        'flows' => [
            'clientCredentials' => [
                'tokenUrl' => 'https://auth.example.com/oauth/token',
                'scopes'   => [
                    'payments:read'  => 'Read payment data',
                    'payments:write' => 'Create and modify payments',
                    'admin'          => 'Full administrative access',
                ],
            ],
        ],
    ],
],
```

---

## Applying security to controllers

Add `#[ApiSecurity]` to a controller class and every method in that controller
inherits it automatically.

```php
use Botnetdobbs\Luminous\Attributes\ApiSecurity;

#[ApiSecurity('bearerAuth')]
class PaymentController extends Controller
{
    public function index() {}   // requires bearerAuth
    public function store() {}   // requires bearerAuth
    public function show() {}    // requires bearerAuth
}
```

### OAuth scopes

When using OAuth 2.0, pass the required scopes as the second argument:

```php
#[ApiSecurity('oauth2', ['payments:read'])]
public function index() {}

#[ApiSecurity('oauth2', ['payments:read', 'payments:write'])]
public function store() {}
```

An empty scopes array means any valid token works, regardless of scope:

```php
#[ApiSecurity('bearerAuth', [])]
public function show() {}
```

---

## Overriding security on a single method

Add `#[ApiSecurity]` directly on a method to override what the class declares:

```php
#[ApiSecurity('bearerAuth')]
class PaymentController extends Controller
{
    // This method needs a different scheme
    #[ApiSecurity('apiKey')]
    public function webhook() {}

    // Everything else still uses bearerAuth
    public function show() {}
}
```

---

## Public endpoints on a secured controller

Use `#[ApiNoSecurity]` to mark a single method as requiring no authentication, even
when the controller has `#[ApiSecurity]`:

```php
use Botnetdobbs\Luminous\Attributes\ApiNoSecurity;

#[ApiSecurity('bearerAuth')]
class PaymentController extends Controller
{
    // Anyone can call this
    #[ApiNoSecurity]
    public function publicStatus(string $id): JsonResponse {}

    // All other methods still require bearerAuth
    public function show(string $id): JsonResponse {}
    public function store(CreatePaymentRequest $request): JsonResponse {}
}
```

---

## Multiple security schemes on one endpoint

Stack `#[ApiSecurity]` attributes to require multiple schemes:

```php
#[ApiSecurity('bearerAuth')]
#[ApiSecurity('apiKey')]
public function store(CreatePaymentRequest $request): JsonResponse {}
```

In OpenAPI, multiple security entries in the same operation mean ALL must be
satisfied at the same time (AND logic). If you want any one of them to satisfy the
requirement (OR logic), use `default_security` in the config with an array of
alternatives instead.

---

## Default security for all routes

To apply a security scheme to every route without putting `#[ApiSecurity]` on every
controller, set `default_security` in `config/luminous.php`:

```php
'default_security' => [
    ['bearerAuth' => []],
],
```

Individual controllers and methods can override or remove this default:

```php
// Override: use a different scheme for this controller
#[ApiSecurity('apiKey')]
class WebhookController extends Controller {}

// Remove: this endpoint needs no auth even though default_security is set
#[ApiNoSecurity]
public function publicStatus(string $id): JsonResponse {}
```

---

[← The Shape Builder](shape-builder.md) &nbsp;&nbsp; [CLI Commands and Deployment →](deployment.md)
