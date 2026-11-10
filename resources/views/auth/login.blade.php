<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · MCC-FERMS</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="/css/ferms.css">
<style>body{align-items:center;justify-content:center;padding:20px}.box{width:100%;max-width:410px;background:#fff;border:1px solid var(--line);border-radius:24px;box-shadow:0 20px 50px rgba(23,105,224,.15);overflow:hidden}.box .top2{background:linear-gradient(135deg,#0a4fc8,#38b6ff);color:#fff;padding:30px 28px}.box .in{padding:26px 28px 30px}.dev{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px}</style></head>
<body><div class="box">
  <div class="top2"><div class="brand"><i style="background:#fff;color:var(--blue);box-shadow:none">F</i><div><b style="color:#fff;font-size:18px">MCC FERMS</b><small style="color:#d6e8ff;max-width:none">Facility Equipment Reservation Management System</small></div></div>
    <h1 style="color:#fff;margin:22px 0 4px">Welcome</h1><div style="color:#d6e8ff;font-size:14px">Sign in to reserve facilities and borrow equipment.</div></div>
  <div class="in">
    @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
    <a class="btn" style="display:block;text-align:center" href="/auth/microsoft">Sign in with MCC Microsoft account</a>
    @if(app()->environment('local'))
      <div style="margin-top:22px;font-size:12px;font-weight:700;color:var(--mute);letter-spacing:.08em">DEVELOPMENT LOGIN</div>
      <div class="dev">@foreach(['student','organization','staff','admin'] as $r)<a class="btn s l" style="text-align:center" href="/dev-login/{{ $r }}">{{ ucfirst($r) }}</a>@endforeach</div>
    @endif
  </div>
</div></body></html>
