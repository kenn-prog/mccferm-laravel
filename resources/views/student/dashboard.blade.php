@extends('layouts.app')
@section('content')
@php
  $hr = now()->hour; $greet = $hr < 12 ? 'Good morning' : ($hr < 18 ? 'Good afternoon' : 'Good evening');
  $totalFac = \App\Models\Facility::count(); $pct = $totalFac ? round($facilities / $totalFac * 100) : 0;
  $first = explode(' ', trim(auth()->user()->name))[0];
@endphp
<div class="head">
  <div><div class="eyebrow">{{ now()->format('l, F j, Y') }}</div><h1>{{ $greet }}, {{ $first }}!</h1><p class="sub">Here's what's happening around your campus today.</p></div>
  <details class="request-dropdown">
    <summary class="btn">+ New request</summary>
    <div class="request-dropdown-menu">
      <a href="/facilities">Facilities</a>
      <a href="/equipment">Equipment</a>
    </div>
  </details>
</div>
<div class="grid dashboard-stats">
  <div class="card stat"><div class="ic"><svg viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-4h4v4"/></svg></div><div><span>Available Facilities</span><b>{{ $facilities }}</b></div></div>
  <div class="card stat"><div class="ic"><svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/></svg></div><div><span>Available Equipment</span><b>{{ $equipment }}</b></div></div>
  <div class="card stat"><div class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div><div><span>Pending Requests</span><b>{{ $counts['pending'] }}</b></div></div>
  <div class="card stat"><div class="ic"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div><div><span>Approved</span><b>{{ $counts['approved'] }}</b></div></div>
</div>
<div class="cols">
  <div>
    <div class="card">
      <div class="cardhead"><h3>Upcoming Reservations</h3><a class="link" href="/requests">See all →</a></div>
      @forelse($upcoming as $r)
        <div class="item"><div class="ico"><svg viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2"/></svg></div>
          <div><b>{{ $r->facility->name }}</b><small>{{ $r->starts_at->format('M d · g:i A') }} – {{ $r->ends_at->format('g:i A') }}</small></div>
          <div class="end"><span class="badge approved">Approved</span></div></div>
      @empty <div class="empty">No upcoming reservations.</div> @endforelse
    </div>
    <div class="card">
      <div class="cardhead"><h3>Borrowed Equipment</h3><a class="link" href="/requests">See all →</a></div>
      @forelse($borrowed as $t)
        <div class="item"><div class="ico"><svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/></svg></div>
          <div><b>{{ $t->equipment->name }} ×{{ $t->quantity }}</b><small>Due {{ $t->expected_return->format('M d, Y') }}</small></div>
          <div class="end"><span class="badge {{ $t->is_overdue ? 'rejected' : 'borrowed' }}">{{ $t->is_overdue ? 'Overdue' : 'Borrowed' }}</span></div></div>
      @empty <div class="empty">Nothing borrowed right now.</div> @endforelse
    </div>
  </div>
  <div class="card hero">
    <h3>Facility Overview</h3>
    <div class="donut" style="--p:{{ $pct }}"><div>{{ $pct }}%<small>Available</small></div></div>
    <div class="legend"><span><i style="background:#7fd0ff"></i>Available</span><span><i style="background:rgba(255,255,255,.35)"></i>Occupied</span></div>
    <p class="sub" style="margin:16px 0 0;text-align:center">{{ $facilities }} of {{ $totalFac }} facilities open for reservation</p>
  </div>
</div>
@endsection
