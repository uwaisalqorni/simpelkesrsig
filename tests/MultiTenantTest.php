<?php
require_once __DIR__ . '/TestCase.php';

class MultiTenantTest extends TestCase {

    private $superToken;
    private $rsigToken;
    private $kpsmToken;

    public function setUp() {
        // Login Super Admin
        $super = $this->api('POST', '/auth/login', ['username' => 'superadmin', 'password' => 'password123']);
        $this->superToken = $super['body']['data']['token'] ?? null;

        // Login Admin RSIG (Tenant 1)
        $rsig = $this->api('POST', '/auth/login', ['username' => 'admin', 'password' => 'password123']);
        $this->rsigToken = $rsig['body']['data']['token'] ?? null;

        // Login Admin KPSM (Tenant 2)
        $kpsm = $this->api('POST', '/auth/login', ['username' => 'adminkpsm', 'password' => 'password123']);
        $this->kpsmToken = $kpsm['body']['data']['token'] ?? null;
    }

    public function test_tenants_list_accessible_by_superadmin() {
        $res = $this->api('GET', '/tenants', null, $this->superToken);
        $this->assertEquals(200, $res['code'], 'Super Admin berhak mengakses daftar faskes');
        $this->assertTrue($res['body']['success']);
        $this->assertTrue(count($res['body']['data']) >= 2, 'Daftar faskes minimal harus ada 2 tenant (RSIG & KPSM)');
    }

    public function test_tenants_overview_metrics() {
        $res = $this->api('GET', '/tenants/overview', null, $this->superToken);
        $this->assertEquals(200, $res['code']);
        $this->assertArrayHasKey('total_tenants', $res['body']['data']);
        $this->assertArrayHasKey('total_equipment_all', $res['body']['data']);
        $this->assertArrayHasKey('total_active_tickets_all', $res['body']['data']);
    }

    public function test_data_isolation_between_tenants() {
        $rsig_eq = $this->api('GET', '/equipment', null, $this->rsigToken);
        $kpsm_eq = $this->api('GET', '/equipment', null, $this->kpsmToken);

        $this->assertEquals(200, $rsig_eq['code']);
        $this->assertEquals(200, $kpsm_eq['code']);

        $rsig_items = $rsig_eq['body']['data'] ?? [];
        $kpsm_items = $kpsm_eq['body']['data'] ?? [];

        $this->assertTrue(count($rsig_items) > 0, 'RSIG harus memiliki data alkes terdaftar');

        // Pastikan tidak ada alkes RSIG yang bocor ke daftar alkes KPSM
        $rsig_ids = array_column($rsig_items, 'id');
        foreach ($kpsm_items as $ki) {
            $this->assertFalse(in_array($ki['id'], $rsig_ids), 'Data alkes RSIG tidak boleh muncul pada faskes KPSM');
        }
    }

    public function test_super_admin_switcher_header() {
        // Super admin melihat tenant 1
        $view_rsig = $this->api('GET', '/equipment', null, $this->superToken, 1);
        $this->assertEquals(200, $view_rsig['code']);
        $count_rsig = count($view_rsig['body']['data'] ?? []);

        // Super admin melihat tenant 2
        $view_kpsm = $this->api('GET', '/equipment', null, $this->superToken, 2);
        $this->assertEquals(200, $view_kpsm['code']);
        $count_kpsm = count($view_kpsm['body']['data'] ?? []);

        $this->assertTrue($count_rsig > 0, 'Super Admin dengan header X-Tenant-Id: 1 harus melihat alkes RSIG');
        $this->assertTrue($count_rsig !== $count_kpsm, 'Jumlah alkes RSIG dan KPSM harus berbeda sesuai isolasi');
    }

    public function test_regular_admin_cannot_create_super_admin() {
        $hack = $this->api('POST', '/users', [
            'username'  => 'fake_super_' . rand(1000, 9999),
            'full_name' => 'Fake Super Admin',
            'role'      => 'super_admin',
            'password'  => 'password123'
        ], $this->rsigToken);

        $this->assertEquals(403, $hack['code'], 'Admin faskes biasa harus diblokir dari membuat akun super_admin (HTTP 403)');
        $this->assertFalse($hack['body']['success']);
    }
}
