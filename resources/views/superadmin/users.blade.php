@extends('layouts.app')
@section('content')
<div class="head">
  <div>
    <h1>User Management</h1>
    <p class="sub">Manage user accounts, roles, and permissions.</p>
  </div>
</div>

<!-- Role Statistics -->
<div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));margin-bottom:1.5rem">
  @foreach(['superadmin'=>'Super Admins','admin'=>'Admins','staff'=>'Staff','organization'=>'Organizations','student'=>'Students'] as $role => $label)
    <div class="card stat">
      <b>{{ $roleStats[$role] ?? 0 }}</b>
      <span>{{ $label }}</span>
    </div>
  @endforeach
</div>

<!-- Users Table -->
<div class="card">
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Joined</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($users as $user)
        <tr style="{{ !$user->is_active ? 'opacity:0.6' : '' }}">
          <td><b>{{ $user->name }}</b></td>
          <td style="font-family:monospace;font-size:0.9rem">{{ $user->email }}</td>
          <td>
            <form method="post" action="/superadmin/users/{{ $user->id }}/role" style="display:inline">
              @csrf
              <select name="role" class="input" style="padding:0.25rem 0.5rem;font-size:0.85rem" onchange="this.form.submit()">
                <option value="student" {{ $user->role==='student' ? 'selected' : '' }}>Student</option>
                <option value="organization" {{ $user->role==='organization' ? 'selected' : '' }}>Organization</option>
                <option value="staff" {{ $user->role==='staff' ? 'selected' : '' }}>Staff</option>
                <option value="admin" {{ $user->role==='admin' ? 'selected' : '' }}>Admin</option>
                <option value="superadmin" {{ $user->role==='superadmin' ? 'selected' : '' }}>Super Admin</option>
              </select>
            </form>
          </td>
          <td>
            <span class="badge {{ $user->is_active ? 'approved' : 'rejected' }}">
              {{ $user->is_active ? 'Active' : 'Inactive' }}
            </span>
          </td>
          <td style="white-space:nowrap">{{ $user->created_at->format('M d, Y') }}</td>
          <td>
            <form method="post" action="/superadmin/users/{{ $user->id }}/toggle" style="display:inline">
              @csrf
              <button class="btn s {{ $user->is_active ? 'warn o' : '' }}" type="submit">
                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
              </button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

@endsection
