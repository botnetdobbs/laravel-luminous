<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable / Disable
    |--------------------------------------------------------------------------
    |
    | Set LUMINOUS_ENABLED=false in production to hide the docs entirely.
    | All three routes (/docs, /docs/openapi.json, /docs/openapi.yaml)
    | return 404 when disabled.
    |
    */
    'enabled' => env('LUMINOUS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | URL Path
    |--------------------------------------------------------------------------
    |
    | The URL prefix under which the docs are served.
    | Defaults to "docs", so the UI is at /docs and the spec at /docs/openapi.json.
    |
    */
    'path' => env('LUMINOUS_PATH', 'docs'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to all three docs routes. Accepts a comma-separated
    | list of middleware names in the environment variable.
    |
    | Example: LUMINOUS_MIDDLEWARE=auth|throttle:60,1
    |
    | Use | as the delimiter, not commas. Commas appear inside middleware parameters
    | like throttle:60,1 and would silently split them into invalid middleware names.
    |
    | For production, always set at minimum: LUMINOUS_MIDDLEWARE=auth
    | Leaving this blank exposes your full API surface publicly with no authentication.
    |
    */
    'middleware' => env('LUMINOUS_MIDDLEWARE')
        ? array_map('trim', explode('|', env('LUMINOUS_MIDDLEWARE')))
        : [],

    /*
    |--------------------------------------------------------------------------
    | API Info
    |--------------------------------------------------------------------------
    |
    | Appears in the OpenAPI "info" object and in the Swagger UI page title.
    |
    */
    'info' => [
        'title' => env('LUMINOUS_TITLE', 'Luminous API'),
        'version' => env('LUMINOUS_VERSION', '1.0.0'),
        'description' => env('LUMINOUS_DESCRIPTION', ''),
        'contact' => [
            'name' => env('LUMINOUS_CONTACT_NAME', ''),
            'email' => env('LUMINOUS_CONTACT_EMAIL', ''),
            'url' => env('LUMINOUS_CONTACT_URL', ''),
        ],
        'license' => [
            'name' => env('LUMINOUS_LICENSE', ''),
            'url' => env('LUMINOUS_LICENSE_URL', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Servers
    |--------------------------------------------------------------------------
    |
    | The list of servers shown in Swagger UI's server selector.
    | Defaults to APP_URL. Add staging or production servers here.
    |
    */
    'servers' => [
        ['url' => env('APP_URL', 'http://localhost'), 'description' => 'Local'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Inclusion Filter
    |--------------------------------------------------------------------------
    |
    | When this array is empty, all routes are included except those matched
    | by "exclude_routes" below.
    |
    | When non-empty, only routes whose name matches one of the listed patterns
    | are included in the spec. Patterns are matched against the route name.
    | A trailing ".*" wildcard matches any suffix.
    |
    | Example: include only API v1 routes
    |
    |   'include_routes' => ['api.v1.*'],
    |
    | Route names that do not match any pattern are excluded regardless of the
    | "exclude_routes" list.
    |
    */
    'include_routes' => [],

    /*
    |--------------------------------------------------------------------------
    | Route Exclusion Filter
    |--------------------------------------------------------------------------
    |
    | Route name patterns to exclude from the generated spec. Evaluated after
    | "include_routes". A trailing ".*" wildcard matches any suffix.
    |
    | The package's own routes (luminous.*) are always excluded so they do
    | not appear as documented endpoints.
    |
    */
    'exclude_routes' => [
        'luminous.*', 'telescope.*', 'horizon.*',
        'debugbar.*', 'sanctum.*', 'ignition.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Schemes
    |--------------------------------------------------------------------------
    |
    | Named security schemes that can be referenced by #[ApiSecurity] attributes.
    | These appear in the OpenAPI "components.securitySchemes" object.
    |
    | Supported types: http, apiKey, oauth2, openIdConnect
    |
    */
    'security_schemes' => [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ],
        'apiKey' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'Authorization',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Security
    |--------------------------------------------------------------------------
    |
    | Applied to every operation unless overridden by #[ApiSecurity] or
    | #[ApiNoSecurity] on the controller or method.
    |
    | Each entry is a security requirement object as defined in the OpenAPI spec.
    | Example: require bearerAuth on all routes by default
    |
    |   'default_security' => [['bearerAuth' => []]],
    |
    */
    'default_security' => [],

    /*
    |--------------------------------------------------------------------------
    | Response Envelope Wrapping
    |--------------------------------------------------------------------------
    |
    | When true, every response schema is wrapped in a top-level object using
    | the key defined by "response_wrapper_key".
    |
    | Enable this if your API always returns {"data": ...} around its payloads.
    |
    | For paginated responses (#[ApiResponse(paginated: true)]), a "pagination"
    | key is added alongside "data" when "include_pagination_schema" is true.
    |
    */
    'wrap_responses' => false,
    'response_wrapper_key' => 'data',
    'include_pagination_schema' => true,

    /*
    |--------------------------------------------------------------------------
    | Shared Schemas
    |--------------------------------------------------------------------------
    |
    | Named schemas registered in components.schemas before any routes are
    | processed. Useful for error envelopes and other app-wide shapes that do
    | not belong to a specific resource class.
    |
    | Override or extend by publishing the config and editing this array.
    | Remove an entry by setting it to null. Add new entries as needed.
    |
    */
    'shared_schemas' => [
        'ErrorResponse' => [
            'type' => 'object',
            'properties' => [
                'code' => ['type' => 'string'],
                'message' => ['type' => 'string'],
                'request_id' => ['type' => 'string'],
                'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                'details' => ['type' => 'object'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External Documentation
    |--------------------------------------------------------------------------
    |
    | A link to external documentation shown at the root of the generated spec.
    | Set to an array with 'url' (required) and 'description' (optional),
    | or leave null to omit the field entirely.
    |
    | Example:
    |   'external_docs' => ['url' => 'https://docs.example.com', 'description' => 'Full docs'],
    |
    */
    'external_docs' => null,

    /*
    |--------------------------------------------------------------------------
    | Self URL ($self)
    |--------------------------------------------------------------------------
    |
    | OAS 3.2 uses JSON Schema 2020-12 reference resolution. Without $self,
    | resolvers fall back to the retrieval URI, which breaks when the spec is
    | fetched from a proxy, CDN, or any URL that differs from where it lives.
    | Setting $self makes $ref resolution deterministic regardless of how the
    | document is retrieved.
    |
    | Defaults to your APP_URL + LUMINOUS_PATH + /openapi.json so it stays
    | correct as long as those two values are set.
    |
    */
    'self_url' => env('APP_URL', 'http://localhost').'/'.env('LUMINOUS_PATH', 'docs').'/openapi.json',

    /*
    |--------------------------------------------------------------------------
    | Spec Cache
    |--------------------------------------------------------------------------
    |
    | Caching avoids re-generating the spec on every request. Recommended for
    | production. Flush the cache with: php artisan luminous:generate --force
    |
    | "store" accepts any cache store defined in config/cache.php.
    | Leave null to use the default store.
    |
    */
    'cache' => [
        'enabled' => env('LUMINOUS_CACHE', true),
        'ttl' => env('LUMINOUS_CACHE_TTL', 3600),
        'key' => env('LUMINOUS_CACHE_KEY', 'luminous:spec'),
        'store' => env('LUMINOUS_CACHE_STORE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Driver
    |--------------------------------------------------------------------------
    |
    | Which documentation UI to serve at /docs.
    | Supported: swagger (default), redoc, scalar
    |
    | Set LUMINOUS_UI_DRIVER in your .env to switch.
    | CDN URLs and SRI hashes for each driver are managed by the package
    | internally. You do not need to configure them.
    |
    | Each driver has its own sub-key for its options. Settings under a
    | different driver's sub-key are ignored when that driver is not active.
    |
    */
    'ui' => [
        'driver' => env('LUMINOUS_UI_DRIVER', 'swagger'),

        'swagger' => [
            'dark_mode' => false,
            'persist_authorization' => true,     // keep auth token filled in after page refresh
            'display_request_duration' => true,  // show how long each request took
            // How many levels deep to expand schemas in the Schemas section.
            // Set to -1 to hide the Schemas section entirely.
            'default_models_expand_depth' => -1,
            // Code sample syntax highlight theme.
            // Options: agate, arta, monokai, nord, obsidian, tomorrow-night, idea
            'syntax_highlight_theme' => 'monokai',
            'try_it_out_enabled' => true,        // show the "Try it out" button on every endpoint
        ],

        'redoc' => [
            'theme' => 'default',    // default, dark, stripe
            'hide_download_button' => false,
            'expand_responses' => '',    // 'all' or comma-separated status codes e.g. '200,201'
            'native_scrollbars' => false,
            'path_in_middle_panel' => false,    // show the endpoint path in the middle panel instead of the right panel
            // Regex matched against schema names. Matching schemas are hidden from the sidebar.
            // ''             show all (default)
            // '.*'           hide every schema
            // 'Internal.*'   hide schemas whose name starts with "Internal"
            // '^(Foo|Bar)$'  hide only schemas named exactly "Foo" or "Bar"
            'hide_schema_pattern' => '',
        ],

        'scalar' => [
            'theme' => 'laserwave',    // default, alternate, moon, purple, solarized, etc.
            'layout' => 'modern',    // modern, classic
            'dark_mode' => true,
            'hide_models' => true,
            'show_sidebar' => true,
            // Scalar AI agent. Get a key at scalar.com.
            // Leave null to disable. Works on localhost without a key (10 free test messages).
            'agent_key' => env('SCALAR_AGENT_KEY', null),
        ],
    ],

];
