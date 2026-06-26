<?php

namespace Botnetdobbs\Luminous\Tests\Feature;

use Botnetdobbs\Luminous\Extractors\EnumExtractor;
use Botnetdobbs\Luminous\Extractors\RequestExtractor;
use Botnetdobbs\Luminous\Extractors\ResourceExtractor;
use Botnetdobbs\Luminous\Generator\ComponentsRegistry;
use Botnetdobbs\Luminous\LuminousServiceProvider;
use Botnetdobbs\Luminous\Support\TypeMapper;
use Botnetdobbs\Luminous\Tests\Fixtures\Enums\PaymentStatus;
use Botnetdobbs\Luminous\Tests\Fixtures\Requests\AddressRequest;
use Botnetdobbs\Luminous\Tests\Fixtures\Requests\CreatePaymentRequest;
use Botnetdobbs\Luminous\Tests\Fixtures\Requests\ThrowingRequest;
use Botnetdobbs\Luminous\Tests\Fixtures\Requests\UnionTypeRequest;
use Botnetdobbs\Luminous\Tests\Fixtures\Resources\PaymentResource;
use Botnetdobbs\Luminous\Tests\Fixtures\Resources\TreeNodeResource;
use Orchestra\Testbench\TestCase;

class SchemaExtractorsTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LuminousServiceProvider::class];
    }

    private function makeRegistry(): ComponentsRegistry
    {
        return new ComponentsRegistry;
    }

    private function makeRequestExtractor(ComponentsRegistry $registry): RequestExtractor
    {
        $enumExtractor = new EnumExtractor;
        $typeMapper = new TypeMapper($enumExtractor);

        return new RequestExtractor($typeMapper, $registry, $enumExtractor);
    }

    private function makeResourceExtractor(ComponentsRegistry $registry): ResourceExtractor
    {
        $enumExtractor = new EnumExtractor;
        $typeMapper = new TypeMapper($enumExtractor);

        return new ResourceExtractor($typeMapper, $registry, $enumExtractor);
    }

    public function test_request_extractor_registers_schema_and_returns_ref(): void
    {
        $registry = $this->makeRegistry();
        $extractor = $this->makeRequestExtractor($registry);

        $result = $extractor->extract(CreatePaymentRequest::class);

        $this->assertSame(['$ref' => '#/components/schemas/CreatePaymentRequest'], $result);
        $this->assertArrayHasKey('CreatePaymentRequest', $registry->all());
        $this->assertSame('object', $registry->all()['CreatePaymentRequest']['type']);
    }

    public function test_request_extractor_is_idempotent(): void
    {
        $registry = $this->makeRegistry();
        $extractor = $this->makeRequestExtractor($registry);

        $first = $extractor->extract(CreatePaymentRequest::class);
        $second = $extractor->extract(CreatePaymentRequest::class);

        $this->assertSame($first, $second);
        $this->assertCount(2, $registry->all()); // CreatePaymentRequest + PaymentStatus enum
    }

    public function test_resource_extractor_registers_schema_and_returns_ref(): void
    {
        $registry = $this->makeRegistry();
        $extractor = $this->makeResourceExtractor($registry);

        $result = $extractor->extract(PaymentResource::class);

        $this->assertSame(['$ref' => '#/components/schemas/PaymentResource'], $result);
        $this->assertArrayHasKey('PaymentResource', $registry->all());
    }

    public function test_backed_enum_property_produces_ref_to_enum_schema(): void
    {
        $registry = $this->makeRegistry();
        $extractor = $this->makeResourceExtractor($registry);

        $extractor->extract(PaymentResource::class);

        $schemas = $registry->all();
        $this->assertArrayHasKey('PaymentStatus', $schemas);

        $props = $schemas['PaymentResource']['properties'];
        $this->assertArrayHasKey('$ref', $props['status']);
        $this->assertSame('#/components/schemas/PaymentStatus', $props['status']['$ref']);
    }

    public function test_enum_extractor_produces_correct_schema(): void
    {
        $extractor = new EnumExtractor;
        $schema = $extractor->extract(PaymentStatus::class);

        $this->assertSame('string', $schema['type']);
        $this->assertContains('succeeded', $schema['enum']);
        $this->assertCount(5, $schema['enum']);
    }

    public function test_dot_notation_rules_produce_nested_object(): void
    {
        $registry = $this->makeRegistry();
        $extractor = $this->makeRequestExtractor($registry);

        // Force rules-based extraction by using AddressRequest (no #[ApiProperty] annotations)
        $extractor->extract(AddressRequest::class);

        $schema = $registry->all()['AddressRequest'];
        $this->assertArrayHasKey('address', $schema['properties']);
        $this->assertSame('object', $schema['properties']['address']['type']);
        $this->assertArrayHasKey('street', $schema['properties']['address']['properties']);
    }

    public function test_components_registry_reset_clears_all_schemas(): void
    {
        $registry = $this->makeRegistry();
        $registry->registerAnonymous('Test', ['type' => 'string']);
        $this->assertNotEmpty($registry->all());

        $registry->reset();
        $this->assertEmpty($registry->all());
    }

    public function test_self_referential_resource_does_not_stack_overflow(): void
    {
        $registry = $this->makeRegistry();
        $extractor = $this->makeResourceExtractor($registry);

        $result = $extractor->extract(TreeNodeResource::class);

        $this->assertSame(['$ref' => '#/components/schemas/TreeNodeResource'], $result);
        $this->assertArrayHasKey('TreeNodeResource', $registry->all());
    }

    public function test_annotated_json_resource_property_is_not_overwritten_by_secondary_loop(): void
    {
        $registry = $this->makeRegistry();
        $extractor = $this->makeResourceExtractor($registry);

        $extractor->extract(TreeNodeResource::class);

        $schema = $registry->all()['TreeNodeResource'];
        // $annotated has #[ApiProperty(ref: '#/components/schemas/CustomSchema')] — annotation must win
        $this->assertSame(['$ref' => '#/components/schemas/CustomSchema'], $schema['properties']['annotated']);
    }

    public function test_union_type_property_does_not_crash(): void
    {
        $registry = $this->makeRegistry();
        $extractor = $this->makeRequestExtractor($registry);

        $result = $extractor->extract(UnionTypeRequest::class);

        $this->assertSame(['$ref' => '#/components/schemas/UnionTypeRequest'], $result);
        $schema = $registry->all()['UnionTypeRequest'];
        // Union type int|float maps to 'mixed' → empty schema fragment — property still present
        $this->assertArrayHasKey('amount', $schema['properties']);
    }

    public function test_rules_exception_falls_back_to_empty_schema(): void
    {
        $registry = $this->makeRegistry();
        $extractor = $this->makeRequestExtractor($registry);

        $result = $extractor->extract(ThrowingRequest::class);

        $this->assertSame(['$ref' => '#/components/schemas/ThrowingRequest'], $result);
        $schema = $registry->all()['ThrowingRequest'];
        $this->assertSame([], $schema['properties']);
    }

    public function test_register_anonymous_with_invalid_name_throws(): void
    {
        $registry = $this->makeRegistry();

        $this->expectException(\InvalidArgumentException::class);
        $registry->registerAnonymous('Invalid Name', ['type' => 'string']);
    }
}
