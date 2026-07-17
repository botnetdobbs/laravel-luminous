# Attribute reference

Where each attribute goes and what it does.

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

## Learn more by topic

- Controllers and operations: [Controllers](/controllers)
- Request bodies from validation: [Form requests](/form-requests)
- Response schemas: [API resources](/resources) and [Shape builder](/shape-builder)
- Auth schemes and scopes: [Security](/security)
