<?php
namespace App\Models;

use App\Config\Database;
use RuntimeException;

class ClubEstadistica
{
    /**
     * @return array{guardados:int,omitidos:int}
     */
    public static function upsertOverallStats(array $data, string $principalClubId): array
    {
        $stats = ['guardados' => 0, 'omitidos' => 0];
        $pdo = Database::conectar();

        $sql = <<<'SQL'
INSERT INTO tab_club_estadisticas (
    id_club, partidos_jugados, apariciones_liga, partidos_playoff,
    victorias, empates, derrotas, goles_favor, goles_contra,
    skill_rating, mejor_division, mejor_grupo_final, ascensos,
    descensos, nivel_reputacion, racha_invicto, racha_victorias,
    finales_division_1_grupo_1, finales_division_2_grupo_1,
    finales_division_3_grupo_1, finales_division_4_grupo_1,
    finales_division_5_grupo_1, finales_division_6_grupo_1,
    payload_json, sincronizado_en
) VALUES (
    :id_club, :partidos_jugados, :apariciones_liga, :partidos_playoff,
    :victorias, :empates, :derrotas, :goles_favor, :goles_contra,
    :skill_rating, :mejor_division, :mejor_grupo_final, :ascensos,
    :descensos, :nivel_reputacion, :racha_invicto, :racha_victorias,
    :finales_division_1_grupo_1, :finales_division_2_grupo_1,
    :finales_division_3_grupo_1, :finales_division_4_grupo_1,
    :finales_division_5_grupo_1, :finales_division_6_grupo_1,
    :payload_json, CURRENT_TIMESTAMP
)
ON DUPLICATE KEY UPDATE
    partidos_jugados = VALUES(partidos_jugados),
    apariciones_liga = VALUES(apariciones_liga),
    partidos_playoff = VALUES(partidos_playoff),
    victorias = VALUES(victorias),
    empates = VALUES(empates),
    derrotas = VALUES(derrotas),
    goles_favor = VALUES(goles_favor),
    goles_contra = VALUES(goles_contra),
    skill_rating = VALUES(skill_rating),
    mejor_division = VALUES(mejor_division),
    mejor_grupo_final = VALUES(mejor_grupo_final),
    ascensos = VALUES(ascensos),
    descensos = VALUES(descensos),
    nivel_reputacion = VALUES(nivel_reputacion),
    racha_invicto = VALUES(racha_invicto),
    racha_victorias = VALUES(racha_victorias),
    finales_division_1_grupo_1 = VALUES(finales_division_1_grupo_1),
    finales_division_2_grupo_1 = VALUES(finales_division_2_grupo_1),
    finales_division_3_grupo_1 = VALUES(finales_division_3_grupo_1),
    finales_division_4_grupo_1 = VALUES(finales_division_4_grupo_1),
    finales_division_5_grupo_1 = VALUES(finales_division_5_grupo_1),
    finales_division_6_grupo_1 = VALUES(finales_division_6_grupo_1),
    payload_json = VALUES(payload_json),
    sincronizado_en = CURRENT_TIMESTAMP
SQL;
        $stmt = $pdo->prepare($sql);

        foreach ($data as $row) {
            if (!is_array($row)) {
                $stats['omitidos']++;
                continue;
            }

            $eaClubId = trim((string) ($row['clubId'] ?? $principalClubId));
            $clubId = Club::idByEaId($eaClubId);
            if ($clubId === null) {
                $stats['omitidos']++;
                continue;
            }

            $payload = json_encode(
                $row,
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
            $stmt->execute([
                ':id_club' => $clubId,
                ':partidos_jugados' => (int) ($row['gamesPlayed'] ?? 0),
                ':apariciones_liga' => (int) ($row['leagueAppearances'] ?? 0),
                ':partidos_playoff' => (int) ($row['gamesPlayedPlayoff'] ?? 0),
                ':victorias' => (int) ($row['wins'] ?? 0),
                ':empates' => (int) ($row['ties'] ?? 0),
                ':derrotas' => (int) ($row['losses'] ?? 0),
                ':goles_favor' => (int) ($row['goals'] ?? 0),
                ':goles_contra' => (int) ($row['goalsAgainst'] ?? 0),
                ':skill_rating' => (int) ($row['skillRating'] ?? 0),
                ':mejor_division' => self::nullableInt($row['bestDivision'] ?? null),
                ':mejor_grupo_final' => self::nullableInt($row['bestFinishGroup'] ?? null),
                ':ascensos' => (int) ($row['promotions'] ?? 0),
                ':descensos' => (int) ($row['relegations'] ?? 0),
                ':nivel_reputacion' => self::nullableInt($row['reputationtier'] ?? null),
                ':racha_invicto' => (int) ($row['unbeatenstreak'] ?? 0),
                ':racha_victorias' => (int) ($row['wstreak'] ?? 0),
                ':finales_division_1_grupo_1' => (int) ($row['finishesInDivision1Group1'] ?? 0),
                ':finales_division_2_grupo_1' => (int) ($row['finishesInDivision2Group1'] ?? 0),
                ':finales_division_3_grupo_1' => (int) ($row['finishesInDivision3Group1'] ?? 0),
                ':finales_division_4_grupo_1' => (int) ($row['finishesInDivision4Group1'] ?? 0),
                ':finales_division_5_grupo_1' => (int) ($row['finishesInDivision5Group1'] ?? 0),
                ':finales_division_6_grupo_1' => (int) ($row['finishesInDivision6Group1'] ?? 0),
                ':payload_json' => $payload === false ? null : $payload,
            ]);
            $stats['guardados']++;
        }

        return $stats;
    }

    /**
     * @return array{guardados:int,omitidos:int}
     */
    public static function upsertPlayoffAchievements(
        array $data,
        string $principalClubId
    ): array {
        $clubId = Club::idByEaId($principalClubId);
        if ($clubId === null) {
            throw new RuntimeException("No existe tab_clubes.ea_club_id={$principalClubId}");
        }

        $stats = ['guardados' => 0, 'omitidos' => 0];
        $pdo = Database::conectar();
        $sql = <<<'SQL'
INSERT INTO tab_club_logros_playoff (
    id_club, season_id, season_name, mejor_division,
    mejor_grupo_final, payload_json, sincronizado_en
) VALUES (
    :id_club, :season_id, :season_name, :mejor_division,
    :mejor_grupo_final, :payload_json, CURRENT_TIMESTAMP
)
ON DUPLICATE KEY UPDATE
    season_name = VALUES(season_name),
    mejor_division = VALUES(mejor_division),
    mejor_grupo_final = VALUES(mejor_grupo_final),
    payload_json = VALUES(payload_json),
    sincronizado_en = CURRENT_TIMESTAMP
SQL;
        $stmt = $pdo->prepare($sql);

        foreach ($data as $row) {
            if (!is_array($row)) {
                $stats['omitidos']++;
                continue;
            }

            $seasonId = trim((string) ($row['seasonId'] ?? ''));
            if ($seasonId === '') {
                $stats['omitidos']++;
                continue;
            }

            $payload = json_encode(
                $row,
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
            $stmt->execute([
                ':id_club' => $clubId,
                ':season_id' => $seasonId,
                ':season_name' => self::nullableText($row['seasonName'] ?? null, 100),
                ':mejor_division' => self::nullableInt($row['bestDivision'] ?? null),
                ':mejor_grupo_final' => self::nullableInt($row['bestFinishGroup'] ?? null),
                ':payload_json' => $payload === false ? null : $payload,
            ]);
            $stats['guardados']++;
        }

        return $stats;
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private static function nullableText(mixed $value, int $length): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $length)
            : substr($value, 0, $length);
    }
}
