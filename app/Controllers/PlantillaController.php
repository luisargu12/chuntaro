<?php
namespace App\Controllers;

use App\Config\App;
use App\Models\Jugador;
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

    /** Datos públicos de la plantilla almacenados en MySQL. */
    public function membersApi(): array
    {
        return [
            'exito' => true,
            'source' => 'database',
            'data' => Jugador::publicRoster(
                (string) App::env('EA_CLUB_ID', '2043111')
            ),
        ];
    }
}
