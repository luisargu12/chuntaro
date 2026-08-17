<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Usuario
{
    public static function buscarPorUsuario(string $usuario): array|false
    {
        $pdo = Database::conectar();
        $stmt = $pdo->prepare('SELECT * FROM tab_usuarios WHERE usuario = :u LIMIT 1');
        $stmt->execute([':u' => $usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
