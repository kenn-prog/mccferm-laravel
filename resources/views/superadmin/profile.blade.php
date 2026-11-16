@extends('layouts.app')
@section('content')
<div class="head">
  <div>
    <h1>Profile</h1>
    <p class="sub">Manage your IT Administrator account information.</p>
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

@endsection
