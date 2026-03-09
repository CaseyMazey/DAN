<?php
/**
 * CLI-Helfer: Finalisiert Wochenberichte automatisiert (auch fuer verpasste Wochen).
 * Aufruf per Taskplaner:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\DAN\backend\wochenbericht_lock.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Nur per CLI ausfuehrbar.\n";
    exit(1);
}

require_once __DIR__ . '/wochenbericht_lock_core.php';

$logger = function (string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . "] {$msg}\n";
};

runWochenberichtLock($conn, null, $logger);

