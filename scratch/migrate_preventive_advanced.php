<?php
require_once __DIR__ . '/../application/core/Env.php';
Env::load(__DIR__ . '/../.env');

$db = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASS', ''), env('DB_NAME', 'simpelkesrsig'));
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== MIGRATING PREVENTIVE ADVANCED COLUMNS ===\n";

$columns = [
    'execution_start_at'   => "DATETIME NULL AFTER scheduled_date",
    'execution_end_at'     => "DATETIME NULL AFTER execution_start_at",
    'sp_number'            => "VARCHAR(100) NULL AFTER execution_end_at",
    'executor_type'        => "ENUM('internal', 'external') NOT NULL DEFAULT 'internal' AFTER sp_number",
    'activity_type'        => "VARCHAR(100) NOT NULL DEFAULT 'pemeliharaan' AFTER executor_type",
    'final_condition'      => "ENUM('laik_pakai', 'rusak_ringan', 'rusak_berat') NOT NULL DEFAULT 'laik_pakai' AFTER activity_type",
    'technician_name'      => "VARCHAR(150) NULL AFTER final_condition",
    'supervisor_name'      => "VARCHAR(150) NULL AFTER technician_name",
    'inspection_checklist' => "LONGTEXT NULL AFTER supervisor_name",
    'maintenance_actions'  => "LONGTEXT NULL AFTER inspection_checklist",
    'electrical_safety'    => "LONGTEXT NULL AFTER maintenance_actions",
    'photo_proof_path'     => "VARCHAR(255) NULL AFTER electrical_safety",
    'followup_work_order_id' => "INT(11) NULL AFTER photo_proof_path"
];

foreach ($columns as $col => $definition) {
    $check = $db->query("SHOW COLUMNS FROM preventive_schedules LIKE '{$col}'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE preventive_schedules ADD COLUMN {$col} {$definition}";
        if ($db->query($sql)) {
            echo "[OK] Added column: {$col}\n";
        } else {
            echo "[ERROR] Failed to add column: {$col} - " . $db->error . "\n";
        }
    } else {
        echo "[EXISTS] Column {$col} already exists\n";
    }
}

echo "\nPREVENTIVE ADVANCED MIGRATION COMPLETED!\n";
