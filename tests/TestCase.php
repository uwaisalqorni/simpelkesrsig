<?php
/**
 * SIMPELKES Base TestCase
 * Menyediakan assertion helpers dan API client untuk pengujian backend
 */

class TestCase {

    protected $assertionsCount = 0;
    protected $failures = [];
    protected $baseUrl = 'http://localhost/simpelkesrsig-backend/api';

    public function setUp() {}
    public function tearDown() {}

    public function getAssertionsCount() {
        return $this->assertionsCount;
    }

    public function getFailures() {
        return $this->failures;
    }

    // =========================================================================
    // ASSERTION HELPERS
    // =========================================================================

    public function assertTrue($condition, $message = 'Expected true but got false') {
        $this->assertionsCount++;
        if ($condition !== true) {
            $this->failures[] = $message;
            throw new Exception($message);
        }
    }

    public function assertFalse($condition, $message = 'Expected false but got true') {
        $this->assertionsCount++;
        if ($condition !== false) {
            $this->failures[] = $message;
            throw new Exception($message);
        }
    }

    public function assertEquals($expected, $actual, $message = '') {
        $this->assertionsCount++;
        if ($expected != $actual) {
            $msg = $message ?: ("Expected " . var_export($expected, true) . " but got " . var_export($actual, true));
            $this->failures[] = $msg;
            throw new Exception($msg);
        }
    }

    public function assertSame($expected, $actual, $message = '') {
        $this->assertionsCount++;
        if ($expected !== $actual) {
            $msg = $message ?: ("Expected exact " . var_export($expected, true) . " but got " . var_export($actual, true));
            $this->failures[] = $msg;
            throw new Exception($msg);
        }
    }

    public function assertNull($actual, $message = 'Expected value to be null') {
        $this->assertionsCount++;
        if ($actual !== null) {
            $this->failures[] = $message;
            throw new Exception($message);
        }
    }

    public function assertNotNull($actual, $message = 'Expected value not to be null') {
        $this->assertionsCount++;
        if ($actual === null) {
            $this->failures[] = $message;
            throw new Exception($message);
        }
    }

    public function assertArrayHasKey($key, $array, $message = '') {
        $this->assertionsCount++;
        if (!is_array($array) || !array_key_exists($key, $array)) {
            $msg = $message ?: "Array does not contain key '{$key}'";
            $this->failures[] = $msg;
            throw new Exception($msg);
        }
    }

    public function assertContains($needle, $haystack, $message = '') {
        $this->assertionsCount++;
        if (is_string($haystack)) {
            if (strpos($haystack, $needle) === false) {
                $msg = $message ?: "String does not contain '{$needle}'";
                $this->failures[] = $msg;
                throw new Exception($msg);
            }
        } elseif (is_array($haystack)) {
            if (!in_array($needle, $haystack)) {
                $msg = $message ?: "Array does not contain " . var_export($needle, true);
                $this->failures[] = $msg;
                throw new Exception($msg);
            }
        }
    }

    // =========================================================================
    // HTTP CLIENT HELPER
    // =========================================================================

    public function api($method, $endpoint, $data = null, $token = null, $tenant_id = null) {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $ch = curl_init();
        $headers = ['Accept: application/json'];

        if ($token) {
            $headers[] = "Authorization: Bearer {$token}";
        }
        if ($tenant_id !== null) {
            $headers[] = "X-Tenant-Id: {$tenant_id}";
        }

        $method = strtoupper($method);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET') {
            if ($data && is_array($data)) {
                $url .= '?' . http_build_query($data);
            }
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $raw = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($raw, true);

        return [
            'code' => $http_code,
            'body' => $json,
            'raw'  => $raw
        ];
    }

    public function getDb() {
        static $db = null;
        if ($db === null) {
            if (!function_exists('env')) {
                require_once dirname(__DIR__) . '/application/core/Env.php';
                Env::load(dirname(__DIR__) . '/.env');
            }
            $host = env('DB_HOST', 'localhost');
            $user = env('DB_USER', 'root');
            $pass = env('DB_PASS', '');
            $name = env('DB_NAME', 'simpelkesrsig');

            $db = new mysqli($host, $user, $pass, $name);
            if ($db->connect_error) {
                throw new Exception('Database connection error: ' . $db->connect_error);
            }
        }
        return $db;
    }
}
