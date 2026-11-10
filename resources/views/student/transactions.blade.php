@extends('layouts.app')
@section('content')
<h1>History</h1>
<p class="sub">Accepted, rejected, and completed requests.</p>

<div class="card scroll">
  <table>
    <tr><th>Type</th><th>Details</th><th>When</th><th>Status</th></tr>
    @forelse($rows as $q)
      <tr>
        <td>{{ ucfirst($q->type) }}</td>
        <td>{{ $q->type==='facility' ? $q->reservation->facility->name : $q->item->equipment->name.' ×'.$q->item->quantity }}<br><small>{{ $q->purpose }}</small></td>
        <td>{{ $q->type==='facility' ? $q->reservation->starts_at->format('M d, g:i A') : $q->item->borrow_date->format('M d').' → '.$q->item->expected_return->format('M d') }}</td>
        <td><span class="badge {{ $q->status }}">{{ ucfirst($q->status) }}</span></td>
      </tr>
    @empty
      <tr><td colspan="4">No history yet.</td></tr>
    @endforelse
  </table>
</div>
@endsection
