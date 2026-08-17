<?php

use App\Config\App;

$ruta_actual = $_GET['ruta'] ?? '';
$menu = [
    ['ruta' => 'admin/dashboard', 'icono' => 'bi-speedometer2', 'label' => 'Dashboard'],
];
?>
<nav id="sidebar" class="bg-dark text-white d-flex flex-column p-3" style="min-width:220px;min-height:100vh;">
    <a class="navbar-brand text-white fw-bold fs-4 mb-4 text-decoration-none" href="<?= htmlspecialchars(App::url('/admin/dashboard')) ?>">
        <i class="bi bi-grid-3x3-gap-fill me-2"></i><?= htmlspecialchars(App::name()) ?>
    </a>
    <ul class="nav flex-column gap-1 flex-grow-1">
        <?php foreach ($menu as $item): ?>
            <li class="nav-item">
                <a class="nav-link text-white rounded <?= ($ruta_actual === $item['ruta']) ? 'active bg-primary' : '' ?>"
                   href="<?= htmlspecialchars(App::url('/' . $item['ruta'])) ?>">
                    <i class="bi <?= $item['icono'] ?> me-2"></i>
                    <?= $item['label'] ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="mt-auto pt-3 border-top border-secondary">
        <small class="text-secondary d-block mb-2">
            <i class="bi bi-person-circle me-1"></i>
            <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
        </small>
        <a href="<?= htmlspecialchars(App::url('/')) ?>" class="btn btn-sm btn-outline-light w-100 mb-2">Ver sitio</a>
        <a href="<?= htmlspecialchars(App::url('/admin/logout')) ?>" class="btn btn-sm btn-outline-danger w-100">
            <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
        </a>
    </div>
</nav>
