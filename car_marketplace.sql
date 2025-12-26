-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 26, 2025 at 01:09 AM
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
-- Database: `car_marketplace`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `brand_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `brand_name`) VALUES
(2, 'BMW'),
(4, 'Hyundai'),
(6, 'Iran Khodro'),
(7, 'Kia'),
(3, 'Mercedes-Benz'),
(5, 'Peugeot'),
(1, 'Toyota');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year_produced` int(11) NOT NULL,
  `mileage` int(11) DEFAULT 0,
  `price` bigint(20) NOT NULL,
  `color` varchar(30) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `status` enum('available','sold','pending') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `user_id`, `brand_id`, `model`, `year_produced`, `mileage`, `price`, `color`, `description`, `city`, `status`, `created_at`) VALUES
(1, 2, 1, 'Corolla', 2021, 15000, 28000, 'White', 'Like new, low mileage, lady driven.', 'Tehran', 'available', '2025-12-25 19:40:55'),
(2, 2, 2, 'X5', 2019, 55000, 62000, 'Black', 'Fully loaded, panoramic roof, new tires.', 'Shiraz', 'available', '2025-12-25 19:40:55'),
(4, 3, 6, 'Tara', 0, 0, 18000, 'Black', '', '', 'sold', '2025-12-25 19:40:55'),
(5, 2, 7, 'Sportage', 2016, 110000, 29000, 'Red', 'Regularly serviced at dealership.', 'Mashhad', 'available', '2025-12-25 19:40:55'),
(6, 3, 5, '207i', 2022, 18000, 14000, 'White', 'Automatic transmission, cruise control.', 'Tabriz', 'available', '2025-12-25 19:40:55'),
(7, 3, 6, 'soren', 2019, 0, 12000, 'Pearl White', 'factory new', 'shahrekord', 'available', '2025-12-25 23:34:31');

-- --------------------------------------------------------

--
-- Table structure for table `car_images`
--

CREATE TABLE `car_images` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `car_images`
--

INSERT INTO `car_images` (`id`, `car_id`, `image_url`, `is_main`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?q=80&w=800', 1),
(2, 2, 'https://images.unsplash.com/photo-1555215695-3004980ad54e?q=80&w=800', 1),
(4, 4, 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=800', 1),
(5, 5, 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?q=80&w=800', 1),
(6, 6, 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?q=80&w=800', 1),
(12, 7, 'uploads/1766705671_0.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `colors`
--

CREATE TABLE `colors` (
  `id` int(11) NOT NULL,
  `color_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `colors`
--

INSERT INTO `colors` (`id`, `color_name`) VALUES
(2, 'Black'),
(11, 'British Racing Green'),
(10, 'Champagne Gold'),
(12, 'Charcoal Gray'),
(6, 'Crimson Red'),
(9, 'Deep Navy'),
(4, 'Gray'),
(8, 'Matte Black'),
(5, 'Metallic Blue'),
(14, 'Orange Flare'),
(7, 'Pearl White'),
(3, 'Silver'),
(13, 'Sky Blue'),
(1, 'White'),
(15, 'Yellow Sport');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `phone_number`, `password`, `role`, `created_at`) VALUES
(1, 'System Admin', 'admin', '09120000000', '$2y$10$4hEa2esUABVMFSJ2F1VVruiYhvMehsg0dSSifvlGfl28ObTc2nWHm', 'admin', '2025-12-25 19:40:55'),
(2, 'Ali Rad', 'alirad', '09351111111', '$2y$10$4hEa2esUABVMFSJ2F1VVruiYhvMehsg0dSSifvlGfl28ObTc2nWHm', 'user', '2025-12-25 19:40:55'),
(3, 'Sara Karimi', 'sara_k', '09192222222', '$2y$10$4hEa2esUABVMFSJ2F1VVruiYhvMehsg0dSSifvlGfl28ObTc2nWHm', 'user', '2025-12-25 19:40:55'),
(4, 'the dingool', 'ahmad', '09369166877', '$2y$10$4hEa2esUABVMFSJ2F1VVruiYhvMehsg0dSSifvlGfl28ObTc2nWHm', 'user', '2025-12-25 19:42:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brand_name` (`brand_name`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `car_images`
--
ALTER TABLE `car_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `color_name` (`color_name`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`car_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `car_images`
--
ALTER TABLE `car_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `colors`
--
ALTER TABLE `colors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cars_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`);

--
-- Constraints for table `car_images`
--
ALTER TABLE `car_images`
  ADD CONSTRAINT `car_images_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
