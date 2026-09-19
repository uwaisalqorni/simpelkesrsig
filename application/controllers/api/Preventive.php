<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Preventive extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Preventive_model', 'preventive_m');
        $this->load->model('Equipment_model', 'equipment_m');
    }

    /**
     * GET /api/preventive/schedules or POST to create schedule
     */
    public function schedules() {
        if ($this->input->method() === 'post') {
            return $this->store();
        }

        $user = $this->authenticate(true);
        $filters = [
            'tenant_id'    => $this->get_tenant_id(),
            'status'       => $this->input->get('status'),
            'equipment_id' => $this->input->get('equipment_id'),
            'room_id'      => $this->input->get('room_id'),
            'search'       => $this->input->get('search')
        ];
        if ($user['role'] === 'ruangan' && empty($filters['room_id'])) {
            $filters['room_id'] = $user['room_id'];
        }

        $page = $this->input->get('page') ? (int)$this->input->get('page') : 1;
        $limit_param = $this->input->get('limit');
        $limit = ($limit_param !== null && $limit_param !== '') ? (int)$limit_param : null;

        if (!is_null($limit) && $limit > 0) {
            $total = $this->preventive_m->count_all($filters);
            $offset = ($page - 1) * $limit;
            $schedules = $this->preventive_m->get_all($filters, $limit, $offset);

            foreach ($schedules as &$sch) {
                $sch['checklist_data']       = !empty($sch['checklist_data']) ? json_decode($sch['checklist_data'], true) : [];
                $sch['inspection_checklist'] = !empty($sch['inspection_checklist']) ? json_decode($sch['inspection_checklist'], true) : [];
                $sch['maintenance_actions']  = !empty($sch['maintenance_actions']) ? json_decode($sch['maintenance_actions'], true) : [];
                $sch['electrical_safety']    = !empty($sch['electrical_safety']) ? json_decode($sch['electrical_safety'], true) : [];
            }

            $this->json_response(true, 'Data jadwal pemeliharaan preventif berhasil diambil.', [
                'items' => $schedules,
                'pagination' => [
                    'page'        => $page,
                    'limit'       => $limit,
                    'total'       => (int)$total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
        } else {
            $schedules = $this->preventive_m->get_all($filters);
            foreach ($schedules as &$sch) {
                $sch['checklist_data']       = !empty($sch['checklist_data']) ? json_decode($sch['checklist_data'], true) : [];
                $sch['inspection_checklist'] = !empty($sch['inspection_checklist']) ? json_decode($sch['inspection_checklist'], true) : [];
                $sch['maintenance_actions']  = !empty($sch['maintenance_actions']) ? json_decode($sch['maintenance_actions'], true) : [];
                $sch['electrical_safety']    = !empty($sch['electrical_safety']) ? json_decode($sch['electrical_safety'], true) : [];
            }
            $this->json_response(true, 'Data jadwal pemeliharaan preventif berhasil diambil.', $schedules);
        }
    }

    /**
     * GET /api/preventive/(:num)
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
        $schedule = $this->preventive_m->find_by_id($id);
        if (!$schedule) {
            $this->json_response(false, 'Jadwal preventif tidak ditemukan.', null, 404);
        }

        // Isolasi multi-tenant: hanya data tenant aktif yang boleh diakses
        if ($this->current_user['role'] !== 'super_admin' && (int)$schedule['tenant_id'] !== (int)$this->get_tenant_id()) {
            $this->json_response(false, 'Jadwal preventif tidak ditemukan.', null, 404);
        }

        $schedule['checklist_data']       = !empty($schedule['checklist_data']) ? json_decode($schedule['checklist_data'], true) : [];
        $schedule['inspection_checklist'] = !empty($schedule['inspection_checklist']) ? json_decode($schedule['inspection_checklist'], true) : [];
        $schedule['maintenance_actions']  = !empty($schedule['maintenance_actions']) ? json_decode($schedule['maintenance_actions'], true) : [];
        $schedule['electrical_safety']    = !empty($schedule['electrical_safety']) ? json_decode($schedule['electrical_safety'], true) : [];
        $this->json_response(true, 'Detail jadwal preventif.', $schedule);
    }

    /**
     * POST /api/preventive/schedules atau POST /api/preventive/store
     */
    public function store() {
        $this->require_role(['admin', 'teknisi']);
        $input = $this->get_json_input();

        if (empty($input['equipment_id']) || empty($input['scheduled_date'])) {
            $this->json_response(false, 'Pilih alat medis dan tentukan tanggal jadwal pemeliharaan.', null, 400);
        }

        $checklist = isset($input['checklist_data']) ? $input['checklist_data'] : [
            'cek_fisik'         => 'Pending',
            'kebocoran_arus'    => 'Pending',
            'uji_fungsi_tombol' => 'Pending',
            'uji_performa'      => 'Pending'
        ];

        $data = [
            'tenant_id'      => $this->get_tenant_id(),
            'equipment_id'   => (int)$input['equipment_id'],
            'scheduled_date' => $input['scheduled_date'],
            'frequency'      => !empty($input['frequency']) ? $input['frequency'] : 'quarterly',
            'status'         => 'pending',
            'checklist_data' => json_encode($checklist),
            'notes'          => isset($input['notes']) ? trim($input['notes']) : null
        ];

        $insert_id = $this->preventive_m->insert($data);
        $this->log_audit('CREATE_PREVENTIVE', 'preventive_schedules', $insert_id, $data);

        $this->json_response(true, 'Jadwal pemeliharaan preventif berhasil dibuat.', ['id' => $insert_id], 201);
    }

    /**
     * PUT/POST /api/preventive/update/{id}
     */
    public function update($id = null) {
        $this->require_role(['admin', 'teknisi']);

        $schedule = $this->preventive_m->find_by_id($id);
        if (!$schedule) {
            $this->json_response(false, 'Jadwal preventif tidak ditemukan.', null, 404);
        }

        if ($this->current_user['role'] !== 'super_admin' && (int)$schedule['tenant_id'] !== (int)$this->get_tenant_id()) {
            $this->json_response(false, 'Jadwal preventif tidak ditemukan.', null, 404);
        }

        $input = $this->get_json_input();

        $update_data = [];

        // Jika belum selesai, boleh ubah alat, tanggal, dan frekuensi
        if ($schedule['status'] !== 'done') {
            if (!empty($input['equipment_id'])) {
                $update_data['equipment_id'] = (int)$input['equipment_id'];
            }
            if (!empty($input['scheduled_date'])) {
                $update_data['scheduled_date'] = $input['scheduled_date'];
            }
            if (!empty($input['frequency'])) {
                $update_data['frequency'] = $input['frequency'];
            }
        }

        if (isset($input['notes'])) {
            $update_data['notes'] = trim($input['notes']);
        }

        if (empty($update_data)) {
            $this->json_response(false, 'Tidak ada data perubahan yang dikirim.', null, 400);
        }

        $this->preventive_m->update($id, $update_data);
        $this->log_audit('UPDATE_PREVENTIVE', 'preventive_schedules', $id, $update_data);

        $this->json_response(true, 'Jadwal pemeliharaan preventif berhasil diperbarui.');
    }

    /**
     * DELETE/POST /api/preventive/delete/{id}
     */
    public function delete($id = null) {
        $this->require_role(['admin', 'teknisi']);

        $schedule = $this->preventive_m->find_by_id($id);
        if (!$schedule) {
            $this->json_response(false, 'Jadwal preventif tidak ditemukan.', null, 404);
        }

        if ($this->current_user['role'] !== 'super_admin' && (int)$schedule['tenant_id'] !== (int)$this->get_tenant_id()) {
            $this->json_response(false, 'Jadwal preventif tidak ditemukan.', null, 404);
        }

        // Proteksi: Jadwal yang sudah selesai (done) tidak dapat dihapus
        if ($schedule['status'] === 'done') {
            $this->json_response(false, 'Jadwal pemeliharaan yang sudah berstatus Selesai (Done) tidak dapat dihapus demi kepatuhan audit & riwayat pemeliharaan alkes.', null, 400);
        }

        $this->preventive_m->delete($id);
        $this->log_audit('DELETE_PREVENTIVE', 'preventive_schedules', $id, [
            'equipment_id'   => $schedule['equipment_id'],
            'scheduled_date' => $schedule['scheduled_date'],
            'equipment_name' => $schedule['equipment_name']
        ]);

        $this->json_response(true, 'Jadwal pemeliharaan preventif berhasil dihapus.');
    }

    /**
     * POST /api/preventive/complete/{id}
     */
    public function complete($id = null) {
        $this->require_role(['admin', 'teknisi']);
        $schedule = $this->preventive_m->find_by_id($id);
        if (!$schedule) {
            $this->json_response(false, 'Jadwal preventif tidak ditemukan.', null, 404);
        }

        if ($this->current_user['role'] !== 'super_admin' && (int)$schedule['tenant_id'] !== (int)$this->get_tenant_id()) {
            $this->json_response(false, 'Jadwal preventif tidak ditemukan.', null, 404);
        }

        // Handle both multipart/form-data and JSON payloads
        $raw_json = $this->get_json_input();
        $is_post_form = !empty($_POST);

        $execution_start_at = $is_post_form ? $this->input->post('execution_start_at') : ($raw_json['execution_start_at'] ?? null);
        $execution_end_at   = $is_post_form ? $this->input->post('execution_end_at') : ($raw_json['execution_end_at'] ?? null);
        $sp_number          = $is_post_form ? $this->input->post('sp_number') : ($raw_json['sp_number'] ?? null);
        $executor_type      = $is_post_form ? $this->input->post('executor_type') : ($raw_json['executor_type'] ?? 'internal');
        $activity_type      = $is_post_form ? $this->input->post('activity_type') : ($raw_json['activity_type'] ?? 'pemeliharaan');
        $final_condition    = $is_post_form ? $this->input->post('final_condition') : ($raw_json['final_condition'] ?? 'laik_pakai');
        $technician_name    = $is_post_form ? $this->input->post('technician_name') : ($raw_json['technician_name'] ?? null);
        $supervisor_name    = $is_post_form ? $this->input->post('supervisor_name') : ($raw_json['supervisor_name'] ?? null);
        $notes              = $is_post_form ? $this->input->post('notes') : ($raw_json['notes'] ?? '');

        // Section 1: Checklist pemantauan fungsi
        $raw_inspection = $is_post_form ? $this->input->post('inspection_checklist') : ($raw_json['inspection_checklist'] ?? null);
        if (is_string($raw_inspection)) {
            $inspection_checklist = json_decode($raw_inspection, true) ?: [];
        } else {
            $inspection_checklist = is_array($raw_inspection) ? $raw_inspection : [];
        }

        // Section 2: Checklist tindakan preventif
        $raw_actions = $is_post_form ? $this->input->post('maintenance_actions') : ($raw_json['maintenance_actions'] ?? null);
        if (is_string($raw_actions)) {
            $maintenance_actions = json_decode($raw_actions, true) ?: [];
        } else {
            $maintenance_actions = is_array($raw_actions) ? $raw_actions : [];
        }

        // Section 3: Pengukuran keselamatan listrik
        $raw_safety = $is_post_form ? $this->input->post('electrical_safety') : ($raw_json['electrical_safety'] ?? null);
        if (is_string($raw_safety)) {
            $electrical_safety = json_decode($raw_safety, true) ?: [];
        } else {
            $electrical_safety = is_array($raw_safety) ? $raw_safety : [];
        }

        // Handle upload foto stiker / bukti pemeliharaan jika ada
        $photo_path = null;
        if (!empty($_FILES['photo_proof']['name'])) {
            $upload_res = $this->safe_upload('photo_proof', 'uploads/preventive/', 'image', [
                'file_prefix' => 'pm_' . $id . '_'
            ]);
            if (!$upload_res['success'] && $upload_res['error']) {
                $this->json_response(false, $upload_res['error'], null, 400);
            }
            $photo_path = $upload_res['path'];
        }

        if (empty($technician_name)) {
            $technician_name = !empty($this->current_user['full_name']) ? $this->current_user['full_name'] : 'Teknisi IPSRS';
        }

        $valid_conditions = ['laik_pakai', 'rusak_ringan', 'rusak_berat'];
        if (!in_array($final_condition, $valid_conditions)) {
            $final_condition = 'laik_pakai';
        }

        $valid_executors = ['internal', 'external'];
        if (!in_array($executor_type, $valid_executors)) {
            $executor_type = 'internal';
        }

        // Format datetime strings jika perlu
        $formatted_start = !empty($execution_start_at) ? date('Y-m-d H:i:s', strtotime($execution_start_at)) : date('Y-m-d H:i:s');
        $formatted_end   = !empty($execution_end_at) ? date('Y-m-d H:i:s', strtotime($execution_end_at)) : date('Y-m-d H:i:s');

        $update_data = [
            'status'                    => 'done',
            'execution_start_at'        => $formatted_start,
            'execution_end_at'          => $formatted_end,
            'sp_number'                 => !empty($sp_number) ? trim($sp_number) : null,
            'executor_type'             => $executor_type,
            'activity_type'             => !empty($activity_type) ? trim($activity_type) : 'pemeliharaan',
            'final_condition'           => $final_condition,
            'technician_name'           => trim($technician_name),
            'supervisor_name'           => !empty($supervisor_name) ? trim($supervisor_name) : null,
            'inspection_checklist'      => json_encode($inspection_checklist),
            'maintenance_actions'       => json_encode($maintenance_actions),
            'electrical_safety'         => json_encode($electrical_safety),
            'checklist_data'            => json_encode($inspection_checklist), // Backward compatibility
            'notes'                     => trim($notes),
            'executed_by_technician_id' => $this->current_user['id'],
            'completed_at'              => date('Y-m-d H:i:s')
        ];

        if ($photo_path) {
            $update_data['photo_proof_path'] = $photo_path;
        }

        $this->preventive_m->update($id, $update_data);

        // Sinkronisasi status operasional alat medis
        $target_eq_status = ($final_condition === 'laik_pakai') ? 'operasional' : $final_condition;
        $this->equipment_m->update($schedule['equipment_id'], [
            'operational_status' => $target_eq_status
        ]);

        // Auto schedule interval berikutnya
        $equipment = $this->equipment_m->find_by_id($schedule['equipment_id']);
        $interval_days = !empty($equipment['default_maintenance_interval_days']) ? (int)$equipment['default_maintenance_interval_days'] : 90;
        $next_date = date('Y-m-d', strtotime("+{$interval_days} days"));

        $next_schedule_id = $this->preventive_m->insert([
            'tenant_id'      => $this->get_tenant_id(),
            'equipment_id'   => $schedule['equipment_id'],
            'scheduled_date' => $next_date,
            'frequency'      => !empty($schedule['frequency']) ? $schedule['frequency'] : 'quarterly',
            'status'         => 'pending',
            'checklist_data' => json_encode([
                'cek_fisik'         => 'Pending',
                'kebocoran_arus'    => 'Pending',
                'uji_fungsi_tombol' => 'Pending',
                'uji_performa'      => 'Pending'
            ])
        ]);

        $this->log_audit('COMPLETE_PREVENTIVE', 'preventive_schedules', $id, "Preventive maintenance selesai. Kondisi: {$final_condition}, Jadwal berikutnya: {$next_date}");

        $this->json_response(true, 'Pemeliharaan preventif berhasil diselesaikan. Status alat diperbarui dan jadwal berikutnya otomatis dibuat.', [
            'next_schedule_id' => $next_schedule_id,
            'next_date'        => $next_date,
            'final_condition'  => $final_condition,
            'photo_proof_path' => $photo_path
        ]);
    }
}
