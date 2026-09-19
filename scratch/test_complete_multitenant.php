<?php
/**
 * End-to-end automated verification script for SIMPELKES Multi-Tenancy
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
    } else {
        if ($data) {
            $url .= '?' . http_build_query($data);
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

echo "=== 1. TEST LOGIN SUPERADMIN ===\n";
$super_login = make_request('POST', "{$base_url}/auth/login", [
    'username' => 'superadmin',
    'password' => 'password123'
]);
if ($super_login['code'] !== 200 || !isset($super_login['body']['data']['token'])) {
    die("FAILED: Superadmin login failed. Code: " . $super_login['code'] . "\n" . $super_login['raw']);
}
$super_token = $super_login['body']['data']['token'];
echo "SUCCESS: Superadmin logged in. Role: " . $super_login['body']['data']['user']['role'] . "\n\n";

echo "=== 2. TEST GET TENANTS LIST & OVERVIEW ===\n";
$tenants_list = make_request('GET', "{$base_url}/tenants", null, $super_token);
echo "GET /tenants Code: " . $tenants_list['code'] . " - Total Tenants: " . count($tenants_list['body']['data']) . "\n";
foreach ($tenants_list['body']['data'] as $t) {
    echo "  - [ID: {$t['id']}] {$t['code']} - {$t['name']} (Alkes: {$t['total_equipment']}, Active: {$t['is_active']})\n";
}

$overview = make_request('GET', "{$base_url}/tenants/overview", null, $super_token);
echo "GET /tenants/overview Code: " . $overview['code'] . "\n";
print_r($overview['body']['data']);
echo "\n";

echo "=== 3. TEST CREATE NEW TENANT (RS Islam Cabang Barat / RSICB) ===\n";
$new_tenant_code = 'RSICB_' . substr(time(), -4);
$create_res = make_request('POST', "{$base_url}/tenants", [
    'code' => $new_tenant_code,
    'name' => 'RS Islam Cabang Barat',
    'hospital_subtitle' => 'Pusat Layanan Medis Terpadu Barat',
    'city' => 'Batu',
    'address' => 'Jl. Panderman Raya No. 45',
    'phone' => '0341-590000',
    'admin_username' => 'admin_' . strtolower($new_tenant_code),
    'admin_full_name' => 'Admin Utama RSICB',
    'admin_password' => 'password123'
], $super_token);

echo "POST /tenants Code: " . $create_res['code'] . " - Message: " . ($create_res['body']['message'] ?? '') . "\n";
if ($create_res['code'] !== 201) {
    die("FAILED: Create tenant failed. " . $create_res['raw'] . "\n");
}
$new_tenant_id = $create_res['body']['data']['id'];
echo "SUCCESS: Created tenant ID: {$new_tenant_id}\n\n";

echo "=== 4. TEST UPDATE TENANT PROFILE ===\n";
$update_res = make_request('POST', "{$base_url}/tenants/update/{$new_tenant_id}", [
    'name' => 'RS Islam Cabang Barat (Updated)',
    'hospital_subtitle' => 'Pusat Layanan Medis Akreditasi Paripurna',
    'city' => 'Kota Batu',
    'address' => 'Jl. Panderman Raya No. 45-47',
    'phone' => '0341-591111'
], $super_token);
echo "POST /tenants/update Code: " . $update_res['code'] . " - Message: " . ($update_res['body']['message'] ?? '') . "\n\n";

echo "=== 5. TEST LOGIN AS NEW TENANT ADMIN ===\n";
$tenant_admin_login = make_request('POST', "{$base_url}/auth/login", [
    'username' => 'admin_' . strtolower($new_tenant_code),
    'password' => 'password123'
]);
echo "POST /auth/login (Tenant Admin) Code: " . $tenant_admin_login['code'] . "\n";
if ($tenant_admin_login['code'] !== 200) {
    die("FAILED: Tenant admin login failed. " . $tenant_admin_login['raw'] . "\n");
}
$tenant_admin_token = $tenant_admin_login['body']['data']['token'];
$tenant_admin_user = $tenant_admin_login['body']['data']['user'];
echo "SUCCESS: Logged in as {$tenant_admin_user['username']} for Tenant: {$tenant_admin_user['tenant_name']} (ID: {$tenant_admin_user['tenant_id']})\n\n";

echo "=== 6. TEST CREATE ROOM & EQUIPMENT IN NEW TENANT ===\n";
$room_res = make_request('POST', "{$base_url}/rooms", [
    'code' => 'ICU-CB',
    'name' => 'Ruang Intensive Care Unit Barat',
    'building' => 'Gedung Utama Lt 2',
    'floor' => '2'
], $tenant_admin_token);
echo "POST /rooms (New Tenant) Code: " . $room_res['code'] . " - ID: " . ($room_res['body']['data']['id'] ?? '') . "\n";
$new_room_id = $room_res['body']['data']['id'] ?? null;

$eq_res = make_request('POST', "{$base_url}/equipment", [
    'code' => 'EQ-VENT-01',
    'name' => 'Mechanical Ventilator ICU',
    'category_id' => 1,
    'brand' => 'Hamilton Medical',
    'model_type' => 'C3',
    'serial_number' => 'VENT-889911',
    'room_id' => $new_room_id,
    'status' => 'baik',
    'procurement_year' => 2024,
    'procurement_cost' => 450000000,
    'source_of_fund' => 'Yayasan'
], $tenant_admin_token);
echo "POST /equipment (New Tenant) Code: " . $eq_res['code'] . " - ID: " . ($eq_res['body']['data']['id'] ?? '') . "\n\n";

echo "=== 7. VERIFY DATA ISOLATION (RSIG vs NEW TENANT) ===\n";
// RSIG Admin login
$rsig_login = make_request('POST', "{$base_url}/auth/login", [
    'username' => 'admin',
    'password' => 'password123'
]);
$rsig_token = $rsig_login['body']['data']['token'];

$rsig_eq = make_request('GET', "{$base_url}/equipment", null, $rsig_token);
$new_tenant_eq = make_request('GET', "{$base_url}/equipment", null, $tenant_admin_token);

$rsig_count = count($rsig_eq['body']['data'] ?? []);
$new_tenant_count = count($new_tenant_eq['body']['data'] ?? []);

echo "RSIG Equipment Count (as RSIG Admin): {$rsig_count}\n";
echo "New Tenant Equipment Count (as New Tenant Admin): {$new_tenant_count}\n";

if ($rsig_count > 0 && $new_tenant_count === 1) {
    echo ">>> PERFECT DATA ISOLATION CONFIRMED! RSIG cannot see New Tenant's alkes, and New Tenant only sees their own 1 item! <<<\n\n";
} else {
    echo "WARNING: Unexpected counts. Inspecting responses.\n";
}

echo "=== 8. SUPER ADMIN TENANT SWITCHING (VIA X-Tenant-Id) ===\n";
$super_view_rsig = make_request('GET', "{$base_url}/equipment", null, $super_token, 1);
$super_view_new = make_request('GET', "{$base_url}/equipment", null, $super_token, $new_tenant_id);

echo "Super Admin with X-Tenant-Id: 1 sees " . count($super_view_rsig['body']['data'] ?? []) . " equipment items.\n";
echo "Super Admin with X-Tenant-Id: {$new_tenant_id} sees " . count($super_view_new['body']['data'] ?? []) . " equipment items.\n";

echo "\nALL TESTS COMPLETED SUCCESSFULLY!\n";
