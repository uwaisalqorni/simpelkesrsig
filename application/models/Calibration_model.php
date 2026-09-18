<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Calibration_model extends CI_Model {

    private function _apply_filters($filters = []) {
        if (!empty($filters['equipment_id'])) {
            $this->db->where('c.equipment_id', $filters['equipment_id']);
        }
        if (!empty($filters['result'])) {
            $this->db->where('c.result', $filters['result']);
        }
        if (!empty($filters['room_id'])) {
            $this->db->where('e.room_id', $filters['room_id']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('e.name', $filters['search']);
            $this->db->or_like('e.asset_code', $filters['search']);
            $this->db->or_like('c.certificate_number', $filters['search']);
            $this->db->or_like('c.vendor_name', $filters['search']);
            $this->db->or_like('r.name', $filters['search']);
            $this->db->group_end();
        }
    }

    public function get_all($filters = [], $limit = null, $offset = null) {
        $this->db->select('c.*, e.asset_code, e.name as equipment_name, e.serial_number, e.room_id, r.name as room_name, DATEDIFF(c.valid_until, CURRENT_DATE) as days_remaining');
        $this->db->from('calibration_logs c');
        $this->db->join('medical_equipment e', 'e.id = c.equipment_id', 'left');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');

        $this->_apply_filters($filters);

        $this->db->order_by('c.valid_until', 'ASC');
        if (!is_null($limit) && (int)$limit > 0) {
            $this->db->limit((int)$limit, (int)$offset);
        }
        return $this->db->get()->result_array();
    }

    public function count_all($filters = []) {
        $this->db->from('calibration_logs c');
        $this->db->join('medical_equipment e', 'e.id = c.equipment_id', 'left');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->_apply_filters($filters);
        return $this->db->count_all_results();
    }

    public function find_by_id($id) {
        $this->db->select('c.*, e.asset_code, e.name as equipment_name, e.serial_number, e.room_id, r.name as room_name, DATEDIFF(c.valid_until, CURRENT_DATE) as days_remaining');
        $this->db->from('calibration_logs c');
        $this->db->join('medical_equipment e', 'e.id = c.equipment_id', 'left');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->where('c.id', $id);
        return $this->db->get()->row_array();
    }

    public function get_expiring($days = 30) {
        $this->db->select('c.*, e.asset_code, e.name as equipment_name, e.serial_number, r.name as room_name, DATEDIFF(c.valid_until, CURRENT_DATE) as days_remaining');
        $this->db->from('calibration_logs c');
        $this->db->join('medical_equipment e', 'e.id = c.equipment_id', 'left');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->where("c.valid_until <= DATE_ADD(CURRENT_DATE, INTERVAL {$days} DAY)");
        $this->db->order_by('c.valid_until', 'ASC');
        return $this->db->get()->result_array();
    }

    public function insert($data) {
        $this->db->insert('calibration_logs', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('calibration_logs', $data);
    }

    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete('calibration_logs');
    }
}
