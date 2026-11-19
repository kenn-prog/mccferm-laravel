<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Equipment, EquipmentItem, FermsRequest};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Closure;

class RequestController extends Controller
{
    private function businessHoursRule(): Closure
    {
        return function ($attribute, $value, $fail) {
            if ($value < '08:00' || $value > '20:00') {
                $fail('The time must be between 8:00 AM and 8:00 PM.');
            }
        };
    }

    private function schoolOpenDateRule(): Closure
    {
        return function ($attribute, $value, $fail) {
            if (Carbon::parse($value)->isSunday()) {
                $fail('The school is closed on Sundays. Please choose another date.');
            }
        };
    }

    /**
     * Get user's requests
     */
    public function index(Request $request)
    {
        $query = FermsRequest::with(['user', 'reservation.facility', 'item.equipment'])
            ->where('user_id', $request->user()->id);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->get();

        return response()->json([
            'requests' => $requests,
        ], 200);
    }

    /**
     * Get single request
     */
    public function show(Request $request, FermsRequest $fermsRequest)
    {
        // Ensure user owns this request
        if ($fermsRequest->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fermsRequest->load(['user', 'reservation.facility', 'item.equipment']);

        return response()->json([
            'request' => $fermsRequest,
        ], 200);
    }

    /**
     * Create equipment borrow request
     */
    public function borrow(Request $request)
    {
        $data = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'quantity' => 'required|integer|min:1',
            'borrow_date' => ['bail', 'required', 'date', 'after_or_equal:today', $this->schoolOpenDateRule()],
            'borrow_time' => ['required', 'date_format:H:i', $this->businessHoursRule()],
            'expected_return' => ['bail', 'required', 'date', 'after_or_equal:borrow_date', $this->schoolOpenDateRule()],
            'expected_return_time' => ['required', 'date_format:H:i', $this->businessHoursRule()],
            'purpose' => 'required|string|max:255',
        ]);

        // Validate same-day return time
        if ($data['expected_return'] === $data['borrow_date'] && $data['expected_return_time'] <= $data['borrow_time']) {
            return response()->json([
                'message' => 'Return time must be later than pickup time when returning on the same day',
                'errors' => ['expected_return_time' => ['Must be later than borrow time']],
            ], 422);
        }

        // Validate max borrow days
        $latestReturnDate = Carbon::parse($data['borrow_date'])->addDays(5);
        if (Carbon::parse($data['expected_return'])->greaterThan($latestReturnDate)) {
            return response()->json([
                'message' => 'Expected return date must be no more than 5 days after the borrow date',
                'errors' => ['expected_return' => ['Maximum 5 days borrow period']],
            ], 422);
        }

        $equipment = Equipment::findOrFail($data['equipment_id']);

        if ($equipment->status !== 'available' || $data['quantity'] > $equipment->available_quantity) {
            return response()->json([
                'message' => 'Requested quantity is not available',
                'errors' => ['quantity' => ['Not enough equipment available']],
            ], 422);
        }

        $fermsRequest = null;
        DB::transaction(function () use ($request, $data, &$fermsRequest) {
            $fermsRequest = FermsRequest::create([
                'user_id' => $request->user()->id,
                'type' => 'equipment',
                'purpose' => $data['purpose'],
            ]);

            EquipmentItem::create([
                'request_id' => $fermsRequest->id,
                'equipment_id' => $data['equipment_id'],
                'quantity' => $data['quantity'],
                'borrow_date' => $data['borrow_date'],
                'borrow_time' => $data['borrow_time'],
                'expected_return' => $data['expected_return'],
                'expected_return_time' => $data['expected_return_time'],
            ]);
        });

        $fermsRequest->load(['item.equipment']);

        return response()->json([
            'message' => 'Borrowing request submitted successfully',
            'request' => $fermsRequest,
        ], 201);
    }

    /**
     * Update pending request
     */
    public function update(Request $request, FermsRequest $fermsRequest)
    {
        // Ensure user owns this request
        if ($fermsRequest->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($fermsRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be updated',
            ], 422);
        }

        if ($fermsRequest->type === 'equipment') {
            $data = $request->validate([
                'equipment_id' => 'required|exists:equipment,id',
                'quantity' => 'required|integer|min:1',
                'borrow_date' => ['bail', 'required', 'date', 'after_or_equal:today', $this->schoolOpenDateRule()],
                'borrow_time' => ['required', 'date_format:H:i', $this->businessHoursRule()],
                'expected_return' => ['bail', 'required', 'date', 'after_or_equal:borrow_date', $this->schoolOpenDateRule()],
                'expected_return_time' => ['required', 'date_format:H:i', $this->businessHoursRule()],
                'purpose' => 'required|string|max:255',
            ]);

            if ($data['expected_return'] === $data['borrow_date'] && $data['expected_return_time'] <= $data['borrow_time']) {
                return response()->json([
                    'message' => 'Return time must be later than pickup time',
                    'errors' => ['expected_return_time' => ['Must be later than borrow time']],
                ], 422);
            }

            if (Carbon::parse($data['expected_return'])->greaterThan(Carbon::parse($data['borrow_date'])->addDays(5))) {
                return response()->json([
                    'message' => 'Maximum 5 days borrow period',
                    'errors' => ['expected_return' => ['Maximum 5 days']],
                ], 422);
            }

            $equipment = Equipment::findOrFail($data['equipment_id']);
            if ($equipment->status !== 'available' || $data['quantity'] > $equipment->available_quantity) {
                return response()->json([
                    'message' => 'Requested quantity is not available',
                    'errors' => ['quantity' => ['Not available']],
                ], 422);
            }

            DB::transaction(function () use ($fermsRequest, $data) {
                $fermsRequest->update(['purpose' => $data['purpose']]);
                $fermsRequest->item->update([
                    'equipment_id' => $data['equipment_id'],
                    'quantity' => $data['quantity'],
                    'borrow_date' => $data['borrow_date'],
                    'borrow_time' => $data['borrow_time'],
                    'expected_return' => $data['expected_return'],
                    'expected_return_time' => $data['expected_return_time'],
                ]);
            });
        }

        $fermsRequest->load(['item.equipment']);

        return response()->json([
            'message' => 'Request updated successfully',
            'request' => $fermsRequest,
        ], 200);
    }

    /**
     * Cancel request
     */
    public function destroy(Request $request, FermsRequest $fermsRequest)
    {
        // Ensure user owns this request
        if ($fermsRequest->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($fermsRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be cancelled',
            ], 422);
        }

        $fermsRequest->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Request cancelled successfully',
        ], 200);
    }
}
