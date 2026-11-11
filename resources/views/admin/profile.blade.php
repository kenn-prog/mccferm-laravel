@extends('layouts.app')
@section('content')
<div class="page-header">
  <div class="page-header-content">
    <h1>Profile & Settings</h1>
    <p class="sub">Manage your account information and system preferences.</p>
  </div>
</div>

<div class="hero-card" style="margin-bottom:20px">
    <b style="font-size:18px">{{ auth()->user()->name }}</b>
    <p style="margin: 8px 0 0; display: block;">
        {{ auth()->user()->email }} · 
        <span class="badge" style="background: rgba(255,255,255,0.25); color: #fff; border:1px solid rgba(255,255,255,0.3)">{{ ucfirst(auth()->user()->role) }}</span>
    </p>
</div>

<div class="content-grid" style="grid-template-columns: 2fr 1fr;">
    <div class="enhanced-card">
        <div class="cardhead"><h3>Personal Information</h3></div>
        <form method="post" action="/admin/profile">
            @csrf
            <label>Full Name</label>
            <input name="name" value="{{ old('name', auth()->user()->name) }}" required>
            
            <label>Email Address</label>
            <input type="email" value="{{ auth()->user()->email }}" disabled style="background: var(--bg-secondary); cursor: not-allowed;">
            <small style="color: var(--text-muted); font-size: 13px; display: block; margin-top: -8px;">Email cannot be changed. Contact system administrator if needed.</small>
            
            <label>Department / Office</label>
            <input name="organization_class" value="{{ old('organization_class', auth()->user()->organization_class) }}" placeholder="e.g., ICT Office, Registrar, Admin Office">
            
            <label>Phone Number</label>
            <input type="tel" name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}" placeholder="09XX XXX XXXX" required>
            
            <button class="btn" style="margin-top: 8px;">Save Changes</button>
        </form>
    </div>

    <!-- Account Information -->
    <div class="profile-sidebar">
        <div class="card">
            <h3>Account Details</h3>
            <div style="margin-bottom: 16px;">
                <small style="color: var(--text-muted); display: block; margin-bottom: 4px;">Account Type</small>
                <span class="badge {{ auth()->user()->role === 'admin' ? 'approved' : 'pending' }}" style="font-size: 13px;">
                    {{ ucfirst(auth()->user()->role) }}
                </span>
            </div>
            
            <div style="margin-bottom: 16px;">
                <small style="color: var(--text-muted); display: block; margin-bottom: 4px;">Status</small>
                <span class="badge {{ auth()->user()->is_active ? 'approved' : 'rejected' }}">
                    {{ auth()->user()->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            
            <div style="margin-bottom: 16px;">
                <small style="color: var(--text-muted); display: block; margin-bottom: 4px;">Member Since</small>
                <strong>{{ auth()->user()->created_at->format('M d, Y') }}</strong>
            </div>
            
            <div>
                <small style="color: var(--text-muted); display: block; margin-bottom: 4px;">Last Login</small>
                <strong>{{ auth()->user()->updated_at->diffForHumans() }}</strong>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card">
            <h3>Activity Stats</h3>
            <div style="margin-bottom: 12px;">
                <small style="color: var(--text-muted); display: block;">Processed Requests</small>
                <strong style="font-size: 20px; color: var(--blue);">—</strong>
            </div>
            <div>
                <small style="color: var(--text-muted); display: block;">Avg. Response Time</small>
                <strong style="font-size: 20px; color: var(--blue);">—</strong>
            </div>
            <small style="color: var(--text-muted); font-size: 12px; display: block; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border);">
                Statistics coming soon
            </small>
        </div>
    </div>
</div>

<!-- Password & Security -->
<div class="card">
    <h3>Password & Security</h3>
    <p class="sub" style="margin-bottom: 20px;">Change your password to keep your account secure.</p>
    
    <form method="post" action="/admin/profile/password">
        @csrf
        <div class="row">
            <div>
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div>
                <label>New Password</label>
                <input type="password" name="password" required minlength="8">
                <small style="color: var(--text-muted); font-size: 13px; display: block; margin-top: 4px;">
                    Minimum 8 characters
                </small>
            </div>
            <div>
                <label>Confirm New Password</label>
                <input type="password" name="password_confirmation" required>
            </div>
        </div>
        <button class="btn" style="margin-top: 8px;">Update Password</button>
    </form>
</div>

<!-- System Preferences (Admin Only) -->
@if(auth()->user()->role === 'admin')
<div class="card">
    <h3>System Preferences</h3>
    <p class="sub" style="margin-bottom: 20px;">Configure system-wide settings and notifications.</p>
    
    <form method="post" action="/admin/settings">
        @csrf
        <div class="row">
            <div>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 0;">
                    <input type="checkbox" name="email_notifications" checked style="width: auto;">
                    Email Notifications
                </label>
                <small style="color: var(--text-muted); font-size: 13px; display: block; margin-top: 4px;">
                    Receive email alerts for new requests
                </small>
            </div>
            
            <div>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 0;">
                    <input type="checkbox" name="auto_approve_reservations" style="width: auto;">
                    Auto-approve Reservations
                </label>
                <small style="color: var(--text-muted); font-size: 13px; display: block; margin-top: 4px;">
                    Automatically approve facility reservations
                </small>
            </div>
        </div>
        
        <label>Default Reservation Duration (hours)</label>
        <select name="default_duration">
            <option value="1">1 hour</option>
            <option value="2" selected>2 hours</option>
            <option value="3">3 hours</option>
            <option value="4">4 hours</option>
            <option value="8">8 hours (full day)</option>
        </select>
        
        <label>Maximum Borrow Period (days)</label>
        <input type="number" name="max_borrow_days" value="7" min="1" max="30">
        
        <button class="btn" style="margin-top: 16px;">Save Preferences</button>
    </form>
</div>
@endif

<!-- Danger Zone -->
<div class="card" style="border-color: var(--danger-bg);">
    <h3 style="color: var(--danger);">Danger Zone</h3>
    <p style="color: var(--text-secondary); margin-bottom: 16px;">
        Actions in this section are irreversible. Proceed with caution.
    </p>
    
    <div style="padding: 16px; background: var(--danger-bg); border-radius: var(--radius-sm); border: 1px solid var(--danger); margin-bottom: 12px;">
        <strong style="display: block; margin-bottom: 4px; color: var(--danger);">Deactivate Account</strong>
        <small style="color: #991b1b; display: block; margin-bottom: 8px;">
            Your account will be disabled and you will lose access to the system.
        </small>
        <button class="btn o" style="font-size: 13px; padding: 6px 12px;" onclick="return confirm('Are you sure you want to deactivate your account?')">
            Deactivate Account
        </button>
    </div>
</div>

@endsection
