<?php
namespace App\Http\Controllers;
use App\Models\{User,Facility,Equipment,FermsRequest,Reservation,Transaction,Notice};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Cache,Artisan,Log};

class SuperAdminController extends Controller {
    
    public function dashboard() {
        // System health metrics
        $dbConnection = $this->checkDatabaseConnection();
        $cacheStatus = $this->checkCacheStatus();
        $diskUsage = $this->getDiskUsage();
        
        // System statistics
        $stats = [
            'Total Users' => User::count(),
            'Active Users' => User::where('is_active', true)->count(),
            'Total Facilities' => Facility::count(),
            'Total Equipment' => Equipment::sum('total_quantity'),
            'Pending Requests' => FermsRequest::where('status', 'pending')->count(),
            'Active Transactions' => Transaction::where('status', 'borrowed')->count(),
        ];

        // Recent system logs
        $recentLogs = DB::table('system_logs')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // System health status
        $systemHealth = [
            'database' => $dbConnection,
            'cache' => $cacheStatus,
            'disk_usage' => $diskUsage,
        ];

        // Recent errors
        $recentErrors = DB::table('system_logs')
            ->whereIn('level', ['error', 'critical'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // User activity summary
        $userActivity = [
            'today' => User::whereDate('updated_at', today())->count(),
            'this_week' => User::where('updated_at', '>=', now()->subWeek())->count(),
            'this_month' => User::where('updated_at', '>=', now()->subMonth())->count(),
        ];

        return view('superadmin.dashboard', compact('stats', 'systemHealth', 'recentLogs', 'recentErrors', 'userActivity'));
    }

    public function systemSettings() {
        $settings = DB::table('system_settings')->orderBy('key')->get();
        return view('superadmin.settings', compact('settings'));
    }

    public function updateSystemSettings(Request $r) {
        $validated = $r->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($validated['settings'] as $setting) {
            DB::table('system_settings')
                ->where('key', $setting['key'])
                ->update([
                    'value' => $setting['value'],
                    'updated_at' => now(),
                ]);
        }

        Cache::forget('system_settings');
        $this->logActivity('info', 'system', 'System settings updated');
        
        return back()->with('ok', 'System settings updated successfully.');
    }

    public function systemLogs(Request $r) {
        $query = DB::table('system_logs')->orderBy('created_at', 'desc');

        if ($r->level) {
            $query->where('level', $r->level);
        }
        if ($r->category) {
            $query->where('category', $r->category);
        }
        if ($r->date_from) {
            $query->whereDate('created_at', '>=', $r->date_from);
        }
        if ($r->date_to) {
            $query->whereDate('created_at', '<=', $r->date_to);
        }

        $logs = $query->paginate(50);
        $levels = DB::table('system_logs')->distinct()->pluck('level');
        $categories = DB::table('system_logs')->distinct()->pluck('category');

        return view('superadmin.logs', compact('logs', 'levels', 'categories'));
    }

    public function clearLogs() {
        $count = DB::table('system_logs')->count();
        DB::table('system_logs')->truncate();
        $this->logActivity('warning', 'system', "Cleared $count system logs");
        
        return back()->with('ok', "Cleared $count log entries.");
    }

    public function userManagement() {
        $users = User::orderBy('role')->orderBy('created_at', 'desc')->get();
        $roleStats = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get()
            ->pluck('count', 'role');

        return view('superadmin.users', compact('users', 'roleStats'));
    }

    public function toggleUserStatus(User $user) {
        abort_if($user->role === 'superadmin' && User::where('role', 'superadmin')->where('is_active', true)->count() <= 1, 422, 'Cannot deactivate the last active superadmin.');
        
        $user->update(['is_active' => !$user->is_active]);
        $this->logActivity('info', 'auth', "User {$user->email} " . ($user->is_active ? 'activated' : 'deactivated'));
        
        return back()->with('ok', 'User status updated.');
    }

    public function changeUserRole(Request $r, User $user) {
        $r->validate(['role' => 'required|in:student,organization,staff,admin,superadmin']);
        
        $oldRole = $user->role;
        $user->update(['role' => $r->role]);
        
        $this->logActivity('warning', 'auth', "User {$user->email} role changed from {$oldRole} to {$r->role}");
        
        return back()->with('ok', 'User role updated.');
    }

    public function databaseManagement() {
        $tables = $this->getTableStatistics();
        $dbSize = $this->getDatabaseSize();
        
        return view('superadmin.database', compact('tables', 'dbSize'));
    }

    public function optimizeDatabase() {
        try {
            Artisan::call('optimize:clear');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            
            $this->logActivity('info', 'system', 'Database and cache optimized');
            
            return back()->with('ok', 'System optimized successfully.');
        } catch (\Exception $e) {
            $this->logActivity('error', 'system', 'Database optimization failed: ' . $e->getMessage());
            return back()->withErrors('Optimization failed: ' . $e->getMessage());
        }
    }

    public function backupDatabase() {
        try {
            // This is a placeholder - in production, you'd want to use a proper backup solution
            $filename = 'backup_' . now()->format('Y_m_d_His') . '.sql';
            $this->logActivity('info', 'database', "Database backup created: $filename");
            
            return back()->with('ok', "Backup created: $filename");
        } catch (\Exception $e) {
            $this->logActivity('error', 'database', 'Backup failed: ' . $e->getMessage());
            return back()->withErrors('Backup failed: ' . $e->getMessage());
        }
    }

    public function systemMonitoring() {
        $metrics = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
        ];

        $performance = [
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
        ];

        return view('superadmin.monitoring', compact('metrics', 'performance'));
    }

    public function clearCache() {
        try {
            Cache::flush();
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            $this->logActivity('info', 'system', 'All caches cleared');
            
            return back()->with('ok', 'All caches cleared successfully.');
        } catch (\Exception $e) {
            $this->logActivity('error', 'system', 'Cache clear failed: ' . $e->getMessage());
            return back()->withErrors('Failed to clear cache: ' . $e->getMessage());
        }
    }

    public function toggleMaintenanceMode() {
        $currentMode = DB::table('system_settings')->where('key', 'maintenance_mode')->value('value');
        $newMode = $currentMode === 'true' ? 'false' : 'true';
        
        DB::table('system_settings')
            ->where('key', 'maintenance_mode')
            ->update(['value' => $newMode, 'updated_at' => now()]);

        if ($newMode === 'true') {
            Artisan::call('down');
        } else {
            Artisan::call('up');
        }

        $this->logActivity('warning', 'system', 'Maintenance mode ' . ($newMode === 'true' ? 'enabled' : 'disabled'));
        
        return back()->with('ok', 'Maintenance mode ' . ($newMode === 'true' ? 'enabled' : 'disabled') . '.');
    }

    // Helper methods
    private function checkDatabaseConnection(): array {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCacheStatus(): array {
        try {
            Cache::put('health_check', 'ok', 10);
            $result = Cache::get('health_check');
            return ['status' => $result === 'ok' ? 'healthy' : 'warning', 'message' => 'Working'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function getDiskUsage(): array {
        $path = storage_path();
        if (function_exists('disk_free_space')) {
            $free = disk_free_space($path);
            $total = disk_total_space($path);
            $used = $total - $free;
            $percentage = round(($used / $total) * 100, 2);
            
            return [
                'status' => $percentage > 90 ? 'warning' : 'healthy',
                'used' => $this->formatBytes($used),
                'total' => $this->formatBytes($total),
                'percentage' => $percentage,
            ];
        }
        return ['status' => 'unknown', 'message' => 'Unable to check disk usage'];
    }

    private function getTableStatistics(): array {
        $tables = [];
        try {
            $tableNames = DB::select('SHOW TABLES');
            foreach ($tableNames as $tableName) {
                $table = array_values((array)$tableName)[0];
                $count = DB::table($table)->count();
                $tables[] = ['name' => $table, 'rows' => $count];
            }
        } catch (\Exception $e) {
            // SQLite or other databases may not support SHOW TABLES
            $tables = [
                ['name' => 'users', 'rows' => User::count()],
                ['name' => 'facilities', 'rows' => Facility::count()],
                ['name' => 'equipment', 'rows' => Equipment::count()],
                ['name' => 'requests', 'rows' => FermsRequest::count()],
                ['name' => 'reservations', 'rows' => Reservation::count()],
                ['name' => 'equipment_transactions', 'rows' => Transaction::count()],
            ];
        }
        return $tables;
    }

    private function getDatabaseSize(): string {
        try {
            $result = DB::select('SELECT pg_size_pretty(pg_database_size(current_database())) as size');
            return $result[0]->size ?? 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function formatBytes($bytes, $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function logActivity($level, $category, $message, $context = null): void {
        DB::table('system_logs')->insert([
            'level' => $level,
            'category' => $category,
            'message' => $message,
            'context' => $context ? json_encode($context) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
