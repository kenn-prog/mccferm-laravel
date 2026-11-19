<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Reservation;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    /**
     * Get all facilities
     */
    public function index(Request $request)
    {
        $query = Facility::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Search by name or location
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $facilities = $query->orderBy('name')->get();

        return response()->json([
            'facilities' => $facilities,
        ], 200);
    }

    /**
     * Get single facility
     */
    public function show(Facility $facility)
    {
        return response()->json([
            'facility' => $facility,
        ], 200);
    }

    /**
     * Check facility availability
     */
    public function availability(Request $request, Facility $facility)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->date;

        // Get approved reservations for this facility on the given date
        $reservations = Reservation::where('facility_id', $facility->id)
            ->where('status', 'approved')
            ->whereDate('starts_at', $date)
            ->orderBy('starts_at')
            ->get()
            ->map(function ($reservation) {
                return [
                    'starts_at' => $reservation->starts_at,
                    'ends_at' => $reservation->ends_at,
                ];
            });

        return response()->json([
            'facility' => $facility,
            'date' => $date,
            'is_available' => $facility->status === 'available',
            'reservations' => $reservations,
        ], 200);
    }
}
