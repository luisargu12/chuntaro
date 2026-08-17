<?php
namespace App\Controllers;

use App\Config\App;
use App\Services\EaClubsApi;

class PlantillaController
{
    /** Vista HTML: los datos los carga el navegador vía JS */
    public function index(): void
    {
        $api = new EaClubsApi();
        $clubId = $api->clubId();
        $platform = $api->platform();
        $clubName = App::name() . ' FC';

        $navSolid = true;
        $pageTitle = 'Plantilla';

        require dirname(__DIR__) . '/views/public/plantilla.php';
    }

    /** Proxy same-origin (útil en Hostinger si el browser tiene CORS) */
    public function membersApi(): array
    {
        $api = new EaClubsApi();
        $result = $api->memberStats();

        if (!($result['ok'] ?? false)) {
            http_response_code(502);
            return [
                'exito' => false,
                'mensaje' => $result['error'] ?? 'Error al consultar EA',
            ];
        }

        return [
            'exito' => true,
            'cached' => !empty($result['cached']),
            'stale' => !empty($result['stale']),
            'warning' => $result['warning'] ?? null,
            'data' => $result['data'],
        ];
    }
}
