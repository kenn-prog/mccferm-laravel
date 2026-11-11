@extends('layouts.app')
@section('content')
@php
  $iconPaths = [
    'users'=>'<path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
    'user-check'=>'<path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/>',
    'building'=>'<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-4h4v4"/>',
    'box'=>'<path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/>',
    'clipboard'=>'<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M9 10h6M9 14h6"/>',
    'database'=>'<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>',
    'layers'=>'<path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/>',
    'hard-drive'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 15h.01M11 15h.01M3 10h18"/>',
    'check'=>'<path d="m5 12 4 4L19 6"/>',
    'broom'=>'<path d="m15 4 5 5M3 21l8-8M14 5l5 5-7 7-5-5 7-7zM3 21l2-6 4 4-6 2z"/>',
    'zap'=>'<path d="m13 2-3 8h7l-6 12 1-9H5l8-11z"/>',
    'archive'=>'<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v12h14V8M10 12h4"/>',
    'file-text'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
    'chart'=>'<path d="M3 3v18h18M8 16v-4M13 16V7M18 16v-7"/>',
  ];
  $statIcons = [
    'Total Users'=>'users',
    'Active Users'=>'user-check',
    'Total Facilities'=>'building',
    'Total Equipment'=>'box',
    'Pending Requests'=>'clipboard',
  ];
@endphp
<div class="superadmin-page">
<div class="superadmin-header">
  <div>
    <div class="eyebrow">{{ now()->format('l, F j, Y · H:i') }}</div>
    <h1>System Dashboard</h1>
  </div>
  <a class="btn s" href="/superadmin/settings">
    <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6M1 12h6m6 0h6M4.2 19.8l4.2-4.2m5.6 0 4.2 4.2M19.8 4.2l-4.2 4.2m-5.6 0L4.8 4.2"/></svg>
    Settings
  </a>
</div>

<div class="superadmin-health-grid">
  <div class="health-card {{ $systemHealth['database']['status'] === 'healthy' ? 'healthy' : 'error' }}">
    <div class="health-card-header">
      <span class="health-icon">
        <svg viewBox="0 0 24 24">{!! $iconPaths['database'] !!}</svg>
      </span>
      <span class="health-status {{ $systemHealth['database']['status'] }}">
        <svg viewBox="0 0 24 24">{!! $iconPaths['check'] !!}</svg>
      </span>
    </div>
    <div class="health-card-body">
      <h4>Database</h4>
      <p>{{ $systemHealth['database']['message'] }}</p>
    </div>
  </div>
  <div class="health-card {{ $systemHealth['cache']['status'] === 'healthy' ? 'healthy' : 'error' }}">
    <div class="health-card-header">
      <span class="health-icon">
        <svg viewBox="0 0 24 24">{!! $iconPaths['layers'] !!}</svg>
      </span>
      <span class="health-status {{ $systemHealth['cache']['status'] }}">
        <svg viewBox="0 0 24 24">{!! $iconPaths['check'] !!}</svg>
      </span>
    </div>
    <div class="health-card-body">
      <h4>Cache</h4>
      <p>{{ $systemHealth['cache']['message'] }}</p>
    </div>
  </div>
  <div class="health-card {{ $systemHealth['disk_usage']['status'] === 'healthy' ? 'healthy' : ($systemHealth['disk_usage']['status'] === 'warning' ? 'warning' : 'error') }}">
    <div class="health-card-header">
      <span class="health-icon">
        <svg viewBox="0 0 24 24">{!! $iconPaths['hard-drive'] !!}</svg>
      </span>
      <span class="health-status-text">{{ $systemHealth['disk_usage']['percentage'] ?? 'N/A' }}%</span>
    </div>
    <div class="health-card-body">
      <h4>Storage</h4>
      <p>
        @if(isset($systemHealth['disk_usage']['used']))
          {{ $systemHealth['disk_usage']['used'] }} / {{ $systemHealth['disk_usage']['total'] }}
        @else
          {{ $systemHealth['disk_usage']['message'] ?? 'Unknown' }}
        @endif
      </p>
    </div>
    @if(isset($systemHealth['disk_usage']['percentage']))
      @php $diskPercentage = min(max((float) $systemHealth['disk_usage']['percentage'], 0), 100); @endphp
      <div class="health-progress">
        <div class="health-progress-bar" style="width:{{ $diskPercentage }}%"></div>
      </div>
    @endif
  </div>
</div>

<div class="superadmin-stats-grid">
  @foreach($stats as $label => $value)
    <div class="stat-card">
      <div class="stat-card-icon">
        <svg viewBox="0 0 24 24">{!! $iconPaths[$statIcons[$label]] !!}</svg>
      </div>
      <div class="stat-card-content">
        <div class="stat-card-value">{{ number_format($value) }}</div>
        <div class="stat-card-label">{{ $label }}</div>
      </div>
    </div>
  @endforeach
</div>

<div class="card superadmin-actions-wrapper">
  <div class="cardhead"><h3>Quick Actions</h3></div>
  <div class="superadmin-actions-grid">
    <form method="post" action="/superadmin/clear-cache">
      @csrf
      <button class="action-btn" type="submit">
        <svg viewBox="0 0 24 24">{!! $iconPaths['broom'] !!}</svg>
        <span>Clear Cache</span>
      </button>
    </form>
    <form method="post" action="/superadmin/optimize">
      @csrf
      <button class="action-btn" type="submit">
        <svg viewBox="0 0 24 24">{!! $iconPaths['zap'] !!}</svg>
        <span>Optimize</span>
      </button>
    </form>
    <form method="post" action="/superadmin/backup">
      @csrf
      <button class="action-btn" type="submit">
        <svg viewBox="0 0 24 24">{!! $iconPaths['archive'] !!}</svg>
        <span>Backup</span>
      </button>
    </form>
    <a class="action-btn" href="/superadmin/logs">
      <svg viewBox="0 0 24 24">{!! $iconPaths['file-text'] !!}</svg>
      <span>View Logs</span>
    </a>
    <a class="action-btn" href="/superadmin/users">
      <svg viewBox="0 0 24 24">{!! $iconPaths['users'] !!}</svg>
      <span>Users</span>
    </a>
    <a class="action-btn" href="/superadmin/monitoring">
      <svg viewBox="0 0 24 24">{!! $iconPaths['chart'] !!}</svg>
      <span>Monitor</span>
    </a>
  </div>
</div>

<div class="superadmin-bottom-grid">
  <div class="card">
    <div class="cardhead">
      <h3>Recent Errors</h3>
      <a class="link" href="/superadmin/logs?level=error">View all →</a>
    </div>
    @forelse($recentErrors as $log)
      <div class="log-item error">
        <div class="log-item-icon">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <div class="log-item-content">
          <div class="log-item-title">{{ $log->category }}</div>
          <div class="log-item-message">{{ Str::limit($log->message, 70) }}</div>
          <div class="log-item-time">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</div>
        </div>
      </div>
    @empty
      <div class="empty-state">
        <svg viewBox="0 0 24 24">{!! $iconPaths['check'] !!}</svg>
        <p>No recent errors</p>
      </div>
    @endforelse
  </div>

  <div class="card">
    <div class="cardhead"><h3>User Activity</h3></div>
    <div class="activity-list">
      <div class="activity-item">
        <div class="activity-label">Today</div>
        <div class="activity-value">{{ $userActivity['today'] }}</div>
      </div>
      <div class="activity-item">
        <div class="activity-label">This Week</div>
        <div class="activity-value">{{ $userActivity['this_week'] }}</div>
      </div>
      <div class="activity-item">
        <div class="activity-label">This Month</div>
        <div class="activity-value">{{ $userActivity['this_month'] }}</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="cardhead">
      <h3>Recent Activity</h3>
      <a class="link" href="/superadmin/logs">View all →</a>
    </div>
    @forelse($recentLogs->take(5) as $log)
      <div class="log-item {{ $log->level }}">
        <div class="log-item-content">
          <div class="log-item-title">{{ $log->category }}</div>
          <div class="log-item-message">{{ Str::limit($log->message, 50) }}</div>
        </div>
        <span class="badge {{ $log->level }}">{{ $log->level }}</span>
      </div>
    @empty
      <div class="empty-state">
        <svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        <p>No recent activity</p>
      </div>
    @endforelse
  </div>
</div>
</div>

@endsection
