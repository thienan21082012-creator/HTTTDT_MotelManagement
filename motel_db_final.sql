-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2025 at 10:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `user_id`, `room_id`, `billing_month`, `billing_year`, `rent_amount`, `electricity_amount`, `water_amount`, `service_fee`, `total_amount`, `status`, `created_at`) VALUES
(3, 5, 2, 9, 2025, 2000000.00, 35000.00, 100000.00, 100000.00, 2235000.00, 'unpaid', '2025-09-16 14:55:37'),
(4, 6, 3, 9, 2025, 3500000.00, 388500.00, 100000.00, 100000.00, 4088500.00, 'paid', '2025-09-16 14:55:37'),
(5, 5, 10, 9, 2025, 3000000.00, 1512000.00, 100000.00, 100000.00, 4712000.00, 'unpaid', '2025-09-16 14:55:37');

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
(8, 11, 9, 2025, 0, 111, '2025-09-16');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) DEFAULT 'completed',
  `payment_date` datetime DEFAULT current_timestamp(),
  `contract_duration` int(11) DEFAULT NULL,
  `payment_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `room_id`, `start_date`, `total_amount`, `payment_status`, `payment_date`, `contract_duration`, `payment_type`) VALUES
(1, 4, 1, '2025-09-14', 2500000.00, 'completed', '2025-09-13 13:33:42', 3, NULL),
(2, 5, 4, '2025-09-11', 2200000.00, 'completed', '2025-09-13 13:47:43', 6, NULL),
(3, 5, 5, '2025-09-04', 3200000.00, 'completed', '2025-09-14 04:37:18', 3, NULL),
(4, 5, 10, '2025-09-01', 3000000.00, 'completed', '2025-09-14 10:05:56', 6, 'deposit'),
(5, 5, 2, '2025-09-10', 2000000.00, 'completed', '2025-09-14 10:23:46', 12, 'deposit'),
(11, 6, 3, '2025-09-16', 3150000.00, 'completed', '2025-09-16 14:10:11', 6, 'deposit'),
(12, 6, 3, '0000-00-00', 3150000.00, 'completed', '2025-09-16 14:15:44', NULL, 'deposit');

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
  `contract_duration` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `description`, `status`, `rent_price`, `contract_duration`, `start_date`) VALUES
(1, '101', 'Phòng đơn, có ban công', 'occupied', 2500000.00, NULL, NULL),
(2, '102', 'Phòng đơn, không có ban công', 'occupied', 2000000.00, NULL, NULL),
(3, '103', 'Phòng đôi, 1 giường lớn', 'occupied', 3500000.00, 6, '2025-09-16'),
(4, '104', 'Phòng đơn, tầng 2', 'occupied', 2200000.00, NULL, NULL),
(5, '105', 'Phòng đôi, 2 giường nhỏ', 'occupied', 3200000.00, NULL, NULL),
(6, '201', 'Phòng đơn, tầng 3, hướng ra đường', 'available', 2800000.00, NULL, NULL),
(7, '202', 'Phòng đơn, tầng 3, hướng vào sân', 'available', 2400000.00, NULL, NULL),
(8, '203', 'Phòng đôi, có bếp nhỏ', 'available', 4000000.00, NULL, NULL),
(9, '204', 'Phòng đơn, có gác lửng', 'available', 2700000.00, NULL, NULL),
(10, '301', 'Phòng đơn, tầng thượng, có ban công lớn', 'occupied', 3000000.00, NULL, NULL),
(11, '3295', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.', 'occupied', 4000000.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'guest'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin_user', '$2y$10$tJ9fUj2/kPj.o9fV6x3fO.2HlqZ.2XoGfO.w.Ea3D3/V8zT0P4.N.J', 'admin'),
(4, 'admin', '$2y$10$3C6EZJWQkmA9XAamZFIIxO8tyQW0L7a21Sedume4TAGN6eyX1N4ui', 'admin'),
(5, 'customer1', '$2y$10$dnfz/pDhXGVT70Fs43bXZefJu4gZqRbYe7eF52Oo4PjrP7ah2HZMO', 'guest'),
(6, 'user1', '$2y$10$B0A2RR3nLIoPjPCvFR5V4OXShnnLfGdZ8lxgJ4ShXNIzgJ8gahfyG', 'guest'),
(7, 'admin1', '$2y$10$5wQYPnEXtFHiLuX8cDFq2eDt5ye.9EhYaB6JEqU90a2KsrOPOyZFC', 'admin');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `electricity_readings`
--
ALTER TABLE `electricity_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
