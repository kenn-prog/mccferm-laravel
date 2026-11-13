<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Complete MCC FERMS registration</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/ferms.css">
  <style>
    body{align-items:center;justify-content:center;padding:20px}.box{width:100%;max-width:560px;background:#fff;border:1px solid var(--line);border-radius:24px;box-shadow:0 20px 50px rgba(23,105,224,.15);overflow:hidden}.box .top2{background:linear-gradient(135deg,#0a4fc8,#38b6ff);color:#fff;padding:30px 28px}.box .in{padding:26px 28px 30px}.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.field label{display:block;margin-bottom:8px;font-weight:700;color:#3f5a86}.field input,.field select{margin:0}.field{margin-bottom:14px}@media(max-width:520px){.row{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="box">
  <div class="top2">
    <div class="brand"><i style="background:#fff;color:var(--blue);box-shadow:none">F</i><div><b style="color:#fff;font-size:18px">MCC FERMS</b><small style="color:#d6e8ff;max-width:none">Facility Equipment Reservation Management System</small></div></div>
    <h1 style="color:#fff;margin:22px 0 4px">Complete your profile</h1>
    <div style="color:#d6e8ff;font-size:14px">Your MCC email is not yet registered. Fill in the details below.</div>
  </div>
  <div class="in">
    @if(session('info'))
      <div class="alert ok" style="margin-bottom:16px">{{ session('info') }}</div>
    @endif
    <form method="POST" action="/register">
      @csrf
      <div class="row">
        <div class="field">
          <label for="name">Full name</label>
          <input id="name" type="text" name="name" value="{{ old('name', $name ?? '') }}" placeholder="Your full name" required>
        </div>
        <div class="field">
          <label for="email">MCC email</label>
          <input id="email" type="email" value="{{ $email }}" disabled>
        </div>
      </div>
      <div class="row">
        <div class="field">
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <option value="">Select role</option>
            <option value="student" {{ old('role') === 'student' ? 'selected' : '' }}>Student</option>
            <option value="organization" {{ old('role') === 'organization' ? 'selected' : '' }}>Organization</option>
          </select>
        </div>
        <div class="field">
          <label for="phone">Phone number</label>
          <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="09XX XXX XXXX" required>
        </div>
      </div>
      <div class="row">
        <div class="field">
          <label for="organization_class">Class / organization</label>
          <input id="organization_class" type="text" name="organization_class" value="{{ old('organization_class') }}" placeholder="BSIT 3A or Student Council">
        </div>
        <div class="field">
          <label for="student_employee_id">Employee / student ID</label>
          <input id="student_employee_id" type="text" name="student_employee_id" value="{{ old('student_employee_id') }}" placeholder="Optional for students">
        </div>
      </div>
      <button class="btn" type="submit" style="width:100%">Create account</button>
    </form>
  </div>
</div>
@if($errors->any())
  <dialog class="confirm-dialog error-dialog" id="error-dialog" aria-labelledby="error-dialog-title">
    <div class="add-form-header"><h3 id="error-dialog-title">Registration error</h3></div>
    <div class="error-dialog-messages">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    <div class="row confirm-actions"><button class="btn" type="button" data-close-error>OK</button></div>
  </dialog>
  <script>
    const errorDialog = document.getElementById('error-dialog');
    errorDialog.showModal();
    errorDialog.querySelector('[data-close-error]').addEventListener('click', function () { errorDialog.close(); });
    errorDialog.addEventListener('click', function (event) { if (event.target === errorDialog) errorDialog.close(); });
  </script>
@endif
</body>
</html>
