# Installation

```bash
composer require botnetdobbs/laravel-luminous
```

Publish the config file:

```bash
php artisan vendor:publish --tag=luminous-config
```

Your API docs are live at `/docs`.

## Local vs production

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

- [CLI and deployment](/deployment): middleware, cache, generate, export
- [Security](/security): documenting auth schemes and scopes in the spec

## Optional YAML support

YAML export needs `symfony/yaml`:

```bash
composer require symfony/yaml
```

Then you can run:

```bash
php artisan luminous:export --format=yaml
```

## Next step

See the [quick look](/quick-look) for a full controller, FormRequest, and resource example.
