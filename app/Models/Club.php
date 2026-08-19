<?php
namespace App\Models;

use App\Config\App;
use App\Config\Database;

class Club
{
    /**
     * Guarda la respuesta de /clubs/info.
     *
     * @return array{guardados:int,omitidos:int}
     */
    public static function upsertFromClubInfo(array $clubInfo, string $principalClubId): array
    {
        $stats = ['guardados' => 0, 'omitidos' => 0];

        foreach ($clubInfo as $key => $details) {
            if (!is_array($details)) {
                $stats['omitidos']++;
                continue;
            }

            $clubId = trim((string) ($details['clubId'] ?? $key));
            if ($clubId === '') {
                $stats['omitidos']++;
                continue;
            }

            self::upsert($clubId, $details, $clubId === $principalClubId);
            $stats['guardados']++;
        }

        return $stats;
    }

    /**
     * Extrae y guarda ambos clubes presentes en cada partido.
     *
     * @return array{guardados:int,omitidos:int}
     */
    public static function upsertFromMatches(array $matches, string $principalClubId): array
    {
        $stats = ['guardados' => 0, 'omitidos' => 0];
        $processed = [];

        foreach ($matches as $match) {
            $clubs = is_array($match) ? ($match['clubs'] ?? null) : null;
            if (!is_array($clubs)) {
                $stats['omitidos']++;
                continue;
            }

            foreach ($clubs as $clubId => $club) {
                $clubId = trim((string) $clubId);
                if ($clubId === '' || !is_array($club) || isset($processed[$clubId])) {
                    continue;
                }

                $details = is_array($club['details'] ?? null)
                    ? $club['details']
                    : $club;

                self::upsert($clubId, $details, $clubId === $principalClubId);
                $processed[$clubId] = true;
                $stats['guardados']++;
            }
        }

        return $stats;
    }

    private static function upsert(string $clubId, array $details, bool $isPrincipal): void
    {
        $customKit = is_array($details['customKit'] ?? null)
            ? $details['customKit']
            : [];

        $crestAssetId = trim((string) (
            $customKit['crestAssetId']
            ?? $details['crestAssetId']
            ?? ''
        ));

        $name = trim((string) ($details['name'] ?? 'Equipo rival'));
        if ($name === '') {
            $name = 'Equipo rival';
        }

        $stadium = trim((string) (
            $customKit['stadName']
            ?? $details['stadName']
            ?? ''
        ));

        $kitJson = $customKit === []
            ? null
            : json_encode(
                $customKit,
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );

        $sql = <<<'SQL'
INSERT INTO tab_clubes (
    ea_club_id, nombre, crest_asset_id, escudo_url, estadio_nombre,
    kit_json, es_principal, sincronizado_en
) VALUES (
    :ea_club_id, :nombre, :crest_asset_id, :escudo_url, :estadio_nombre,
    :kit_json, :es_principal, CURRENT_TIMESTAMP
)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    crest_asset_id = COALESCE(VALUES(crest_asset_id), crest_asset_id),
    escudo_url = COALESCE(VALUES(escudo_url), escudo_url),
    estadio_nombre = COALESCE(VALUES(estadio_nombre), estadio_nombre),
    kit_json = COALESCE(VALUES(kit_json), kit_json),
    es_principal = GREATEST(es_principal, VALUES(es_principal)),
    activo = 1,
    sincronizado_en = CURRENT_TIMESTAMP
SQL;

        $pdo = Database::conectar();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ea_club_id' => $clubId,
            ':nombre' => self::limit($name, 120),
            ':crest_asset_id' => $crestAssetId !== '' ? self::limit($crestAssetId, 60) : null,
            ':escudo_url' => self::crestUrl($crestAssetId),
            ':estadio_nombre' => $stadium !== '' ? self::limit($stadium, 120) : null,
            ':kit_json' => $kitJson,
            ':es_principal' => $isPrincipal ? 1 : 0,
        ]);
    }

    private static function crestUrl(string $crestAssetId): ?string
    {
        if ($crestAssetId === '' || !preg_match('/^\d+$/', $crestAssetId)) {
            return null;
        }

        $base = rtrim((string) App::env(
            'EA_CREST_CDN_BASE',
            'https://eafc26.content.easports.com/fc/fltOnlineAssets/26E4D4D6-8DBB-4A9A-BD99-9C47D3AA341D/2026/fcweb/crests/256x256'
        ), '/');

        return $base . '/l' . $crestAssetId . '.png';
    }

    private static function limit(string $value, int $length): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, 0, $length)
            : substr($value, 0, $length);
    }
}
