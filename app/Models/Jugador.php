<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use RuntimeException;

class Jugador
{
    public static function gamertagFromSlug(string $slug, string $eaClubId): ?string
    {
        $pdo = Database::conectar();
        $stmt = $pdo->prepare(
            'SELECT j.gamertag
             FROM tab_jugadores j
             INNER JOIN tab_clubes c ON c.id_club = j.id_club
             WHERE c.ea_club_id = :ea_club_id'
        );
        $stmt->execute([':ea_club_id' => $eaClubId]);

        foreach ($stmt->fetchAll() as $row) {
            $gamertag = (string) $row['gamertag'];
            if (self::slug($gamertag) === strtolower(trim($slug))) {
                return $gamertag;
            }
        }

        return null;
    }

    /**
     * Perfil público completo de un jugador.
     *
     * @return array<string,mixed>|null
     */
    public static function profileForPublic(string $gamertag, string $eaClubId): ?array
    {
        $pdo = Database::conectar();
        $stmt = $pdo->prepare(
            'SELECT
                j.id_jugador, j.ea_player_id, j.gamertag, j.pro_name,
                j.favorite_position, j.pro_position, j.nacionalidad_id,
                j.altura_cm, j.overall, j.foto_url, j.foto_path, j.activo,
                e.partidos_jugados, e.win_rate, e.goles, e.asistencias,
                e.rating_promedio, e.man_of_the_match, e.tarjetas_rojas,
                e.porterias_defensa, e.porterias_portero,
                e.pases_completados, e.porcentaje_pases,
                e.tackles_completados, e.porcentaje_tackles,
                e.porcentaje_tiros, c.id_club
             FROM tab_jugadores j
             INNER JOIN tab_clubes c ON c.id_club = j.id_club
             LEFT JOIN tab_jugador_estadisticas e ON e.id_jugador = j.id_jugador
             WHERE c.ea_club_id = :ea_club_id AND j.gamertag = :gamertag
             LIMIT 1'
        );
        $stmt->execute([
            ':ea_club_id' => $eaClubId,
            ':gamertag' => $gamertag,
        ]);
        $player = $stmt->fetch();
        if ($player === false) {
            return null;
        }

        $matchStmt = $pdo->prepare(
            'SELECT
                p.match_id, p.timestamp_ea, p.jugado_en, p.tipo,
                p.rival_club_id, p.rival_nombre, p.goles_favor,
                p.goles_contra, p.resultado, rival.crest_asset_id,
                pj.posicion, pj.rating, pj.goles, pj.asistencias,
                pj.tiros, pj.pases_intentados, pj.pases_completados,
                pj.tackles_intentados, pj.tackles_completados,
                pj.atajadas, pj.tarjetas_rojas, pj.man_of_the_match
             FROM tab_partido_jugadores pj
             INNER JOIN tab_partidos p ON p.id_partido = pj.id_partido
             LEFT JOIN tab_clubes rival ON rival.ea_club_id = p.rival_club_id
             WHERE pj.id_jugador = :id_jugador
             ORDER BY p.timestamp_ea DESC
             LIMIT 100'
        );
        $matchStmt->execute([':id_jugador' => (int) $player['id_jugador']]);
        $matches = array_map(
            static fn (array $row): array => [
                'matchId' => (string) $row['match_id'],
                'timestamp' => (int) $row['timestamp_ea'],
                'playedAt' => $row['jugado_en'],
                'type' => $row['tipo'],
                'rivalClubId' => (string) $row['rival_club_id'],
                'rivalName' => $row['rival_nombre'],
                'rivalCrestAssetId' => $row['crest_asset_id'],
                'goalsFor' => (int) $row['goles_favor'],
                'goalsAgainst' => (int) $row['goles_contra'],
                'result' => $row['resultado'],
                'position' => $row['posicion'],
                'rating' => $row['rating'] === null ? null : (float) $row['rating'],
                'goals' => (int) $row['goles'],
                'assists' => (int) $row['asistencias'],
                'shots' => (int) $row['tiros'],
                'passAttempts' => (int) $row['pases_intentados'],
                'passesCompleted' => (int) $row['pases_completados'],
                'tackleAttempts' => (int) $row['tackles_intentados'],
                'tacklesCompleted' => (int) $row['tackles_completados'],
                'saves' => (int) $row['atajadas'],
                'redCards' => (int) $row['tarjetas_rojas'],
                'manOfTheMatch' => (bool) $row['man_of_the_match'],
            ],
            $matchStmt->fetchAll()
        );

        $ratedMatches = array_values(array_filter(
            $matches,
            static fn (array $match): bool => $match['rating'] !== null
        ));
        $lastFive = array_slice($ratedMatches, 0, 5);
        $recentRating = $lastFive === []
            ? null
            : round(array_sum(array_column($lastFive, 'rating')) / count($lastFive), 2);

        $bestMatch = null;
        foreach ($matches as $match) {
            if (
                $match['rating'] !== null
                && ($bestMatch === null || $match['rating'] > $bestMatch['rating'])
            ) {
                $bestMatch = $match;
            }
        }

        $gamesPlayed = (int) ($player['partidos_jugados'] ?? 0);
        $clubDbId = (int) $player['id_club'];

        return [
            'id' => (int) $player['id_jugador'],
            'eaPlayerId' => $player['ea_player_id'],
            'gamertag' => $player['gamertag'],
            'proName' => $player['pro_name'],
            'favoritePosition' => $player['favorite_position'],
            'proPosition' => $player['pro_position'],
            'nationalityId' => self::nullableInt($player['nacionalidad_id']),
            'heightCm' => self::nullableInt($player['altura_cm']),
            'overall' => self::nullableInt($player['overall']),
            'photoUrl' => $player['foto_url'],
            'photoPath' => $player['foto_path'],
            'active' => (bool) $player['activo'],
            'stats' => [
                'gamesPlayed' => $gamesPlayed,
                'winRate' => (float) ($player['win_rate'] ?? 0),
                'goals' => (int) ($player['goles'] ?? 0),
                'assists' => (int) ($player['asistencias'] ?? 0),
                'rating' => $player['rating_promedio'] === null
                    ? null
                    : (float) $player['rating_promedio'],
                'manOfTheMatch' => (int) ($player['man_of_the_match'] ?? 0),
                'redCards' => (int) ($player['tarjetas_rojas'] ?? 0),
                'cleanSheetsDef' => (int) ($player['porterias_defensa'] ?? 0),
                'cleanSheetsGk' => (int) ($player['porterias_portero'] ?? 0),
                'passesCompleted' => (int) ($player['pases_completados'] ?? 0),
                'passSuccessRate' => (float) ($player['porcentaje_pases'] ?? 0),
                'tacklesCompleted' => (int) ($player['tackles_completados'] ?? 0),
                'tackleSuccessRate' => (float) ($player['porcentaje_tackles'] ?? 0),
                'shotSuccessRate' => (float) ($player['porcentaje_tiros'] ?? 0),
                'goalsPerGame' => $gamesPlayed > 0
                    ? round((int) $player['goles'] / $gamesPlayed, 2)
                    : 0,
                'assistsPerGame' => $gamesPlayed > 0
                    ? round((int) $player['asistencias'] / $gamesPlayed, 2)
                    : 0,
            ],
            'form' => [
                'recentRating' => $recentRating,
                'bestMatch' => $bestMatch,
                'lastFiveRatings' => array_column($lastFive, 'rating'),
            ],
            'rankings' => [
                'goals' => self::ranking($pdo, $clubDbId, 'goles', (int) ($player['goles'] ?? 0)),
                'assists' => self::ranking(
                    $pdo,
                    $clubDbId,
                    'asistencias',
                    (int) ($player['asistencias'] ?? 0)
                ),
                'rating' => self::ranking(
                    $pdo,
                    $clubDbId,
                    'rating_promedio',
                    (float) ($player['rating_promedio'] ?? 0)
                ),
            ],
            'matches' => $matches,
        ];
    }

    /**
     * Devuelve la plantilla normalizada directamente desde MySQL.
     *
     * @return array{members:list<array<string,mixed>>,positionCount:array<string,int>}
     */
    public static function publicRoster(string $eaClubId): array
    {
        $pdo = Database::conectar();
        $stmt = $pdo->prepare(
            'SELECT
                j.gamertag, j.pro_name, j.favorite_position, j.pro_position,
                j.nacionalidad_id, j.altura_cm, j.overall, j.foto_url,
                j.foto_path, e.partidos_jugados, e.win_rate, e.goles,
                e.asistencias, e.rating_promedio, e.man_of_the_match,
                e.tarjetas_rojas, e.porterias_defensa, e.porterias_portero,
                e.pases_completados, e.porcentaje_pases,
                e.tackles_completados, e.porcentaje_tackles,
                e.porcentaje_tiros
             FROM tab_jugadores j
             INNER JOIN tab_clubes c ON c.id_club = j.id_club
             LEFT JOIN tab_jugador_estadisticas e ON e.id_jugador = j.id_jugador
             WHERE c.ea_club_id = :ea_club_id AND j.activo = 1
             ORDER BY e.rating_promedio DESC, e.goles DESC, j.gamertag ASC'
        );
        $stmt->execute([':ea_club_id' => $eaClubId]);

        $members = [];
        $positionCount = [
            'forward' => 0,
            'midfielder' => 0,
            'defender' => 0,
            'goalkeeper' => 0,
        ];

        foreach ($stmt->fetchAll() as $row) {
            $position = (string) ($row['favorite_position'] ?? '');
            if (array_key_exists($position, $positionCount)) {
                $positionCount[$position]++;
            }

            $members[] = [
                'name' => $row['gamertag'],
                'slug' => self::slug((string) $row['gamertag']),
                'proName' => $row['pro_name'],
                'favoritePosition' => $position,
                'proPos' => $row['pro_position'],
                'proNationality' => self::nullableInt($row['nacionalidad_id']),
                'proHeight' => self::nullableInt($row['altura_cm']),
                'proOverall' => self::nullableInt($row['overall']),
                'gamesPlayed' => (int) ($row['partidos_jugados'] ?? 0),
                'winRate' => (float) ($row['win_rate'] ?? 0),
                'goals' => (int) ($row['goles'] ?? 0),
                'assists' => (int) ($row['asistencias'] ?? 0),
                'ratingAve' => $row['rating_promedio'] === null
                    ? null
                    : (float) $row['rating_promedio'],
                'manOfTheMatch' => (int) ($row['man_of_the_match'] ?? 0),
                'redCards' => (int) ($row['tarjetas_rojas'] ?? 0),
                'cleanSheetsDef' => (int) ($row['porterias_defensa'] ?? 0),
                'cleanSheetsGK' => (int) ($row['porterias_portero'] ?? 0),
                'passesMade' => (int) ($row['pases_completados'] ?? 0),
                'passSuccessRate' => (float) ($row['porcentaje_pases'] ?? 0),
                'tacklesMade' => (int) ($row['tackles_completados'] ?? 0),
                'tackleSuccessRate' => (float) ($row['porcentaje_tackles'] ?? 0),
                'shotSuccessRate' => (float) ($row['porcentaje_tiros'] ?? 0),
                'photoUrl' => $row['foto_url'],
                'photoPath' => $row['foto_path'],
            ];
        }

        return ['members' => $members, 'positionCount' => $positionCount];
    }

    /**
     * Guarda la plantilla y sus estadísticas acumuladas.
     *
     * @return array{insertados:int,actualizados:int,estadisticas:int,omitidos:int}
     */
    public static function upsertFromMembers(array $data, string $eaClubId): array
    {
        $members = $data['members'] ?? [];
        if (!is_array($members)) {
            return ['insertados' => 0, 'actualizados' => 0, 'estadisticas' => 0, 'omitidos' => 0];
        }

        $clubId = Club::idByEaId($eaClubId);
        if ($clubId === null) {
            throw new RuntimeException("No existe tab_clubes.ea_club_id={$eaClubId}");
        }

        $stats = ['insertados' => 0, 'actualizados' => 0, 'estadisticas' => 0, 'omitidos' => 0];
        $pdo = Database::conectar();
        $pdo->beginTransaction();

        try {
            // Quien no venga en el snapshot deja de estar en la plantilla activa.
            $deactivate = $pdo->prepare(
                'UPDATE tab_jugadores SET activo = 0 WHERE id_club = :id_club'
            );
            $deactivate->execute([':id_club' => $clubId]);

            foreach ($members as $member) {
                if (!is_array($member)) {
                    $stats['omitidos']++;
                    continue;
                }

                $gamertag = trim((string) ($member['name'] ?? ''));
                if ($gamertag === '') {
                    $stats['omitidos']++;
                    continue;
                }

                $playerResult = self::upsertPlayer($pdo, $clubId, $gamertag, $member);
                $stats[$playerResult['result']]++;
                self::upsertStats($pdo, $playerResult['id'], $member);
                $stats['estadisticas']++;
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return $stats;
    }

    public static function findAndLinkMatchPlayer(
        PDO $pdo,
        int $clubId,
        string $eaPlayerId,
        string $gamertag
    ): ?int {
        $stmt = $pdo->prepare(
            'SELECT id_jugador
             FROM tab_jugadores
             WHERE id_club = :id_club
               AND (ea_player_id = :ea_player_id OR gamertag = :gamertag)
             ORDER BY (ea_player_id = :ea_player_id_order) DESC
             LIMIT 1'
        );
        $stmt->execute([
            ':id_club' => $clubId,
            ':ea_player_id' => $eaPlayerId,
            ':gamertag' => $gamertag,
            ':ea_player_id_order' => $eaPlayerId,
        ]);
        $id = $stmt->fetchColumn();

        if ($id === false) {
            return null;
        }

        $update = $pdo->prepare(
            'UPDATE tab_jugadores
             SET ea_player_id = COALESCE(ea_player_id, :ea_player_id),
                 ultimo_avistamiento = CURRENT_TIMESTAMP
             WHERE id_jugador = :id_jugador'
        );
        $update->execute([
            ':ea_player_id' => $eaPlayerId,
            ':id_jugador' => (int) $id,
        ]);

        return (int) $id;
    }

    /** @return array{id:int,result:'insertados'|'actualizados'} */
    private static function upsertPlayer(PDO $pdo, int $clubId, string $gamertag, array $member): array
    {
        $gamertag = self::limit($gamertag, 100);
        $existing = $pdo->prepare(
            'SELECT id_jugador
             FROM tab_jugadores
             WHERE id_club = :id_club AND gamertag = :gamertag
             LIMIT 1'
        );
        $existing->execute([':id_club' => $clubId, ':gamertag' => $gamertag]);
        $existingId = $existing->fetchColumn();

        $sql = <<<'SQL'
INSERT INTO tab_jugadores (
    id_club, gamertag, pro_name, favorite_position, pro_position,
    nacionalidad_id, altura_cm, overall, activo, ultimo_avistamiento
) VALUES (
    :id_club, :gamertag, :pro_name, :favorite_position, :pro_position,
    :nacionalidad_id, :altura_cm, :overall, 1, CURRENT_TIMESTAMP
)
ON DUPLICATE KEY UPDATE
    pro_name = VALUES(pro_name),
    favorite_position = VALUES(favorite_position),
    pro_position = VALUES(pro_position),
    nacionalidad_id = VALUES(nacionalidad_id),
    altura_cm = VALUES(altura_cm),
    overall = VALUES(overall),
    activo = 1,
    ultimo_avistamiento = CURRENT_TIMESTAMP
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_club' => $clubId,
            ':gamertag' => $gamertag,
            ':pro_name' => self::nullableText($member['proName'] ?? null, 100),
            ':favorite_position' => self::nullableText($member['favoritePosition'] ?? null, 30),
            ':pro_position' => self::nullableText($member['proPos'] ?? null, 20),
            ':nacionalidad_id' => self::nullableInt($member['proNationality'] ?? null),
            ':altura_cm' => self::nullableInt($member['proHeight'] ?? null),
            ':overall' => self::nullableInt($member['proOverall'] ?? null),
        ]);

        if ($existingId === false) {
            $id = (int) $pdo->lastInsertId();
            return ['id' => $id, 'result' => 'insertados'];
        }

        return ['id' => (int) $existingId, 'result' => 'actualizados'];
    }

    private static function upsertStats(PDO $pdo, int $playerId, array $member): void
    {
        $sql = <<<'SQL'
INSERT INTO tab_jugador_estadisticas (
    id_jugador, partidos_jugados, win_rate, goles, asistencias,
    rating_promedio, man_of_the_match, tarjetas_rojas,
    porterias_defensa, porterias_portero, pases_completados,
    porcentaje_pases, tackles_completados, porcentaje_tackles,
    porcentaje_tiros, sincronizado_en
) VALUES (
    :id_jugador, :partidos_jugados, :win_rate, :goles, :asistencias,
    :rating_promedio, :man_of_the_match, :tarjetas_rojas,
    :porterias_defensa, :porterias_portero, :pases_completados,
    :porcentaje_pases, :tackles_completados, :porcentaje_tackles,
    :porcentaje_tiros, CURRENT_TIMESTAMP
)
ON DUPLICATE KEY UPDATE
    partidos_jugados = VALUES(partidos_jugados),
    win_rate = VALUES(win_rate),
    goles = VALUES(goles),
    asistencias = VALUES(asistencias),
    rating_promedio = VALUES(rating_promedio),
    man_of_the_match = VALUES(man_of_the_match),
    tarjetas_rojas = VALUES(tarjetas_rojas),
    porterias_defensa = VALUES(porterias_defensa),
    porterias_portero = VALUES(porterias_portero),
    pases_completados = VALUES(pases_completados),
    porcentaje_pases = VALUES(porcentaje_pases),
    tackles_completados = VALUES(tackles_completados),
    porcentaje_tackles = VALUES(porcentaje_tackles),
    porcentaje_tiros = VALUES(porcentaje_tiros),
    sincronizado_en = CURRENT_TIMESTAMP
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_jugador' => $playerId,
            ':partidos_jugados' => (int) ($member['gamesPlayed'] ?? 0),
            ':win_rate' => (float) ($member['winRate'] ?? 0),
            ':goles' => (int) ($member['goals'] ?? 0),
            ':asistencias' => (int) ($member['assists'] ?? 0),
            ':rating_promedio' => self::nullableFloat($member['ratingAve'] ?? null),
            ':man_of_the_match' => (int) ($member['manOfTheMatch'] ?? 0),
            ':tarjetas_rojas' => (int) ($member['redCards'] ?? 0),
            ':porterias_defensa' => (int) ($member['cleanSheetsDef'] ?? 0),
            ':porterias_portero' => (int) ($member['cleanSheetsGK'] ?? 0),
            ':pases_completados' => (int) ($member['passesMade'] ?? 0),
            ':porcentaje_pases' => (float) ($member['passSuccessRate'] ?? 0),
            ':tackles_completados' => (int) ($member['tacklesMade'] ?? 0),
            ':porcentaje_tackles' => (float) ($member['tackleSuccessRate'] ?? 0),
            ':porcentaje_tiros' => (float) ($member['shotSuccessRate'] ?? 0),
        ]);
    }

    private static function nullableText(mixed $value, int $length): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : self::limit($value, $length);
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
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

    private static function ranking(PDO $pdo, int $clubId, string $field, int|float $value): int
    {
        $allowed = ['goles', 'asistencias', 'rating_promedio'];
        if (!in_array($field, $allowed, true)) {
            return 0;
        }

        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM tab_jugador_estadisticas e
             INNER JOIN tab_jugadores j ON j.id_jugador = e.id_jugador
             WHERE j.id_club = :id_club AND j.activo = 1 AND e.{$field} > :valor"
        );
        $stmt->execute([
            ':id_club' => $clubId,
            ':valor' => $value,
        ]);

        return (int) $stmt->fetchColumn() + 1;
    }

    private static function slug(string $value): string
    {
        $ascii = function_exists('iconv')
            ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)
            : $value;
        $ascii = $ascii === false ? $value : $ascii;
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $ascii) ?? $ascii;

        return strtolower(trim($slug, '-'));
    }
}
