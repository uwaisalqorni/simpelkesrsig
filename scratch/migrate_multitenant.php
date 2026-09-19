<?php
/**
 * Migration Script: Multi-Tenancy for SIMPELKES
 */
$c = new mysqli('localhost', 'root', 'bismillah', 'simpelkesrsig');
if ($c->connect_error) {
    die("Database connection failed: " . $c->connect_error . "\n");
}

echo "=== SIMPELKES MULTI-TENANT MIGRATION ===\n";

// 1. Create table `tenants`
$c->query("
    CREATE TABLE IF NOT EXISTS `tenants` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(150) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `hospital_subtitle` VARCHAR(150) NULL,
        `address` TEXT NULL,
        `phone` VARCHAR(50) NULL,
        `city` VARCHAR(100) NULL,
        `logo_path` VARCHAR(255) NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");
echo "1. Table `tenants` ensured.\n";

// 2. Fetch existing app_settings to seed Tenant #1
$settings_res = $c->query("SELECT * FROM app_settings LIMIT 1");
$st = $settings_res ? $settings_res->fetch_assoc() : null;

$name = $st['hospital_name'] ?? 'RS Islam Gondanglegi';
$sub  = $st['hospital_subtitle'] ?? 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)';
$addr = $st['hospital_address'] ?? 'Jl. Hayam Wuruk No. 24, Gondanglegi, Malang';
$ph   = $st['hospital_phone'] ?? '(0341) 879222';
$city = $st['hospital_city'] ?? 'Kabupaten Malang';
$logo = $st['hospital_logo'] ?? null;

$check_t1 = $c->query("SELECT id FROM tenants WHERE id = 1");
if ($check_t1->num_rows === 0) {
    $stmt = $c->prepare("INSERT INTO tenants (id, code, name, slug, hospital_subtitle, address, phone, city, logo_path, is_active) VALUES (1, 'RSIG', ?, 'rsig', ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param('ssssss', $name, $sub, $addr, $ph, $city, $logo);
    $stmt->execute();
    echo "2. Seeded Tenant #1: {$name} (RSIG).\n";
} else {
    echo "2. Tenant #1 already exists.\n";
}

// 3. Update users.role enum to include 'super_admin'
$c->query("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('super_admin','admin','teknisi','ruangan') NOT NULL DEFAULT 'ruangan'");
echo "3. Altered `users.role` enum to include 'super_admin'.\n";

// 4. Add `tenant_id` to tables
$tables_to_tenant = [
    'users'                 => ['type' => 'INT NULL DEFAULT 1', 'default_val' => 1],
    'rooms'                 => ['type' => 'INT NOT NULL DEFAULT 1', 'default_val' => 1],
    'equipment_categories'  => ['type' => 'INT NULL DEFAULT 1', 'default_val' => 1],
    'medical_equipment'     => ['type' => 'INT NOT NULL DEFAULT 1', 'default_val' => 1],
    'work_orders'           => ['type' => 'INT NOT NULL DEFAULT 1', 'default_val' => 1],
    'calibration_logs'      => ['type' => 'INT NOT NULL DEFAULT 1', 'default_val' => 1],
    'preventive_schedules'  => ['type' => 'INT NOT NULL DEFAULT 1', 'default_val' => 1],
    'spareparts'            => ['type' => 'INT NOT NULL DEFAULT 1', 'default_val' => 1],
    'audit_logs'            => ['type' => 'INT NULL DEFAULT 1', 'default_val' => 1],
    'app_settings'          => ['type' => 'INT NOT NULL DEFAULT 1', 'default_val' => 1],
];

foreach ($tables_to_tenant as $table => $cfg) {
    // Check if column exists
    $col_check = $c->query("SHOW COLUMNS FROM `{$table}` LIKE 'tenant_id'");
    if ($col_check->num_rows === 0) {
        $c->query("ALTER TABLE `{$table}` ADD COLUMN `tenant_id` {$cfg['type']}, ADD INDEX (`tenant_id`)");
        echo "4. Added `tenant_id` to `{$table}`.\n";
    } else {
        echo "4. `tenant_id` already exists on `{$table}`.\n";
    }
    // Ensure existing rows are set to default_val
    $c->query("UPDATE `{$table}` SET `tenant_id` = {$cfg['default_val']} WHERE `tenant_id` IS NULL AND '{$table}' != 'users'");
}

// 5. Create or ensure Super Admin user
$check_sa = $c->query("SELECT id FROM users WHERE username = 'superadmin'");
if ($check_sa->num_rows === 0) {
    $hash = password_hash('password123', PASSWORD_BCRYPT);
    $c->query("
        INSERT INTO users (username, password_hash, full_name, role, room_id, tenant_id, is_active, created_at)
        VALUES ('superadmin', '{$hash}', 'Super Administrator (Holding)', 'super_admin', NULL, NULL, 1, NOW())
    ");
    echo "5. Created default super_admin account: 'superadmin' / 'password123'.\n";
} else {
    echo "5. Account 'superadmin' already exists.\n";
}

echo "\n=== MIGRATION COMPLETED SUCCESSFULLY! ===\n";
