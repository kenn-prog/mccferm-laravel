@extends('layouts.app')@section('content')<div class="page-header">
  <div class="page-header-content">
    <h1>Equipment Management</h1>
    <p class="sub">Available stock is adjusted automatically by borrowing and returns.</p>
  </div>
  <div class="page-actions">
    <button class="btn s l btn-icon" type="button" data-toggle-target="edit-equipment-form" aria-haspopup="dialog" aria-expanded="false" aria-controls="edit-equipment-form" @disabled($rows->isEmpty())>
      <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit
    </button>
    <button class="btn btn-icon" type="button" data-toggle-target="add-equipment-form" aria-haspopup="dialog" aria-expanded="false" aria-controls="add-equipment-form">
      <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Add equipment
    </button>
  </div>
</div>
<dialog class="card add-form" id="add-equipment-form"><div class="add-form-header"><h3>Add equipment</h3><button class="btn s l" type="button" data-close-dialog>Cancel</button></div><form method="post" action="/admin/equipment">@csrf<div class="row"><div class="form-field"><label for="add-equipment-name">Name</label><input id="add-equipment-name" name="name" required></div><div class="form-field"><label for="add-equipment-category">Category</label><input id="add-equipment-category" name="category" required></div><div class="form-field"><label for="add-equipment-quantity">Quantity</label><input id="add-equipment-quantity" name="total_quantity" type="number" min="0" required></div><div class="form-field"><label for="add-equipment-condition">Condition</label><input id="add-equipment-condition" name="condition" value="Good" required></div></div><button class="btn">Add</button></form></dialog>
<div class="table-container">
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Category</th>
        <th>Total</th>
        <th>Available</th>
        <th>Condition</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $e)
        <tr>
          <td><b>{{ $e->name }}</b></td>
          <td>{{ $e->category }}</td>
          <td>{{ $e->total_quantity }}</td>
          <td>{{ $e->available_quantity }}</td>
          <td>{{ $e->condition }}</td>
          <td><span class="badge {{ $e->status }}">{{ $e->status }}</span></td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
<dialog class="card add-form" id="edit-equipment-form"><div class="add-form-header"><h3>Edit equipment</h3><button class="btn s l" type="button" data-close-dialog>Cancel</button></div><form method="post" action="/admin/equipment" data-edit-form="/admin/equipment">@csrf<div class="form-field"><label for="edit-equipment-select">Equipment</label><select id="edit-equipment-select" data-edit-select required><option value="">Select equipment</option>@foreach($rows as $e)<option value="{{ $e->id }}" data-name="{{ $e->name }}" data-category="{{ $e->category }}" data-total-quantity="{{ $e->total_quantity }}" data-condition="{{ $e->condition }}" data-status="{{ $e->status }}">{{ $e->name }}</option>@endforeach</select></div><div class="row"><div class="form-field"><label for="edit-equipment-name">Name</label><input id="edit-equipment-name" name="name" data-edit-field="name" required disabled></div><div class="form-field"><label for="edit-equipment-category">Category</label><input id="edit-equipment-category" name="category" data-edit-field="category" required disabled></div><div class="form-field"><label for="edit-equipment-quantity">Quantity</label><input id="edit-equipment-quantity" type="number" name="total_quantity" data-edit-field="total_quantity" min="0" required disabled></div><div class="form-field"><label for="edit-equipment-condition">Condition</label><input id="edit-equipment-condition" name="condition" data-edit-field="condition" required disabled></div><div class="form-field"><label for="edit-equipment-status">Status</label><select id="edit-equipment-status" name="status" data-edit-field="status" disabled>@foreach(['available','unavailable','maintenance'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div></div><button class="btn" type="submit" disabled>Save changes</button></form></dialog>
@endsection
