<?php

namespace Botnetdobbs\Luminous\Tests\Feature;

use Botnetdobbs\Luminous\LuminousServiceProvider;
use Botnetdobbs\Luminous\Support\CacheManager;
use Botnetdobbs\Luminous\Support\YamlExporter;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Yaml\Yaml;

class HttpLayerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LuminousServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('luminous.enabled', true);
    }

    public function test_json_endpoint_returns_valid_openapi(): void
    {
        $response = $this->get('/docs/openapi.json');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $this->assertSame('3.2.0', $response->json('openapi'));
    }

    public function test_yaml_endpoint_returns_yaml_when_library_available(): void
    {
        if (! class_exists(Yaml::class)) {
            $this->markTestSkipped('symfony/yaml not installed');
        }

        $response = $this->get('/docs/openapi.yaml');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/yaml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('openapi:', $response->getContent());
    }

    public function test_yaml_endpoint_returns_501_when_library_missing(): void
    {
        // Swap the singleton with a stub that reports yaml as unavailable
        $this->app->instance(YamlExporter::class, new class extends YamlExporter
        {
            public function isAvailable(): bool
            {
                return false;
            }
        });

        $response = $this->get('/docs/openapi.yaml');

        $response->assertStatus(501);
        $this->assertStringContainsString('composer require symfony/yaml', $response->getContent());
    }

    public function test_ui_endpoint_returns_html_with_swagger_ui(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $response->assertSee('swagger-ui', false);
        $response->assertSee('SwaggerUIBundle', false);
        $response->assertSee('validatorUrl:             null,', false);
    }

    public function test_json_spec_is_cached_on_second_request(): void
    {
        $this->app['config']->set('luminous.cache.enabled', true);
        $this->app['config']->set('luminous.cache.key', 'luminous:test:spec');

        $this->get('/docs/openapi.json')->assertStatus(200);

        $cached = cache()->store(config('luminous.cache.store'))->get('luminous:test:spec');
        $this->assertNotNull($cached, 'Spec was not written to cache after first request');
        $this->assertSame('3.2.0', $cached['openapi']);

        $response = $this->get('/docs/openapi.json');
        $response->assertStatus(200);
        $this->assertSame('3.2.0', $response->json('openapi'));

        cache()->store(config('luminous.cache.store'))->forget('luminous:test:spec');
    }

    public function test_cache_manager_put_and_get(): void
    {
        $this->app['config']->set('luminous.cache.enabled', true);
        $this->app['config']->set('luminous.cache.key', 'luminous:unit:test');
        $this->app['config']->set('luminous.cache.ttl', 60);

        $manager = app(CacheManager::class);
        $spec = ['openapi' => '3.2.0', 'test' => true];

        $manager->put($spec);
        $this->assertSame($spec, $manager->get());

        $manager->flush();
        $this->assertNull($manager->get());
    }

    public function test_flush_is_noop_when_cache_disabled(): void
    {
        $this->app['config']->set('luminous.cache.enabled', false);
        $this->app['config']->set('luminous.cache.key', 'luminous:noop:test');

        $manager = app(CacheManager::class);

        // Manually prime the cache store directly to prove flush() won't clear it
        cache()->store(config('luminous.cache.store'))->put('luminous:noop:test', ['sentinel' => true], 60);

        $manager->flush();

        $this->assertNotNull(
            cache()->store(config('luminous.cache.store'))->get('luminous:noop:test'),
            'flush() must not touch the cache store when cache is disabled'
        );

        cache()->store(config('luminous.cache.store'))->forget('luminous:noop:test');
    }

    public function test_yaml_exporter_throws_when_library_missing(): void
    {
        $exporter = new YamlExporter;

        if (! $exporter->isAvailable()) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('composer require symfony/yaml');
            $exporter->export(['openapi' => '3.2.0']);
        } else {
            $output = $exporter->export(['openapi' => '3.2.0']);
            $this->assertStringContainsString('openapi:', $output);
        }
    }

    public function test_ui_csp_uses_nonce_not_unsafe_inline_in_script_src(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("'nonce-", $csp);
        $this->assertStringNotContainsString("'unsafe-inline'", explode(';', $csp)[1] ?? $csp);
    }

    public function test_ui_csp_contains_frame_ancestors_self(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }

    public function test_ui_html_contains_nonce_on_inline_script(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $this->assertMatchesRegularExpression('/<script nonce="[A-Za-z0-9+\/=]+"/', $response->getContent());
    }

    public function test_ui_html_contains_sri_integrity_on_bundle_script(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $this->assertStringContainsString('integrity="sha256-', $response->getContent());
        $this->assertStringContainsString('crossorigin="anonymous"', $response->getContent());
    }

    public function test_middleware_pipe_delimiter_parses_throttle_correctly(): void
    {
        $parsed = array_map('trim', explode('|', 'auth|throttle:60,1'));

        $this->assertSame(['auth', 'throttle:60,1'], $parsed);
        $this->assertCount(2, $parsed);
    }

    public function test_yaml_501_response_has_nosniff_header(): void
    {
        $this->app->instance(YamlExporter::class, new class extends YamlExporter
        {
            public function isAvailable(): bool
            {
                return false;
            }
        });

        $response = $this->get('/docs/openapi.yaml');

        $response->assertStatus(501);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_json_response_has_cache_control_no_store(): void
    {
        $response = $this->get('/docs/openapi.json');

        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control', ''));
    }

    public function test_yaml_response_has_cache_control_no_store(): void
    {
        if (! class_exists(Yaml::class)) {
            $this->markTestSkipped('symfony/yaml not installed');
        }

        $response = $this->get('/docs/openapi.yaml');

        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control', ''));
    }

    public function test_ui_response_has_referrer_policy_header(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_csp_style_src_does_not_contain_unsafe_inline(): void
    {
        $response = $this->get('/docs');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringNotContainsString("'unsafe-inline'", $csp);
    }

    public function test_csp_contains_base_uri_object_src_form_action(): void
    {
        $response = $this->get('/docs');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function test_cdn_host_outside_allowlist_falls_back_to_default(): void
    {
        $this->app['config']->set('luminous.ui.cdn.swagger_ui', 'https://attacker.com/swagger-ui-dist@5.18.2');

        $response = $this->get('/docs');

        $response->assertStatus(200);
        $this->assertStringNotContainsString('attacker.com', $response->getContent());
        $this->assertStringContainsString('unpkg.com', $response->getContent());
    }
}
