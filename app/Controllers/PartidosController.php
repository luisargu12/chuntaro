<?php
namespace App\Controllers;

use App\Config\App;
use App\Models\Partido;

class PartidosController
{
    public function latest(): array
    {
        $type = (string) ($_GET['type'] ?? 'leagueMatch');
        $limit = max(1, min((int) ($_GET['limit'] ?? 3), 20));
        $clubId = (string) App::env('EA_CLUB_ID', '2043111');

        return [
            'exito' => true,
            'clubId' => $clubId,
            'source' => 'database',
            'data' => Partido::latestForPublic($type, $limit, $clubId),
        ];
    }
}
