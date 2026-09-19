<?php
require_once __DIR__ . '/TestCase.php';

class SecurityTest extends TestCase {

    private $adminToken;
    private $ruanganToken;

    public function setUp() {
        // 1. Login Admin
        $adminLogin = $this->api('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $this->adminToken = $adminLogin['body']['data']['token'] ?? null;

        // 2. Cari atau buat user role ruangan untuk tes RBAC
        $db = $this->getDb();
        $res = $db->query("SELECT username FROM users WHERE role = 'ruangan' AND tenant_id = 1 LIMIT 1");
        if ($row = $res->fetch_assoc()) {
            $username = $row['username'];
        } else {
            // Jika belum ada, buat user ruangan sementara
            $username = 'test_ruangan_sec';
            $hash = password_hash('password123', PASSWORD_BCRYPT);
            $db->query("INSERT INTO users (tenant_id, username, password_hash, full_name, role, is_active) VALUES (1, '{$username}', '{$hash}', 'Staf Ruangan Test', 'ruangan', 1)");
        }

        $ruanganLogin = $this->api('POST', '/auth/login', [
            'username' => $username,
            'password' => 'password123'
        ]);
        $this->ruanganToken = $ruanganLogin['body']['data']['token'] ?? null;
    }

    public function test_ruangan_cannot_access_user_management() {
        $res = $this->api('POST', '/users', [
            'username'  => 'illegal_user',
            'password'  => 'password123',
            'full_name' => 'Illegal User',
            'role'      => 'admin'
        ], $this->ruanganToken);

        $this->assertEquals(403, $res['code'], 'User dengan role ruangan dilarang menambah pengguna baru (HTTP 403)');
        $this->assertFalse($res['body']['success']);
        $this->assertContains('Akses ditolak', $res['body']['message']);
    }

    public function test_ruangan_cannot_modify_hospital_settings() {
        $res = $this->api('POST', '/settings/update', [
            'hospital_name' => 'Hacked Hospital Name'
        ], $this->ruanganToken);

        $this->assertEquals(403, $res['code'], 'User dengan role ruangan dilarang mengubah konfigurasi rumah sakit (HTTP 403)');
        $this->assertFalse($res['body']['success']);
    }

    public function test_ruangan_cannot_create_master_room() {
        $res = $this->api('POST', '/rooms', [
            'code' => 'ROOM_ILLEGAL',
            'name' => 'Ruangan Ilegal'
        ], $this->ruanganToken);

        $this->assertEquals(403, $res['code'], 'User dengan role ruangan dilarang menambah master ruangan (HTTP 403)');
        $this->assertFalse($res['body']['success']);
    }

    public function test_direct_malicious_upload_rejection() {
        // Uji validasi ekstensi terlarang (misal PHP webshell atau script berbahaya)
        // Kita kirim multipart form-data via curl langsung ke endpoint work-orders
        $url = rtrim($this->baseUrl, '/') . '/work-orders';
        $ch = curl_init();

        $tmpFile = tempnam(sys_get_temp_dir(), 'malicious_') . '.php';
        file_put_contents($tmpFile, '<?php echo "evil_payload"; ?>');

        $cfile = new CURLFile($tmpFile, 'text/x-php', 'exploit.php');
        $postData = [
            'equipment_id'      => 1,
            'issue_description' => 'Test file upload injection',
            'issue_photo'       => $cfile
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->adminToken
        ]);

        $raw = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        unlink($tmpFile);

        $json = json_decode($raw, true);

        $this->assertEquals(400, $http_code, 'Upload file berekstensi .php harus ditolak HTTP 400');
        $this->assertFalse($json['success']);
        $this->assertContains('tidak diizinkan', $json['message']);
    }
}
