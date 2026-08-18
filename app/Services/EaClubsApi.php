<?php
namespace App\Services;

use App\Config\App;
use App\Models\Partido;

class EaClubsApi
{
    private string $baseUrl = 'https://proclubs.ea.com/api/fc/';
    private string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }
    }

    public function clubId(): string
    {
        return (string) App::env('EA_CLUB_ID', '2043111');
    }

    public function platform(): string
    {
        return (string) App::env('EA_PLATFORM', 'common-gen5');
    }

    public function importSyncedData(array $resources, int $matchLimit = 10): array
    {
        $stored = [];

        if (isset($resources['members']) && is_array($resources['members'])) {
            $this->writeCache(
                'members_stats_' . $this->clubId() . '_' . $this->platform(),
                $resources['members']
            );
            $stored[] = 'members';
        }

        if (isset($resources['clubInfo']) && is_array($resources['clubInfo'])) {
            $this->writeCache(
                'club_info_' . $this->clubId() . '_' . $this->platform(),
                $resources['clubInfo']
            );
            $stored[] = 'clubInfo';
        }

        $matchLimit = max(1, min($matchLimit, 20));
        $allowedTypes = ['leagueMatch', 'playoffMatch', 'friendlyMatch'];
        $partidoReports = [];
        $partidoWarning = null;

        foreach (($resources['matches'] ?? []) as $type => $matches) {
            if (!in_array($type, $allowedTypes, true) || !is_array($matches)) {
                continue;
            }

            $this->writeCache(
                sprintf(
                    'matches_%s_%s_%s_%d',
                    $this->clubId(),
                    $this->platform(),
                    $type,
                    $matchLimit
                ),
                $matches
            );
            $stored[] = 'matches.' . $type;

            try {
                $partidoReports[] = Partido::upsertFromEaList(
                    $matches,
                    (string) $type,
                    $this->clubId(),
                    $this->platform()
                );
            } catch (\Throwable $e) {
                error_log('EA sync DB: ' . $e->getMessage());
                $partidoWarning = 'Los partidos se cachearon, pero no se pudieron guardar en la base de datos: ' . $e->getMessage();
            }
        }

        return [
            'recursos' => $stored,
            'partidos' => Partido::mergeStats($partidoReports),
            'partidosWarning' => $partidoWarning,
        ];
    }

    /** Stats de miembros (plantilla) */
    public function memberStats(bool $forceRefresh = false): array
    {
        $cacheKey = 'members_stats_' . $this->clubId() . '_' . $this->platform();
        $ttl = (int) App::env('EA_CACHE_TTL', 300); // 5 min

        if (!$forceRefresh) {
            $cached = $this->readCache($cacheKey, $ttl);
            if ($cached !== null) {
                return ['ok' => true, 'cached' => true, 'data' => $cached];
            }
        }

        if (!$this->remoteFetchEnabled()) {
            return $this->staleCacheOrError($cacheKey);
        }

        $url = $this->baseUrl . 'members/stats?' . http_build_query([
            'platform' => $this->platform(),
            'clubId' => $this->clubId(),
        ]);

        $result = $this->get($url);
        if (!$result['ok']) {
            // Si falla la red, intenta cache viejo
            $stale = $this->readCache($cacheKey, PHP_INT_MAX);
            if ($stale !== null) {
                return ['ok' => true, 'cached' => true, 'stale' => true, 'data' => $stale, 'warning' => $result['error']];
            }
            return $result;
        }

        $this->writeCache($cacheKey, $result['data']);
        return ['ok' => true, 'cached' => false, 'data' => $result['data']];
    }

    public function clubInfo(bool $forceRefresh = false): array
    {
        $cacheKey = 'club_info_' . $this->clubId() . '_' . $this->platform();
        $ttl = (int) App::env('EA_CACHE_TTL', 300);

        if (!$forceRefresh) {
            $cached = $this->readCache($cacheKey, $ttl);
            if ($cached !== null) {
                return ['ok' => true, 'cached' => true, 'data' => $cached];
            }
        }

        if (!$this->remoteFetchEnabled()) {
            return $this->staleCacheOrError($cacheKey);
        }

        $url = $this->baseUrl . 'clubs/info?' . http_build_query([
            'platform' => $this->platform(),
            'clubIds' => $this->clubId(),
        ]);

        $result = $this->get($url);
        if (!$result['ok']) {
            $stale = $this->readCache($cacheKey, PHP_INT_MAX);
            if ($stale !== null) {
                return ['ok' => true, 'cached' => true, 'stale' => true, 'data' => $stale, 'warning' => $result['error']];
            }
            return $result;
        }

        $this->writeCache($cacheKey, $result['data']);
        return ['ok' => true, 'cached' => false, 'data' => $result['data']];
    }

    /** Últimos partidos del club (liga, playoffs o amistosos). */
    public function matches(
        string $matchType = 'leagueMatch',
        int $maxResults = 3,
        bool $forceRefresh = false
    ): array {
        $allowedTypes = ['leagueMatch', 'playoffMatch', 'friendlyMatch'];
        if (!in_array($matchType, $allowedTypes, true)) {
            return ['ok' => false, 'error' => 'Tipo de partido no válido', 'status' => 422];
        }

        $maxResults = max(1, min($maxResults, 20));
        $cacheKey = sprintf(
            'matches_%s_%s_%s_%d',
            $this->clubId(),
            $this->platform(),
            $matchType,
            $maxResults
        );
        $ttl = (int) App::env('EA_CACHE_TTL', 300);

        if (!$forceRefresh) {
            $cached = $this->readCache($cacheKey, $ttl);
            if ($cached !== null) {
                $this->persistMatchesQuietly($cached, $matchType);
                return ['ok' => true, 'cached' => true, 'data' => $cached];
            }
        }

        if (!$this->remoteFetchEnabled()) {
            return $this->staleCacheOrError($cacheKey);
        }

        $url = $this->baseUrl . 'clubs/matches?' . http_build_query([
            'platform' => $this->platform(),
            'clubIds' => $this->clubId(),
            'matchType' => $matchType,
            'maxResultCount' => $maxResults,
        ]);

        $result = $this->get($url);
        if (!$result['ok']) {
            $stale = $this->readCache($cacheKey, PHP_INT_MAX);
            if ($stale !== null) {
                return [
                    'ok' => true,
                    'cached' => true,
                    'stale' => true,
                    'data' => $stale,
                    'warning' => $result['error'],
                ];
            }
            return $result;
        }

        $this->writeCache($cacheKey, $result['data']);
        $this->persistMatchesQuietly($result['data'], $matchType);

        return ['ok' => true, 'cached' => false, 'data' => $result['data']];
    }

    /** Guarda en tab_partidos sin romper la consulta si falla la DB. */
    private function persistMatchesQuietly(array $matches, string $matchType): void
    {
        try {
            Partido::upsertFromEaList(
                $matches,
                $matchType,
                $this->clubId(),
                $this->platform()
            );
        } catch (\Throwable $e) {
            error_log('EA matches DB: ' . $e->getMessage());
        }
    }

    private function remoteFetchEnabled(): bool
    {
        return (bool) App::env('EA_REMOTE_FETCH_ENABLED', true);
    }

    private function staleCacheOrError(string $cacheKey): array
    {
        $stale = $this->readCache($cacheKey, PHP_INT_MAX);
        if ($stale !== null) {
            return ['ok' => true, 'cached' => true, 'stale' => true, 'data' => $stale];
        }

        return [
            'ok' => false,
            'error' => 'Todavía no hay datos sincronizados de EA Clubs',
            'status' => 503,
        ];
    }

    private function get(string $url): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'cURL no está habilitado en PHP', 'status' => 0];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_ENCODING => '', // acepta gzip
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: es-MX,es;q=0.9,en;q=0.8',
                'Origin: https://www.ea.com',
                'Referer: https://www.ea.com/',
                'Connection: close',
            ],
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return [
                'ok' => false,
                'error' => $err !== '' ? $err : 'Fallo cURL sin cuerpo',
                'status' => $code,
            ];
        }

        if ($code >= 400) {
            $snippet = trim(strip_tags(substr($body, 0, 180)));
            return [
                'ok' => false,
                'error' => "EA respondió HTTP $code" . ($snippet !== '' ? ": $snippet" : ''),
                'status' => $code,
            ];
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            return ['ok' => false, 'error' => 'Respuesta inválida de EA Clubs', 'status' => $code];
        }

        return ['ok' => true, 'data' => $json, 'status' => $code];
    }

    private function cachePath(string $key): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key) . '.json';
    }

    private function readCache(string $key, int $ttl): ?array
    {
        $path = $this->cachePath($key);
        if (!is_file($path)) {
            return null;
        }
        if ($ttl < PHP_INT_MAX && (time() - filemtime($path)) > $ttl) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    private function writeCache(string $key, array $data): void
    {
        file_put_contents($this->cachePath($key), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
