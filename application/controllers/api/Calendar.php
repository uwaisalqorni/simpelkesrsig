<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Calendar extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Preventive_model', 'preventive_m');
        $this->load->model('Calibration_model', 'calibration_m');
    }

    /**
     * GET /api/calendar/events
     * Mengambil jadwal gabungan PM dan Kalibrasi untuk visualisasi kalender
     */
    public function events() {
        $user = $this->authenticate(true);

        $start_date = $this->input->get('start');
        $end_date   = $this->input->get('end');
        $type       = $this->input->get('type') ?: 'all';
        $room_id    = $this->input->get('room_id');

        // Jika user adalah role ruangan, otomatis kunci ke room_id miliknya
        if ($user['role'] === 'ruangan') {
            $room_id = $user['room_id'];
        }

        // Default rentang tanggal jika tidak disediakan: 2 bulan (bulan lalu sd bulan depan)
        if (empty($start_date)) {
            $start_date = date('Y-m-01', strtotime('-1 month'));
        }
        if (empty($end_date)) {
            $end_date = date('Y-m-t', strtotime('+1 month'));
        }

        $events = [];

        // 1. Fetch Preventive Maintenance Events
        if ($type === 'all' || $type === 'preventive') {
            $this->db->select('ps.*, e.name as equipment_name, e.asset_code, e.serial_number, e.brand, e.model_type, e.room_id, r.name as room_name, u.full_name as technician_name');
            $this->db->from('preventive_schedules ps');
            $this->db->join('medical_equipment e', 'e.id = ps.equipment_id', 'left');
            $this->db->join('rooms r', 'r.id = e.room_id', 'left');
            $this->db->join('users u', 'u.id = ps.executed_by_technician_id', 'left');
            $this->db->where("ps.scheduled_date BETWEEN '{$start_date}' AND '{$end_date}'");
            if (!empty($room_id)) {
                $this->db->where('e.room_id', (int)$room_id);
            }
            $this->db->order_by('ps.scheduled_date', 'ASC');
            $pm_list = $this->db->get()->result_array();

            $today = date('Y-m-d');
            foreach ($pm_list as $pm) {
                $is_done = ($pm['status'] === 'done');
                $is_overdue = (!$is_done && $pm['scheduled_date'] < $today);

                $status_code = $is_done ? 'done' : ($is_overdue ? 'overdue' : 'scheduled');
                $status_label = $is_done ? 'Selesai' : ($is_overdue ? 'Terlambat (Overdue)' : 'Terjadwal');
                $color = $is_done ? 'emerald' : ($is_overdue ? 'rose' : 'amber');

                $events[] = [
                    'id'               => 'pm-' . $pm['id'],
                    'raw_id'           => (int)$pm['id'],
                    'type'             => 'preventive',
                    'type_label'       => 'Pemeliharaan Preventif',
                    'title'            => 'PM: ' . $pm['equipment_name'],
                    'date'             => $pm['scheduled_date'],
                    'status'           => $status_code,
                    'status_label'     => $status_label,
                    'color'            => $color,
                    'equipment_id'     => (int)$pm['equipment_id'],
                    'equipment_name'   => $pm['equipment_name'],
                    'asset_code'       => $pm['asset_code'],
                    'serial_number'    => $pm['serial_number'],
                    'brand'            => $pm['brand'],
                    'model_type'       => $pm['model_type'],
                    'room_id'          => (int)$pm['room_id'],
                    'room_name'        => $pm['room_name'],
                    'frequency'        => $pm['frequency'],
                    'frequency_label'  => $this->_format_frequency($pm['frequency']),
                    'technician_name'  => $pm['technician_name'],
                    'notes'            => $pm['notes'],
                    'completed_at'     => $pm['completed_at']
                ];
            }
        }

        // 2. Fetch Calibration Logs (Jatuh Tempo Kalibrasi)
        if ($type === 'all' || $type === 'calibration') {
            $this->db->select('c.*, e.name as equipment_name, e.asset_code, e.serial_number, e.brand, e.model_type, e.room_id, r.name as room_name, DATEDIFF(c.valid_until, CURRENT_DATE) as days_remaining');
            $this->db->from('calibration_logs c');
            $this->db->join('medical_equipment e', 'e.id = c.equipment_id', 'left');
            $this->db->join('rooms r', 'r.id = e.room_id', 'left');
            $this->db->where("c.valid_until BETWEEN '{$start_date}' AND '{$end_date}'");
            if (!empty($room_id)) {
                $this->db->where('e.room_id', (int)$room_id);
            }
            $this->db->order_by('c.valid_until', 'ASC');
            $cal_list = $this->db->get()->result_array();

            foreach ($cal_list as $cal) {
                $days = (int)$cal['days_remaining'];
                $is_expired = ($days <= 0);
                $is_expiring = (!$is_expired && $days <= 30);

                $status_code = $is_expired ? 'expired' : ($is_expiring ? 'expiring' : 'valid');
                $status_label = $is_expired ? 'Kedaluwarsa' : ($is_expiring ? 'Mendekati Tempo' : 'Laik Pakai');
                $color = $is_expired ? 'rose' : ($is_expiring ? 'amber' : 'sky');

                $events[] = [
                    'id'                 => 'cal-' . $cal['id'],
                    'raw_id'             => (int)$cal['id'],
                    'type'               => 'calibration',
                    'type_label'         => 'Jatuh Tempo Kalibrasi',
                    'title'              => 'Kalibrasi: ' . $cal['equipment_name'],
                    'date'               => $cal['valid_until'],
                    'status'             => $status_code,
                    'status_label'       => $status_label,
                    'color'              => $color,
                    'equipment_id'       => (int)$cal['equipment_id'],
                    'equipment_name'     => $cal['equipment_name'],
                    'asset_code'         => $cal['asset_code'],
                    'serial_number'      => $cal['serial_number'],
                    'brand'              => $cal['brand'],
                    'model_type'         => $cal['model_type'],
                    'room_id'            => (int)$cal['room_id'],
                    'room_name'          => $cal['room_name'],
                    'certificate_number' => $cal['certificate_number'],
                    'vendor_name'        => $cal['vendor_name'],
                    'calibration_date'   => $cal['calibration_date'],
                    'valid_until'        => $cal['valid_until'],
                    'result'             => $cal['result'],
                    'days_remaining'     => $days,
                    'notes'              => $cal['notes']
                ];
            }
        }

        // Urutkan event berdasarkan tanggal ascending
        usort($events, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        // Hitung ringkasan indikator
        $stats = [
            'total_events'   => count($events),
            'pm_total'       => 0,
            'pm_done'        => 0,
            'pm_overdue'     => 0,
            'pm_scheduled'   => 0,
            'cal_total'      => 0,
            'cal_expired'    => 0,
            'cal_expiring'   => 0,
            'cal_valid'      => 0
        ];

        foreach ($events as $ev) {
            if ($ev['type'] === 'preventive') {
                $stats['pm_total']++;
                if ($ev['status'] === 'done') $stats['pm_done']++;
                else if ($ev['status'] === 'overdue') $stats['pm_overdue']++;
                else $stats['pm_scheduled']++;
            } else if ($ev['type'] === 'calibration') {
                $stats['cal_total']++;
                if ($ev['status'] === 'expired') $stats['cal_expired']++;
                else if ($ev['status'] === 'expiring') $stats['cal_expiring']++;
                else $stats['cal_valid']++;
            }
        }

        $this->json_response(true, 'Daftar agenda kalender pemeliharaan berhasil diambil.', [
            'range'  => [
                'start' => $start_date,
                'end'   => $end_date
            ],
            'stats'  => $stats,
            'events' => $events
        ]);
    }

    private function _format_frequency($freq) {
        $map = [
            'monthly'     => 'Bulanan (Monthly)',
            'quarterly'   => '3 Bulan (Quarterly)',
            'semi_annual' => '6 Bulan (Semi-Annual)',
            'annual'      => 'Tahunan (Annual)'
        ];
        return isset($map[$freq]) ? $map[$freq] : ucfirst($freq);
    }
}
