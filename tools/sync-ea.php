<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\App;

App::load();

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo puede ejecutarse desde la consola.\n");
    exit(1);
}

$clubId = (string) App::env('EA_CLUB_ID', '');
$platform = (string) App::env('EA_PLATFORM', 'common-gen5');
$syncUrl = (string) App::env('EA_SYNC_URL', '');
$syncToken = (string) App::env('EA_SYNC_TOKEN', '');
$matchLimit = 3;

if ($clubId === '' || $syncUrl === '' || strlen($syncToken) < 32) {
    fwrite(
        STDERR,
        "Configura EA_CLUB_ID, EA_SYNC_URL y un EA_SYNC_TOKEN de al menos 32 caracteres en .env.\n"
    );
    exit(1);
}

$eaBase = 'https://proclubs.ea.com/api/fc/';
$requests = [
    'members' => $eaBase . 'members/stats?' . http_build_query([
        'platform' => $platform,
        'clubId' => $clubId,
    ]),
    'clubInfo' => $eaBase . 'clubs/info?' . http_build_query([
        'platform' => $platform,
        'clubIds' => $clubId,
    ]),
];

foreach (['leagueMatch', 'playoffMatch', 'friendlyMatch'] as $type) {
    $requests['matches.' . $type] = $eaBase . 'clubs/matches?' . http_build_query([
        'platform' => $platform,
        'clubIds' => $clubId,
        'matchType' => $type,
        'maxResultCount' => $matchLimit,
    ]);
}

$resources = ['matches' => []];
$errors = [];

foreach ($requests as $name => $url) {
    fwrite(STDOUT, "Consultando {$name}... ");
    $result = fetchJson($url);

    if (!$result['ok']) {
        fwrite(STDOUT, "ERROR ({$result['error']})\n");
        $errors[$name] = $result['error'];
        continue;
    }

    fwrite(STDOUT, "OK\n");
    if (str_starts_with($name, 'matches.')) {
        $type = substr($name, strlen('matches.'));
        $resources['matches'][$type] = $result['data'];
    } else {
        $resources[$name] = $result['data'];
    }
}

if (count($resources) === 1 && $resources['matches'] === []) {
    fwrite(STDERR, "EA no devolvió ningún recurso. No se enviaron datos a producción.\n");
    exit(1);
}

$payload = [
    'clubId' => $clubId,
    'platform' => $platform,
    'matchLimit' => $matchLimit,
    'syncedAt' => gmdate(DATE_ATOM),
    'resources' => $resources,
];

fwrite(STDOUT, "Enviando datos a {$syncUrl}... ");
$upload = postJson($syncUrl, $payload, $syncToken);

if (!$upload['ok']) {
    fwrite(STDOUT, "ERROR\n");
    fwrite(STDERR, $upload['error'] . "\n");
    exit(1);
}

fwrite(STDOUT, "OK\n");
fwrite(STDOUT, json_encode($upload['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");

if ($errors !== []) {
    fwrite(STDOUT, "Aviso: algunos recursos no se sincronizaron:\n");
    foreach ($errors as $resource => $error) {
        fwrite(STDOUT, "- {$resource}: {$error}\n");
    }
}

function fetchJson(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
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
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false || $status >= 400) {
        return [
            'ok' => false,
            'error' => $error !== '' ? $error : "EA HTTP {$status}",
        ];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'EA no devolvió JSON válido'];
    }

    return ['ok' => true, 'data' => $data];
}

function postJson(string $url, array $payload, string $token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Sync-Token: ' . $token,
        ],
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => $error ?: 'No se pudo contactar el endpoint de sincronización'];
    }

    $data = json_decode($body, true);
    if ($status >= 400 || !is_array($data) || !($data['exito'] ?? false)) {
        $message = is_array($data) ? ($data['mensaje'] ?? "HTTP {$status}") : "HTTP {$status}: {$body}";
        return ['ok' => false, 'error' => $message];
    }

    return ['ok' => true, 'data' => $data];
}
