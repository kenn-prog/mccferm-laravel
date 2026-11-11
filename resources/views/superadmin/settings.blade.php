@extends('layouts.app')
@section('content')
<div class="head">
  <div>
    <h1>System Settings</h1>
    <p class="sub">Configure system-wide parameters and behavior.</p>
  </div>
  <a class="btn o" href="/superadmin">← Back to Dashboard</a>
</div>

<div class="card">
  <form method="post" action="/superadmin/settings">
    @csrf
    <div class="cardhead">
      <h3>Configuration</h3>
      <button class="btn" type="submit">Save Changes</button>
    </div>
    
    @foreach($settings as $setting)
      <div class="row" style="border-bottom:1px solid var(--border);padding:1rem 0">
        <div style="flex:1">
          <label style="display:block;font-weight:600;margin-bottom:0.25rem">
            {{ ucwords(str_replace('_', ' ', $setting->key)) }}
          </label>
          <small style="color:var(--muted);display:block">{{ $setting->description }}</small>
        </div>
        <div style="min-width:200px">
          @if($setting->type === 'boolean')
            <select name="settings[{{ $loop->index }}][value]" class="input" required>
              <option value="true" {{ $setting->value === 'true' ? 'selected' : '' }}>Enabled</option>
              <option value="false" {{ $setting->value === 'false' ? 'selected' : '' }}>Disabled</option>
            </select>
          @elseif($setting->type === 'integer')
            <input type="number" name="settings[{{ $loop->index }}][value]" class="input" value="{{ $setting->value }}" required>
          @else
            <input type="text" name="settings[{{ $loop->index }}][value]" class="input" value="{{ $setting->value }}" required>
          @endif
          <input type="hidden" name="settings[{{ $loop->index }}][key]" value="{{ $setting->key }}">
        </div>
      </div>
    @endforeach
  </form>
</div>

<!-- Maintenance Mode Control -->
<div class="card">
  <div class="cardhead"><h3>Maintenance Mode</h3></div>
  <div class="row">
    <div style="flex:1">
      <p>Enable maintenance mode to restrict access to the system for all users except superadmins.</p>
      <small style="color:var(--muted)">Users will see a maintenance page when trying to access the system.</small>
    </div>
    <form method="post" action="/superadmin/maintenance-toggle">
      @csrf
      <button class="btn {{ collect($settings)->firstWhere('key', 'maintenance_mode')?->value === 'true' ? 'warn' : 'o' }}" type="submit">
        {{ collect($settings)->firstWhere('key', 'maintenance_mode')?->value === 'true' ? 'Disable' : 'Enable' }} Maintenance Mode
      </button>
    </form>
  </div>
</div>

@endsection
