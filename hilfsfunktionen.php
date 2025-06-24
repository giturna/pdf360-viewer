<?php
declare(strict_types=1);

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        putenv("$key=$value");
    }
}

date_default_timezone_set('Europe/Berlin');


function db_connect(): mysqli
{
    $host = getenv('DB_HOST') ?: 'db';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: 'root';
    $name = getenv('DB_NAME') ?: '360cams';

    $dbh = new mysqli($host, $user, $pass, $name);

    if ($dbh->connect_errno) {
        throw new RuntimeException(
            'Database connection error: ' . $dbh->connect_error,
            $dbh->connect_errno
        );
    }

    $dbh->set_charset('utf8mb4');
    return $dbh;
}
?>
