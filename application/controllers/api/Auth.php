<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Auth extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model', 'user_m');
    }

    /**
     * POST /api/auth/login
     */
    public function login() {
        $ip = $this->input->ip_address();
        $this->_check_rate_limit($ip);

        $input = $this->get_json_input();
        $username = isset($input['username']) ? trim($input['username']) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';

        if (empty($username) || empty($password)) {
            $this->json_response(false, 'Username dan password wajib diisi.', null, 400);
        }

        $user = $this->user_m->find_by_username($username);

        if (!$user) {
            $this->_record_failed_attempt($ip);
            $this->json_response(false, 'Pengguna tidak ditemukan atau dinonaktifkan.', null, 401);
        }

        if ($user['is_active'] != 1) {
            $this->json_response(false, 'Akun Anda sedang dinonaktifkan. Hubungi Administrator IPSRS.', null, 403);
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->_record_failed_attempt($ip);
            $this->json_response(false, 'Password yang Anda masukkan salah.', null, 401);
        }

        // Login berhasil — reset percobaan gagal
        $this->_clear_failed_attempts($ip);

        // Generate JWT Token
        $payload = [
            'user_id'   => $user['id'],
            'username'  => $user['username'],
            'role'      => $user['role'],
            'room_id'   => $user['room_id'],
            'tenant_id' => $user['tenant_id'] ?? 1
        ];
        $token = $this->jwt->encode($payload);

        // Jangan kirim password_hash ke client
        unset($user['password_hash']);

        // Log audit
        $this->current_user = $user;
        $this->log_audit('LOGIN', 'users', $user['id'], 'User login berhasil');

        $this->json_response(true, 'Login berhasil.', [
            'token' => $token,
            'user'  => $user
        ]);
    }

    /**
     * GET /api/auth/me
     */
    public function me() {
        $user = $this->authenticate(true);
        unset($user['password_hash']);
        $this->json_response(true, 'Profil pengguna.', $user);
    }

    /**
     * POST /api/auth/refresh
     */
    public function refresh() {
        $user = $this->authenticate(true);
        $payload = [
            'user_id'   => $user['id'],
            'username'  => $user['username'],
            'role'      => $user['role'],
            'room_id'   => $user['room_id']
        ];
        $token = $this->jwt->encode($payload);
        $this->json_response(true, 'Token berhasil diperbarui.', ['token' => $token]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout() {
        $user = $this->authenticate(false);
        if ($user) {
            $this->log_audit('LOGOUT', 'users', $user['id'], 'User logout');
        }
        $this->json_response(true, 'Logout berhasil.');
    }

    /**
     * Rate Limiting Helper: Cek apakah IP terkena batasan percobaan login gagal
     */
    private function _check_rate_limit($ip) {
        $cache_dir = APPPATH . 'cache/';
        $file = $cache_dir . 'login_rate_' . md5($ip) . '.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (!empty($data) && isset($data['count']) && isset($data['reset_time'])) {
                if (time() < $data['reset_time']) {
                    if ($data['count'] >= 7) { // Maks 7 kali gagal berturut-turut
                        $remaining = ceil(($data['reset_time'] - time()) / 60);
                        $this->json_response(false, "Terlalu banyak percobaan login gagal. Demi keamanan, akun/IP Anda dibatasi sementara. Silakan coba kembali dalam {$remaining} menit.", null, 429);
                    }
                } else {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Catat percobaan login yang gagal
     */
    private function _record_failed_attempt($ip) {
        $cache_dir = APPPATH . 'cache/';
        if (!is_dir($cache_dir)) @mkdir($cache_dir, 0777, true);
        $file = $cache_dir . 'login_rate_' . md5($ip) . '.json';
        $count = 1;
        $reset_time = time() + (15 * 60); // 15 menit

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (!empty($data) && time() < ($data['reset_time'] ?? 0)) {
                $count = ($data['count'] ?? 0) + 1;
                $reset_time = $data['reset_time'];
            }
        }
        @file_put_contents($file, json_encode(['count' => $count, 'reset_time' => $reset_time]));
    }

    /**
     * Bersihkan catatan gagal saat login berhasil
     */
    private function _clear_failed_attempts($ip) {
        $file = APPPATH . 'cache/login_rate_' . md5($ip) . '.json';
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
