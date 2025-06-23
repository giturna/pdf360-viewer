<?php
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue; // yorum satırı veya bozuk satır
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        putenv("$key=$value");
    }
}

date_default_timezone_set('Europe/Berlin');

/* Establishes the connection to the database \$dbName and
returns the database handler \$dbh. */
function db_connect($dbName)
{
    $servername = getenv('DB_SERVER');
    $username = getenv('DB_USER');
    $password = getenv('DB_PASS');

    // Create connection
    $dbh = new mysqli($servername, $username, $password, $dbName);
    // Check connection
    if ($dbh->connect_error) {
        die("Connection failed: " . $dbh->connect_error);
    }else{
        return $dbh;
    }
}




?>