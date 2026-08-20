<?php
namespace App\Core;

class Router
{
    private array $routes = [];
    private string $viewsPath;

    public function __construct(string $viewsPath)
    {
        $this->viewsPath = rtrim($viewsPath, '/\\');
    }

    public function get(string $ruta, callable|array $handler): void
    {
        $this->routes['GET'][$ruta] = $handler;
    }

    public function post(string $ruta, callable|array $handler): void
    {
        $this->routes['POST'][$ruta] = $handler;
    }

    public function view(string $ruta, string $archivo, bool $protegida = true): void
    {
        $viewsPath = $this->viewsPath;
        $this->routes['GET'][$ruta] = function () use ($archivo, $viewsPath, $protegida) {
            if ($protegida) {
                Auth::requireLogin();
            }
            $fullPath = $viewsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $archivo);
            if (file_exists($fullPath)) {
                require $fullPath;
            } else {
                http_response_code(404);
                echo "Vista no encontrada: $archivo";
            }
        };
    }

    public function dispatch(): void
    {
        $ruta = $_GET['ruta'] ?? 'home';
        $ruta = trim($ruta, '/');
        if ($ruta === '') {
            $ruta = 'home';
        }

        $metodo = $_SERVER['REQUEST_METHOD'];

        if ($this->isApiRoute($ruta)) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $handler = $this->routes[$metodo][$ruta]
            ?? $this->routes['GET'][$ruta]
            ?? null;

        if ($handler === null) {
            $this->notFound($ruta);
            return;
        }

        try {
            $result = $this->callHandler($handler);
        } catch (\Throwable $e) {
            error_log('Ruta ' . $ruta . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            if (!$this->isApiRoute($ruta)) {
                throw $e;
            }
            http_response_code(500);
            $mensaje = 'Error interno del servidor';
            if (\App\Config\App::debug()) {
                $mensaje = $e->getMessage();
            }
            echo json_encode(['exito' => false, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (is_array($result)) {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
    }

    private function callHandler(callable|array $handler): mixed
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = new $class();
            return $instance->$method();
        }

        return call_user_func($handler);
    }

    private function notFound(string $ruta): void
    {
        http_response_code(404);

        if ($this->isApiRoute($ruta)) {
            echo json_encode(['error' => 'Endpoint no encontrado: ' . $ruta]);
            return;
        }

        $file = $this->viewsPath . DIRECTORY_SEPARATOR . '404.php';
        if (file_exists($file)) {
            require $file;
            return;
        }

        echo "404 — Ruta no encontrada: " . htmlspecialchars($ruta);
    }

    private function isApiRoute(string $ruta): bool
    {
        return str_starts_with($ruta, 'api/')
            || str_contains($ruta, '/api/');
    }
}
