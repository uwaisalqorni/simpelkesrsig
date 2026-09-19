<?php
require_once __DIR__ . '/TestCase.php';

class TelegramServiceTest extends TestCase {

    private $adminToken;

    public function setUp() {
        $login = $this->api('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $this->adminToken = $login['body']['data']['token'] ?? null;
    }

    public function test_test_telegram_empty_credentials_returns_400() {
        $res = $this->api('POST', '/settings/test-telegram', [
            'bot_token' => '',
            'chat_id'   => ''
        ], $this->adminToken);

        $this->assertEquals(400, $res['code'], 'Pengujian telegram tanpa token/chat_id harus 400 Bad Request');
        $this->assertFalse($res['body']['success']);
        $this->assertContains('belum diisi', strtolower($res['body']['message']));
    }

    public function test_test_telegram_invalid_token_handles_gracefully_without_crash() {
        $res = $this->api('POST', '/settings/test-telegram', [
            'bot_token' => '9999999999:FAKE_INVALID_TOKEN_FOR_TESTING',
            'chat_id'   => '-100123456789'
        ], $this->adminToken);

        $this->assertEquals(400, $res['code'], 'Token Telegram tidak valid harus mengembalikan HTTP 400 secara graceful');
        $this->assertFalse($res['body']['success']);
        $this->assertContains('telegram error', strtolower($res['body']['message']));
    }

    public function test_save_and_retrieve_telegram_settings() {
        $dummyToken = '123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ_TEST';
        $dummyChat  = '-1001987654321';

        // 1. Simpan konfigurasi
        $update = $this->api('POST', '/settings/update', [
            'telegram_bot_token'        => $dummyToken,
            'telegram_chat_id'          => $dummyChat,
            'telegram_notif_emergency'  => 1,
            'telegram_notif_routine'    => 1,
            'telegram_notif_validation' => 1,
            'telegram_notif_calibration'=> 1
        ], $this->adminToken);

        $this->assertEquals(200, $update['code'], 'Simpan pengaturan telegram harus mengembalikan status HTTP 200');
        $this->assertTrue($update['body']['success']);

        // 2. Ambil kembali dan verifikasi data
        $get = $this->api('GET', '/settings', null, $this->adminToken);
        $this->assertEquals(200, $get['code']);
        $data = $get['body']['data'];
        $this->assertEquals($dummyToken, $data['telegram_bot_token']);
        $this->assertEquals($dummyChat, $data['telegram_chat_id']);
        $this->assertEquals(1, $data['telegram_notif_emergency']);
        $this->assertEquals(1, $data['telegram_notif_routine']);
        $this->assertEquals(1, $data['telegram_notif_validation']);
    }
}
