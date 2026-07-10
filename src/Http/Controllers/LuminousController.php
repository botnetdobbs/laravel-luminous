<?php

namespace Botnetdobbs\Luminous\Http\Controllers;

use Botnetdobbs\Luminous\Contracts\OpenApiGeneratorContract;
use Botnetdobbs\Luminous\Support\CacheManager;
use Botnetdobbs\Luminous\Support\YamlExporter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class LuminousController extends Controller
{
    public function __construct(
        private readonly OpenApiGeneratorContract $generator,
        private readonly CacheManager $cache,
        private readonly YamlExporter $yaml,
    ) {}

    public function json(): JsonResponse
    {
        return response()
            ->json($this->getSpec())
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function yaml(): Response
    {
        try {
            $output = $this->yaml->export($this->getSpec());

            return response($output, 200, [
                'Content-Type' => 'application/yaml; charset=utf-8',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
            ]);
        } catch (\RuntimeException $e) {
            logger()->warning('luminous: YAML export failed', ['error' => $e->getMessage()]);

            return response(
                'YAML export is unavailable. Run: composer require symfony/yaml',
                501,
                [
                    'Content-Type' => 'text/plain',
                    'X-Content-Type-Options' => 'nosniff',
                ]
            );
        }
    }

    public function ui(): Response
    {
        $drivers = config('luminous.ui.drivers', []);
        $driver = config('luminous.ui.driver', 'swagger');

        if (! isset($drivers[$driver])) {
            logger()->warning("Luminous: unknown UI driver [{$driver}], falling back to swagger.");
            $driver = 'swagger';
        }

        $driverCfg = $drivers[$driver];
        $cdnUrl = $driverCfg['cdn'];

        $allowedCdnHosts = ['unpkg.com', 'cdn.jsdelivr.net', 'cdnjs.cloudflare.com'];
        $cdnHost = parse_url((string) $cdnUrl, PHP_URL_HOST) ?? '';
        if (! in_array($cdnHost, $allowedCdnHosts, true)) {
            logger()->warning("Luminous: CDN host [{$cdnHost}] not in allowlist, falling back to swagger.");
            $driver = 'swagger';
            $internalDrivers = require __DIR__.'/../../config/luminous-ui.php';
            $driverCfg = $internalDrivers['swagger'];
            $cdnUrl = $driverCfg['cdn'];
        }

        $nonce = base64_encode(random_bytes(16));
        $csp = $this->buildCspHeader((string) $cdnUrl, $nonce, $driver);
        $viewName = $driverCfg['view'] ?? $driver;

        return response()
            ->view("luminous::{$viewName}", [
                'title' => config('luminous.info.title'),
                'specUrl' => route('luminous.json'),
                'driverCfg' => $driverCfg,
                'driverOptions' => config("luminous.ui.{$driver}", []),
                'nonce' => $nonce,
            ])
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('Content-Security-Policy', $csp);
    }

    private function buildCspHeader(string $cdnBase, string $nonce, string $driver = 'swagger'): string
    {
        $parsed = parse_url($cdnBase);
        $cdnOrigin = isset($parsed['host']) ? (($parsed['scheme'] ?? 'https').'://'.$parsed['host']) : '';

        $needsUnsafeInline = in_array($driver, ['redoc', 'scalar'], true);

        $styleSrc = $needsUnsafeInline
            ? "style-src 'self' {$cdnOrigin} 'unsafe-inline'"
            : "style-src 'self' {$cdnOrigin} 'nonce-{$nonce}'";

        $workerSrc = ($driver === 'redoc') ? 'worker-src blob:' : "worker-src 'none'";

        return "default-src 'none'; script-src 'self' {$cdnOrigin} 'nonce-{$nonce}'; ".
               "{$styleSrc}; {$workerSrc}; ".
               "img-src 'self' data:; connect-src 'self'; font-src 'self' data:; ".
               "base-uri 'self'; object-src 'none'; form-action 'self'; ".
               "frame-ancestors 'self'";
    }

    private function getSpec(): array
    {
        if (! config('luminous.cache.enabled')) {
            return $this->generator->generate();
        }

        $resolve = fn (): array => $this->cache->get()
            ?? tap($this->generator->generate(), fn ($s) => $this->cache->put($s));

        $store = cache()->store(config('luminous.cache.store'))->getStore();
        if (! $store instanceof LockProvider) {
            return $resolve();
        }

        return $store
            ->lock('luminous:generating', 30)
            ->block(10, $resolve);
    }
}
