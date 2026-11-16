<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#f3f7ff">
  <title>Sign in · MCC-FERMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/ferms.css">
  <style>
    body.login-body {
      display: block;
      min-height: 100vh;
      min-height: 100svh;
      background: #f3f7ff url('/images/login-background.png') center / cover no-repeat;
    }
    .login-page {
      width: min(1440px, 100%);
      min-height: 100vh;
      min-height: 100svh;
      margin: 0 auto;
      padding: clamp(24px, 5vw, 72px);
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(340px, 430px);
      align-items: center;
      gap: clamp(36px, 8vw, 120px);
    }
    .login-intro {
      min-height: min(660px, calc(100svh - 96px));
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .login-brand {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      width: fit-content;
      color: var(--navy);
      text-decoration: none;
    }
    .login-mark {
      width: 42px;
      height: 42px;
      display: grid;
      place-items: center;
      border-radius: 14px;
      background: linear-gradient(145deg, #1769e0, #38b6ff);
      box-shadow: 0 8px 18px rgba(23, 105, 224, .2);
      color: #fff;
      font-size: 19px;
      font-weight: 800;
      letter-spacing: -.06em;
    }
    .login-brand-name {
      display: block;
      font-size: 16px;
      font-weight: 800;
      letter-spacing: .01em;
    }
    .login-brand-caption {
      display: block;
      margin-top: 2px;
      color: #58739e;
      font-size: 10px;
      font-weight: 600;
    }
    .login-message {
      max-width: 580px;
      padding: 48px 0;
    }
    .login-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 18px;
      color: #436aab;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: .15em;
      text-transform: uppercase;
    }
    .login-eyebrow::before {
      width: 22px;
      height: 2px;
      border-radius: 2px;
      background: #2879ed;
      content: "";
    }
    .login-message h1 {
      max-width: 540px;
      margin: 0;
      color: #102d68;
      font-size: clamp(42px, 6vw, 74px);
      font-weight: 800;
      letter-spacing: -.065em;
      line-height: 1.02;
    }
    .login-message p {
      max-width: 440px;
      margin: 20px 0 0;
      color: #49658f;
      font-size: clamp(15px, 1.4vw, 18px);
      line-height: 1.7;
    }
    .login-footer {
      color: #58739e;
      font-size: 12px;
      font-weight: 600;
    }
    .login-card {
      width: 100%;
      padding: clamp(26px, 4vw, 38px);
      border: 1px solid rgba(255, 255, 255, .86);
      border-radius: 22px;
      background: rgba(255, 255, 255, .88);
      box-shadow: 0 22px 65px rgba(31, 74, 145, .16), 0 2px 8px rgba(31, 74, 145, .05);
      backdrop-filter: blur(18px);
    }
    .login-card h2 {
      margin: 0;
      color: #122e65;
      font-size: 22px;
      font-weight: 800;
      letter-spacing: -.035em;
    }
    .login-card-copy {
      margin: 9px 0 26px;
      color: #657b9d;
      font-size: 13px;
      line-height: 1.6;
    }
    .microsoft-button {
      min-height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 11px;
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 13.5px;
      text-align: center;
    }
    .microsoft-mark {
      width: 17px;
      height: 17px;
      display: grid;
      flex: none;
      grid-template-columns: 1fr 1fr;
      grid-template-rows: 1fr 1fr;
      gap: 2px;
    }
    .microsoft-mark i:nth-child(1) { background: #f25022; }
    .microsoft-mark i:nth-child(2) { background: #7fba00; }
    .microsoft-mark i:nth-child(3) { background: #00a4ef; }
    .microsoft-mark i:nth-child(4) { background: #ffb900; }
    .login-divider {
      height: 1px;
      margin: 25px 0 20px;
      background: #e7edf7;
    }
    .login-access-note {
      margin: 0;
      color: #637a9e;
      font-size: 12px;
      line-height: 1.6;
      text-align: center;
    }
    .dev-heading {
      margin: 0 0 10px;
      color: #657b9d;
      font-size: 10px;
      font-weight: 800;
      letter-spacing: .12em;
    }
    .dev {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 8px;
    }
    .dev .btn {
      min-width: 0;
      text-align: center;
    }
    @media (max-width: 820px) {
      .login-page {
        grid-template-columns: minmax(0, 1fr) minmax(320px, 390px);
        gap: 28px;
        padding: 32px;
      }
      .login-intro {
        min-height: 580px;
      }
      .login-message h1 {
        font-size: clamp(40px, 7vw, 58px);
      }
    }
    @media (max-width: 680px) {
      body.login-body {
        background-position: 58% center;
      }
      .login-page {
        min-height: 100svh;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
        padding: 24px 20px;
      }
      .login-intro {
        min-height: 0;
        gap: 26px;
      }
      .login-message {
        padding: 0 0 8px;
      }
      .login-message h1 {
        max-width: 440px;
        font-size: clamp(38px, 11vw, 54px);
      }
      .login-message p {
        max-width: 440px;
        margin-top: 12px;
        font-size: 14px;
      }
      .login-footer {
        display: none;
      }
      .login-card {
        max-width: 480px;
        justify-self: center;
        padding: 26px 22px;
      }
    }
    @media (max-width: 380px) {
      .login-page {
        padding: 20px 16px;
      }
      .login-card h2 {
        font-size: 20px;
      }
      .microsoft-button {
        font-size: 12.5px;
      }
    }
  </style>
</head>
<body class="login-body">
  <main class="login-page">
    <section class="login-intro" aria-label="MCC FERMS">
      <a class="login-brand" href="/login" aria-label="MCC FERMS home">
        <span class="login-mark" aria-hidden="true">F</span>
        <span>
          <span class="login-brand-name">MCC FERMS</span>
          <span class="login-brand-caption">Mabalacat City College</span>
        </span>
      </a>
      <div class="login-message">
        <div class="login-eyebrow">Campus facilities, made easy</div>
        <h1>Your campus.<br>Ready when you are.</h1>
        <p>Reserve facilities and request the equipment you need for learning, events, and campus activities.</p>
      </div>
      <div class="login-footer">Facility &amp; Equipment Reservation Management System</div>
    </section>

    <section class="login-card" aria-labelledby="login-title">
      <h2 id="login-title">Sign in to your account</h2>
      <p class="login-card-copy">Continue securely with your MCC Microsoft account.</p>
      <a class="btn microsoft-button" href="/auth/microsoft">
        <span class="microsoft-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
        <span>Sign in with Microsoft</span>
      </a>

      @if(app()->environment('local'))
        <div class="login-divider"></div>
        <div class="dev-heading">DEVELOPMENT LOGIN</div>
        <div class="dev">
          @foreach(['student','organization','staff','admin','superadmin'] as $r)
            <a class="btn l s" href="/dev-login/{{ $r }}">{{ $r==='superadmin' ? 'IT Administrator' : ucfirst($r) }}</a>
          @endforeach
        </div>
      @else
        <div class="login-divider"></div>
        <p class="login-access-note">Need help accessing your account? Contact your system administrator.</p>
      @endif
    </section>
  </main>
  @if($errors->any())
    <dialog class="confirm-dialog error-dialog" id="error-dialog" aria-labelledby="error-dialog-title">
      <div class="add-form-header"><h3 id="error-dialog-title">Sign-in error</h3></div>
      <div class="error-dialog-messages">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
      <div class="row confirm-actions"><button class="btn" type="button" data-close-error>OK</button></div>
    </dialog>
    <script>
      const errorDialog = document.getElementById('error-dialog');
      errorDialog.showModal();
      errorDialog.querySelector('[data-close-error]').addEventListener('click', function () {
        errorDialog.close();
      });
      errorDialog.addEventListener('click', function (event) {
        if (event.target === errorDialog) errorDialog.close();
      });
    </script>
  @endif
</body>
</html>
