<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function find_by_username($username) {
        $this->db->select('u.*, r.name as room_name, r.code as room_code, t.name as tenant_name, t.code as tenant_code, t.logo_path as tenant_logo');
        $this->db->from('users u');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->join('tenants t', 't.id = u.tenant_id', 'left');
        $this->db->where('u.username', $username);
        return $this->db->get()->row_array();
    }

    public function find_by_id($id) {
        $this->db->select('u.id, u.username, u.full_name, u.role, u.room_id, u.tenant_id, u.phone, u.is_active, u.created_at, r.name as room_name, r.code as room_code, t.name as tenant_name, t.code as tenant_code, t.logo_path as tenant_logo');
        $this->db->from('users u');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->join('tenants t', 't.id = u.tenant_id', 'left');
        $this->db->where('u.id', $id);
        return $this->db->get()->row_array();
    }

    public function get_technicians($tenant_id = null) {
        $this->db->select('id, full_name, username, phone, tenant_id');
        $this->db->from('users');
        $this->db->where('role', 'teknisi');
        $this->db->where('is_active', 1);
        if ($tenant_id) {
            $this->db->where('tenant_id', $tenant_id);
        }
        return $this->db->get()->result_array();
    }

    public function get_all($role = null, $tenant_id = null) {
        $this->db->select('u.id, u.username, u.full_name, u.role, u.room_id, u.tenant_id, u.phone, u.is_active, u.created_at, r.name as room_name, t.name as tenant_name, t.code as tenant_code');
        $this->db->from('users u');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->join('tenants t', 't.id = u.tenant_id', 'left');
        if ($role) {
            $this->db->where('u.role', $role);
        }
        if ($tenant_id) {
            $this->db->where('u.tenant_id', $tenant_id);
        }
        $this->db->order_by('u.id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function insert($data) {
        $this->db->insert('users', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('users', $data);
    }
}
