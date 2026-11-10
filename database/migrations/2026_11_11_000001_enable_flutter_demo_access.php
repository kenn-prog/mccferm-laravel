<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES_WITH_TIMESTAMPS = [
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

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('users', function ($table) {
            $table->uuid('supabase_user_id')->nullable()->unique();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE public.users
                ADD CONSTRAINT users_role_check_flutter_demo
                CHECK (role IN ('student', 'organization', 'staff', 'admin')) NOT VALID
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE public.facilities
                ADD CONSTRAINT facilities_status_check_flutter_demo
                CHECK (status IN ('available', 'reserved', 'unavailable')) NOT VALID
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE public.equipment
                ADD CONSTRAINT equipment_status_check_flutter_demo
                CHECK (status IN ('available', 'unavailable', 'maintenance')) NOT VALID
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE public.requests
                ADD CONSTRAINT requests_type_check_flutter_demo
                CHECK (type IN ('facility', 'equipment')) NOT VALID
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE public.requests
                ADD CONSTRAINT requests_status_check_flutter_demo
                CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled', 'released', 'returned')) NOT VALID
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE public.reservations
                ADD CONSTRAINT reservations_status_check_flutter_demo
                CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled')) NOT VALID
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE public.equipment_transactions
                ADD CONSTRAINT equipment_transactions_status_check_flutter_demo
                CHECK (status IN ('borrowed', 'returned')) NOT VALID
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.ferms_set_updated_at()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $function$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $function$
        SQL);

        foreach (self::TABLES_WITH_TIMESTAMPS as $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$table}_set_updated_at ON public.{$table}");
            DB::statement(
                "CREATE TRIGGER {$table}_set_updated_at BEFORE UPDATE ON public.{$table} ".
                'FOR EACH ROW EXECUTE FUNCTION public.ferms_set_updated_at()'
            );
        }

        DB::statement(<<<'SQL'
            CREATE INDEX facilities_flutter_search_idx
                ON public.facilities USING GIN (
                    to_tsvector(
                        'simple'::regconfig,
                        coalesce(name, '') || ' ' || coalesce(location, '') || ' ' ||
                        coalesce(category, '') || ' ' || coalesce(description, '')
                    )
                )
        SQL);
        DB::statement(<<<'SQL'
            CREATE INDEX equipment_flutter_search_idx
                ON public.equipment USING GIN (
                    to_tsvector(
                        'simple'::regconfig,
                        coalesce(name, '') || ' ' || coalesce(category, '') || ' ' ||
                        coalesce(description, '')
                    )
                )
        SQL);

        $supabaseRolesExist = DB::selectOne(<<<'SQL'
            SELECT
                (EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'anon'))::text AS has_anon,
                (EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'authenticated'))::text AS has_authenticated,
                (to_regprocedure('auth.uid()') IS NOT NULL)::text AS has_auth_uid
        SQL);

        if ($supabaseRolesExist->has_anon !== 'true' || $supabaseRolesExist->has_authenticated !== 'true' || $supabaseRolesExist->has_auth_uid !== 'true') {
            return;
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

        DB::statement('REVOKE ALL ON '.implode(', ', array_map(fn ($table) => "public.{$table}", $tables)).' FROM anon, authenticated');
        DB::statement('GRANT USAGE ON SCHEMA public TO authenticated');
        DB::statement('GRANT SELECT ON public.facilities, public.facility_schedules, public.equipment TO authenticated');
        DB::statement(<<<'SQL'
            GRANT SELECT (
                id, name, email, role, student_employee_id, organization_class,
                is_active, phone, supabase_user_id, created_at, updated_at
            ) ON public.users TO authenticated
        SQL);
        DB::statement('GRANT SELECT ON public.requests, public.reservations, public.equipment_request_items, public.equipment_transactions, public.notifications TO authenticated');

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE public.{$table} ENABLE ROW LEVEL SECURITY");
        }

        foreach (['facilities', 'facility_schedules', 'equipment'] as $table) {
            DB::statement("CREATE POLICY flutter_demo_authenticated_read ON public.{$table} FOR SELECT TO authenticated USING (true)");
        }

        DB::statement(<<<'SQL'
            CREATE POLICY flutter_demo_authenticated_read ON public.users
                FOR SELECT TO authenticated
                USING (supabase_user_id = (SELECT auth.uid()))
        SQL);
        DB::statement(<<<'SQL'
            CREATE POLICY flutter_demo_authenticated_read ON public.requests
                FOR SELECT TO authenticated
                USING (
                    user_id IN (
                        SELECT id FROM public.users
                        WHERE supabase_user_id = (SELECT auth.uid())
                    )
                )
        SQL);
        DB::statement(<<<'SQL'
            CREATE POLICY flutter_demo_authenticated_read ON public.reservations
                FOR SELECT TO authenticated
                USING (
                    request_id IN (
                        SELECT id FROM public.requests
                        WHERE user_id IN (
                            SELECT id FROM public.users
                            WHERE supabase_user_id = (SELECT auth.uid())
                        )
                    )
                )
        SQL);
        DB::statement(<<<'SQL'
            CREATE POLICY flutter_demo_authenticated_read ON public.equipment_request_items
                FOR SELECT TO authenticated
                USING (
                    request_id IN (
                        SELECT id FROM public.requests
                        WHERE user_id IN (
                            SELECT id FROM public.users
                            WHERE supabase_user_id = (SELECT auth.uid())
                        )
                    )
                )
        SQL);
        DB::statement(<<<'SQL'
            CREATE POLICY flutter_demo_authenticated_read ON public.equipment_transactions
                FOR SELECT TO authenticated
                USING (
                    user_id IN (
                        SELECT id FROM public.users
                        WHERE supabase_user_id = (SELECT auth.uid())
                    )
                )
        SQL);
        DB::statement(<<<'SQL'
            CREATE POLICY flutter_demo_authenticated_read ON public.notifications
                FOR SELECT TO authenticated
                USING (
                    user_id IN (
                        SELECT id FROM public.users
                        WHERE supabase_user_id = (SELECT auth.uid())
                    )
                )
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'users',
            'facilities',
            'facility_schedules',
            'requests',
            'reservations',
            'equipment',
            'equipment_request_items',
            'equipment_transactions',
            'notifications',
        ] as $table) {
            DB::statement("DROP POLICY IF EXISTS flutter_demo_authenticated_read ON public.{$table}");
            DB::statement("ALTER TABLE public.{$table} DISABLE ROW LEVEL SECURITY");
        }

        foreach (self::TABLES_WITH_TIMESTAMPS as $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$table}_set_updated_at ON public.{$table}");
        }

        DB::statement('DROP FUNCTION IF EXISTS public.ferms_set_updated_at()');
        DB::statement('DROP INDEX IF EXISTS public.facilities_flutter_search_idx');
        DB::statement('DROP INDEX IF EXISTS public.equipment_flutter_search_idx');

        foreach ([
            'users_role_check_flutter_demo' => 'users',
            'facilities_status_check_flutter_demo' => 'facilities',
            'equipment_status_check_flutter_demo' => 'equipment',
            'requests_type_check_flutter_demo' => 'requests',
            'requests_status_check_flutter_demo' => 'requests',
            'reservations_status_check_flutter_demo' => 'reservations',
            'equipment_transactions_status_check_flutter_demo' => 'equipment_transactions',
        ] as $constraint => $table) {
            DB::statement("ALTER TABLE public.{$table} DROP CONSTRAINT IF EXISTS {$constraint}");
        }

        Schema::table('users', function ($table) {
            $table->dropUnique(['supabase_user_id']);
            $table->dropColumn('supabase_user_id');
        });
    }
};
