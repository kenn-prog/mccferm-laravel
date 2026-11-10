@extends('layouts.app')
@section('content')
<h1>Profile & Settings</h1>
<p class="sub">Manage your personal information and account security.</p>

<div class="card hero">
    <b>{{ auth()->user()->name }}</b>
    <p style="margin: 8px 0 0; display: block;">
        {{ auth()->user()->email }} ·
        <span class="badge" style="background: rgba(255,255,255,0.2); color: #fff;">{{ ucfirst(auth()->user()->role) }}</span>
    </p>
</div>

<div class="grid profile-grid" style="grid-template-columns: 2fr 1fr;">
    <div class="card">
        <h3>Personal Information</h3>
        <form method="post" action="/profile">
            @csrf
            <label>Full Name</label>
            <input name="name" value="{{ old('name', auth()->user()->name) }}" required>

            <label>Email Address</label>
            <input type="email" value="{{ auth()->user()->email }}" disabled style="background: var(--sky2); cursor: not-allowed;">
            <small style="color: var(--mute); font-size: 13px; display: block; margin-top: -8px;">Email cannot be changed. Contact a system administrator if needed.</small>

            <label>{{ auth()->user()->role === 'organization' ? 'Organization / Class' : 'Class / Section' }}</label>
            <input name="organization_class" value="{{ old('organization_class', auth()->user()->organization_class) }}" placeholder="{{ auth()->user()->role === 'organization' ? 'Organization or class' : 'e.g., BSIT 3A' }}">

            <label>Phone Number (Optional)</label>
            <input type="tel" name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}" placeholder="+63 9XX XXX XXXX">

            <button class="btn" style="margin-top: 8px;">Save Changes</button>
        </form>
    </div>

    <div class="profile-sidebar">
        <div class="card">
            <h3>Account Details</h3>
            <div style="margin-bottom: 16px;">
                <small style="color: var(--mute); display: block; margin-bottom: 4px;">Account Type</small>
                <span class="badge pending" style="font-size: 13px;">{{ ucfirst(auth()->user()->role) }}</span>
            </div>
            <div style="margin-bottom: 16px;">
                <small style="color: var(--mute); display: block; margin-bottom: 4px;">Status</small>
                <span class="badge {{ auth()->user()->is_active ? 'approved' : 'rejected' }}">{{ auth()->user()->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <div style="margin-bottom: 16px;">
                <small style="color: var(--mute); display: block; margin-bottom: 4px;">Member Since</small>
                <strong>{{ auth()->user()->created_at->format('M d, Y') }}</strong>
            </div>
            <div>
                <small style="color: var(--mute); display: block; margin-bottom: 4px;">Last Updated</small>
                <strong>{{ auth()->user()->updated_at->diffForHumans() }}</strong>
            </div>
        </div>

        <div class="card">
            <h3>Activity Stats</h3>
            <div style="margin-bottom: 12px;">
                <small style="color: var(--mute); display: block;">Requests Submitted</small>
                <strong style="font-size: 20px; color: var(--blue);">{{ $requestCount }}</strong>
            </div>
            <div>
                <small style="color: var(--mute); display: block;">Currently Borrowed</small>
                <strong style="font-size: 20px; color: var(--blue);">{{ $borrowedCount }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <h3>Password & Security</h3>
    <p class="sub" style="margin-bottom: 20px;">Change your password to keep your account secure.</p>
    <form method="post" action="/profile/password">
        @csrf
        <div class="row">
            <div>
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div>
                <label>New Password</label>
                <input type="password" name="password" required minlength="8">
                <small style="color: var(--mute); font-size: 13px; display: block; margin-top: 4px;">Minimum 8 characters</small>
            </div>
            <div>
                <label>Confirm New Password</label>
                <input type="password" name="password_confirmation" required>
            </div>
        </div>
        <button class="btn" style="margin-top: 8px;">Update Password</button>
    </form>
</div>
@endsection
