<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Work_order_model extends CI_Model {

    private function _apply_filters($filters = []) {
        if (!empty($filters['tenant_id'])) {
            $this->db->where('wo.tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('wo.status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $this->db->where('wo.priority', $filters['priority']);
        }
        if (!empty($filters['equipment_id'])) {
            $this->db->where('wo.equipment_id', $filters['equipment_id']);
        }
        if (!empty($filters['room_id'])) {
            $this->db->where('e.room_id', $filters['room_id']);
        }
        if (!empty($filters['technician_id'])) {
            $this->db->where('wo.assigned_technician_id', $filters['technician_id']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('wo.ticket_number', $filters['search']);
            $this->db->or_like('e.name', $filters['search']);
            $this->db->or_like('e.asset_code', $filters['search']);
            $this->db->or_like('wo.issue_description', $filters['search']);
            $this->db->group_end();
        }
    }

    public function get_all($filters = [], $limit = null, $offset = null) {
        $this->db->select('wo.*, e.asset_code, e.name as equipment_name, e.brand as equipment_brand, e.model_type as equipment_model, r.name as room_name, r.code as room_code, u_rep.full_name as reported_by_name, u_tech.full_name as technician_name, u_ver.full_name as verifier_name');
        $this->db->from('work_orders wo');
        $this->db->join('medical_equipment e', 'e.id = wo.equipment_id', 'left');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->join('users u_rep', 'u_rep.id = wo.reported_by_user_id', 'left');
        $this->db->join('users u_tech', 'u_tech.id = wo.assigned_technician_id', 'left');
        $this->db->join('users u_ver', 'u_ver.id = wo.verified_by_user_id', 'left');

        $this->_apply_filters($filters);

        $this->db->order_by('wo.id', 'DESC');
        if (!is_null($limit) && (int)$limit > 0) {
            $this->db->limit((int)$limit, (int)$offset);
        }
        return $this->db->get()->result_array();
    }

    public function count_all($filters = []) {
        $this->db->from('work_orders wo');
        $this->db->join('medical_equipment e', 'e.id = wo.equipment_id', 'left');
        $this->_apply_filters($filters);
        return $this->db->count_all_results();
    }

    public function find_by_id($id) {
        $this->db->select('wo.*, e.asset_code, e.serial_number, e.name as equipment_name, e.brand as equipment_brand, e.model_type as equipment_model, e.room_id, r.name as room_name, r.code as room_code, r.building, r.floor, u_rep.full_name as reported_by_name, u_rep.phone as reporter_phone, u_tech.full_name as technician_name, u_tech.phone as technician_phone, u_ver.full_name as verifier_name');
        $this->db->from('work_orders wo');
        $this->db->join('medical_equipment e', 'e.id = wo.equipment_id', 'left');
        $this->db->join('rooms r', 'r.id = e.room_id', 'left');
        $this->db->join('users u_rep', 'u_rep.id = wo.reported_by_user_id', 'left');
        $this->db->join('users u_tech', 'u_tech.id = wo.assigned_technician_id', 'left');
        $this->db->join('users u_ver', 'u_ver.id = wo.verified_by_user_id', 'left');
        $this->db->where('wo.id', $id);
        return $this->db->get()->row_array();
    }

    public function insert($data) {
        $this->db->insert('work_orders', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('work_orders', $data);
    }

    public function generate_ticket_number() {
        $prefix = 'WO-' . date('Ym') . '-';
        $this->db->like('ticket_number', $prefix, 'after');
        $count = $this->db->count_all_results('work_orders');
        $next = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        return $prefix . $next;
    }

    public function get_logs($work_order_id) {
        $this->db->select('wl.*, u.full_name as technician_name');
        $this->db->from('work_order_logs wl');
        $this->db->join('users u', 'u.id = wl.technician_id', 'left');
        $this->db->where('wl.work_order_id', $work_order_id);
        $this->db->order_by('wl.id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function add_log($data) {
        return $this->db->insert('work_order_logs', $data);
    }

    public function get_parts($work_order_id) {
        $this->db->select('wop.*, sp.part_number, sp.name as part_name, (wop.quantity * wop.unit_cost) as total_cost');
        $this->db->from('work_order_parts wop');
        $this->db->join('spareparts sp', 'sp.id = wop.sparepart_id', 'left');
        $this->db->where('wop.work_order_id', $work_order_id);
        return $this->db->get()->result_array();
    }

    public function add_part($data) {
        // Insert work order part
        $this->db->insert('work_order_parts', $data);
        // Kurangi stok di tabel spareparts
        $this->db->set('stock_qty', 'stock_qty - ' . (int)$data['quantity'], FALSE);
        $this->db->where('id', $data['sparepart_id']);
        $this->db->update('spareparts');
        return true;
    }

    public function delete($id) {
        $this->db->trans_start();
        // Kembalikan stock jika ada parts terpakai
        $parts = $this->get_parts($id);
        if (!empty($parts)) {
            foreach ($parts as $p) {
                $this->db->set('stock_qty', 'stock_qty + ' . (int)$p['quantity'], FALSE);
                $this->db->where('id', $p['sparepart_id']);
                $this->db->update('spareparts');
            }
        }
        $this->db->where('work_order_id', $id)->delete('work_order_logs');
        $this->db->where('work_order_id', $id)->delete('work_order_parts');
        $this->db->where('id', $id)->delete('work_orders');
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
