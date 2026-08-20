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

}
