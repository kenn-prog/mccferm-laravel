<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class RoleMiddleware {
  public function handle(Request $r, Closure $next, string ...$roles) {
    $u = $r->user();
    abort_unless($u && $u->is_active && in_array($u->role, $roles), 403, 'You are not allowed to access this page.');
    return $next($r);
  }
}
