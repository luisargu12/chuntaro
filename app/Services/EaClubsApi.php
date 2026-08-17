<?php
namespace App\Services;

use App\Config\App;

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

    private function get(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/112.0',
                'Accept: application/json,text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: es-MX,es;q=0.9,en;q=0.8',
                'Referer: https://www.ea.com/',
            ],
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false || $code >= 400) {
            return [
                'ok' => false,
                'error' => $err !== '' ? $err : ("HTTP $code al consultar EA Clubs"),
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
