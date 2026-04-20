-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 20, 2026 at 04:59 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `containers`
--

CREATE TABLE `containers` (
  `id` varchar(20) NOT NULL,
  `booking_no` varchar(50) DEFAULT NULL,
  `vessel` varchar(100) DEFAULT NULL,
  `voyage` varchar(50) DEFAULT NULL,
  `type` varchar(30) DEFAULT NULL,
  `weight` int DEFAULT '0',
  `commodity` varchar(100) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `eta` date DEFAULT NULL,
  `status` enum('booking','gate_in','on_vessel','discharged','clearance','on_delivery','gate_in_depo','completed','delay') DEFAULT 'booking',
  `owner_id` int DEFAULT NULL,
  `operator_id` int DEFAULT NULL,
  `position_lat` decimal(10,6) DEFAULT '-7.257500',
  `position_lng` decimal(10,6) DEFAULT '112.752100',
  `position_desc` varchar(200) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `containers`
--

INSERT INTO `containers` (`id`, `booking_no`, `vessel`, `voyage`, `type`, `weight`, `commodity`, `origin`, `destination`, `eta`, `status`, `owner_id`, `operator_id`, `position_lat`, `position_lng`, `position_desc`, `created_at`) VALUES
('CTR001', 'BK-2026-0312', 'KM. Nusantara Jaya', 'NJ-2026-03', '20ft Dry', 18500, 'Elektronik', 'Jakarta', 'Surabaya', '2026-03-05', 'gate_in', 4, 2, '-7.257500', '112.752100', 'Yard A-12, Tanjung Perak', '2026-03-01 08:00:00'),
('CTR002', 'BK-2026-0287', 'MV. Samudra Biru', 'SB-2026-02', '40ft HC', 24000, 'Tekstil', 'Surabaya', 'Makassar', '2026-03-08', 'on_vessel', 4, 2, '-7.180000', '112.720000', 'On Board MV. Samudra Biru', '2026-02-28 10:00:00'),
('CTR003', 'BK-2026-0301', 'KM. Garuda Mas', 'GM-2026-04', '20ft Reefer', 12000, 'Produk Segar', 'Makassar', 'Jakarta', '2026-03-10', 'clearance', 5, 3, '-6.105000', '106.830000', 'Clearance Bea Cukai - Tanjung Priok', '2026-03-02 07:30:00'),
('CTR004', 'BK-2026-0315', 'KM. Nusantara Jaya', 'NJ-2026-03', '40ft Dry', 28000, 'Mesin & Spare Part', 'Jakarta', 'Surabaya', '2026-03-05', 'discharged', 5, 2, '-7.260000', '112.750000', 'Discharged - Menunggu Gate Out', '2026-03-01 09:00:00'),
('CTR005', 'BK-2026-0290', 'MV. Cemara Indah', 'CI-2026-02', '20ft Dry', 15000, 'Bahan Kimia', 'Batam', 'Surabaya', '2026-03-12', 'on_delivery', 4, 2, '-7.300000', '112.780000', 'Dalam Pengiriman Truk - KM 45', '2026-02-27 14:00:00'),
('CTR006', 'BK-2026-0278', 'MV. Cemara Indah', 'CI-2026-01', '40ft Reefer', 22000, 'Daging Sapi', 'Surabaya', 'Papua', '2026-03-03', 'completed', 5, 2, '-7.280000', '112.740000', 'Depo - Selesai', '2026-02-20 11:00:00'),
('CTR007', 'BK-2026-0320', 'KM. Garuda Mas', 'GM-2026-04', '20ft Dry', 16000, 'Furnitur', 'Jepara', 'Surabaya', '2026-03-08', 'gate_in', 4, 2, '-7.258000', '112.753000', 'Yard B-01, Tanjung Perak', '2026-03-05 08:00:00'),
('CTR008', 'BK-2026-0321', 'MV. Samudra Biru', 'SB-2026-02', '40ft Dry', 26000, 'Elektronik', 'Batam', 'Jakarta', '2026-03-09', 'booking', 5, 3, '-6.105500', '106.831000', 'Pending Depo Placement', '2026-03-06 09:15:00'),
('CTR009', 'BK-2026-0322', 'MV. Cemara Indah', 'CI-2026-02', '20ft Reefer', 14000, 'Ikan Laut', 'Makassar', 'Surabaya', '2026-03-10', 'on_vessel', 4, 2, '-6.500000', '115.000000', 'On Board MV. Cemara Indah', '2026-03-05 10:20:00'),
('CTR010', 'BK-2026-0323', 'KM. Nusantara Jaya', 'NJ-2026-03', '40ft HC', 22000, 'Plastik', 'Surabaya', 'Makassar', '2026-03-11', 'completed', 5, 2, '-5.147600', '119.432700', 'Depo Pelindo Makassar', '2026-03-01 11:30:00'),
('CTR011', 'BK-2026-0324', 'MV. Samudra Biru', 'SB-2026-02', '20ft Dry', 17000, 'Ban Mobil', 'Jakarta', 'Papua', '2026-03-15', 'clearance', 4, 2, '-6.100000', '106.830000', 'Bea Cukai Priok', '2026-03-07 14:00:00'),
('CTR012', 'BK-2026-0325', 'KM. Garuda Mas', 'GM-2026-05', '20ft Dry', 19000, 'Besi Baja', 'Surabaya', 'Batam', '2026-03-16', 'discharged', 5, 2, '-7.265000', '112.758000', 'Terminal TPS', '2026-03-08 14:00:00'),
('CTR013', 'BK-2026-0326', 'MV. Samudra Biru', 'SB-2026-03', '40ft HC', 25000, 'Kertas', 'Surabaya', 'Makassar', '2026-03-20', 'booking', 4, 2, '-7.257500', '112.752100', 'Menunggu Kedatangan', '2026-03-09 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` varchar(20) NOT NULL,
  `container_id` varchar(20) DEFAULT NULL,
  `type` varchar(100) NOT NULL,
  `filename` varchar(200) DEFAULT NULL,
  `filepath` varchar(300) DEFAULT NULL,
  `status` enum('pending','approved','revision') DEFAULT 'pending',
  `uploaded_by` int DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `container_id`, `type`, `filename`, `filepath`, `status`, `uploaded_by`, `notes`, `created_at`) VALUES
('DOC001', 'CTR001', 'Bill of Lading', 'BL_CTR001.pdf', NULL, 'approved', 2, 'Terverifikasi', '2026-03-01 09:00:00'),
('DOC002', 'CTR001', 'Packing List', 'PL_CTR001.pdf', NULL, 'approved', 2, '', '2026-03-01 09:30:00'),
('DOC003', 'CTR001', 'Invoice', 'INV_CTR001.pdf', NULL, 'pending', 4, 'Menunggu verifikasi operator', '2026-03-02 10:00:00'),
('DOC004', 'CTR002', 'Bill of Lading', 'BL_CTR002.pdf', NULL, 'approved', 2, '', '2026-02-28 11:00:00'),
('DOC005', 'CTR003', 'Customs Declaration', 'CD_CTR003.pdf', NULL, 'revision', 4, 'Data komoditi perlu dikoreksi', '2026-03-02 08:00:00'),
('DOC006', 'CTR004', 'Delivery Order', 'DO_CTR004.pdf', NULL, 'approved', 2, '', '2026-03-03 10:00:00'),
('DOC007', 'CTR005', 'Surat Jalan', 'SJ_CTR005.pdf', NULL, 'approved', 2, '', '2026-03-04 07:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` varchar(20) NOT NULL,
  `container_id` varchar(20) DEFAULT NULL,
  `event` varchar(100) NOT NULL,
  `actor` varchar(100) DEFAULT NULL,
  `note` text,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `container_id`, `event`, `actor`, `note`, `timestamp`) VALUES
('EVT001', 'CTR001', 'Booking Diterima', 'System', 'Order dibuat', '2026-03-01 08:00:00'),
('EVT002', 'CTR001', 'Gate-In Terminal', 'Operator: Budi Santoso', 'Kontainer masuk terminal', '2026-03-02 06:30:00'),
('EVT003', 'CTR001', 'Loaded On Vessel', 'Operator: Budi Santoso', 'Dimuat ke KM. Nusantara Jaya', '2026-03-03 14:00:00'),
('EVT004', 'CTR001', 'Discharged', 'Operator: Budi Santoso', 'Dibongkar di Tanjung Perak', '2026-03-05 09:00:00'),
('EVT005', 'CTR001', 'Yard Placement', 'Operator: Budi Santoso', 'Ditempatkan di Yard A-12', '2026-03-05 11:00:00'),
('EVT006', 'CTR002', 'Booking Diterima', 'System', '', '2026-02-28 10:00:00'),
('EVT007', 'CTR002', 'Gate-In Terminal', 'Operator: Budi Santoso', '', '2026-03-01 07:00:00'),
('EVT008', 'CTR002', 'Loaded On Vessel', 'Operator: Budi Santoso', '', '2026-03-02 15:00:00'),
('EVT009', 'CTR003', 'Booking Diterima', 'System', '', '2026-03-02 07:30:00'),
('EVT010', 'CTR003', 'Clearance Diajukan', 'Stakeholder: CV. Nusantara Cargo', 'Dokumen disubmit', '2026-03-03 09:00:00'),
('EVT011', 'CTR004', 'Discharged', 'Operator: Budi Santoso', '', '2026-03-05 08:00:00'),
('EVT012', 'CTR005', 'Gate-Out Terminal', 'Operator: Budi Santoso', 'Keluar terminal ke truk', '2026-03-04 06:00:00'),
('EVT013', 'CTR005', 'On Delivery', 'Operator: Budi Santoso', 'Truk B 1234 CD', '2026-03-04 07:30:00'),
('EVT014', 'CTR006', 'Gate-In Depo', 'Operator: Budi Santoso', 'Sampai di depo tujuan', '2026-03-03 16:00:00'),
('EVT015', 'CTR006', 'Completed', 'System', 'Pengiriman selesai', '2026-03-03 16:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` varchar(20) NOT NULL,
  `user_id` int DEFAULT NULL,
  `container_id` varchar(20) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `container_id`, `message`, `type`, `is_read`, `created_at`) VALUES
('NTF001', 4, 'CTR001', 'Dokumen Invoice CTR001 menunggu verifikasi', 'warning', 1, '2026-03-02 10:00:00'),
('NTF002', 4, 'CTR003', 'Dokumen Customs Declaration perlu revisi', 'danger', 1, '2026-03-03 08:00:00'),
('NTF003', 5, 'CTR006', 'Pengiriman CTR006 telah selesai', 'success', 1, '2026-03-03 16:30:00'),
('NTF004', 2, 'CTR003', 'Dokumen baru diupload untuk CTR003', 'info', 0, '2026-03-02 08:00:00'),
('NTF005', 1, 'CTR001', 'Kontainer baru terdaftar: CTR001', 'info', 1, '2026-03-01 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','operator','stakeholder') NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `port` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`, `email`, `port`, `created_at`) VALUES
(1, 'admin', '$2y$10$eaUv9Sn/aB.vnz7mSNkJO.u2a5FUZPvKWoeE04kcAIuadKtc8FOpW', 'admin', 'Administrator', 'admin@cms.id', NULL, '2026-04-19 16:22:19'),
(2, 'operator1', '$2y$10$horeWbcu/d22HzlKXDwgAuhAgowvKTyrb1wVimG.gjBQGWSnV7s2.', 'operator', 'Budi Santoso', 'budi@pelabuhan.id', 'Tanjung Perak', '2026-04-19 16:22:19'),
(3, 'operator2', '$2y$10$o3k5Z8yJphip6MuPxeYGmuclZG0x1L4i718FUJz9B0OOZXPIRinaW', 'operator', 'Sari Dewi', 'sari@pelabuhan.id', 'Pelabuhan Merak', '2026-04-19 16:22:19'),
(4, 'stakeholder1', '$2y$10$bHV/0sqzQF920z9a40w6e.8/3.eyBX4HxC/U6LYxK0202GnYNQCCW', 'stakeholder', 'PT. Maju Sejahtera', 'cs@majusejahtera.id', NULL, '2026-04-19 16:22:19'),
(5, 'stakeholder2', '$2y$10$D8fzZFavf9dIL.o5Ff88KulbAoWDm8IxQsQprQD.gc5cwNIJfgYZG', 'stakeholder', 'CV. Nusantara Cargo', 'info@nusantaracargo.id', NULL, '2026-04-19 16:22:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `containers`
--
ALTER TABLE `containers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `operator_id` (`operator_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `container_id` (`container_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `container_id` (`container_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `containers`
--
ALTER TABLE `containers`
  ADD CONSTRAINT `containers_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `containers_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`container_id`) REFERENCES `containers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`container_id`) REFERENCES `containers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
