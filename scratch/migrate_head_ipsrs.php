<?php
require_once __DIR__ . '/../application/core/Env.php';
Env::load(__DIR__ . '/../.env');

$db = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASS', ''), env('DB_NAME', 'simpelkesrsig'));
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== MIGRATING HEAD IPSRS COLUMNS ===\n";

// 1. Cek & Tambahkan ke app_settings
$res = $db->query("SHOW COLUMNS FROM app_settings LIKE 'head_ipsrs_name'");
if ($res->num_rows == 0) {
    $db->query("ALTER TABLE app_settings ADD COLUMN head_ipsrs_name VARCHAR(150) NULL DEFAULT 'Ahmad Elektromedik, S.Tr.Kes' AFTER hospital_city");
    echo "Added head_ipsrs_name to app_settings\n";
} else {
    echo "head_ipsrs_name already exists in app_settings\n";
}

$res = $db->query("SHOW COLUMNS FROM app_settings LIKE 'head_ipsrs_nip'");
if ($res->num_rows == 0) {
    $db->query("ALTER TABLE app_settings ADD COLUMN head_ipsrs_nip VARCHAR(50) NULL DEFAULT '19850712 201001 1 002' AFTER head_ipsrs_name");
    echo "Added head_ipsrs_nip to app_settings\n";
} else {
    echo "head_ipsrs_nip already exists in app_settings\n";
}

// 2. Cek & Tambahkan ke tenants
$res = $db->query("SHOW COLUMNS FROM tenants LIKE 'head_ipsrs_name'");
if ($res->num_rows == 0) {
    $db->query("ALTER TABLE tenants ADD COLUMN head_ipsrs_name VARCHAR(150) NULL DEFAULT 'Ahmad Elektromedik, S.Tr.Kes' AFTER city");
    echo "Added head_ipsrs_name to tenants\n";
} else {
    echo "head_ipsrs_name already exists in tenants\n";
}

$res = $db->query("SHOW COLUMNS FROM tenants LIKE 'head_ipsrs_nip'");
if ($res->num_rows == 0) {
    $db->query("ALTER TABLE tenants ADD COLUMN head_ipsrs_nip VARCHAR(50) NULL DEFAULT '19850712 201001 1 002' AFTER head_ipsrs_name");
    echo "Added head_ipsrs_nip to tenants\n";
} else {
    echo "head_ipsrs_nip already exists in tenants\n";
}

// Set default values jika null
$db->query("UPDATE app_settings SET head_ipsrs_name = 'Ahmad Elektromedik, S.Tr.Kes', head_ipsrs_nip = '19850712 201001 1 002' WHERE head_ipsrs_name IS NULL OR head_ipsrs_name = ''");

echo "MIGRATION COMPLETED SUCCESSFULLY!\n";
