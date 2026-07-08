<?php

namespace Botnetdobbs\Luminous\Tests\Feature;

use Botnetdobbs\Luminous\Extractors\ControllerExtractor;
use Botnetdobbs\Luminous\Extractors\EnumExtractor;
use Botnetdobbs\Luminous\Extractors\ExtractedRoute;
use Botnetdobbs\Luminous\Extractors\RequestExtractor;
use Botnetdobbs\Luminous\Extractors\ResourceExtractor;
use Botnetdobbs\Luminous\Extractors\ResponseBuilder;
use Botnetdobbs\Luminous\Extractors\RulesSchemaBuilder;
use Botnetdobbs\Luminous\Generator\ComponentsRegistry;
use Botnetdobbs\Luminous\Generator\TagRegistry;
use Botnetdobbs\Luminous\Support\TypeMapper;
use Botnetdobbs\Luminous\Tests\Fixtures\Controllers\DanglingTagController;
use Botnetdobbs\Luminous\Tests\Fixtures\Controllers\IgnoredController;
use Botnetdobbs\Luminous\Tests\Fixtures\Controllers\OrderController;
use Botnetdobbs\Luminous\Tests\Fixtures\Controllers\PaymentController;
use Botnetdobbs\Luminous\Tests\Fixtures\Controllers\PlainController;
use Botnetdobbs\Luminous\Tests\Fixtures\Controllers\TestAttributeController;
use Botnetdobbs\Luminous\Tests\Fixtures\LedgerEntry;
use Botnetdobbs\Luminous\Tests\Fixtures\PaymentEvent;
use Botnetdobbs\Luminous\Tests\LuminousTestCase;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ControllerExtractorTest extends LuminousTestCase
{
    private function makeExtractor(array $config = [], ?TagRegistry $tagRegistry = null): ControllerExtractor
    {
        $registry = new ComponentsRegistry;
        $tagRegistry ??= new TagRegistry;
        $enumExtractor = new EnumExtractor;
        $typeMapper = new TypeMapper($enumExtractor);
        $rulesBuilder = new RulesSchemaBuilder($typeMapper, $registry, $enumExtractor);
        $requestEx = new RequestExtractor($typeMapper, $registry, $enumExtractor, $rulesBuilder);
        $mergedConfig = array_merge([
            'wrap_responses' => false,
            'response_wrapper_key' => 'data',
            'include_pagination_schema' => true,
            'default_security' => [],
        ], $config);
        $resourceEx = new ResourceExtractor($typeMapper, $registry, $enumExtractor);
        $responseBuilder = new ResponseBuilder($resourceEx, $mergedConfig);

        return new ControllerExtractor(
            requestExtractor: $requestEx,
            tagRegistry: $tagRegistry,
            responseBuilder: $responseBuilder,
            config: $mergedConfig,
        );
    }

    private function route(string $method, string $path, string $controllerMethod): ExtractedRoute
    {
        return new ExtractedRoute(
            httpMethod: $method,
            path: $path,
            controllerClass: PaymentController::class,
            methodName: $controllerMethod,
            routeName: 'payment.'.$controllerMethod,
            middlewares: [],
        );
    }

    public function test_extract_produces_valid_operation_object(): void
    {
        $op = $this->makeExtractor()->extract($this->route('post', '/v1/payments', 'store'));

        $this->assertArrayHasKey('summary', $op);
        $this->assertArrayHasKey('operationId', $op);
        $this->assertArrayHasKey('tags', $op);
        $this->assertArrayHasKey('requestBody', $op);
        $this->assertArrayHasKey('responses', $op);
    }

    public function test_request_body_uses_ref_not_inline(): void
    {
        $op = $this->makeExtractor()->extract($this->route('post', '/v1/payments', 'store'));

        $schema = $op['requestBody']['content']['application/json']['schema'];
        $this->assertArrayHasKey('$ref', $schema);
        $this->assertStringStartsWith('#/components/schemas/', $schema['$ref']);
    }

    public function test_api_no_security_produces_empty_security_array(): void
    {
        $op = $this->makeExtractor()->extract($this->route('get', '/v1/payments/{id}/status', 'publicStatus'));

        $this->assertSame([], $op['security']);
    }

    public function test_class_security_cascades_to_methods(): void
    {
        $op = $this->makeExtractor()->extract($this->route('get', '/v1/payments', 'index'));

        $this->assertArrayHasKey('security', $op);
        $this->assertNotEmpty($op['security']);
    }

    public function test_deprecated_operation_has_deprecated_flag(): void
    {
        $op = $this->makeExtractor()->extract($this->route('post', '/v1/payments/legacy', 'legacyStore'));

        $this->assertTrue($op['deprecated']);
        $this->assertStringContainsString('Deprecated', $op['description']);
    }

    public function test_class_tag_appears_in_operation(): void
    {
        $op = $this->makeExtractor()->extract($this->route('get', '/v1/payments', 'index'));

        $this->assertContains('Payments', $op['tags']);
    }

    public function test_collection_response_wraps_in_array_schema(): void
    {
        // TODO : this assertion will need updating when wrap_responses envelope lands
        $op = $this->makeExtractor()->extract($this->route('get', '/v1/payments', 'index'));

        $schema = $op['responses']['200']['content']['application/json']['schema'];
        $this->assertSame('array', $schema['type']);
        $this->assertArrayHasKey('items', $schema);
    }

    public function test_cancel_method_auto_detects_last_form_request(): void
    {
        $op = $this->makeExtractor()->extract($this->route('delete', '/v1/payments/{id}', 'cancel'));

        // cancel(string $id, CancelPaymentRequest $request). no #[ApiBody], should auto-detect CancelPaymentRequest
        $this->assertArrayHasKey('requestBody', $op);
        $schema = $op['requestBody']['content']['application/json']['schema']['$ref'];
        $this->assertStringContainsString('CancelPaymentRequest', $schema);
    }

    public function test_idempotency_key_header_is_documented(): void
    {
        $op = $this->makeExtractor()->extract($this->route('post', '/v1/payments', 'store'));

        $headerParam = collect($op['parameters'] ?? [])
            ->first(fn ($p) => $p['in'] === 'header' && $p['name'] === 'Idempotency-Key');

        $this->assertNotNull($headerParam);
        $this->assertTrue($headerParam['required']);
    }

    public function test_500_added_unless_explicitly_declared(): void
    {
        $op = $this->makeExtractor()->extract($this->route('get', '/v1/payments', 'index'));

        $this->assertArrayHasKey('500', $op['responses']);
    }

    public function test_request_example_attached_to_request_body(): void
    {
        $op = $this->makeExtractor()->extract($this->route('post', '/v1/payments', 'store'));

        $examples = $op['requestBody']['content']['application/json']['examples'] ?? [];
        $this->assertArrayHasKey('usd-payment', $examples);
    }

    public function test_class_level_api_ignore_returns_empty_array(): void
    {
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/internal',
            controllerClass: IgnoredController::class,
            methodName: 'index',
            routeName: 'internal.index',
            middlewares: [],
        );

        $this->assertSame([], $this->makeExtractor()->extract($route));
    }

    public function test_composed_of_with_for_status_applies_schema_to_target_response(): void
    {
        $op = $this->makeExtractor()->extract($this->route('post', '/v1/payments/merge', 'merge'));

        $schema = $op['responses']['200']['content']['application/json']['schema'];
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(2, $schema['oneOf']);
    }

    public function test_composed_of_without_for_status_applies_to_first_description_only_response(): void
    {
        $op = $this->makeExtractor()->extract($this->route('get', '/v1/payments/options', 'paymentOptions'));

        $schema = $op['responses']['200']['content']['application/json']['schema'];
        $this->assertArrayHasKey('anyOf', $schema);
        $this->assertCount(2, $schema['anyOf']);
    }

    public function test_default_security_config_used_when_no_security_attributes(): void
    {
        $defaultSec = [['apiKey' => []]];
        $extractor = $this->makeExtractor(['default_security' => $defaultSec]);
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/plain',
            controllerClass: PlainController::class,
            methodName: 'index',
            routeName: 'plain.index',
            middlewares: [],
        );

        $this->assertSame($defaultSec, $extractor->extract($route)['security']);
    }

    public function test_api_query_enum_appears_in_parameter_schema(): void
    {
        $op = $this->makeExtractor()->extract($this->route('get', '/v1/payments', 'index'));

        $statusParam = collect($op['parameters'] ?? [])
            ->first(fn ($p) => $p['name'] === 'status');

        $this->assertNotNull($statusParam);
        $this->assertArrayHasKey('enum', $statusParam['schema']);
        $this->assertContains('succeeded', $statusParam['schema']['enum']);
    }

    public function test_api_response_with_ref_uses_ref_path_directly(): void
    {
        $op = $this->makeExtractor()->extract($this->route('get', '/v1/payments', 'index'));

        $schema = $op['responses']['401']['content']['application/json']['schema'];
        $this->assertSame('#/components/schemas/ErrorResponse', $schema['$ref']);
    }

    public function test_inline_schema_emitted_as_is(): void
    {
        $op = $this->makeExtractor()->extract($this->route('get', '/v1/payments/{id}/status', 'statusSummary'));

        $schema = $op['responses']['200']['content']['application/json']['schema'];
        $this->assertSame('object', $schema['type']);
        $this->assertSame(['type' => 'string', 'format' => 'uuid'], $schema['properties']['id']);
        $this->assertSame(['id', 'status'], $schema['required']);
    }

    public function test_inline_schema_not_wrapped_by_wrap_responses(): void
    {
        $op = $this->makeExtractor(['wrap_responses' => true, 'response_wrapper_key' => 'data'])
            ->extract($this->route('get', '/v1/payments/{id}/status', 'statusSummary'));

        $schema = $op['responses']['200']['content']['application/json']['schema'];
        $this->assertArrayNotHasKey('data', $schema['properties'] ?? []);
        $this->assertSame('object', $schema['type']);
    }

    public function test_path_params_are_auto_detected_from_route_uri(): void
    {
        $route = new ExtractedRoute('get', '/orders/{orderId}', OrderController::class, 'show', 'order.show', []);
        $op = $this->makeExtractor()->extract($route);

        $pathParam = collect($op['parameters'] ?? [])->firstWhere('name', 'orderId');

        $this->assertNotNull($pathParam, 'orderId path parameter was not generated');
        $this->assertSame('path', $pathParam['in']);
        $this->assertTrue($pathParam['required']);
        $this->assertSame('integer', $pathParam['schema']['type']);
    }

    public function test_multiple_path_params_are_all_auto_detected(): void
    {
        $route = new ExtractedRoute('get', '/orders/{orderId}/items/{itemId}', OrderController::class, 'item', 'order.item', []);
        $op = $this->makeExtractor()->extract($route);

        $names = collect($op['parameters'] ?? [])->where('in', 'path')->pluck('name')->all();

        $this->assertContains('orderId', $names);
        $this->assertContains('itemId', $names);
    }

    public function test_explicit_api_param_overrides_auto_detection(): void
    {
        $op = $this->makeExtractor()->extract($this->route('get', '/v1/payments/{id}', 'show'));

        $idParams = collect($op['parameters'] ?? [])->where('name', 'id')->where('in', 'path')->values()->all();

        $this->assertCount(1, $idParams, 'id must appear exactly once, not duplicated by auto-detection');
        $this->assertSame('uuid', $idParams[0]['schema']['format'] ?? null, 'Explicit format from #[ApiParam] must be preserved');
    }

    public function test_api_body_file_upload_request_uses_multipart_media_type(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute(
            httpMethod: 'post',
            path: '/avatar',
            controllerClass: TestAttributeController::class,
            methodName: 'uploadAvatar',
            routeName: 'avatar.upload',
            middlewares: [],
        );

        $op = $extractor->extract($route);

        $this->assertArrayHasKey('requestBody', $op);
        $this->assertArrayHasKey('multipart/form-data', $op['requestBody']['content'],
            'ApiBody with a file-upload request must use multipart/form-data, not application/json');
        $this->assertArrayNotHasKey('application/json', $op['requestBody']['content']);
    }

    public function test_api_example_targeting_absent_status_is_skipped_with_warning(): void
    {
        Log::spy();

        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/ghost',
            controllerClass: TestAttributeController::class,
            methodName: 'ghostExample',
            routeName: 'ghost.example',
            middlewares: [],
        );

        $op = $extractor->extract($route);

        $this->assertArrayNotHasKey('999', $op['responses'],
            'example targeting absent status 999 must not create a response entry');
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($msg) => str_contains((string) $msg, 'targets response status 999'));
    }

    public function test_api_stream_with_shape_class_produces_item_schema(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/stream',
            controllerClass: TestAttributeController::class,
            methodName: 'eventStream',
            routeName: 'stream.events',
            middlewares: [],
        );

        $op = $extractor->extract($route);

        $content = $op['responses']['200']['content'];
        $this->assertArrayHasKey('text/event-stream', $content);
        $this->assertArrayHasKey('itemSchema', $content['text/event-stream'],
            'streaming media types must use itemSchema, not schema');
        $this->assertArrayNotHasKey('schema', $content['text/event-stream']);
        $this->assertStringContainsString('PaymentEvent', $content['text/event-stream']['itemSchema']['$ref']);
    }

    public function test_api_stream_schema_class_need_not_be_json_resource(): void
    {
        $this->assertFalse(
            is_subclass_of(PaymentEvent::class, JsonResource::class),
            'PaymentEvent must be a plain PHP class to prove ApiStream works without JsonResource'
        );
        $this->assertFalse(
            is_subclass_of(LedgerEntry::class, JsonResource::class),
            'LedgerEntry must be a plain PHP class to prove ApiStream works without JsonResource'
        );

        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/stream',
            controllerClass: TestAttributeController::class,
            methodName: 'eventStream',
            routeName: 'stream.events',
            middlewares: [],
        );

        $op = $extractor->extract($route);

        $itemSchema = $op['responses']['200']['content']['text/event-stream']['itemSchema'];
        $this->assertArrayHasKey('$ref', $itemSchema);
    }

    public function test_api_stream_with_jsonl_emits_correct_media_type(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/jsonl',
            controllerClass: TestAttributeController::class,
            methodName: 'jsonlStream',
            routeName: 'stream.jsonl',
            middlewares: [],
        );

        $op = $extractor->extract($route);

        $content = $op['responses']['200']['content'];
        $this->assertArrayHasKey('application/jsonl', $content);
        $this->assertArrayHasKey('itemSchema', $content['application/jsonl']);
        $this->assertStringContainsString('LedgerEntry', $content['application/jsonl']['itemSchema']['$ref']);
        $this->assertArrayNotHasKey('text/event-stream', $content);
    }

    public function test_api_query_querystring_location_emits_content_map(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/filtered',
            controllerClass: TestAttributeController::class,
            methodName: 'filteredIndex',
            routeName: 'filtered.index',
            middlewares: [],
        );

        $op = $extractor->extract($route);

        $filtersParam = collect($op['parameters'] ?? [])
            ->first(fn ($p) => $p['name'] === 'filters');

        $this->assertNotNull($filtersParam);
        $this->assertSame('querystring', $filtersParam['in']);
        $this->assertArrayHasKey('content', $filtersParam,
            'querystring parameters must use content map, not bare schema');
        $this->assertArrayNotHasKey('schema', $filtersParam,
            'querystring parameters must not have top-level schema');
        $this->assertArrayHasKey('schema',
            $filtersParam['content']['application/x-www-form-urlencoded'],
            'schema must be nested under content.application/x-www-form-urlencoded');
    }

    public function test_enhanced_tag_fields_appear_in_x_luminous_tags(): void
    {
        $tagRegistry = new TagRegistry;
        $extractor = $this->makeExtractor(tagRegistry: $tagRegistry);
        $route = new ExtractedRoute(
            httpMethod: 'post',
            path: '/test',
            controllerClass: TestAttributeController::class,
            methodName: 'store',
            routeName: 'test.store',
            middlewares: [],
        );

        $extractor->extract($route);

        $tagObj = collect($tagRegistry->all())->firstWhere('name', 'Test');
        $this->assertNotNull($tagObj);
        $this->assertSame('Test endpoints', $tagObj['summary']);
        $this->assertSame('internal', $tagObj['kind']);
    }

    public function test_api_stream_does_not_overwrite_api_response_at_same_status(): void
    {
        Log::spy();

        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/conflict',
            controllerClass: TestAttributeController::class,
            methodName: 'streamWithConflict',
            routeName: 'stream.conflict',
            middlewares: [],
        );

        $op = $extractor->extract($route);

        // ApiResponse(200) is description-only, so it has no content key
        $this->assertSame('Regular JSON response', $op['responses']['200']['description'],
            'ApiResponse must win; description must come from ApiResponse not ApiStream');
        $this->assertArrayNotHasKey('content', $op['responses']['200'],
            'ApiStream must not inject content when the status is already occupied by ApiResponse');
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($msg) => str_contains((string) $msg, 'conflicts with'));
    }

    public function test_api_example_on_streaming_response_is_applied(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/stream',
            controllerClass: TestAttributeController::class,
            methodName: 'eventStream',
            routeName: 'stream.events',
            middlewares: [],
        );

        $op = $extractor->extract($route);

        $examples = $op['responses']['200']['content']['text/event-stream']['examples'] ?? [];
        $this->assertArrayHasKey('payment-event', $examples,
            'ApiExample targeting a streaming response must be applied when itemSchema is present');
        $this->assertSame('Sample event', $examples['payment-event']['summary']);
        $this->assertSame(['event' => 'payment.succeeded'], $examples['payment-event']['value']);
    }

    public function test_api_query_invalid_location_falls_back_to_query_with_warning(): void
    {
        Log::spy();

        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/search',
            controllerClass: TestAttributeController::class,
            methodName: 'queryWithInvalidLocation',
            routeName: 'search.index',
            middlewares: [],
        );

        $op = $extractor->extract($route);

        $param = collect($op['parameters'] ?? [])->firstWhere('name', 'q');
        $this->assertNotNull($param);
        $this->assertSame('query', $param['in'],
            'Invalid location must fall back to query');
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($msg) => str_contains((string) $msg, "invalid location 'invalid'"));
    }

    public function test_deprecated_api_param_emits_deprecated_flag(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/deprecated/{id}',
            controllerClass: TestAttributeController::class,
            methodName: 'deprecatedPathParam',
            routeName: 'deprecated.param',
            middlewares: [],
        );

        $op = $extractor->extract($route);

        $idParam = collect($op['parameters'] ?? [])->firstWhere('name', 'id');
        $this->assertNotNull($idParam);
        $this->assertTrue($idParam['deprecated'] ?? false,
            'ApiParam with deprecated:true must emit deprecated flag');
    }

    public function test_non_deprecated_api_param_has_no_deprecated_key(): void
    {
        $route = new ExtractedRoute('get', '/orders/{orderId}', OrderController::class, 'show', 'order.show', []);
        $op = $this->makeExtractor()->extract($route);

        $orderId = collect($op['parameters'] ?? [])->firstWhere('name', 'orderId');

        $this->assertNotNull($orderId, 'orderId must be present as an auto-detected path param');
        $this->assertArrayNotHasKey('deprecated', $orderId,
            'auto-detected path params must not carry a deprecated key');
    }

    public function test_api_example_description_appears_in_request_body_examples(): void
    {
        $op = $this->makeExtractor()->extract($this->route('post', '/v1/payments', 'store'));

        $example = $op['requestBody']['content']['application/json']['examples']['usd-payment'] ?? null;
        $this->assertNotNull($example);
        $this->assertSame('A payment of $100.00 in USD', $example['description'],
            'ApiExample description must be emitted when set');
    }

    public function test_parent_tag_field_appears_in_x_luminous_tags(): void
    {
        $tagRegistry = new TagRegistry;
        $extractor = $this->makeExtractor(tagRegistry: $tagRegistry);
        $route = new ExtractedRoute(
            httpMethod: 'get',
            path: '/invoices',
            controllerClass: DanglingTagController::class,
            methodName: 'index',
            routeName: 'invoices.index',
            middlewares: [],
        );

        $extractor->extract($route);

        $tagObj = collect($tagRegistry->all())->firstWhere('name', 'Invoices');
        $this->assertNotNull($tagObj);
        $this->assertSame('Billing', $tagObj['parent'] ?? null,
            'parent field from #[ApiTag] must survive buildTags() into TagRegistry');
    }

    public function test_duplicate_tag_names_merge_fields_from_both_levels(): void
    {
        $tagRegistry = new TagRegistry;
        $extractor = $this->makeExtractor(tagRegistry: $tagRegistry);
        $route = new ExtractedRoute(
            httpMethod: 'post',
            path: '/test',
            controllerClass: TestAttributeController::class,
            methodName: 'store',
            routeName: 'test.store',
            middlewares: [],
        );

        $extractor->extract($route);

        // class-level: ApiTag('Test', summary:'Test endpoints', kind:'internal')
        // method-level: ApiTag('Test', description:'Method override')
        // merged: all three fields must appear in a single tag object
        $tagObj = collect($tagRegistry->all())->firstWhere('name', 'Test');
        $this->assertNotNull($tagObj);
        $this->assertSame('Test endpoints', $tagObj['summary'] ?? null,
            'summary from class-level tag must survive merge');
        $this->assertSame('internal', $tagObj['kind'] ?? null,
            'kind from class-level tag must survive merge');
        $this->assertSame('Method override', $tagObj['description'] ?? null,
            'description from method-level tag must be merged in');
        $this->assertCount(1, $tagRegistry->all(),
            'duplicate tag names must be collapsed into one object');
    }

    public function test_response_headers_appear_on_correct_status(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test', TestAttributeController::class, 'rateLimited', 'test.rate', []);

        $operation = $extractor->extract($route);

        $headers = $operation['responses']['200']['headers'] ?? [];
        $this->assertArrayHasKey('X-Rate-Limit-Remaining', $headers);
        $this->assertSame('integer', $headers['X-Rate-Limit-Remaining']['schema']['type']);
        $this->assertSame('Remaining requests in window', $headers['X-Rate-Limit-Remaining']['description']);
        $this->assertArrayHasKey('X-Request-Id', $headers);
        $this->assertSame('uuid', $headers['X-Request-Id']['schema']['format']);
    }

    public function test_response_header_on_missing_status_is_skipped_with_warning(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test', TestAttributeController::class, 'orphanResponseHeader', 'test.orphan', []);

        $warnings = [];
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $msg) use (&$warnings) {
                $warnings[] = $msg;

                return str_contains($msg, 'X-Orphan') && str_contains($msg, '999');
            });

        $operation = $extractor->extract($route);

        $this->assertArrayNotHasKey('headers', $operation['responses']['200'] ?? []);
    }

    public function test_api_operation_external_docs_emitted(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test', TestAttributeController::class, 'rateLimited', 'test.rate', []);

        $operation = $extractor->extract($route);

        $this->assertArrayHasKey('externalDocs', $operation);
        $this->assertSame('https://example.com/docs', $operation['externalDocs']['url']);
        $this->assertSame('Rate limit docs', $operation['externalDocs']['description']);
    }

    public function test_api_query_style_deep_object_emitted(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test', TestAttributeController::class, 'queryWithDeepObject', 'test.deep', []);

        $operation = $extractor->extract($route);

        $param = collect($operation['parameters'])->firstWhere('name', 'ids');
        $this->assertNotNull($param);
        $this->assertSame('deepObject', $param['style']);
        $this->assertTrue($param['explode']);
    }

    public function test_api_query_invalid_style_logs_warning_and_omits_style(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $msg) => str_contains($msg, 'badStyle') && str_contains($msg, 'tags'));

        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test', TestAttributeController::class, 'queryWithInvalidStyle', 'test.badstyle', []);

        $operation = $extractor->extract($route);

        $param = collect($operation['parameters'])->firstWhere('name', 'tags');
        $this->assertNotNull($param);
        $this->assertArrayNotHasKey('style', $param);
    }

    public function test_explode_false_emitted_on_query(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test', TestAttributeController::class, 'queryWithExplodeFalse', 'test.explode', []);

        $operation = $extractor->extract($route);

        $param = collect($operation['parameters'])->firstWhere('name', 'page');
        $this->assertNotNull($param);
        $this->assertFalse($param['explode']);
        $this->assertArrayNotHasKey('style', $param);
    }

    public function test_api_param_style_label_emitted(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test/{slug}', TestAttributeController::class, 'paramWithLabelStyle', 'test.label', []);

        $operation = $extractor->extract($route);

        $param = collect($operation['parameters'])->firstWhere('name', 'slug');
        $this->assertNotNull($param);
        $this->assertSame('label', $param['style']);
    }

    public function test_api_header_style_simple_emitted(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test', TestAttributeController::class, 'headerWithSimpleStyle', 'test.header', []);

        $operation = $extractor->extract($route);

        $param = collect($operation['parameters'])->firstWhere('name', 'X-Trace-Id');
        $this->assertNotNull($param);
        $this->assertSame('simple', $param['style']);
    }

    public function test_style_and_explode_not_emitted_for_querystring_location(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test', TestAttributeController::class, 'queryWithQuerystringAndStyle', 'test.qs', []);

        $operation = $extractor->extract($route);

        $param = collect($operation['parameters'])->firstWhere('name', 'filter');
        $this->assertNotNull($param);
        $this->assertArrayNotHasKey('style', $param);
        $this->assertArrayNotHasKey('explode', $param);
        $this->assertArrayHasKey('content', $param);
    }

    public function test_api_query_invalid_style_for_cookie_location_warns_and_omits(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $msg) => str_contains($msg, 'pipeDelimited') && str_contains($msg, 'cookie'));

        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test', TestAttributeController::class, 'queryWithCookieInvalidStyle', 'test.cookie', []);

        $operation = $extractor->extract($route);

        $param = collect($operation['parameters'])->firstWhere('name', 'token');
        $this->assertNotNull($param);
        $this->assertArrayNotHasKey('style', $param);
    }

    public function test_api_query_valid_style_for_cookie_location_emitted(): void
    {
        $extractor = $this->makeExtractor();
        $route = new ExtractedRoute('get', '/test', TestAttributeController::class, 'queryWithCookieValidStyle', 'test.cookieok', []);

        $operation = $extractor->extract($route);

        $param = collect($operation['parameters'])->firstWhere('name', 'session');
        $this->assertNotNull($param);
        $this->assertSame('form', $param['style']);
    }
}
