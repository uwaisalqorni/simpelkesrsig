<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Users extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model', 'user_m');
    }

    /**
     * GET /api/users/technicians
     */
    public function technicians() {
        $this->authenticate(true);
        $techs = $this->user_m->get_technicians();
        $this->json_response(true, 'Daftar teknisi elektromedis.', $techs);
    }

    /**
     * GET /api/users or POST /api/users (Admin only)
     */
    public function index() {
        $this->require_role(['admin']);
        if ($this->input->method() === 'post') {
            return $this->store();
        }

        $role = $this->input->get('role');
        $users = $this->user_m->get_all($role);
        $this->json_response(true, 'Daftar pengguna sistem.', $users);
    }

    /**
     * POST /api/users
     */
    public function store() {
        $this->require_role(['admin']);
        $input = $this->get_json_input();

        $username  = isset($input['username']) ? trim($input['username']) : '';
        $password  = isset($input['password']) ? trim($input['password']) : 'password123';
        $full_name = isset($input['full_name']) ? trim($input['full_name']) : '';
        $role      = isset($input['role']) ? trim($input['role']) : 'ruangan';
        $room_id   = !empty($input['room_id']) ? (int)$input['room_id'] : null;
        $phone     = isset($input['phone']) ? trim($input['phone']) : null;
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

        if (empty($username) || empty($full_name)) {
            $this->json_response(false, 'Username dan nama lengkap pengguna wajib diisi.', null, 400);
        }

        // Cek duplikasi username
        $existing = $this->user_m->find_by_username($username);
        if ($existing) {
            $this->json_response(false, "Username '{$username}' sudah digunakan. Silakan gunakan username lain.", null, 400);
        }

        $data = [
            'username'      => strtolower($username),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'full_name'     => $full_name,
            'role'          => $role,
            'room_id'       => $room_id,
            'phone'         => $phone,
            'is_active'     => $is_active
        ];

        $insert_id = $this->user_m->insert($data);
        $this->log_audit('CREATE_USER', 'users', $insert_id, "User {$username} ({$role}) didaftarkan");

        $this->json_response(true, 'Pengguna baru berhasil ditambahkan.', ['id' => $insert_id], 201);
    }

    /**
     * POST /api/users/update/{id}
     */
    public function update($id = null) {
        $this->require_role(['admin']);
        $user = $this->user_m->find_by_id($id);
        if (!$user) {
            $this->json_response(false, 'Pengguna tidak ditemukan.', null, 404);
        }

        $input = $this->get_json_input();
        $data = [];

        if (isset($input['full_name'])) $data['full_name'] = trim($input['full_name']);
        if (isset($input['role'])) $data['role'] = trim($input['role']);
        if (array_key_exists('room_id', $input)) $data['room_id'] = !empty($input['room_id']) ? (int)$input['room_id'] : null;
        if (isset($input['phone'])) $data['phone'] = trim($input['phone']);
        if (isset($input['is_active'])) $data['is_active'] = (int)$input['is_active'];

        // Reset password jika diisi
        if (!empty($input['password'])) {
            $data['password_hash'] = password_hash(trim($input['password']), PASSWORD_BCRYPT);
        }

        if (!empty($data)) {
            $this->user_m->update($id, $data);
            $this->log_audit('UPDATE_USER', 'users', $id, "Profil user {$user['username']} diperbarui");
        }

        $this->json_response(true, 'Data pengguna berhasil diperbarui.');
    }
}
