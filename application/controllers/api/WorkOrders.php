<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class WorkOrders extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Work_order_model', 'work_order_m');
        $this->load->model('Equipment_model', 'equipment_m');
    }

    /**
     * GET /api/work-orders
     */
    public function index() {
        if ($this->input->method() === 'post') {
            return $this->store();
        }

        $user = $this->authenticate(true);
        $filters = [
            'tenant_id'     => $this->get_tenant_id(),
            'status'        => $this->input->get('status'),
            'priority'      => $this->input->get('priority'),
            'equipment_id'  => $this->input->get('equipment_id'),
            'room_id'       => $this->input->get('room_id'),
            'technician_id' => $this->input->get('technician_id'),
            'search'        => $this->input->get('search')
        ];

        // Jika user adalah ruangan, batasi list hanya ke ruangan miliknya jika tidak filter
        if ($user['role'] === 'ruangan') {
            $filters['room_id'] = $user['room_id'];
        }

        $page = (int)$this->input->get('page');
        $limit = (int)$this->input->get('limit');
        $total = $this->work_order_m->count_all($filters);

        if ($limit > 0) {
            $page = max(1, $page);
            $offset = ($page - 1) * $limit;
            $tickets = $this->work_order_m->get_all($filters, $limit, $offset);
            $total_pages = ceil($total / $limit);
        } else {
            $tickets = $this->work_order_m->get_all($filters);
            $page = 1;
            $limit = $total > 0 ? $total : 10;
            $total_pages = 1;
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode([
                'success'    => true,
                'message'    => 'Data tiket perbaikan berhasil diambil.',
                'data'       => $tickets,
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
     * GET /api/work-orders/show/{id}
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
        $ticket = $this->work_order_m->find_by_id($id);
        if (!$ticket) {
            $this->json_response(false, 'Tiket perbaikan tidak ditemukan.', null, 404);
        }

        $ticket['logs'] = $this->work_order_m->get_logs($id);
        $ticket['parts'] = $this->work_order_m->get_parts($id);

        $this->json_response(true, 'Detail tiket perbaikan.', $ticket);
    }

    /**
     * POST /api/work-orders (Pelaporan Tiket Baru)
     */
    public function store() {
        $user = $this->authenticate(true);
        
        // Handle input (bisa form-data jika ada upload foto, atau JSON)
        $equipment_id = $this->input->post('equipment_id');
        $issue_description = $this->input->post('issue_description');
        $priority = $this->input->post('priority') ?: 'medium';

        if (empty($equipment_id)) {
            $raw = $this->get_json_input();
            $equipment_id = isset($raw['equipment_id']) ? $raw['equipment_id'] : null;
            $issue_description = isset($raw['issue_description']) ? $raw['issue_description'] : null;
            $priority = isset($raw['priority']) ? $raw['priority'] : 'medium';
        }

        if (empty($equipment_id) || empty($issue_description)) {
            $this->json_response(false, 'Pilih alat medis dan isi deskripsi kendala kerusakan.', null, 400);
        }

        $equipment = $this->equipment_m->find_by_id($equipment_id);
        if (!$equipment) {
            $this->json_response(false, 'Alat medis yang dipilih tidak valid.', null, 400);
        }

        // Handle upload foto kendala jika ada (validasi terpusat)
        $photo_path = null;
        $upload_result = $this->safe_upload('issue_photo', 'uploads/tickets/', 'image_gif', [
            'file_prefix' => 'wo'
        ]);
        if (!$upload_result['success'] && $upload_result['error']) {
            $this->json_response(false, $upload_result['error'], null, 400);
        }
        $photo_path = $upload_result['path'];

        $ticket_number = $this->work_order_m->generate_ticket_number();
        $ticket_data = [
            'tenant_id'            => $this->get_tenant_id(),
            'ticket_number'        => $ticket_number,
            'equipment_id'         => (int)$equipment_id,
            'reported_by_user_id'  => $user['id'],
            'issue_description'    => trim($issue_description),
            'issue_photo_path'     => $photo_path,
            'priority'             => $priority,
            'status'               => 'reported',
            'reported_at'          => date('Y-m-d H:i:s')
        ];

        $insert_id = $this->work_order_m->insert($ticket_data);

        // Update status operasional alat ke 'rusak_ringan' jika sebelumnya operasional
        if ($equipment['operational_status'] === 'operasional') {
            $new_eq_status = ($priority === 'emergency' || $priority === 'high') ? 'rusak_berat' : 'rusak_ringan';
            $this->equipment_m->update($equipment_id, ['operational_status' => $new_eq_status]);
        }

        // Catat initial log
        $this->work_order_m->add_log([
            'work_order_id'  => $insert_id,
            'technician_id'  => $user['id'],
            'action_taken'   => 'Laporan kerusakan dibuat oleh unit kerja (' . $user['full_name'] . '). Menunggu respon teknisi IPSRS.',
            'current_status' => 'reported'
        ]);

        // Kirim Notifikasi Telegram Otomatis ke Grup Teknisi Faskes
        try {
            $this->load->library('Telegram_service');
            $this->load->model('Room_model', 'room_m');
            $room = !empty($equipment['room_id']) ? $this->room_m->find_by_id($equipment['room_id']) : ['name' => 'Unit Pelayanan'];
            $notif_ticket = $ticket_data;
            $notif_ticket['id'] = $insert_id;
            $notif_ticket['complaint'] = $issue_description;
            $notif_ticket['reporter_name'] = $user['full_name'];
            $this->telegram_service->notify_ticket_created($notif_ticket, $equipment, $room, $ticket_data['tenant_id']);
        } catch (Exception $e) {
            log_message('error', 'Telegram notification error: ' . $e->getMessage());
        }

        $this->log_audit('CREATE_TICKET', 'work_orders', $insert_id, "Tiket {$ticket_number} dilaporkan");

        $this->json_response(true, 'Tiket perbaikan berhasil dilaporkan dengan nomor ' . $ticket_number . '.', [
            'id' => $insert_id,
            'ticket_number' => $ticket_number
        ], 201);
    }

    /**
     * POST /api/work-orders/assign/{id}
     * Admin/Teknisi mendisposisi teknisi penanggungjawab
     */
    public function assign($id = null) {
        $this->require_role(['admin', 'teknisi']);
        $ticket = $this->work_order_m->find_by_id($id);
        if (!$ticket) {
            $this->json_response(false, 'Tiket tidak ditemukan.', null, 404);
        }

        $input = $this->get_json_input();
        $technician_id = isset($input['technician_id']) ? (int)$input['technician_id'] : $this->current_user['id'];

        $tech = $this->db->get_where('users', ['id' => $technician_id, 'role' => 'teknisi'])->row_array();
        if (!$tech) {
            $this->json_response(false, 'Teknisi yang dipilih tidak valid.', null, 400);
        }

        $update_data = [
            'assigned_technician_id' => $technician_id,
            'status' => ($ticket['status'] === 'reported') ? 'in_progress' : $ticket['status']
        ];
        if (empty($ticket['response_at'])) {
            $update_data['response_at'] = date('Y-m-d H:i:s');
        }

        $this->work_order_m->update($id, $update_data);

        $this->work_order_m->add_log([
            'work_order_id'  => $id,
            'technician_id'  => $technician_id,
            'action_taken'   => 'Tiket ditugaskan kepada teknisi: ' . $tech['full_name'],
            'current_status' => $update_data['status']
        ]);

        $this->log_audit('ASSIGN_TICKET', 'work_orders', $id, "Tiket ditugaskan ke {$tech['full_name']}");
        $this->json_response(true, 'Tiket berhasil didisposisikan kepada ' . $tech['full_name'] . '.');
    }

    /**
     * POST /api/work-orders/progress/{id}
     * Teknisi mencatat tindakan progres perbaikan
     */
    public function progress($id = null) {
        $this->require_role(['admin', 'teknisi']);
        $ticket = $this->work_order_m->find_by_id($id);
        if (!$ticket) {
            $this->json_response(false, 'Tiket tidak ditemukan.', null, 404);
        }

        $action_taken = $this->input->post('action_taken');
        $new_status   = $this->input->post('status') ?: $ticket['status'];
        $vendor_name  = $this->input->post('vendor_name');
        $repair_cost  = $this->input->post('repair_cost');

        if (empty($action_taken)) {
            $raw = $this->get_json_input();
            $action_taken = isset($raw['action_taken']) ? $raw['action_taken'] : null;
            $new_status   = isset($raw['status']) ? $raw['status'] : $ticket['status'];
            $vendor_name  = isset($raw['vendor_name']) ? $raw['vendor_name'] : null;
            $repair_cost  = isset($raw['repair_cost']) ? $raw['repair_cost'] : null;
        }

        if (empty($action_taken)) {
            $this->json_response(false, 'Tindakan teknisi wajib dicatat.', null, 400);
        }

        // Upload foto progress jika ada (validasi terpusat)
        $photo_path = null;
        $upload_result = $this->safe_upload('progress_photo', 'uploads/progress/', 'image_gif', [
            'file_prefix' => 'prog'
        ]);
        if (!$upload_result['success'] && $upload_result['error']) {
            $this->json_response(false, $upload_result['error'], null, 400);
        }
        $photo_path = $upload_result['path'];

        // Catat ke work_order_logs
        $this->work_order_m->add_log([
            'work_order_id'       => $id,
            'technician_id'       => $this->current_user['id'],
            'action_taken'        => trim($action_taken),
            'current_status'      => $new_status,
            'photo_progress_path' => $photo_path
        ]);

        // Update status work order
        $update_data = [
            'status' => $new_status,
            'action_summary' => trim($action_taken)
        ];
        if (!empty($vendor_name)) $update_data['vendor_name'] = trim($vendor_name);
        if ($repair_cost !== null && $repair_cost !== '') $update_data['repair_cost'] = (float)$repair_cost;

        if ($new_status === 'completed_technician') {
            // Jika teknisi menyatakan selesai, status alat diubah jadi operasional
            $this->equipment_m->update($ticket['equipment_id'], ['operational_status' => 'operasional']);

            // Kirim Notifikasi Telegram bahwa alat siap diuji coba oleh unit ruangan
            try {
                $this->load->library('Telegram_service');
                $this->load->model('Room_model', 'room_m');
                $equipment = $this->equipment_m->find_by_id($ticket['equipment_id']);
                $room = !empty($ticket['room_id']) ? $this->room_m->find_by_id($ticket['room_id']) : ['name' => 'Unit Pelayanan'];
                $ticket['action_taken'] = trim($action_taken);
                $this->telegram_service->notify_ticket_validation_needed($ticket, $equipment, $room, $this->current_user['full_name'], $ticket['tenant_id']);
            } catch (Exception $e) {
                log_message('error', 'Telegram validation notification error: ' . $e->getMessage());
            }
        }

        $this->work_order_m->update($id, $update_data);
        $this->log_audit('UPDATE_PROGRESS', 'work_orders', $id, "Status tiket diubah ke {$new_status}");

        $this->json_response(true, 'Progress pengerjaan teknisi berhasil disimpan.');
    }

    /**
     * POST /api/work-orders/add-part/{id}
     * Menambahkan pemakaian sparepart pada tiket
     */
    public function add_part($id = null) {
        $this->require_role(['admin', 'teknisi']);
        $ticket = $this->work_order_m->find_by_id($id);
        if (!$ticket) {
            $this->json_response(false, 'Tiket tidak ditemukan.', null, 404);
        }

        $input = $this->get_json_input();
        $sparepart_id = isset($input['sparepart_id']) ? (int)$input['sparepart_id'] : 0;
        $quantity     = isset($input['quantity']) ? (int)$input['quantity'] : 1;

        $sparepart = $this->db->get_where('spareparts', ['id' => $sparepart_id])->row_array();
        if (!$sparepart) {
            $this->json_response(false, 'Suku cadang tidak ditemukan.', null, 404);
        }

        if ($sparepart['stock_qty'] < $quantity) {
            $this->json_response(false, 'Stok sparepart tidak mencukupi. Sisa stok: ' . $sparepart['stock_qty'], null, 400);
        }

        $this->work_order_m->add_part([
            'work_order_id' => $id,
            'sparepart_id'  => $sparepart_id,
            'quantity'      => $quantity,
            'unit_cost'     => $sparepart['unit_cost']
        ]);

        $this->work_order_m->add_log([
            'work_order_id'  => $id,
            'technician_id'  => $this->current_user['id'],
            'action_taken'   => 'Penggantian suku cadang: ' . $sparepart['name'] . " (Qty: {$quantity})",
            'current_status' => $ticket['status']
        ]);

        $this->json_response(true, 'Suku cadang berhasil ditambahkan ke tiket perbaikan.');
    }

    /**
     * POST /api/work-orders/verify/{id}
     * User Ruangan atau Admin melakukan uji fungsi & serah terima (Digital Signature)
     */
    public function verify($id = null) {
        $user = $this->authenticate(true);
        $ticket = $this->work_order_m->find_by_id($id);
        if (!$ticket) {
            $this->json_response(false, 'Tiket tidak ditemukan.', null, 404);
        }

        // Proteksi Ruangan: user role ruangan hanya boleh memverifikasi tiket unit miliknya
        if ($user['role'] === 'ruangan' && !empty($ticket['room_id']) && $ticket['room_id'] != $user['room_id']) {
            $this->json_response(false, 'Anda hanya berwenang memvalidasi alat medis di unit ruangan Anda.', null, 403);
        }

        $input = $this->get_json_input();
        $is_accepted = isset($input['is_accepted']) ? (bool)$input['is_accepted'] : true;
        $signature_data = isset($input['signature_data']) ? $input['signature_data'] : null; // Base64 PNG
        $notes = isset($input['notes']) ? trim($input['notes']) : '';

        // Simpan tanda tangan jika ada base64
        $signature_path = null;
        if (!empty($signature_data) && strpos($signature_data, 'data:image') === 0) {
            $upload_dir = FCPATH . 'uploads/signatures/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $dataParts = explode(',', $signature_data);
            $decoded = base64_decode($dataParts[1]);
            $fileName = 'sig_' . $id . '_' . time() . '.png';
            $filePath = $upload_dir . $fileName;
            file_put_contents($filePath, $decoded);
            $signature_path = 'uploads/signatures/' . $fileName;
        }

        if ($is_accepted) {
            $update_data = [
                'status'              => 'closed',
                'closed_at'           => date('Y-m-d H:i:s'),
                'room_signature_path' => $signature_path ?: $ticket['room_signature_path'],
                'verified_by_user_id' => $user['id'],
                'verified_at'         => date('Y-m-d H:i:s')
            ];
            $this->work_order_m->update($id, $update_data);
            $this->equipment_m->update($ticket['equipment_id'], ['operational_status' => 'operasional']);

            $this->work_order_m->add_log([
                'work_order_id'  => $id,
                'technician_id'  => $user['id'],
                'action_taken'   => 'Uji fungsi dinyatakan NORMAL & serah terima divalidasi oleh unit kerja (' . $user['full_name'] . '). Tiket ditutup resmi.',
                'current_status' => 'closed'
            ]);

            // Kirim Notifikasi Telegram bahwa alat resmi operasional kembali
            try {
                $this->load->library('Telegram_service');
                $this->load->model('Room_model', 'room_m');
                $equipment = $this->equipment_m->find_by_id($ticket['equipment_id']);
                $room = !empty($ticket['room_id']) ? $this->room_m->find_by_id($ticket['room_id']) : ['name' => 'Unit Pelayanan'];
                $this->telegram_service->notify_ticket_resolved($ticket, $equipment, $room, $user['full_name'], $ticket['tenant_id']);
            } catch (Exception $e) {
                log_message('error', 'Telegram resolved notification error: ' . $e->getMessage());
            }

            $this->log_audit('VERIFY_AND_CLOSE', 'work_orders', $id, 'Tiket perbaikan diverifikasi dan ditutup');
            $this->json_response(true, 'Tiket perbaikan berhasil diverifikasi dan diserahterimakan secara resmi.');
        } else {
            // Validasi: Catatan kendala wajib diisi saat menolak
            if (empty($notes)) {
                $this->json_response(false, 'Silakan tuliskan catatan kendala yang masih ditemukan agar teknisi dapat menindaklanjutinya.', null, 400);
            }

            // Ditolak / belum normal -> kembalikan ke in_progress
            $update_data = [
                'status'           => 'in_progress',
                'rejection_reason' => $notes
            ];
            $this->work_order_m->update($id, $update_data);

            $this->work_order_m->add_log([
                'work_order_id'  => $id,
                'technician_id'  => $user['id'],
                'action_taken'   => 'Uji fungsi dinyatakan BELUM SESUAI oleh unit kerja (' . $user['full_name'] . '). Catatan penolakan: ' . $notes,
                'current_status' => 'in_progress'
            ]);

            $this->log_audit('REJECT_REOPEN', 'work_orders', $id, 'Hasil perbaikan ditolak unit: ' . $notes);
            $this->json_response(true, 'Tiket berhasil dikembalikan ke teknisi untuk perbaikan lanjutan.');
        }
    }

    /**
     * POST/PUT /api/work-orders/update/{id}
     */
    public function update($id = null) {
        $user = $this->authenticate(true);
        $ticket = $this->work_order_m->find_by_id($id);
        if (!$ticket) {
            $this->json_response(false, 'Tiket perbaikan tidak ditemukan.', null, 404);
        }

        // Jika user ruangan, batasi hanya tiket dari ruangannya atau yang ia buat
        if ($user['role'] === 'ruangan' && $ticket['reported_by_user_id'] != $user['id'] && $ticket['room_id'] != $user['room_id']) {
            $this->json_response(false, 'Anda tidak memiliki hak akses untuk mengubah tiket ini.', null, 403);
        }

        $raw = $this->get_json_input();
        $issue_description = isset($raw['issue_description']) ? trim($raw['issue_description']) : $this->input->post('issue_description');
        $priority = isset($raw['priority']) ? trim($raw['priority']) : $this->input->post('priority');
        $equipment_id = isset($raw['equipment_id']) ? (int)$raw['equipment_id'] : (int)$this->input->post('equipment_id');

        $update_data = [];
        if (!empty($issue_description)) $update_data['issue_description'] = $issue_description;
        if (!empty($priority)) $update_data['priority'] = $priority;
        if (!empty($equipment_id)) $update_data['equipment_id'] = $equipment_id;

        if (empty($update_data)) {
            $this->json_response(false, 'Tidak ada data perubahan yang dikirim.', null, 400);
        }

        $this->work_order_m->update($id, $update_data);
        $this->log_audit('UPDATE_WORK_ORDER', 'work_orders', $id, $update_data);

        $updated_ticket = $this->work_order_m->find_by_id($id);
        $this->json_response(true, 'Tiket perbaikan berhasil diperbarui.', $updated_ticket);
    }

    /**
     * POST/DELETE /api/work-orders/delete/{id}
     */
    public function delete($id = null) {
        $user = $this->authenticate(true);
        $ticket = $this->work_order_m->find_by_id($id);
        if (!$ticket) {
            $this->json_response(false, 'Tiket perbaikan tidak ditemukan.', null, 404);
        }

        // ATURAN KRUSIAL: Jika status closed / selesai, user TIDAK BISA hapus
        if ($ticket['status'] === 'closed') {
            $this->json_response(false, 'Tiket yang sudah berstatus Selesai (Closed) tidak dapat dihapus.', null, 400);
        }

        // Jika user adalah ruangan, hanya boleh hapus tiket yang dilaporkan sendiri
        if ($user['role'] === 'ruangan' && $ticket['reported_by_user_id'] != $user['id']) {
            $this->json_response(false, 'Anda hanya dapat menghapus tiket yang Anda laporkan sendiri.', null, 403);
        }

        $this->work_order_m->delete($id);
        $this->log_audit('DELETE_WORK_ORDER', 'work_orders', $id, ['ticket_number' => $ticket['ticket_number']]);

        $this->json_response(true, 'Tiket perbaikan berhasil dihapus.');
    }
}
