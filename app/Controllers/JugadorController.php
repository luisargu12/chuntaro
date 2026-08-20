<?php
namespace App\Controllers;

use App\Config\App;
use App\Models\Jugador;

class JugadorController
{
    public function show(string $slug): void
    {
        $clubId = (string) App::env('EA_CLUB_ID', '2043111');
        $gamertag = Jugador::gamertagFromSlug(
            trim(rawurldecode($slug)),
            $clubId
        );
        $player = $gamertag === null
            ? null
            : Jugador::profileForPublic($gamertag, $clubId);

        if ($player === null) {
            http_response_code(404);
            require dirname(__DIR__) . '/views/404.php';
            return;
        }

        $navSolid = true;
        $pageTitle = (string) $player['gamertag'];
        require dirname(__DIR__) . '/views/public/jugador.php';
    }
}
