<?php
require_once __DIR__ . '/../application/core/Env.php';
Env::load(__DIR__ . '/../.env');

$db = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASS', ''), env('DB_NAME', 'simpelkesrsig'));
$res = $db->query('DESCRIBE preventive_schedules');
echo "=== COLUMNS IN preventive_schedules ===\n";
while($r = $res->fetch_assoc()) {
    echo $r['Field'] . ' (' . $r['Type'] . ')' . PHP_EOL;
}
