<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    /**
     * Get all equipment
     */
    public function index(Request $request)
    {
        $query = Equipment::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Only available
        if ($request->boolean('available_only')) {
            $query->where('status', 'available')
                  ->where('available_quantity', '>', 0);
        }

        $equipment = $query->orderBy('category')->orderBy('name')->get();

        return response()->json([
            'equipment' => $equipment,
        ], 200);
    }

    /**
     * Get single equipment
     */
    public function show(Equipment $equipment)
    {
        return response()->json([
            'equipment' => $equipment,
        ], 200);
    }
}
