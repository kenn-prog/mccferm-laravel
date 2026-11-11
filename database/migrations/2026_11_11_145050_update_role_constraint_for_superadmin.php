<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // Drop the existing constraint if it exists (PostgreSQL specific)
        $driver = DB::getDriverName();
        
        if ($driver === 'pgsql') {
            // Drop the old constraint
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check_flutter_demo');
            
            // Add new constraint that includes superadmin
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check_flutter_demo CHECK (role IN ('student', 'organization', 'staff', 'admin', 'superadmin'))");
        }
    }

    public function down(): void {
        $driver = DB::getDriverName();
        
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check_flutter_demo');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check_flutter_demo CHECK (role IN ('student', 'organization', 'staff', 'admin'))");
        }
    }
};
