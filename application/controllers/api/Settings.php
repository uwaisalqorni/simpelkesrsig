<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Settings extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Setting_model', 'setting_m');
    }

    /**
     * GET /api/settings
     */
    public function index() {
        if ($this->input->method() === 'post') {
            return $this->update();
        }

        $auth_header = $this->input->get_request_header('Authorization', TRUE);
        $tenant_id = null;

        if (!empty($auth_header)) {
            try {
                $this->authenticate();
                $tenant_id = $this->get_tenant_id();
            } catch (Exception $e) {
                // Abaikan error token untuk fetch awal/publik
            }
        }

        // Jika belum ada tenant_id atau superadmin tanpa token, cek header X-Tenant-Id atau query param
        if (empty($tenant_id)) {
            $header_tenant = $this->input->get_request_header('X-Tenant-Id', TRUE);
            if (!empty($header_tenant) && is_numeric($header_tenant)) {
                $tenant_id = (int)$header_tenant;
            } elseif ($this->input->get('tenant_id')) {
                $tenant_id = (int)$this->input->get('tenant_id');
            } else {
                $tenant_id = 1; // Default ke Tenant 1 (RS Islam Gondanglegi)
            }
        }

        $settings = $this->setting_m->get_settings($tenant_id);
        $this->json_response(true, 'Pengaturan sistem berhasil diambil.', $settings);
    }

    /**
     * POST /api/settings/update (Admin only)
     */
    public function update() {
        $this->require_role(['admin']);
        $tenant_id = $this->get_tenant_id();
        if (empty($tenant_id)) {
            $tenant_id = 1;
        }
        
        $input = $this->input->post();
        if (empty($input)) {
            $input = $this->get_json_input();
        }

        $data = [];
        if (isset($input['hospital_name']))     $data['hospital_name']     = trim($input['hospital_name']);
        if (isset($input['hospital_subtitle'])) $data['hospital_subtitle'] = trim($input['hospital_subtitle']);
        if (isset($input['hospital_address']))  $data['hospital_address']  = trim($input['hospital_address']);
        if (isset($input['hospital_phone']))    $data['hospital_phone']    = trim($input['hospital_phone']);
        if (isset($input['hospital_city']))     $data['hospital_city']     = trim($input['hospital_city']);
        if (isset($input['head_ipsrs_name']))   $data['head_ipsrs_name']   = trim($input['head_ipsrs_name']);
        if (isset($input['head_ipsrs_nip']))    $data['head_ipsrs_nip']    = trim($input['head_ipsrs_nip']);

        // Konfigurasi Notifikasi Telegram Bot
        if (array_key_exists('telegram_bot_token', $input))        $data['telegram_bot_token']        = trim($input['telegram_bot_token']);
        if (array_key_exists('telegram_chat_id', $input))          $data['telegram_chat_id']          = trim($input['telegram_chat_id']);
        if (array_key_exists('telegram_notif_emergency', $input))  $data['telegram_notif_emergency']  = (int)$input['telegram_notif_emergency'];
        if (array_key_exists('telegram_notif_routine', $input))    $data['telegram_notif_routine']    = (int)$input['telegram_notif_routine'];
        if (array_key_exists('telegram_notif_validation', $input)) $data['telegram_notif_validation'] = (int)$input['telegram_notif_validation'];
        if (array_key_exists('telegram_notif_calibration', $input)) $data['telegram_notif_calibration'] = (int)$input['telegram_notif_calibration'];

        // Handle upload logo RS jika ada (validasi terpusat)
        $upload_result = $this->safe_upload('logo', 'application/uploads/logo/', 'logo', [
            'encrypt_name' => true
        ]);
        if (!$upload_result['success'] && $upload_result['error']) {
            $this->json_response(false, $upload_result['error'], null, 400);
        }
        if ($upload_result['path']) {
            $data['hospital_logo'] = $upload_result['path'];
        }

        if (!empty($data)) {
            $this->setting_m->update_settings($data, $tenant_id);
            $this->log_audit('UPDATE_SETTINGS', 'app_settings', $tenant_id, $data);
        }

        $updated = $this->setting_m->get_settings($tenant_id);
        $this->json_response(true, 'Pengaturan Rumah Sakit berhasil disimpan.', $updated);
    }

    /**
     * POST /api/settings/test-telegram (Admin / Super Admin only)
     */
    public function test_telegram() {
        $this->require_role(['admin']);
        $tenant_id = $this->get_tenant_id() ?: 1;
        $current_settings = $this->setting_m->get_settings($tenant_id);

        $input = $this->get_json_input();
        $bot_token = isset($input['bot_token']) ? trim($input['bot_token']) : ($current_settings['telegram_bot_token'] ?? '');
        $chat_id   = isset($input['chat_id']) ? trim($input['chat_id']) : ($current_settings['telegram_chat_id'] ?? '');

        if (empty($bot_token) || empty($chat_id)) {
            $this->json_response(false, 'Token Bot dan Chat ID Grup belum diisi. Silakan masukkan token dan ID grup terlebih dahulu.', null, 400);
        }

        $this->load->library('Telegram_service');
        $hospital_name = $current_settings['hospital_name'] ?? 'SIMPELKES RS';
        $result = $this->telegram_service->test_connection($bot_token, $chat_id, $hospital_name);

        if ($result['success']) {
            $this->json_response(true, 'Pesan uji coba Telegram berhasil dikirim ke grup!', $result['data'] ?? null);
        } else {
            $this->json_response(false, $result['message'], null, 400);
        }
    }
}
