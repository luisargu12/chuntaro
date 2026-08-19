<?php
namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function conectar(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        App::load();

        $host = (string) App::env('DB_HOST', 'localhost');
        $db = (string) App::env('DB_NAME', '');
        $user = (string) App::env('DB_USER', 'root');
        $pass = (string) App::env('DB_PASS', '');
        $port = (string) App::env('DB_PORT', '3306');

        try {
            // localhost sin puerto = socket Unix (Hostinger: user@localhost).
            // 127.0.0.1 = TCP (XAMPP en Windows). No reescribir el host.
            if ($host === 'localhost') {
                $dsn = "mysql:host=localhost;dbname=$db;charset=utf8mb4";
            } else {
                $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            }
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return self::$connection;
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
