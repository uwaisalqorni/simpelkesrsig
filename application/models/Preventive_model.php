<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Preventive_model extends CI_Model {

    private function _apply_filters($filters = []) {
        if (!empty($filters['tenant_id'])) {
            $this->db->where('ps.tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('ps.status', $filters['status']);
        }
        if (!empty($filters['room_id'])) {
            $this->db->where('e.room_id', $filters['room_id']);
        }
        if (!empty($filters['equipment_id'])) {
            $this->db->where('ps.equipment_id', $filters['equipment_id']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $this->db->group_start();
            $this->db->like('e.name', $search);
            $this->db->or_like('e.asset_code', $search);
            $this->db->or_like('r.name', $search);
            $this->db->or_like('ps.notes', $search);
            $this->db->or_like('u.full_name', $search);
            $this->db->group_end();
        }
    }

    public function get_all($filters = [], $limit = null, $offset = null) {
        $this->db->select('ps.*, e.asset_code, e.name as equipment_name, e.brand, e.brand as equipment_brand, e.model_type, e.model_type as equipment_model, e.serial_number, r.name as room_name, r.code as room_code, COALESCE(NULLIF(ps.technician_name, ""), u.full_name) as technician_name');
        $this->db->from('preventive_schedules ps');
        $this->db->join('medical_equipment e', 'e.id = ps.equipment_id', 'left');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->join('users u', 'u.id = ps.executed_by_technician_id', 'left');

        $this->_apply_filters($filters);

        $this->db->order_by('ps.scheduled_date', 'ASC');
        if (!is_null($limit) && (int)$limit > 0) {
            $this->db->limit((int)$limit, (int)$offset);
        }
        return $this->db->get()->result_array();
    }

    public function count_all($filters = []) {
        $this->db->from('preventive_schedules ps');
        $this->db->join('medical_equipment e', 'e.id = ps.equipment_id', 'left');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->join('users u', 'u.id = ps.executed_by_technician_id', 'left');
        $this->_apply_filters($filters);
        return $this->db->count_all_results();
    }

    public function find_by_id($id) {
        $this->db->select('ps.*, e.asset_code, e.name as equipment_name, e.brand, e.brand as equipment_brand, e.model_type, e.model_type as equipment_model, e.serial_number, e.room_id, r.name as room_name, COALESCE(NULLIF(ps.technician_name, ""), u.full_name) as technician_name');
        $this->db->from('preventive_schedules ps');
        $this->db->join('medical_equipment e', 'e.id = ps.equipment_id', 'left');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->join('users u', 'u.id = ps.executed_by_technician_id', 'left');
        $this->db->where('ps.id', $id);
        return $this->db->get()->row_array();
    }

    public function insert($data) {
        $this->db->insert('preventive_schedules', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('preventive_schedules', $data);
    }

    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete('preventive_schedules');
    }
}
