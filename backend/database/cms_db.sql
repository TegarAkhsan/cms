-- ============================================================
-- CMS — Container Monitoring System
-- Database: MySQL
-- ✅ Jalankan file ini di phpMyAdmin tab SQL
-- ✅ Termasuk semua perubahan terbaru (status user, booking_status)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Buat & gunakan database
CREATE DATABASE IF NOT EXISTS cms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cms_db;

-- ── USERS ──────────────────────────────────────────────────
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS containers;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','operator','stakeholder') NOT NULL,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(100),
    port       VARCHAR(100),
    status     ENUM('pending','verified','rejected') DEFAULT 'verified',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CONTAINERS ─────────────────────────────────────────────
CREATE TABLE containers (
    id             VARCHAR(20) PRIMARY KEY,
    booking_no     VARCHAR(50),
    vessel         VARCHAR(100),
    voyage         VARCHAR(50),
    type           VARCHAR(30),
    weight         INT DEFAULT 0,
    commodity      VARCHAR(100),
    origin         VARCHAR(100),
    destination    VARCHAR(100),
    eta            DATE,
    status         ENUM('booking','gate_in','ship_arrival','discharge','yard_map','clearance','loading','ship_departure','delivery','completed') DEFAULT 'booking',
    booking_status ENUM('Ekspor','Impor') DEFAULT 'Ekspor',
    owner_id       INT,
    operator_id    INT,
    position_lat   DECIMAL(10,6) DEFAULT -7.257500,
    position_lng   DECIMAL(10,6) DEFAULT 112.752100,
    position_desc  VARCHAR(200),
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id)    REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── DOCUMENTS ──────────────────────────────────────────────
CREATE TABLE documents (
    id           VARCHAR(20) PRIMARY KEY,
    container_id VARCHAR(20),
    type         VARCHAR(100) NOT NULL,
    filename     VARCHAR(200),
    filepath     VARCHAR(300),
    status       ENUM('pending','approved','revision') DEFAULT 'pending',
    uploaded_by  INT,
    notes        TEXT,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (container_id) REFERENCES containers(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── EVENTS (Timeline) ──────────────────────────────────────
CREATE TABLE events (
    id           VARCHAR(20) PRIMARY KEY,
    container_id VARCHAR(20),
    event        VARCHAR(100) NOT NULL,
    actor        VARCHAR(100),
    note         TEXT,
    timestamp    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (container_id) REFERENCES containers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── NOTIFICATIONS ──────────────────────────────────────────
CREATE TABLE notifications (
    id           VARCHAR(20) PRIMARY KEY,
    user_id      INT,
    container_id VARCHAR(20),
    message      TEXT NOT NULL,
    type         ENUM('info','success','warning','danger') DEFAULT 'info',
    is_read      TINYINT(1) DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Users
-- Semua password default: "password"
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (username, password, role, name, email, port, status) VALUES
('admin',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',       'Administrator',       'admin@cms.id',           NULL,             'verified'),
('operator1',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator',    'Budi Santoso',        'budi@pelabuhan.id',      'Tanjung Perak',  'verified'),
('operator2',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator',    'Sari Dewi',           'sari@pelabuhan.id',      'Pelabuhan Merak','verified'),
('stakeholder1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'stakeholder', 'PT. Maju Sejahtera',  'cs@majusejahtera.id',    NULL,             'verified'),
('stakeholder2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'stakeholder', 'CV. Nusantara Cargo', 'info@nusantaracargo.id', NULL,             'verified');

-- ⚠️ Catatan: Hash di atas adalah untuk password 'password'
-- Jalankan fix_passwords.php setelah install untuk set password yang benar (admin123, op123, sk123, dll)

-- Containers
INSERT INTO containers (id, booking_no, vessel, voyage, type, weight, commodity, origin, destination, eta, status, booking_status, owner_id, operator_id, position_lat, position_lng, position_desc, created_at) VALUES
('CTR001','BK-2026-0312','KM. Nusantara Jaya','NJ-2026-03','20ft Dry',18500,'Elektronik','Jakarta','Surabaya','2026-03-05','gate_in','Ekspor',4,2,-7.257500,112.752100,'Yard A-12, Tanjung Perak','2026-03-01 08:00:00'),
('CTR002','BK-2026-0287','MV. Samudra Biru','SB-2026-02','40ft HC',24000,'Tekstil','Surabaya','Makassar','2026-03-08','on_vessel','Ekspor',4,2,-7.180000,112.720000,'On Board MV. Samudra Biru','2026-02-28 10:00:00'),
('CTR003','BK-2026-0301','KM. Garuda Mas','GM-2026-04','20ft Reefer',12000,'Produk Segar','Makassar','Jakarta','2026-03-10','clearance','Impor',5,3,-6.105000,106.830000,'Clearance Bea Cukai - Tanjung Priok','2026-03-02 07:30:00'),
('CTR004','BK-2026-0315','KM. Nusantara Jaya','NJ-2026-03','40ft Dry',28000,'Mesin & Spare Part','Jakarta','Surabaya','2026-03-05','discharged','Ekspor',5,2,-7.260000,112.750000,'Discharged - Menunggu Gate Out','2026-03-01 09:00:00'),
('CTR005','BK-2026-0290','MV. Cemara Indah','CI-2026-02','20ft Dry',15000,'Bahan Kimia','Batam','Surabaya','2026-03-12','on_delivery','Impor',4,2,-7.300000,112.780000,'Dalam Pengiriman Truk - KM 45','2026-02-27 14:00:00'),
('CTR006','BK-2026-0278','MV. Cemara Indah','CI-2026-01','40ft Reefer',22000,'Daging Sapi','Surabaya','Papua','2026-03-03','completed','Ekspor',5,2,-7.280000,112.740000,'Depo - Selesai','2026-02-20 11:00:00');

-- Documents
INSERT INTO documents (id, container_id, type, filename, status, uploaded_by, notes, created_at) VALUES
('DOC001','CTR001','Bill of Lading','BL_CTR001.pdf','approved',2,'Terverifikasi','2026-03-01 09:00:00'),
('DOC002','CTR001','Packing List','PL_CTR001.pdf','approved',2,'','2026-03-01 09:30:00'),
('DOC003','CTR001','Invoice','INV_CTR001.pdf','pending',4,'Menunggu verifikasi operator','2026-03-02 10:00:00'),
('DOC004','CTR002','Bill of Lading','BL_CTR002.pdf','approved',2,'','2026-02-28 11:00:00'),
('DOC005','CTR003','Customs Declaration','CD_CTR003.pdf','revision',4,'Data komoditi perlu dikoreksi','2026-03-02 08:00:00'),
('DOC006','CTR004','Delivery Order','DO_CTR004.pdf','approved',2,'','2026-03-03 10:00:00'),
('DOC007','CTR005','Surat Jalan','SJ_CTR005.pdf','approved',2,'','2026-03-04 07:00:00');

-- Events
INSERT INTO events (id, container_id, event, actor, note, timestamp) VALUES
('EVT001','CTR001','Booking Diterima','System','Order dibuat','2026-03-01 08:00:00'),
('EVT002','CTR001','Gate-In Terminal','Operator: Budi Santoso','Kontainer masuk terminal','2026-03-02 06:30:00'),
('EVT003','CTR001','Loaded On Vessel','Operator: Budi Santoso','Dimuat ke KM. Nusantara Jaya','2026-03-03 14:00:00'),
('EVT004','CTR001','Discharged','Operator: Budi Santoso','Dibongkar di Tanjung Perak','2026-03-05 09:00:00'),
('EVT005','CTR001','Yard Placement','Operator: Budi Santoso','Ditempatkan di Yard A-12','2026-03-05 11:00:00'),
('EVT006','CTR002','Booking Diterima','System','','2026-02-28 10:00:00'),
('EVT007','CTR002','Gate-In Terminal','Operator: Budi Santoso','','2026-03-01 07:00:00'),
('EVT008','CTR002','Loaded On Vessel','Operator: Budi Santoso','','2026-03-02 15:00:00'),
('EVT009','CTR003','Booking Diterima','System','','2026-03-02 07:30:00'),
('EVT010','CTR003','Clearance Diajukan','Stakeholder: CV. Nusantara Cargo','Dokumen disubmit','2026-03-03 09:00:00'),
('EVT011','CTR004','Discharged','Operator: Budi Santoso','','2026-03-05 08:00:00'),
('EVT012','CTR005','Gate-Out Terminal','Operator: Budi Santoso','Keluar terminal ke truk','2026-03-04 06:00:00'),
('EVT013','CTR005','On Delivery','Operator: Budi Santoso','Truk B 1234 CD','2026-03-04 07:30:00'),
('EVT014','CTR006','Gate-In Depo','Operator: Budi Santoso','Sampai di depo tujuan','2026-03-03 16:00:00'),
('EVT015','CTR006','Completed','System','Pengiriman selesai','2026-03-03 16:30:00');

-- Notifications
INSERT INTO notifications (id, user_id, container_id, message, type, is_read, created_at) VALUES
('NTF001',4,'CTR001','Dokumen Invoice CTR001 menunggu verifikasi','warning',0,'2026-03-02 10:00:00'),
('NTF002',4,'CTR003','Dokumen Customs Declaration perlu revisi','danger',0,'2026-03-03 08:00:00'),
('NTF003',5,'CTR006','Pengiriman CTR006 telah selesai','success',1,'2026-03-03 16:30:00'),
('NTF004',2,'CTR003','Dokumen baru diupload untuk CTR003','info',0,'2026-03-02 08:00:00'),
('NTF005',1,'CTR001','Kontainer baru terdaftar: CTR001','info',1,'2026-03-01 08:00:00');

-- ============================================================
-- ✅ SELESAI — Database siap digunakan
-- Login default:
--   admin       / password  (Admin)
--   operator1   / password  (Operator - Tanjung Perak)
--   operator2   / password  (Operator - Merak)
--   stakeholder1/ password  (Stakeholder - PT. Maju Sejahtera)
--   stakeholder2/ password  (Stakeholder - CV. Nusantara Cargo)
-- ============================================================
