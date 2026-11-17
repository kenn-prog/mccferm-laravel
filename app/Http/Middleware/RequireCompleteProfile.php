<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireCompleteProfile
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $isProfilePage = $request->is('profile') || $request->is('admin/profile');
        $hasValidStudentEmployeeId = preg_match('/\A[0-9]{4}-[0-9]{4}\z/', (string) $user->student_employee_id) === 1;
        $hasValidPhone = preg_match('/\A09[0-9]{9}\z/', (string) $user->phone) === 1;

        if (! $isProfilePage && (! $hasValidStudentEmployeeId || ! $hasValidPhone)) {
            return redirect($user->isAdminSide() ? '/admin/profile' : '/profile');
        }

        return $next($request);
    }
}
