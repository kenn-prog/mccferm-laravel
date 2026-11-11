@extends('layouts.app')
@section('content')
<h1>Return reminders</h1>
<p class="sub">Notify students and student organizations about equipment that is due or overdue.</p>
<div class="card scroll">
  <table>
    <tr><th>Borrower</th><th>Equipment</th><th>Due</th><th>Status</th><th>Action</th></tr>
    @forelse($rows as $t)
      @php
        $dueAt = \Illuminate\Support\Carbon::parse($t->expected_return->format('Y-m-d').' '.($t->expected_return_time ?: '23:59:59'));
        $isOverdue = $dueAt->isPast();
      @endphp
      <tr>
        <td>{{ $t->user->name }}<br><small>{{ ucfirst($t->user->role) }}{{ $t->user->organization_class ? ' · '.$t->user->organization_class : '' }}</small></td>
        <td>{{ $t->equipment->name }} ×{{ $t->quantity }}</td>
        <td>{{ $t->expected_return->format('M d, Y') }}{{ $t->expected_return_time ? ' · '.\Illuminate\Support\Carbon::parse($t->expected_return_time)->format('g:i A') : '' }}</td>
        <td><span class="badge {{ $isOverdue ? 'rejected' : 'approved' }}">{{ $isOverdue ? 'Overdue' : 'Due soon' }}</span></td>
        <td><form method="post" action="/admin/transactions/{{ $t->id }}/remind">@csrf<button class="btn s">Send reminder</button></form></td>
      </tr>
    @empty
      <tr><td colspan="5">No equipment is currently borrowed.</td></tr>
    @endforelse
  </table>
</div>
@endsection
