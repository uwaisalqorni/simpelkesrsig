<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Calibrations extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Calibration_model', 'calibration_m');
    }

    /**
     * GET /api/calibrations or POST to create new calibration
     */
    public function index() {
        if ($this->input->method() === 'post') {
            return $this->store();
        }

        $user = $this->authenticate(true);
        $filters = [
            'equipment_id' => $this->input->get('equipment_id'),
            'result'       => $this->input->get('result'),
            'room_id'      => $this->input->get('room_id'),
            'search'       => $this->input->get('search')
        ];

        // Jika user adalah role ruangan, tampilkan alat sesuai mapping unit login nya
        if ($user['role'] === 'ruangan' && !empty($user['room_id'])) {
            $filters['room_id'] = $user['room_id'];
        }

        $page = (int)$this->input->get('page');
        $limit = (int)$this->input->get('limit');
        $total = $this->calibration_m->count_all($filters);

        if ($limit > 0) {
            $page = max(1, $page);
            $offset = ($page - 1) * $limit;
            $logs = $this->calibration_m->get_all($filters, $limit, $offset);
            $total_pages = ceil($total / $limit);
        } else {
            $logs = $this->calibration_m->get_all($filters);
            $page = 1;
            $limit = $total > 0 ? $total : 10;
            $total_pages = 1;
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode([
                'success'    => true,
                'message'    => 'Data kalibrasi alat berhasil diambil.',
                'data'       => $logs,
                'pagination' => [
                    'total'       => (int)$total,
                    'page'        => (int)$page,
                    'limit'       => (int)$limit,
                    'total_pages' => (int)$total_pages
                ],
                'timestamp'  => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
            ->_display();
        exit;
    }

    /**
     * GET /api/calibrations/show/{id}
     */
    public function show($id = null) {
        $method = strtolower($this->input->method());
        if ($method === 'delete') {
            return $this->delete($id);
        }
        if ($method === 'put') {
            return $this->update($id);
        }

        $this->authenticate(true);
        $log = $this->calibration_m->find_by_id($id);
        if (!$log) {
            $this->json_response(false, 'Data kalibrasi tidak ditemukan.', null, 404);
        }

        $this->json_response(true, 'Detail kalibrasi.', $log);
    }

    /**
     * GET /api/calibrations/expiring
     * Mengambil alkes yang masa kalibrasinya mendekati kedaluwarsa
     */
    public function expiring() {
        $this->authenticate(true);
        $days = $this->input->get('days') ? (int)$this->input->get('days') : 30;
        $expiring = $this->calibration_m->get_expiring($days);
        $this->json_response(true, 'Daftar kalibrasi mendekati kedaluwarsa.', $expiring);
    }

    /**
     * POST /api/calibrations
     */
    public function store() {
        $this->require_role(['admin', 'teknisi']);

        $equipment_id       = $this->input->post('equipment_id');
        $calibration_date   = $this->input->post('calibration_date');
        $valid_until        = $this->input->post('valid_until');
        $certificate_number = $this->input->post('certificate_number');
        $vendor_name        = $this->input->post('vendor_name');
        $result             = $this->input->post('result') ?: 'laik_pakai';
        $notes              = $this->input->post('notes');

        if (empty($equipment_id)) {
            $raw = $this->get_json_input();
            $equipment_id       = isset($raw['equipment_id']) ? $raw['equipment_id'] : null;
            $calibration_date   = isset($raw['calibration_date']) ? $raw['calibration_date'] : null;
            $valid_until        = isset($raw['valid_until']) ? $raw['valid_until'] : null;
            $certificate_number = isset($raw['certificate_number']) ? $raw['certificate_number'] : null;
            $vendor_name        = isset($raw['vendor_name']) ? $raw['vendor_name'] : null;
            $result             = isset($raw['result']) ? $raw['result'] : 'laik_pakai';
            $notes              = isset($raw['notes']) ? $raw['notes'] : null;
        }

        if (empty($equipment_id) || empty($calibration_date) || empty($valid_until) || empty($certificate_number)) {
            $this->json_response(false, 'Alat medis, tanggal kalibrasi, masa berlaku, dan nomor sertifikat wajib diisi.', null, 400);
        }

        // Upload sertifikat kalibrasi (validasi terpusat)
        $certificate_path = null;
        $upload_result = $this->safe_upload('certificate_file', 'uploads/calibrations/', 'certificate', [
            'file_prefix' => 'cert'
        ]);
        if (!$upload_result['success'] && $upload_result['error']) {
            $this->json_response(false, $upload_result['error'], null, 400);
        }
        $certificate_path = $upload_result['path'];

        $data = [
            'equipment_id'          => (int)$equipment_id,
            'calibration_date'      => $calibration_date,
            'valid_until'           => $valid_until,
            'certificate_number'    => trim($certificate_number),
            'certificate_file_path' => $certificate_path,
            'vendor_name'           => !empty($vendor_name) ? trim($vendor_name) : 'Balai Pengujian Fasilitas Kesehatan (BPFK)',
            'result'                => $result,
            'notes'                 => !empty($notes) ? trim($notes) : null
        ];

        $insert_id = $this->calibration_m->insert($data);
        $this->log_audit('CREATE_CALIBRATION', 'calibration_logs', $insert_id, $data);

        $this->json_response(true, 'Data kalibrasi dan sertifikat berhasil disimpan.', ['id' => $insert_id], 201);
    }

    /**
     * POST/PUT /api/calibrations/update/{id}
     * HANYA ADMIN
     */
    public function update($id = null) {
        $this->require_role(['admin']);

        $log = $this->calibration_m->find_by_id($id);
        if (!$log) {
            $this->json_response(false, 'Data kalibrasi tidak ditemukan.', null, 404);
        }

        $equipment_id       = $this->input->post('equipment_id');
        $calibration_date   = $this->input->post('calibration_date');
        $valid_until        = $this->input->post('valid_until');
        $certificate_number = $this->input->post('certificate_number');
        $vendor_name        = $this->input->post('vendor_name');
        $result             = $this->input->post('result');
        $notes              = $this->input->post('notes');

        if (empty($equipment_id)) {
            $raw = $this->get_json_input();
            $equipment_id       = isset($raw['equipment_id']) ? $raw['equipment_id'] : null;
            $calibration_date   = isset($raw['calibration_date']) ? $raw['calibration_date'] : null;
            $valid_until        = isset($raw['valid_until']) ? $raw['valid_until'] : null;
            $certificate_number = isset($raw['certificate_number']) ? $raw['certificate_number'] : null;
            $vendor_name        = isset($raw['vendor_name']) ? $raw['vendor_name'] : null;
            $result             = isset($raw['result']) ? $raw['result'] : null;
            $notes              = isset($raw['notes']) ? $raw['notes'] : null;
        }

        $update_data = [];
        if (!empty($equipment_id))       $update_data['equipment_id'] = (int)$equipment_id;
        if (!empty($calibration_date))   $update_data['calibration_date'] = $calibration_date;
        if (!empty($valid_until))        $update_data['valid_until'] = $valid_until;
        if (!empty($certificate_number)) $update_data['certificate_number'] = trim($certificate_number);
        if (!empty($vendor_name))        $update_data['vendor_name'] = trim($vendor_name);
        if (!empty($result))             $update_data['result'] = $result;
        if ($notes !== null)             $update_data['notes'] = trim($notes);

        // Upload sertifikat baru jika ada (validasi terpusat)
        $upload_result = $this->safe_upload('certificate_file', 'uploads/calibrations/', 'certificate', [
            'file_prefix'  => 'cert',
            'old_file_path' => !empty($log['certificate_file_path']) ? $log['certificate_file_path'] : null
        ]);
        if (!$upload_result['success'] && $upload_result['error']) {
            $this->json_response(false, $upload_result['error'], null, 400);
        }
        if ($upload_result['path']) {
            $update_data['certificate_file_path'] = $upload_result['path'];
        }

        if (empty($update_data)) {
            $this->json_response(false, 'Tidak ada data perubahan yang dikirim.', null, 400);
        }

        $this->calibration_m->update($id, $update_data);
        $this->log_audit('UPDATE_CALIBRATION', 'calibration_logs', $id, $update_data);

        $updated_log = $this->calibration_m->find_by_id($id);
        $this->json_response(true, 'Data kalibrasi berhasil diperbarui.', $updated_log);
    }

    /**
     * POST/DELETE /api/calibrations/delete/{id}
     * HANYA ADMIN
     */
    public function delete($id = null) {
        $this->require_role(['admin']);

        $log = $this->calibration_m->find_by_id($id);
        if (!$log) {
            $this->json_response(false, 'Data kalibrasi tidak ditemukan.', null, 404);
        }

        // Hapus file sertifikat jika ada
        if (!empty($log['certificate_file_path']) && file_exists(FCPATH . $log['certificate_file_path'])) {
            @unlink(FCPATH . $log['certificate_file_path']);
        }

        $this->calibration_m->delete($id);
        $this->log_audit('DELETE_CALIBRATION', 'calibration_logs', $id, [
            'certificate_number' => $log['certificate_number'],
            'equipment_name'     => $log['equipment_name']
        ]);

        $this->json_response(true, 'Data kalibrasi berhasil dihapus.');
    }
}
