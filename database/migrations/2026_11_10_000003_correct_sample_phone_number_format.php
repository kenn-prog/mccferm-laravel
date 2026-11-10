<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('phone', '0000000000')
            ->update(['phone' => '0900 000 0000']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('phone', '0900 000 0000')
            ->update(['phone' => '0000000000']);
    }
};
