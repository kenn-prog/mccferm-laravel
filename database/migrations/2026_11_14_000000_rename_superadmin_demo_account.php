<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('users')
            ->where('email', 'superadmin@mcc.edu.ph')
            ->where('name', 'Super Administrator')
            ->update(['name' => 'IT Administrator']);
    }

    public function down(): void {
        DB::table('users')
            ->where('email', 'superadmin@mcc.edu.ph')
            ->where('name', 'IT Administrator')
            ->update(['name' => 'Super Administrator']);
    }
};
