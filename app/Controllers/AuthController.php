<?php
namespace App\Controllers;

use App\Models\Usuario;
use App\Core\Auth;

class AuthController
{
    public function login(): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // Acepta "usuario" o "email" (el front Vue mandaba email)
        $usuario_txt = trim((string) ($input['usuario'] ?? $input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($usuario_txt === '' || $password === '') {
            return ['exito' => false, 'mensaje' => 'Usuario y contraseña son obligatorios'];
        }

        try {
            $usuario = Usuario::buscarPorUsuario($usuario_txt);
        } catch (\PDOException $e) {
            error_log('Login DB: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error de conexión a la base de datos'];
        }

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            return ['exito' => false, 'mensaje' => 'Credenciales incorrectas'];
        }

        if ((int) $usuario['status'] !== 1) {
            return ['exito' => false, 'mensaje' => 'Usuario inactivo'];
        }

        Auth::login($usuario);

        return [
            'exito' => true,
            'mensaje' => 'Bienvenido, ' . $usuario['nombre'],
            'usuario' => [
                'id' => (int) $usuario['id_usuario'],
                'nombre' => $usuario['nombre'],
                'perfil' => $usuario['perfil'],
            ],
        ];
    }

    public function logout(): array
    {
        Auth::logout();
        return ['exito' => true];
    }

    public function me(): array
    {
        if (!Auth::check()) {
            http_response_code(401);
            return ['exito' => false, 'mensaje' => 'No autenticado'];
        }

        return [
            'exito' => true,
            'usuario' => [
                'id' => Auth::id(),
                'nombre' => $_SESSION['nombre'] ?? '',
                'perfil' => $_SESSION['perfil'] ?? '',
            ],
        ];
    }
}
