@extends('layouts.app')
@section('content')
<div class="page-heading">
  <div>
    <h1>Users</h1>
    <p class="sub">Only School Administrators can change roles.</p>
  </div>
  @if(auth()->user()->role==='admin')
    <div class="page-actions">
      <button class="btn s l" type="button" data-toggle-target="edit-user-form" aria-haspopup="dialog" aria-expanded="false" aria-controls="edit-user-form" @disabled($rows->isEmpty())>Edit</button>
    </div>
  @endif
</div>
<div class="card scroll">
  <table>
    <tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr>
    @foreach($rows as $u)
      <tr>
        <td>{{ $u->name }}</td>
        <td>{{ $u->email }}</td>
        <td>{{ $u->role }}</td>
        <td>{{ $u->is_active ? 'Yes' : 'No' }}</td>
      </tr>
    @endforeach
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
