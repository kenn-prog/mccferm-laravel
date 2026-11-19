<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Facility, Equipment, FermsRequest, Reservation, Transaction, User, Notice};
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private function notify($userId, $title, $message, $type = 'info')
    {
        Notice::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ]);
    }

    /**
     * Admin dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $stats = [
            'total_reservations' => Reservation::count(),
            'pending_requests' => FermsRequest::where('status', 'pending')->count(),
            'approved_reservations' => Reservation::where('status', 'approved')->count(),
            'available_facilities' => Facility::where('status', 'available')->count(),
            'total_equipment' => Equipment::sum('total_quantity'),
            'currently_borrowed' => Transaction::where('status', 'borrowed')->sum('quantity'),
            'overdue_items' => Transaction::where('status', 'borrowed')
                ->whereDate('expected_return', '<', now())
                ->count(),
        ];

        $upcomingReservations = Reservation::with(['facility', 'request.user'])
            ->where('status', 'approved')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        $recentRequests = FermsRequest::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'statistics' => $stats,
            'upcoming_reservations' => $upcomingReservations,
            'recent_requests' => $recentRequests,
        ], 200);
    }

    /**
     * Get all requests for admin review
     */
    public function requests(Request $request)
    {
        $query = FermsRequest::with(['user', 'reservation.facility', 'item.equipment'])
            ->whereNotIn('status', ['rejected', 'cancelled', 'released', 'returned']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $requests = $query->latest()->get();

        return response()->json([
            'requests' => $requests,
        ], 200);
    }

    /**
     * Approve request
     */
    public function approve(Request $request, FermsRequest $fermsRequest)
    {
        if ($fermsRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Request already processed',
            ], 422);
        }

        try {
            DB::transaction(function () use ($fermsRequest) {
                if ($fermsRequest->type === 'facility') {
                    $reservation = $fermsRequest->reservation;
                    
                    // Check for conflicts
                    if (Reservation::conflicting($reservation->facility_id, $reservation->starts_at, $reservation->ends_at)->exists()) {
                        throw new \RuntimeException('Conflicts with an already approved reservation');
                    }
                    
                    $reservation->update(['status' => 'approved']);
                } elseif ($fermsRequest->type === 'equipment') {
                    // Check availability
                    if ($fermsRequest->item->equipment->available_quantity < $fermsRequest->item->quantity) {
                        throw new \RuntimeException('Not enough equipment available');
                    }
                }

                $fermsRequest->update(['status' => 'approved']);

                $this->notify(
                    $fermsRequest->user_id,
                    'Request Approved',
                    "Your {$fermsRequest->type} request \"{$fermsRequest->purpose}\" was approved.",
                    'success'
                );
            });
        } catch (\RuntimeException | QueryException $e) {
            return response()->json([
                'message' => $e instanceof QueryException ? 'Conflicting reservation detected' : $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Request approved successfully',
            'request' => $fermsRequest->fresh(['user', 'reservation.facility', 'item.equipment']),
        ], 200);
    }

    /**
     * Reject request
     */
    public function reject(Request $request, FermsRequest $fermsRequest)
    {
        if ($fermsRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Request already processed',
            ], 422);
        }

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($fermsRequest, $data) {
            if ($fermsRequest->type === 'facility') {
                $fermsRequest->reservation?->update(['status' => 'rejected']);
            }

            $fermsRequest->update(['status' => 'rejected']);

            $message = "Your {$fermsRequest->type} request \"{$fermsRequest->purpose}\" was rejected.";
            if (!empty($data['reason'])) {
                $message .= " Reason: {$data['reason']}";
            }

            $this->notify(
                $fermsRequest->user_id,
                'Request Rejected',
                $message,
                'danger'
            );
        });

        return response()->json([
            'message' => 'Request rejected',
        ], 200);
    }

    /**
     * Release equipment (create transaction)
     */
    public function release(Request $request, FermsRequest $fermsRequest)
    {
        if ($fermsRequest->type !== 'equipment' || $fermsRequest->status !== 'approved') {
            return response()->json([
                'message' => 'Invalid request for release',
            ], 422);
        }

        try {
            DB::transaction(function () use ($fermsRequest) {
                $item = $fermsRequest->item;
                $equipment = Equipment::lockForUpdate()->find($item->equipment_id);

                if ($equipment->available_quantity < $item->quantity) {
                    throw new \RuntimeException('Insufficient stock to release');
                }

                $equipment->decrement('available_quantity', $item->quantity);

                Transaction::create([
                    'request_id' => $fermsRequest->id,
                    'equipment_id' => $equipment->id,
                    'user_id' => $fermsRequest->user_id,
                    'quantity' => $item->quantity,
                    'borrow_date' => $item->borrow_date,
                    'borrow_time' => $item->borrow_time,
                    'expected_return' => $item->expected_return,
                    'expected_return_time' => $item->expected_return_time,
                ]);

                $fermsRequest->update(['status' => 'released']);

                $this->notify(
                    $fermsRequest->user_id,
                    'Equipment Released',
                    "{$equipment->name} x{$item->quantity} released. Return by {$item->expected_return->format('M d, Y')}.",
                    'info'
                );
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Equipment released successfully',
        ], 200);
    }

    /**
     * Get all transactions
     */
    public function transactions(Request $request)
    {
        $query = Transaction::with(['equipment', 'user']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Only borrowed
        if ($request->boolean('borrowed_only')) {
            $query->where('status', 'borrowed');
        }

        // Only overdue
        if ($request->boolean('overdue_only')) {
            $query->where('status', 'borrowed')
                  ->whereDate('expected_return', '<', now());
        }

        $transactions = $query->latest()->get();

        return response()->json([
            'transactions' => $transactions,
        ], 200);
    }

    /**
     * Mark equipment as returned
     */
    public function markReturned(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'borrowed') {
            return response()->json([
                'message' => 'Transaction is not in borrowed status',
            ], 422);
        }

        $data = $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($transaction, $data) {
            Equipment::lockForUpdate()
                ->find($transaction->equipment_id)
                ->increment('available_quantity', $transaction->quantity);

            $transaction->update([
                'status' => 'returned',
                'actual_return' => now(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            if ($transaction->request_id) {
                FermsRequest::find($transaction->request_id)?->update(['status' => 'returned']);
            }

            $this->notify(
                $transaction->user_id,
                'Return Recorded',
                "Your return of {$transaction->equipment->name} was recorded.",
                'success'
            );
        });

        return response()->json([
            'message' => 'Return recorded successfully',
        ], 200);
    }

    /**
     * Send return reminder
     */
    public function sendReminder(Transaction $transaction)
    {
        if ($transaction->status !== 'borrowed') {
            return response()->json([
                'message' => 'Only borrowed equipment can be reminded',
            ], 422);
        }

        $due = $transaction->expected_return->format('M d, Y');
        if ($transaction->expected_return_time) {
            $due .= ' at ' . \Carbon\Carbon::parse($transaction->expected_return_time)->format('g:i A');
        }

        $this->notify(
            $transaction->user_id,
            'Equipment Return Reminder',
            "Please return {$transaction->equipment->name} (quantity: {$transaction->quantity}) by {$due}. Contact staff if you need assistance.",
            'warning'
        );

        return response()->json([
            'message' => 'Reminder sent successfully',
        ], 200);
    }

    /**
     * Get all facilities (admin view)
     */
    public function facilities()
    {
        $facilities = Facility::orderBy('name')->get();

        return response()->json([
            'facilities' => $facilities,
        ], 200);
    }

    /**
     * Create facility
     */
    public function storeFacility(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255|unique:facilities,name',
            'location' => 'required',
            'category' => 'required',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'nullable|in:available,reserved,unavailable',
        ]);

        $facility = Facility::create($data);

        return response()->json([
            'message' => 'Facility created successfully',
            'facility' => $facility,
        ], 201);
    }

    /**
     * Update facility
     */
    public function updateFacility(Request $request, Facility $facility)
    {
        $data = $request->validate([
            'name' => 'required|max:255|unique:facilities,name,' . $facility->id,
            'location' => 'required',
            'category' => 'required',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'nullable|in:available,reserved,unavailable',
        ]);

        $facility->update($data);

        return response()->json([
            'message' => 'Facility updated successfully',
            'facility' => $facility->fresh(),
        ], 200);
    }

    /**
     * Delete facility
     */
    public function deleteFacility(Facility $facility)
    {
        // Check if facility has active reservations
        if (Reservation::where('facility_id', $facility->id)
            ->where('status', 'approved')
            ->where('ends_at', '>=', now())
            ->exists()) {
            return response()->json([
                'message' => 'Cannot delete facility with active reservations',
            ], 422);
        }

        $facility->delete();

        return response()->json([
            'message' => 'Facility deleted successfully',
        ], 200);
    }

    /**
     * Get all equipment (admin view)
     */
    public function equipment()
    {
        $equipment = Equipment::orderBy('name')->get();

        return response()->json([
            'equipment' => $equipment,
        ], 200);
    }

    /**
     * Create equipment
     */
    public function storeEquipment(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255|unique:equipment,name',
            'category' => 'required',
            'description' => 'nullable|string',
            'total_quantity' => 'required|integer|min:0',
            'condition' => 'required',
            'status' => 'nullable|in:available,unavailable,maintenance',
        ]);

        $data['available_quantity'] = $data['total_quantity'];
        $equipment = Equipment::create($data);

        return response()->json([
            'message' => 'Equipment created successfully',
            'equipment' => $equipment,
        ], 201);
    }

    /**
     * Update equipment
     */
    public function updateEquipment(Request $request, Equipment $equipment)
    {
        $data = $request->validate([
            'name' => 'required|max:255|unique:equipment,name,' . $equipment->id,
            'category' => 'required',
            'description' => 'nullable|string',
            'total_quantity' => 'required|integer|min:0',
            'condition' => 'required',
            'status' => 'nullable|in:available,unavailable,maintenance',
        ]);

        $borrowed = $equipment->total_quantity - $equipment->available_quantity;

        if ($data['total_quantity'] < $borrowed) {
            return response()->json([
                'message' => "Total cannot be below currently borrowed ($borrowed)",
            ], 422);
        }

        $data['available_quantity'] = $data['total_quantity'] - $borrowed;
        $equipment->update($data);

        return response()->json([
            'message' => 'Equipment updated successfully',
            'equipment' => $equipment->fresh(),
        ], 200);
    }

    /**
     * Delete equipment
     */
    public function deleteEquipment(Equipment $equipment)
    {
        // Check if equipment has active transactions
        if (Transaction::where('equipment_id', $equipment->id)
            ->where('status', 'borrowed')
            ->exists()) {
            return response()->json([
                'message' => 'Cannot delete equipment with active transactions',
            ], 422);
        }

        $equipment->delete();

        return response()->json([
            'message' => 'Equipment deleted successfully',
        ], 200);
    }

    /**
     * Get all users (admin only)
     */
    public function users()
    {
        $users = User::orderBy('role')->orderBy('name')->get();

        return response()->json([
            'users' => $users,
        ], 200);
    }

    /**
     * Update user (admin only)
     */
    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => 'required|in:student,organization,staff,admin',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh(),
        ], 200);
    }
}
