<?php
namespace App\Controllers;

use App\Config\App;
use App\Models\Partido;

class PartidosController
{
    public function index(): void
    {
        $navSolid = true;
        $pageTitle = 'Partidos';
        $clubId = (string) App::env('EA_CLUB_ID', '2043111');
        $clubName = App::name() . ' FC';

        require dirname(__DIR__) . '/views/public/partidos.php';
    }

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

    public function history(): array
    {
        $clubId = (string) App::env('EA_CLUB_ID', '2043111');

        return [
            'exito' => true,
            'clubId' => $clubId,
            'source' => 'database',
            'data' => Partido::historyForPublic($clubId),
        ];
    }

    public function detail(): array
    {
        $matchId = trim((string) ($_GET['matchId'] ?? ''));
        if ($matchId === '') {
            http_response_code(422);
            return ['exito' => false, 'mensaje' => 'Falta matchId'];
        }

        $detail = Partido::detailForPublic(
            $matchId,
            (string) App::env('EA_CLUB_ID', '2043111')
        );
        if ($detail === null) {
            http_response_code(404);
            return ['exito' => false, 'mensaje' => 'Partido no encontrado'];
        }

        return [
            'exito' => true,
            'source' => 'database',
            'data' => $detail,
        ];
    }
}
