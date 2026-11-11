@extends('layouts.app')@section('content')<div class="page-header">
  <div class="page-header-content">
    <h1>Notifications</h1>
    <p class="sub">Updates about your requests.</p>
  </div>
</div>
@forelse($rows as $n)
  <div class="enhanced-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <div class="notification-heading">
        <b style="font-size:15px">{{ $n->title }}</b>
        <span class="badge notice-type {{ $n->type }}">{{ ucfirst($n->type) }}</span>
      </div>
      <small style="color:var(--mute)">{{ $n->created_at->diffForHumans() }}</small>
    </div>
    <p style="margin:0;color:var(--navy)">{{ $n->message }}</p>
  </div>
@empty
  <div class="empty-state-enhanced">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
    <p>No notifications</p>
  </div>
@endforelse @endsection
