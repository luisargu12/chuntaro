<?php
namespace App\Config;

use PDO;
use PDOException;

class Database
{
    public static function conectar(): PDO
    {
        App::load();

        $host = App::env('DB_HOST', 'localhost');
        $db = App::env('DB_NAME', '');
        $user = App::env('DB_USER', 'root');
        $pass = App::env('DB_PASS', '');

        try {
            $tcpHost = ($host === 'localhost') ? '127.0.0.1' : $host;
            $dsn = "mysql:host=$tcpHost;port=3306;dbname=$db;charset=utf8mb4";
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            http_response_code(500);
            if (App::debug()) {
                die(json_encode(['error' => 'DB: ' . $e->getMessage()]));
            }
            die(json_encode(['error' => 'Error de conexión a la base de datos']));
        }
    }
}
