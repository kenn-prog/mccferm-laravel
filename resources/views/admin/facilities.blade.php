@extends('layouts.app')@section('content')<div class="page-header">
  <div class="page-header-content">
    <h1>Facility Management</h1>
    <p class="sub">Add facilities and update their details and availability.</p>
  </div>
  <div class="page-actions">
    <button class="btn s l btn-icon" type="button" data-toggle-target="edit-facility-form" aria-haspopup="dialog" aria-expanded="false" aria-controls="edit-facility-form" @disabled($rows->isEmpty())>
      <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit
    </button>
    <button class="btn btn-icon" type="button" data-toggle-target="add-facility-form" aria-haspopup="dialog" aria-expanded="false" aria-controls="add-facility-form">
      <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Add facility
    </button>
  </div>
</div>
<dialog class="card add-form" id="add-facility-form"><div class="add-form-header"><h3>Add facility</h3><button class="btn s l" type="button" data-close-dialog>Cancel</button></div><form method="post" action="/admin/facilities">@csrf<div class="row"><div class="form-field"><label for="add-facility-name">Name</label><input id="add-facility-name" name="name" required></div><div class="form-field"><label for="add-facility-location">Location</label><input id="add-facility-location" name="location" required></div><div class="form-field"><label for="add-facility-category">Category</label><input id="add-facility-category" name="category" placeholder="Courts / Rooms / Halls / Outdoor" required></div><div class="form-field"><label for="add-facility-capacity">Capacity</label><input id="add-facility-capacity" name="capacity" type="number" required></div></div><button class="btn">Add</button></form></dialog>
<div class="table-container">
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Location</th>
        <th>Category</th>
        <th>Capacity</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $f)
        <tr>
          <td><b>{{ $f->name }}</b></td>
          <td>{{ $f->location }}</td>
          <td>{{ $f->category }}</td>
          <td>{{ $f->capacity }}</td>
          <td><span class="badge {{ $f->status }}">{{ $f->status }}</span></td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
<dialog class="card add-form" id="edit-facility-form"><div class="add-form-header"><h3>Edit facility</h3><button class="btn s l" type="button" data-close-dialog>Cancel</button></div><form method="post" action="/admin/facilities" data-edit-form="/admin/facilities">@csrf<div class="form-field"><label for="edit-facility-select">Facility</label><select id="edit-facility-select" data-edit-select required><option value="">Select a facility</option>@foreach($rows as $f)<option value="{{ $f->id }}" data-name="{{ $f->name }}" data-location="{{ $f->location }}" data-category="{{ $f->category }}" data-capacity="{{ $f->capacity }}" data-status="{{ $f->status }}"> {{ $f->name }}</option>@endforeach</select></div><div class="row"><div class="form-field"><label for="edit-facility-name">Name</label><input id="edit-facility-name" name="name" data-edit-field="name" required disabled></div><div class="form-field"><label for="edit-facility-location">Location</label><input id="edit-facility-location" name="location" data-edit-field="location" required disabled></div><div class="form-field"><label for="edit-facility-category">Category</label><input id="edit-facility-category" name="category" data-edit-field="category" required disabled></div><div class="form-field"><label for="edit-facility-capacity">Capacity</label><input id="edit-facility-capacity" name="capacity" type="number" data-edit-field="capacity" required disabled></div><div class="form-field"><label for="edit-facility-status">Status</label><select id="edit-facility-status" name="status" data-edit-field="status" disabled>@foreach(['available','reserved','unavailable'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div></div><button class="btn" type="submit" disabled>Save changes</button></form></dialog>
@endsection
