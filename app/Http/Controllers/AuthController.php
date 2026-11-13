<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }
    public function redirect()
    {
        if (!config('services.microsoft.client_id') || !config('services.microsoft.client_secret')) {
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

        if (! $email) {
            return redirect('/login')->withErrors(['microsoft' => 'Your Microsoft sign-in session expired. Please sign in again.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:student,organization'],
            'organization_class' => ['nullable', 'string', 'max:100'],
            'student_employee_id' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        if (in_array($data['role'], ['student', 'organization'], true) && empty($data['organization_class'])) {
            $request->validate(['organization_class' => 'required|string|max:100'], ['organization_class.required' => 'Class or organization name is required for this role.']);
        }

        $email = strtolower($email);
        if (User::where('email', $email)->exists()) {
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

        session()->forget(['pending_microsoft_email', 'pending_microsoft_name']);
        Auth::login($user);

        return redirect($this->roleRedirect($user));
    }

    public function callback()
    {
        if (!config('services.microsoft.client_id') || !config('services.microsoft.client_secret')) {
            return redirect('/login')->withErrors([
                'microsoft' => 'Microsoft sign-in is not configured. Contact the system administrator.',
            ]);
        }

        $ms = Socialite::driver('microsoft')->user();
        $email = strtolower($ms->getEmail() ?? '');
        $domain = strtolower(config('services.microsoft.email_domain', 'mcc.edu.ph'));

        if (!str_ends_with($email, '@' . $domain)) {
            return redirect('/login')->withErrors(['microsoft' => 'Only authorized MCC Microsoft accounts may sign in.']);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            session([
                'pending_microsoft_email' => $email,
                'pending_microsoft_name' => $ms->getName() ?: preg_replace('/@.*/', '', $email),
            ]);

            return redirect('/register')->with('info', 'Your MCC email is not yet registered. Complete the form to continue.');
        }

        abort_unless($user->is_active, 403, 'This MCC account is inactive.');
        Auth::login($user);

        return redirect($this->roleRedirect($user));
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
