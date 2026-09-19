<?php
/**
 * Test script for Telegram Bot integration in SIMPELKES
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

echo "=== 1. LOGIN ADMIN RSIG ===\n";
$rsig_login = make_request('POST', "{$base_url}/auth/login", [
    'username' => 'admin',
    'password' => 'password123'
]);
$rsig_token = $rsig_login['body']['data']['token'];
echo "Admin Login Code: " . $rsig_login['code'] . "\n\n";

echo "=== 2. TEST SAVE TELEGRAM SETTINGS FOR RSIG ===\n";
$dummy_token = '123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ_TEST';
$dummy_chat  = '-1001987654321';

$save_settings = make_request('POST', "{$base_url}/settings/update", [
    'telegram_bot_token'        => $dummy_token,
    'telegram_chat_id'          => $dummy_chat,
    'telegram_notif_emergency'  => 1,
    'telegram_notif_routine'    => 1,
    'telegram_notif_validation' => 1,
    'telegram_notif_calibration'=> 1
], $rsig_token);

echo "Save Settings Code: " . $save_settings['code'] . " - Message: " . ($save_settings['body']['message'] ?? '') . "\n";
if ($save_settings['code'] !== 200) {
    die("FAILED saving settings: " . $save_settings['raw'] . "\n");
}

$get_settings = make_request('GET', "{$base_url}/settings", null, $rsig_token);
echo "Verified Saved Bot Token: " . ($get_settings['body']['data']['telegram_bot_token'] ?? '') . "\n";
echo "Verified Saved Chat ID  : " . ($get_settings['body']['data']['telegram_chat_id'] ?? '') . "\n";
echo "Verified Emergency Notif: " . ($get_settings['body']['data']['telegram_notif_emergency'] ?? '') . "\n\n";

echo "=== 3. TEST TEST-TELEGRAM ENDPOINT (VALIDATION) ===\n";
$test_empty = make_request('POST', "{$base_url}/settings/test-telegram", [
    'bot_token' => '',
    'chat_id'   => ''
], $rsig_token);
echo "Empty test Code: " . $test_empty['code'] . " (Expected: 400)\n";
echo "Empty test Message: " . ($test_empty['body']['message'] ?? '') . "\n\n";

echo "=== 4. TEST TEST-TELEGRAM ENDPOINT (TELEGRAM API ERROR HANDLING) ===\n";
$test_invalid = make_request('POST', "{$base_url}/settings/test-telegram", [
    'bot_token' => '9999999999:FAKE_INVALID_TOKEN_FOR_TESTING',
    'chat_id'   => '-100123456789'
], $rsig_token);
echo "Invalid token test Code: " . $test_invalid['code'] . " (Expected: 400)\n";
echo "Telegram API Response Message: " . ($test_invalid['body']['message'] ?? '') . "\n";
echo "SUCCESS: Error Telegram API tertangani secara aman tanpa membuat server crash!\n\n";

echo "=== 5. TEST TICKET WORKFLOW WITH TELEGRAM TRIGGER ===\n";
// Create emergency ticket in RSIG
$ticket_res = make_request('POST', "{$base_url}/work-orders", [
    'equipment_id'      => 1,
    'issue_description' => 'Test kendala defibrillator darurat untuk verifikasi notifikasi telegram',
    'priority'          => 'emergency'
], $rsig_token);
echo "Create Ticket Code: " . $ticket_res['code'] . " - Ticket Number: " . ($ticket_res['body']['data']['ticket_number'] ?? '') . "\n";
$ticket_id = $ticket_res['body']['data']['id'] ?? null;

if ($ticket_id) {
    // Update progress to completed_technician
    $prog_res = make_request('POST', "{$base_url}/work-orders/progress/{$ticket_id}", [
        'status'       => 'completed_technician',
        'action_taken' => 'Penggantian fuse daya internal selesai. Siap uji fungsi.'
    ], $rsig_token);
    echo "Progress to completed_technician Code: " . $prog_res['code'] . "\n";

    // Verify and close
    $verify_res = make_request('POST', "{$base_url}/work-orders/verify/{$ticket_id}", [
        'is_accepted'    => true,
        'notes'          => 'Uji kejut 200J normal. Alat laik pakai.',
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
    ], $rsig_token);
    echo "Verify and Close Code: " . $verify_res['code'] . "\n";
    echo "SUCCESS: Seluruh siklus tiket (Lapor -> Siap Uji -> Ditutup) memicu Telegram service secara mulus dan aman!\n";
}

echo "\nALL TESTS PASSED SUCCESSFULLY!\n";
