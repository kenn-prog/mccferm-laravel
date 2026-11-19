<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }
    public function redirect()
    {
        return $this->redirectToMicrosoft();
    }

    public function mobileRedirect(Request $request)
    {
        $validated = $request->validate([
            'code_challenge' => ['required', 'string', 'regex:/\A[A-Za-z0-9_-]{43,128}\z/'],
            'code_challenge_method' => ['required', 'in:S256'],
        ]);

        session([
            'mobile_oauth' => true,
            'mobile_oauth_challenge' => $validated['code_challenge'],
        ]);

        return $this->redirectToMicrosoft(true);
    }

    private function redirectToMicrosoft(bool $mobile = false)
    {
        if (!config('services.microsoft.client_id') || !config('services.microsoft.client_secret')) {
            if ($mobile) {
                return $this->redirectToMobileApp(['error' => 'microsoft_not_configured']);
            }

            return redirect('/login')->withErrors([
                'microsoft' => 'Microsoft sign-in is not configured. Contact the system administrator.',
            ]);
        }

        return Socialite::driver('microsoft')->redirect();
    }

    public function registerForm()
    {
        $email = session('pending_microsoft_email');
        $name = session('pending_microsoft_name');

        if (! $email) {
            return redirect('/login')->withErrors(['microsoft' => 'Your Microsoft sign-in session expired. Please sign in again.']);
        }

        return view('auth.register', compact('email', 'name'));
    }

    public function storeRegistration(Request $request)
    {
        $email = session('pending_microsoft_email');
        $mobileRegistration = (bool) session('pending_mobile_oauth', false);
        $codeChallenge = session('pending_mobile_oauth_challenge');

        if (! $email) {
            return redirect('/login')->withErrors(['microsoft' => 'Your Microsoft sign-in session expired. Please sign in again.']);
        }

        if ($mobileRegistration && !is_string($codeChallenge)) {
            return $this->redirectToMobileApp(['error' => 'invalid_auth_request']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:student,organization'],
            'organization_class' => ['required', 'string', 'max:100'],
            'student_employee_id' => ['required', 'string', 'regex:/\A[0-9]{4}-[0-9]{4}\z/', 'unique:users,student_employee_id'],
            'phone' => ['required', 'string', 'regex:/\A09[0-9]{9}\z/'],
        ], [
            'phone.regex' => 'Enter an 11-digit phone number starting with 09.',
            'student_employee_id.regex' => 'Enter your school ID in the format 1234-5678.',
            'student_employee_id.unique' => 'This student or employee ID is already registered.',
        ]);

        $email = strtolower($email);
        if (User::where('email', $email)->exists()) {
            if ($mobileRegistration) {
                return $this->redirectToMobileApp(['error' => 'account_already_registered']);
            }

            return redirect('/login')->withErrors([
                'microsoft' => 'This MCC account is already registered. Sign in with Microsoft to continue.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'role' => $data['role'],
            'organization_class' => $data['organization_class'] ?? null,
            'student_employee_id' => $data['student_employee_id'] ?? null,
            'phone' => $data['phone'],
            'is_active' => true,
        ]);

        session()->forget([
            'pending_microsoft_email',
            'pending_microsoft_name',
            'pending_mobile_oauth',
            'pending_mobile_oauth_challenge',
        ]);
        if ($mobileRegistration) {
            return $this->issueMobileTicket($user, $codeChallenge);
        }

        Auth::login($user);

        return redirect($this->roleRedirect($user));
    }

    public function callback(Request $request)
    {
        $mobile = (bool) session()->pull('mobile_oauth', false);
        $codeChallenge = session()->pull('mobile_oauth_challenge');

        if ($mobile && $request->query('error')) {
            return $this->redirectToMobileApp(['error' => 'sign_in_cancelled']);
        }

        if (!config('services.microsoft.client_id') || !config('services.microsoft.client_secret')) {
            if ($mobile) {
                return $this->redirectToMobileApp(['error' => 'microsoft_not_configured']);
            }

            return redirect('/login')->withErrors([
                'microsoft' => 'Microsoft sign-in is not configured. Contact the system administrator.',
            ]);
        }

        $ms = Socialite::driver('microsoft')->user();
        $email = strtolower($ms->getEmail() ?? '');
        $domain = strtolower(config('services.microsoft.email_domain', 'mcc.edu.ph'));

        if (!str_ends_with($email, '@' . $domain)) {
            if ($mobile) {
                return $this->redirectToMobileApp(['error' => 'unauthorized_account']);
            }

            return redirect('/login')->withErrors(['microsoft' => 'Only authorized MCC Microsoft accounts may sign in.']);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            if ($mobile) {
                if (!is_string($codeChallenge)) {
                    return $this->redirectToMobileApp(['error' => 'invalid_auth_request']);
                }
            }

            session([
                'pending_microsoft_email' => $email,
                'pending_microsoft_name' => $ms->getName() ?: preg_replace('/@.*/', '', $email),
                'pending_mobile_oauth' => $mobile,
                'pending_mobile_oauth_challenge' => $mobile ? $codeChallenge : null,
            ]);

            return redirect('/register')->with('info', 'Your MCC email is not yet registered. Complete the form to continue.');
        }

        if (! $user->is_active) {
            if ($mobile) {
                return $this->redirectToMobileApp(['error' => 'account_inactive']);
            }

            abort(403, 'This MCC account is inactive.');
        }

        if ($mobile) {
            if (!is_string($codeChallenge)) {
                return $this->redirectToMobileApp(['error' => 'invalid_auth_request']);
            }

            return $this->issueMobileTicket($user, $codeChallenge);
        }

        Auth::login($user);

        return redirect($this->roleRedirect($user));
    }

    private function redirectToMobileApp(array $parameters)
    {
        return redirect()->away(
            'mccferms://auth/callback?' . http_build_query($parameters),
        );
    }

    private function issueMobileTicket(User $user, string $codeChallenge)
    {
        $code = Str::random(64);
        Cache::put(
            'mobile-auth:' . hash('sha256', $code),
            ['user_id' => $user->id, 'code_challenge' => $codeChallenge],
            now()->addMinutes(2),
        );

        return $this->redirectToMobileApp(['code' => $code]);
    }

    public function dev(string $role)
    {
        abort_unless(app()->environment('local'), 404);
        Auth::login(User::where('role', $role)->firstOrFail());

        return redirect('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    protected function roleRedirect(?User $user = null): string
    {
        $user = $user ?: Auth::user();

        if (! $user) {
            return '/';
        }

        if ($user->role === 'superadmin') {
            return '/superadmin';
        }

        return $user->isAdminSide() ? '/admin' : '/dashboard';
    }
}
