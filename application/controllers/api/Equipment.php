<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Equipment extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Equipment_model', 'equipment_m');
    }

    /**
     * GET /api/equipment
     */
    public function index() {
        if ($this->input->method() === 'post') {
            return $this->store();
        }

        $user = $this->authenticate(true);
        $filters = [
            'tenant_id'   => $this->get_tenant_id(),
            'room_id'     => $this->input->get('room_id'),
            'category_id' => $this->input->get('category_id'),
            'status'      => $this->input->get('status'),
            'search'      => $this->input->get('search')
        ];

        // Jika user ruangan, bisa default ke ruangan miliknya jika tidak ditentukan
        if ($user['role'] === 'ruangan' && empty($filters['room_id'])) {
            $filters['room_id'] = $user['room_id'];
        }

        $page = (int)$this->input->get('page');
        $limit = (int)$this->input->get('limit');

        $total = $this->equipment_m->count_all($filters);

        if ($limit > 0) {
            $page = max(1, $page);
            $offset = ($page - 1) * $limit;
            $equipment = $this->equipment_m->get_all($filters, $limit, $offset);
            $total_pages = ceil($total / $limit);
        } else {
            $equipment = $this->equipment_m->get_all($filters);
            $page = 1;
            $limit = $total > 0 ? $total : 10;
            $total_pages = 1;
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode([
                'success'    => true,
                'message'    => 'Data alat medis berhasil diambil.',
                'data'       => $equipment,
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
     * GET /api/equipment/show/{id}
     */
    public function show($id = null) {
        $this->authenticate(true);
        $item = $this->equipment_m->find_by_id($id);
        if (!$item) {
            $this->json_response(false, 'Alat medis tidak ditemukan.', null, 404);
        }

        // Ambil riwayat kalibrasi terakhir
        $this->db->where('equipment_id', $id);
        $this->db->order_by('id', 'DESC');
        $item['calibrations'] = $this->db->get('calibration_logs')->result_array();

        // Ambil riwayat tiket servis
        $this->db->select('wo.*, u.full_name as reported_by_name, tech.full_name as technician_name');
        $this->db->from('work_orders wo');
        $this->db->join('users u', 'u.id = wo.reported_by_user_id', 'left');
        $this->db->join('users tech', 'tech.id = wo.assigned_technician_id', 'left');
        $this->db->where('wo.equipment_id', $id);
        $this->db->order_by('wo.id', 'DESC');
        $item['work_orders'] = $this->db->get()->result_array();

        // Ambil jadwal preventif
        $this->db->where('equipment_id', $id);
        $this->db->order_by('scheduled_date', 'DESC');
        $item['preventives'] = $this->db->get('preventive_schedules')->result_array();

        $this->json_response(true, 'Detail alat medis.', $item);
    }

    /**
     * GET /api/equipment/lookup?code=...
     * Scan QR code helper: mencari alat berdasarkan asset_code atau serial_number
     */
    public function lookup() {
        $this->authenticate(true);
        $code = trim($this->input->get('code'));
        if (empty($code)) {
            $this->json_response(false, 'Parameter kode QR/Aset tidak boleh kosong.', null, 400);
        }

        $item = $this->equipment_m->find_by_asset_code($code);
        if (!$item) {
            // Coba cari by serial_number
            $this->db->select('e.*, r.name as room_name, r.code as room_code, c.name as category_name, c.risk_level');
            $this->db->from('medical_equipment e');
            $this->db->join('rooms r', 'r.id = e.room_id', 'left');
            $this->db->join('equipment_categories c', 'c.id = e.category_id', 'left');
            $this->db->where('e.serial_number', $code);
            $this->db->where('e.is_deleted', 0);
            $item = $this->db->get()->row_array();
        }

        if (!$item) {
            $this->json_response(false, 'Alat medis dengan kode ' . htmlspecialchars($code) . ' tidak ditemukan di sistem.', null, 404);
        }

        $this->json_response(true, 'Alat ditemukan.', $item);
    }

    /**
     * POST /api/equipment
     * POST /api/equipment/store
     * Admin menambah alat medis baru
     */
    public function store() {
        $this->require_role(['admin']);
        
        // Cek input FormData (multipart) atau JSON
        $input = $this->input->post();
        if (empty($input)) {
            $input = $this->get_json_input();
        }

        if (empty($input['name']) || empty($input['serial_number']) || empty($input['room_id']) || empty($input['category_id'])) {
            $this->json_response(false, 'Nama alat, nomor seri, ruangan, dan kategori wajib diisi.', null, 400);
        }

        // Cek duplikasi serial_number
        $exist = $this->db->get_where('medical_equipment', ['serial_number' => trim($input['serial_number']), 'is_deleted' => 0])->row_array();
        if ($exist) {
            $this->json_response(false, 'Nomor seri ' . $input['serial_number'] . ' sudah terdaftar untuk alat ' . $exist['name'] . '.', null, 400);
        }

        $asset_code = !empty($input['asset_code']) ? trim($input['asset_code']) : $this->equipment_m->generate_asset_code($input['category_id']);

        // Handle upload foto bukti alat jika ada (validasi terpusat)
        $image_path = null;
        $upload_field = !empty($_FILES['image']['name']) ? 'image' : (!empty($_FILES['photo']['name']) ? 'photo' : null);
        if ($upload_field) {
            $upload_result = $this->safe_upload($upload_field, 'application/uploads/equipment/', 'image', [
                'encrypt_name' => true
            ]);
            if (!$upload_result['success']) {
                $this->json_response(false, $upload_result['error'], null, 400);
            }
            $image_path = $upload_result['path'];
        }

        $data = [
            'tenant_id'          => $this->get_tenant_id(),
            'asset_code'         => $asset_code,
            'serial_number'      => trim($input['serial_number']),
            'name'               => trim($input['name']),
            'brand'              => isset($input['brand']) ? trim($input['brand']) : null,
            'model_type'         => isset($input['model_type']) ? trim($input['model_type']) : null,
            'category_id'        => (int)$input['category_id'],
            'room_id'            => (int)$input['room_id'],
            'purchase_date'      => !empty($input['purchase_date']) ? $input['purchase_date'] : null,
            'warranty_expire'    => !empty($input['warranty_expire']) ? $input['warranty_expire'] : null,
            'price'              => !empty($input['price']) ? (float)$input['price'] : 0.00,
            'vendor_supplier'    => isset($input['vendor_supplier']) ? trim($input['vendor_supplier']) : null,
            'operational_status' => !empty($input['operational_status']) ? $input['operational_status'] : 'operasional',
            'quantity'           => !empty($input['quantity']) ? max(1, (int)$input['quantity']) : 1,
            'image_path'         => $image_path,
        ];

        $insert_id = $this->equipment_m->insert($data);
        $this->log_audit('CREATE_EQUIPMENT', 'medical_equipment', $insert_id, $data);

        $this->json_response(true, 'Alat medis baru berhasil didaftarkan.', [
            'id'         => $insert_id,
            'asset_code' => $asset_code,
            'quantity'   => $data['quantity'],
            'image_path' => $image_path
        ], 201);
    }

    /**
     * POST /api/equipment/update/{id}
     */
    public function update($id = null) {
        $this->require_role(['admin', 'teknisi']);
        $item = $this->equipment_m->find_by_id($id);
        if (!$item) {
            $this->json_response(false, 'Alat medis tidak ditemukan.', null, 404);
        }

        $input = $this->input->post();
        if (empty($input)) {
            $input = $this->get_json_input();
        }

        if (!empty($input['serial_number']) && trim($input['serial_number']) !== $item['serial_number']) {
            $exist = $this->db->get_where('medical_equipment', [
                'serial_number' => trim($input['serial_number']),
                'id !='         => $id,
                'is_deleted'    => 0
            ])->row_array();
            if ($exist) {
                $this->json_response(false, 'Nomor seri ' . $input['serial_number'] . ' sudah terdaftar untuk alat ' . $exist['name'] . '.', null, 400);
            }
        }

        $data = [];
        $fields = ['name', 'brand', 'model_type', 'category_id', 'room_id', 'purchase_date', 'warranty_expire', 'price', 'vendor_supplier', 'operational_status', 'serial_number', 'quantity'];
        foreach ($fields as $f) {
            if (isset($input[$f])) {
                if ($f === 'quantity') {
                    $data[$f] = max(1, (int)$input[$f]);
                } else {
                    $data[$f] = $input[$f];
                }
            }
        }

        // Handle upload image baru jika ada (validasi terpusat)
        $upload_field = !empty($_FILES['image']['name']) ? 'image' : (!empty($_FILES['photo']['name']) ? 'photo' : null);
        if ($upload_field) {
            $upload_result = $this->safe_upload($upload_field, 'application/uploads/equipment/', 'image', [
                'encrypt_name'  => true,
                'old_file_path' => isset($item['image_path']) ? $item['image_path'] : null
            ]);
            if (!$upload_result['success']) {
                $this->json_response(false, $upload_result['error'], null, 400);
            }
            if ($upload_result['path']) {
                $data['image_path'] = $upload_result['path'];
            }
        }

        if (!empty($data)) {
            $this->equipment_m->update($id, $data);
            $this->log_audit('UPDATE_EQUIPMENT', 'medical_equipment', $id, $data);
        }

        $this->json_response(true, 'Data alat medis berhasil diperbarui.');
    }

    /**
     * POST /api/equipment/delete/{id} (Soft Delete)
     */
    public function delete($id = null) {
        $this->require_role(['admin']);
        $item = $this->equipment_m->find_by_id($id);
        if (!$item) {
            $this->json_response(false, 'Alat medis tidak ditemukan.', null, 404);
        }

        $this->equipment_m->soft_delete($id);
        $this->log_audit('DELETE_EQUIPMENT', 'medical_equipment', $id, 'Soft delete alkes ' . $item['name']);
        $this->json_response(true, 'Alat medis berhasil dihapus (soft delete).');
    }
}
