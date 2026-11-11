@extends('layouts.app')@section('content')<div class="page-header">
  <div class="page-header-content">
    <h1>Requests & Borrowing</h1>
    <p class="sub">Approve or reject requests, release equipment, and record returns.</p>
  </div>
</div>
<form class="row card filter-form"><select name="type"><option value="">All types</option><option value="facility" @selected(request('type')=='facility')>Facility</option><option value="equipment" @selected(request('type')=='equipment')>Equipment</option></select><select name="status"><option value="">All active statuses</option>@foreach(['pending','approved'] as $s)<option @selected(request('status')==$s)>{{ $s }}</option>@endforeach</select><button class="btn">Filter</button></form>
<div class="table-container"><table><thead><tr><th>Requester</th><th>Details</th><th>Status</th><th>Actions</th></tr></thead><tbody>@foreach($rows as $q)<tr><td><b>{{ $q->user->name }}</b><br><small>{{ $q->organization_class }}</small></td><td><b>{{ $q->type==='facility'?$q->reservation->facility->name.' · '.$q->reservation->starts_at->format('M d g:i A').'–'.$q->reservation->ends_at->format('g:i A'):$q->item->equipment->name.' ×'.$q->item->quantity }}</b><br><small>{{ $q->purpose }}</small></td><td><span class="badge {{ $q->status }}">{{ $q->status }}</span></td><td style="white-space:nowrap">
@if($q->status==='pending')@foreach(['approved'=>'Approve','rejected'=>'Reject'] as $v=>$l)<form method="post" action="/admin/requests/{{ $q->id }}/decide" data-request-decision="{{ $v }}" data-request-name="{{ $q->user->name }}" style="display:inline">@csrf<input type="hidden" name="decision" value="{{ $v }}"><button class="btn s {{ $v=='rejected'?'o':'' }}">{{ $l }}</button></form> @endforeach @endif
@if($q->type==='equipment'&&$q->status==='approved')<form method="post" action="/admin/requests/{{ $q->id }}/release" style="display:inline">@csrf<button class="btn s">Release</button></form>@endif</td></tr>@endforeach</tbody></table></div>
<div class="table-container"><div class="cardhead" style="padding:18px 18px 0"><h3>Currently Borrowed</h3></div><table><thead><tr><th>Borrower</th><th>Item</th><th>Due</th><th></th></tr></thead><tbody>@forelse($trans as $t)<tr><td><b>{{ $t->user->name }}</b></td><td>{{ $t->equipment->name }} ×{{ $t->quantity }}</td><td>{{ $t->expected_return->format('M d') }} @if($t->is_overdue)<span class="badge rejected">Overdue</span>@endif</td><td><form method="post" action="/admin/transactions/{{ $t->id }}/return">@csrf<button class="btn s">Record return</button></form></td></tr>@empty<tr><td colspan="4" style="text-align:center;padding:24px;color:var(--mute)">Nothing borrowed.</td></tr>@endforelse</tbody></table></div>
<dialog class="confirm-dialog" id="request-decision-dialog" aria-labelledby="request-decision-title">
  <div class="add-form-header"><h3 id="request-decision-title">Confirm decision</h3></div>
  <p data-request-decision-message></p>
  <div class="row confirm-actions">
    <button class="btn l" type="button" data-cancel-request-decision>Cancel</button>
    <button class="btn" type="button" data-confirm-request-decision>Confirm</button>
  </div>
</dialog>
<script>
const requestDecisionDialog = document.getElementById('request-decision-dialog');
let pendingRequestDecision = null;
document.querySelectorAll('form[data-request-decision]').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    if (form.dataset.confirmed === 'true') {
      delete form.dataset.confirmed;
      return;
    }

    event.preventDefault();
    pendingRequestDecision = form;
    const decision = form.dataset.requestDecision;
    const action = decision === 'approved' ? 'approve' : 'reject';
    document.getElementById('request-decision-title').textContent = 'Confirm ' + action;
    requestDecisionDialog.querySelector('[data-request-decision-message]').textContent =
      'Are you sure you want to ' + action + ' ' + form.dataset.requestName + '\'s request?';
    const confirmButton = requestDecisionDialog.querySelector('[data-confirm-request-decision]');
    confirmButton.textContent = decision === 'approved' ? 'Approve request' : 'Reject request';
    confirmButton.classList.toggle('o', decision === 'rejected');
    requestDecisionDialog.showModal();
  });
});
requestDecisionDialog.querySelector('[data-cancel-request-decision]').addEventListener('click', function () {
  pendingRequestDecision = null;
  requestDecisionDialog.close();
});
requestDecisionDialog.querySelector('[data-confirm-request-decision]').addEventListener('click', function () {
  const form = pendingRequestDecision;
  if (!form) return;
  pendingRequestDecision = null;
  form.dataset.confirmed = 'true';
  requestDecisionDialog.close();
  form.requestSubmit();
});
requestDecisionDialog.addEventListener('close', function () {
  pendingRequestDecision = null;
});
requestDecisionDialog.addEventListener('click', function (event) {
  if (event.target === requestDecisionDialog) requestDecisionDialog.close();
});
</script>
@endsection
