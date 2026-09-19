<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Reports extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * GET /api/reports/recap
     * Rekapitulasi eksekutif lengkap
     */
    public function recap() {
        $this->authenticate(true);
        $tenant_id = $this->get_tenant_id();

        // 1. Data Inventaris per Ruangan
        $this->db->select('r.name as room_name, r.code as room_code, COUNT(e.id) as total_alkes, 
            SUM(CASE WHEN e.operational_status = "operasional" THEN 1 ELSE 0 END) as operasional,
            SUM(CASE WHEN e.operational_status = "rusak_ringan" THEN 1 ELSE 0 END) as rusak_ringan,
            SUM(CASE WHEN e.operational_status = "rusak_berat" THEN 1 ELSE 0 END) as rusak_berat
        ');
        $this->db->from('rooms r');
        $this->db->join('medical_equipment e', 'e.room_id = r.id AND e.is_deleted = 0', 'left');
        if ($tenant_id) {
            $this->db->where('r.tenant_id', $tenant_id);
        }
        $this->db->group_by('r.id');
        $this->db->order_by('total_alkes', 'DESC');
        $by_room = $this->db->get()->result_array();

        // 2. Data Inventaris per Kategori Risiko
        $this->db->select('c.name as category_name, c.risk_level, COUNT(e.id) as total_alkes');
        $this->db->from('equipment_categories c');
        $this->db->join('medical_equipment e', 'e.category_id = c.id AND e.is_deleted = 0', 'left');
        if ($tenant_id) {
            $this->db->where('e.tenant_id', $tenant_id);
        }
        $this->db->group_by('c.id');
        $by_category = $this->db->get()->result_array();

        // 3. Kalibrasi Kepatuhan
        $this->db->select("
            COUNT(*) as total_calibrated,
            SUM(CASE WHEN valid_until >= CURRENT_DATE THEN 1 ELSE 0 END) as laik_aktif,
            SUM(CASE WHEN valid_until < CURRENT_DATE THEN 1 ELSE 0 END) as expired,
            SUM(CASE WHEN valid_until BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
        ");
        $this->db->from('calibration_logs');
        if ($tenant_id) {
            $this->db->where('tenant_id', $tenant_id);
        }
        $calibration_compliance = $this->db->get()->row_array();

        // 4. Tiket Perbaikan Bulan Ini
        $this->db->select("
            COUNT(*) as total_tickets_month,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_month,
            SUM(CASE WHEN status != 'closed' THEN 1 ELSE 0 END) as active_month
        ");
        $this->db->from('work_orders');
        $this->db->where('MONTH(reported_at) = MONTH(CURRENT_DATE()) AND YEAR(reported_at) = YEAR(CURRENT_DATE())');
        if ($tenant_id) {
            $this->db->where('tenant_id', $tenant_id);
        }
        $ticket_recap = $this->db->get()->row_array();

        // 5. Tren Kerusakan & Penyelesaian Bulanan (6 Bulan Terakhir)
        $where_tenant_wo = $tenant_id ? "AND tenant_id = {$tenant_id}" : "";
        $trends_query = $this->db->query("
            SELECT 
                DATE_FORMAT(reported_at, '%Y-%m') as period_ym,
                DATE_FORMAT(reported_at, '%b %Y') as month_label,
                COUNT(*) as total_reported,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as total_closed,
                SUM(CASE WHEN priority = 'emergency' THEN 1 ELSE 0 END) as emergency_count
            FROM work_orders
            WHERE reported_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH) {$where_tenant_wo}
            GROUP BY period_ym
            ORDER BY period_ym ASC
        ");
        $trends_db = $trends_query->result_array();

        $monthly_trends = [];
        for ($i = 5; $i >= 0; $i--) {
            $time = strtotime("-{$i} month");
            $ym = date('Y-m', $time);
            $label = date('M Y', $time);
            $monthly_trends[$ym] = [
                'period_ym'       => $ym,
                'month_label'     => $label,
                'total_reported'  => 0,
                'total_closed'    => 0,
                'emergency_count' => 0
            ];
        }
        foreach ($trends_db as $t) {
            if (isset($monthly_trends[$t['period_ym']])) {
                $monthly_trends[$t['period_ym']]['total_reported']  = (int)$t['total_reported'];
                $monthly_trends[$t['period_ym']]['total_closed']    = (int)$t['total_closed'];
                $monthly_trends[$t['period_ym']]['emergency_count'] = (int)$t['emergency_count'];
            }
        }
        $monthly_trends = array_values($monthly_trends);

        // 6. Top 5 Unit / Ruangan dengan Kerusakan Tertinggi
        $where_tenant_r = $tenant_id ? "WHERE r.tenant_id = {$tenant_id}" : "";
        $top_rooms_query = $this->db->query("
            SELECT 
                r.id as room_id,
                r.name as room_name,
                r.code as room_code,
                COUNT(wo.id) as ticket_count,
                COUNT(DISTINCT e.id) as affected_equipment_count,
                (SELECT COUNT(*) FROM medical_equipment WHERE room_id = r.id AND is_deleted = 0) as total_equipment
            FROM rooms r
            JOIN medical_equipment e ON e.room_id = r.id
            JOIN work_orders wo ON wo.equipment_id = e.id
            {$where_tenant_r}
            GROUP BY r.id
            ORDER BY ticket_count DESC
            LIMIT 5
        ");
        $top_damaged_rooms = $top_rooms_query->result_array();

        // 7. Kepatuhan Respon & SPM Tanggap Darurat Elektromedis
        $spm_query = $this->db->query("
            SELECT 
                COUNT(*) as total_with_response,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, reported_at, response_at) <= 15 THEN 1 ELSE 0 END) as fast_under_15m,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, reported_at, response_at) > 15 AND TIMESTAMPDIFF(MINUTE, reported_at, response_at) <= 30 THEN 1 ELSE 0 END) as standard_15_30m,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, reported_at, response_at) > 30 AND TIMESTAMPDIFF(MINUTE, reported_at, response_at) <= 60 THEN 1 ELSE 0 END) as moderate_30_60m,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, reported_at, response_at) > 60 THEN 1 ELSE 0 END) as late_over_60m,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, reported_at, response_at)), 1) as avg_response_minutes
            FROM work_orders
            WHERE response_at IS NOT NULL {$where_tenant_wo}
        ");
        $spm_compliance = $spm_query->row_array();

        $this->json_response(true, 'Data rekapitulasi laporan RS.', [
            'by_room'                => $by_room,
            'by_category'            => $by_category,
            'calibration_compliance' => $calibration_compliance,
            'ticket_recap'           => $ticket_recap,
            'monthly_trends'         => $monthly_trends,
            'top_damaged_rooms'      => $top_damaged_rooms,
            'spm_compliance'         => $spm_compliance
        ]);
    }
}
