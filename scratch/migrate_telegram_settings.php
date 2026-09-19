<?php
/**
 * Database Migration for Telegram Bot Settings in app_settings
 */
$mysqli = new mysqli('localhost', 'root', 'bismillah', 'simpelkesrsig');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

echo "=== Adding Telegram columns to app_settings ===\n";

$columns = [
    'telegram_bot_token'       => 'VARCHAR(255) NULL AFTER hospital_logo',
    'telegram_chat_id'         => 'VARCHAR(100) NULL AFTER telegram_bot_token',
    'telegram_notif_emergency' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER telegram_chat_id',
    'telegram_notif_routine'   => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER telegram_notif_emergency',
    'telegram_notif_validation'=> 'TINYINT(1) NOT NULL DEFAULT 1 AFTER telegram_notif_routine',
    'telegram_notif_calibration'=> 'TINYINT(1) NOT NULL DEFAULT 1 AFTER telegram_notif_validation'
];

foreach ($columns as $col => $definition) {
    $check = $mysqli->query("SHOW COLUMNS FROM app_settings LIKE '{$col}'");
    if ($check->num_rows === 0) {
        $sql = "ALTER TABLE app_settings ADD COLUMN {$col} {$definition}";
        if ($mysqli->query($sql)) {
            echo "Added column: {$col}\n";
        } else {
            echo "Error adding {$col}: " . $mysqli->error . "\n";
        }
    } else {
        echo "Column {$col} already exists.\n";
    }
}

echo "Migration completed successfully!\n";
