<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class PartidoJugador
{
    /**
     * @return array{insertados:int,actualizados:int,sin_cambios:int,omitidos:int}
     */
    public static function upsertFromEaList(array $matches, string $eaClubId): array
    {
        $stats = [
            'insertados' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'omitidos' => 0,
        ];
        $pdo = Database::conectar();
        $clubId = Club::idByEaId($eaClubId);

        foreach ($matches as $match) {
            if (!is_array($match)) {
                $stats['omitidos']++;
                continue;
            }

            foreach (self::upsertFromMatch($match, $eaClubId, $clubId, $pdo) as $key => $value) {
                $stats[$key] += $value;
            }
        }

        return $stats;
    }

    /**
     * @return array{insertados:int,actualizados:int,sin_cambios:int,omitidos:int}
     */
    private static function upsertFromMatch(
        array $match,
        string $eaClubId,
        ?int $clubId,
        PDO $pdo
    ): array {
        $stats = [
            'insertados' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'omitidos' => 0,
        ];

        $matchId = trim((string) ($match['matchId'] ?? ''));
        $players = $match['players'][$eaClubId] ?? null;
        if ($matchId === '' || !is_array($players)) {
            $stats['omitidos']++;
            return $stats;
        }

        $matchStmt = $pdo->prepare(
            'SELECT id_partido FROM tab_partidos WHERE match_id = :match_id LIMIT 1'
        );
        $matchStmt->execute([':match_id' => $matchId]);
        $partidoId = $matchStmt->fetchColumn();
        if ($partidoId === false) {
            $stats['omitidos'] += count($players);
            return $stats;
        }

        $sql = <<<'SQL'
INSERT INTO tab_partido_jugadores (
    id_partido, id_jugador, ea_player_id, gamertag, posicion, rating,
    goles, asistencias, tiros, pases_intentados, pases_completados,
    tackles_intentados, tackles_completados, atajadas,
    tarjetas_rojas, man_of_the_match, payload_json
) VALUES (
    :id_partido, :id_jugador, :ea_player_id, :gamertag, :posicion, :rating,
    :goles, :asistencias, :tiros, :pases_intentados, :pases_completados,
    :tackles_intentados, :tackles_completados, :atajadas,
    :tarjetas_rojas, :man_of_the_match, :payload_json
)
ON DUPLICATE KEY UPDATE
    id_jugador = VALUES(id_jugador),
    ea_player_id = VALUES(ea_player_id),
    posicion = VALUES(posicion),
    rating = VALUES(rating),
    goles = VALUES(goles),
    asistencias = VALUES(asistencias),
    tiros = VALUES(tiros),
    pases_intentados = VALUES(pases_intentados),
    pases_completados = VALUES(pases_completados),
    tackles_intentados = VALUES(tackles_intentados),
    tackles_completados = VALUES(tackles_completados),
    atajadas = VALUES(atajadas),
    tarjetas_rojas = VALUES(tarjetas_rojas),
    man_of_the_match = VALUES(man_of_the_match),
    payload_json = VALUES(payload_json)
SQL;

        $stmt = $pdo->prepare($sql);

        foreach ($players as $eaPlayerId => $player) {
            if (!is_array($player)) {
                $stats['omitidos']++;
                continue;
            }

            $gamertag = trim((string) ($player['playername'] ?? ''));
            if ($gamertag === '') {
                $stats['omitidos']++;
                continue;
            }

            $eaPlayerId = trim((string) $eaPlayerId);
            $jugadorId = $clubId === null
                ? null
                : Jugador::findAndLinkMatchPlayer(
                    $pdo,
                    $clubId,
                    $eaPlayerId,
                    $gamertag
                );

            $payload = json_encode(
                $player,
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );

            $stmt->execute([
                ':id_partido' => (int) $partidoId,
                ':id_jugador' => $jugadorId,
                ':ea_player_id' => $eaPlayerId !== '' ? $eaPlayerId : null,
                ':gamertag' => self::limit($gamertag, 100),
                ':posicion' => self::nullableText($player['pos'] ?? null, 20),
                ':rating' => self::nullableFloat($player['rating'] ?? null),
                ':goles' => (int) ($player['goals'] ?? 0),
                ':asistencias' => (int) ($player['assists'] ?? 0),
                ':tiros' => (int) ($player['shots'] ?? 0),
                ':pases_intentados' => (int) ($player['passattempts'] ?? 0),
                ':pases_completados' => (int) ($player['passesmade'] ?? 0),
                ':tackles_intentados' => (int) ($player['tackleattempts'] ?? 0),
                ':tackles_completados' => (int) ($player['tacklesmade'] ?? 0),
                ':atajadas' => (int) ($player['saves'] ?? 0),
                ':tarjetas_rojas' => (int) ($player['redcards'] ?? 0),
                ':man_of_the_match' => (int) ($player['mom'] ?? 0) > 0 ? 1 : 0,
                ':payload_json' => $payload === false ? null : $payload,
            ]);

            $result = match ($stmt->rowCount()) {
                1 => 'insertados',
                2 => 'actualizados',
                default => 'sin_cambios',
            };
            $stats[$result]++;
        }

        return $stats;
    }

    /**
     * @param list<array{insertados:int,actualizados:int,sin_cambios:int,omitidos:int}> $reports
     * @return array{insertados:int,actualizados:int,sin_cambios:int,omitidos:int}
     */
    public static function mergeStats(array $reports): array
    {
        $total = [
            'insertados' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'omitidos' => 0,
        ];

        foreach ($reports as $report) {
            foreach ($total as $key => $_) {
                $total[$key] += (int) ($report[$key] ?? 0);
            }
        }

        return $total;
    }

    private static function nullableText(mixed $value, int $length): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : self::limit($value, $length);
    }

    private static function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private static function limit(string $value, int $length): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, 0, $length)
            : substr($value, 0, $length);
    }
}
