<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Equipment_model extends CI_Model {

    public function get_all($filters = [], $limit = null, $offset = null) {
        $this->db->select('e.*, r.name as room_name, r.code as room_code, c.name as category_name, c.risk_level, c.default_maintenance_interval_days, cl.calibration_date, cl.valid_until, cl.certificate_number, cl.calibration_result');
        $this->db->from('medical_equipment e');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->join('equipment_categories c', 'c.id = e.category_id', 'left');
        $this->db->join('(SELECT c1.equipment_id, c1.calibration_date, c1.valid_until, c1.certificate_number, c1.result as calibration_result FROM calibration_logs c1 INNER JOIN (SELECT equipment_id, MAX(id) as max_id FROM calibration_logs GROUP BY equipment_id) c2 ON c1.id = c2.max_id) cl', 'cl.equipment_id = e.id', 'left');
        $this->db->where('e.is_deleted', 0);

        if (!empty($filters['room_id'])) {
            $this->db->where('e.room_id', $filters['room_id']);
        }
        if (!empty($filters['category_id'])) {
            $this->db->where('e.category_id', $filters['category_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('e.operational_status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('e.name', $filters['search']);
            $this->db->or_like('e.asset_code', $filters['search']);
            $this->db->or_like('e.serial_number', $filters['search']);
            $this->db->or_like('e.brand', $filters['search']);
            $this->db->group_end();
        }

        $this->db->order_by('e.id', 'DESC');
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        return $this->db->get()->result_array();
    }

    public function count_all($filters = []) {
        $this->db->from('medical_equipment e');
        $this->db->where('e.is_deleted', 0);

        if (!empty($filters['room_id'])) {
            $this->db->where('e.room_id', $filters['room_id']);
        }
        if (!empty($filters['category_id'])) {
            $this->db->where('e.category_id', $filters['category_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('e.operational_status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('e.name', $filters['search']);
            $this->db->or_like('e.asset_code', $filters['search']);
            $this->db->or_like('e.serial_number', $filters['search']);
            $this->db->or_like('e.brand', $filters['search']);
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    public function find_by_id($id) {
        $this->db->select('e.*, r.name as room_name, r.code as room_code, r.building, r.floor, c.name as category_name, c.risk_level, c.default_maintenance_interval_days, cl.calibration_date, cl.valid_until, cl.certificate_number, cl.calibration_result');
        $this->db->from('medical_equipment e');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->join('equipment_categories c', 'c.id = e.category_id', 'left');
        $this->db->join('(SELECT c1.equipment_id, c1.calibration_date, c1.valid_until, c1.certificate_number, c1.result as calibration_result FROM calibration_logs c1 INNER JOIN (SELECT equipment_id, MAX(id) as max_id FROM calibration_logs GROUP BY equipment_id) c2 ON c1.id = c2.max_id) cl', 'cl.equipment_id = e.id', 'left');
        $this->db->where('e.id', $id);
        $this->db->where('e.is_deleted', 0);
        return $this->db->get()->row_array();
    }

    public function find_by_asset_code($code) {
        $this->db->select('e.*, r.name as room_name, r.code as room_code, c.name as category_name, c.risk_level, cl.calibration_date, cl.valid_until, cl.certificate_number, cl.calibration_result');
        $this->db->from('medical_equipment e');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->join('equipment_categories c', 'c.id = e.category_id', 'left');
        $this->db->join('(SELECT c1.equipment_id, c1.calibration_date, c1.valid_until, c1.certificate_number, c1.result as calibration_result FROM calibration_logs c1 INNER JOIN (SELECT equipment_id, MAX(id) as max_id FROM calibration_logs GROUP BY equipment_id) c2 ON c1.id = c2.max_id) cl', 'cl.equipment_id = e.id', 'left');
        $this->db->where('e.asset_code', $code);
        $this->db->where('e.is_deleted', 0);
        return $this->db->get()->row_array();
    }

    public function insert($data) {
        $this->db->insert('medical_equipment', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('medical_equipment', $data);
    }

    public function soft_delete($id) {
        $this->db->where('id', $id);
        return $this->db->update('medical_equipment', ['is_deleted' => 1]);
    }

    public function generate_asset_code($category_id) {
        $year = date('Y');
        $this->db->like('asset_code', "EQ-{$year}-", 'after');
        $count = $this->db->count_all_results('medical_equipment');
        $next_number = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        return "EQ-{$year}-{$next_number}";
    }
}
