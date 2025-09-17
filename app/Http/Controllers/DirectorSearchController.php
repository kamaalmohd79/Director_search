<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use App\Http\Requests\DirectorSearchRequest;
use App\Models\SearchLog;
use App\Models\SearchResult;
use App\Services\CompaniesHouseClient;
use App\Services\PythonGeoService;
use Illuminate\Support\Facades\Log;

class DirectorSearchController extends Controller
{
    public function index()
    {
        $total = SearchLog::count();
        $today = SearchLog::whereDate('created_at', now()->toDateString())->count();

        return view('directors.search', compact('total', 'today'));
    }

    public function search(
        DirectorSearchRequest $req,
        CompaniesHouseClient $chs,
        PythonGeoService $geo
    ) {
        $data = $req->validated();

        // 1) log this search
        $log = SearchLog::create([
            'first_name' => $data['first_name'] ?? null,
            'surname'    => $data['surname'],
            'postcode'   => $data['postcode'] ?? null,
            'ip'         => $req->ip(),
        ]);

        $queryName     = trim(($data['first_name'] ?? '') . ' ' . $data['surname']);
        $perPage       = 20;
        $startIndex    = (int) $req->input('start_index', 0);
        $onlyDirectors = true;

        $officers = [];
        $errorMsg = null;

        // 2) CH search + director enrichment
        try {
            $officers = $chs->searchOfficers($queryName, $perPage, $startIndex);
            $officers = $chs->enrichDirectors($officers, maxLookups: 10);

            if ($onlyDirectors) {
                $officers = array_values(array_filter($officers, fn ($o) => !empty($o['is_director'])));
            }
        } catch (\Throwable $e) {
            Log::error('CompaniesHouse error', ['msg' => $e->getMessage()]);
            $errorMsg = 'Companies House API error: ' . $e->getMessage();
        }

        // 3) Python geolocation
        $distanceMatrix = [];
        $stats = [];
        $pairs = [];
        if (!$errorMsg && !empty($officers)) {
            try {
                $enriched       = $geo->computeDistances($officers);
                $officers       = $enriched['officers'] ?? $officers;
                $distanceMatrix = $enriched['distance_matrix'] ?? [];
                $stats          = $enriched['stats'] ?? [];
                $pairs          = $enriched['pairs'] ?? [];
            } catch (\Throwable $e) {
                Log::error('PythonGeoService failed', ['msg' => $e->getMessage()]);
                $stats = ['error' => 'Distance calculation temporarily unavailable'];
            }
        }

        // 4) Build rows for client columns + persist
        $rows = [];
        $searchedFullName = trim(($data['first_name'] ?? '') . ' ' . ($data['surname'] ?? ''));

        foreach ($officers as $o) {
            $peopleId   = $o['officer_id'] ?? null;
            $firstName  = $o['first_name'] ?? null;
            $lastName   = $o['last_name']  ?? null;
            $dob        = $o['dob']        ?? null;
            $apps = $o['director_appointments'] ?? [];

            if (empty($apps)) {
                $rows[] = [
                    'search_log_id'      => $log->id,
                    'searchedPeopleId'   => null,
                    'searchedFirstName'  => $data['first_name'] ?? null,
                    'searchedSurname'    => $data['surname'] ?? null,
                    'searchedFullName'   => $searchedFullName,
                    'searchedDOB'        => null,
                    'peopleId'           => $peopleId,
                    'score'              => null,
                    'firstName'          => $firstName,
                    'lastName'           => $lastName,
                    'status'             => null,
                    'dateOfBirth'        => $dob,
                    'localDirectorNumber'=> null,
                    'isOriginalDirector' => null,
                    'companyId'          => null,
                    'companyName'        => null,
                    'companyNumber'      => null,
                    'safeNumber'         => null,
                    'companyType'        => null,
                    'address'            => $o['address'] ?? null,
                    'addressCity'        => $o['address_city'] ?? null,
                    'addressPostCode'    => $o['postcode'] ?? null,
                    'addressHouseNo'     => $o['address_house'] ?? null,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
                continue;
            }

            foreach ($apps as $app) {
                $rows[] = [
                    'search_log_id'      => $log->id,
                    'searchedPeopleId'   => null,
                    'searchedFirstName'  => $data['first_name'] ?? null,
                    'searchedSurname'    => $data['surname'] ?? null,
                    'searchedFullName'   => $searchedFullName,
                    'searchedDOB'        => null,
                    'peopleId'           => $peopleId,
                    'score'              => null,
                    'firstName'          => $firstName,
                    'lastName'           => $lastName,
                    'status'             => $app['status'] ?? null,
                    'dateOfBirth'        => $dob,
                    'localDirectorNumber'=> null,
                    'isOriginalDirector' => null,
                    'companyId'          => null,
                    'companyName'        => $app['company_name'] ?? null,
                    'companyNumber'      => $app['company_number'] ?? null,
                    'safeNumber'         => null,
                    'companyType'        => null,
                    'address'            => $o['address'] ?? null,
                    'addressCity'        => $o['address_city'] ?? null,
                    'addressPostCode'    => $o['postcode'] ?? null,
                    'addressHouseNo'     => $o['address_house'] ?? null,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }
        }

        if (!empty($rows)) {
            SearchResult::insert($rows);
        }

        // 5) Render view
        return view('directors.results', [
            'query'          => $data,
            'officers'       => $officers,
            'distanceMatrix' => $distanceMatrix,
            'stats'          => $stats,
            'pairs'          => $pairs,   // NEW
            'start_index'    => $startIndex,
            'items_per_page' => $perPage,
            'error'          => $errorMsg,
            'grid'           => $rows,
        ]);
    }
}
