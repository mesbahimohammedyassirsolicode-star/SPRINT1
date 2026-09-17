<?php
/**
 * Database Connection Helper (PDO)
 * Project: Hotel Reservation System
 */

declare(strict_types=1);

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $host = '127.0.0.1';
        $port = 3306;
        $dbname = 'reservation_hotels';
        $user = 'root';
        $password = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            die("<div style='font-family:sans-serif;padding:20px;background:#ffebee;color:#c62828;border-radius:8px;margin:20px;'>
                <h2>Database Connection Error</h2>
                <p>Could not connect to MySQL database <strong>{$dbname}</strong> on {$host}:{$port}.</p>
                <p><strong>Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            </div>");
        }
        return $pdo;
    }

    return $pdo;
}
