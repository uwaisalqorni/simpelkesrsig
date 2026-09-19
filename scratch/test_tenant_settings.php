<?php
/**
 * Test per-tenant hospital settings isolation and synchronization
 */

function make_request($method, $url, $data = null, $token = null, $tenant_id = null) {
    $ch = curl_init();
    $headers = ['Accept: application/json'];
    
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }
    if ($tenant_id !== null) {
        $headers[] = "X-Tenant-Id: {$tenant_id}";
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $http_code,
        'body' => json_decode($response, true),
        'raw'  => $response
    ];
}

$base_url = "http://localhost/simpelkesrsig-backend/api";

echo "=== 1. LOGIN ADMIN RSIG (TENANT 1) ===\n";
$rsig_login = make_request('POST', "{$base_url}/auth/login", [
    'username' => 'admin',
    'password' => 'password123'
]);
$rsig_token = $rsig_login['body']['data']['token'];
$rsig_settings = make_request('GET', "{$base_url}/settings", null, $rsig_token);
echo "RSIG Hospital Name: " . ($rsig_settings['body']['data']['hospital_name'] ?? 'FAIL') . "\n";
echo "RSIG Address: " . ($rsig_settings['body']['data']['hospital_address'] ?? 'FAIL') . "\n\n";

echo "=== 2. LOGIN ADMIN KLINIK PRATAMA (TENANT 2) ===\n";
$kpsm_login = make_request('POST', "{$base_url}/auth/login", [
    'username' => 'adminkpsm',
    'password' => 'password123'
]);
$kpsm_token = $kpsm_login['body']['data']['token'];
$kpsm_settings_before = make_request('GET', "{$base_url}/settings", null, $kpsm_token);
echo "KPSM Hospital Name (Before Update): " . ($kpsm_settings_before['body']['data']['hospital_name'] ?? 'FAIL') . "\n";
echo "KPSM Subtitle: " . ($kpsm_settings_before['body']['data']['hospital_subtitle'] ?? 'FAIL') . "\n\n";

echo "=== 3. UPDATE SETTINGS KHUSUS KLINIK PRATAMA (TENANT 2) ===\n";
$update_kpsm = make_request('POST', "{$base_url}/settings/update", [
    'hospital_name' => 'Klinik Pratama Sehat Mandiri Utama',
    'hospital_subtitle' => 'Pusat Pelayanan Kesehatan & Elektromedis Mandiri',
    'hospital_address' => 'Jl. Soekarno Hatta No. 88, Malang',
    'hospital_phone' => '0341-404040',
    'hospital_city' => 'Kota Malang'
], $kpsm_token);

echo "Update KPSM Code: " . $update_kpsm['code'] . "\n";
echo "Update Message: " . ($update_kpsm['body']['message'] ?? '') . "\n\n";

echo "=== 4. VERIFY KPSM SETTINGS AFTER UPDATE ===\n";
$kpsm_settings_after = make_request('GET', "{$base_url}/settings", null, $kpsm_token);
echo "KPSM Hospital Name: " . ($kpsm_settings_after['body']['data']['hospital_name'] ?? 'FAIL') . "\n";
echo "KPSM Address: " . ($kpsm_settings_after['body']['data']['hospital_address'] ?? 'FAIL') . "\n";
echo "KPSM Phone: " . ($kpsm_settings_after['body']['data']['hospital_phone'] ?? 'FAIL') . "\n\n";

echo "=== 5. VERIFY RSIG SETTINGS REMAIN 100% UNCHANGED (NO LEAKAGE) ===\n";
$rsig_settings_after = make_request('GET', "{$base_url}/settings", null, $rsig_token);
echo "RSIG Hospital Name: " . ($rsig_settings_after['body']['data']['hospital_name'] ?? 'FAIL') . "\n";
echo "RSIG Address: " . ($rsig_settings_after['body']['data']['hospital_address'] ?? 'FAIL') . "\n";
echo "RSIG Phone: " . ($rsig_settings_after['body']['data']['hospital_phone'] ?? 'FAIL') . "\n\n";

if ($rsig_settings_after['body']['data']['hospital_address'] === 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang' &&
    $kpsm_settings_after['body']['data']['hospital_address'] === 'Jl. Soekarno Hatta No. 88, Malang') {
    echo ">>> BERHASIL: SETIAP RUMAH SAKIT / FASKES MEMILIKI PENGATURAN KOP SURAT & NAMA SENDIRI-SENDIRI SECARA INDEPENDEN! <<<\n";
} else {
    echo ">>> GAGAL: Masih terjadi konflik atau percampuran konfigurasi antar RS <<<\n";
}
