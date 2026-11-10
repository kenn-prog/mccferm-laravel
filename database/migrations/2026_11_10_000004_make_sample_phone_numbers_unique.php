<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SAMPLE_PHONE = '0900 000 0000';

    public function up(): void
    {
        $users = DB::table('users')
            ->where('phone', self::SAMPLE_PHONE)
            ->orderBy('id')
            ->get(['id']);

        $sequence = 1;
        foreach ($users as $user) {
            do {
                $digits = '09'.str_pad((string) $sequence++, 9, '0', STR_PAD_LEFT);
                $phone = substr($digits, 0, 4).' '.substr($digits, 4, 3).' '.substr($digits, 7, 4);
            } while (DB::table('users')->where('phone', $phone)->exists());

            DB::table('users')->where('id', $user->id)->update(['phone' => $phone]);
        }
    }

    public function down(): void
    {
    }
};
