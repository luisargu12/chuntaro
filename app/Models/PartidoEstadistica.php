<?php
namespace App\Models;

use App\Config\Database;

class PartidoEstadistica
{
    /**
     * Guarda las estadísticas generales de ambos clubes en cada partido.
     *
     * @return array{insertados:int,actualizados:int,sin_cambios:int,omitidos:int}
     */
    public static function upsertFromEaList(array $matches, string $principalClubId): array
    {
        $stats = [
            'insertados' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'omitidos' => 0,
        ];
        $pdo = Database::conectar();

        $matchStmt = $pdo->prepare(
            'SELECT id_partido FROM tab_partidos WHERE match_id = :match_id LIMIT 1'
        );

        $sql = <<<'SQL'
INSERT INTO tab_partido_estadisticas (
    id_partido, id_club, ea_club_id, es_principal, jugadores_contados,
    goles, asistencias, tiros, pases_intentados, pases_completados,
    tackles_intentados, tackles_completados, atajadas, tarjetas_rojas,
    porterias_cero, man_of_the_match, rating_suma, rating_promedio,
    segundos_jugados, payload_json, sincronizado_en
) VALUES (
    :id_partido, :id_club, :ea_club_id, :es_principal, :jugadores_contados,
    :goles, :asistencias, :tiros, :pases_intentados, :pases_completados,
    :tackles_intentados, :tackles_completados, :atajadas, :tarjetas_rojas,
    :porterias_cero, :man_of_the_match, :rating_suma, :rating_promedio,
    :segundos_jugados, :payload_json, CURRENT_TIMESTAMP
)
ON DUPLICATE KEY UPDATE
    id_club = VALUES(id_club),
    es_principal = VALUES(es_principal),
    jugadores_contados = VALUES(jugadores_contados),
    goles = VALUES(goles),
    asistencias = VALUES(asistencias),
    tiros = VALUES(tiros),
    pases_intentados = VALUES(pases_intentados),
    pases_completados = VALUES(pases_completados),
    tackles_intentados = VALUES(tackles_intentados),
    tackles_completados = VALUES(tackles_completados),
    atajadas = VALUES(atajadas),
    tarjetas_rojas = VALUES(tarjetas_rojas),
    porterias_cero = VALUES(porterias_cero),
    man_of_the_match = VALUES(man_of_the_match),
    rating_suma = VALUES(rating_suma),
    rating_promedio = VALUES(rating_promedio),
    segundos_jugados = VALUES(segundos_jugados),
    payload_json = VALUES(payload_json),
    sincronizado_en = CURRENT_TIMESTAMP
SQL;
        $upsert = $pdo->prepare($sql);

        foreach ($matches as $match) {
            if (!is_array($match)) {
                $stats['omitidos']++;
                continue;
            }

            $matchId = trim((string) ($match['matchId'] ?? ''));
            $aggregates = $match['aggregate'] ?? null;
            if ($matchId === '' || !is_array($aggregates)) {
                $stats['omitidos']++;
                continue;
            }

            $matchStmt->execute([':match_id' => $matchId]);
            $partidoId = $matchStmt->fetchColumn();
            if ($partidoId === false) {
                $stats['omitidos'] += count($aggregates);
                continue;
            }

            foreach ($aggregates as $eaClubId => $aggregate) {
                if (!is_array($aggregate)) {
                    $stats['omitidos']++;
                    continue;
                }

                $eaClubId = trim((string) $eaClubId);
                if ($eaClubId === '') {
                    $stats['omitidos']++;
                    continue;
                }

                $players = $match['players'][$eaClubId] ?? [];
                $playerCount = is_array($players) ? count($players) : 0;
                $ratingSum = self::nullableFloat($aggregate['rating'] ?? null);
                $ratingAverage = $ratingSum !== null && $playerCount > 0
                    ? round($ratingSum / $playerCount, 2)
                    : null;
                $payload = json_encode(
                    $aggregate,
                    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                );

                $upsert->execute([
                    ':id_partido' => (int) $partidoId,
                    ':id_club' => Club::idByEaId($eaClubId),
                    ':ea_club_id' => $eaClubId,
                    ':es_principal' => $eaClubId === $principalClubId ? 1 : 0,
                    ':jugadores_contados' => $playerCount,
                    ':goles' => (int) ($aggregate['goals'] ?? 0),
                    ':asistencias' => (int) ($aggregate['assists'] ?? 0),
                    ':tiros' => (int) ($aggregate['shots'] ?? 0),
                    ':pases_intentados' => (int) ($aggregate['passattempts'] ?? 0),
                    ':pases_completados' => (int) ($aggregate['passesmade'] ?? 0),
                    ':tackles_intentados' => (int) ($aggregate['tackleattempts'] ?? 0),
                    ':tackles_completados' => (int) ($aggregate['tacklesmade'] ?? 0),
                    ':atajadas' => (int) ($aggregate['saves'] ?? 0),
                    ':tarjetas_rojas' => (int) ($aggregate['redcards'] ?? 0),
                    ':porterias_cero' => (int) ($aggregate['cleansheetsany'] ?? 0),
                    ':man_of_the_match' => (int) ($aggregate['mom'] ?? 0),
                    ':rating_suma' => $ratingSum,
                    ':rating_promedio' => $ratingAverage,
                    ':segundos_jugados' => (int) ($aggregate['secondsPlayed'] ?? 0),
                    ':payload_json' => $payload === false ? null : $payload,
                ]);

                $result = match ($upsert->rowCount()) {
                    1 => 'insertados',
                    2 => 'actualizados',
                    default => 'sin_cambios',
                };
                $stats[$result]++;
            }
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

    private static function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}
