<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
    public function __construct() {
        parent::__construct();
    }
}

class Base_Api_Controller extends CI_Controller {

    protected $current_user = null;

    public function __construct() {
        parent::__construct();
        $this->load->library('jwt');
        $this->load->database();
    }

    /**
     * Response JSON standar
     */
    protected function json_response($success, $message, $data = null, $http_code = 200) {
        $this->output
            ->set_status_header($http_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode([
                'success' => (bool)$success,
                'message' => $message,
                'data'    => $data,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
            ->_display();
        exit;
    }

    /**
     * Mengambil JSON body dari request
     */
    protected function get_json_input() {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return $this->input->post();
        }
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }

    /**
     * Validasi Header Authorization Bearer Token
     */
    protected function authenticate($required = true) {
        $auth_header = $this->input->get_request_header('Authorization', TRUE);
        if (!$auth_header) {
            $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null;
        }

        if (empty($auth_header) || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            if ($required) {
                $this->json_response(false, 'Token otentikasi tidak ditemukan.', null, 401);
            }
            return null;
        }

        $token = $matches[1];
        $payload = $this->jwt->decode($token);

        if (!$payload || empty($payload['user_id'])) {
            if ($required) {
                $this->json_response(false, 'Token tidak valid atau telah kadaluarsa.', null, 401);
            }
            return null;
        }

        // Ambil data user terkini dari database
        $this->db->select('u.id, u.username, u.full_name, u.role, u.room_id, u.is_active, r.name as room_name, r.code as room_code');
        $this->db->from('users u');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->where('u.id', $payload['user_id']);
        $user = $this->db->get()->row_array();

        if (!$user || $user['is_active'] != 1) {
            if ($required) {
                $this->json_response(false, 'Akun pengguna tidak aktif atau tidak ditemukan.', null, 403);
            }
            return null;
        }

        $this->current_user = $user;
        return $this->current_user;
    }

    /**
     * Cek izin peran (Role Based Access Control)
     */
    protected function require_role($allowed_roles = []) {
        if (!$this->current_user) {
            $this->authenticate(true);
        }

        if (is_string($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }

        if (!empty($allowed_roles) && !in_array($this->current_user['role'], $allowed_roles)) {
            $this->json_response(false, 'Akses ditolak. Peran pengguna (' . $this->current_user['role'] . ') tidak memiliki izin untuk tindakan ini.', null, 403);
        }
    }

    /**
     * Audit log helper
     */
    protected function log_audit($action, $table = null, $record_id = null, $details = null) {
        $user_id = $this->current_user ? $this->current_user['id'] : null;
        if (is_array($details) || is_object($details)) {
            $details = json_encode($details);
        }

        $this->db->insert('audit_logs', [
            'user_id'    => $user_id,
            'action'     => $action,
            'table_name' => $table,
            'record_id'  => $record_id,
            'details'    => $details,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        ]);
    }

    // ================================================================
    // Validasi & Upload File Terpusat (Centralized File Upload Helper)
    // ================================================================

    /**
     * Daftar preset konfigurasi upload yang diizinkan
     * Setiap preset mendefinisikan tipe file, ukuran maks, dan MIME yang valid
     */
    protected $upload_presets = [
        // Foto alat medis, foto kerusakan, foto progress
        'image' => [
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size'      => 5120,  // 5MB
            'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'label'         => 'foto/gambar'
        ],
        // Foto + GIF (untuk tiket perbaikan)
        'image_gif' => [
            'allowed_types' => 'gif|jpg|jpeg|png|webp',
            'max_size'      => 5120,  // 5MB
            'allowed_mimes' => ['image/gif', 'image/jpeg', 'image/png', 'image/webp'],
            'label'         => 'foto/gambar'
        ],
        // Sertifikat kalibrasi (PDF + gambar)
        'certificate' => [
            'allowed_types' => 'pdf|jpg|jpeg|png',
            'max_size'      => 10240, // 10MB
            'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
            'label'         => 'sertifikat (PDF/gambar)'
        ],
        // Logo rumah sakit
        'logo' => [
            'allowed_types' => 'jpg|jpeg|png|webp|svg',
            'max_size'      => 3072,  // 3MB
            'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
            'label'         => 'logo (gambar/SVG)'
        ]
    ];

    /**
     * Upload file dengan validasi ketat
     * 
     * @param string $field_name   Nama field pada form (e.g. 'image', 'issue_photo')
     * @param string $upload_dir   Path direktori tujuan (relatif terhadap FCPATH)
     * @param string $preset       Nama preset konfigurasi ('image', 'certificate', 'logo')
     * @param array  $options      Opsi tambahan:
     *   - 'file_prefix'   => Prefix nama file (default: 'file')
     *   - 'encrypt_name'  => Boolean, encrypt nama file (default: false)
     *   - 'required'      => Boolean, apakah wajib diisi (default: false)
     *   - 'old_file_path' => Path file lama yang akan dihapus jika upload baru berhasil
     * 
     * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
     */
    protected function safe_upload($field_name, $upload_dir, $preset = 'image', $options = []) {
        $result = ['success' => false, 'path' => null, 'error' => null];

        // Cek apakah ada file yang di-upload
        if (empty($_FILES[$field_name]['name'])) {
            if (!empty($options['required'])) {
                $result['error'] = 'File wajib diupload.';
                return $result;
            }
            // Tidak wajib dan tidak ada file — skip, bukan error
            $result['success'] = true;
            return $result;
        }

        // Validasi preset
        if (!isset($this->upload_presets[$preset])) {
            $result['error'] = 'Konfigurasi upload tidak valid.';
            return $result;
        }
        $preset_config = $this->upload_presets[$preset];

        // === 1. Validasi ukuran file (sebelum upload) ===
        $file_size_kb = $_FILES[$field_name]['size'] / 1024;
        $max_size_kb = $preset_config['max_size'];
        if ($file_size_kb > $max_size_kb) {
            $max_size_mb = round($max_size_kb / 1024, 1);
            $actual_mb = round($file_size_kb / 1024, 1);
            $result['error'] = "Ukuran file terlalu besar ({$actual_mb}MB). Maksimal {$max_size_mb}MB.";
            return $result;
        }

        // === 2. Validasi ekstensi file ===
        $original_name = $_FILES[$field_name]['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_exts = explode('|', $preset_config['allowed_types']);
        if (!in_array($ext, $allowed_exts)) {
            $result['error'] = "Tipe file .{$ext} tidak diizinkan. Format yang diperbolehkan: " . implode(', ', $allowed_exts) . ".";
            return $result;
        }

        // === 3. Validasi MIME type (cek isi file sebenarnya, bukan hanya ekstensi) ===
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actual_mime = finfo_file($finfo, $_FILES[$field_name]['tmp_name']);
        finfo_close($finfo);

        if (!in_array($actual_mime, $preset_config['allowed_mimes'])) {
            $result['error'] = "Isi file tidak sesuai dengan format yang diizinkan ({$preset_config['label']}). Terdeteksi: {$actual_mime}.";
            return $result;
        }

        // === 4. Sanitasi nama file (hapus karakter berbahaya) ===
        $sanitized_name = $this->_sanitize_filename($original_name);

        // === 5. Cek error upload PHP ===
        $upload_error = $_FILES[$field_name]['error'];
        if ($upload_error !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE   => 'File melebihi batas ukuran server (php.ini).',
                UPLOAD_ERR_FORM_SIZE  => 'File melebihi batas ukuran form.',
                UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian. Silakan coba lagi.',
                UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dikirim.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary server tidak ditemukan.',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menyimpan file ke disk server.',
            ];
            $result['error'] = isset($error_messages[$upload_error]) 
                ? $error_messages[$upload_error] 
                : 'Gagal mengupload file (error code: ' . $upload_error . ').';
            return $result;
        }

        // === 6. Buat direktori tujuan jika belum ada ===
        $full_upload_dir = FCPATH . $upload_dir;
        if (!is_dir($full_upload_dir)) {
            if (!mkdir($full_upload_dir, 0755, true)) {
                $result['error'] = 'Gagal membuat direktori upload.';
                return $result;
            }
        }

        // === 7. Proses upload dengan CI Upload Library ===
        $prefix = isset($options['file_prefix']) ? $options['file_prefix'] : 'file';
        $encrypt = isset($options['encrypt_name']) ? (bool)$options['encrypt_name'] : false;

        $config = [
            'upload_path'   => $full_upload_dir,
            'allowed_types' => $preset_config['allowed_types'],
            'max_size'      => $preset_config['max_size'],
        ];

        if ($encrypt) {
            $config['encrypt_name'] = TRUE;
        } else {
            $config['file_name'] = $prefix . '_' . time() . '_' . uniqid();
        }

        $this->load->library('upload', $config);
        // Reset konfigurasi jika library sudah pernah dimuat sebelumnya
        $this->upload->initialize($config);

        if ($this->upload->do_upload($field_name)) {
            $upload_data = $this->upload->data();

            // Hapus file lama jika ada
            if (!empty($options['old_file_path'])) {
                $old_full_path = FCPATH . $options['old_file_path'];
                if (file_exists($old_full_path) && is_file($old_full_path)) {
                    @unlink($old_full_path);
                }
            }

            $result['success'] = true;
            $result['path'] = $upload_dir . $upload_data['file_name'];
            return $result;
        }

        // Upload gagal melalui CI library
        $result['error'] = 'Gagal mengupload ' . $preset_config['label'] . ': ' . strip_tags($this->upload->display_errors());
        return $result;
    }

    /**
     * Sanitasi nama file dari karakter berbahaya
     * Mencegah path traversal dan injection via filename
     */
    private function _sanitize_filename($filename) {
        // Hapus path traversal
        $filename = basename($filename);
        // Hapus karakter berbahaya, sisakan alfanumerik, dash, underscore, titik
        $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $filename);
        // Hapus titik berulang (untuk mencegah ekstensi ganda seperti file.php.jpg)
        $filename = preg_replace('/\.{2,}/', '.', $filename);
        // Batas panjang nama file
        if (strlen($filename) > 200) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 190) . '.' . $ext;
        }
        return $filename;
    }
}
