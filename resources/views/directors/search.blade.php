@extends('layouts.app')

@section('content')
<h2 class="mb-4">Director Search</h2>

@if(isset($total))
  <div class="mb-3 text-muted">Total searches: {{ $total }} • Today: {{ $today }}</div>
@endif

<form method="POST" action="{{ route('directors.search.run') }}" class="row g-3">
  @csrf
  <div class="col-md-4">
    <label class="form-label">First name</label>
    <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Surname <span class="text-danger">*</span></label>
    <input type="text" name="surname" value="{{ old('surname') }}" class="form-control" required>
    @error('surname') <div class="text-danger small">{{ $message }}</div> @enderror
  </div>
  <div class="col-md-4">
    <label class="form-label">Postcode (optional)</label>
    <input type="text" name="postcode" value="{{ old('postcode') }}" class="form-control">
  </div>
  <div class="col-12">
    <button class="btn btn-primary">Search</button>
  </div>
</form>
@endsection
