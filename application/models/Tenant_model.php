<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tenant_model extends CI_Model {

    public function get_all($only_active = false) {
        $this->db->select('t.*, 
            (SELECT COUNT(*) FROM medical_equipment WHERE tenant_id = t.id AND is_deleted = 0) as total_equipment,
            (SELECT COUNT(*) FROM users WHERE tenant_id = t.id AND is_active = 1) as total_users,
            (SELECT COUNT(*) FROM work_orders wo JOIN medical_equipment e ON e.id = wo.equipment_id WHERE e.tenant_id = t.id AND wo.status NOT IN ("closed", "cancelled")) as active_tickets
        ');
        $this->db->from('tenants t');
        if ($only_active) {
            $this->db->where('t.is_active', 1);
        }
        $this->db->order_by('t.id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function find_by_id($id) {
        $this->db->select('t.*');
        $this->db->from('tenants t');
        $this->db->where('t.id', $id);
        return $this->db->get()->row_array();
    }

    public function find_by_code($code) {
        $this->db->where('code', $code);
        return $this->db->get('tenants')->row_array();
    }

    public function find_by_slug($slug) {
        $this->db->where('slug', $slug);
        return $this->db->get('tenants')->row_array();
    }

    public function insert($data) {
        $this->db->insert('tenants', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('tenants', $data);
    }

    /**
     * Ringkasan Global Multi-Tenant untuk Super Admin
     */
    public function get_global_overview() {
        $tenants = $this->get_all();

        $total_tenants = count($tenants);
        $total_equipment_all = 0;
        $total_active_tickets_all = 0;
        $tenant_summaries = [];

        foreach ($tenants as $t) {
            $tid = (int)$t['id'];
            $total_equipment_all += (int)$t['total_equipment'];
            $total_active_tickets_all += (int)$t['active_tickets'];

            // Kesiapan per RS
            $this->db->select("
                COUNT(*) as total,
                SUM(CASE WHEN operational_status = 'operasional' THEN 1 ELSE 0 END) as operasional
            ");
            $this->db->from('medical_equipment');
            $this->db->where('tenant_id', $tid);
            $this->db->where('is_deleted', 0);
            $eq_row = $this->db->get()->row_array();

            $total_eq = (int)($eq_row['total'] ?? 0);
            $op_eq    = (int)($eq_row['operasional'] ?? 0);
            $rate     = $total_eq > 0 ? round(($op_eq / $total_eq) * 100, 1) : 0;

            $tenant_summaries[] = [
                'id'                => $t['id'],
                'code'              => $t['code'],
                'name'              => $t['name'],
                'hospital_subtitle' => $t['hospital_subtitle'],
                'city'              => $t['city'],
                'logo_path'         => $t['logo_path'],
                'is_active'         => (int)$t['is_active'],
                'total_equipment'   => $total_eq,
                'operasional'       => $op_eq,
                'readiness_rate'    => $rate,
                'active_tickets'    => (int)$t['active_tickets'],
                'total_users'       => (int)$t['total_users']
            ];
        }

        return [
            'total_tenants'            => $total_tenants,
            'total_equipment_all'      => $total_equipment_all,
            'total_active_tickets_all' => $total_active_tickets_all,
            'tenants'                  => $tenant_summaries
        ];
    }
}
