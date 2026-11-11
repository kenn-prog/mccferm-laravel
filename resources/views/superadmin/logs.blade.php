@extends('layouts.app')
@section('content')
<div class="head">
  <div>
    <h1>System Logs</h1>
    <p class="sub">Track system events, errors, and user activities.</p>
  </div>
  <div class="row" style="gap:0.5rem">
    <form method="post" action="/superadmin/logs/clear">
      @csrf
      <button class="btn warn o s" type="submit" onclick="return confirm('Clear all log entries? This cannot be undone.')">Clear Logs</button>
    </form>
    <a class="btn o" href="/superadmin">← Back</a>
  </div>
</div>

<!-- Filters -->
<div class="card">
  <form method="get" action="/superadmin/logs">
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;align-items:end">
      <div>
        <label for="level">Level</label>
        <select name="level" id="level" class="input">
          <option value="">All Levels</option>
          @foreach($levels as $level)
            <option value="{{ $level }}" {{ request('level')===$level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="category">Category</label>
        <select name="category" id="category" class="input">
          <option value="">All Categories</option>
          @foreach($categories as $category)
            <option value="{{ $category }}" {{ request('category')===$category ? 'selected' : '' }}>{{ ucfirst($category) }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="date_from">From Date</label>
        <input type="date" name="date_from" id="date_from" class="input" value="{{ request('date_from') }}">
      </div>
      <div>
        <label for="date_to">To Date</label>
        <input type="date" name="date_to" id="date_to" class="input" value="{{ request('date_to') }}">
      </div>
      <button class="btn" type="submit">Apply Filters</button>
      @if(request()->hasAny(['level', 'category', 'date_from', 'date_to']))
        <a class="btn o" href="/superadmin/logs">Clear Filters</a>
      @endif
    </div>
  </form>
</div>

<!-- Logs Table -->
<div class="card">
  <table>
    <thead>
      <tr>
        <th>Timestamp</th>
        <th>Level</th>
        <th>Category</th>
        <th>Message</th>
        <th>User</th>
        <th>IP Address</th>
      </tr>
    </thead>
    <tbody>
      @forelse($logs as $log)
        <tr>
          <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y H:i:s') }}</td>
          <td><span class="badge {{ $log->level }}">{{ $log->level }}</span></td>
          <td>{{ $log->category }}</td>
          <td style="max-width:400px">{{ $log->message }}</td>
          <td>{{ $log->user_id ? \App\Models\User::find($log->user_id)?->name ?? 'Unknown' : 'System' }}</td>
          <td style="font-family:monospace;font-size:0.85rem">{{ $log->ip_address ?? 'N/A' }}</td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">No logs found.</td></tr>
      @endforelse
    </tbody>
  </table>
  
  @if($logs->hasPages())
    <div style="padding:1rem;border-top:1px solid var(--border)">
      {{ $logs->links() }}
    </div>
  @endif
</div>

@endsection
