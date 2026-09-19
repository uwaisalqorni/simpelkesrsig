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

    public function test_preventive_schedules_isolation_between_tenants() {
        $db = $this->getDb();
        // Buat jadwal khusus di Tenant 1 (RSIG)
        $q = $db->query("SELECT id FROM medical_equipment WHERE tenant_id = 1 LIMIT 1");
        $eqId = (int)$q->fetch_assoc()['id'];

        $db->query("INSERT INTO preventive_schedules (tenant_id, equipment_id, scheduled_date, frequency, status, notes)
                    VALUES (1, {$eqId}, CURDATE(), 'quarterly', 'pending', '[ISOLATION-TEST-RSIG]')");
        $schId = (int)$db->insert_id;

        // 1. Admin KPSM (Tenant 2) ambil list jadwal preventif -> Jadwal RSIG TIDAK boleh muncul
        $kpsmList = $this->api('GET', '/preventive/schedules', null, $this->kpsmToken);
        $this->assertEquals(200, $kpsmList['code']);
        $items = $kpsmList['body']['data'] ?? [];
        if (isset($items['items'])) $items = $items['items'];
        $scheduleIds = array_column($items, 'id');
        $this->assertFalse(in_array($schId, $scheduleIds), 'Jadwal preventif Tenant 1 tidak boleh bocor ke daftar jadwal Tenant 2');

        // 2. Admin KPSM (Tenant 2) mencoba langsung akses ID jadwal Tenant 1 -> harus 404 (Not Found / Hidden)
        $kpsmDetail = $this->api('GET', "/preventive/{$schId}", null, $this->kpsmToken);
        $this->assertEquals(404, $kpsmDetail['code'], 'Tenant 2 tidak boleh dapat melihat detail jadwal milik Tenant 1 (harus 404)');

        // 3. Admin KPSM mencoba mengeksekusi / menyelesaikan jadwal milik Tenant 1 -> harus 404
        $kpsmComplete = $this->api('POST', "/preventive/complete/{$schId}", [
            'final_condition' => 'laik_pakai',
            'technician_name' => 'Hacker KPSM'
        ], $this->kpsmToken);
        $this->assertEquals(404, $kpsmComplete['code'], 'Tenant 2 tidak boleh dapat mengeksekusi jadwal milik Tenant 1 (harus 404)');

        // 4. Admin RSIG (Tenant 1 yang sah) dapat melihat detailnya
        $rsigDetail = $this->api('GET', "/preventive/{$schId}", null, $this->rsigToken);
        $this->assertEquals(200, $rsigDetail['code'], 'Tenant 1 yang sah harus bisa mengakses jadwal miliknya');

        // Bersihkan
        $db->query("DELETE FROM preventive_schedules WHERE id = {$schId}");
    }
}
