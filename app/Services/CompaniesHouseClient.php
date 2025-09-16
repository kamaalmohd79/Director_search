<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CompaniesHouseClient
{
    protected string $base;
    protected string $key;

    public function __construct()
    {
        $this->base = env('COMPANIES_HOUSE_BASE', 'https://api.company-information.service.gov.uk');
        $this->key  = trim((string) env('COMPANIES_HOUSE_API_KEY'));
    }

    protected function authHeaders(): array
    {
        // Companies House uses HTTP Basic: username = API key, password = "" (empty)
        $auth = 'Basic ' . base64_encode($this->key . ':');
        return ['Authorization' => $auth, 'Accept' => 'application/json'];
    }

    public function searchOfficers(string $name, int $limit = 20, int $start = 0): array
    {
        $res = Http::withHeaders($this->authHeaders())
            // ->withOptions(['verify' => false]) // uncomment if you need to bypass local SSL
            ->get($this->base . '/search/officers', [
                'q'             => $name,
                'items_per_page'=> $limit,
                'start_index'   => $start,
            ]);

        \Log::debug('CH /search/officers', ['status' => $res->status()]);
        $res->throw();

        $items = $res->json()['items'] ?? [];

        $out = [];
        foreach ($items as $it) {
            $title  = $it['title'] ?? '';
            $addr   = $it['address_snippet'] ?? '';

            // 1) postcode from structured field, fallback to snippet regex, then normalize
            $postal = $it['address']['postal_code'] ?? null;
            if (!$postal && $addr && preg_match('/\b[A-Z]{1,2}\d{1,2}[A-Z]?\s*\d[A-Z]{2}\b/i', $addr, $m)) {
                $postal = $m[0];
            }
            $postal = $postal ? strtoupper(trim($postal)) : null; // <- normalize

            $city   = $it['address']['locality'] ?? null;
            $house  = $it['address']['premises'] ?? null;

            // DOB: CH gives {year, month}
            $dob = null;
            if (!empty($it['date_of_birth'])) {
                $y = $it['date_of_birth']['year']  ?? null;
                $m = $it['date_of_birth']['month'] ?? null;
                $dob = $y ? ($m ? sprintf('%04d-%02d', $y, $m) : sprintf('%04d', $y)) : null;
            }

            // naive first/last split for the client grid
            $firstName = $lastName = null;
            if ($title) {
                $parts = preg_split('/\s+/', trim($title));
                if ($parts && count($parts) > 0) {
                    $firstName = $parts[0] ?? null;
                    $lastName  = count($parts) > 1 ? $parts[count($parts) - 1] : null;
                }
            }

            $self      = $it['links']['self'] ?? '';
            $officerId = $this->extractOfficerId($self);

            $out[] = [
                'id'             => $self ?: uniqid('off_'),
                'officer_id'     => $officerId,
                'name'           => trim($title),
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'address'        => $addr,
                'postcode'       => $postal,          // normalized
                'address_city'   => $city,
                'address_house'  => $house,
                'dob'            => $dob,

                // enrichment fields
                'is_director'             => false,
                'director_appointments'   => [],
                'current_director_count'  => 0,
                'resigned_director_count' => 0,

                // geocoding placeholders — Python fills these
                'lat'         => null,
                'lng'         => null,
                'geo_status'  => null,  // 'ok' | 'missing' | 'failed'
            ];
        }

        return $out;
    }

    protected function extractOfficerId(?string $self): ?string
    {
        if (!$self) return null;
        if (preg_match('#/officers/([^/]+)/appointments#', $self, $m)) {
            return $m[1];
        }
        return null;
    }

    public function getOfficerAppointments(string $officerId): array
    {
        $res = Http::withHeaders($this->authHeaders())
            ->get($this->base . '/officers/' . $officerId . '/appointments');

        \Log::debug('CH /officers/{id}/appointments', ['id' => $officerId, 'status' => $res->status()]);
        $res->throw();
        return $res->json();
    }

    public function enrichDirectors(array $officers, int $maxLookups = 10): array
    {
        $count = 0;

        foreach ($officers as &$o) {
            $officerId = $o['officer_id'] ?? null;
            if (!$officerId || $count >= $maxLookups) continue;

            try {
                $payload = $this->getOfficerAppointments($officerId);
                $apps    = $payload['items'] ?? [];

                // keep only director roles (includes 'director' & 'corporate-director')
                $directorApps = array_values(array_filter($apps, function ($app) {
                    $role = strtolower($app['officer_role'] ?? '');
                    return str_contains($role, 'director');
                }));

                if (!empty($directorApps)) {
                    $o['is_director'] = true;

                    $mapped = array_map(function ($app) {
                        $companyName   = $app['appointed_to']['company_name']   ?? null;
                        $companyNumber = $app['appointed_to']['company_number'] ?? null;
                        $appointedOn   = $app['appointed_on'] ?? null;
                        $resignedOn    = $app['resigned_on']  ?? null;
                        $status        = $resignedOn ? 'resigned' : 'current';

                        return [
                            'company_name'   => $companyName,
                            'company_number' => $companyNumber,
                            'officer_role'   => $app['officer_role'] ?? null,
                            'appointed_on'   => $appointedOn,
                            'resigned_on'    => $resignedOn,
                            'status'         => $status,
                        ];
                    }, $directorApps);

                    $o['director_appointments']   = $mapped;
                    $o['current_director_count']   = count(array_filter($mapped, fn ($m) => $m['status'] === 'current'));
                    $o['resigned_director_count']  = count(array_filter($mapped, fn ($m) => $m['status'] === 'resigned'));
                }

                $count++;
            } catch (\Throwable $e) {
                \Log::warning('Officer enrichment failed', ['id' => $officerId, 'e' => $e->getMessage()]);
            }
        }

        return $officers;
    }
}
