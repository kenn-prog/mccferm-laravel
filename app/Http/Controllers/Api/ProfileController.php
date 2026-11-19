<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Transaction, FermsRequest};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Get user profile with statistics
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Get user statistics
        $stats = [
            'total_requests' => FermsRequest::where('user_id', $user->id)->count(),
            'pending_requests' => FermsRequest::where('user_id', $user->id)
                ->where('status', 'pending')->count(),
            'approved_requests' => FermsRequest::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'released'])->count(),
            'borrowed_count' => Transaction::where('user_id', $user->id)
                ->where('status', 'borrowed')->sum('quantity'),
            'returned_count' => Transaction::where('user_id', $user->id)
                ->where('status', 'returned')->count(),
        ];

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
            'statistics' => $stats,
        ], 200);
    }

    /**
     * Update user profile
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'organization_class' => 'nullable|string|max:100',
            'student_employee_id' => [
                'required',
                'string',
                'regex:/\A[0-9]{4}-[0-9]{4}\z/',
                Rule::unique('users', 'student_employee_id')->ignore($user->id),
            ],
            'phone' => [
                'required',
                'string',
                'regex:/\A09[0-9]{9}\z/',
            ],
        ], [
            'phone.regex' => 'Phone number must be 11 digits starting with 09',
            'student_employee_id.regex' => 'ID must be in format 1234-5678',
            'student_employee_id.unique' => 'This ID is already registered',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
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
