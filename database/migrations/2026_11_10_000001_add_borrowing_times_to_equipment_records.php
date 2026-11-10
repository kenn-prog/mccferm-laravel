<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_request_items', function (Blueprint $table) {
            $table->time('borrow_time')->nullable();
            $table->time('expected_return_time')->nullable();
        });

        Schema::table('equipment_transactions', function (Blueprint $table) {
            $table->time('borrow_time')->nullable();
            $table->time('expected_return_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_request_items', function (Blueprint $table) {
            $table->dropColumn(['borrow_time', 'expected_return_time']);
        });

        Schema::table('equipment_transactions', function (Blueprint $table) {
            $table->dropColumn(['borrow_time', 'expected_return_time']);
        });
    }
};
