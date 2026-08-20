<?php
namespace App\Controllers;

use App\Config\App;
use App\Models\ClubEstadistica;

class ClubController
{
    public function overview(): array
    {
        $overview = ClubEstadistica::publicOverview(
            (string) App::env('EA_CLUB_ID', '2043111')
        );

        if ($overview === null) {
            http_response_code(404);
            return [
                'exito' => false,
                'mensaje' => 'Todavía no hay estadísticas históricas sincronizadas',
            ];
        }

        return [
            'exito' => true,
            'source' => 'database',
            'data' => $overview,
        ];
    }
}
