<?php

namespace Botnetdobbs\Luminous\Tests\Feature;

use Botnetdobbs\Luminous\Generator\ComponentsRegistry;
use Botnetdobbs\Luminous\Generator\GeneratorFactory;
use Botnetdobbs\Luminous\Generator\OpenApiGenerator;
use Botnetdobbs\Luminous\Generator\TagRegistry;
use Botnetdobbs\Luminous\Tests\Fixtures\Controllers\DanglingTagController;
use Botnetdobbs\Luminous\Tests\Fixtures\Controllers\PaymentController;
use Botnetdobbs\Luminous\Tests\LuminousTestCase;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;

class OpenApiGeneratorTest extends LuminousTestCase
{
    protected function defineRoutes($router): void
    {
        $router->prefix('v1')->group(function () use ($router) {
            $router->get('/payments', [PaymentController::class, 'index'])->name('payment.index');
            $router->post('/payments', [PaymentController::class, 'store'])->name('payment.store');
            $router->get('/payments/{id}', [PaymentController::class, 'show'])->name('payment.show');
            $router->delete('/payments/{id}', [PaymentController::class, 'cancel'])->name('payment.cancel');
            $router->post('/payments/merge', [PaymentController::class, 'merge'])->name('payment.merge');
            $router->get('/payments/options', [PaymentController::class, 'paymentOptions'])->name('payment.paymentOptions');
        });
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('luminous.enabled', true);
        $app['config']->set('luminous.include_pagination_schema', true);
        $app['config']->set('luminous.security_schemes', [
            'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'],
        ]);
    }

    protected function makeGenerator(?ComponentsRegistry $registry = null): OpenApiGenerator
    {
        return $this->app->make(GeneratorFactory::class)->make(
            config: config('luminous'),
            registry: $registry ?? new ComponentsRegistry,
            tagRegistry: new TagRegistry,
        );
    }

    public function test_generates_valid_openapi_32_structure(): void
    {
        $spec = $this->makeGenerator()->generate();

        $this->assertSame('3.2.0', $spec['openapi']);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('components', $spec);
        $this->assertArrayHasKey('tags', $spec);
        $this->assertArrayHasKey('servers', $spec);
    }

    public function test_info_uses_config_defaults(): void
    {
        $spec = $this->makeGenerator()->generate();

        $this->assertSame('Luminous API', $spec['info']['title']);
        $this->assertSame('1.0.0', $spec['info']['version']);
        $this->assertArrayNotHasKey('description', $spec['info']);
        $this->assertArrayNotHasKey('contact', $spec['info']);
        $this->assertArrayNotHasKey('license', $spec['info']);
    }

    public function test_components_schemas_is_populated(): void
    {
        $spec = $this->makeGenerator()->generate();

        $this->assertArrayHasKey('schemas', $spec['components']);
        $this->assertArrayHasKey('ErrorResponse', $spec['components']['schemas']);
        $this->assertArrayHasKey('PaginationMeta', $spec['components']['schemas']);

        // OpenAPI 3.2 uses type union, not nullable: true
        $cursor = $spec['components']['schemas']['PaginationMeta']['properties']['cursor'];
        $this->assertSame(['string', 'null'], $cursor['type']);
    }

    public function test_pagination_schema_absent_when_disabled(): void
    {
        $this->app['config']->set('luminous.include_pagination_schema', false);

        $spec = $this->makeGenerator()->generate();

        $this->assertArrayNotHasKey('PaginationMeta', $spec['components']['schemas']);
    }

    public function test_request_body_schema_uses_ref_not_inline(): void
    {
        $spec = $this->makeGenerator()->generate();

        $schema = $spec['paths']['/v1/payments']['post']['requestBody']['content']['application/json']['schema'];
        $this->assertArrayHasKey('$ref', $schema);
        $this->assertStringStartsWith('#/components/schemas/', $schema['$ref']);
    }

    public function test_paths_are_sorted_alphabetically(): void
    {
        $spec = $this->makeGenerator()->generate();

        $paths = array_keys($spec['paths']);
        $sorted = collect($paths)->sort()->values()->all();

        $this->assertSame($sorted, $paths);
    }

    public function test_registry_is_reset_between_calls(): void
    {
        $registry = new ComponentsRegistry;
        $generator = $this->makeGenerator($registry);

        $generator->generate();
        $countAfterFirst = count($registry->all());

        $generator->generate();
        $countAfterSecond = count($registry->all());

        $this->assertSame($countAfterFirst, $countAfterSecond);
    }

    public function test_failed_route_extraction_is_skipped_not_thrown(): void
    {
        // PaymentController exists but 'ghost' method does not. ReflectionException inside generate()'s try/catch
        $this->app['router']->get('/bad', [PaymentController::class, 'ghost'])->name('bad.test');

        $spec = $this->makeGenerator()->generate();

        $this->assertArrayNotHasKey('/bad', $spec['paths']);
    }

    public function test_tags_appear_at_top_level_and_are_sorted(): void
    {
        $spec = $this->makeGenerator()->generate();

        $tagNames = collect($spec['tags'])->pluck('name')->all();
        $this->assertContains('Payments', $tagNames);

        $sorted = collect($tagNames)->sort()->values()->all();
        $this->assertSame($sorted, $tagNames);
    }

    public function test_security_schemes_appear_in_components(): void
    {
        $spec = $this->makeGenerator()->generate();

        $this->assertArrayHasKey('securitySchemes', $spec['components']);
        $this->assertArrayHasKey('bearerAuth', $spec['components']['securitySchemes']);
    }

    public function test_api_tag_description_appears_in_top_level_tags(): void
    {
        $spec = $this->makeGenerator()->generate();

        $paymentsTag = collect($spec['tags'])->firstWhere('name', 'Payments');

        $this->assertNotNull($paymentsTag, 'Payments tag not found in top-level tags');
        $this->assertSame('Payment lifecycle: create, retrieve, cancel', $paymentsTag['description'] ?? '');
    }

    public function test_top_level_tags_are_sorted_alphabetically(): void
    {
        $spec = $this->makeGenerator()->generate();

        $tagNames = collect($spec['tags'])->pluck('name')->values()->all();
        $this->assertSame(collect($tagNames)->sort()->values()->all(), $tagNames);
    }

    public function test_route_extractor_uses_injected_router(): void
    {
        // Passing a fresh router with no routes should produce an empty paths object.
        $emptyRouter = new Router(new Dispatcher);
        $generator = $this->app->make(GeneratorFactory::class)->make(
            config: array_merge(config('luminous'), ['exclude_routes' => []]),
            registry: new ComponentsRegistry,
            tagRegistry: new TagRegistry,
            router: $emptyRouter,
        );

        $spec = $generator->generate();

        $this->assertEmpty($spec['paths'], 'Empty router must produce no paths');
    }

    public function test_dangling_parent_tag_logs_warning(): void
    {
        Log::spy();

        $this->app['router']
            ->get('/invoices', [DanglingTagController::class, 'index'])
            ->name('invoices.index');

        $this->makeGenerator()->generate();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($msg) => str_contains((string) $msg, "'Invoices'")
                && str_contains((string) $msg, "'Billing'")
            );
    }

    public function test_self_url_appears_in_doc_when_configured(): void
    {
        $this->app['config']->set('luminous.self_url', 'https://api.example.com/openapi.json');

        $spec = $this->makeGenerator()->generate();

        $this->assertSame('https://api.example.com/openapi.json', $spec['$self']);
    }

    public function test_duplicate_operation_ids_get_numeric_suffix(): void
    {
        // Add a second versioned route for the same controller method so both share the same base operationId.
        $this->app['router']->get('/v2/payments', [PaymentController::class, 'index'])->name('v2.payment.index');

        $spec = $this->makeGenerator()->generate();

        $operationIds = collect($spec['paths'])
            ->flatMap(fn ($methods) => collect($methods)->pluck('operationId'))
            ->filter()
            ->values()
            ->all();

        $this->assertSame(
            count($operationIds),
            count(array_unique($operationIds)),
            'Spec must not contain duplicate operationIds'
        );

        $v1Id = $spec['paths']['/v1/payments']['get']['operationId'] ?? '';
        $v2Id = $spec['paths']['/v2/payments']['get']['operationId'] ?? '';

        $this->assertNotSame('', $v1Id);
        $this->assertSame($v1Id.'_2', $v2Id);
    }

    public function test_root_external_docs_emitted_when_configured(): void
    {
        $this->app['config']->set('luminous.external_docs', [
            'url' => 'https://docs.example.com',
            'description' => 'Full API documentation',
        ]);

        $spec = $this->makeGenerator()->generate();

        $this->assertArrayHasKey('externalDocs', $spec);
        $this->assertSame('https://docs.example.com', $spec['externalDocs']['url']);
        $this->assertSame('Full API documentation', $spec['externalDocs']['description']);
    }

    public function test_root_external_docs_omitted_when_null(): void
    {
        $spec = $this->makeGenerator()->generate();

        $this->assertArrayNotHasKey('externalDocs', $spec);
    }

    public function test_non_standard_http_method_placed_under_additional_operations(): void
    {
        try {
            $this->app['router']->addRoute(['LINK'], '/link-test', [PaymentController::class, 'index'])
                ->name('link.test');
        } catch (\Throwable) {
            $this->markTestSkipped('Laravel router does not support the LINK HTTP verb.');
        }

        $spec = $this->makeGenerator()->generate();

        $this->assertArrayHasKey('/link-test', $spec['paths']);
        $pathItem = $spec['paths']['/link-test'];
        $this->assertArrayHasKey('additionalOperations', $pathItem);
        $this->assertArrayHasKey('link', $pathItem['additionalOperations']);
        $this->assertArrayNotHasKey('link', collect($pathItem)->except('additionalOperations')->all());
    }

    public function test_standard_http_methods_not_placed_under_additional_operations(): void
    {
        $spec = $this->makeGenerator()->generate();

        foreach ($spec['paths'] as $path => $pathItem) {
            $this->assertArrayNotHasKey(
                'additionalOperations',
                $pathItem,
                "Path {$path} should not have additionalOperations for standard HTTP methods"
            );
        }
    }

    public function test_default_security_unknown_scheme_logs_warning(): void
    {
        Log::spy();

        $this->app['config']->set('luminous.default_security', [['undeclaredScheme' => []]]);

        $this->makeGenerator()->generate();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($msg) => str_contains((string) $msg, 'undeclaredScheme'));
    }

    public function test_default_security_known_schemes_no_warning(): void
    {
        Log::spy();

        $this->app['config']->set('luminous.default_security', [['bearerAuth' => []]]);

        $this->makeGenerator()->generate();

        Log::shouldNotHaveReceived('warning');
    }

    public function test_default_security_scalar_entry_logs_warning_and_does_not_throw(): void
    {
        Log::spy();

        $this->app['config']->set('luminous.default_security', ['BearerAuth']);

        $spec = $this->makeGenerator()->generate();

        $this->assertIsArray($spec);
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($msg) => str_contains((string) $msg, 'must be an array'));
    }
}
