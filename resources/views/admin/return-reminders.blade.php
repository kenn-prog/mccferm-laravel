@extends('layouts.app')
@section('content')
<div class="page-header">
  <div class="page-header-content">
    <h1>Return Reminders</h1>
    <p class="sub">Notify students and student organizations about equipment that is due or overdue.</p>
  </div>
</div>
<div class="table-container">
  <table>
    <thead>
      <tr><th>Borrower</th><th>Equipment</th><th>Due</th><th>Status</th><th>Action</th></tr>
    </thead>
    <tbody>
      @forelse($rows as $t)
        @php
          $dueAt = \Illuminate\Support\Carbon::parse($t->expected_return->format('Y-m-d').' '.($t->expected_return_time ?: '23:59:59'));
          $isOverdue = $dueAt->isPast();
        @endphp
        <tr>
          <td><b>{{ $t->user->name }}</b><br><small>{{ ucfirst($t->user->role) }}{{ $t->user->organization_class ? ' · '.$t->user->organization_class : '' }}</small></td>
          <td><b>{{ $t->equipment->name }}</b> ×{{ $t->quantity }}</td>
          <td>{{ $t->expected_return->format('M d, Y') }}{{ $t->expected_return_time ? ' · '.\Illuminate\Support\Carbon::parse($t->expected_return_time)->format('g:i A') : '' }}</td>
          <td><span class="badge {{ $isOverdue ? 'rejected' : 'approved' }}">{{ $isOverdue ? 'Overdue' : 'Due soon' }}</span></td>
          <td><form method="post" action="/admin/transactions/{{ $t->id }}/remind">@csrf<button class="btn s">Send reminder</button></form></td>
        </tr>
      @empty
        <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--mute)">No equipment is currently borrowed.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
