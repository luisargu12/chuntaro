<?php
namespace App\Controllers;

use App\Config\App;
use App\Services\EaClubsApi;

class EaSyncController
{
    public function store(): array
    {
        $expectedToken = (string) App::env('EA_SYNC_TOKEN', '');
        $providedToken = $this->requestToken();

        if (
            strlen($expectedToken) < 32
            || $providedToken === ''
            || !hash_equals($expectedToken, $providedToken)
        ) {
            http_response_code(401);
            return ['exito' => false, 'mensaje' => 'Token de sincronización inválido'];
        }

        $rawBody = file_get_contents('php://input');
        if ($rawBody === false || strlen($rawBody) > 2_000_000) {
            http_response_code(413);
            return ['exito' => false, 'mensaje' => 'Payload vacío o demasiado grande'];
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            http_response_code(422);
            return ['exito' => false, 'mensaje' => 'JSON inválido'];
        }

        $api = new EaClubsApi();
        if (
            (string) ($payload['clubId'] ?? '') !== $api->clubId()
            || (string) ($payload['platform'] ?? '') !== $api->platform()
        ) {
            http_response_code(422);
            return ['exito' => false, 'mensaje' => 'Club o plataforma no coinciden con el servidor'];
        }

        $resources = $payload['resources'] ?? null;
        if (!is_array($resources)) {
            http_response_code(422);
            return ['exito' => false, 'mensaje' => 'No se recibieron recursos para sincronizar'];
        }

        $imported = $api->importSyncedData(
            $resources,
            (int) ($payload['matchLimit'] ?? 10)
        );

        $stored = $imported['recursos'] ?? [];
        if ($stored === []) {
            http_response_code(422);
            return ['exito' => false, 'mensaje' => 'Ningún recurso válido fue almacenado'];
        }

        $response = [
            'exito' => true,
            'mensaje' => 'Datos de EA sincronizados',
            'recursos' => $stored,
            'clubes' => $imported['clubes'] ?? null,
            'clubEstadisticas' => $imported['clubEstadisticas'] ?? null,
            'logrosPlayoff' => $imported['logrosPlayoff'] ?? null,
            'jugadores' => $imported['jugadores'] ?? null,
            'partidos' => $imported['partidos'] ?? null,
            'partidoJugadores' => $imported['partidoJugadores'] ?? null,
            'partidoEstadisticas' => $imported['partidoEstadisticas'] ?? null,
            'sincronizadoEn' => gmdate(DATE_ATOM),
        ];

        $warnings = array_values(array_filter([
            $imported['clubesWarning'] ?? null,
            $imported['clubEstadisticasWarning'] ?? null,
            $imported['logrosPlayoffWarning'] ?? null,
            $imported['jugadoresWarning'] ?? null,
            $imported['partidosWarning'] ?? null,
            $imported['partidoJugadoresWarning'] ?? null,
            $imported['partidoEstadisticasWarning'] ?? null,
        ]));
        if ($warnings !== []) {
            $response['warning'] = implode(' | ', $warnings);
        }

        return $response;
    }

    private function requestToken(): string
    {
        $token = (string) ($_SERVER['HTTP_X_SYNC_TOKEN'] ?? '');
        if ($token !== '') {
            return $token;
        }

        $authorization = (string) (
            $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? ''
        );

        if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }
}
