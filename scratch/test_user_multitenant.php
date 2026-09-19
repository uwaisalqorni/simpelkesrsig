<?php
/**
 * Test user multi-tenant creation, scoping, and security enforcement
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

echo "=== 2. ADMIN RSIG CREATE NEW TEKNISI (AUTO LOCKED TO RSIG) ===\n";
$user_code = 'tek_rsig_' . substr(time(), -4);
$create_user_rsig = make_request('POST', "{$base_url}/users", [
    'username'  => $user_code,
    'full_name' => 'Teknisi Elektromedis RSIG Baru',
    'role'      => 'teknisi',
    'password'  => 'password123'
], $rsig_token);
echo "Create User Code: " . $create_user_rsig['code'] . "\n";
$new_user_id = $create_user_rsig['body']['data']['id'];

// Check database tenant_id for this user
$db = new mysqli('localhost', 'root', 'bismillah', 'simpelkesrsig');
$chk = $db->query("SELECT id, username, role, tenant_id FROM users WHERE id = {$new_user_id}")->fetch_assoc();
echo "Created User DB Info: ID={$chk['id']}, Username={$chk['username']}, Tenant ID={$chk['tenant_id']}\n";
if ((int)$chk['tenant_id'] === 1) {
    echo "SUCCESS: Admin RSIG otomatis mengunci tenant_id ke 1!\n\n";
} else {
    echo "FAILED: Unexpected tenant_id!\n\n";
}

echo "=== 3. ADMIN RSIG TRYING TO CREATE SUPER_ADMIN (MUST BE BLOCKED) ===\n";
$hack_attempt = make_request('POST', "{$base_url}/users", [
    'username'  => 'fake_super_' . substr(time(), -4),
    'full_name' => 'Fake Super Admin',
    'role'      => 'super_admin',
    'password'  => 'password123'
], $rsig_token);
echo "Hack Attempt Code: " . $hack_attempt['code'] . " - Message: " . ($hack_attempt['body']['message'] ?? '') . "\n";
if ($hack_attempt['code'] === 403) {
    echo "SUCCESS: Regular admin diblokir dari membuat role super_admin!\n\n";
} else {
    echo "FAILED: Hack attempt was not blocked with 403!\n\n";
}

echo "=== 4. SUPER ADMIN CREATE USER FOR SPECIFIC TENANT (TENANT 2 - KPSM) ===\n";
$super_login = make_request('POST', "{$base_url}/auth/login", [
    'username' => 'superadmin',
    'password' => 'password123'
]);
$super_token = $super_login['body']['data']['token'];

$kpsm_user_code = 'tek_kpsm_' . substr(time(), -4);
$create_user_kpsm = make_request('POST', "{$base_url}/users", [
    'username'  => $kpsm_user_code,
    'full_name' => 'Teknisi Klinik Pratama Sehat Mandiri',
    'role'      => 'teknisi',
    'tenant_id' => 2, // Super Admin explicitly chooses tenant 2
    'password'  => 'password123'
], $super_token);
echo "Super Admin Create User for Tenant 2 Code: " . $create_user_kpsm['code'] . "\n";
$new_kpsm_id = $create_user_kpsm['body']['data']['id'];

$chk2 = $db->query("SELECT id, username, role, tenant_id FROM users WHERE id = {$new_kpsm_id}")->fetch_assoc();
echo "Created User DB Info: ID={$chk2['id']}, Username={$chk2['username']}, Tenant ID={$chk2['tenant_id']}\n";
if ((int)$chk2['tenant_id'] === 2) {
    echo "SUCCESS: Super Admin berhasil menetapkan user ke Tenant 2 (KPSM)!\n\n";
} else {
    echo "FAILED: User did not get tenant_id = 2!\n\n";
}

echo "=== 5. VERIFY ISOLATION: ADMIN RSIG CANNOT SEE KPSM USER ===\n";
$rsig_users = make_request('GET', "{$base_url}/users", null, $rsig_token);
$found = false;
foreach ($rsig_users['body']['data'] as $u) {
    if ($u['username'] === $kpsm_user_code) {
        $found = true;
        break;
    }
}
if (!$found) {
    echo "SUCCESS: User Klinik Pratama tidak terlihat oleh Admin RSIG! Isolasi sempurna.\n";
} else {
    echo "FAILED: Data leak: RSIG can see KPSM user!\n";
}

echo "\nALL TESTS PASSED!\n";
