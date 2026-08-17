<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\App;
use App\Core\Router;
use App\Core\Auth;
use App\Controllers\AuthController;
use App\Controllers\PlantillaController;
use App\Controllers\PartidosController;
use App\Controllers\EaSyncController;

App::load();

if (App::debug()) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

session_start();

$router = new Router(__DIR__ . '/../app/views');

// Entrada = vista pública
$router->view('home', 'public/home.php', protegida: false);
$router->get('plantilla', [PlantillaController::class, 'index']);
$router->get('api/ea/members', [PlantillaController::class, 'membersApi']);
$router->get('api/ea/matches', [PartidosController::class, 'latest']);
$router->post('api/sync/ea', [EaSyncController::class, 'store']);

$router->get('admin/logout', function () {
    Auth::logout();
    header('Location: ' . App::url('/admin/login'));
    exit;
});

// Admin
$router->view('admin/login', 'admin/login.php', protegida: false);
$router->view('admin/dashboard', 'admin/dashboard.php', protegida: true);

// API auth
$router->post('api/auth/login', [AuthController::class, 'login']);
$router->post('api/auth/logout', [AuthController::class, 'logout']);
$router->get('api/auth/logout', [AuthController::class, 'logout']);
$router->get('api/auth/me', [AuthController::class, 'me']);

$router->dispatch();
