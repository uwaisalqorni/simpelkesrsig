<?php
require_once __DIR__ . '/TestCase.php';

class WorkOrdersTest extends TestCase {

    private $adminToken;
    private $testEquipmentId;
    private $createdTicketIds = [];

    public function setUp() {
        $login = $this->api('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $this->adminToken = $login['body']['data']['token'] ?? null;

        // Ambil 1 alkes RSIG untuk pengujian
        $db = $this->getDb();
        $res = $db->query("SELECT id, operational_status FROM medical_equipment WHERE tenant_id = 1 LIMIT 1");
        if ($row = $res->fetch_assoc()) {
            $this->testEquipmentId = (int)$row['id'];
            // Pastikan awal pengujian alat berstatus operasional
            $db->query("UPDATE medical_equipment SET operational_status = 'operasional' WHERE id = {$this->testEquipmentId}");
        }
    }

    public function tearDown() {
        if (!empty($this->createdTicketIds)) {
            $db = $this->getDb();
            $ids = implode(',', array_map('intval', $this->createdTicketIds));
            $db->query("DELETE FROM work_order_logs WHERE work_order_id IN ({$ids})");
            $db->query("DELETE FROM work_order_parts WHERE work_order_id IN ({$ids})");
            $db->query("DELETE FROM work_orders WHERE id IN ({$ids})");
        }

        // Kembalikan status alkes ke operasional
        if ($this->testEquipmentId) {
            $this->getDb()->query("UPDATE medical_equipment SET operational_status = 'operasional' WHERE id = {$this->testEquipmentId}");
        }
    }

    public function test_create_regular_ticket_updates_equipment_to_rusak_ringan() {
        $res = $this->api('POST', '/work-orders', [
            'equipment_id'      => $this->testEquipmentId,
            'issue_description' => 'Unit Test: Kabel power longgar kendala ringan',
            'priority'          => 'medium'
        ], $this->adminToken);

        $this->assertEquals(201, $res['code'], 'Pelaporan tiket harus mengembalikan HTTP 201');
        $this->assertTrue($res['body']['success']);
        $ticketId = $res['body']['data']['id'];
        $this->createdTicketIds[] = $ticketId;

        // Cek nomor tiket berformat WO-YYYYMM-XXXX
        $this->assertContains('WO-', $res['body']['data']['ticket_number']);

        // Verifikasi status alat berubah jadi rusak_ringan
        $check = $this->getDb()->query("SELECT operational_status FROM medical_equipment WHERE id = {$this->testEquipmentId}")->fetch_assoc();
        $this->assertEquals('rusak_ringan', $check['operational_status'], 'Status alat harus rusak_ringan untuk prioritas medium');
    }

    public function test_create_emergency_ticket_updates_equipment_to_rusak_berat() {
        // Reset dulu ke operasional
        $this->getDb()->query("UPDATE medical_equipment SET operational_status = 'operasional' WHERE id = {$this->testEquipmentId}");

        $res = $this->api('POST', '/work-orders', [
            'equipment_id'      => $this->testEquipmentId,
            'issue_description' => 'Unit Test: Konsleting darurat mati total di ICU',
            'priority'          => 'emergency'
        ], $this->adminToken);

        $this->assertEquals(201, $res['code']);
        $ticketId = $res['body']['data']['id'];
        $this->createdTicketIds[] = $ticketId;

        // Verifikasi status alat berubah jadi rusak_berat
        $check = $this->getDb()->query("SELECT operational_status FROM medical_equipment WHERE id = {$this->testEquipmentId}")->fetch_assoc();
        $this->assertEquals('rusak_berat', $check['operational_status'], 'Status alat harus rusak_berat untuk prioritas emergency');
    }

    public function test_technician_progress_to_completed_restores_operational_status() {
        // 1. Buat tiket
        $created = $this->api('POST', '/work-orders', [
            'equipment_id'      => $this->testEquipmentId,
            'issue_description' => 'Unit Test: Pengujian perbaikan teknisi',
            'priority'          => 'high'
        ], $this->adminToken);
        $ticketId = $created['body']['data']['id'];
        $this->createdTicketIds[] = $ticketId;

        // 2. Teknisi update progress ke completed_technician
        $prog = $this->api('POST', "/work-orders/progress/{$ticketId}", [
            'action_taken' => 'Penggantian fuse dan kalibrasi tegangan selesai dilakukan',
            'status'       => 'completed_technician'
        ], $this->adminToken);

        $this->assertEquals(200, $prog['code'], 'Update progress harus berhasil HTTP 200');
        $this->assertTrue($prog['body']['success']);

        // Verifikasi status tiket di DB
        $dbTicket = $this->getDb()->query("SELECT status FROM work_orders WHERE id = {$ticketId}")->fetch_assoc();
        $this->assertEquals('completed_technician', $dbTicket['status']);

        // Verifikasi status alat otomatis kembali operasional
        $check = $this->getDb()->query("SELECT operational_status FROM medical_equipment WHERE id = {$this->testEquipmentId}")->fetch_assoc();
        $this->assertEquals('operasional', $check['operational_status'], 'Alat harus kembali operasional saat teknisi menyelesaikan perbaikan');
    }

    public function test_room_rejection_validation_and_flow() {
        // 1. Buat tiket dan set ke completed_technician
        $created = $this->api('POST', '/work-orders', [
            'equipment_id'      => $this->testEquipmentId,
            'issue_description' => 'Unit Test: Pengujian penolakan hasil perbaikan',
            'priority'          => 'medium'
        ], $this->adminToken);
        $ticketId = $created['body']['data']['id'];
        $this->createdTicketIds[] = $ticketId;

        $this->api('POST', "/work-orders/progress/{$ticketId}", [
            'action_taken' => 'Perbaikan awal selesai',
            'status'       => 'completed_technician'
        ], $this->adminToken);

        // 2. Tolak tanpa alasan kendala -> Harus gagal HTTP 400
        $failReject = $this->api('POST', "/work-orders/verify/{$ticketId}", [
            'is_accepted' => false,
            'notes'       => ''
        ], $this->adminToken);

        $this->assertEquals(400, $failReject['code'], 'Penolakan serah terima tanpa catatan harus ditolak HTTP 400');
        $this->assertFalse($failReject['body']['success']);

        // 3. Tolak dengan alasan jelas -> Harus berhasil HTTP 200 dan tiket kembali in_progress
        $note = 'Layar display masih buram dan ada dengung saat dinyalakan';
        $validReject = $this->api('POST', "/work-orders/verify/{$ticketId}", [
            'is_accepted' => false,
            'notes'       => $note
        ], $this->adminToken);

        $this->assertEquals(200, $validReject['code']);
        $this->assertTrue($validReject['body']['success']);

        $dbTicket = $this->getDb()->query("SELECT status, rejection_reason FROM work_orders WHERE id = {$ticketId}")->fetch_assoc();
        $this->assertEquals('in_progress', $dbTicket['status'], 'Status tiket harus kembali in_progress setelah ditolak unit');
        $this->assertEquals($note, $dbTicket['rejection_reason'], 'Alasan penolakan harus tersimpan di database');
    }

    public function test_room_acceptance_with_signature_closes_ticket() {
        // 1. Buat tiket dan set ke completed_technician
        $created = $this->api('POST', '/work-orders', [
            'equipment_id'      => $this->testEquipmentId,
            'issue_description' => 'Unit Test: Pengujian serah terima tanda tangan',
            'priority'          => 'medium'
        ], $this->adminToken);
        $ticketId = $created['body']['data']['id'];
        $this->createdTicketIds[] = $ticketId;

        $this->api('POST', "/work-orders/progress/{$ticketId}", [
            'action_taken' => 'Pembersihan sensor dan kalibrasi selesai',
            'status'       => 'completed_technician'
        ], $this->adminToken);

        // 2. Validasi & tanda tangan digital
        $fakeSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $accept = $this->api('POST', "/work-orders/verify/{$ticketId}", [
            'is_accepted'    => true,
            'signature_data' => $fakeSignature,
            'notes'          => 'Alat berfungsi sempurna dan siap dipakai pelayanan'
        ], $this->adminToken);

        $this->assertEquals(200, $accept['code'], 'Verifikasi serah terima harus berhasil HTTP 200');
        $this->assertTrue($accept['body']['success']);

        // 3. Verifikasi tiket status = closed, verified_at terisi, signature terisi
        $dbTicket = $this->getDb()->query("SELECT status, room_signature_path, verified_at FROM work_orders WHERE id = {$ticketId}")->fetch_assoc();
        $this->assertEquals('closed', $dbTicket['status'], 'Status tiket harus closed');
        $this->assertNotNull($dbTicket['verified_at'], 'Waktu verifikasi harus terisi');
        $this->assertNotNull($dbTicket['room_signature_path'], 'Path tanda tangan digital harus tersimpan');

        // 4. Pastikan tiket closed TIDAK BISA dihapus melalui API
        $del = $this->api('POST', "/work-orders/delete/{$ticketId}", null, $this->adminToken);
        $this->assertEquals(400, $del['code'], 'Tiket yang sudah closed tidak boleh dihapus melalui API (HTTP 400)');
        $this->assertFalse($del['body']['success']);
    }
}
