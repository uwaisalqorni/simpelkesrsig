<?php
$db = new mysqli('localhost', 'root', 'bismillah', 'simpelkesrsig');
echo "=== APP SETTINGS ===\n";
$r = $db->query('SELECT * FROM app_settings');
while($row = $r->fetch_assoc()) {
    print_r($row);
}
echo "=== TENANTS ===\n";
$t = $db->query('SELECT id, code, name, hospital_subtitle, address, phone, city, logo_path FROM tenants');
while($row = $t->fetch_assoc()) {
    print_r($row);
}
