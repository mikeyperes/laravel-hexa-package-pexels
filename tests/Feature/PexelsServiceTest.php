<?php

namespace Tests\Feature;

use hexa_package_pexels\Services\PexelsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PexelsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireInstalledPackage("hexawebsystems/laravel-hexa-package-pexels", PexelsService::class);
    }

    public function test_api_key_probe_uses_provider_endpoint(): void
    {
        Http::fake(["*api.pexels.com/*" => Http::response(["photos" => []], 200)]);

        $result = app(PexelsService::class)->testApiKey("test-key");

        $this->assertTrue($result["success"]);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), "api.pexels.com/v1/search"));
    }
}
