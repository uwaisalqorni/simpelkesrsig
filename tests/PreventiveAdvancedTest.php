<?php
/**
 * Test Suite: Preventive Maintenance Advanced (Kemenkes / MFK 8)
 * 
 * Menguji kepatuhan formulir pemeliharaan preventif 3 pilar:
 * 1. Pemantauan fungsi fisik (8 item checklist)
 * 2. Tindakan preventif berkala (4 tindakan)
 * 3. Pengukuran keselamatan listrik (Grounding & Kebocoran Arus)
 * 4. Sinkronisasi status operasional alat medis
 * 5. Proteksi penghapusan jadwal selesai & auto-rescheduling
 */

require_once __DIR__ . '/TestCase.php';

class PreventiveAdvancedTest extends TestCase {

    private $token;
    private $testEquipmentId;
    private $testScheduleId;

    public function setUp() {
        // Login sebagai admin
        $res = $this->api('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        if (!empty($res['body']['data']['token'])) {
            $this->token = $res['body']['data']['token'];
        }

        // Ambil alat medis uji
        $db = $this->getDb();
        $q = $db->query("SELECT id FROM medical_equipment WHERE tenant_id = 1 LIMIT 1");
        if ($q && $row = $q->fetch_assoc()) {
            $this->testEquipmentId = (int)$row['id'];
        } else {
            $this->testEquipmentId = 1;
        }

        // Buat jadwal preventif uji
        $today = date('Y-m-d');
        $db->query("INSERT INTO preventive_schedules (tenant_id, equipment_id, scheduled_date, frequency, status, checklist_data)
                    VALUES (1, {$this->testEquipmentId}, '{$today}', 'quarterly', 'pending', '[]')");
        $this->testScheduleId = (int)$db->insert_id;
    }

    public function tearDown() {
        if ($this->testScheduleId) {
            $db = $this->getDb();
            // Bersihkan data uji jika diperlukan
            $db->query("DELETE FROM preventive_schedules WHERE id = {$this->testScheduleId}");
            $db->query("DELETE FROM preventive_schedules WHERE notes LIKE '%[TEST-AUTO-PM]%'");
            // Kembalikan status alat ke operasional
            $db->query("UPDATE medical_equipment SET operational_status = 'operasional' WHERE id = {$this->testEquipmentId}");
        }
    }

    public function test_complete_schedule_with_3_pillar_advanced_checklist() {
        $inspectionData = [
            'casing'          => 'baik',
            'battery'         => 'baik',
            'mounting'        => 'baik',
            'power_cord'      => 'baik',
            'filter'          => 'baik',
            'probe_connector' => 'baik',
            'alarm'           => 'baik',
            'controls'        => 'baik'
        ];

        $actionsData = [
            'cleaning'    => 'ya',
            'lubricating' => 'ya',
            'tightening'  => 'ya',
            'replacement' => 'na'
        ];

        $safetyData = [
            'grounding_resistance' => '0.14',
            'leakage_current'      => '42.5'
        ];

        $payload = [
            'execution_start_at'   => date('Y-m-d 08:30:00'),
            'execution_end_at'     => date('Y-m-d 09:45:00'),
            'sp_number'            => 'SPK-TEST-MFK8-001',
            'executor_type'        => 'internal',
            'activity_type'        => 'pemeliharaan',
            'final_condition'      => 'laik_pakai',
            'technician_name'      => 'Ahmad Teknisi Uji',
            'supervisor_name'      => 'Faiz Kurniawan, S.Tr.Kes',
            'notes'                => '[TEST-AUTO-PM] Pemeliharaan preventif selesai sesuai standar SOP MFK 8.',
            'inspection_checklist' => $inspectionData,
            'maintenance_actions'  => $actionsData,
            'electrical_safety'    => $safetyData
        ];

        $res = $this->api('POST', "/preventive/complete/{$this->testScheduleId}", $payload, $this->token);
        $this->assertEquals(200, $res['code'], 'Endpoint complete PM harus merespons 200 OK');
        $this->assertTrue($res['body']['success'], 'Response complete harus bernilai success: true');

        // Verifikasi row di database
        $db = $this->getDb();
        $check = $db->query("SELECT * FROM preventive_schedules WHERE id = {$this->testScheduleId}")->fetch_assoc();
        $this->assertEquals('done', $check['status'], 'Status jadwal harus berubah menjadi done');
        $this->assertEquals('laik_pakai', $check['final_condition'], 'Kondisi akhir alat harus laik_pakai');
        $this->assertEquals('SPK-TEST-MFK8-001', $check['sp_number'], 'Nomor SPK harus tersimpan');
        $this->assertEquals('Ahmad Teknisi Uji', $check['technician_name'], 'Nama teknisi harus tersimpan');
        $this->assertEquals('Faiz Kurniawan, S.Tr.Kes', $check['supervisor_name'], 'Nama penanggung jawab harus tersimpan');

        // Verifikasi JSON checklist
        $savedInspection = json_decode($check['inspection_checklist'], true);
        $this->assertArrayHasKey('casing', $savedInspection, 'Checklist fisik harus memuat parameter casing');
        $this->assertEquals('baik', $savedInspection['casing']);

        $savedSafety = json_decode($check['electrical_safety'], true);
        $this->assertEquals('0.14', $savedSafety['grounding_resistance'], 'Nilai tahanan grounding harus sesuai');

        // Verifikasi status alat sinkron menjadi operasional
        $eqCheck = $db->query("SELECT operational_status FROM medical_equipment WHERE id = {$this->testEquipmentId}")->fetch_assoc();
        $this->assertEquals('operasional', $eqCheck['operational_status'], 'Alat laik pakai harus berstatus operasional');

        // Verifikasi auto-reschedule jadwal berikutnya
        $this->assertNotNull($res['body']['data']['next_schedule_id'], 'Harus mengembalikan id jadwal baru berikutnya');
        $nextId = (int)$res['body']['data']['next_schedule_id'];
        $nextCheck = $db->query("SELECT * FROM preventive_schedules WHERE id = {$nextId}")->fetch_assoc();
        $this->assertEquals('pending', $nextCheck['status'], 'Jadwal berikutnya harus berstatus pending');
        
        // Bersihkan jadwal berikutnya hasil test
        $db->query("DELETE FROM preventive_schedules WHERE id = {$nextId}");
    }

    public function test_complete_schedule_syncs_damaged_condition_to_equipment() {
        $db = $this->getDb();

        // 1. Uji kondisi rusak ringan
        $payloadRingan = [
            'final_condition'      => 'rusak_ringan',
            'technician_name'      => 'Teknisi Uji',
            'notes'                => 'Ada kabel aus perlu diganti.',
            'inspection_checklist' => ['casing' => 'rusak'],
            'maintenance_actions'  => ['cleaning' => 'ya'],
            'electrical_safety'    => ['grounding_resistance' => '0.25', 'leakage_current' => '50']
        ];
        $resRingan = $this->api('POST', "/preventive/complete/{$this->testScheduleId}", $payloadRingan, $this->token);
        $this->assertEquals(200, $resRingan['code']);

        $eqCheck = $db->query("SELECT operational_status FROM medical_equipment WHERE id = {$this->testEquipmentId}")->fetch_assoc();
        $this->assertEquals('rusak_ringan', $eqCheck['operational_status'], 'Status alat harus sinkron menjadi rusak_ringan');

        // Bersihkan jadwal berikutnya hasil auto reschedule
        if (!empty($resRingan['body']['data']['next_schedule_id'])) {
            $db->query("DELETE FROM preventive_schedules WHERE id = " . (int)$resRingan['body']['data']['next_schedule_id']);
        }

        // Buat jadwal lagi untuk uji rusak berat
        $db->query("INSERT INTO preventive_schedules (tenant_id, equipment_id, scheduled_date, frequency, status)
                    VALUES (1, {$this->testEquipmentId}, CURDATE(), 'quarterly', 'pending')");
        $schId2 = (int)$db->insert_id;

        $payloadBerat = [
            'final_condition'      => 'rusak_berat',
            'technician_name'      => 'Teknisi Uji',
            'notes'                => 'Bahaya kebocoran arus tinggi!',
            'inspection_checklist' => ['power_cord' => 'rusak'],
            'maintenance_actions'  => [],
            'electrical_safety'    => ['grounding_resistance' => '1.50', 'leakage_current' => '450']
        ];
        $resBerat = $this->api('POST', "/preventive/complete/{$schId2}", $payloadBerat, $this->token);
        $this->assertEquals(200, $resBerat['code']);

        $eqCheck2 = $db->query("SELECT operational_status FROM medical_equipment WHERE id = {$this->testEquipmentId}")->fetch_assoc();
        $this->assertEquals('rusak_berat', $eqCheck2['operational_status'], 'Status alat harus sinkron menjadi rusak_berat');

        $db->query("DELETE FROM preventive_schedules WHERE id = {$schId2}");
        if (!empty($resBerat['body']['data']['next_schedule_id'])) {
            $db->query("DELETE FROM preventive_schedules WHERE id = " . (int)$resBerat['body']['data']['next_schedule_id']);
        }
    }

    public function test_done_schedule_cannot_be_deleted() {
        $db = $this->getDb();
        $db->query("UPDATE preventive_schedules SET status = 'done' WHERE id = {$this->testScheduleId}");

        $res = $this->api('DELETE', "/preventive/delete/{$this->testScheduleId}", null, $this->token);
        $this->assertEquals(400, $res['code'], 'Jadwal yang sudah selesai (done) tidak boleh dihapus (harus error 400)');
        $this->assertFalse($res['body']['success']);
        $this->assertContains('tidak dapat dihapus', $res['body']['message']);
    }

    public function test_get_schedules_decodes_all_json_pillars() {
        $db = $this->getDb();
        $inspJson = json_encode(['casing' => 'baik', 'controls' => 'baik']);
        $actJson  = json_encode(['cleaning' => 'ya']);
        $safeJson = json_encode(['grounding_resistance' => '0.12']);

        $db->query("UPDATE preventive_schedules SET 
            status = 'done', 
            inspection_checklist = '{$inspJson}', 
            maintenance_actions = '{$actJson}', 
            electrical_safety = '{$safeJson}' 
            WHERE id = {$this->testScheduleId}");

        $res = $this->api('GET', "/preventive/{$this->testScheduleId}", null, $this->token);
        $this->assertEquals(200, $res['code']);
        $data = $res['body']['data'];

        $this->assertTrue(is_array($data['inspection_checklist']), 'inspection_checklist harus otomatis didecode menjadi array');
        $this->assertEquals('baik', $data['inspection_checklist']['casing']);

        $this->assertTrue(is_array($data['maintenance_actions']), 'maintenance_actions harus otomatis didecode menjadi array');
        $this->assertEquals('ya', $data['maintenance_actions']['cleaning']);

        $this->assertTrue(is_array($data['electrical_safety']), 'electrical_safety harus otomatis didecode menjadi array');
        $this->assertEquals('0.12', $data['electrical_safety']['grounding_resistance']);
    }
}
