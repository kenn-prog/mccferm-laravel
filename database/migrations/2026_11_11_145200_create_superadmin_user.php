<?php
use Illuminate\Database\Migrations\Migration;
use App\Models\User;

return new class extends Migration {
    public function up(): void {
        // Create a sample superadmin user
        User::firstOrCreate(
            ['email' => 'superadmin@mcc.edu.ph'],
            [
                'name' => 'Super Administrator',
                'role' => 'superadmin',
                'is_active' => true,
                'phone' => '+639171234567',
                'password' => bcrypt('superadmin123'), // Default password for testing
            ]
        );
    }

    public function down(): void {
        User::where('email', 'superadmin@mcc.edu.ph')->delete();
    }
};
