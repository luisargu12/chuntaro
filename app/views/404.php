<?php

use App\Config\App;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>404</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="text-center">
    <h1 class="display-4">404</h1>
    <p class="text-muted">Página no encontrada</p>
    <a class="btn btn-dark" href="<?= htmlspecialchars(App::url('/')) ?>">Ir al inicio</a>
</div>
</body>
</html>
