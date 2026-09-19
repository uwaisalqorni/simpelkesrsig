<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Tenants extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Tenant_model', 'tenant_m');
    }

    /**
     * GET /api/tenants
     * Daftar faskes / tenant (publik / terotentikasi)
     */
    public function index() {
        if ($this->input->method() === 'post') {
            return $this->create();
        }
        $user = $this->authenticate(false);
        $is_super = ($user && $user['role'] === 'super_admin');
        
        $tenants = $this->tenant_m->get_all(!$is_super);
        $this->json_response(true, 'Daftar Faskes / Rumah Sakit terdaftar.', $tenants);
    }

    /**
     * GET /api/tenants/overview
     * Statistik agregat seluruh faskes (Super Admin only)
     */
    public function overview() {
        $this->require_role(['super_admin']);
        $overview = $this->tenant_m->get_global_overview();
        $this->json_response(true, 'Ringkasan global seluruh faskes.', $overview);
    }

    /**
     * GET /api/tenants/{id}
     */
    public function show($id = null) {
        $this->authenticate(true);
        $tenant = $this->tenant_m->find_by_id($id);
        if (!$tenant) {
            $this->json_response(false, 'Faskes tidak ditemukan.', null, 404);
        }
        $this->json_response(true, 'Detail faskes.', $tenant);
    }

    /**
     * POST /api/tenants
     * Tambah faskes baru (Super Admin only)
     */
    public function create() {
        $this->require_role(['super_admin']);
        $input = $this->get_json_input();

        $code = trim($input['code'] ?? '');
        $name = trim($input['name'] ?? '');
        $slug = trim($input['slug'] ?? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $code)));

        if (empty($code) || empty($name)) {
            $this->json_response(false, 'Kode dan Nama Rumah Sakit / Faskes wajib diisi.', null, 400);
        }

        // Cek duplikasi kode atau slug
        if ($this->tenant_m->find_by_code($code)) {
            $this->json_response(false, "Kode faskes '{$code}' sudah digunakan oleh Rumah Sakit lain.", null, 400);
        }

        // Handle upload logo jika ada
        $logo_path = null;
        if (!empty($_FILES['logo'])) {
            $upload_res = $this->safe_upload('logo', 'application/uploads/logo/', 'logo', ['encrypt_name' => true]);
            if ($upload_res['success']) {
                $logo_path = $upload_res['path'];
            }
        }

        $insert_data = [
            'code'              => strtoupper($code),
            'name'              => $name,
            'slug'              => $slug,
            'hospital_subtitle' => trim($input['hospital_subtitle'] ?? 'Instalasi Pemeliharaan Sarana (IPSRS)'),
            'address'           => trim($input['address'] ?? ''),
            'phone'             => trim($input['phone'] ?? ''),
            'city'              => trim($input['city'] ?? ''),
            'logo_path'         => $logo_path,
            'is_active'         => 1,
            'created_at'        => date('Y-m-d H:i:s')
        ];

        $tenant_id = $this->tenant_m->insert($insert_data);

        // Buat admin default untuk tenant baru jika diminta
        $admin_username = trim($input['admin_username'] ?? '');
        $admin_password = trim($input['admin_password'] ?? '');
        if (!empty($admin_username) && !empty($admin_password)) {
            $this->load->model('User_model', 'user_m');
            $this->user_m->insert([
                'username'      => $admin_username,
                'password_hash' => password_hash($admin_password, PASSWORD_BCRYPT),
                'full_name'     => !empty($input['admin_full_name']) ? trim($input['admin_full_name']) : "Admin {$name}",
                'role'          => 'admin',
                'tenant_id'     => $tenant_id,
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s')
            ]);
        }

        $this->log_audit('CREATE_TENANT', 'tenants', $tenant_id, "Tenant baru {$name} ({$code}) dibuat");
        $this->json_response(true, "Rumah Sakit / Faskes '{$name}' berhasil ditambahkan ke jaringan SIMPELKES.", [
            'id' => $tenant_id,
            'code' => $code,
            'name' => $name
        ], 201);
    }

    /**
     * POST /api/tenants/update/{id}
     */
    public function update($id = null) {
        $user = $this->authenticate(true);
        if ($user['role'] !== 'super_admin' && ($user['role'] !== 'admin' || $user['tenant_id'] != $id)) {
            $this->json_response(false, 'Anda tidak memiliki hak akses untuk mengubah profil faskes ini.', null, 403);
        }

        $tenant = $this->tenant_m->find_by_id($id);
        if (!$tenant) {
            $this->json_response(false, 'Faskes tidak ditemukan.', null, 404);
        }

        $input = $this->input->post();
        if (empty($input)) {
            $input = $this->get_json_input();
        }

        $update_data = [];
        if (isset($input['name']))              $update_data['name']              = trim($input['name']);
        if (isset($input['hospital_subtitle'])) $update_data['hospital_subtitle'] = trim($input['hospital_subtitle']);
        if (isset($input['address']))           $update_data['address']           = trim($input['address']);
        if (isset($input['phone']))             $update_data['phone']             = trim($input['phone']);
        if (isset($input['city']))              $update_data['city']              = trim($input['city']);

        if (!empty($_FILES['logo'])) {
            $upload_res = $this->safe_upload('logo', 'application/uploads/logo/', 'logo', ['encrypt_name' => true]);
            if ($upload_res['success'] && $upload_res['path']) {
                $update_data['logo_path'] = $upload_res['path'];
            }
        }

        if (!empty($update_data)) {
            $this->tenant_m->update($id, $update_data);
            $this->log_audit('UPDATE_TENANT', 'tenants', $id, $update_data);
        }

        $updated = $this->tenant_m->find_by_id($id);
        $this->json_response(true, 'Profil Rumah Sakit berhasil diperbarui.', $updated);
    }

    /**
     * POST /api/tenants/toggle-status/{id}
     */
    public function toggle_status($id = null) {
        $this->require_role(['super_admin']);
        if ($id == 1) {
            $this->json_response(false, 'Tenant utama (RS Islam Gondanglegi) tidak dapat dinonaktifkan.', null, 400);
        }

        $tenant = $this->tenant_m->find_by_id($id);
        if (!$tenant) {
            $this->json_response(false, 'Faskes tidak ditemukan.', null, 404);
        }

        $new_status = $tenant['is_active'] == 1 ? 0 : 1;
        $this->tenant_m->update($id, ['is_active' => $new_status]);

        $status_label = $new_status == 1 ? 'diaktifkan' : 'dinonaktifkan';
        $this->log_audit('TOGGLE_TENANT_STATUS', 'tenants', $id, "Status faskes {$tenant['name']} {$status_label}");
        $this->json_response(true, "Rumah Sakit '{$tenant['name']}' berhasil {$status_label}.");
    }
}
