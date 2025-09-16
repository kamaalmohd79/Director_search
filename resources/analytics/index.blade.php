@extends('layouts.app')
@section('title', 'Analytics')
@section('content')
<div class="card">
  <h2>Usage Analytics</h2>
  <p>Total searches: <strong>{{ $total }}</strong></p>
  <table>
    <thead><tr><th>When</th><th>First</th><th>Last</th><th>Postcode</th><th>Results</th><th>IP</th></tr></thead>
    <tbody>
      @foreach($recent as $r)
      <tr>
        <td>{{ $r->created_at->format('Y-m-d H:i') }}</td>
        <td>{{ $r->first_name ?? '—' }}</td>
        <td>{{ $r->last_name ?? '—' }}</td>
        <td>{{ $r->postcode ?? '—' }}</td>
        <td>{{ $r->results_count }}</td>
        <td>{{ $r->ip ?? '—' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
