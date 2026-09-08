<?php

namespace hexa_package_pexels\Services;

use hexa_core\Models\Setting;
use hexa_core\Security\Http\OutboundHttpException;
use hexa_core\Security\Http\OutboundHttpResponse;
use hexa_core\Security\Http\SafeOutboundHttpClient;
use Illuminate\Support\Facades\Log;

class PexelsService
{
    private const SEARCH_ENDPOINT = 'https://api.pexels.com/v1/search';

    private const PROBE_TIMEOUT_SECONDS = 10;

    private const SEARCH_TIMEOUT_SECONDS = 15;

    private const PROBE_MAX_RESPONSE_BYTES = 512 * 1024;

    private const SEARCH_MAX_RESPONSE_BYTES = 4 * 1024 * 1024;

    private const MAX_PER_PAGE = 80;

    public function __construct(private readonly SafeOutboundHttpClient $http) {}

    /** @return array{success: bool, message: string} */
    public function testApiKey(?string $apiKey = null): array
    {
        $key = $this->validApiKey($apiKey ?? $this->getApiKey());
        if ($key === null) {
            return ['success' => false, 'message' => 'No Pexels API key configured.'];
        }

        try {
            $response = $this->execute($this->request(
                $key,
                ['query' => 'test', 'per_page' => 1, 'page' => 1],
                self::PROBE_TIMEOUT_SECONDS,
                self::PROBE_MAX_RESPONSE_BYTES,
            ));
        } catch (OutboundHttpException $exception) {
            $this->logTransportFailure('probe', $exception);

            return ['success' => false, 'message' => 'Pexels API key validation failed safely.'];
        }

        if ($response->successful()) {
            $remaining = $response->headerValues('x-ratelimit-remaining')[0] ?? '?';

            return ['success' => true, 'message' => "Pexels API key is valid. Rate limit remaining: {$remaining}."];
        }

        if ($response->status === 401) {
            return ['success' => false, 'message' => 'Invalid API key.'];
        }

        return ['success' => false, 'message' => "Pexels returned HTTP {$response->status}."];
    }

    /**
     * Build the provider-owned request contract used by direct and pooled searches.
     *
     * @return array{method: string, url: string, options: array<string, mixed>}|null
     */
    public function searchRequest(
        string $query,
        int $perPage = 15,
        int $page = 1,
        ?string $apiKey = null,
    ): ?array {
        $key = $this->validApiKey($apiKey ?? $this->getApiKey());
        $query = $this->normalizeQuery($query);
        if ($key === null || $query === '') {
            return null;
        }

        return $this->request(
            $key,
            [
                'query' => $query,
                'per_page' => max(1, min($perPage, self::MAX_PER_PAGE)),
                'page' => max(1, min($page, 1000)),
            ],
            self::SEARCH_TIMEOUT_SECONDS,
            self::SEARCH_MAX_RESPONSE_BYTES,
        );
    }

    /** @return array{success: bool, message: string, data: array|null} */
    public function searchPhotos(string $query, int $perPage = 15, int $page = 1): array
    {
        if ($this->normalizeQuery($query) === '') {
            return ['success' => false, 'message' => 'A Pexels search query is required.', 'data' => null];
        }

        $request = $this->searchRequest($query, $perPage, $page);
        if ($request === null) {
            return ['success' => false, 'message' => 'No Pexels API key configured.', 'data' => null];
        }

        try {
            return $this->parseSearchResponse($this->execute($request));
        } catch (OutboundHttpException $exception) {
            $this->logTransportFailure('search', $exception);

            return $this->failedSearch('Pexels request failed safely.');
        }
    }

    /**
     * Normalize a pooled provider response without exposing provider or transport detail.
     *
     * @return array{success: bool, message: string, data: array|null}
     */
    public function parseSearchResponse(OutboundHttpResponse|OutboundHttpException|null $response): array
    {
        if ($response instanceof OutboundHttpException || ! $response instanceof OutboundHttpResponse) {
            return $this->failedSearch('Pexels request failed safely.');
        }

        if (! $response->successful()) {
            return $this->failedSearch("Pexels returned HTTP {$response->status}.");
        }

        $payload = $response->json();
        if (! is_array($payload) || ! is_array($payload['photos'] ?? [])) {
            return $this->failedSearch('Pexels returned an invalid response.');
        }

        $photos = [];
        foreach ($payload['photos'] as $photo) {
            if (! is_array($photo)) {
                continue;
            }

            $src = is_array($photo['src'] ?? null) ? $photo['src'] : [];
            $sourceUrl = is_string($photo['url'] ?? null) ? $photo['url'] : '';
            $photos[] = [
                'source' => 'pexels',
                'id' => $photo['id'] ?? null,
                'url_thumb' => $src['medium'] ?? $src['small'] ?? $src['large'] ?? null,
                'url_full' => $src['original'] ?? null,
                'url_large' => $src['large2x'] ?? $src['large'] ?? $src['medium'] ?? null,
                'source_url' => $sourceUrl,
                'pexels_url' => $sourceUrl,
                'alt' => is_string($photo['alt'] ?? null) ? $photo['alt'] : '',
                'photographer' => is_string($photo['photographer'] ?? null) ? $photo['photographer'] : '',
                'photographer_url' => is_string($photo['photographer_url'] ?? null) ? $photo['photographer_url'] : '',
                'width' => max(0, (int) ($photo['width'] ?? 0)),
                'height' => max(0, (int) ($photo['height'] ?? 0)),
            ];
        }

        return [
            'success' => true,
            'message' => count($photos).' photos found.',
            'data' => [
                'photos' => $photos,
                'total' => max(0, (int) ($payload['total_results'] ?? 0)),
                'page' => max(1, (int) ($payload['page'] ?? 1)),
            ],
        ];
    }

    private function getApiKey(): ?string
    {
        return Setting::getValue('pexels_api_key');
    }

    /**
     * @param  array<string, string|int>  $query
     * @return array{method: string, url: string, options: array<string, mixed>}
     */
    private function request(string $apiKey, array $query, int $timeout, int $maxBytes): array
    {
        return [
            'method' => 'GET',
            'url' => self::SEARCH_ENDPOINT.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            'options' => [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => $apiKey,
                ],
                'timeout' => $timeout,
                'max_bytes' => $maxBytes,
                'max_redirects' => 0,
            ],
        ];
    }

    /** @param array{method: string, url: string, options: array<string, mixed>} $request */
    private function execute(array $request): OutboundHttpResponse
    {
        return $this->http->request($request['method'], $request['url'], $request['options']);
    }

    private function normalizeQuery(string $query): string
    {
        $query = preg_replace('/\s+/u', ' ', trim($query)) ?? '';

        return mb_substr($query, 0, 255);
    }

    private function validApiKey(?string $apiKey): ?string
    {
        $apiKey = trim((string) $apiKey);

        return $apiKey !== ''
            && strlen($apiKey) <= 4096
            && preg_match('/[\x00-\x1f\x7f]/', $apiKey) !== 1
                ? $apiKey
                : null;
    }

    /** @return array{success: false, message: string, data: null} */
    private function failedSearch(string $message): array
    {
        return ['success' => false, 'message' => $message, 'data' => null];
    }

    private function logTransportFailure(string $operation, OutboundHttpException $exception): void
    {
        Log::warning('Pexels request failed safely', [
            'operation' => $operation,
            'failure_code' => $exception->failureCode(),
        ]);
    }
}
