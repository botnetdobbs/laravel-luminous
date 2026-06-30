# CLI Commands and Deployment

---

## Where your docs are served

Once installed, Luminous registers three routes under the path you set in config
(default: `docs`):

| URL | What you get |
|-----|-------------|
| `/docs` | Swagger UI — the interactive browser |
| `/docs/openapi.json` | The raw OpenAPI 3.1 spec as JSON |
| `/docs/openapi.yaml` | The raw OpenAPI 3.1 spec as YAML (requires `symfony/yaml`) |

Change the path with `LUMINOUS_PATH=api-docs` in your `.env` and the routes move to
`/api-docs`, `/api-docs/openapi.json`, and `/api-docs/openapi.yaml`.

---

## CLI Commands

### Generate and cache the spec

```bash
php artisan luminous:generate
```

This reflects over your routes and classes, generates the OpenAPI spec, stores it
in the cache, and prints a summary:

```
Generating OpenAPI 3.1 spec...
Done in 142ms.
+-----------------------+-------+
| Metric                | Count |
+-----------------------+-------+
| Paths                 |    34 |
| Schemas in components |    21 |
+-----------------------+-------+
Spec: http://localhost/docs/openapi.json
```

Force a fresh generation even if the cache is already warm:

```bash
php artisan luminous:generate --force
```

Run a basic structural check after generating:

```bash
php artisan luminous:generate --validate
```

The `--validate` flag checks for structural problems like duplicate `operationId`
values, missing required OpenAPI fields, and invalid `$ref` paths. It does not do
full OpenAPI schema validation. For that, use a dedicated validator (see below).

---

### Export the spec to a file

Export as JSON:

```bash
php artisan luminous:export --format=json --output=openapi.json
```

Export as YAML (requires the `symfony/yaml` package):

```bash
composer require symfony/yaml
php artisan luminous:export --format=yaml --output=openapi.yaml
```

Print to stdout and pipe to a validator:

```bash
php artisan luminous:export --format=json | npx @redocly/cli lint -
```

Skip the cache and generate fresh output:

```bash
php artisan luminous:export --format=json --no-cache --output=openapi.json
```

Pretty-print the JSON output:

```bash
php artisan luminous:export --format=json --pretty --output=openapi.json
```

---

## Deployment

### Caching in production

Always enable the cache in production. The spec is generated through reflection and
route inspection. It is fast enough for development, but caching means production
requests never pay for reflection at all.

```env
LUMINOUS_CACHE=true
LUMINOUS_CACHE_TTL=3600
LUMINOUS_CACHE_STORE=redis
```

Add spec generation to your deploy script so the cache is warm the moment your
deploy goes live:

```bash
# Run this after composer install and migrations
php artisan luminous:generate --force
```

---

### Restricting access in production

Docs should not be publicly accessible in production. Set middleware to require
authentication before anyone can view the spec:

```env
LUMINOUS_MIDDLEWARE=auth:sanctum
```

You can use any middleware your application supports. Multiple middleware can be
provided as a comma-separated list:

```env
LUMINOUS_MIDDLEWARE=auth:sanctum,verified
```

Or hide the docs entirely and distribute the spec as a static file to internal
consumers only:

```env
LUMINOUS_ENABLED=false
```

```bash
php artisan luminous:export --format=yaml --output=storage/app/openapi.yaml
```

---

### OpenAPI validation in CI

Add spec validation to your CI pipeline to catch documentation errors before they
reach production. This also ensures that new routes and schema changes do not break
the spec structure.

```yaml
# .github/workflows/ci.yml
- name: Validate OpenAPI spec
  run: |
    php artisan luminous:export --format=json --no-cache --output=/tmp/openapi.json
    npx @redocly/cli lint /tmp/openapi.json
```

You can also use `swagger-cli` or the Redocly GitHub Action if you prefer.

---

### Exporting for third-party tools

Some teams distribute the spec to consumers via Postman, Insomnia, or an internal
developer portal. Export the spec to a known location as part of your deployment:

```bash
# Export to a storage path that your portal can read
php artisan luminous:export --format=json --output=storage/app/public/openapi.json

# Or commit it to the repo as documentation
php artisan luminous:export --format=yaml --output=docs/openapi.yaml
```
