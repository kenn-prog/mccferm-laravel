<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Exchange a short-lived PKCE-protected Microsoft sign-in code for an API token.
     */
    public function exchangeMobileTicket(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'min:32', 'max:128'],
            'code_verifier' => ['required', 'string', 'min:43', 'max:128', 'regex:/\A[A-Za-z0-9._~-]+\z/'],
        ]);

        $cacheKey = 'mobile-auth:' . hash('sha256', $validated['code']);
        $ticket = Cache::get($cacheKey);

        if (!is_array($ticket) || !isset($ticket['user_id'], $ticket['code_challenge'])) {
            return response()->json(['message' => 'The sign-in code is invalid or expired. Please try again.'], 422);
        }

        $challenge = rtrim(strtr(
            base64_encode(hash('sha256', $validated['code_verifier'], true)),
            '+/',
            '-_',
        ), '=');

        if (!hash_equals($ticket['code_challenge'], $challenge)) {
            return response()->json(['message' => 'The sign-in code is invalid or expired. Please try again.'], 422);
        }

        $ticket = Cache::pull($cacheKey);
        if (!is_array($ticket) || !isset($ticket['user_id'])) {
            return response()->json(['message' => 'The sign-in code is invalid or expired. Please try again.'], 422);
        }

        $user = User::find($ticket['user_id']);
        if (!$user || !$user->is_active) {
            return response()->json(['message' => 'This account is inactive or unavailable.'], 403);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'student_employee_id' => $user->student_employee_id,
                'organization_class' => $user->organization_class,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
            'token' => $user->createToken('mobile-app')->plainTextToken,
        ]);
    }

    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account is inactive. Please contact the administrator.',
            ], 403);
        }

        // Create token
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'student_employee_id' => $user->student_employee_id,
                'organization_class' => $user->organization_class,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
            'token' => $token,
        ], 200);
    }

    /**
     * Register new user
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:student,organization',
            'student_employee_id' => 'required|string|regex:/\A[0-9]{4}-[0-9]{4}\z/|unique:users',
            'organization_class' => 'nullable|string|max:100',
            'phone' => 'required|string|regex:/\A09[0-9]{9}\z/',
        ], [
            'student_employee_id.regex' => 'Student/Employee ID must be in format 1234-5678',
            'phone.regex' => 'Phone must be 11 digits starting with 09',
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        // Create token
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'student_employee_id' => $user->student_employee_id,
                'organization_class' => $user->organization_class,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'student_employee_id' => $user->student_employee_id,
                'organization_class' => $user->organization_class,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
        ], 200);
    }
}
