@extends('layouts.app')
@section('content')
@php $cats = $facilities->pluck('category')->unique()->values(); @endphp
<div class="page-header">
  <div class="page-header-content">
    <div class="eyebrow">Campus Directory</div>
    <h1>Facilities</h1>
    <p class="sub">Browse spaces, check availability, and request a reservation.</p>
  </div>
  <button class="btn btn-icon" type="button" data-toggle-target="reservation-dialog" aria-haspopup="dialog" aria-expanded="false" aria-controls="reservation-dialog">
    <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
    New reservation
  </button>
</div>
<div class="chips"><button type="button" class="chip on" data-filter="all">All</button>@foreach($cats as $c)<button type="button" class="chip" data-filter="{{ strtolower($c) }}">{{ $c }}</button>@endforeach</div>
<div class="facility-grid">
  @foreach($facilities as $f)
    <div class="facility-card" data-cat="{{ strtolower($f->category) }}">
      <div class="card-header" style="background:linear-gradient(135deg,{{ in_array(strtolower($f->category),['courts']) ? '#1f9d6a,#6fd1a0' : (in_array(strtolower($f->category),['rooms']) ? '#27467f,#4f86d9' : (in_array(strtolower($f->category),['halls']) ? '#5b4fc7,#8f94f5' : (in_array(strtolower($f->category),['outdoor']) ? '#2c8f4e,#a3d66f' : '#1c4fa8,#4aa8ff'))) }})">
        <svg viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-4h4v4"/></svg>
      </div>
      <div class="card-body">
        <div class="card-title">{{ $f->name }}</div>
        <div class="card-subtitle">
          <svg viewBox="0 0 24 24" style="width:14px;height:14px"><path d="M12 2l9 4.5v9L12 20l-9-4.5v-9L12 2z"/></svg>
          {{ $f->location }} · Capacity {{ $f->capacity }}
        </div>
        <div class="card-footer">
          <span class="badge {{ $f->status }}">{{ $f->status }}</span>
          @if($f->status==='available')
            <button type="button" class="btn s" data-open-selected-dialog aria-haspopup="dialog" aria-expanded="false" aria-controls="reservation-dialog" onclick="pick('facility_id','{{ $f->id }}','reservation-dialog')">Reserve</button>
          @endif
        </div>
      </div>
    </div>
  @endforeach
</div>
<div class="enhanced-card">
  <div class="cardhead"><h3>Booked Schedule</h3></div>
  @forelse($booked as $b)
    <div class="enhanced-item">
      <div class="item-icon">
        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      </div>
      <div class="item-content">
        <div class="item-title">{{ $b->facility->name }}</div>
        <div class="item-subtitle">{{ $b->starts_at->format('M d, Y') }} · {{ $b->starts_at->format('g:i A') }}–{{ $b->ends_at->format('g:i A') }}</div>
      </div>
    </div>
  @empty
    <div class="empty-state-enhanced">
      <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/></svg>
      <p>No upcoming bookings</p>
    </div>
  @endforelse
</div>
<dialog class="card add-form" id="reservation-dialog">
  <div class="add-form-header"><h3>Request a reservation</h3><button class="btn s l" type="button" data-close-dialog>Cancel</button></div>
  <form method="post" action="{{ request()->boolean('request_followup') ? '/reservations?request_followup=1' : '/reservations' }}">@csrf
      <div class="row">
        <div><label>Facility</label><select name="facility_id">@foreach($facilities->where('status','available') as $f)<option value="{{ $f->id }}" @selected(old('facility_id')==$f->id)>{{ $f->name }}</option>@endforeach</select></div>
        <div><label>Date</label><input type="date" name="date" data-school-open-date min="{{ today()->toDateString() }}" value="{{ old('date') }}" required><small>School is closed on Sundays.</small></div>
        <div><label>Start (8:00 AM–8:00 PM)</label><input type="time" name="start_time" min="08:00" max="20:00" value="{{ old('start_time') }}" required></div>
        <div><label>End (8:00 AM–8:00 PM)</label><input type="time" name="end_time" min="08:00" max="20:00" value="{{ old('end_time') }}" required></div>
        <div><label>Participants</label><input type="number" name="participants" min="1" value="{{ old('participants') }}"></div>
      </div>
      <label>Purpose / activity</label><input name="purpose" value="{{ old('purpose') }}" required>
      <label>Notes</label><textarea name="notes">{{ old('notes') }}</textarea>
      <button class="btn">Submit request</button>
  </form>
</dialog>
@if($errors->any())<script>document.getElementById('reservation-dialog').showModal();</script>@endif
@endsection
