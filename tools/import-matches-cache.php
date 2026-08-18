<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\App;
use App\Models\Partido;
use App\Services\EaClubsApi;

App::load();

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo puede ejecutarse desde la consola.\n");
    exit(1);
}

$api = new EaClubsApi();
$cacheDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
$pattern = $cacheDir . DIRECTORY_SEPARATOR . 'matches_' . $api->clubId() . '_' . $api->platform() . '_*.json';
$files = glob($pattern) ?: [];

if ($files === []) {
    fwrite(STDERR, "No hay archivos de caché de partidos para importar.\n");
    exit(1);
}

$reports = [];

foreach ($files as $file) {
    $base = basename($file, '.json');
    if (!preg_match('/_((?:league|playoff|friendly)Match)_\d+$/', $base, $matches)) {
        fwrite(STDOUT, "Omitido (nombre no reconocido): {$base}\n");
        continue;
    }

    $tipo = $matches[1];
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) {
        fwrite(STDOUT, "Omitido (JSON inválido): {$base}\n");
        continue;
    }

    fwrite(STDOUT, "Importando {$tipo} desde {$base}... ");

    try {
        $stats = Partido::upsertFromEaList(
            $data,
            $tipo,
            $api->clubId(),
            $api->platform()
        );
        $reports[] = $stats;
        fwrite(
            STDOUT,
            sprintf(
                "OK (insertados %d, actualizados %d, sin cambios %d, omitidos %d)\n",
                $stats['insertados'],
                $stats['actualizados'],
                $stats['sin_cambios'],
                $stats['omitidos']
            )
        );
    } catch (\PDOException $e) {
        fwrite(STDOUT, "ERROR\n");
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
}

$total = Partido::mergeStats($reports);
fwrite(STDOUT, json_encode($total, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
