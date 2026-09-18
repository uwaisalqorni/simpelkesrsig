<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Dashboard extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * GET /api/dashboard/summary
     */
    public function summary() {
        $user = $this->authenticate(true);

        // Filter jika user ruangan
        $room_filter = ($user['role'] === 'ruangan') ? $user['room_id'] : null;

        // 1. Statistik Inventaris Alat
        $this->db->select("
            COUNT(*) as total_equipment,
            SUM(CASE WHEN operational_status = 'operasional' THEN 1 ELSE 0 END) as count_operasional,
            SUM(CASE WHEN operational_status = 'rusak_ringan' THEN 1 ELSE 0 END) as count_rusak_ringan,
            SUM(CASE WHEN operational_status = 'rusak_berat' THEN 1 ELSE 0 END) as count_rusak_berat,
            SUM(CASE WHEN operational_status = 'afkir' THEN 1 ELSE 0 END) as count_afkir
        ");
        $this->db->from('medical_equipment');
        $this->db->where('is_deleted', 0);
        if ($room_filter) {
            $this->db->where('room_id', $room_filter);
        }
        $eq_stats = $this->db->get()->row_array();

        $total_eq = (int)$eq_stats['total_equipment'];
        $operasional_eq = (int)$eq_stats['count_operasional'];
        $readiness_rate = $total_eq > 0 ? round(($operasional_eq / $total_eq) * 100, 1) : 0;

        // 2. Statistik Tiket Perbaikan (Work Orders)
        $this->db->select("
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status IN ('reported', 'in_progress', 'waiting_parts', 'vendor_repair') THEN 1 ELSE 0 END) as active_tickets,
            SUM(CASE WHEN status = 'completed_technician' THEN 1 ELSE 0 END) as waiting_verification_tickets,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets,
            SUM(CASE WHEN priority = 'emergency' AND status != 'closed' THEN 1 ELSE 0 END) as emergency_tickets
        ");
        $this->db->from('work_orders wo');
        if ($room_filter) {
            $this->db->join('medical_equipment e', 'e.id = wo.equipment_id');
            $this->db->where('e.room_id', $room_filter);
        }
        $wo_stats = $this->db->get()->row_array();

        // 3. Perhitungan MTTR (Mean Time To Repair) dalam Jam
        // Selisih reported_at sampai closed_at pada tiket tertutup
        $this->db->select("AVG(TIMESTAMPDIFF(MINUTE, reported_at, closed_at)) as avg_repair_minutes");
        $this->db->from('work_orders');
        $this->db->where('closed_at IS NOT NULL');
        if ($room_filter) {
            $this->db->join('medical_equipment e', 'e.id = work_orders.equipment_id');
            $this->db->where('e.room_id', $room_filter);
        }
        $mttr_row = $this->db->get()->row_array();
        $mttr_minutes = !empty($mttr_row['avg_repair_minutes']) ? round($mttr_row['avg_repair_minutes'], 1) : 0;
        $mttr_hours = round($mttr_minutes / 60, 1);

        // 4. Perhitungan Rata-rata Response Time Teknisi (dalam Menit)
        $this->db->select("AVG(TIMESTAMPDIFF(MINUTE, reported_at, response_at)) as avg_response_minutes");
        $this->db->from('work_orders');
        $this->db->where('response_at IS NOT NULL');
        $resp_row = $this->db->get()->row_array();
        $avg_response_minutes = !empty($resp_row['avg_response_minutes']) ? round($resp_row['avg_response_minutes'], 1) : 0;

        // 5. Kalibrasi Kedaluwarsa & Mendekati Jatuh Tempo (Expiring & Expired)
        $this->db->select("
            c.id as calibration_id,
            c.equipment_id,
            c.calibration_date,
            c.valid_until,
            c.certificate_number,
            c.vendor_name,
            c.result,
            e.name as equipment_name,
            e.asset_code,
            e.serial_number,
            e.brand,
            e.model_type,
            r.name as room_name,
            DATEDIFF(c.valid_until, CURRENT_DATE) as days_remaining,
            CASE 
                WHEN c.valid_until < CURRENT_DATE THEN 'expired'
                ELSE 'expiring'
            END as urgency_status
        ");
        $this->db->from('calibration_logs c');
        $this->db->join('(SELECT equipment_id, MAX(id) as max_id FROM calibration_logs GROUP BY equipment_id) latest', 'c.id = latest.max_id');
        $this->db->join('medical_equipment e', 'e.id = c.equipment_id');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->where("c.valid_until <= DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY)");
        $this->db->where('e.is_deleted', 0);
        if ($room_filter) {
            $this->db->where('e.room_id', $room_filter);
        }
        $this->db->order_by('c.valid_until', 'ASC');
        $calibration_alerts = $this->db->get()->result_array();

        $count_expired = 0;
        $count_expiring = 0;
        foreach ($calibration_alerts as $ca) {
            if ($ca['urgency_status'] === 'expired') {
                $count_expired++;
            } else {
                $count_expiring++;
            }
        }

        // 6. Preventive Maintenance Pending / Overdue
        $this->db->select("
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_pm,
            SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_pm
        ");
        $this->db->from('preventive_schedules ps');
        if ($room_filter) {
            $this->db->join('medical_equipment e', 'e.id = ps.equipment_id');
            $this->db->where('e.room_id', $room_filter);
        }
        $pm_stats = $this->db->get()->row_array();

        // 7. Tiket Perbaikan Terbaru
        $this->db->select('wo.*, e.name as equipment_name, e.asset_code, r.name as room_name');
        $this->db->from('work_orders wo');
        $this->db->join('medical_equipment e', 'e.id = wo.equipment_id', 'left');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        if ($room_filter) {
            $this->db->where('e.room_id', $room_filter);
        }
        $this->db->order_by('wo.id', 'DESC');
        $this->db->limit(5);
        $recent_tickets = $this->db->get()->result_array();

        $this->json_response(true, 'Statistik ringkasan dashboard.', [
            'equipment' => [
                'total'        => $total_eq,
                'operasional'  => $operasional_eq,
                'rusak_ringan' => (int)$eq_stats['count_rusak_ringan'],
                'rusak_berat'  => (int)$eq_stats['count_rusak_berat'],
                'afkir'        => (int)$eq_stats['count_afkir'],
                'readiness_pct'=> $readiness_rate
            ],
            'work_orders' => [
                'total'                => (int)$wo_stats['total_tickets'],
                'active'               => (int)$wo_stats['active_tickets'],
                'waiting_verification' => (int)$wo_stats['waiting_verification_tickets'],
                'closed'               => (int)$wo_stats['closed_tickets'],
                'emergency'            => (int)$wo_stats['emergency_tickets']
            ],
            'kpi' => [
                'mttr_hours'           => $mttr_hours,
                'avg_response_minutes' => $avg_response_minutes,
                'expiring_calibrations'=> (int)$count_expiring,
                'expired_calibrations' => (int)$count_expired,
                'total_calibration_alerts' => count($calibration_alerts),
                'pending_pm'           => (int)$pm_stats['pending_pm'],
                'overdue_pm'           => (int)$pm_stats['overdue_pm']
            ],
            'calibration_alerts' => $calibration_alerts,
            'recent_tickets'     => $recent_tickets
        ]);
    }
}
