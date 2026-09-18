<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting_model extends CI_Model {

    public function get_settings() {
        $row = $this->db->get('app_settings', 1)->row_array();
        if (!$row) {
            $default = [
                'hospital_name'     => 'RS Islam Gondanglegi',
                'hospital_subtitle' => 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)',
                'hospital_address'  => 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang',
                'hospital_phone'    => '(0341) 879222',
                'hospital_city'     => 'Gondanglegi',
                'hospital_logo'     => null
            ];
            $this->db->insert('app_settings', $default);
            return $this->db->get('app_settings', 1)->row_array();
        }
        return $row;
    }

    public function update_settings($data) {
        $existing = $this->db->get('app_settings', 1)->row_array();
        if ($existing) {
            $this->db->where('id', $existing['id']);
            return $this->db->update('app_settings', $data);
        } else {
            return $this->db->insert('app_settings', $data);
        }
    }
}
