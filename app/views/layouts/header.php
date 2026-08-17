<?php

use App\Config\App;

$ruta_actual = $_GET['ruta'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(App::name()) ?> — Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(App::asset('css/style.css')) ?>">
</head>
<body>
<div class="d-flex" id="wrapper">
<?php require __DIR__ . '/sidebar.php'; ?>
<div id="page-content-wrapper" class="w-100">
