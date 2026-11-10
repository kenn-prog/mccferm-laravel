@extends('layouts.app')
@section('content')
@php $cats = $facilities->pluck('category')->unique()->values(); @endphp
<div class="head"><div><div class="eyebrow">Campus Directory</div><h1>Facilities</h1><p class="sub">Browse spaces, check availability, and request a reservation.</p></div>
  <button class="btn" type="button" data-toggle-target="reservation-dialog" aria-haspopup="dialog" aria-expanded="false" aria-controls="reservation-dialog">+ New reservation</button></div>
<div class="chips"><button type="button" class="chip on" data-filter="all">All</button>@foreach($cats as $c)<button type="button" class="chip" data-filter="{{ strtolower($c) }}">{{ $c }}</button>@endforeach</div>
<div class="fgrid">
  @foreach($facilities as $f)
    <div class="fcard" data-cat="{{ strtolower($f->category) }}">
      <div class="fimg g-{{ in_array(strtolower($f->category),['courts','rooms','halls','outdoor']) ? strtolower($f->category) : 'def' }}"><svg viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-4h4v4"/></svg></div>
      <div class="fbody"><b>{{ $f->name }}</b><small>{{ $f->location }} · Capacity {{ $f->capacity }}</small>
        <div class="fmeta"><span class="badge {{ $f->status }}">{{ $f->status }}</span>
          @if($f->status==='available')<button type="button" class="btn s" data-open-selected-dialog aria-haspopup="dialog" aria-expanded="false" aria-controls="reservation-dialog" onclick="pick('facility_id','{{ $f->id }}','reservation-dialog')">Reserve</button>@endif</div></div>
    </div>
  @endforeach
</div>
<div class="card">
  <h3>Booked schedule</h3>
  @forelse($booked as $b)<div class="item"><div><b>{{ $b->facility->name }}</b><small>{{ $b->starts_at->format('M d, Y') }} · {{ $b->starts_at->format('g:i A') }}–{{ $b->ends_at->format('g:i A') }}</small></div></div>
  @empty<div class="empty">No upcoming bookings.</div>@endforelse
</div>
<dialog class="card add-form" id="reservation-dialog">
  <div class="add-form-header"><h3>Request a reservation</h3><button class="btn s l" type="button" data-close-dialog>Cancel</button></div>
  <form method="post" action="{{ request()->boolean('request_followup') ? '/reservations?request_followup=1' : '/reservations' }}">@csrf
      <div class="row">
        <div><label>Facility</label><select name="facility_id">@foreach($facilities->where('status','available') as $f)<option value="{{ $f->id }}" @selected(old('facility_id')==$f->id)>{{ $f->name }}</option>@endforeach</select></div>
        <div><label>Date</label><input type="date" name="date" min="{{ today()->toDateString() }}" value="{{ old('date') }}" required></div>
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
