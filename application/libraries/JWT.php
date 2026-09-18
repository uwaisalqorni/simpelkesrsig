<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class JWT {

    protected $ci;
    protected $secret_key;
    protected $algorithm;
    protected $expire_seconds;

    public function __construct() {
        $this->ci =& get_instance();
        $this->ci->load->config('jwt', TRUE);
        $this->secret_key = $this->ci->config->item('jwt_key', 'jwt') ?: 'SIMPELKES_SECRET_KEY_DEF_2026';
        $this->algorithm = $this->ci->config->item('jwt_algorithm', 'jwt') ?: 'HS256';
        $this->expire_seconds = $this->ci->config->item('jwt_expire_seconds', 'jwt') ?: 604800;
    }

    public function encode($payload) {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];

        // Tambahkan iat dan exp jika belum ada
        $now = time();
        if (!isset($payload['iat'])) {
            $payload['iat'] = $now;
        }
        if (!isset($payload['exp'])) {
            $payload['exp'] = $now + $this->expire_seconds;
        }

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function decode($jwt) {
        if (empty($jwt)) {
            return false;
        }

        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) != 3) {
            return false;
        }

        $header = json_decode($this->base64UrlDecode($tokenParts[0]), true);
        $payload = json_decode($this->base64UrlDecode($tokenParts[1]), true);
        $signature_provided = $tokenParts[2];

        if (!$header || !$payload) {
            return false;
        }

        // Verifikasi Signature
        $base64UrlHeader = $tokenParts[0];
        $base64UrlPayload = $tokenParts[1];
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        if (!hash_equals($base64UrlSignature, $signature_provided)) {
            return false;
        }

        // Verifikasi Expiration
        if (isset($payload['exp']) && ($payload['exp'] - time()) < 0) {
            return false; // Token kadaluarsa
        }

        return $payload;
    }

    private function base64UrlEncode($text) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }

    private function base64UrlDecode($text) {
        $b64 = str_replace(['-', '_'], ['+', '/'], $text);
        return base64_decode($b64);
    }
}
