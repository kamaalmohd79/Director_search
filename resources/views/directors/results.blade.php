{{-- resources/views/directors/results.blade.php --}}
@extends('layouts.app')

@section('content')
<h2 class="mb-4">Results</h2>

@if(!empty($error))
<div class="alert alert-danger">{{ $error }}</div>
@endif

<div class="card mb-3">
  <div class="card-body">
    <div class="fw-semibold">Query:</div>
    {{ trim(($query['first_name'] ?? '').' '.($query['surname'] ?? '')) }}
  </div>
</div>

@php
// Pre-slice pairs once so we can reuse
$pairsTop10 = array_slice($pairs ?? [], 0, 10);

// Geocoding diagnostic counts
$withPc = collect($officers)->filter(fn($o) => !empty($o['postcode']))->count();
$okCount = collect($officers)->where('geo_status', 'ok')->count();
$invalid = collect($officers)->where('geo_status', 'invalid')->count();
$missing = collect($officers)->where('geo_status', 'missing')->count();
@endphp

<div class="row g-3">
  {{-- SUMMARY + DIAGNOSTICS --}}
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Summary</h5>
        <ul class="mb-0">
          <li>Total officers (directors only): {{ count($officers) }}</li>
          <li>Pairs: {{ $stats['count_pairs'] ?? 0 }}</li>
          <li>
            Min/Avg/Max (km):
            {{ isset($stats['min_km']) ? round($stats['min_km'], 2) : '—' }} /
            {{ isset($stats['avg_km']) ? round($stats['avg_km'], 2) : '—' }} /
            {{ isset($stats['max_km']) ? round($stats['max_km'], 2) : '—' }}
          </li>
        </ul>

        <div class="alert alert-info mt-3">
          <div class="fw-semibold mb-1">Geocoding diagnostics</div>
          <ul class="mb-0">
            <li>Officers with a postcode: {{ $withPc }}</li>
            <!-- <li>Geocoded OK: {{ $okCount }}</li>
            <li>Missing postcodes: {{ $missing }}</li>
            <li>Invalid postcodes: {{ $invalid }}</li> -->
            <li>Total pairs computed: {{ $stats['count_pairs'] ?? 0 }}</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  {{-- DIRECTORS TABLE --}}
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Directors</h5>
        <div class="table-responsive">
          <table class="table table-bordered table-sm">
            <tbody>
              @foreach($officers as $i => $o)
              <tr>
                <td>{{ $i+1 }}</td>
                <td>
                  {{ $o['name'] }}
                  <span class="badge bg-success ms-1">Director</span>
                  @php $gs = $o['geo_status'] ?? null; @endphp
                  @if($gs === 'ok')
                  <span class="badge bg-primary ms-1">ok</span>
                  @elseif($gs === 'invalid')
                  <span class="badge bg-warning text-dark ms-1">invalid</span>
                  @elseif($gs === 'missing')
                  <span class="badge bg-secondary ms-1">missing</span>
                  @endif
                </td>

                <td>{{ $o['postcode'] }}</td>
                <td>{{ $o['current_director_count'] ?? 0 }}</td>
                <td>{{ $o['resigned_director_count'] ?? 0 }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- CLOSEST PAIRS (TOP 10) --}}
@if(!empty($pairsTop10))
<div class="card mt-4">
  <div class="card-body">
    <h5 class="card-title">Closest Director Pairs</h5>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>From</th>
            <th>To</th>
            <th>Distance (km)</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pairsTop10 as $k => $p)
          <tr>
            <td>{{ $k+1 }}</td>
            <td>{{ $p['from'] }}</td>
            <td>{{ $p['to'] }}</td>
            <td>{{ round($p['km'], 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="text-muted small">Showing top {{ count($pairsTop10) }} of {{ count($pairs) }} pairs.</div>
  </div>
</div>
@endif

{{-- CLIENT GRID --}}
@if(isset($grid) && count($grid))
<div class="card mt-4">
  <div class="card-body">
    <h5 class="card-title">Client Format (Directors × Appointments)</h5>
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle">
        <thead>
          <tr>
            <th>Searched People Id</th>
            <th>Searched First Name</th>
            <th>Searched Surname</th>
            <th>Searched Full Name</th>
            <th>Searched DOB</th>
            <th>People Id</th>
            <th>Score</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Status</th>
            <th>Date Of Birth</th>
            <th>Local Director Number</th>
            <th>Is Original Director</th>
            <th>Company Id</th>
            <th>Company Name</th>
            <th>Company Number</th>
            <th>Safe Number</th>
            <th>Company Type</th>
            <th>Address</th>
            <th>Address City</th>
            <th>Address Post Code</th>
            <th>Address House No</th>
          </tr>
        </thead>
        <tbody>
          @foreach($grid as $r)
          <tr>
            <td>{{ $r['searchedPeopleId'] }}</td>
            <td>{{ $r['searchedFirstName'] }}</td>
            <td>{{ $r['searchedSurname'] }}</td>
            <td>{{ $r['searchedFullName'] }}</td>
            <td>{{ $r['searchedDOB'] }}</td>
            <td>{{ $r['peopleId'] }}</td>
            <td>{{ $r['score'] }}</td>
            <td>{{ $r['firstName'] }}</td>
            <td>{{ $r['lastName'] }}</td>
            <td>{{ $r['status'] }}</td>
            <td>{{ $r['dateOfBirth'] }}</td>
            <td>{{ $r['localDirectorNumber'] }}</td>
            <td>{{ is_null($r['isOriginalDirector']) ? '' : ($r['isOriginalDirector'] ? 'true' : 'false') }}</td>
            <td>{{ $r['companyId'] }}</td>
            <td>{{ $r['companyName'] }}</td>
            <td>{{ $r['companyNumber'] }}</td>
            <td>{{ $r['safeNumber'] }}</td>
            <td>{{ $r['companyType'] }}</td>
            <td>{{ $r['address'] }}</td>
            <td>{{ $r['addressCity'] }}</td>
            <td>{{ $r['addressPostCode'] }}</td>
            <td>{{ $r['addressHouseNo'] }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="text-muted small">Saved {{ count($grid) }} row(s) to database (search_results).</div>
  </div>
</div>
@endif

{{-- OPTIONAL raw distance matrix --}}
@if(!empty($distanceMatrix) && is_iterable($distanceMatrix))
<div class="card mt-4">
  <div class="card-body">
    <h5 class="card-title">Distance Matrix (km)</h5>
    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <tbody>
          @foreach($distanceMatrix as $r)
          <tr>
            @if(is_iterable($r))
            @foreach($r as $cell)
            <td>{{ is_null($cell) ? '—' : round($cell, 2) }}</td>
            @endforeach
            @else
            <td>{{ is_null($r) ? '—' : round($r, 2) }}</td>
            @endif
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif

<div class="mt-4">
  <a href="{{ route('directors.search') }}" class="btn btn-secondary">New Search</a>
</div>
@endsection