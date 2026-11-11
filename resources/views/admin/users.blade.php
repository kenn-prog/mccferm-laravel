@extends('layouts.app')
@section('content')
<div class="page-header">
  <div class="page-header-content">
    <h1>Users</h1>
    <p class="sub">Only School Administrators can change roles.</p>
  </div>
  @if(auth()->user()->role==='admin')
    <div class="page-actions">
      <button class="btn s l btn-icon" type="button" data-toggle-target="edit-user-form" aria-haspopup="dialog" aria-expanded="false" aria-controls="edit-user-form" @disabled($rows->isEmpty())>
        <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit
      </button>
    </div>
  @endif
</div>
<div class="table-container">
  <table>
    <thead>
      <tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr>
    </thead>
    <tbody>
      @foreach($rows as $u)
        <tr>
          <td><b>{{ $u->name }}</b></td>
          <td>{{ $u->email }}</td>
          <td><span class="badge pending">{{ ucfirst($u->role) }}</span></td>
          <td><span class="badge {{ $u->is_active ? 'approved' : 'rejected' }}">{{ $u->is_active ? 'Yes' : 'No' }}</span></td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@if(auth()->user()->role==='admin')
  <dialog class="card add-form" id="edit-user-form">
    <div class="add-form-header">
      <h3>Edit user</h3>
      <button class="btn s l" type="button" data-close-dialog>Cancel</button>
    </div>
    <form method="post" action="/admin/users" data-edit-form="/admin/users">
      @csrf
      <div class="form-field">
        <label for="edit-user-select">User</label>
        <select id="edit-user-select" data-edit-select required>
          <option value="">Select a user</option>
          @foreach($rows as $u)
            <option value="{{ $u->id }}" data-name="{{ $u->name }}" data-email="{{ $u->email }}" data-role="{{ $u->role }}" data-is-active="{{ $u->is_active ? '1' : '0' }}">{{ $u->name }} ({{ $u->email }})</option>
          @endforeach
        </select>
      </div>
      <div class="row">
        <div class="form-field">
          <label for="edit-user-role">Role</label>
          <select id="edit-user-role" name="role" data-edit-field="role" required disabled>
            @foreach(['student','organization','staff','admin'] as $r)
              <option value="{{ $r }}">{{ ucfirst($r) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-field">
          <label for="edit-user-active">Active</label>
          <select id="edit-user-active" name="is_active" data-edit-field="is_active" required disabled>
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>
      <button class="btn" type="submit" disabled>Save changes</button>
    </form>
  </dialog>
@endif
@endsection
