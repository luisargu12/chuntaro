<?php
namespace App\Models;

use App\Config\Database;

class Partido
{
    private const TIPOS = ['leagueMatch', 'playoffMatch', 'friendlyMatch'];

    /**
     * Devuelve partidos desde MySQL con la estructura que consume el front.
     *
     * @return list<array<string,mixed>>
     */
    public static function latestForPublic(
        string $tipo,
        int $limit,
        string $principalClubId
    ): array {
        if (!in_array($tipo, self::TIPOS, true)) {
            return [];
        }

        $limit = max(1, min($limit, 20));
        $pdo = Database::conectar();
        $stmt = $pdo->prepare(
            'SELECT
                p.match_id, p.timestamp_ea, p.jugado_en, p.tipo,
                p.club_id, p.rival_club_id, p.rival_nombre,
                p.goles_favor, p.goles_contra, p.resultado,
                principal.nombre AS principal_nombre,
                principal.crest_asset_id AS principal_crest_asset_id,
                rival.nombre AS rival_nombre_catalogo,
                rival.crest_asset_id AS rival_crest_asset_id
             FROM tab_partidos p
             LEFT JOIN tab_clubes principal ON principal.ea_club_id = p.club_id
             LEFT JOIN tab_clubes rival ON rival.ea_club_id = p.rival_club_id
             WHERE p.tipo = :tipo AND p.club_id = :club_id
             ORDER BY p.timestamp_ea DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':club_id', $principalClubId);
        $stmt->bindValue(':limite', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $matches = [];
        foreach ($stmt->fetchAll() as $row) {
            $ownClubId = (string) $row['club_id'];
            $rivalClubId = (string) $row['rival_club_id'];
            $matches[] = [
                'matchId' => (string) $row['match_id'],
                'timestamp' => (int) $row['timestamp_ea'],
                'playedAt' => $row['jugado_en'],
                'type' => $row['tipo'],
                'result' => $row['resultado'],
                'clubs' => [
                    $ownClubId => self::publicClub(
                        (string) ($row['principal_nombre'] ?: 'Chuntaro FC'),
                        (int) $row['goles_favor'],
                        $row['principal_crest_asset_id']
                    ),
                    $rivalClubId => self::publicClub(
                        (string) ($row['rival_nombre_catalogo'] ?: $row['rival_nombre']),
                        (int) $row['goles_contra'],
                        $row['rival_crest_asset_id']
                    ),
                ],
            ];
        }

        return $matches;
    }

    /**
     * Historial de partidos para la vista pública, ordenado del más reciente.
     *
     * @return list<array<string,mixed>>
     */
    public static function historyForPublic(string $principalClubId, int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));
        $pdo = Database::conectar();
        $stmt = $pdo->prepare(
            'SELECT
                p.match_id, p.timestamp_ea, p.jugado_en, p.tipo,
                p.club_id, p.rival_club_id, p.rival_nombre,
                p.goles_favor, p.goles_contra, p.resultado,
                principal.nombre AS principal_nombre,
                principal.crest_asset_id AS principal_crest_asset_id,
                rival.nombre AS rival_nombre_catalogo,
                rival.crest_asset_id AS rival_crest_asset_id
             FROM tab_partidos p
             LEFT JOIN tab_clubes principal ON principal.ea_club_id = p.club_id
             LEFT JOIN tab_clubes rival ON rival.ea_club_id = p.rival_club_id
             WHERE p.club_id = :club_id
             ORDER BY p.timestamp_ea DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':club_id', $principalClubId);
        $stmt->bindValue(':limite', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $matches = [];
        foreach ($stmt->fetchAll() as $row) {
            $matches[] = self::publicMatchFromRow($row);
        }

        return $matches;
    }

    /**
     * Estadísticas generales e individuales de un partido.
     *
     * @return array<string,mixed>|null
     */
    public static function detailForPublic(string $matchId, string $principalClubId): ?array
    {
        $pdo = Database::conectar();
        $matchStmt = $pdo->prepare(
            'SELECT id_partido, match_id, jugado_en, tipo, club_id,
                    rival_club_id, rival_nombre, goles_favor, goles_contra,
                    resultado
             FROM tab_partidos
             WHERE match_id = :match_id AND club_id = :club_id
             LIMIT 1'
        );
        $matchStmt->execute([
            ':match_id' => $matchId,
            ':club_id' => $principalClubId,
        ]);
        $match = $matchStmt->fetch();
        if ($match === false) {
            return null;
        }

        $teamStmt = $pdo->prepare(
            'SELECT
                e.ea_club_id, e.es_principal, e.jugadores_contados,
                e.goles, e.asistencias, e.tiros, e.pases_intentados,
                e.pases_completados, e.tackles_intentados,
                e.tackles_completados, e.atajadas, e.tarjetas_rojas,
                e.porterias_cero, e.man_of_the_match, e.rating_promedio,
                e.segundos_jugados, c.nombre, c.crest_asset_id
             FROM tab_partido_estadisticas e
             LEFT JOIN tab_clubes c ON c.id_club = e.id_club
             WHERE e.id_partido = :id_partido
             ORDER BY e.es_principal DESC'
        );
        $teamStmt->execute([':id_partido' => (int) $match['id_partido']]);

        $playerStmt = $pdo->prepare(
            'SELECT
                pj.ea_player_id, pj.gamertag, pj.posicion, pj.rating,
                pj.goles, pj.asistencias, pj.tiros, pj.pases_intentados,
                pj.pases_completados, pj.tackles_intentados,
                pj.tackles_completados, pj.atajadas, pj.tarjetas_rojas,
                pj.man_of_the_match, j.pro_name, j.favorite_position
             FROM tab_partido_jugadores pj
             LEFT JOIN tab_jugadores j ON j.id_jugador = pj.id_jugador
             WHERE pj.id_partido = :id_partido
             ORDER BY pj.rating DESC, pj.goles DESC, pj.gamertag ASC'
        );
        $playerStmt->execute([':id_partido' => (int) $match['id_partido']]);

        return [
            'match' => [
                'matchId' => (string) $match['match_id'],
                'playedAt' => $match['jugado_en'],
                'type' => $match['tipo'],
                'result' => $match['resultado'],
                'clubId' => (string) $match['club_id'],
                'rivalClubId' => (string) $match['rival_club_id'],
                'rivalName' => $match['rival_nombre'],
                'goalsFor' => (int) $match['goles_favor'],
                'goalsAgainst' => (int) $match['goles_contra'],
            ],
            'teams' => array_map(
                static fn (array $row): array => [
                    'clubId' => (string) $row['ea_club_id'],
                    'name' => $row['nombre'] ?: (
                        (int) $row['es_principal'] === 1
                            ? 'Chuntaro FC'
                            : $match['rival_nombre']
                    ),
                    'crestAssetId' => $row['crest_asset_id'],
                    'isPrincipal' => (bool) $row['es_principal'],
                    'players' => (int) $row['jugadores_contados'],
                    'goals' => (int) $row['goles'],
                    'assists' => (int) $row['asistencias'],
                    'shots' => (int) $row['tiros'],
                    'passAttempts' => (int) $row['pases_intentados'],
                    'passesCompleted' => (int) $row['pases_completados'],
                    'tackleAttempts' => (int) $row['tackles_intentados'],
                    'tacklesCompleted' => (int) $row['tackles_completados'],
                    'saves' => (int) $row['atajadas'],
                    'redCards' => (int) $row['tarjetas_rojas'],
                    'cleanSheets' => (int) $row['porterias_cero'],
                    'rating' => $row['rating_promedio'] === null
                        ? null
                        : (float) $row['rating_promedio'],
                ],
                $teamStmt->fetchAll()
            ),
            'players' => array_map(
                static fn (array $row): array => [
                    'playerId' => $row['ea_player_id'],
                    'gamertag' => $row['gamertag'],
                    'proName' => $row['pro_name'],
                    'position' => $row['posicion'] ?: $row['favorite_position'],
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
                $playerStmt->fetchAll()
            ),
        ];
    }

    /**
     * Inserta o actualiza una lista de partidos de EA.
     *
     * @return array{insertados:int,actualizados:int,sin_cambios:int,omitidos:int}
     */
    public static function upsertFromEaList(
        array $matches,
        string $tipo,
        string $clubId,
        string $platform
    ): array {
        $stats = [
            'insertados' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'omitidos' => 0,
        ];

        if (!in_array($tipo, self::TIPOS, true)) {
            $stats['omitidos'] = count($matches);
            return $stats;
        }

        foreach (array_values($matches) as $match) {
            if (!is_array($match)) {
                $stats['omitidos']++;
                continue;
            }

            $result = self::upsertFromEa($match, $tipo, $clubId, $platform);
            $stats[$result]++;
        }

        return $stats;
    }

    /** @return 'insertados'|'actualizados'|'sin_cambios'|'omitidos' */
    public static function upsertFromEa(
        array $match,
        string $tipo,
        string $clubId,
        string $platform
    ): string {
        $row = self::normalize($match, $tipo, $clubId, $platform);
        if ($row === null) {
            return 'omitidos';
        }

        $sql = <<<SQL
INSERT INTO tab_partidos (
    match_id, club_id, plataforma, tipo, timestamp_ea, jugado_en,
    rival_club_id, rival_nombre, goles_favor, goles_contra,
    resultado, season_id, payload_json
) VALUES (
    :match_id, :club_id, :plataforma, :tipo, :timestamp_ea, :jugado_en,
    :rival_club_id, :rival_nombre, :goles_favor, :goles_contra,
    :resultado, :season_id, :payload_json
)
ON DUPLICATE KEY UPDATE
    club_id = VALUES(club_id),
    plataforma = VALUES(plataforma),
    tipo = VALUES(tipo),
    timestamp_ea = VALUES(timestamp_ea),
    jugado_en = VALUES(jugado_en),
    rival_club_id = VALUES(rival_club_id),
    rival_nombre = VALUES(rival_nombre),
    goles_favor = VALUES(goles_favor),
    goles_contra = VALUES(goles_contra),
    resultado = VALUES(resultado),
    season_id = VALUES(season_id),
    payload_json = VALUES(payload_json),
    sincronizado_en = CURRENT_TIMESTAMP
SQL;

        $pdo = Database::conectar();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($row);

        return match ($stmt->rowCount()) {
            1 => 'insertados',
            2 => 'actualizados',
            default => 'sin_cambios',
        };
    }

    /**
     * Suma varios reportes de upsert (liga + playoff + amistoso).
     *
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

    /**
     * @return array<string, mixed>|null
     */
    public static function normalize(
        array $match,
        string $tipo,
        string $clubId,
        string $platform
    ): ?array {
        if (!in_array($tipo, self::TIPOS, true)) {
            return null;
        }

        $matchId = trim((string) ($match['matchId'] ?? ''));
        if ($matchId === '') {
            return null;
        }

        $timestamp = (int) ($match['timestamp'] ?? 0);
        if ($timestamp <= 0) {
            return null;
        }

        if ($timestamp > 1000000000000) {
            $timestamp = (int) floor($timestamp / 1000);
        }

        $clubs = $match['clubs'] ?? [];
        if (!is_array($clubs) || $clubs === []) {
            return null;
        }

        $ownClub = null;
        $rivalClub = null;
        $rivalClubId = '';

        foreach ($clubs as $id => $club) {
            if (!is_array($club)) {
                continue;
            }
            if ((string) $id === (string) $clubId) {
                $ownClub = $club;
                continue;
            }
            $rivalClub = $club;
            $rivalClubId = (string) $id;
        }

        if ($ownClub === null || $rivalClub === null || $rivalClubId === '') {
            return null;
        }

        $golesFavor = self::scoreValue($ownClub);
        $golesContra = self::scoreValue($rivalClub);
        $resultado = 'empate';
        if ($golesFavor > $golesContra) {
            $resultado = 'victoria';
        } elseif ($golesFavor < $golesContra) {
            $resultado = 'derrota';
        }

        $rivalNombre = trim((string) (
            $rivalClub['details']['name']
            ?? $rivalClub['name']
            ?? 'Equipo rival'
        ));
        if ($rivalNombre === '') {
            $rivalNombre = 'Equipo rival';
        }

        $seasonId = $ownClub['season_id'] ?? $ownClub['seasonId'] ?? null;
        $seasonId = $seasonId === null || $seasonId === '' ? null : (string) $seasonId;

        $payload = json_encode($match, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($payload === false) {
            return null;
        }

        return [
            ':match_id' => $matchId,
            ':club_id' => (string) $clubId,
            ':plataforma' => $platform,
            ':tipo' => $tipo,
            ':timestamp_ea' => $timestamp,
            ':jugado_en' => gmdate('Y-m-d H:i:s', $timestamp),
            ':rival_club_id' => $rivalClubId,
            ':rival_nombre' => function_exists('mb_substr')
                ? mb_substr($rivalNombre, 0, 120)
                : substr($rivalNombre, 0, 120),
            ':goles_favor' => $golesFavor,
            ':goles_contra' => $golesContra,
            ':resultado' => $resultado,
            ':season_id' => $seasonId,
            ':payload_json' => $payload,
        ];
    }

    private static function scoreValue(array $club): int
    {
        return (int) ($club['goals'] ?? $club['score'] ?? 0);
    }

    /** @return array<string,mixed> */
    private static function publicClub(string $name, int $goals, mixed $crestAssetId): array
    {
        $crestAssetId = trim((string) ($crestAssetId ?? ''));

        return [
            'goals' => $goals,
            'score' => $goals,
            'TEAM' => $crestAssetId,
            'details' => [
                'name' => $name,
                'customKit' => [
                    'crestAssetId' => $crestAssetId,
                ],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private static function publicMatchFromRow(array $row): array
    {
        $ownClubId = (string) $row['club_id'];
        $rivalClubId = (string) $row['rival_club_id'];

        return [
            'matchId' => (string) $row['match_id'],
            'timestamp' => (int) $row['timestamp_ea'],
            'playedAt' => $row['jugado_en'],
            'type' => $row['tipo'],
            'result' => $row['resultado'],
            'clubs' => [
                $ownClubId => self::publicClub(
                    (string) ($row['principal_nombre'] ?: 'Chuntaro FC'),
                    (int) $row['goles_favor'],
                    $row['principal_crest_asset_id']
                ),
                $rivalClubId => self::publicClub(
                    (string) ($row['rival_nombre_catalogo'] ?: $row['rival_nombre']),
                    (int) $row['goles_contra'],
                    $row['rival_crest_asset_id']
                ),
            ],
        ];
    }

}
