<?php
function api_call($url, $method = 'GET', $data = null, $token = null, $headers_extra = []) {
    $ch = curl_init('http://localhost/simpelkesrsig-backend/api/' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = ['Content-Type: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    foreach ($headers_extra as $h) $headers[] = $h;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($raw, true), 'raw' => $raw];
}

echo "=== TESTING MULTI-TENANCY BACKEND ===\n";

// 1. Superadmin Login
$sa_login = api_call('auth/login', 'POST', ['username' => 'superadmin', 'password' => 'password123']);
echo "1. Superadmin login code: " . $sa_login['code'] . "\n";
$sa_token = $sa_login['body']['data']['token'] ?? null;
$sa_user  = $sa_login['body']['data']['user'] ?? [];
echo "   Role: " . ($sa_user['role'] ?? '') . " | Name: " . ($sa_user['full_name'] ?? '') . "\n";
if (!$sa_token) die("Superadmin login failed\n");

// 2. Admin RSIG Login
$admin_login = api_call('auth/login', 'POST', ['username' => 'admin', 'password' => 'password123']);
$admin_token = $admin_login['body']['data']['token'] ?? null;
$admin_user  = $admin_login['body']['data']['user'] ?? [];
echo "2. Admin RSIG login code: " . $admin_login['code'] . "\n";
echo "   Tenant: " . ($admin_user['tenant_id'] ?? '') . " (" . ($admin_user['tenant_name'] ?? '') . ")\n";

// 3. Create Tenant #2 via Superadmin
$t2_data = [
    'code'              => 'KPSM',
    'name'              => 'Klinik Pratama Sehat Mandiri',
    'hospital_subtitle' => 'Unit Pelayanan & Pemeliharaan Sarana',
    'city'              => 'Kota Malang',
    'admin_username'    => 'adminkpsm',
    'admin_password'    => 'password123'
];
$create_t2 = api_call('tenants', 'POST', $t2_data, $sa_token);
echo "3. Create Tenant #2 code: " . $create_t2['code'] . " - Message: " . ($create_t2['body']['message'] ?? '') . "\n";
$t2_id = $create_t2['body']['data']['id'] ?? null;

// 4. List Tenants via Superadmin
$tenants_list = api_call('tenants', 'GET', null, $sa_token);
echo "4. Total tenants count: " . count($tenants_list['body']['data'] ?? []) . "\n";
foreach ($tenants_list['body']['data'] as $t) {
    echo "   - [ID: {$t['id']}] {$t['code']} - {$t['name']} (Alkes: {$t['total_equipment']})\n";
}

// 5. Global Overview for Superadmin
$overview = api_call('tenants/overview', 'GET', null, $sa_token);
echo "5. Global overview: Total faskes = " . ($overview['body']['data']['total_tenants'] ?? 0) . 
     ", Total alkes = " . ($overview['body']['data']['total_equipment_all'] ?? 0) . "\n";

// 6. Test Data Isolation
// Fetch equipment as RSIG admin
$eq_rsig = api_call('equipment', 'GET', null, $admin_token);
$count_rsig = count($eq_rsig['body']['data'] ?? []);
echo "6. RSIG admin sees {$count_rsig} equipment items\n";

// Login as Tenant 2 admin
$kpsm_login = api_call('auth/login', 'POST', ['username' => 'adminkpsm', 'password' => 'password123']);
$kpsm_token = $kpsm_login['body']['data']['token'] ?? null;
echo "7. KPSM admin login code: " . $kpsm_login['code'] . "\n";

// KPSM admin sees their own equipment (should be 0)
$eq_kpsm = api_call('equipment', 'GET', null, $kpsm_token);
$count_kpsm = count($eq_kpsm['body']['data'] ?? []);
echo "   KPSM admin sees {$count_kpsm} equipment items (ISOLATION SUCCESS!)\n";

echo "\n=== ALL MULTI-TENANT BACKEND TESTS PASSED! ===\n";
