<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sparepart_model extends CI_Model {

    public function get_all($search = null) {
        $this->db->select('*');
        $this->db->from('spareparts');
        if ($search) {
            $this->db->group_start();
            $this->db->like('name', $search);
            $this->db->or_like('part_number', $search);
            $this->db->or_like('category', $search);
            $this->db->group_end();
        }
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result_array();
    }

    public function find_by_id($id) {
        return $this->db->get_where('spareparts', ['id' => $id])->row_array();
    }

    public function insert($data) {
        $this->db->insert('spareparts', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('spareparts', $data);
    }
}
