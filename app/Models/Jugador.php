<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use RuntimeException;

class Jugador
{
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
}
