@extends('layouts.app')
@section('content')
<div class="head">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y') }}</div>
    <h1>Super Admin Dashboard</h1>
    <p class="sub">System management, monitoring, and troubleshooting.</p>
  </div>
  <a class="btn" href="/superadmin/settings">System Settings</a>
</div>

<!-- System Health Status -->
<div class="card" style="margin-bottom:1.5rem;border-left:4px solid {{ $systemHealth['database']['status']==='healthy' ? 'var(--green)' : 'var(--red)' }}">
  <div class="cardhead"><h3>System Health</h3></div>
  <div class="grid system-health-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
    <div>
      <b style="display:block;margin-bottom:0.25rem">Database</b>
      <span class="badge {{ $systemHealth['database']['status'] }}">{{ $systemHealth['database']['status'] }}</span>
      <small style="display:block;margin-top:0.25rem;color:var(--muted)">{{ $systemHealth['database']['message'] }}</small>
    </div>
    <div>
      <b style="display:block;margin-bottom:0.25rem">Cache</b>
      <span class="badge {{ $systemHealth['cache']['status'] }}">{{ $systemHealth['cache']['status'] }}</span>
      <small style="display:block;margin-top:0.25rem;color:var(--muted)">{{ $systemHealth['cache']['message'] }}</small>
    </div>
    <div>
      <b style="display:block;margin-bottom:0.25rem">Disk Usage</b>
      <span class="badge {{ $systemHealth['disk_usage']['status'] }}">{{ $systemHealth['disk_usage']['percentage'] ?? 'N/A' }}%</span>
      <small style="display:block;margin-top:0.25rem;color:var(--muted)">
        @if(isset($systemHealth['disk_usage']['used']))
          {{ $systemHealth['disk_usage']['used'] }} / {{ $systemHealth['disk_usage']['total'] }}
        @else
          {{ $systemHealth['disk_usage']['message'] ?? 'Unknown' }}
        @endif
      </small>
    </div>
  </div>
</div>

<!-- System Statistics -->
<div class="grid superadmin-stats" style="margin-bottom:1.5rem">
  @foreach($stats as $label => $value)
    <div class="card stat big">
      <b>{{ $value }}</b>
      <span>{{ $label }}</span>
    </div>
  @endforeach
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom:1.5rem">
  <div class="cardhead"><h3>Quick Actions</h3></div>
  <div class="grid superadmin-actions" style="gap:0.75rem">
    <form method="post" action="/superadmin/clear-cache">
      @csrf
      <button class="btn o" type="submit" style="width:100%">Clear All Caches</button>
    </form>
    <form method="post" action="/superadmin/optimize">
      @csrf
      <button class="btn o" type="submit" style="width:100%">Optimize System</button>
    </form>
    <form method="post" action="/superadmin/backup">
      @csrf
      <button class="btn o" type="submit" style="width:100%">Backup Database</button>
    </form>
    <a class="btn o" href="/superadmin/logs" style="width:100%;text-align:center">View System Logs</a>
    <a class="btn o" href="/superadmin/users" style="width:100%;text-align:center">Manage Users</a>
    <a class="btn o" href="/superadmin/monitoring" style="width:100%;text-align:center">System Monitoring</a>
  </div>
</div>

<div class="cols">
  <!-- Recent Errors -->
  <div class="card">
    <div class="cardhead">
      <h3>Recent Errors</h3>
      <a class="link" href="/superadmin/logs?level=error">See all →</a>
    </div>
    @forelse($recentErrors as $log)
      <div class="item">
        <div>
          <b style="color:var(--red)">{{ $log->category }}</b>
          <small>{{ $log->message }}</small>
          <small style="color:var(--muted)">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</small>
        </div>
      </div>
    @empty
      <div class="empty">No recent errors. System is running smoothly.</div>
    @endforelse
  </div>

  <!-- User Activity -->
  <div>
    <div class="card">
      <div class="cardhead"><h3>User Activity</h3></div>
      <div class="item">
        <div><b>Today</b></div>
        <div class="end"><b>{{ $userActivity['today'] }}</b></div>
      </div>
      <div class="item">
        <div><b>This Week</b></div>
        <div class="end"><b>{{ $userActivity['this_week'] }}</b></div>
      </div>
      <div class="item">
        <div><b>This Month</b></div>
        <div class="end"><b>{{ $userActivity['this_month'] }}</b></div>
      </div>
    </div>

    <!-- Recent System Logs -->
    <div class="card" style="margin-bottom:0">
      <div class="cardhead">
        <h3>Recent Activity</h3>
        <a class="link" href="/superadmin/logs">All logs →</a>
      </div>
      @forelse($recentLogs->take(5) as $log)
        <div class="item">
          <div>
            <b>{{ $log->category }}</b>
            <small>{{ \Illuminate\Support\Str::limit($log->message, 60) }}</small>
          </div>
          <div class="end">
            <span class="badge {{ $log->level }}">{{ $log->level }}</span>
          </div>
        </div>
      @empty
        <div class="empty">No recent activity.</div>
      @endforelse
    </div>
  </div>
</div>

@endsection
