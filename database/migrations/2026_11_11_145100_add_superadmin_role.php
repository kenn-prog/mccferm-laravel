<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void {
        // Add system_logs table for error tracking and system monitoring
        Schema::create('system_logs', function (Blueprint $t) {
            $t->id();
            $t->string('level')->index(); // info, warning, error, critical
            $t->string('category')->index(); // auth, database, request, system
            $t->text('message');
            $t->text('context')->nullable(); // JSON encoded context data
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->index('created_at');
        });

        // Add system_settings table for dynamic configuration
        Schema::create('system_settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value');
            $t->string('type')->default('string'); // string, boolean, integer, json
            $t->text('description')->nullable();
            $t->timestamps();
        });

        // Insert default system settings
        $defaults = [
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Enable maintenance mode to restrict access'],
            ['key' => 'max_borrow_days', 'value' => '7', 'type' => 'integer', 'description' => 'Maximum days equipment can be borrowed'],
            ['key' => 'auto_approve_reservations', 'value' => 'false', 'type' => 'boolean', 'description' => 'Automatically approve facility reservations'],
            ['key' => 'email_notifications', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable email notifications'],
            ['key' => 'session_timeout', 'value' => '120', 'type' => 'integer', 'description' => 'Session timeout in minutes'],
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'description' => 'Maximum login attempts before lockout'],
        ];
        
        foreach ($defaults as $setting) {
            DB::table('system_settings')->insert($setting + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void {
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('system_settings');
    }
};
