@extends('layouts.app')
@section('content')
@php
  $pending = \App\Models\FermsRequest::with(['user','reservation.facility','item.equipment'])->where('status','pending')->latest()->limit(6)->get();
  $style = ['Pending requests'=>'warn'];
@endphp
<div class="head"><div><div class="eyebrow">{{ now()->format('l, F j, Y') }}</div><h1>Admin Dashboard</h1><p class="sub">Monitor facilities, equipment, and reservation activity.</p></div>
  <a class="btn" href="/admin/requests">Review requests</a></div>
<div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))">
  @foreach($s as $label => $v)
    <div class="card stat big {{ $label==='Pending requests' ? 'warn' : '' }}"><b>{{ $v }}</b><span>{{ $label }}</span></div>
  @endforeach
</div>
<div class="cols">
  <div class="card">
    <div class="cardhead"><h3>Pending Requests</h3><a class="link" href="/admin/requests?status=pending">See all →</a></div>
    @forelse($pending as $q)
      <div class="item">
        <div class="ico"><svg viewBox="0 0 24 24">@if($q->type==='facility')<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2"/>@else<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/>@endif</svg></div>
        <div><b>{{ $q->type==='facility' ? $q->reservation->facility->name : $q->item->equipment->name.' ×'.$q->item->quantity }}</b>
          <small>{{ $q->user->name }} · {{ $q->type==='facility' ? $q->reservation->starts_at->format('M d, g:i A') : \Illuminate\Support\Carbon::parse($q->item->borrow_date)->format('M d') }}</small></div>
        <div class="end">
          <form method="post" action="/admin/requests/{{ $q->id }}/decide">@csrf<input type="hidden" name="decision" value="approved"><button class="btn s">Approve</button></form>
          <form method="post" action="/admin/requests/{{ $q->id }}/decide">@csrf<input type="hidden" name="decision" value="rejected"><button class="btn s o">Reject</button></form>
        </div>
      </div>
    @empty <div class="empty">No pending requests. You're all caught up.</div> @endforelse
  </div>
  <div>
    <div class="card"><div class="cardhead"><h3>Upcoming Reservations</h3></div>
      @forelse($upcoming as $r)
        <div class="item"><div><b>{{ $r->facility->name }}</b><small>{{ $r->starts_at->format('M d · g:i A') }} · {{ $r->request->user->name }}</small></div></div>
      @empty <div class="empty">None scheduled.</div> @endforelse
    </div>
    <div class="card" style="margin-bottom:0"><div class="cardhead"><h3>Recent Activity</h3></div>
      @forelse($recent as $q)
        <div class="item"><div><b>{{ $q->user->name }}</b><small>{{ ucfirst($q->type) }} request · {{ $q->created_at->diffForHumans() }}</small></div><div class="end"><span class="badge {{ $q->status }}">{{ $q->status }}</span></div></div>
      @empty <div class="empty">No activity yet.</div> @endforelse
    </div>
  </div>
</div>
@endsection
