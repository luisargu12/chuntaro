<?php
namespace App\Controllers;

use App\Services\EaClubsApi;

class PartidosController
{
    public function latest(): array
    {
        $type = (string) ($_GET['type'] ?? 'leagueMatch');
        $limit = (int) ($_GET['limit'] ?? 3);

        $api = new EaClubsApi();
        $result = $api->matches($type, $limit);

        if (!($result['ok'] ?? false)) {
            http_response_code((int) ($result['status'] ?? 502));
            return [
                'exito' => false,
                'mensaje' => $result['error'] ?? 'No se pudieron consultar los partidos',
            ];
        }

        return [
            'exito' => true,
            'clubId' => $api->clubId(),
            'cached' => !empty($result['cached']),
            'stale' => !empty($result['stale']),
            'warning' => $result['warning'] ?? null,
            'data' => $result['data'],
        ];
    }
}
