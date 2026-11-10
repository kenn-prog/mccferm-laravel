<?php
namespace App\Http\Controllers;
use App\Models\User; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth; use Laravel\Socialite\Facades\Socialite;
class AuthController extends Controller {
  public function show() { return view('auth.login'); }
  public function redirect() { return Socialite::driver('microsoft')->redirect(); }
  public function callback() {
    $ms = Socialite::driver('microsoft')->user(); $domain = config('services.microsoft.email_domain');
    if (!str_ends_with(strtolower($ms->getEmail()), '@'.$domain)) return redirect('/login')->withErrors('Only authorized MCC Microsoft accounts may sign in.');
    $u = User::firstOrCreate(['email'=>strtolower($ms->getEmail())], ['name'=>$ms->getName(),'role'=>'student']); // roles assigned by admin
    abort_unless($u->is_active, 403); Auth::login($u); return redirect('/');
  }
  public function dev(string $role) { abort_unless(app()->environment('local'), 404); Auth::login(User::where('role',$role)->firstOrFail()); return redirect('/'); }
  public function logout(Request $r) { Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return redirect('/login'); }
}
