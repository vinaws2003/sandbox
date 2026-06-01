<?php
/**
 * scripts/safe-migrate.php — Redis-locked `php artisan migrate --force`.
 *
 * The 4 NASes share ONE database, so a deploy must migrate exactly once.
 * The first node to win the Redis lock runs migrations; the rest see the
 * lock held and skip (their DB is already migrated — it's the same DB).
 * The lock auto-expires (TTL), so a crashed migrator can never wedge deploys.
 *
 * The Redis connection is the app's own sentinel-aware `default` connection
 * (from .env), so the lock follows Sentinel failover automatically. The key
 * is auto-prefixed with REDIS_PREFIX, giving each app an independent lock.
 *
 * Usage:  php84 scripts/safe-migrate.php
 *         php84 scripts/safe-migrate.php --hold=15   # debug: hold lock N s (lock test)
 *
 * Exit: 0 = migrated or skipped (DB already current); non-zero = migration failed.
 */

putenv('LOG_CHANNEL=errorlog');   // a deploy hook must never write to storage/logs

$hold = 0;
foreach ($argv as $a) {
    if (preg_match('/^--hold=(\d+)$/', $a, $m)) { $hold = (int) $m[1]; }
}

define('LARAVEL_START', microtime(true));
$base = dirname(__DIR__);
require $base.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require $base.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$redis = $app->make('redis')->connection();   // sentinel-aware, REDIS_PREFIX applied
$key   = 'deploy:migrate:lock';
$token = bin2hex(random_bytes(16));
$ttl   = 300;                                  // seconds; longer than any single migration

// SET key token EX ttl NX  -> Status('OK') when acquired, null when already held.
$acquired = $redis->set($key, $token, 'EX', $ttl, 'NX');

if (! $acquired) {
    fwrite(STDOUT, "[safe-migrate] migrate lock held by another node — DB already current, skipping\n");
    exit(0);
}

fwrite(STDOUT, "[safe-migrate] lock acquired on ".gethostname()."\n");

$code = 0;
try {
    if ($hold > 0) {
        fwrite(STDOUT, "[safe-migrate] --hold=$hold: holding lock for lock-contention test\n");
        sleep($hold);
    }
    $code = $kernel->call('migrate', ['--force' => true]);
    fwrite(STDOUT, $kernel->output());
} catch (\Throwable $e) {
    fwrite(STDERR, "[safe-migrate] FAILED: ".$e->getMessage()."\n");
    $code = $code ?: 1;
} finally {
    // Release only if we still own it (best-effort; the TTL is the real safety net).
    try {
        if ($redis->get($key) === $token) {
            $redis->del($key);
            fwrite(STDOUT, "[safe-migrate] lock released\n");
        }
    } catch (\Throwable $e) { /* lock will expire on its own */ }
}

exit($code);
