@extends('layouts.app')
@section('content')
@php
  $pending = \App\Models\FermsRequest::with(['user','reservation.facility','item.equipment'])->where('status','pending')->latest()->limit(6)->get();
  $style = ['Pending requests'=>'warn'];
@endphp
<div class="page-header">
  <div class="page-header-content">
    <div class="eyebrow">{{ now()->format('l, F j, Y · H:i') }}</div>
    <h1>Admin Dashboard</h1>
    <p class="sub">Monitor facilities, equipment, and reservation activity.</p>
  </div>
  <a class="btn btn-icon" href="/admin/requests">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
    Review requests
  </a>
</div>
<div class="stat-grid">
  @foreach($s as $label => $v)
    <div class="stat-card-compact {{ $label==='Pending requests' ? 'warn' : '' }}">
      <div class="icon">
        <svg viewBox="0 0 24 24">
          @if($label==='Pending requests')<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M9 10h6M9 14h6"/>
          @elseif($label==='Approved reservations')<path d="M5 13l4 4L19 7"/>
          @elseif($label==='Total reservations')<path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
          @elseif($label==='Available facilities')<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2"/>
          @else<path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/>
          @endif
        </svg>
      </div>
      <div class="content">
        <div class="value">{{ $v }}</div>
        <div class="label">{{ $label }}</div>
      </div>
    </div>
  @endforeach
</div>
<div class="content-grid">
  <div class="enhanced-card">
    <div class="cardhead">
      <h3>Pending Requests</h3>
      <a class="link" href="/admin/requests?status=pending">View all →</a>
    </div>
    @forelse($pending as $q)
      <div class="enhanced-item">
        <div class="item-icon">
          <svg viewBox="0 0 24 24">
            @if($q->type==='facility')
              <rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2"/>
            @else
              <path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/>
            @endif
          </svg>
        </div>
        <div class="item-content">
          <div class="item-title">{{ $q->type==='facility' ? $q->reservation->facility->name : $q->item->equipment->name.' ×'.$q->item->quantity }}</div>
          <div class="item-subtitle">{{ $q->user->name }} · {{ $q->type==='facility' ? $q->reservation->starts_at->format('M d, g:i A') : \Illuminate\Support\Carbon::parse($q->item->borrow_date)->format('M d') }}</div>
        </div>
        <div class="item-actions">
          <form method="post" action="/admin/requests/{{ $q->id }}/decide" style="display:inline">
            @csrf
            <input type="hidden" name="decision" value="approved">
            <button class="btn s">Approve</button>
          </form>
          <form method="post" action="/admin/requests/{{ $q->id }}/decide" style="display:inline">
            @csrf
            <input type="hidden" name="decision" value="rejected">
            <button class="btn s o">Reject</button>
          </form>
        </div>
      </div>
    @empty
      <div class="empty-state-enhanced">
        <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        <p>No pending requests. You're all caught up.</p>
      </div>
    @endforelse
  </div>

  <div>
    <div class="enhanced-card">
      <div class="cardhead"><h3>Upcoming Reservations</h3></div>
      @forelse($upcoming as $r)
        <div class="enhanced-item">
          <div class="item-content">
            <div class="item-title">{{ $r->facility->name }}</div>
            <div class="item-subtitle">{{ $r->starts_at->format('M d · g:i A') }} · {{ $r->request->user->name }}</div>
          </div>
        </div>
      @empty
        <div class="empty-state-enhanced">
          <svg viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/></svg>
          <p>None scheduled</p>
        </div>
      @endforelse
    </div>

    <div class="enhanced-card">
      <div class="cardhead"><h3>Recent Activity</h3></div>
      @forelse($recent as $q)
        <div class="enhanced-item">
          <div class="item-content">
            <div class="item-title">{{ $q->user->name }}</div>
            <div class="item-subtitle">{{ ucfirst($q->type) }} request · {{ $q->created_at->diffForHumans() }}</div>
          </div>
          <div class="item-actions">
            <span class="badge {{ $q->status }}">{{ $q->status }}</span>
          </div>
        </div>
      @empty
        <div class="empty-state-enhanced">
          <svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          <p>No activity yet</p>
        </div>
      @endforelse
    </div>
  </div>
</div>
@endsection
