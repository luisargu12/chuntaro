<?php

use App\Config\App;

$appName = App::name();
$ruta = $_GET['ruta'] ?? 'home';
$navSolid = $navSolid ?? false;
$pageTitle = $pageTitle ?? null;
$docTitle = $pageTitle ? ($pageTitle . ' · ' . $appName . ' FC') : ($appName . ' FC');
$navLinkClass = static fn (bool $active): string =>
    'nav-link text-white fw-semibold' . ($active ? ' active' : '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($docTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(App::asset('css/style.css')) ?>">
    <link rel="icon" href="<?= htmlspecialchars(App::asset('img/favicon.png')) ?>" type="image/x-icon">
</head>
<body>
<nav id="mainNav" class="navbar navbar-expand-lg navbar-dark fixed-top custom-navbar <?= $navSolid ? 'navbar-solid shadow' : 'navbar-transparent' ?>">
    <div class="container position-relative mt-3">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= htmlspecialchars(App::url('/')) ?>">
            <img src="<?= htmlspecialchars(App::asset('img/escudo.png')) ?>" alt="Escudo Chuntaro FC" class="logo-escudo">
        </a>
        <button class="navbar-toggler border-0 ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 mt-3 mt-lg-0 align-items-center gap-3">
                <li class="nav-item">
                    <a class="<?= $navLinkClass($ruta === 'home' || $ruta === '') ?>"
                       href="<?= htmlspecialchars(App::url('/')) ?>">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="<?= $navLinkClass(str_starts_with($ruta, 'martes-botanero')) ?>"
                       href="<?= htmlspecialchars(App::url('/martes-botanero')) ?>">Martes Botanero</a>
                </li>
                <li class="nav-item">
                    <a class="<?= $navLinkClass(str_starts_with($ruta, 'fc-clubs')) ?>"
                       href="<?= htmlspecialchars(App::url('/fc-clubs')) ?>">FC Clubs</a>
                </li>
                <li class="nav-item">
                    <a class="text-decoration-none btn-reta text-center d-inline-block"
                       href="<?= htmlspecialchars(App::url('/torneo')) ?>">Torneo</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
