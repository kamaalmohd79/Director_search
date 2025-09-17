<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PythonGeoService
{
    protected string $baseUrl;

    public function __construct()
    {
        // Default Flask service URL
        $this->baseUrl = env('PYTHON_GEO_URL', 'http://127.0.0.1:5000');
    }

    public function computeDistances(array $officers): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/compute", [
                'officers' => $officers,
            ]);

            if ($response->failed()) {
                return $this->fail("Flask service responded with HTTP {$response->status()}");
            }

            $data = $response->json();

            if (!is_array($data)) {
                return $this->fail("Invalid JSON response from Flask", [
                    'body' => $response->body(),
                ]);
            }

            return [
                'officers'        => $data['officers'] ?? $officers,
                'distance_matrix' => $data['distance_matrix'] ?? [],
                'stats'           => array_merge([
                    'count_pairs' => 0,
                    'min_km'      => null,
                    'avg_km'      => null,
                    'max_km'      => null,
                ], $data['stats'] ?? []),
                'pairs'           => $data['pairs'] ?? [],
            ];
        } catch (\Throwable $e) {
            return $this->fail("Flask request failed", [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    protected function fail(string $error, array $extra = []): array
    {
        return [
            'officers'        => [],
            'distance_matrix' => [],
            'pairs'           => [],
            'stats'           => array_merge([
                'count_pairs' => 0,
                'min_km'      => null,
                'avg_km'      => null,
                'max_km'      => null,
                'error'       => $error,
            ], $extra),
        ];
    }
}
