<?php
/**
 * scripts/safe-migrate.php — run `php artisan migrate --force` exactly once
 * across the 4-NAS cluster, guarded by a cluster-wide lock.
 *
 * All 4 NASes share one database, so a deploy must migrate once. The deploy
 * workflow runs this from ONE designated node, so every migrate invocation
 * reaches the same DB endpoint; the lock then serializes *overlapping deploys*
 * of the same app — the first acquires and migrates, the rest skip (the DB is
 * already current). The mutex is a MySQL GET_LOCK:
 *   - reachable from the migrating node by definition (migrate needs the DB);
 *   - session-scoped, so a crashed migrator auto-releases (no wedged deploys);
 *   - named per-app (GET_LOCK names are server-global) so concurrent deploys
 *     of different apps don't falsely block each other.
 *
 * Why not a Redis lock: the CLI redis client diverges per node (verified: some
 * apps have neither ext-redis at the CLI nor predis vendored). Why not a
 * cross-node lock: nodes connect to different DB endpoints (direct vs local
 * ProxySQL) and the cluster is Galera, where GET_LOCK is node-local — so no
 * primitive locks uniformly across nodes. Single-designated-node migrate side-
 * steps that: if that node is down during a deploy, the migrate step fails
 * loudly (code still deployed) rather than racing migrations.
 *
 * Usage:  php84 scripts/safe-migrate.php
 *         php84 scripts/safe-migrate.php --hold=15   # debug: hold lock N s (contention test)
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

$db   = $app->make('db')->connection();          // default connection = the shared DB
$lock = 'migrate_'.$db->getDatabaseName();        // per-app scope on the server-global lock namespace

// GET_LOCK(name, 0): 1 = acquired, 0 = held by another session, NULL = error.
$got = (int) ($db->selectOne('SELECT GET_LOCK(?, 0) AS l', [$lock])->l ?? 0);
if ($got !== 1) {
    fwrite(STDOUT, "[safe-migrate] migrate lock held by a concurrent deploy — DB already current, skipping\n");
    exit(0);
}

fwrite(STDOUT, "[safe-migrate] lock acquired on ".gethostname()." ($lock)\n");

$code = 0;
try {
    if ($hold > 0) {
        fwrite(STDOUT, "[safe-migrate] --hold=$hold: holding lock for contention test\n");
        sleep($hold);
    }
    $code = $kernel->call('migrate', ['--force' => true]);
    fwrite(STDOUT, $kernel->output());
} catch (\Throwable $e) {
    fwrite(STDERR, "[safe-migrate] FAILED: ".$e->getMessage()."\n");
    $code = $code ?: 1;
} finally {
    // Best-effort release; the lock also auto-releases when this session ends.
    try { $db->statement('SELECT RELEASE_LOCK(?)', [$lock]); } catch (\Throwable $e) { /* expires with session */ }
    fwrite(STDOUT, "[safe-migrate] lock released\n");
}

exit($code);
