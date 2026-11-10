@extends('layouts.app')
@section('content')
<h1>My Requests</h1>
<p class="sub">Approved requests still need a signed letter from the school office. Pending requests can be edited or cancelled.</p>
<div class="card scroll">
  <table>
    <tr><th>Type</th><th>Details</th><th>When</th><th>Status</th><th>Actions</th></tr>
    @forelse($rows as $q)
      <tr>
        <td>{{ ucfirst($q->type) }}</td>
        <td>{{ $q->type==='facility' ? $q->reservation->facility->name : $q->item->equipment->name.' ×'.$q->item->quantity }}<br><small>{{ $q->purpose }}</small></td>
        <td>{{ $q->type==='facility' ? $q->reservation->starts_at->format('M d, g:i A') : $q->item->borrow_date->format('M d').' → '.$q->item->expected_return->format('M d') }}</td>
        <td><span class="badge {{ $q->status }}">{{ ucfirst($q->status) }}</span></td>
        <td style="white-space:nowrap">
          @if($q->status==='pending')
            <button class="btn s" type="button" data-toggle-target="edit-request-{{ $q->id }}" aria-haspopup="dialog" aria-expanded="false" aria-controls="edit-request-{{ $q->id }}">Edit</button>
            <form method="post" action="/requests/{{ $q->id }}/cancel" data-edit-form="/requests/{{ $q->id }}/cancel" data-confirm-action="cancel" data-confirm-name="{{ $q->type==='facility' ? $q->reservation->facility->name : $q->item->equipment->name }}" style="display:inline">
              @csrf
              <button class="btn s o" type="submit">Cancel</button>
            </form>
          @else
            <span class="sub">—</span>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="5">No requests yet.</td></tr>
    @endforelse
  </table>
</div>

@foreach($rows->where('status','pending') as $q)
  <dialog class="card add-form" id="edit-request-{{ $q->id }}">
    <div class="add-form-header">
      <h3>Edit {{ $q->type }} request</h3>
      <button class="btn s l" type="button" data-close-dialog>Close</button>
    </div>
    @if($q->type==='facility')
      <form method="post" action="/requests/{{ $q->id }}/edit" data-edit-form="/requests/{{ $q->id }}/edit" data-confirm-name="{{ $q->reservation->facility->name }}">
        @csrf
        <input type="hidden" name="request_id" value="{{ $q->id }}">
        <div class="row">
          <div class="form-field">
            <label for="request-{{ $q->id }}-facility">Facility</label>
            <select id="request-{{ $q->id }}-facility" name="facility_id" data-edit-field="facility_id" data-original="{{ $q->reservation->facility_id }}" required>
              @foreach($facilities as $facility)
                @if($facility->status==='available' || $facility->id===$q->reservation->facility_id || (old('request_id')==$q->id && old('facility_id')==$facility->id))
                  <option value="{{ $facility->id }}" @selected(old('request_id')==$q->id ? old('facility_id', $q->reservation->facility_id)==$facility->id : $facility->id===$q->reservation->facility_id)>{{ $facility->name }}{{ $facility->status!=='available' ? ' (currently unavailable)' : '' }}</option>
                @endif
              @endforeach
            </select>
          </div>
          <div class="form-field">
            <label for="request-{{ $q->id }}-date">Date</label>
            <input id="request-{{ $q->id }}-date" type="date" name="date" data-edit-field="date" data-original="{{ $q->reservation->starts_at->toDateString() }}" min="{{ today()->toDateString() }}" value="{{ old('request_id')==$q->id ? old('date', $q->reservation->starts_at->toDateString()) : $q->reservation->starts_at->toDateString() }}" required>
          </div>
          <div class="form-field">
            <label for="request-{{ $q->id }}-start">Start</label>
            <input id="request-{{ $q->id }}-start" type="time" name="start_time" data-edit-field="start_time" data-original="{{ $q->reservation->starts_at->format('H:i') }}" value="{{ old('request_id')==$q->id ? old('start_time', $q->reservation->starts_at->format('H:i')) : $q->reservation->starts_at->format('H:i') }}" required>
          </div>
          <div class="form-field">
            <label for="request-{{ $q->id }}-end">End</label>
            <input id="request-{{ $q->id }}-end" type="time" name="end_time" data-edit-field="end_time" data-original="{{ $q->reservation->ends_at->format('H:i') }}" value="{{ old('request_id')==$q->id ? old('end_time', $q->reservation->ends_at->format('H:i')) : $q->reservation->ends_at->format('H:i') }}" required>
          </div>
          <div class="form-field">
            <label for="request-{{ $q->id }}-participants">Participants</label>
            <input id="request-{{ $q->id }}-participants" type="number" name="participants" data-edit-field="participants" data-original="{{ $q->participants }}" min="1" value="{{ old('request_id')==$q->id ? old('participants', $q->participants) : $q->participants }}">
          </div>
        </div>
        <div class="form-field">
          <label for="request-{{ $q->id }}-purpose">Purpose / activity</label>
          <input id="request-{{ $q->id }}-purpose" name="purpose" data-edit-field="purpose" data-original="{{ $q->purpose }}" value="{{ old('request_id')==$q->id ? old('purpose', $q->purpose) : $q->purpose }}" required>
        </div>
        <div class="form-field">
          <label for="request-{{ $q->id }}-notes">Notes</label>
          <textarea id="request-{{ $q->id }}-notes" name="notes" data-edit-field="notes" data-original="{{ $q->notes }}">{{ old('request_id')==$q->id ? old('notes', $q->notes) : $q->notes }}</textarea>
        </div>
        <button class="btn" type="submit">Save changes</button>
      </form>
    @else
      <form method="post" action="/requests/{{ $q->id }}/edit" data-edit-form="/requests/{{ $q->id }}/edit" data-confirm-name="{{ $q->item->equipment->name }}">
        @csrf
        <input type="hidden" name="request_id" value="{{ $q->id }}">
        <div class="row">
          <div class="form-field">
            <label for="request-{{ $q->id }}-equipment">Equipment</label>
            <select id="request-{{ $q->id }}-equipment" name="equipment_id" data-edit-field="equipment_id" data-original="{{ $q->item->equipment_id }}" required>
              @foreach($items as $equipment)
                @if(($equipment->status==='available' && $equipment->available_quantity>0) || $equipment->id===$q->item->equipment_id || (old('request_id')==$q->id && old('equipment_id')==$equipment->id))
                  <option value="{{ $equipment->id }}" @selected(old('request_id')==$q->id ? old('equipment_id', $q->item->equipment_id)==$equipment->id : $equipment->id===$q->item->equipment_id)>{{ $equipment->name }}{{ $equipment->status!=='available' ? ' (currently unavailable)' : '' }}</option>
                @endif
              @endforeach
            </select>
          </div>
          <div class="form-field">
            <label for="request-{{ $q->id }}-quantity">Quantity</label>
            <input id="request-{{ $q->id }}-quantity" type="number" name="quantity" data-edit-field="quantity" data-original="{{ $q->item->quantity }}" min="1" value="{{ old('request_id')==$q->id ? old('quantity', $q->item->quantity) : $q->item->quantity }}" required>
          </div>
          <div class="form-field">
            <label for="request-{{ $q->id }}-borrow-date">Borrow date</label>
            <input id="request-{{ $q->id }}-borrow-date" type="date" name="borrow_date" data-edit-field="borrow_date" data-original="{{ $q->item->borrow_date->toDateString() }}" data-borrow-date min="{{ today()->toDateString() }}" value="{{ old('request_id')==$q->id ? old('borrow_date', $q->item->borrow_date->toDateString()) : $q->item->borrow_date->toDateString() }}" required>
          </div>
          <div class="form-field">
            <label for="request-{{ $q->id }}-return-date">Expected return</label>
            <input id="request-{{ $q->id }}-return-date" type="date" name="expected_return" data-edit-field="expected_return" data-original="{{ $q->item->expected_return->toDateString() }}" data-return-date data-borrow-date-id="request-{{ $q->id }}-borrow-date" min="{{ $q->item->borrow_date->toDateString() }}" max="{{ $q->item->borrow_date->copy()->addDays(5)->toDateString() }}" value="{{ old('request_id')==$q->id ? old('expected_return', $q->item->expected_return->toDateString()) : $q->item->expected_return->toDateString() }}" required>
          </div>
        </div>
        <div class="form-field">
          <label for="request-{{ $q->id }}-purpose">Purpose</label>
          <input id="request-{{ $q->id }}-purpose" name="purpose" data-edit-field="purpose" data-original="{{ $q->purpose }}" value="{{ old('request_id')==$q->id ? old('purpose', $q->purpose) : $q->purpose }}" required>
        </div>
        <button class="btn" type="submit">Save changes</button>
      </form>
    @endif
  </dialog>
@endforeach
@if(session('request_followup'))
  <dialog class="confirm-dialog request-followup-dialog" id="request-followup-dialog">
    <div class="add-form-header">
      <h3>Would you also like to {{ session('request_followup')==='equipment' ? 'borrow equipment' : 'reserve a facility' }}?</h3>
    </div>
    <p>Requesting both can help you plan your activity.</p>
    <div class="row confirm-actions">
      <button class="btn l" type="button" data-finish-request>No, finish</button>
      <a class="btn" href="{{ session('request_followup')==='equipment' ? '/equipment?request_followup=1' : '/facilities?request_followup=1' }}">Yes, continue</a>
    </div>
  </dialog>
@endif
@if(session('request_reminder') || session('request_followup'))
  <dialog class="confirm-dialog" id="request-reminder-dialog">
    <div class="add-form-header">
      <h3>Request submitted</h3>
      <button class="btn s l" type="button" data-close-request-reminder>Close</button>
    </div>
    <p>Please visit the staff office to sign the required letter for your request.</p>
  </dialog>
  <script>
    const requestFollowupDialog = document.getElementById('request-followup-dialog');
    const requestReminderDialog = document.getElementById('request-reminder-dialog');
    if (requestFollowupDialog) {
      requestFollowupDialog.showModal();
      requestFollowupDialog.querySelector('[data-finish-request]').addEventListener('click', function () {
        requestFollowupDialog.close();
        requestReminderDialog.showModal();
      });
    } else if (requestReminderDialog) {
      requestReminderDialog.showModal();
    }
    requestReminderDialog.querySelector('[data-close-request-reminder]').addEventListener('click', function () {
      requestReminderDialog.close();
    });
  </script>
@endif
@if($errors->any() && old('request_id'))
  <script>document.getElementById('edit-request-{{ old('request_id') }}')?.showModal();</script>
@endif
@endsection
