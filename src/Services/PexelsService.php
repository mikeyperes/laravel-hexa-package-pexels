<?php

namespace hexa_package_pexels\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use hexa_core\Models\Setting;

class PexelsService
{
    /**
     * Get the API key from settings.
     *
     * @return string|null
     */
    private function getApiKey(): ?string
    {
        return Setting::getValue('pexels_api_key');
    }

    /**
     * Test the API key by making a small request.
     *
     * @param string|null $apiKey Override key to test (for settings page).
     * @return array{success: bool, message: string}
     */
    public function testApiKey(?string $apiKey = null): array
    {
        $key = $apiKey ?? $this->getApiKey();

        if (!$key) {
            return ['success' => false, 'message' => 'No Pexels API key configured.'];
        }

        try {
            $response = Http::withHeaders(['Authorization' => $key])
                ->timeout(10)
                ->get('https://api.pexels.com/v1/search', ['query' => 'test', 'per_page' => 1]);

            if ($response->successful()) {
                $remaining = $response->header('X-Ratelimit-Remaining') ?? '?';
                return ['success' => true, 'message' => "Pexels API key is valid. Rate limit remaining: {$remaining}."];
            }

            if ($response->status() === 401) {
                return ['success' => false, 'message' => 'Invalid API key.'];
            }

            return ['success' => false, 'message' => "Pexels returned HTTP {$response->status()}."];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Search for photos.
     *
     * @param string $query Search keywords.
     * @param int $perPage Results per page (max 80).
     * @param int $page Page number.
     * @return array{success: bool, message: string, data: array|null}
     */
    public function searchPhotos(string $query, int $perPage = 15, int $page = 1): array
    {
        $key = $this->getApiKey();

        if (!$key) {
            return ['success' => false, 'message' => 'No Pexels API key configured.', 'data' => null];
        }

        try {
            $response = Http::withHeaders(['Authorization' => $key])
                ->timeout(15)
                ->get('https://api.pexels.com/v1/search', [
                    'query' => $query,
                    'per_page' => min($perPage, 80),
                    'page' => $page,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $photos = collect($data['photos'] ?? [])->map(fn($p) => [
                    'source' => 'pexels',
                    'id' => $p['id'],
                    'url_thumb' => $p['src']['medium'] ?? $p['src']['small'],
                    'url_full' => $p['src']['original'],
                    'url_large' => $p['src']['large2x'] ?? $p['src']['large'],
                    'alt' => $p['alt'] ?? '',
                    'photographer' => $p['photographer'] ?? '',
                    'photographer_url' => $p['photographer_url'] ?? '',
                    'width' => $p['width'],
                    'height' => $p['height'],
                    'pexels_url' => $p['url'],
                ])->toArray();

                return [
                    'success' => true,
                    'message' => count($photos) . ' photos found.',
                    'data' => [
                        'photos' => $photos,
                        'total' => $data['total_results'] ?? 0,
                        'page' => $data['page'] ?? $page,
                    ],
                ];
            }

            return ['success' => false, 'message' => "Pexels returned HTTP {$response->status()}.", 'data' => null];

        } catch (\Exception $e) {
            Log::error('PexelsService::searchPhotos error', ['query' => $query, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => null];
        }
    }
}
