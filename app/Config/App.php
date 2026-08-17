<?php
namespace App\Config;

use Dotenv\Dotenv;

class App
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $root = dirname(__DIR__, 2);
        $dotenv = Dotenv::createImmutable($root);
        $dotenv->safeLoad();
        self::$loaded = true;
    }

    public static function env(string $key, mixed $default = null): mixed
    {
        self::load();
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        if (is_string($value)) {
            $lower = strtolower($value);
            if ($lower === 'true') {
                return true;
            }
            if ($lower === 'false') {
                return false;
            }
            if ($lower === 'null' || $value === '') {
                return $default;
            }
        }
        return $value;
    }

    public static function name(): string
    {
        return (string) self::env('APP_NAME', 'Chuntaro');
    }

    public static function envName(): string
    {
        return (string) self::env('APP_ENV', 'local');
    }

    public static function isProduction(): bool
    {
        return self::envName() === 'production';
    }

    public static function debug(): bool
    {
        return (bool) self::env('APP_DEBUG', !self::isProduction());
    }

    /** Prefijo de URL (ej. /chuntaro en XAMPP; vacío en dominio raíz) */
    public static function basePath(): string
    {
        $base = (string) self::env('BASE_PATH', '');
        $base = rtrim($base, '/');
        return $base === '/' ? '' : $base;
    }

    public static function url(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            return self::basePath() === '' ? '/' : self::basePath() . '/';
        }
        return self::basePath() . $path;
    }

    public static function asset(string $path): string
    {
        return self::url(ltrim($path, '/'));
    }
}
