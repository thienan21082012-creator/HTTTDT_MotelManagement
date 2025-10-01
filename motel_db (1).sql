-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 01, 2025 at 08:04 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `motel_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `billing_month` int(2) NOT NULL,
  `billing_year` int(4) NOT NULL,
  `rent_amount` decimal(10,2) NOT NULL,
  `electricity_amount` decimal(10,2) DEFAULT 0.00,
  `water_amount` decimal(10,2) DEFAULT 0.00,
  `service_fee` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'unpaid',
  `created_at` datetime DEFAULT current_timestamp(),
  `person` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `user_id`, `room_id`, `billing_month`, `billing_year`, `rent_amount`, `electricity_amount`, `water_amount`, `service_fee`, `total_amount`, `status`, `created_at`, `person`) VALUES
(14, 5, 1, 9, 2025, 2500000.00, 308000.00, 200000.00, 200000.00, 3208000.00, 'paid', '2025-09-26 23:17:52', 2),
(15, 5, 2, 9, 2025, 2000000.00, 160000.00, 100000.00, 200000.00, 2460000.00, 'paid', '2025-09-26 23:17:55', 1);

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `electricity_readings`
--

CREATE TABLE `electricity_readings` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `old_reading` int(11) NOT NULL,
  `new_reading` int(11) NOT NULL,
  `reading_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `electricity_readings`
--

INSERT INTO `electricity_readings` (`id`, `room_id`, `month`, `year`, `old_reading`, `new_reading`, `reading_date`) VALUES
(1, 4, 9, 2025, 100, 200, '2025-09-12'),
(2, 5, 9, 2025, 0, 122, '2025-09-14'),
(3, 2, 9, 2025, 0, 10, '2025-09-14'),
(4, 1, 9, 2025, 0, 123, '2025-09-13'),
(5, 10, 9, 2025, 0, 432, '2025-09-14'),
(6, 3, 9, 2025, 0, 111, '2025-09-15'),
(7, 3, 9, 2025, 111, 211, '2025-09-16'),
(9, 6, 9, 2025, 0, 10, '2025-09-16'),
(10, 1, 9, 2025, 123, 200, '2025-09-19'),
(11, 2, 9, 2025, 10, 50, '2025-09-26');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) DEFAULT 'completed',
  `payment_date` datetime DEFAULT current_timestamp(),
  `transaction_id` varchar(255) DEFAULT NULL,
  `contract_duration` int(11) DEFAULT NULL,
  `payment_type` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'unknown'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `room_id`, `order_id`, `start_date`, `total_amount`, `payment_status`, `payment_date`, `transaction_id`, `contract_duration`, `payment_type`, `payment_method`) VALUES
(14, 5, 1, NULL, '0000-00-00', 4500000.00, 'completed', '2025-09-19 17:29:07', NULL, NULL, 'deposit', 'unknown'),
(15, 5, 2, NULL, '0000-00-00', 3500000.00, 'completed', '2025-09-26 23:10:17', NULL, NULL, 'deposit', 'unknown'),
(16, 5, 8, NULL, '0000-00-00', 16150000.00, 'completed', '2025-09-30 19:52:10', NULL, NULL, 'deposit', 'unknown');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'available',
  `rent_price` decimal(10,2) DEFAULT NULL,
  `num_people` int(11) DEFAULT NULL,
  `contract_duration` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `description`, `status`, `rent_price`, `num_people`, `contract_duration`, `start_date`) VALUES
(1, '101', 'Căn hộ Thủy Tiên, diện tích 25m²\r\nCửa sổ hướng sân', 'occupied', 5500000.00, 2, NULL, NULL),
(2, '102', 'Căn hộ Mộc Lan, diện tích 40m²\r\nCửa sổ hướng sân', 'occupied', 8000000.00, 1, NULL, NULL),
(3, '103', 'Căn hộ Thủy Tiên, diện tích 25m²\r\nCửa sổ hướng sân vườn', 'available', 6000000.00, NULL, NULL, NULL),
(4, '104', 'Căn hộ Thủy Tiên, diện tích 25m²\r\nCó gác lửng', 'available', 6500000.00, NULL, NULL, NULL),
(5, '105', 'Căn hộ Thủy Tiên, diện tích 25m², cửa sổ hướng sân', 'repairing', 5700000.00, NULL, NULL, NULL),
(6, '201', 'Căn hộ Thủy Tiên, diện tích 25m²\r\nhướng ra đường', 'available', 5800000.00, NULL, NULL, NULL),
(7, '202', 'Căn hộ Mộc Lan, diện tích 40m² \r\nPhòng đơn, tầng 3, hướng vào sân', 'available', 8400000.00, NULL, NULL, NULL),
(8, '203', 'Căn hộ Mộc Lan, diện tích 40m²\r\nPhòng có bếp lớn, cửa sổ hướng sân', 'occupied', 8500000.00, NULL, NULL, NULL),
(9, '204', 'Căn hộ Mộc Lan, diện tích 40m², Phòng đơn, có gác lửng', 'available', 8700000.00, NULL, NULL, NULL),
(10, '301', 'Căn hộ Hồng Nhung, diện tích 60m², tầng thượng, có ban công lớn', 'available', 12000000.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'guest'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `email`, `password`, `role`) VALUES
(1, 'admin_user', NULL, NULL, '$2y$10$tJ9fUj2/kPj.o9fV6x3fO.2HlqZ.2XoGfO.w.Ea3D3/V8zT0P4.N.J', 'admin'),
(4, 'admin', NULL, NULL, '$2y$10$3C6EZJWQkmA9XAamZFIIxO8tyQW0L7a21Sedume4TAGN6eyX1N4ui', 'admin'),
(5, 'customer1', 'Trần Thị B', 'thienan21082012@gmail.com', '$2y$10$dnfz/pDhXGVT70Fs43bXZefJu4gZqRbYe7eF52Oo4PjrP7ah2HZMO', 'guest'),
(6, 'user1', 'Nguyễn Văn A', 'antran.89244020162@st.ueh.edu.vn', '$2y$10$B0A2RR3nLIoPjPCvFR5V4OXShnnLfGdZ8lxgJ4ShXNIzgJ8gahfyG', 'guest'),
(7, 'admin1', NULL, NULL, '$2y$10$5wQYPnEXtFHiLuX8cDFq2eDt5ye.9EhYaB6JEqU90a2KsrOPOyZFC', 'admin'),
(8, 'customer2', 'Trần Thiên Ân', 'thienan21082025@gmail.com', '$2y$10$BePTRxsl7SaZAHlNfI4oueT1sg2SHtRFiNPxPkdMaL/422uHJaT66', 'guest');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `electricity_readings`
--
ALTER TABLE `electricity_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

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
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `electricity_readings`
--
ALTER TABLE `electricity_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bills_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carts_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `electricity_readings`
--
ALTER TABLE `electricity_readings`
  ADD CONSTRAINT `electricity_readings_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
