<?php
// Izinkan akses langsung dari index.php saat inisialisasi awal environment
if (!defined('BASEPATH') && !defined('ENV_LOADER')) {
    define('ENV_LOADER', true);
}

/**
 * SIMPELKES Environment Loader (.env)
 * Engine mandiri (Zero-Dependency) untuk membaca konfigurasi environment
 */
class Env {

    private static $loaded = false;

    public static function load($filePath) {
        if (self::$loaded || !file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Lewati baris komentar
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Pisahkan key dan value berdasarkan tanda '=' pertama
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key   = trim($parts[0]);
            $value = trim($parts[1]);

            // Hapus kutip ganda atau tunggal jika ada
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }

            // Konversi tipe data khusus
            switch (strtolower($value)) {
                case 'true':
                    $val = true;
                    break;
                case 'false':
                    $val = false;
                    break;
                case 'null':
                    $val = null;
                    break;
                default:
                    $val = $value;
                    break;
            }

            if (!array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $val;
            }
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $val;
            }
            putenv("{$key}={$value}");
        }

        self::$loaded = true;
    }
}

/**
 * Helper global untuk mengambil variabel environment
 */
if (!function_exists('env')) {
    function env($key, $default = null) {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        return $default;
    }
}
