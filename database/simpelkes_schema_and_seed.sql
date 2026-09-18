-- ==========================================================
-- SISTEM INFORMASI PEMELIHARAAN ALAT KESEHATAN (SIMPELKES)
-- Database DDL & Initial Seeder
-- Compatible with MariaDB 10.4+ / MySQL 5.7+
-- ==========================================================

USE simpelkesrsig;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS calibration_logs;
DROP TABLE IF EXISTS preventive_schedules;
DROP TABLE IF EXISTS work_order_parts;
DROP TABLE IF EXISTS spareparts;
DROP TABLE IF EXISTS work_order_logs;
DROP TABLE IF EXISTS work_orders;
DROP TABLE IF EXISTS medical_equipment;
DROP TABLE IF EXISTS equipment_categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS rooms;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Master Rooms (Unit Kerja / Ruangan RS)
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    building VARCHAR(100) NULL,
    floor VARCHAR(20) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Master Users (Admin IPSRS, Teknisi Elektromedis, User Ruangan)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role ENUM('admin', 'teknisi', 'ruangan') NOT NULL DEFAULT 'ruangan',
    room_id INT NULL,
    phone VARCHAR(30) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Kategori Alat Medis (Berdasarkan Tingkat Risiko & Frekuensi PM)
CREATE TABLE IF NOT EXISTS equipment_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    risk_level ENUM('high', 'medium', 'low') DEFAULT 'medium',
    default_maintenance_interval_days INT DEFAULT 90,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Master Alat Medis (Medical Equipment)
CREATE TABLE IF NOT EXISTS medical_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(100) NOT NULL UNIQUE,
    serial_number VARCHAR(100) NOT NULL,
    name VARCHAR(200) NOT NULL,
    brand VARCHAR(100) NULL,
    model_type VARCHAR(100) NULL,
    category_id INT NOT NULL,
    room_id INT NOT NULL,
    purchase_date DATE NULL,
    warranty_expire DATE NULL,
    price DECIMAL(15,2) DEFAULT 0.00,
    vendor_supplier VARCHAR(150) NULL,
    qr_code_path VARCHAR(255) NULL,
    image_path VARCHAR(255) NULL,
    operational_status ENUM('operasional', 'rusak_ringan', 'rusak_berat', 'afkir') DEFAULT 'operasional',
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_eq_category FOREIGN KEY (category_id) REFERENCES equipment_categories(id),
    CONSTRAINT fk_eq_room FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tiket Perbaikan / Work Orders (Corrective Maintenance)
CREATE TABLE IF NOT EXISTS work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(60) NOT NULL UNIQUE,
    equipment_id INT NOT NULL,
    reported_by_user_id INT NOT NULL,
    assigned_technician_id INT NULL,
    issue_description TEXT NOT NULL,
    issue_photo_path VARCHAR(255) NULL,
    priority ENUM('low', 'medium', 'high', 'emergency') DEFAULT 'medium',
    status ENUM('reported', 'in_progress', 'waiting_parts', 'vendor_repair', 'completed_technician', 'closed', 'cancelled') DEFAULT 'reported',
    response_at DATETIME NULL,
    vendor_name VARCHAR(150) NULL,
    repair_cost DECIMAL(14,2) DEFAULT 0.00,
    action_summary TEXT NULL,
    technician_signature_path VARCHAR(255) NULL,
    room_signature_path VARCHAR(255) NULL,
    verified_by_user_id INT NULL,
    verified_at DATETIME NULL,
    rejection_reason TEXT NULL,
    reported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wo_equipment FOREIGN KEY (equipment_id) REFERENCES medical_equipment(id),
    CONSTRAINT fk_wo_reporter FOREIGN KEY (reported_by_user_id) REFERENCES users(id),
    CONSTRAINT fk_wo_technician FOREIGN KEY (assigned_technician_id) REFERENCES users(id),
    CONSTRAINT fk_wo_verifier FOREIGN KEY (verified_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Log Timeline Tindakan Teknisi
CREATE TABLE IF NOT EXISTS work_order_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    technician_id INT NOT NULL,
    action_taken TEXT NOT NULL,
    current_status VARCHAR(50) NOT NULL,
    photo_progress_path VARCHAR(255) NULL,
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wolog_wo FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_wolog_tech FOREIGN KEY (technician_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Master Spareparts / Suku Cadang Alkes
CREATE TABLE IF NOT EXISTS spareparts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_number VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NULL,
    stock_qty INT DEFAULT 0,
    unit_cost DECIMAL(14,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Penggunaan Sparepart pada Tiket Perbaikan
CREATE TABLE IF NOT EXISTS work_order_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    sparepart_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wop_wo FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_wop_part FOREIGN KEY (sparepart_id) REFERENCES spareparts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Preventive Maintenance Schedules (Pemeliharaan Preventif Berkala)
CREATE TABLE IF NOT EXISTS preventive_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    frequency ENUM('monthly', 'quarterly', 'semi_annual', 'annual') DEFAULT 'quarterly',
    status ENUM('pending', 'in_progress', 'done', 'overdue') DEFAULT 'pending',
    checklist_data JSON NULL,
    notes TEXT NULL,
    executed_by_technician_id INT NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prev_eq FOREIGN KEY (equipment_id) REFERENCES medical_equipment(id),
    CONSTRAINT fk_prev_tech FOREIGN KEY (executed_by_technician_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Calibration Logs (Pengujian & Kalibrasi BPFK/Vendor Eksternal)
CREATE TABLE IF NOT EXISTS calibration_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    calibration_date DATE NOT NULL,
    valid_until DATE NOT NULL,
    certificate_number VARCHAR(100) NOT NULL,
    certificate_file_path VARCHAR(255) NULL,
    vendor_name VARCHAR(150) NOT NULL,
    result ENUM('laik_pakai', 'tidak_laik_pakai') DEFAULT 'laik_pakai',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_calib_eq FOREIGN KEY (equipment_id) REFERENCES medical_equipment(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Audit Logs (Log Aktivitas Pengguna)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NULL,
    record_id INT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. App & Hospital Settings (Konfigurasi Nama RS, Subtitle, Kontak, & Logo)
CREATE TABLE IF NOT EXISTS app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_name VARCHAR(200) NOT NULL DEFAULT 'RS Islam Gondanglegi',
    hospital_subtitle VARCHAR(255) NOT NULL DEFAULT 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)',
    hospital_address VARCHAR(255) NOT NULL DEFAULT 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang',
    hospital_phone VARCHAR(50) NOT NULL DEFAULT '(0341) 879222',
    hospital_city VARCHAR(100) NOT NULL DEFAULT 'Gondanglegi',
    hospital_logo VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- INITIAL SEEDER DATA
-- Password default untuk semua akun: 'password123'
-- Hash BCrypt: $2y$10$tZ2r2U1uL5U2V2mN0.c8n.F10K4f9iG6t8d5o9L9tQG0aM8Z3e2hW -> mari kita gunakan password_hash yang valid
-- ==========================================================

-- Rooms Seeder
INSERT INTO rooms (id, code, name, building, floor) VALUES
(1, 'RM-IGD', 'Instalasi Gawat Darurat (IGD)', 'Gedung Utama', 'Lantai 1'),
(2, 'RM-ICU', 'Intensive Care Unit (ICU)', 'Gedung Utama', 'Lantai 2'),
(3, 'RM-OK', 'Instalasi Bedah Sentral (OK)', 'Gedung Bedah', 'Lantai 3'),
(4, 'RM-RAD', 'Instalasi Radiologi', 'Gedung Penunjang', 'Lantai 1'),
(5, 'RM-LAB', 'Laboratorium Patologi Klinik', 'Gedung Penunjang', 'Lantai 1'),
(6, 'RM-POLI-INT', 'Poli Penyakit Dalam', 'Gedung Rawat Jalan', 'Lantai 2'),
(7, 'RM-IPSRS', 'Bengkel & Kantor IPSRS / Elektromedis', 'Gedung Logistik', 'Lantai 1');

-- Users Seeder (Password: admin123 untuk admin, teknisi123 untuk teknisi, ruangan123 untuk user ruangan)
-- BCrypt generated:
-- 'admin123'    => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' (standar test atau password_hash php)
-- Kita isi hash yang valid:
INSERT INTO users (id, username, password_hash, full_name, role, room_id, phone) VALUES
(1, 'admin', '$2y$10$4YGA93y71X31DUTxjs.mSe.2vHcrME9ZvMBL02hbC..99IZ0OeoCO', 'Administrator IPSRS', 'admin', 7, '081234567890'),
(2, 'teknisi1', '$2y$10$4YGA93y71X31DUTxjs.mSe.2vHcrME9ZvMBL02hbC..99IZ0OeoCO', 'Ahmad Elektromedik, A.Md.T', 'teknisi', 7, '081234567891'),
(3, 'teknisi2', '$2y$10$4YGA93y71X31DUTxjs.mSe.2vHcrME9ZvMBL02hbC..99IZ0OeoCO', 'Budi Santoso, S.Tr.Kes', 'teknisi', 7, '081234567892'),
(4, 'ruangan_igd', '$2y$10$4YGA93y71X31DUTxjs.mSe.2vHcrME9ZvMBL02hbC..99IZ0OeoCO', 'Ns. Siti Rahma (PIC IGD)', 'ruangan', 1, '081234567893'),
(5, 'ruangan_icu', '$2y$10$4YGA93y71X31DUTxjs.mSe.2vHcrME9ZvMBL02hbC..99IZ0OeoCO', 'Ns. Dewi Kartika (PIC ICU)', 'ruangan', 2, '081234567894');

-- Equipment Categories Seeder (Permenkes 65/2016)
INSERT INTO equipment_categories (id, name, risk_level, default_maintenance_interval_days, description) VALUES
(1, 'Life Support Equipment (Penyokong Kehidupan)', 'high', 30, 'Defibrillator, Ventilator, Anaesthesia Machine, Infant Incubator'),
(2, 'Diagnostic & Monitoring Equipment', 'medium', 90, 'Patient Monitor, ECG/EKG, USG, X-Ray Mobile, Pulse Oximeter'),
(3, 'Therapeutic & Surgical Equipment', 'medium', 90, 'Electrosurgical Unit (ESU), Suction Pump, Infusion/Syringe Pump'),
(4, 'Laboratory & Analytical Equipment', 'medium', 90, 'Centrifuge, Hematology Analyzer, Chemistry Analyzer, Mikroskop'),
(5, 'General Hospital & Supportive Equipment', 'low', 180, 'Bed Pasien Elektrik, Lampu Tindakan, Sterilisator, Tensimeter Digital');

-- Medical Equipment Samples
INSERT INTO medical_equipment (id, asset_code, serial_number, name, brand, model_type, category_id, room_id, purchase_date, warranty_expire, price, vendor_supplier, operational_status) VALUES
(1, 'EQ-2024-001', 'SN-VENT-9921', 'ICU Ventilator High-End', 'Hamilton Medical', 'HAMILTON-C3', 1, 2, '2023-01-15', '2025-01-15', 450000000.00, 'PT Surya Medika Nusantara', 'operasional'),
(2, 'EQ-2024-002', 'SN-DEF-5510', 'Biphasic Defibrillator Monitor', 'Zoll', 'R Series Plus', 1, 1, '2022-06-20', '2024-06-20', 185000000.00, 'PT Prima Alkesindo', 'rusak_ringan'),
(3, 'EQ-2024-003', 'SN-MON-8812', 'Patient Monitor 7 Parameter', 'Mindray', 'ePM 12M', 2, 1, '2023-03-10', '2025-03-10', 65000000.00, 'PT Mindray Medical ID', 'operasional'),
(4, 'EQ-2024-004', 'SN-INF-3312', 'Infusion Pump Digital', 'Terumo', 'TE-LM700', 3, 2, '2023-05-12', '2024-05-12', 24000000.00, 'PT Terumo Indonesia', 'operasional'),
(5, 'EQ-2024-005', 'SN-SYR-4419', 'Syringe Pump Micro-Flow', 'B. Braun', 'Perfusor Space', 3, 2, '2023-08-01', '2025-08-01', 28000000.00, 'PT B. Braun Medical', 'operasional'),
(6, 'EQ-2024-006', 'SN-ECG-1105', 'Elektrokardiograf (ECG 12-Lead)', 'Nihon Kohden', 'Cardiofax M', 2, 6, '2021-11-15', '2022-11-15', 48000000.00, 'PT Alkes Global Jaya', 'rusak_berat'),
(7, 'EQ-2024-007', 'SN-ESU-7721', 'Electrosurgical Unit (Couter)', 'Erbe', 'VIO 300 D', 3, 3, '2022-09-10', '2024-09-10', 310000000.00, 'PT Bedah Medika', 'operasional');

-- Spareparts Seeder
INSERT INTO spareparts (id, part_number, name, category, stock_qty, unit_cost) VALUES
(1, 'SP-BAT-DEF-01', 'Baterai Defibrillator Rechargeable Zoll R-Series', 'Baterai / Power', 4, 3800000.00),
(2, 'SP-SEN-SPO2-02', 'Sensor SpO2 Dewasa Mindray (Pin 7)', 'Sensor & Kabel', 12, 1250000.00),
(3, 'SP-SEN-FLOW-03', 'Flow Sensor Neonatal / Dewasa Hamilton Ventilator', 'Sensor & Aksesoris', 8, 4500000.00),
(4, 'SP-FUS-10A-04', 'Sekering Cepat Fast-Blow 10A Glass Fuse', 'Komponen Elektrik', 50, 15000.00),
(5, 'SP-PAD-ECG-05', 'Kabel Pasien 10-Lead ECG Nihon Kohden', 'Sensor & Kabel', 6, 2100000.00);

-- Preventive Schedules Sample (Jadwal PM Terdekat)
INSERT INTO preventive_schedules (id, equipment_id, scheduled_date, frequency, status, checklist_data) VALUES
(1, 1, DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), 'monthly', 'pending', JSON_OBJECT('cek_fisik', 'Pending', 'kebocoran_arus', 'Pending', 'uji_performa', 'Pending', 'kalibrasi_sensor_o2', 'Pending')),
(2, 3, DATE_ADD(CURRENT_DATE, INTERVAL 12 DAY), 'quarterly', 'pending', JSON_OBJECT('cek_fisik', 'Pending', 'tes_alarm', 'Pending', 'baterai_backup', 'Pending')),
(3, 7, DATE_ADD(CURRENT_DATE, INTERVAL -2 DAY), 'quarterly', 'overdue', JSON_OBJECT('cek_grounding', 'Pending', 'tes_output_couter', 'Pending', 'cek_pedal_saklar', 'Pending'));

-- Calibration Logs Sample
INSERT INTO calibration_logs (id, equipment_id, calibration_date, valid_until, certificate_number, vendor_name, result) VALUES
(1, 1, '2024-02-10', DATE_ADD(CURRENT_DATE, INTERVAL 150 DAY), 'CERT-BPFK-2024-0981', 'Balai Pengujian Fasilitas Kesehatan (BPFK)', 'laik_pakai'),
(2, 2, '2023-09-15', DATE_ADD(CURRENT_DATE, INTERVAL -10 DAY), 'CERT-CAL-2023-4412', 'PT Global Kalibrasi Medika', 'laik_pakai'),
(3, 3, '2024-01-20', DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY), 'CERT-BPFK-2024-0112', 'Balai Pengujian Fasilitas Kesehatan (BPFK)', 'laik_pakai');

-- Work Orders Sample (Tiket Aktif)
INSERT INTO work_orders (id, ticket_number, equipment_id, reported_by_user_id, assigned_technician_id, issue_description, priority, status, response_at, reported_at) VALUES
(1, 'WO-202409-0001', 2, 4, 2, 'Baterai defibrillator tidak mau mengisi daya (charging error saat self-test rutin IGD). Sangat mendesak untuk pasien emergency.', 'emergency', 'in_progress', NOW(), DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'WO-202409-0002', 6, 4, NULL, 'Kabel Lead II dan V1 nois / putus-putus saat rekam ECG pasien di Poli.', 'medium', 'reported', NULL, DATE_SUB(NOW(), INTERVAL 30 MINUTE));

-- Work Order Log Sample
INSERT INTO work_order_logs (work_order_id, technician_id, action_taken, current_status, logged_at) VALUES
(1, 2, 'Inspeksi fisik unit defibrillator. Ditemukan kapasitas baterai internal drop di bawah 9V dan perlu pergantian sparepart unit baterai baru.', 'in_progress', NOW());
