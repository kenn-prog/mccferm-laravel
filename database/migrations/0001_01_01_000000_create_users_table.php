<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
  public function up(): void {
    $pg = DB::getDriverName() === 'pgsql'; // PostgreSQL-only features are skipped on sqlite (used by the test suite)
    if ($pg) DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
    Schema::create('users', function (Blueprint $t) {
      $t->id(); $t->string('name'); $t->string('email')->unique();
      $t->string('role')->default('student')->index(); // student|organization|staff|admin|superadmin
      $t->string('student_employee_id')->nullable()->unique(); $t->string('organization_class')->nullable();
      $t->boolean('is_active')->default(true); $t->string('password')->nullable(); $t->rememberToken(); $t->timestamps();
    });
    Schema::create('sessions', function (Blueprint $t) {
      $t->string('id')->primary(); $t->foreignId('user_id')->nullable()->index(); $t->string('ip_address',45)->nullable();
      $t->text('user_agent')->nullable(); $t->longText('payload'); $t->integer('last_activity')->index();
    });
    Schema::create('password_reset_tokens', fn(Blueprint $t)=>[$t->string('email')->primary(),$t->string('token'),$t->timestamp('created_at')->nullable()]);
    Schema::create('facilities', function (Blueprint $t) {
      $t->id(); $t->string('name')->unique(); $t->string('location'); $t->string('category')->default('Rooms');
      $t->unsignedInteger('capacity'); $t->text('description')->nullable(); $t->string('status')->default('available')->index(); $t->timestamps();
    });
    Schema::create('facility_schedules', function (Blueprint $t) { // staff-defined blocks (closures, classes)
      $t->id(); $t->foreignId('facility_id')->constrained()->cascadeOnDelete(); $t->string('title')->nullable();
      $t->date('date'); $t->time('start_time'); $t->time('end_time'); $t->string('status')->default('blocked'); $t->timestamps(); $t->index(['facility_id','date']);
    });
    Schema::create('requests', function (Blueprint $t) {
      $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->string('type')->index(); // facility|equipment
      $t->string('purpose'); $t->unsignedInteger('participants')->nullable(); $t->string('organization_class')->nullable();
      $t->text('notes')->nullable(); $t->string('status')->default('pending')->index(); $t->timestamps();
    });
    Schema::create('reservations', function (Blueprint $t) {
      $t->id(); $t->foreignId('request_id')->constrained()->cascadeOnDelete(); $t->foreignId('facility_id')->constrained();
      $t->timestamp('starts_at'); $t->timestamp('ends_at'); $t->string('status')->default('pending')->index(); $t->timestamps();
    });
    if ($pg) DB::statement('ALTER TABLE reservations ADD CONSTRAINT chk_time CHECK (ends_at > starts_at)');
    if ($pg) DB::statement("ALTER TABLE reservations ADD CONSTRAINT no_double_booking EXCLUDE USING gist (facility_id WITH =, tsrange(starts_at, ends_at) WITH &&) WHERE (status = 'approved')");
    Schema::create('equipment', function (Blueprint $t) {
      $t->id(); $t->string('name')->unique(); $t->string('category'); $t->text('description')->nullable();
      $t->unsignedInteger('total_quantity'); $t->unsignedInteger('available_quantity'); $t->string('condition')->default('Good'); $t->string('status')->default('available'); $t->timestamps();
    });
    if ($pg) DB::statement('ALTER TABLE equipment ADD CONSTRAINT chk_qty CHECK (available_quantity >= 0 AND available_quantity <= total_quantity)');
    Schema::create('equipment_request_items', function (Blueprint $t) {
      $t->id(); $t->foreignId('request_id')->constrained()->cascadeOnDelete(); $t->foreignId('equipment_id')->constrained(); $t->unsignedInteger('quantity');
      $t->date('borrow_date'); $t->date('expected_return'); $t->timestamps();
    });
    Schema::create('equipment_transactions', function (Blueprint $t) {
      $t->id(); $t->foreignId('request_id')->nullable()->constrained(); $t->foreignId('equipment_id')->constrained(); $t->foreignId('user_id')->constrained();
      $t->unsignedInteger('quantity'); $t->date('borrow_date'); $t->date('expected_return'); $t->timestamp('actual_return')->nullable();
      $t->string('status')->default('borrowed')->index(); $t->text('remarks')->nullable(); $t->timestamps();
    });
    Schema::create('notifications', function (Blueprint $t) {
      $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->string('title'); $t->text('message'); $t->string('type')->default('info');
      $t->boolean('is_read')->default(false); $t->timestamps(); $t->index(['user_id','is_read']);
    });
  }
  public function down(): void { foreach (['notifications','equipment_transactions','equipment_request_items','equipment','reservations','requests','facility_schedules','facilities','password_reset_tokens','sessions','users'] as $x) Schema::dropIfExists($x); }
};
