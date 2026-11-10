<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SAMPLE_PHONE = '0000000000';

    public function up(): void
    {
        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('phone')->orWhereRaw("TRIM(phone) = ''");
            })
            ->update(['phone' => self::SAMPLE_PHONE]);
    }

    public function down(): void
    {
    }
};
