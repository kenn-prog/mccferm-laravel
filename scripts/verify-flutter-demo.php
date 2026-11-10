<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$db = DB::connection();
$schema = $db->getSchemaBuilder();

if ($db->getDriverName() !== 'pgsql') {
    throw new RuntimeException('This verification requires the configured PostgreSQL connection.');
}

$tables = [
    'users',
    'facilities',
    'facility_schedules',
    'requests',
    'reservations',
    'equipment',
    'equipment_request_items',
    'equipment_transactions',
    'notifications',
];

foreach ($tables as $table) {
    if (! $schema->hasTable($table)) {
        throw new RuntimeException("Required FERMS table public.{$table} is missing. Run the migrations first.");
    }
}

$assert = function (bool $condition, string $message): void {
    if (! $condition) {
        throw new RuntimeException("FAIL: {$message}");
    }

    echo "PASS: {$message}\n";
};

$role = $db->selectOne('SELECT current_user AS name')->name;
echo "Checking FERMS database access as {$role}.\n";

foreach ($tables as $table) {
    $privileges = $db->selectOne(
        <<<'SQL'
            SELECT
                has_table_privilege(current_user, ?, 'SELECT')::text AS can_select,
                has_table_privilege(current_user, ?, 'INSERT')::text AS can_insert,
                has_table_privilege(current_user, ?, 'UPDATE')::text AS can_update,
                has_table_privilege(current_user, ?, 'DELETE')::text AS can_delete
        SQL,
        ["public.{$table}", "public.{$table}", "public.{$table}", "public.{$table}"]
    );

    $assert(
        $privileges->can_select === 'true'
            && $privileges->can_insert === 'true'
            && $privileges->can_update === 'true'
            && $privileges->can_delete === 'true',
        "Configured role has SELECT, INSERT, UPDATE, and DELETE access to public.{$table}"
    );
}

$db->beginTransaction();

try {
    $token = bin2hex(random_bytes(8));
    $oldTimestamp = '2000-01-01 00:00:00';
    $now = now();

    $userId = $db->table('users')->insertGetId([
        'name' => "Flutter verification {$token}",
        'email' => "flutter-verification-{$token}@example.test",
        'role' => 'student',
        'is_active' => true,
        'created_at' => $oldTimestamp,
        'updated_at' => $oldTimestamp,
    ]);

    $facilityId = $db->table('facilities')->insertGetId([
        'name' => "Flutter verification {$token}",
        'location' => 'Verification only',
        'category' => 'Rooms',
        'capacity' => 1,
        'description' => 'Temporary database verification record.',
        'status' => 'available',
        'created_at' => $oldTimestamp,
        'updated_at' => $oldTimestamp,
    ]);

    $equipmentId = $db->table('equipment')->insertGetId([
        'name' => "Flutter verification {$token}",
        'category' => 'Verification only',
        'description' => 'Temporary database verification record.',
        'total_quantity' => 1,
        'available_quantity' => 1,
        'condition' => 'Good',
        'status' => 'available',
        'created_at' => $oldTimestamp,
        'updated_at' => $oldTimestamp,
    ]);

    $requestId = $db->table('requests')->insertGetId([
        'user_id' => $userId,
        'type' => 'facility',
        'purpose' => 'Temporary database verification.',
        'participants' => 1,
        'status' => 'pending',
        'created_at' => $oldTimestamp,
        'updated_at' => $oldTimestamp,
    ]);

    $reservationId = $db->table('reservations')->insertGetId([
        'request_id' => $requestId,
        'facility_id' => $facilityId,
        'starts_at' => $now->copy()->addDay(),
        'ends_at' => $now->copy()->addDay()->addHour(),
        'status' => 'pending',
        'created_at' => $oldTimestamp,
        'updated_at' => $oldTimestamp,
    ]);

    $scheduleId = $db->table('facility_schedules')->insertGetId([
        'facility_id' => $facilityId,
        'title' => 'Temporary verification schedule',
        'date' => $now->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'blocked',
        'created_at' => $oldTimestamp,
        'updated_at' => $oldTimestamp,
    ]);

    $requestItemId = $db->table('equipment_request_items')->insertGetId([
        'request_id' => $requestId,
        'equipment_id' => $equipmentId,
        'quantity' => 1,
        'borrow_date' => $now->toDateString(),
        'expected_return' => $now->copy()->addDay()->toDateString(),
        'created_at' => $oldTimestamp,
        'updated_at' => $oldTimestamp,
    ]);

    $transactionId = $db->table('equipment_transactions')->insertGetId([
        'request_id' => $requestId,
        'equipment_id' => $equipmentId,
        'user_id' => $userId,
        'quantity' => 1,
        'borrow_date' => $now->toDateString(),
        'expected_return' => $now->copy()->addDay()->toDateString(),
        'status' => 'borrowed',
        'created_at' => $oldTimestamp,
        'updated_at' => $oldTimestamp,
    ]);

    $notificationId = $db->table('notifications')->insertGetId([
        'user_id' => $userId,
        'title' => 'Temporary verification',
        'message' => 'This record is rolled back when verification finishes.',
        'type' => 'info',
        'is_read' => false,
        'created_at' => $oldTimestamp,
        'updated_at' => $oldTimestamp,
    ]);

    $records = [
        'users' => $userId,
        'facilities' => $facilityId,
        'facility_schedules' => $scheduleId,
        'requests' => $requestId,
        'reservations' => $reservationId,
        'equipment' => $equipmentId,
        'equipment_request_items' => $requestItemId,
        'equipment_transactions' => $transactionId,
        'notifications' => $notificationId,
    ];

    foreach ($records as $table => $id) {
        $record = $db->table($table)->where('id', $id)->first();
        $assert(
            $record !== null && $record->created_at !== null && $record->updated_at !== null,
            "Can insert and read a timestamped {$table} record"
        );
    }

    $db->table('facilities')->where('id', $facilityId)->update([
        'description' => "Updated verification record {$token}",
    ]);
    $updatedFacility = $db->table('facilities')->where('id', $facilityId)->first();
    $assert(
        $updatedFacility->description === "Updated verification record {$token}"
            && $updatedFacility->created_at === $oldTimestamp
            && $updatedFacility->updated_at !== $oldTimestamp,
        'Can update a facility while preserving created_at and refreshing updated_at'
    );

    foreach ([
        'notifications' => $notificationId,
        'equipment_transactions' => $transactionId,
        'equipment_request_items' => $requestItemId,
        'reservations' => $reservationId,
        'facility_schedules' => $scheduleId,
        'requests' => $requestId,
        'equipment' => $equipmentId,
        'facilities' => $facilityId,
        'users' => $userId,
    ] as $table => $id) {
        $deleted = $db->table($table)->where('id', $id)->delete();
        $assert($deleted === 1, "Can delete the temporary {$table} record");
    }
} finally {
    if ($db->transactionLevel() > 0) {
        $db->rollBack();
    }
}

echo "All checks passed. Temporary FERMS records were rolled back; existing records were untouched.\n";
