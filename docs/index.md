---
layout: home
title: Luminous
titleTemplate: OpenAPI docs from PHP 8 Attributes
hero:
  name: Luminous
  text: OpenAPI from your Laravel code
  tagline: Generate OpenAPI 3.2 docs from PHP 8 Attributes. No YAML files to maintain. No docblocks to parse.
  image:
    src: /logo.svg
    alt: Luminous
  actions:
    - theme: brand
      text: Get started
      link: /introduction
    - theme: alt
      text: View on GitHub
      link: https://github.com/botnet-dobbs/laravel-luminous
    - theme: alt
      text: Packagist
      link: https://packagist.org/packages/botnetdobbs/laravel-luminous
features:
  - title: Attributes, not YAML
    details: Put a few PHP 8 attributes on your controllers. Luminous builds the full OpenAPI 3.2 spec automatically.
  - title: FormRequest rules drive request bodies
    details: Request schemas follow your validation rules, so they stay up to date as your API changes.
  - title: Resources stay honest
    details: Document API Resources with a schema() method next to toArray(), so response docs match what you return.
  - title: Ready-made UI
    details: Swagger UI, Redoc, or Scalar at /docs. Export JSON or YAML for portals, linters, and SDK generators.
---

<div class="home-demo">

## From attributes to a spec

The controller you already have, and the OpenAPI it becomes.

::: code-group

```php [PaymentController.php]
#[ApiTag('Payments')]
class PaymentController extends Controller
{
    #[ApiOperation('Create a payment')]
    #[ApiResponse(201, PaymentResource::class, 'Payment created')]
    #[ApiResponse(422, ErrorResource::class, 'Validation failed')]
    public function store(CreatePaymentRequest $request): JsonResponse
    {
        // ...
    }
}
```

```yaml [openapi.yaml (excerpt)]
/api/payments:
  post:
    tags: [Payments]
    summary: Create a payment
    requestBody:
      required: true
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/CreatePaymentRequest'
    responses:
      '201':
        description: Payment created
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/PaymentResource'
      '422':
        description: Validation failed
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ErrorResource'
```

:::

</div>
