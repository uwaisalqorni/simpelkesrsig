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
                $sch['checklist_data'] = !empty($sch['checklist_data']) ? json_decode($sch['checklist_data'], true) : [];
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
                $sch['checklist_data'] = !empty($sch['checklist_data']) ? json_decode($sch['checklist_data'], true) : [];
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

        $schedule['checklist_data'] = !empty($schedule['checklist_data']) ? json_decode($schedule['checklist_data'], true) : [];
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

        $input = $this->get_json_input();
        $checklist = isset($input['checklist_data']) ? $input['checklist_data'] : [];
        $notes     = isset($input['notes']) ? trim($input['notes']) : '';

        $update_data = [
            'status'                    => 'done',
            'checklist_data'            => json_encode($checklist),
            'notes'                     => $notes,
            'executed_by_technician_id' => $this->current_user['id'],
            'completed_at'              => date('Y-m-d H:i:s')
        ];

        $this->preventive_m->update($id, $update_data);

        // Auto schedule interval berikutnya
        $equipment = $this->equipment_m->find_by_id($schedule['equipment_id']);
        $interval_days = !empty($equipment['default_maintenance_interval_days']) ? (int)$equipment['default_maintenance_interval_days'] : 90;
        $next_date = date('Y-m-d', strtotime("+{$interval_days} days"));

        $next_schedule_id = $this->preventive_m->insert([
            'equipment_id'   => $schedule['equipment_id'],
            'scheduled_date' => $next_date,
            'frequency'      => $schedule['frequency'],
            'status'         => 'pending',
            'checklist_data' => json_encode([
                'cek_fisik'         => 'Pending',
                'kebocoran_arus'    => 'Pending',
                'uji_fungsi_tombol' => 'Pending',
                'uji_performa'      => 'Pending'
            ])
        ]);

        $this->log_audit('COMPLETE_PREVENTIVE', 'preventive_schedules', $id, "Preventive maintenance selesai, jadwal berikutnya: {$next_date}");

        $this->json_response(true, 'Pemeliharaan preventif berhasil diselesaikan. Jadwal berikutnya otomatis dibuat untuk tanggal ' . $next_date . '.', [
            'next_schedule_id' => $next_schedule_id,
            'next_date'        => $next_date
        ]);
    }
}
