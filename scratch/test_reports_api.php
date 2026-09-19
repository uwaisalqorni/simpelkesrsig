<?php
$ch = curl_init('http://localhost/simpelkesrsig-backend/api/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username'=>'admin','password'=>'password123']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$res = json_decode(curl_exec($ch), true);
$token = $res['data']['token'];
curl_close($ch);

$ch = curl_init('http://localhost/simpelkesrsig-backend/api/reports/recap');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Success: " . ($res['success'] ? 'true' : 'false') . "\n";
echo "Monthly Trends (" . count($res['data']['monthly_trends']) . "):\n";
print_r($res['data']['monthly_trends']);
echo "Top Damaged Rooms (" . count($res['data']['top_damaged_rooms']) . "):\n";
print_r($res['data']['top_damaged_rooms']);
echo "SPM Compliance:\n";
print_r($res['data']['spm_compliance']);
