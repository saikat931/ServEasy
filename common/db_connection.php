<?php
/**
 * Standalone Database Connection Helper for Split-Domain Customer Web Portal
 * Checks .env / host.env / db.env (inside public_html or one folder above) and environment variables.
 * Returns PDO instance if configured, or null cleanly (never throws unhandled fatal errors).
 */
date_default_timezone_set('Asia/Kolkata');

if (!class_exists('ServEasyWebDB')) {
    class ServEasyWebDB
    {
        private static ?PDO $pdo = null;
        private static bool $attempted = false;

        public static function connect(): ?PDO
        {
            if (self::$attempted) {
                return self::$pdo;
            }
            self::$attempted = true;

            if (class_exists('App\Helpers\DB')) {
                try {
                    self::$pdo = \App\Helpers\DB::getConnection();
                    return self::$pdo;
                } catch (\Throwable $e) {}
            }

            $vars = [];
            $rootDir = dirname(__DIR__);
            $parentDir = dirname($rootDir);
            $envFiles = [
                $rootDir . '/.env',
                $rootDir . '/host.env',
                $rootDir . '/db.env',
                $parentDir . '/.env',
                $parentDir . '/host.env',
                $parentDir . '/db.env',
            ];
            foreach ($envFiles as $envFile) {
                if (!@file_exists($envFile) || !@is_readable($envFile)) continue;
                $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (!is_array($lines)) continue;
                foreach ($lines as $line) {
                    $trimmed = ltrim(trim($line), "\xEF\xBB\xBF");
                    if ($trimmed === '' || str_starts_with($trimmed, '#')) continue;
                    if (str_contains($trimmed, '=')) {
                        [$k, $v] = explode('=', $trimmed, 2);
                        $key = ltrim(trim($k), "\xEF\xBB\xBF");
                        $val = trim($v, " \t\n\r\0\x0B\"'");
                        if ($key !== '' && (!isset($vars[$key]) || $vars[$key] === '')) {
                            $vars[$key] = $val;
                        }
                    }
                }
            }

            $get = function (array $keys, string $default = '') use ($vars): string {
                foreach ($keys as $k) {
                    if (!empty($vars[$k])) return (string)$vars[$k];
                    if (!empty($_ENV[$k])) return (string)$_ENV[$k];
                    if (!empty($_SERVER[$k])) return (string)$_SERVER[$k];
                    $g = getenv($k);
                    if ($g !== false && $g !== '') return (string)$g;
                }
                return $default;
            };

            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1']) || str_starts_with($host, 'localhost:');

            $dbHost = $get(['DB_HOST'], $isLocal ? '127.0.0.1' : 'localhost');
            $dbPort = $get(['DB_PORT'], '3306');
            $dbName = $get(['DB_DATABASE', 'DB_NAME'], $isLocal ? 'serveasy_v2' : 'u923365340_serveasy');
            $dbUser = $get(['DB_USERNAME', 'DB_USER'], $isLocal ? 'root' : 'u923365340_serveasy');
            $dbPass = $get(['DB_PASSWORD', 'DB_PASS'], $isLocal ? '' : 'Serveasy@931');

            if (empty($dbName) || empty($dbUser)) {
                return null;
            }

            try {
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                self::$pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                return self::$pdo;
            } catch (\Throwable $e) {
                error_log("ServEasyWebDB connection error: " . $e->getMessage());
                return null;
            }
        }
    }
}
