<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Room_model extends CI_Model {

    public function get_all($active_only = true) {
        $this->db->select('r.*, COUNT(e.id) as total_equipment');
        $this->db->from('rooms r');
        $this->db->join('medical_equipment e', 'e.room_id = r.id AND e.is_deleted = 0', 'left');
        if ($active_only) {
            $this->db->where('r.is_active', 1);
        }
        $this->db->group_by('r.id');
        $this->db->order_by('r.name', 'ASC');
        return $this->db->get()->result_array();
    }

    public function find_by_id($id) {
        return $this->db->get_where('rooms', ['id' => $id])->row_array();
    }

    public function insert($data) {
        $this->db->insert('rooms', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('rooms', $data);
    }
}
