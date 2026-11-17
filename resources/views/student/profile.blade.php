@extends('layouts.app')
@section('content')
<div class="page-header">
  <div class="page-header-content">
    <h1>Profile & Settings</h1>
    <p class="sub">Manage your personal information and account security.</p>
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
        <form method="post" action="/profile">
            @csrf
            <label>Full Name</label>
            <input name="name" value="{{ old('name', auth()->user()->name) }}" required>

            <label>Email Address</label>
            <input type="email" value="{{ auth()->user()->email }}" disabled style="background: var(--sky2); cursor: not-allowed;">
            <small style="color: var(--mute); font-size: 13px; display: block; margin-top: -8px;">Email cannot be changed. Contact a system administrator if needed.</small>

            <label>{{ auth()->user()->role === 'organization' ? 'Organization / Class' : 'Class / Section' }}</label>
            <input name="organization_class" value="{{ old('organization_class', auth()->user()->organization_class) }}" placeholder="{{ auth()->user()->role === 'organization' ? 'Organization or class' : 'e.g., BSIT 3A' }}">

            <label>Student ID</label>
            <input type="text" name="student_employee_id" value="{{ old('student_employee_id', auth()->user()->student_employee_id ?? '') }}" placeholder="1234-5678" pattern="[0-9]{4}-[0-9]{4}" minlength="9" maxlength="9" title="Enter your ID in the format 1234-5678" required>

            <label>Phone Number</label>
            <input type="tel" name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}" placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" minlength="11" maxlength="11" inputmode="numeric" autocomplete="tel" title="Enter 11 digits starting with 09" required>

            <button class="btn" style="margin-top: 8px;">Save Changes</button>
        </form>
    </div>

    <div class="profile-sidebar">
        <div class="enhanced-card">
            <div class="cardhead"><h3>Account Details</h3></div>
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

        <div class="enhanced-card">
            <div class="cardhead"><h3>Activity Stats</h3></div>
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

@endsection
