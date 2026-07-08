# Configuration

Open `config/luminous.php` after publishing it. The defaults work for most projects.

```php
return [
    // Set LUMINOUS_ENABLED=false to hide docs entirely in production.
    'enabled' => env('LUMINOUS_ENABLED', true),

    // URL prefix where docs are served. Default: /docs
    'path' => env('LUMINOUS_PATH', 'docs'),

    // Your API info. Shown at the top of every UI.
    'info' => [
        'title'       => env('LUMINOUS_TITLE', 'Luminous API'),
        'version'     => env('LUMINOUS_VERSION', '1.0.0'),
        'description' => env('LUMINOUS_DESCRIPTION', ''),
        'contact' => [
            'name'  => env('LUMINOUS_CONTACT_NAME', ''),
            'email' => env('LUMINOUS_CONTACT_EMAIL', ''),
            'url'   => env('LUMINOUS_CONTACT_URL', ''),
        ],
        'license' => [
            'name' => env('LUMINOUS_LICENSE', ''),
            'url'  => env('LUMINOUS_LICENSE_URL', ''),
        ],
    ],

    // The server selector dropdown. Add one entry per environment.
    'servers' => [
        ['url' => env('APP_URL', 'http://localhost'), 'description' => 'Local'],
    ],

    // Middleware protecting all three docs routes (/docs, .json, .yaml).
    // Use | as the delimiter. Commas appear inside middleware parameters
    // like throttle:60,1 and would split them incorrectly.
    'middleware' => env('LUMINOUS_MIDDLEWARE')
        ? array_map('trim', explode('|', env('LUMINOUS_MIDDLEWARE')))
        : [],

    // Only document routes whose names match these patterns.
    // Empty means all routes are included.
    'include_routes' => [],

    // Exclude routes whose names match these patterns. Evaluated after include_routes.
    'exclude_routes' => [
        'luminous.*', 'telescope.*', 'horizon.*',
        'debugbar.*', 'sanctum.*', 'ignition.*',
    ],

    // Security scheme definitions - referenced by name in #[ApiSecurity] attributes.
    'security_schemes' => [
        'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'],
        'apiKey'     => ['type' => 'apiKey', 'in' => 'header', 'name' => 'Authorization'],
    ],

    // Applied to every endpoint that has no explicit #[ApiSecurity] or #[ApiNoSecurity].
    'default_security' => [],

    // Set to true if your API wraps responses in {"data": ...}.
    'wrap_responses'            => false,
    'response_wrapper_key'      => 'data',
    // Whether to include the shared PaginationMeta schema in components.
    // This is referenced automatically when you use paginated: true on #[ApiResponse].
    'include_pagination_schema' => true,

    // Named schemas registered in components.schemas before any routes are processed.
    'shared_schemas' => [
        'ErrorResponse' => [
            'type' => 'object',
            'properties' => [
                'code'       => ['type' => 'string'],
                'message'    => ['type' => 'string'],
                'request_id' => ['type' => 'string'],
                'timestamp'  => ['type' => 'string', 'format' => 'date-time'],
                'details'    => ['type' => 'object'],
            ],
        ],
    ],

    // Canonical URL of the spec ($self). Defaults to APP_URL/LUMINOUS_PATH/openapi.json.
    'self_url' => env('APP_URL', 'http://localhost').'/'.env('LUMINOUS_PATH', 'docs').'/openapi.json',

    // Caching avoids re-generating the spec on every request. Recommended for production.
    'cache' => [
        'enabled' => env('LUMINOUS_CACHE', true),
        'ttl'     => env('LUMINOUS_CACHE_TTL', 3600),
        'key'     => env('LUMINOUS_CACHE_KEY', 'luminous:spec'),
        'store'   => env('LUMINOUS_CACHE_STORE', null),
    ],

    // Which UI to render at /docs. Options: swagger (default), redoc, scalar.
    // See the Swagger UI options, Redoc options, and Scalar options sections below
    // for each driver's full set of options.
    'ui' => [
        'driver' => env('LUMINOUS_UI_DRIVER', 'swagger'),
    ],
];
```

---

## Common .env settings

```env
# Basic info
LUMINOUS_TITLE="Payments API"
LUMINOUS_VERSION="2.0.0"
LUMINOUS_DESCRIPTION="Handles payment creation and lifecycle management."

# Choose your UI
LUMINOUS_UI_DRIVER=redoc

# Protect docs in production
LUMINOUS_MIDDLEWARE=auth:sanctum

# Or disable docs entirely and export to a static file instead
LUMINOUS_ENABLED=false

# Cache
LUMINOUS_CACHE=true
LUMINOUS_CACHE_TTL=3600
```

---

## Choosing a UI driver

Luminous supports three documentation UIs. Switch with `LUMINOUS_UI_DRIVER` in your `.env`:

| Value | UI | Best for |
|-------|----|----------|
| `swagger` | Swagger UI 5.x | Interactive testing, familiar interface |
| `redoc` | Redoc 2.x | Clean read-only layout, public-facing APIs |
| `scalar` | Scalar | Modern interactive UI with AI agent |

CDN URLs and SRI hashes for each driver are managed by the package internally. You do not need to configure them.

**OpenAPI 3.2 support:** Swagger UI and Redoc have full 3.2 support. Scalar's parser supports 3.2 but full rendering of all 3.2-specific features is still in progress upstream.

---

## Swagger UI options

```php
'swagger' => [
    'dark_mode'                   => false,
    'persist_authorization'       => true,
    'display_request_duration'    => true,
    'default_models_expand_depth' => 1,
    'syntax_highlight_theme'      => 'monokai',
    'try_it_out_enabled'          => true,
],
```

**`dark_mode`**: adds the built-in `dark-mode` CSS class introduced in Swagger UI 5.x. There is no config flag for this upstream; Luminous manages the class for you. Also injects `color-scheme: light dark` so browser scrollbars and native controls follow along.

**`syntax_highlight_theme`**: controls the colour theme for code sample blocks. Valid values: `agate` (default), `arta`, `monokai`, `nord`, `obsidian`, `tomorrow-night`, `idea`.

**`default_models_expand_depth`**: how many levels deep to expand schemas in the Schemas section. Set to `-1` to hide the section entirely.

---

## Redoc options

```php
'redoc' => [
    'theme'                => 'default',
    'hide_download_button' => false,
    'expand_responses'     => '',
    'native_scrollbars'    => false,
    'path_in_middle_panel' => false,
    'hide_schema_pattern'  => '',
],
```

**`theme`**: built-in Redoc themes: `default`, `dark`, `stripe`. See [Redoc themes](#redoc-themes) below.

**`hide_download_button`**: removes the Download button from the top bar. Set to `true` if you do not want consumers downloading a copy of the spec.

**`expand_responses`**: set to `'all'` to expand every response by default, or a comma-separated list of status codes like `'200,201'`.

**`native_scrollbars`**: when `true`, uses the browser's native scrollbars instead of Redoc's custom styled ones. Useful if the custom scrollbars conflict with your OS or browser theme.

**`path_in_middle_panel`**: when `true`, shows the endpoint path (e.g. `POST /payments`) in the middle content panel instead of the right code panel. Useful when your paths are long.

**`hide_schema_pattern`**: a regex for filtering schemas out of the sidebar. See [Hiding the Schemas section](#hiding-the-schemas-section).

---

## Redoc themes

Redoc ships with three built-in themes.

**`default`**: the standard Redoc look. White background, blue sidebar, dark right panel.

**`dark`**: GitHub-dark inspired. Dark background throughout, blue accent colour, and monospace code samples. Falls back to system fonts so no external font requests are made.

**`stripe`**: Stripe Docs inspired. Clean light layout using system fonts (SF Pro on macOS, Segoe UI on Windows), native monospace for code, and Stripe's signature deep navy right panel. No external font requests.

To switch:

```php
'redoc' => [
    'theme' => 'dark',
],
```

The themes are built into the package. There is nothing extra to install.

---

## Scalar options

```php
'scalar' => [
    'theme'        => 'default',
    'layout'       => 'modern',
    'dark_mode'    => false,
    'hide_models'  => false,
    'show_sidebar' => true,
    'agent_key'    => env('SCALAR_AGENT_KEY', null),
],
```

**`theme`**: Scalar's built-in colour themes: `default`, `alternate`, `moon`, `purple`, `solarized`, `bluePlanet`, `deepSpace`, `laserwave`, `kepler`, `mars`, `saturn`, `none`.

**`layout`**: `modern` (default) or `classic`. Classic mimics the three-column Swagger UI layout.

**`dark_mode`**: toggle Scalar's dark mode. Unlike Swagger UI, Scalar has proper built-in dark mode support.

**`show_sidebar`**: set to `false` to hide the left navigation sidebar. Useful for embedded or minimal views where you want just the content.

**`hide_models`**: set to `true` to hide the Models section at the bottom of the page. See [Hiding the Schemas section](#hiding-the-schemas-section).

### Scalar AI agent

Scalar has a built-in AI chat that lets users ask questions about your API based on your OpenAPI document.

It works on localhost without a key (10 free test messages). For production, get a key at scalar.com and set it in `.env`:

```env
SCALAR_AGENT_KEY=your-key-here
```

Leave it unset to disable the agent entirely.

---

## Filtering routes

Use `include_routes` to show only specific routes, or `exclude_routes` to hide routes you do not want in the docs. Both accept exact route names and wildcard patterns using `.*`.

```php
// Only document routes under the "api.*" name prefix
'include_routes' => ['api.*'],

// Exclude internal and admin routes
'exclude_routes' => ['luminous.*', 'admin.*', 'internal.*'],
```

When `include_routes` is non-empty, only matching routes are considered. `exclude_routes` is then applied on top. The package's own `luminous.*` routes are always excluded.

For finer-grained control, use `#[ApiIgnore]` directly on a controller or method instead of matching by route name:

```php
use Botnetdobbs\Luminous\Attributes\ApiIgnore;

// Hide every endpoint in this controller
#[ApiIgnore]
class InternalController extends Controller { ... }

// Hide a single endpoint
class PaymentController extends Controller
{
    #[ApiIgnore]
    public function debugDump(): JsonResponse { ... }
}
```

`#[ApiIgnore]` also works on resource properties when you are using the `#[ApiProperty]` strategy. If your resource defines a static `schema()` method instead, only what you return from that method is included in the spec, so `#[ApiIgnore]` on properties is not needed:

```php
// #[ApiIgnore] needed here: Luminous scans public properties for #[ApiProperty]
class PaymentResource extends JsonResource
{
    #[ApiProperty(description: 'The payment ID')]
    public string $id;

    #[ApiIgnore]
    public string $internalRef; // excluded from the schema
}

// No #[ApiIgnore] needed: schema() is the complete definition
class PaymentResource extends JsonResource
{
    public static function schema(): array
    {
        return [
            'id' => ['type' => 'string'],
        ];
        // internalRef is simply not listed here, so it never appears in the spec
    }
}
```

---

## Multiple servers

List every server your API runs on. All three UIs show a dropdown so consumers can switch between them.

```php
'servers' => [
    ['url' => 'https://api.example.com',         'description' => 'Production'],
    ['url' => 'https://staging.api.example.com', 'description' => 'Staging'],
    ['url' => 'http://localhost',                 'description' => 'Local'],
],
```

---

## External documentation

Link to external docs from the root of the spec. UIs that support it show this as a
link at the top of the page, separate from individual endpoint links.

```php
'external_docs' => [
    'url'         => 'https://docs.example.com',
    'description' => 'Full API documentation',
],
```

Leave it `null` (the default) to omit the field entirely. Individual operations and
tags can also carry their own external docs links via `externalDocsUrl` on
`#[ApiOperation]` and `#[ApiTag]`. See [Documenting Controllers](controllers.md).

---

## Document identity

OAS 3.2 uses JSON Schema 2020-12 reference resolution. Without `$self`, resolvers fall back to the retrieval URI (the URL the document was fetched from). This breaks when the spec is served through a proxy, CDN, or any URL that differs from its canonical location. Setting `$self` makes `$ref` resolution deterministic regardless of how the document is retrieved.

Luminous defaults `$self` to your `APP_URL` + `LUMINOUS_PATH` + `/openapi.json`, so it stays correct as long as those two values are set. No extra configuration needed.

---

## Shared schemas

`shared_schemas` registers named schemas in `components.schemas` before any routes are processed. The default includes `ErrorResponse`, which you can reference in `#[ApiResponse]` attributes.

```php
'shared_schemas' => [
    'ErrorResponse' => [
        'type' => 'object',
        'properties' => [
            'code'       => ['type' => 'string'],
            'message'    => ['type' => 'string'],
            'request_id' => ['type' => 'string'],
            'timestamp'  => ['type' => 'string', 'format' => 'date-time'],
            'details'    => ['type' => 'object'],
        ],
    ],
],
```

Add your own schemas, override the defaults, or remove an entry by setting it to `null`. Any schema you add here is available as a `$ref` across your spec.

---

## Middleware and access control

Luminous only serves the spec and UI. Rate limiting, authentication, and any other access controls are standard Laravel middleware configured via `LUMINOUS_MIDDLEWARE`.

```env
# Require Sanctum authentication
LUMINOUS_MIDDLEWARE=auth:sanctum

# Require auth and throttle to 60 requests per minute
LUMINOUS_MIDDLEWARE=auth:sanctum|throttle:60,1
```

Use `|` as the delimiter between middleware, not commas. Commas appear inside middleware parameters like `throttle:60,1` and would split them into invalid names.

---

## Security schemes

Define your schemes here and reference them by name in `#[ApiSecurity]` attributes on your controllers. See [Security](security.md) for full details.

```php
'security_schemes' => [
    'bearerAuth' => [
        'type'         => 'http',
        'scheme'       => 'bearer',
        'bearerFormat' => 'JWT',
    ],
    'apiKey' => [
        'type' => 'apiKey',
        'in'   => 'header',
        'name' => 'X-API-Key',
    ],
],
```

If `default_security` references a scheme name that is not declared in
`security_schemes`, Luminous logs a warning when generating the spec. This catches
typos before they reach your consumers.

```
Luminous: default_security references undeclared scheme 'bearerAutth'. Add it to security_schemes or fix the name.
```

---

## Hiding the Schemas section

Each driver has its own way to hide schemas. The schemas stay in the JSON spec because they are needed for request and response documentation; only the UI section that lists them is hidden.

**Swagger UI**: set `default_models_expand_depth` to `-1`:

```php
'swagger' => [
    'default_models_expand_depth' => -1,
],
```

**Redoc**: schemas appear as entries in the left sidebar. Use `hide_schema_pattern` with a regex matched against schema names:

```php
'redoc' => [
    'hide_schema_pattern' => '.*',           // hide every schema
    'hide_schema_pattern' => 'Internal.*',   // hide schemas starting with "Internal"
    'hide_schema_pattern' => '^(Foo|Bar)$',  // hide only "Foo" and "Bar"
],
```

Leave it empty (`''`) to show all schemas.

**Scalar**: set `hide_models` to `true`:

```php
'scalar' => [
    'hide_models' => true,
],
```

---

[← README](../README.md) &nbsp;&nbsp; [Documenting Controllers →](controllers.md)
