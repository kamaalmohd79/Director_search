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

<div class="row g-3">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Summary</h5>
        <ul class="mb-0">
          <li>Total officers (directors only): {{ count($officers) }}</li>
          <li>Pairs: {{ $stats['count_pairs'] ?? 0 }}</li>
          <li>Min/Avg/Max (km): {{ $stats['min_km'] ?? '—' }} / {{ $stats['avg_km'] ?? '—' }} / {{ $stats['max_km'] ?? '—' }}</li>
        </ul>

        {{-- Diagnostics if no pairs --}}
        @if(!empty($stats) && (($stats['count_pairs'] ?? 0) == 0))
        <div class="alert alert-info mt-3">
          <div class="fw-semibold mb-1">Geocoding diagnostics</div>
          <div>Distances weren’t computed because there were no valid postcode pairs.</div>
          <ul class="mb-0 mt-2 small">
            <li>Total officers with postcode: {{ $stats['count_geocoded'] ?? 0 }}</li>
            <li>Total officers missing/invalid postcode: {{ $stats['count_skipped'] ?? 0 }}</li>
          </ul>
          @if(!empty($stats['skipped']))
          <details class="mt-2">
            <summary class="small text-muted">View skipped officers</summary>
            <ul class="small mb-0 mt-2">
              @foreach($stats['skipped'] as $s)
              <li>
                {{ $s['name'] ?? 'Unknown' }} —
                postcode: {{ $s['postcode'] ?? 'N/A' }} —
                reason: {{ $s['reason'] ?? 'unknown' }}
              </li>
              @endforeach
            </ul>
          </details>
          @endif
        </div>
        @endif

      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Directors</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Postcode</th>
                <th>Current</th>
                <th>Resigned</th>
              </tr>
            </thead>
            <tbody>
              @forelse($officers as $i => $o)
              <tr>
                <td>{{ $i+1 }}</td>
                <td>
                  {{ $o['name'] }}
                  <span class="badge bg-success ms-1">Director</span>

                  @php
                  $geo = $o['geo_status'] ?? null;
                  // fallback if a very old result has no 'geo_status'
                  if (!$geo) {
                  $pc = trim($o['postcode'] ?? '');
                  if ($pc === '') {
                  $geo = 'missing';
                  } elseif (!empty($o['lat']) && !empty($o['lng'])) {
                  $geo = 'ok';
                  } else {
                  $geo = 'failed';
                  }
                  }
                  @endphp

                  @if($geo === 'missing')
                  <span class="badge bg-warning ms-2">no postcode</span>
                  @elseif($geo === 'failed')
                  <span class="badge bg-secondary ms-2">unresolvable</span>
                  @elseif($geo === 'ok')
                  <span class="badge bg-success ms-2">geo✓</span>
                  @endif
                </td>

                <td>{{ $o['postcode'] }}</td>
                <td>{{ $o['current_director_count'] ?? 0 }}</td>
                <td>{{ $o['resigned_director_count'] ?? 0 }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center text-muted">No directors found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

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

{{-- optional distance matrix display --}}
@if(!empty($distanceMatrix))
<div class="card mt-4">
  <div class="card-body">
    <h5 class="card-title">Distance Matrix (km)</h5>
    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <tbody>
          @foreach($distanceMatrix as $r)
          <tr>
            @foreach($r as $cell)
            <td>{{ is_null($cell) ? '—' : $cell }}</td>
            @endforeach
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