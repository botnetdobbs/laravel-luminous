# Introduction

Generate OpenAPI 3.2.0 docs from PHP 8 Attributes on your Laravel controllers.

No YAML files to maintain. No docblocks to parse. Put a few attributes on your
controllers, let your FormRequest `rules()` define the request body, document your
API Resources with a single static `schema()` method, and Luminous builds the full spec
automatically.

Request bodies follow your FormRequest `rules()`, so they stay up to date with validation.
Response docs use a separate `schema()` method (or `#[ApiProperty]`). Keep `schema()` next to
`toArray()` so the docs still match what you return.

## Requirements

- PHP 8.2+

| Laravel Version |
|-----------------|
| Laravel 11.x    |
| Laravel 12.x    |
| Laravel 13.x    |

## How the pieces fit

| Piece | What Luminous reads |
|-------|---------------------|
| Controllers | PHP 8 attributes like `#[ApiOperation]`, `#[ApiResponse]`, `#[ApiTag]` |
| Form requests | `rules()` for the request body schema; optional `hints()` for examples |
| API resources | `schema()` via `#[ApiShape]`, or `#[ApiProperty]` on public properties |
| Config | Title, servers, UI driver, middleware, security schemes, route filters |

## Where to go next

1. [Install the package](/installation)
2. Walk through a [controller, FormRequest, and resource](/quick-look)
3. Tune [configuration](/configuration) for your project
4. Dig into [controllers](/controllers), [form requests](/form-requests), and [resources](/resources)
