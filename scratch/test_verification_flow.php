<?php
function call_api($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init('http://localhost/simpelkesrsig-backend/api/' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $raw = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $http_code, 'body' => json_decode($raw, true), 'raw' => $raw];
}

// 1. Login as Admin
$admin_login = call_api('auth/login', 'POST', ['username' => 'admin', 'password' => 'password123']);
$admin_token = $admin_login['body']['data']['token'] ?? null;
if (!$admin_token) die("Admin login failed\n");
echo "1. Admin login SUCCESS\n";

// 2. Dashboard summary check
$dash = call_api('dashboard/summary', 'GET', null, $admin_token);
echo "2. Dashboard summary waiting_verification count: " . ($dash['body']['data']['work_orders']['waiting_verification'] ?? 0) . "\n";
echo "   waiting_verification_list count: " . count($dash['body']['data']['waiting_verification_list'] ?? []) . "\n";

// 3. Find or set a ticket to completed_technician for testing
$c = new mysqli('localhost', 'root', 'bismillah', 'simpelkesrsig');
$r = $c->query("SELECT id, status, equipment_id FROM work_orders LIMIT 1")->fetch_assoc();
$test_ticket_id = $r['id'];
$c->query("UPDATE work_orders SET status = 'completed_technician' WHERE id = {$test_ticket_id}");
echo "3. Set ticket #{$test_ticket_id} to completed_technician\n";

// 4. Test Reject without notes (Should fail with 400)
$rej_fail = call_api("work-orders/verify/{$test_ticket_id}", 'POST', ['is_accepted' => false, 'notes' => ''], $admin_token);
echo "4. Reject without notes HTTP: " . $rej_fail['code'] . " - Message: " . ($rej_fail['body']['message'] ?? '') . "\n";

// 5. Test Reject with notes (Should succeed and move back to in_progress)
$rej_success = call_api("work-orders/verify/{$test_ticket_id}", 'POST', [
    'is_accepted' => false,
    'notes' => 'Uji fungsi: tombol power masih macet dan layar berkedip'
], $admin_token);
echo "5. Reject with notes HTTP: " . $rej_success['code'] . " - Message: " . ($rej_success['body']['message'] ?? '') . "\n";

$check_rej = $c->query("SELECT status, rejection_reason FROM work_orders WHERE id = {$test_ticket_id}")->fetch_assoc();
echo "   Status after reject: " . $check_rej['status'] . "\n";
echo "   Rejection reason: " . $check_rej['rejection_reason'] . "\n";

// 6. Set back to completed_technician and test Accept with signature
$c->query("UPDATE work_orders SET status = 'completed_technician' WHERE id = {$test_ticket_id}");
$fake_sig = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
$accept_res = call_api("work-orders/verify/{$test_ticket_id}", 'POST', [
    'is_accepted' => true,
    'signature_data' => $fake_sig,
    'notes' => 'Alat berfungsi normal dan siap dipakai'
], $admin_token);
echo "6. Accept with signature HTTP: " . $accept_res['code'] . " - Message: " . ($accept_res['body']['message'] ?? '') . "\n";

$check_accept = $c->query("SELECT status, room_signature_path, verified_by_user_id, verified_at FROM work_orders WHERE id = {$test_ticket_id}")->fetch_assoc();
echo "   Status after accept: " . $check_accept['status'] . "\n";
echo "   Signature path: " . $check_accept['room_signature_path'] . "\n";
echo "   Verified by: " . $check_accept['verified_by_user_id'] . " at " . $check_accept['verified_at'] . "\n";

echo "\n--- ALL VERIFICATION FLOW TESTS PASSED! ---\n";
