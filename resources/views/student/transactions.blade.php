@extends('layouts.app')@section('content')<div class="page-header">
  <div class="page-header-content">
    <h1>History</h1>
    <p class="sub">Accepted, rejected, and completed requests.</p>
  </div>
</div>

<div class="table-container">
  <table>
    <thead>
      <tr><th>Type</th><th>Details</th><th>When</th><th>Status</th></tr>
    </thead>
    <tbody>
      @forelse($rows as $q)
        <tr>
          <td><b>{{ ucfirst($q->type) }}</b></td>
          <td><b>{{ $q->type==='facility' ? $q->reservation->facility->name : $q->item->equipment->name.' ×'.$q->item->quantity }}</b><br><small>{{ $q->purpose }}</small></td>
          <td><small>{{ $q->type==='facility' ? $q->reservation->starts_at->format('M d, g:i A') : $q->item->borrow_date->format('M d').' '.($q->item->borrow_time ? \Illuminate\Support\Carbon::parse($q->item->borrow_time)->format('g:i A') : '').' → '.$q->item->expected_return->format('M d').' '.($q->item->expected_return_time ? \Illuminate\Support\Carbon::parse($q->item->expected_return_time)->format('g:i A') : '') }}</small></td>
          <td><span class="badge {{ $q->status }}">{{ ucfirst($q->status) }}</span></td>
        </tr>
      @empty
        <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--mute)">No history yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
