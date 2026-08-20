<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\App;
use App\Core\Router;
use App\Core\Auth;
use App\Controllers\AuthController;
use App\Controllers\PlantillaController;
use App\Controllers\PartidosController;
use App\Controllers\EaSyncController;
use App\Controllers\JugadorController;
use App\Controllers\ClubController;

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
$router->view('home', 'public/inicio.php', protegida: false);
$router->view('martes-botanero', 'public/proximamente.php', protegida: false);
$router->view('fc-clubs', 'public/home.php', protegida: false);
$router->get('fc-clubs/plantilla', [PlantillaController::class, 'index']);
$router->get('fc-clubs/plantilla/{gamertag}', [JugadorController::class, 'show']);
$router->get('fc-clubs/partidos', [PartidosController::class, 'index']);
$router->get('fc-clubs/api/ea/members', [PlantillaController::class, 'membersApi']);
$router->get('fc-clubs/api/ea/matches', [PartidosController::class, 'latest']);
$router->get('fc-clubs/api/overview', [ClubController::class, 'overview']);
$router->get('fc-clubs/api/partidos', [PartidosController::class, 'history']);
$router->get('fc-clubs/api/partidos/detalle', [PartidosController::class, 'detail']);
$router->view('torneo', 'public/proximamente.php', protegida: false);

// Compatibilidad con enlaces públicos anteriores.
$router->get('plantilla', function () {
    header('Location: ' . App::url('/fc-clubs/plantilla'), true, 301);
    exit;
});
$router->get('api/ea/members', [PlantillaController::class, 'membersApi']);
$router->get('api/ea/matches', [PartidosController::class, 'latest']);

// La sincronización conserva su URL para no romper tools/sync-ea.php.
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
