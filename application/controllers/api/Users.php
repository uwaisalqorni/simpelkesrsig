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
    /**
     * GET /api/users/technicians
     */
    public function technicians() {
        $this->authenticate(true);
        $tenant_id = $this->get_tenant_id();
        $techs = $this->user_m->get_technicians($tenant_id);
        $this->json_response(true, 'Daftar teknisi elektromedis.', $techs);
    }

    /**
     * GET /api/users or POST /api/users (Admin & Super Admin only)
     */
    public function index() {
        $this->require_role(['admin']);
        if ($this->input->method() === 'post') {
            return $this->store();
        }

        $role = $this->input->get('role');
        $tenant_id = null;

        if ($this->current_user['role'] === 'super_admin') {
            // Super Admin dapat melihat filter per faskes atau seluruh holding
            $query_tenant = $this->input->get('tenant_id');
            if ($query_tenant !== null && $query_tenant !== '') {
                $tenant_id = (int)$query_tenant;
            } else {
                $tenant_id = $this->get_tenant_id(); // Menghormati header X-Tenant-Id jika ada
            }
        } else {
            // Admin faskes biasa hanya dapat melihat user faskesnya sendiri
            $tenant_id = (int) $this->current_user['tenant_id'];
        }

        $users = $this->user_m->get_all($role, $tenant_id);
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

        // Tentukan tenant_id berdasarkan hak akses peran
        $tenant_id = null;
        if ($this->current_user['role'] === 'super_admin') {
            if ($role === 'super_admin') {
                $tenant_id = null; // Super admin tidak terikat satu faskes
            } else {
                $tenant_id = !empty($input['tenant_id']) ? (int)$input['tenant_id'] : ($this->get_tenant_id() ?: 1);
            }
        } else {
            // Admin reguler tidak boleh membuat super_admin
            if ($role === 'super_admin') {
                $this->json_response(false, 'Anda tidak memiliki hak akses untuk membuat peran Super Admin.', null, 403);
            }
            // Terkunci otomatis ke faskes milik admin bersangkutan
            $tenant_id = (int) $this->current_user['tenant_id'];
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
            'tenant_id'     => $tenant_id,
            'phone'         => $phone,
            'is_active'     => $is_active,
            'created_at'    => date('Y-m-d H:i:s')
        ];

        $insert_id = $this->user_m->insert($data);
        $this->log_audit('CREATE_USER', 'users', $insert_id, "User {$username} ({$role}) didaftarkan pada tenant {$tenant_id}");

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

        // Validasi akses isolasi faskes
        if ($this->current_user['role'] !== 'super_admin') {
            if ($user['tenant_id'] != $this->current_user['tenant_id']) {
                $this->json_response(false, 'Anda tidak berhak mengubah pengguna dari faskes lain.', null, 403);
            }
        }

        $input = $this->get_json_input();
        $data = [];

        if (isset($input['full_name'])) $data['full_name'] = trim($input['full_name']);
        if (isset($input['role'])) {
            $new_role = trim($input['role']);
            if ($new_role === 'super_admin' && $this->current_user['role'] !== 'super_admin') {
                $this->json_response(false, 'Hanya Super Admin yang dapat menetapkan peran Super Admin.', null, 403);
            }
            $data['role'] = $new_role;
            if ($new_role === 'super_admin') {
                $data['tenant_id'] = null;
            }
        }

        // Super Admin dapat memutasikan user ke faskes lain jika diperlukan
        if ($this->current_user['role'] === 'super_admin' && array_key_exists('tenant_id', $input)) {
            $data['tenant_id'] = !empty($input['tenant_id']) ? (int)$input['tenant_id'] : null;
        }

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
