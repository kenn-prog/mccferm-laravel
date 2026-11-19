<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Get user's transactions
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['equipment', 'user'])
            ->where('user_id', $request->user()->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Only borrowed
        if ($request->boolean('borrowed_only')) {
            $query->where('status', 'borrowed');
        }

        $transactions = $query->latest()->get();

        return response()->json([
            'transactions' => $transactions,
        ], 200);
    }

    /**
     * Get single transaction
     */
    public function show(Request $request, Transaction $transaction)
    {
        // Ensure user owns this transaction
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->load(['equipment', 'user']);

        return response()->json([
            'transaction' => $transaction,
        ], 200);
    }
}
