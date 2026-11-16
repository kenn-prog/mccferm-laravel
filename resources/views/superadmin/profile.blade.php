@extends('layouts.app')
@section('content')
<div class="head">
  <div>
    <h1>Profile</h1>
    <p class="sub">Manage your IT Administrator account information and password.</p>
  </div>
</div>

<div class="card">
  <div class="cardhead"><h3>Personal Information</h3></div>
  <form method="post" action="/superadmin/profile">
    @csrf
    <label for="name">Full Name</label>
    <input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required maxlength="255">

    <label for="email">Email Address</label>
    <input id="email" type="email" value="{{ auth()->user()->email }}" disabled>

    <label>Account Type</label>
    <p class="sub">IT Administrator</p>

    <button class="btn" type="submit">Save Profile</button>
  </form>
</div>

<div class="card">
  <div class="cardhead"><h3>Password &amp; Security</h3></div>
  <p class="sub">Change your password to keep your account secure.</p>
  <form method="post" action="/superadmin/profile/password">
    @csrf
    <div class="row">
      <div>
        <label for="current_password">Current Password</label>
        <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
      </div>
      <div>
        <label for="password">New Password</label>
        <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
      </div>
      <div>
        <label for="password_confirmation">Confirm New Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
      </div>
    </div>
    <button class="btn" type="submit">Update Password</button>
  </form>
</div>
@endsection
