-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2025 at 05:23 PM
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
-- Database: `bus_booking_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `seat_numbers` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
  `status` enum('Confirmed','Cancelled','Completed') DEFAULT 'Confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `schedule_id`, `booking_date`, `seat_numbers`, `total_amount`, `payment_status`, `status`) VALUES
(1, 2, 1, '2025-04-20 13:37:03', '17,18,19,20,21,22,23,24', 6800.00, 'Paid', 'Confirmed'),
(2, 2, 1, '2025-04-20 13:37:20', '29', 850.00, 'Paid', 'Confirmed'),
(3, 2, 1, '2025-04-20 14:26:26', '3,4', 1700.00, 'Paid', 'Confirmed'),
(4, 3, 1, '2025-04-20 15:56:50', '11,15', 1700.00, 'Paid', 'Confirmed'),
(5, 2, 1, '2025-04-21 03:26:21', '10', 850.00, 'Paid', 'Confirmed'),
(6, 4, 1, '2025-04-21 09:51:06', '32,33', 1700.00, 'Paid', 'Confirmed'),
(7, 6, 2, '2025-04-21 10:23:30', '98', 1300.00, 'Refunded', 'Cancelled'),
(8, 4, 1, '2025-05-12 06:10:53', '31', 850.00, 'Pending', 'Confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `bus_id` int(11) NOT NULL,
  `bus_number` varchar(20) NOT NULL,
  `bus_name` varchar(100) NOT NULL,
  `total_seats` int(11) NOT NULL,
  `bus_type` enum('Standard','Deluxe','Luxury','Sleeper') NOT NULL,
  `amenities` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`bus_id`, `bus_number`, `bus_name`, `total_seats`, `bus_type`, `amenities`, `is_active`) VALUES
(1, 'TN-01-AB-1234', 'Express Deluxe', 40, 'Deluxe', 'AC, Reclining Seats, Charging Points, WiFi', 1),
(2, 'TN-02-CD-5678', 'Luxury Liner', 30, 'Luxury', 'AC, Reclining Seats, Charging Points, WiFi, Blanket, Snacks', 1),
(3, 'TN-03-EF-9012', 'Sleeper Coach', 20, 'Sleeper', 'AC, Sleeping Berths, Charging Points, WiFi', 1),
(4, 'TN-47-AA-2626', 'Vinayaga', 80, 'Standard', 'Reclining Seats,Charging Points', 1),
(5, 'TN-47-BB-3786', 'Volvo', 40, 'Luxury', 'AC, Reclining Seats, Charging Points,wifi', 1),
(11, 'TN-41-AA-5419', 'sarkovia\r\n', 99, 'Sleeper', 'AC, Reclining Seats, Charging Points', 1),
(12, 'TN-47-AA-2625', 'travelyouth', 68, 'Luxury', 'AC, Reclining Seats, Charging Points', 1),
(13, 'TN-47-AD-6699', 'SDK', 50, 'Luxury', 'AC, Reclining Seats, Charging Points', 1);

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

CREATE TABLE `emergency_contacts` (
  `contact_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `contact_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `contact_type` enum('Police','Hospital','Bus Operator','Roadside Assistance') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergency_contacts`
--

INSERT INTO `emergency_contacts` (`contact_id`, `route_id`, `contact_name`, `contact_number`, `contact_type`) VALUES
(1, 1, 'Chennai Highway Police', '100', 'Police'),
(2, 1, 'Bangalore Central Hospital', '108', 'Hospital'),
(3, 2, 'Hyderabad Traffic Control', '103', 'Police'),
(4, 3, 'Karnataka Roadside Assistance', '104', 'Roadside Assistance');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Success','Failed','Pending') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `payment_method`, `transaction_id`, `payment_date`, `status`) VALUES
(1, 7, -1300.00, 'Refund', NULL, '2025-05-05 05:45:31', 'Success');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `route_id` int(11) NOT NULL,
  `departure_city` varchar(100) NOT NULL,
  `arrival_city` varchar(100) NOT NULL,
  `distance` decimal(10,2) NOT NULL,
  `estimated_duration` varchar(50) NOT NULL,
  `base_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`route_id`, `departure_city`, `arrival_city`, `distance`, `estimated_duration`, `base_price`) VALUES
(1, 'Chennai', 'Bangalore', 350.00, '6 hours', 800.00),
(2, 'Chennai', 'Hyderabad', 650.00, '12 hours', 1200.00),
(3, 'Bangalore', 'Hyderabad', 550.00, '10 hours', 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `available_seats` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('Scheduled','Departed','Arrived','Cancelled') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `bus_id`, `route_id`, `departure_time`, `arrival_time`, `available_seats`, `price`, `status`) VALUES
(1, 1, 1, '2025-06-15 08:00:00', '2025-06-15 14:00:00', 23, 850.00, 'Scheduled'),
(2, 11, 2, '2025-05-15 20:00:00', '2025-05-16 08:00:00', 30, 1300.00, 'Scheduled'),
(3, 3, 3, '2023-06-16 10:00:00', '2023-06-16 20:00:00', 20, 1100.00, 'Scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `phone`, `address`, `created_at`, `is_admin`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@busbookingsystem.com', 'Admin User', '1234567890', NULL, '2025-04-16 18:39:16', 1),
(2, 'Hariganesh', '$2y$10$nBljwmzy53YpON4SYO4nV.0Q6ZQGgFBnhSQMEa.h8ENlo3cjAt/8O', 'hganesh465@gmail.com', 'hariganesh', '7904216658', NULL, '2025-04-17 01:40:26', 0),
(3, 'Hari', '$2y$10$ioXmFs429xoDKFFuXP5d8uGBjOFAuqEMwql62DOGLC.8.lVNOxJle', '927623bit038@mkce.ac.in', 'Hariprakash', '7904216658', NULL, '2025-04-20 15:55:53', 0),
(4, 'Faizul', '$2y$10$yTdcFLqxDxFPfiF5TfjIfO8LCz9dhRvvgCY1FqsxCNYvYSpsskz7i', 'faizul@gmail.com', 'Faizul Ahmed', '96963639', NULL, '2025-04-21 09:49:02', 0),
(6, 'abcdefg', '$2y$10$640KZcNxHAnPYM9hF0NoleOQsnCYFjSqEZJLqwXs9cM8f3IQ4czne', 'abcdef@gmail.com', 'abcdefg', '6589865547', NULL, '2025-04-21 10:02:21', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`bus_id`),
  ADD UNIQUE KEY `bus_number` (`bus_number`);

--
-- Indexes for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`contact_id`),
  ADD KEY `route_id` (`route_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`route_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `bus_id` (`bus_id`),
  ADD KEY `route_id` (`route_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `bus_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`);

--
-- Constraints for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `emergency_contacts_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`bus_id`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`bus_id`),
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
