<?php
require_once __DIR__ . '/TestCase.php';

class SettingsTest extends TestCase {

    private $superToken;
    private $rsigToken;
    private $kpsmToken;

    public function setUp() {
        $super = $this->api('POST', '/auth/login', ['username' => 'superadmin', 'password' => 'password123']);
        $this->superToken = $super['body']['data']['token'] ?? null;

        $rsig = $this->api('POST', '/auth/login', ['username' => 'admin', 'password' => 'password123']);
        $this->rsigToken = $rsig['body']['data']['token'] ?? null;

        $kpsm = $this->api('POST', '/auth/login', ['username' => 'adminkpsm', 'password' => 'password123']);
        $this->kpsmToken = $kpsm['body']['data']['token'] ?? null;
    }

    public function test_per_tenant_settings_isolation() {
        $rsigSettings = $this->api('GET', '/settings', null, $this->rsigToken);
        $kpsmSettings = $this->api('GET', '/settings', null, $this->kpsmToken);

        $this->assertEquals(200, $rsigSettings['code']);
        $this->assertEquals(200, $kpsmSettings['code']);

        $rsigName = $rsigSettings['body']['data']['hospital_name'] ?? '';
        $kpsmName = $kpsmSettings['body']['data']['hospital_name'] ?? '';

        $this->assertNotNull($rsigName);
        $this->assertNotNull($kpsmName);
        $this->assertTrue($rsigName !== $kpsmName, 'Nama faskes Tenant 1 dan Tenant 2 harus terisolasi mandiri');
    }

    public function test_updating_tenant_settings_does_not_affect_other_tenant() {
        // Ambil nama awal faskes KPSM
        $kpsmInitial = $this->api('GET', '/settings', null, $this->kpsmToken);
        $kpsmOriginalName = $kpsmInitial['body']['data']['hospital_name'];

        // RSIG update nama/kontak
        $uniquePhone = '0812' . rand(10000000, 99999999);
        $updateRsig = $this->api('POST', '/settings/update', [
            'hospital_phone' => $uniquePhone
        ], $this->rsigToken);

        $this->assertEquals(200, $updateRsig['code']);

        // Verifikasi RSIG terupdate
        $rsigAfter = $this->api('GET', '/settings', null, $this->rsigToken);
        $this->assertEquals($uniquePhone, $rsigAfter['body']['data']['hospital_phone']);

        // Verifikasi KPSM TIDAK terpengaruh sama sekali
        $kpsmAfter = $this->api('GET', '/settings', null, $this->kpsmToken);
        $this->assertEquals($kpsmOriginalName, $kpsmAfter['body']['data']['hospital_name']);
        $this->assertFalse(
            ($kpsmAfter['body']['data']['hospital_phone'] ?? '') === $uniquePhone,
            'Perubahan telepon pada Tenant 1 tidak boleh mencemari konfigurasi Tenant 2'
        );
    }

    public function test_super_admin_can_view_different_tenant_settings_via_header() {
        $rsigView = $this->api('GET', '/settings', null, $this->superToken, 1);
        $kpsmView = $this->api('GET', '/settings', null, $this->superToken, 2);

        $this->assertEquals(200, $rsigView['code']);
        $this->assertEquals(200, $kpsmView['code']);

        $name1 = $rsigView['body']['data']['hospital_name'] ?? '';
        $name2 = $kpsmView['body']['data']['hospital_name'] ?? '';

        $this->assertTrue($name1 !== $name2, 'Super Admin harus dapat melihat setting faskes 1 dan 2 secara terpisah via header X-Tenant-Id');
    }
}
