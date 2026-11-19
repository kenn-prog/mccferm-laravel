<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Facility, FermsRequest, Reservation};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Closure;

class ReservationController extends Controller
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
     * Get user's reservations
     */
    public function index(Request $request)
    {
        $query = FermsRequest::with(['reservation.facility', 'user'])
            ->where('user_id', $request->user()->id)
            ->where('type', 'facility');

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
     * Get single reservation
     */
    public function show(Request $request, FermsRequest $reservation)
    {
        // Ensure user owns this request
        if ($reservation->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $reservation->load(['reservation.facility', 'user']);

        return response()->json([
            'request' => $reservation,
        ], 200);
    }

    /**
     * Create new reservation
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'date' => ['bail', 'required', 'date', 'after_or_equal:today', $this->schoolOpenDateRule()],
            'start_time' => ['required', 'date_format:H:i', $this->businessHoursRule()],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time', $this->businessHoursRule()],
            'purpose' => 'required|string|max:255',
            'participants' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $facility = Facility::findOrFail($data['facility_id']);

        if ($facility->status !== 'available') {
            return response()->json([
                'message' => 'That facility is not available for reservation.',
                'errors' => ['facility_id' => ['Facility is not available']],
            ], 422);
        }

        $startsAt = Carbon::parse("{$data['date']} {$data['start_time']}");
        $endsAt = Carbon::parse("{$data['date']} {$data['end_time']}");

        // Check for conflicts
        if (Reservation::conflicting($facility->id, $startsAt, $endsAt)->exists()) {
            return response()->json([
                'message' => 'That time slot conflicts with an approved reservation.',
                'errors' => ['start_time' => ['Time slot conflicts with existing reservation']],
            ], 422);
        }

        $fermsRequest = null;
        DB::transaction(function () use ($request, $data, $startsAt, $endsAt, &$fermsRequest) {
            $fermsRequest = FermsRequest::create([
                'user_id' => $request->user()->id,
                'type' => 'facility',
                'purpose' => $data['purpose'],
                'participants' => $data['participants'] ?? null,
                'organization_class' => $request->user()->organization_class,
                'notes' => $data['notes'] ?? null,
            ]);

            Reservation::create([
                'request_id' => $fermsRequest->id,
                'facility_id' => $data['facility_id'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        });

        $fermsRequest->load(['reservation.facility']);

        return response()->json([
            'message' => 'Reservation request submitted successfully',
            'request' => $fermsRequest,
        ], 201);
    }

    /**
     * Update pending reservation
     */
    public function update(Request $request, FermsRequest $reservation)
    {
        // Ensure user owns this request
        if ($reservation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($reservation->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be updated',
            ], 422);
        }

        $data = $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'date' => ['bail', 'required', 'date', 'after_or_equal:today', $this->schoolOpenDateRule()],
            'start_time' => ['required', 'date_format:H:i', $this->businessHoursRule()],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time', $this->businessHoursRule()],
            'purpose' => 'required|string|max:255',
            'participants' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $facility = Facility::findOrFail($data['facility_id']);

        if ($facility->status !== 'available' && $facility->id !== $reservation->reservation->facility_id) {
            return response()->json([
                'message' => 'That facility is not available',
                'errors' => ['facility_id' => ['Facility is not available']],
            ], 422);
        }

        $startsAt = Carbon::parse("{$data['date']} {$data['start_time']}");
        $endsAt = Carbon::parse("{$data['date']} {$data['end_time']}");

        // Check for conflicts (excluding current reservation)
        if (Reservation::conflicting($facility->id, $startsAt, $endsAt)
            ->where('id', '!=', $reservation->reservation->id)
            ->exists()) {
            return response()->json([
                'message' => 'That time slot conflicts with an approved reservation',
                'errors' => ['start_time' => ['Time slot conflicts']],
            ], 422);
        }

        DB::transaction(function () use ($reservation, $data, $facility, $startsAt, $endsAt) {
            $reservation->update([
                'purpose' => $data['purpose'],
                'participants' => $data['participants'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $reservation->reservation->update([
                'facility_id' => $facility->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        });

        $reservation->load(['reservation.facility']);

        return response()->json([
            'message' => 'Request updated successfully',
            'request' => $reservation,
        ], 200);
    }

    /**
     * Cancel reservation
     */
    public function destroy(Request $request, FermsRequest $reservation)
    {
        // Ensure user owns this request
        if ($reservation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($reservation->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be cancelled',
            ], 422);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'cancelled']);
            $reservation->reservation?->update(['status' => 'cancelled']);
        });

        return response()->json([
            'message' => 'Request cancelled successfully',
        ], 200);
    }
}
