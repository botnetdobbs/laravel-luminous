# Common questions

## Do I need to annotate every controller method?

No. Every route appears in the spec. Unannotated routes get an auto-generated summary
from the controller and method name. Annotations make things accurate and readable
but are never required.

## What happens if I forget `#[ApiResponse]`?

Luminous adds a default `500 Internal Server Error` response. Other responses are
assumed to return a generic object. Add `#[ApiResponse]` to make the response schemas
accurate.

## My FormRequest has constructor dependencies. Will `rules()` work?

For most cases yes. Luminous instantiates the request without calling the constructor.
If your `rules()` calls `$this->user()` or reads injected services, those calls fail
silently and Luminous returns a bare schema. Restructure those rules to avoid
request-time dependencies, or use `#[ApiShape]` to provide the schema explicitly.

## Can I use a plain PHP class or DTO with `#[ApiResponse]`?

Yes. `#[ApiResponse]` accepts any class name. If the class has `#[ApiShape]` with a
`schema()` method, or has `#[ApiProperty]` on its public properties, Luminous
extracts the schema from it just like it would from a `JsonResource`.

## Can I check the exported file, or build an SDK from it?

Yes. Export with `luminous:export`, then use normal OpenAPI tools. Redocly or Spectral
can check the file. openapi-generator or Fern can build a client library. See
[Using the exported spec](/deployment#using-the-exported-spec).

## How do I hide docs in production?

Use middleware, or turn the UI off:

```bash
LUMINOUS_MIDDLEWARE=auth
# or
LUMINOUS_ENABLED=false
```

See [CLI and deployment](/deployment) for cache generation and access control.

## Which UI should I pick?

Swagger UI is the default and is good for trying requests. Redoc is strong for reading
long specs. Scalar is a modern alternative with a clean layout. Set `LUMINOUS_UI` to
`swagger`, `redoc`, or `scalar`. Details in [Configuration](/configuration#choosing-a-ui-driver).
