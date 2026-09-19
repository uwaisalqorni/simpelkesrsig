<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Telegram_service
 * Library terpusat untuk komunikasi dengan Telegram Bot API
 * Multi-Tenant ready: Mendukung token & chat_id grup per faskes
 */
class Telegram_service {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Setting_model', 'setting_m');
    }

    /**
     * Kirim pesan teks ke Telegram
     */
    public function send_message($message, $tenant_id = 1, $custom_chat_id = null, $custom_token = null) {
        $tenant_id = !empty($tenant_id) ? (int)$tenant_id : 1;
        $settings = $this->CI->setting_m->get_settings($tenant_id);

        $bot_token = !empty($custom_token) ? trim($custom_token) : ($settings['telegram_bot_token'] ?? null);
        $chat_id   = !empty($custom_chat_id) ? trim($custom_chat_id) : ($settings['telegram_chat_id'] ?? null);

        if (empty($bot_token) || empty($chat_id)) {
            return [
                'success' => false,
                'message' => 'Telegram Bot Token atau Chat ID belum dikonfigurasi untuk faskes ini.'
            ];
        }

        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $payload = [
            'chat_id'                  => $chat_id,
            'text'                     => $message,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true
        ];

        return $this->execute_curl($url, $payload);
    }

    /**
     * Uji koneksi pengiriman pesan Telegram
     */
    public function test_connection($bot_token, $chat_id, $hospital_name = 'SIMPELKES RS') {
        if (empty($bot_token) || empty($chat_id)) {
            return [
                'success' => false,
                'message' => 'Token Bot dan Chat ID Grup wajib diisi.'
            ];
        }

        $time = date('d-m-Y H:i:s');
        $msg = "<b>🔔 UJI KONEKSI TELEGRAM BOT BERHASIL</b>\n";
        $msg .= "🏥 <b>{$hospital_name}</b>\n";
        $msg .= "------------------------------------------\n";
        $msg .= "Status: <b>Terhubung Normal (Online)</b>\n";
        $msg .= "Waktu Uji: <code>{$time} WIB</code>\n";
        $msg .= "Sistem: <b>SIMPELKES IPSRS Multi-Tenant</b>\n";
        $msg .= "------------------------------------------\n";
        $msg .= "<i>Bot siap menerima notifikasi laporan darurat, perbaikan alkes, dan validasi unit.</i>";

        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $payload = [
            'chat_id'                  => $chat_id,
            'text'                     => $msg,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true
        ];

        return $this->execute_curl($url, $payload);
    }

    /**
     * Notifikasi Tiket Baru Dibuat (Emergency atau Rutin)
     */
    public function notify_ticket_created($ticket, $equipment, $room, $tenant_id = 1) {
        $settings = $this->CI->setting_m->get_settings($tenant_id);
        $is_emergency = ($ticket['priority'] === 'emergency');

        // Cek toggle pengaturan
        if ($is_emergency && empty($settings['telegram_notif_emergency'])) {
            return false;
        }
        if (!$is_emergency && empty($settings['telegram_notif_routine'])) {
            return false;
        }

        $h_name = htmlspecialchars($settings['hospital_name'] ?? 'SIMPELKES RS');
        $eq_name = htmlspecialchars($equipment['name'] ?? 'Alat Medis');
        $eq_code = htmlspecialchars($equipment['code'] ?? '-');
        $room_name = htmlspecialchars($room['name'] ?? 'Ruangan');
        $reporter = htmlspecialchars($ticket['reporter_name'] ?? 'Petugas Ruangan');
        $complaint = htmlspecialchars($ticket['complaint'] ?? '-');
        $ticket_num = htmlspecialchars($ticket['ticket_number'] ?? 'WO-' . $ticket['id']);
        $time = date('d-m-Y H:i', strtotime($ticket['created_at'] ?? 'now'));

        if ($is_emergency) {
            $msg = "🚨 <b>PERINGATAN ALAT MEDIS DARURAT (EMERGENCY)</b>\n";
            $msg .= "🏥 <b>{$h_name}</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "No. Tiket   : <code>{$ticket_num}</code>\n";
            $msg .= "Alat Medis : <b>{$eq_name}</b> (<code>{$eq_code}</code>)\n";
            $msg .= "Unit/Ruang : 📍 <b>{$room_name}</b>\n";
            $msg .= "Pelapor    : 👤 {$reporter}\n";
            $msg .= "Keluhan    : ⚠️ <i>\"{$complaint}\"</i>\n";
            $msg .= "Waktu      : ⏰ {$time} WIB\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "<b>⚠️ MOHON TEKNISI ELEKTROMEDIS JAGA SEGERA KE LOKASI!</b>";
        } else {
            $p_label = strtoupper($ticket['priority'] ?? 'NORMAL');
            $msg = "🛠️ <b>LAPORAN KERUSAKAN ALAT MEDIS BARU</b>\n";
            $msg .= "🏥 <b>{$h_name}</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "No. Tiket   : <code>{$ticket_num}</code>\n";
            $msg .= "Prioritas  : <b>{$p_label}</b>\n";
            $msg .= "Alat Medis : <b>{$eq_name}</b> (<code>{$eq_code}</code>)\n";
            $msg .= "Unit/Ruang : 📍 {$room_name}\n";
            $msg .= "Pelapor    : 👤 {$reporter}\n";
            $msg .= "Keluhan    : <i>\"{$complaint}\"</i>\n";
            $msg .= "Waktu      : ⏰ {$time} WIB\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "Silakan teknisi IPSRS meninjau dan merespon laporan.";
        }

        return $this->send_message($msg, $tenant_id);
    }

    /**
     * Notifikasi Pengerjaan Selesai, Menunggu Uji Fungsi & Validasi Ruangan
     */
    public function notify_ticket_validation_needed($ticket, $equipment, $room, $technician_name, $tenant_id = 1) {
        $settings = $this->CI->setting_m->get_settings($tenant_id);
        if (empty($settings['telegram_notif_validation'])) {
            return false;
        }

        $h_name = htmlspecialchars($settings['hospital_name'] ?? 'SIMPELKES RS');
        $eq_name = htmlspecialchars($equipment['name'] ?? 'Alat Medis');
        $room_name = htmlspecialchars($room['name'] ?? 'Ruangan');
        $ticket_num = htmlspecialchars($ticket['ticket_number'] ?? 'WO-' . $ticket['id']);
        $action_taken = htmlspecialchars($ticket['action_taken'] ?? 'Perbaikan selesai.');
        $tech = htmlspecialchars($technician_name ?: 'Teknisi Elektromedis');

        $msg = "✍️ <b>PERBAIKAN SELESAI — SIAP UJI FUNGSI UNIT</b>\n";
        $msg .= "🏥 <b>{$h_name}</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "No. Tiket   : <code>{$ticket_num}</code>\n";
        $msg .= "Alat Medis : <b>{$eq_name}</b>\n";
        $msg .= "Unit Kerja : 📍 <b>{$room_name}</b>\n";
        $msg .= "Teknisi    : 👷 {$tech}\n";
        $msg .= "Tindakan   : <i>\"{$action_taken}\"</i>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "👉 <b>Mohon Petugas Ruangan {$room_name} melakukan uji coba fungsi alat dan menandatangani validasi serah terima di SIMPELKES.</b>";

        return $this->send_message($msg, $tenant_id);
    }

    /**
     * Notifikasi Tiket Ditutup & Alat Kembali Siap Operasional
     */
    public function notify_ticket_resolved($ticket, $equipment, $room, $verifier_name, $tenant_id = 1) {
        $settings = $this->CI->setting_m->get_settings($tenant_id);
        if (empty($settings['telegram_notif_validation'])) {
            return false;
        }

        $h_name = htmlspecialchars($settings['hospital_name'] ?? 'SIMPELKES RS');
        $eq_name = htmlspecialchars($equipment['name'] ?? 'Alat Medis');
        $room_name = htmlspecialchars($room['name'] ?? 'Ruangan');
        $ticket_num = htmlspecialchars($ticket['ticket_number'] ?? 'WO-' . $ticket['id']);
        $verifier = htmlspecialchars($verifier_name ?: 'Petugas Ruangan');

        $msg = "✅ <b>ALAT MEDIS RESMI OPERASIONAL KEMBALI</b>\n";
        $msg .= "🏥 <b>{$h_name}</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "No. Tiket   : <code>{$ticket_num}</code>\n";
        $msg .= "Alat Medis : <b>{$eq_name}</b>\n";
        $msg .= "Unit Kerja : 📍 {$room_name}\n";
        $msg .= "Divalidasi : 👤 {$verifier} (Uji Fungsi Normal)\n";
        $msg .= "Status     : 🟢 <b>OPERASIONAL (SIAP PAKAI)</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Tiket perbaikan resmi ditutup. Terima kasih atas kerja sama tim IPSRS dan Unit Pelayanan.";

        return $this->send_message($msg, $tenant_id);
    }

    /**
     * Eksekusi HTTP Request ke Telegram Bot API
     */
    private function execute_curl($url, $payload) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6); // Cepat (maksimal 6 detik) agar tidak blocking

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke Telegram API: ' . $curl_error
            ];
        }

        $res_data = json_decode($result, true);

        if ($http_code === 200 && !empty($res_data['ok'])) {
            return [
                'success' => true,
                'message' => 'Pesan Telegram berhasil terkirim.',
                'data'    => $res_data['result'] ?? null
            ];
        }

        $err_desc = $res_data['description'] ?? 'HTTP Code ' . $http_code;
        return [
            'success' => false,
            'message' => 'Telegram Error: ' . $err_desc
        ];
    }
}
