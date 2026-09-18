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

        // 1. Data Inventaris per Ruangan
        $this->db->select('r.name as room_name, r.code as room_code, COUNT(e.id) as total_alkes, 
            SUM(CASE WHEN e.operational_status = "operasional" THEN 1 ELSE 0 END) as operasional,
            SUM(CASE WHEN e.operational_status = "rusak_ringan" THEN 1 ELSE 0 END) as rusak_ringan,
            SUM(CASE WHEN e.operational_status = "rusak_berat" THEN 1 ELSE 0 END) as rusak_berat
        ');
        $this->db->from('rooms r');
        $this->db->join('medical_equipment e', 'e.room_id = r.id AND e.is_deleted = 0', 'left');
        $this->db->group_by('r.id');
        $this->db->order_by('total_alkes', 'DESC');
        $by_room = $this->db->get()->result_array();

        // 2. Data Inventaris per Kategori Risiko
        $this->db->select('c.name as category_name, c.risk_level, COUNT(e.id) as total_alkes');
        $this->db->from('equipment_categories c');
        $this->db->join('medical_equipment e', 'e.category_id = c.id AND e.is_deleted = 0', 'left');
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
        $calibration_compliance = $this->db->get()->row_array();

        // 4. Tiket Perbaikan Bulan Ini
        $this->db->select("
            COUNT(*) as total_tickets_month,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_month,
            SUM(CASE WHEN status != 'closed' THEN 1 ELSE 0 END) as active_month
        ");
        $this->db->from('work_orders');
        $this->db->where('MONTH(reported_at) = MONTH(CURRENT_DATE()) AND YEAR(reported_at) = YEAR(CURRENT_DATE())');
        $ticket_recap = $this->db->get()->row_array();

        $this->json_response(true, 'Data rekapitulasi laporan RS.', [
            'by_room'               => $by_room,
            'by_category'           => $by_category,
            'calibration_compliance'=> $calibration_compliance,
            'ticket_recap'          => $ticket_recap
        ]);
    }
}
