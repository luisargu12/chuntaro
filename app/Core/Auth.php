<?php
namespace App\Core;

use App\Config\App;

class Auth
{
    public static function requireLogin(string $loginUrl = '/admin/login'): void
    {
        if (empty($_SESSION['id_usuario'])) {
            header('Location: ' . App::url($loginUrl));
            exit;
        }
    }

    public static function check(): bool
    {
        return !empty($_SESSION['id_usuario']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null;
    }

    public static function login(array $usuario): void
    {
        $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['perfil'] = $usuario['perfil'] ?? 'admin';
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}
