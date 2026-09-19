<?php
$c = new mysqli('localhost', 'root', 'bismillah', 'simpelkesrsig');
$res = $c->query("SHOW COLUMNS FROM work_orders");
$cols = [];
while ($r = $res->fetch_assoc()) {
    $cols[] = $r['Field'];
}
echo "Columns in work_orders:\n";
foreach (['rejection_reason', 'verified_by_user_id', 'verified_at', 'room_signature_path', 'status'] as $field) {
    echo $field . ": " . (in_array($field, $cols) ? "EXISTS" : "MISSING") . "\n";
}
$st = $c->query("SHOW COLUMNS FROM work_orders LIKE 'status'")->fetch_assoc();
echo "status type: " . $st['Type'] . "\n";
