<?php
// Checks the Supabase PostgreSQL connection using the values in .env. Usage: php scripts/verify-connection.php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $version = Illuminate\Support\Facades\DB::selectOne('select version() as v')->v;
    echo "Connected: $version\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Connection failed: '.$e->getMessage()."\n");
    exit(1);
}
