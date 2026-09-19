<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting_model extends CI_Model {

    public function get_settings($tenant_id = 1) {
        $tenant_id = !empty($tenant_id) ? (int)$tenant_id : 1;
        $row = $this->db->get_where('app_settings', ['tenant_id' => $tenant_id], 1)->row_array();
        
        if (!$row) {
            // Coba ambil dari data tabel tenants
            $tenant = $this->db->get_where('tenants', ['id' => $tenant_id])->row_array();
            if ($tenant) {
                $default = [
                    'tenant_id'         => $tenant_id,
                    'hospital_name'     => $tenant['name'],
                    'hospital_subtitle' => !empty($tenant['hospital_subtitle']) ? $tenant['hospital_subtitle'] : 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)',
                    'hospital_address'  => !empty($tenant['address']) ? $tenant['address'] : '',
                    'hospital_phone'    => !empty($tenant['phone']) ? $tenant['phone'] : '',
                    'hospital_city'     => !empty($tenant['city']) ? $tenant['city'] : '',
                    'hospital_logo'     => !empty($tenant['logo_path']) ? $tenant['logo_path'] : null,
                    'telegram_bot_token'        => null,
                    'telegram_chat_id'          => null,
                    'telegram_notif_emergency'  => 1,
                    'telegram_notif_routine'    => 1,
                    'telegram_notif_validation' => 1,
                    'telegram_notif_calibration'=> 1
                ];
            } else {
                $default = [
                    'tenant_id'         => $tenant_id,
                    'hospital_name'     => 'SIMPELKES Rumah Sakit',
                    'hospital_subtitle' => 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)',
                    'hospital_address'  => '',
                    'hospital_phone'    => '',
                    'hospital_city'     => '',
                    'hospital_logo'     => null,
                    'telegram_bot_token'        => null,
                    'telegram_chat_id'          => null,
                    'telegram_notif_emergency'  => 1,
                    'telegram_notif_routine'    => 1,
                    'telegram_notif_validation' => 1,
                    'telegram_notif_calibration'=> 1
                ];
            }
            $this->db->insert('app_settings', $default);
            return $this->db->get_where('app_settings', ['tenant_id' => $tenant_id], 1)->row_array();
        }
        return $row;
    }

    public function update_settings($data, $tenant_id = 1) {
        $tenant_id = !empty($tenant_id) ? (int)$tenant_id : 1;
        $existing = $this->db->get_where('app_settings', ['tenant_id' => $tenant_id], 1)->row_array();
        
        if ($existing) {
            $this->db->where('tenant_id', $tenant_id);
            $this->db->update('app_settings', $data);
        } else {
            $data['tenant_id'] = $tenant_id;
            $this->db->insert('app_settings', $data);
        }

        // Sinkronisasi kembali ke tabel tenants agar data faskes konsisten
        $tenant_update = [];
        if (isset($data['hospital_name']))     $tenant_update['name'] = $data['hospital_name'];
        if (isset($data['hospital_subtitle'])) $tenant_update['hospital_subtitle'] = $data['hospital_subtitle'];
        if (isset($data['hospital_address']))  $tenant_update['address'] = $data['hospital_address'];
        if (isset($data['hospital_phone']))    $tenant_update['phone'] = $data['hospital_phone'];
        if (isset($data['hospital_city']))     $tenant_update['city'] = $data['hospital_city'];
        if (isset($data['hospital_logo']))     $tenant_update['logo_path'] = $data['hospital_logo'];

        if (!empty($tenant_update)) {
            $this->db->where('id', $tenant_id);
            $this->db->update('tenants', $tenant_update);
        }

        return true;
    }
}
