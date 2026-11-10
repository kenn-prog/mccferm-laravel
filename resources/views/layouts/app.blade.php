<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MCC-FERMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/ferms.css">
</head>
<body>
@php
  $u = auth()->user(); $adm = $u->isAdminSide();
  $ic = [
    'home'=>'<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
    'building'=>'<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-4h4v4"/>',
    'box'=>'<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
    'list'=>'<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    'swap'=>'<path d="M7 7h13l-3-3M17 17H4l3 3"/>',
    'mail'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
    'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
    'chart'=>'<path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/>',
    'users'=>'<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5M17 11a3 3 0 100-6M22 19c0-2.5-2-4-4-4.5"/>',
  ];
  $unread = $adm ? 0 : $u->notices()->where('is_read', false)->count();
  $nav = $adm
    ? [['/admin','Dashboard','home'],['/admin/facilities','Facilities','building'],['/admin/equipment','Equipment','box'],['/admin/requests','Requests & Borrowing','list'],['/admin/reports','Reports','chart'],['/admin/users','Users','users'],['/admin/profile','Profile & Settings','user']]
    : [['/dashboard','Dashboard','home'],['/facilities','Facilities','building'],['/equipment','Equipment','box'],['/requests','My Requests','list'],['/transactions','History','swap'],['/notifications','Inbox','mail'],['/profile','Profile','user']];
  $initials = collect(explode(' ', $u->name))->map(fn($p)=>mb_substr($p,0,1))->take(2)->implode('');
@endphp
<aside>
  <div class="brand"><i>F</i><div><b>MCC FERMS</b><small>Facility Equipment Reservation Management System</small></div></div>
  <div class="navlabel">{{ $adm ? 'ADMINISTRATION' : 'WORKSPACE' }}</div>
  @foreach($nav as [$url,$label,$icon])
    <a class="nav {{ '/'.request()->path()===$url ? 'on' : '' }}" href="{{ $url }}">
      <svg viewBox="0 0 24 24">{!! $ic[$icon] !!}</svg>{{ $label }}
      @if($url==='/notifications' && $unread)<span class="dot">{{ $unread }}</span>@endif
    </a>
  @endforeach
  <div class="me">
    <div class="who"><div class="av">{{ strtoupper($initials) }}</div><div><b>{{ $u->name }}</b><small>{{ $u->role==='admin' ? 'School Administrator' : ($u->role==='staff' ? 'Facility Staff' : ucfirst($u->role)) }}</small></div></div>
    <form method="post" action="/logout">@csrf<button class="btn l s logout">Log out</button></form>
  </div>
</aside>
<div class="wrap">
  <main>
    @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
    @if($errors->any())<div class="alert err">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>@endif
    @yield('content')
  </main>
</div>
<dialog class="card confirm-dialog" id="confirm-edit-dialog">
  <div class="add-form-header">
    <h3 data-confirm-edit-title>Confirm changes</h3>
    <button class="btn s l" type="button" data-cancel-edit>Cancel</button>
  </div>
  <p data-confirm-edit-message></p>
  <ul class="confirm-change-list" data-confirm-edit-changes></ul>
  <div class="row confirm-actions">
    <button class="btn l" type="button" data-cancel-edit>Go back</button>
    <button class="btn" type="button" data-approve-edit>Confirm changes</button>
  </div>
</dialog>
<script>
document.querySelectorAll('dialog.add-form').forEach(function (dialog) {
  dialog.addEventListener('close', function () {
    document.querySelectorAll('[data-toggle-target], [data-open-selected-dialog]').forEach(function (button) {
      if (button.getAttribute('aria-controls') === dialog.id) button.setAttribute('aria-expanded', 'false');
    });
  });
  dialog.querySelector('[data-close-dialog]').addEventListener('click', function () {
    dialog.close();
  });
  dialog.addEventListener('click', function (event) {
    if (event.target === dialog) dialog.close();
  });
});
document.querySelectorAll('[data-toggle-target]').forEach(function (button) {
  button.addEventListener('click', function () {
    const dialog = document.getElementById(button.dataset.toggleTarget);
    if (!(dialog instanceof HTMLDialogElement)) return;
    dialog.showModal();
    button.setAttribute('aria-expanded', 'true');
  });
});
const editConfirmation = document.getElementById('confirm-edit-dialog');
let pendingEditForm = null;
editConfirmation.querySelectorAll('[data-cancel-edit]').forEach(function (button) {
  button.addEventListener('click', function () {
    pendingEditForm = null;
    editConfirmation.close();
  });
});
editConfirmation.addEventListener('close', function () {
  pendingEditForm = null;
});
editConfirmation.querySelector('[data-approve-edit]').addEventListener('click', function () {
  const form = pendingEditForm;
  if (!form) return;
  pendingEditForm = null;
  form.dataset.editConfirmed = 'true';
  editConfirmation.close();
  form.requestSubmit();
});
document.querySelectorAll('form[data-edit-form]').forEach(function (form) {
  const select = form.querySelector('[data-edit-select]');
  const fields = form.querySelectorAll('[data-edit-field]');
  const submit = form.querySelector('[type="submit"]');

  if (select && submit) {
    select.addEventListener('change', function () {
      const option = select.selectedOptions[0];
      const selected = Boolean(select.value);
      fields.forEach(function (field) {
        const dataKey = field.dataset.editField.replace(/_([a-z])/g, function (_, letter) {
          return letter.toUpperCase();
        });
        field.disabled = !selected;
        field.value = selected ? option.dataset[dataKey] || '' : '';
      });
      form.action = selected ? form.dataset.editForm + '/' + select.value : form.dataset.editForm;
      submit.disabled = !selected;
    });
  }
  form.addEventListener('submit', function (event) {
    if (form.dataset.editConfirmed === 'true') {
      delete form.dataset.editConfirmed;
      return;
    }

    const selected = select && select.selectedOptions[0];
    const itemName = selected && selected.dataset.name || form.dataset.confirmName;
    const itemEmail = selected && selected.dataset.email;
    const message = editConfirmation.querySelector('[data-confirm-edit-message]');
    const changes = editConfirmation.querySelector('[data-confirm-edit-changes]');
    const title = editConfirmation.querySelector('[data-confirm-edit-title]');
    const approveButton = editConfirmation.querySelector('[data-approve-edit]');
    changes.replaceChildren();

    fields.forEach(function (field) {
      if (field.disabled) return;
      const key = field.dataset.editField.replace(/_([a-z])/g, function (_, letter) {
        return letter.toUpperCase();
      });
      const previous = selected && selected.dataset[key] !== undefined
        ? selected.dataset[key]
        : field.dataset.original || '';
      const current = field instanceof HTMLSelectElement
        ? field.selectedOptions[0].textContent.trim()
        : field.value.trim();
      const original = field instanceof HTMLSelectElement
        ? Array.from(field.options).find(function (option) {
            return option.value === previous;
          })?.textContent.trim() || previous
        : previous;
      if (String(field.value).trim() === String(previous).trim()) return;

      const label = form.querySelector('label[for="' + field.id + '"]');
      const item = document.createElement('li');
      item.textContent = (label ? label.textContent.trim() : field.name) + ': ' + original + ' → ' + current;
      changes.appendChild(item);
    });
    if (form.dataset.confirmAction === 'cancel') {
      const item = document.createElement('li');
      item.textContent = 'This request will be marked as cancelled and can no longer be edited.';
      changes.appendChild(item);
    } else if (!changes.childElementCount) {
      const item = document.createElement('li');
      item.textContent = 'No changes detected.';
      changes.appendChild(item);
    }

    event.preventDefault();
    message.textContent = form.dataset.confirmAction === 'cancel'
      ? 'Are you sure you want to cancel the request for ' + (itemName || 'this item') + '?'
      : itemName
        ? 'Are you sure you want to update ' + itemName + (itemEmail ? ' (' + itemEmail + ')' : '') + '?'
        : 'Are you sure you want to save these changes?';
    title.textContent = form.dataset.confirmAction === 'cancel' ? 'Confirm cancellation' : 'Confirm changes';
    approveButton.textContent = form.dataset.confirmAction === 'cancel' ? 'Cancel request' : 'Confirm changes';
    pendingEditForm = form;
    editConfirmation.showModal();
  });
});
document.querySelectorAll('[data-return-date]').forEach(function (returnDate) {
  const borrowDate = document.getElementById(returnDate.dataset.borrowDateId);
  if (!borrowDate) return;
  const updateLimits = function () {
    returnDate.min = borrowDate.value;
    if (!borrowDate.value) {
      returnDate.removeAttribute('max');
      return;
    }
    const latest = new Date(borrowDate.value + 'T00:00:00');
    latest.setDate(latest.getDate() + 5);
    returnDate.max = [
      latest.getFullYear(),
      String(latest.getMonth() + 1).padStart(2, '0'),
      String(latest.getDate()).padStart(2, '0')
    ].join('-');
  };
  borrowDate.addEventListener('change', updateLimits);
  updateLimits();
});
document.querySelectorAll('[data-school-open-date]').forEach(function (dateInput) {
  const validateSchoolDate = function () {
    const isSunday = dateInput.value && new Date(dateInput.value + 'T00:00:00').getDay() === 0;
    dateInput.setCustomValidity(isSunday ? 'The school is closed on Sundays. Please choose another date.' : '');
  };
  dateInput.addEventListener('input', validateSchoolDate);
  dateInput.addEventListener('change', validateSchoolDate);
  validateSchoolDate();
});
// Category chips (facilities / equipment pages).
document.querySelectorAll('.chip').forEach(function (c) {
  c.addEventListener('click', function () {
    document.querySelectorAll('.chip').forEach(x => x.classList.remove('on')); c.classList.add('on');
    const f = c.dataset.filter;
    document.querySelectorAll('[data-cat]').forEach(el => el.style.display = (f === 'all' || el.dataset.cat === f) ? '' : 'none');
  });
});
// "Reserve" / "Borrow" buttons preselect the item in the form below and scroll to it.
function pick(field, value, target) {
  const s = document.querySelector('[name=' + field + ']'); if (s) s.value = value;
  const t = document.getElementById(target);
  if (t instanceof HTMLDialogElement) {
    t.showModal();
    const trigger = document.querySelector('[data-open-selected-dialog][aria-controls="' + target + '"]');
    if (trigger) trigger.setAttribute('aria-expanded', 'true');
  } else if (t) {
    t.scrollIntoView({behavior: 'smooth'});
  }
}
</script>
</body>
</html>
