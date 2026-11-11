@extends('layouts.app')
@section('content')
<div class="head">
  <div>
    <h1>System Monitoring</h1>
    <p class="sub">Real-time system information and performance metrics.</p>
  </div>
</div>

<!-- System Information -->
<div class="card">
  <div class="cardhead"><h3>System Information</h3></div>
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem">
    @foreach($metrics as $key => $value)
      <div class="row" style="border-bottom:1px solid var(--border);padding-bottom:0.5rem">
        <b>{{ ucwords(str_replace('_', ' ', $key)) }}</b>
        <span style="font-family:monospace;color:var(--muted)">{{ $value }}</span>
      </div>
    @endforeach
  </div>
</div>

<!-- Performance Metrics -->
<div class="card">
  <div class="cardhead"><h3>Performance Metrics</h3></div>
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem">
    @foreach($performance as $key => $value)
      <div class="row" style="border-bottom:1px solid var(--border);padding-bottom:0.5rem">
        <b>{{ ucwords(str_replace('_', ' ', $key)) }}</b>
        <span style="font-family:monospace;color:var(--muted)">{{ $value }}</span>
      </div>
    @endforeach
  </div>
</div>

<!-- Database Tables -->
<div class="card">
  <div class="cardhead"><h3>Database Overview</h3></div>
  <table>
    <thead>
      <tr>
        <th>Table</th>
        <th>Records</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @php
        $tables = [
          ['name' => 'users', 'rows' => \App\Models\User::count()],
          ['name' => 'facilities', 'rows' => \App\Models\Facility::count()],
          ['name' => 'equipment', 'rows' => \App\Models\Equipment::count()],
          ['name' => 'requests', 'rows' => \App\Models\FermsRequest::count()],
          ['name' => 'reservations', 'rows' => \App\Models\Reservation::count()],
          ['name' => 'equipment_transactions', 'rows' => \App\Models\Transaction::count()],
          ['name' => 'notifications', 'rows' => \App\Models\Notice::count()],
          ['name' => 'system_logs', 'rows' => DB::table('system_logs')->count()],
        ];
      @endphp
      @foreach($tables as $table)
        <tr>
          <td><b>{{ $table['name'] }}</b></td>
          <td>{{ number_format($table['rows']) }}</td>
          <td><span class="badge approved">Active</span></td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

@endsection
