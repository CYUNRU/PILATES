-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2026-06-19 12:08:19
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `pilates_studio`
--

-- --------------------------------------------------------

--
-- 資料表結構 `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `product_id`, `quantity`, `added_at`) VALUES
(3, 2, 1, 1, '2026-06-17 14:27:30'),
(4, 3, 3, 1, '2026-06-17 14:27:30'),
(5, 4, 5, 3, '2026-06-17 14:27:30'),
(6, 5, 7, 1, '2026-06-17 14:27:30'),
(7, 6, 6, 1, '2026-06-17 14:27:30'),
(8, 7, 9, 2, '2026-06-17 14:27:30'),
(9, 8, 10, 1, '2026-06-17 14:27:30'),
(10, 9, 8, 1, '2026-06-17 14:27:30');

-- --------------------------------------------------------

--
-- 資料表結構 `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_type_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `max_capacity` int(11) NOT NULL,
  `course_type` enum('one-on-one','one-on-two','group-class') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `courses`
--

INSERT INTO `courses` (`course_id`, `course_type_id`, `trainer_id`, `course_name`, `description`, `price`, `max_capacity`, `course_type`, `created_at`, `updated_at`) VALUES
(1, 1, 3, '一對一教學 - 專業私教', '0', 1500.00, 1, 'one-on-one', '2026-06-17 15:52:11', '2026-06-18 02:10:41'),
(2, 2, 2, '一對二教學 - 雙人私教', '0', 1000.00, 2, 'one-on-two', '2026-06-17 15:52:11', '2026-06-18 02:10:57'),
(3, 3, 3, '團課 (小班制) - 基礎器械皮拉提斯', '0', 600.00, 5, 'group-class', '2026-06-17 15:52:11', '2026-06-18 02:11:20'),
(4, 3, 4, '團課 (小班制) - 芭蕾塑身入門', '0', 600.00, 5, 'group-class', '2026-06-17 15:52:11', '2026-06-18 02:11:38'),
(5, 3, 5, '團課 (小班制) - 器械禪柔體態雕塑', '0', 600.00, 5, 'group-class', '2026-06-17 15:52:11', '2026-06-18 02:11:12'),
(6, 3, 4, '團課 (小班制) - 墊上核心基礎訓練', '0', 600.00, 5, 'group-class', '2026-06-17 15:52:11', '2026-06-18 02:11:32'),
(7, 1, 3, '器械皮拉提斯-團班', '0', 2000.00, 5, 'one-on-one', '2026-06-18 02:10:13', '2026-06-18 02:10:13');

-- --------------------------------------------------------

--
-- 資料表結構 `course_enrollments`
--

CREATE TABLE `course_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('confirmed','cancelled','completed') DEFAULT 'confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `course_enrollments`
--

INSERT INTO `course_enrollments` (`enrollment_id`, `schedule_id`, `user_id`, `enrollment_date`, `status`) VALUES
(201, 101, 11, '2026-06-18 03:13:25', 'confirmed'),
(202, 101, 12, '2026-06-18 03:13:25', 'confirmed'),
(203, 102, 13, '2026-06-18 03:13:25', 'confirmed'),
(204, 103, 11, '2026-06-18 03:13:25', 'confirmed'),
(205, 105, 12, '2026-06-18 03:13:25', 'confirmed'),
(206, 102, 14, '2026-06-18 03:23:58', 'confirmed'),
(207, 105, 14, '2026-06-18 03:24:05', 'confirmed'),
(208, 101, 14, '2026-06-18 03:24:28', 'confirmed');

-- --------------------------------------------------------

--
-- 資料表結構 `course_schedules`
--

CREATE TABLE `course_schedules` (
  `schedule_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `course_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `current_enrollment` int(11) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `on_duty_trainer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `course_schedules`
--

INSERT INTO `course_schedules` (`schedule_id`, `course_id`, `trainer_id`, `course_date`, `start_time`, `end_time`, `current_enrollment`, `is_available`, `on_duty_trainer_id`, `created_at`) VALUES
(101, 1, 1, '2026-06-22', '09:00:00', '10:00:00', 0, 1, 1, '2026-06-18 03:13:25'),
(102, 2, 2, '2026-06-23', '10:30:00', '11:30:00', 0, 1, NULL, '2026-06-18 03:13:25'),
(103, 3, 3, '2026-06-24', '14:00:00', '15:00:00', 0, 1, 3, '2026-06-18 03:13:25'),
(104, 4, 4, '2026-06-25', '16:00:00', '17:00:00', 0, 1, NULL, '2026-06-18 03:13:25'),
(105, 5, 5, '2026-06-26', '19:00:00', '20:00:00', 0, 1, 5, '2026-06-18 03:13:25'),
(106, 1, 1, '2026-07-15', '09:00:00', '10:00:00', 0, 1, NULL, '2026-06-18 03:21:29'),
(107, 2, 2, '2026-07-16', '14:00:00', '15:00:00', 0, 1, 2, '2026-06-18 03:21:29'),
(108, 3, 3, '2026-07-17', '10:30:00', '11:30:00', 0, 1, NULL, '2026-06-18 03:21:29'),
(109, 4, 4, '2026-07-18', '16:00:00', '17:00:00', 0, 1, 4, '2026-06-18 03:21:29'),
(110, 5, 5, '2026-07-19', '19:00:00', '20:00:00', 0, 1, NULL, '2026-06-18 03:21:29'),
(111, 1, 1, '2026-07-15', '09:00:00', '10:00:00', 0, 1, NULL, '2026-06-18 03:23:46'),
(112, 2, 2, '2026-07-16', '14:00:00', '15:00:00', 0, 1, 2, '2026-06-18 03:23:46'),
(113, 3, 3, '2026-07-17', '10:30:00', '11:30:00', 0, 1, NULL, '2026-06-18 03:23:46'),
(114, 4, 4, '2026-07-18', '16:00:00', '17:00:00', 0, 1, 4, '2026-06-18 03:23:46'),
(115, 5, 5, '2026-07-19', '19:00:00', '20:00:00', 0, 1, NULL, '2026-06-18 03:23:46');

-- --------------------------------------------------------

--
-- 資料表結構 `course_types`
--

CREATE TABLE `course_types` (
  `course_type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `course_types`
--

INSERT INTO `course_types` (`course_type_id`, `type_name`, `description`, `created_at`) VALUES
(1, '一對一教學', '專業教練全程指導', '2026-06-17 15:52:11'),
(2, '一對二教學', '雙人私教課程', '2026-06-17 15:52:11'),
(3, '團課 (小班制)', '最多5人 (滿班關閉)', '2026-06-17 15:52:11');

-- --------------------------------------------------------

--
-- 資料表結構 `installment_details`
--

CREATE TABLE `installment_details` (
  `detail_id` int(11) NOT NULL,
  `installment_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `installment_details`
--

INSERT INTO `installment_details` (`detail_id`, `installment_id`, `month`, `due_date`, `amount`, `paid_date`, `status`, `created_at`) VALUES
(1, 1, 1, '2026-01-01', 1833.33, '2026-01-01', 'paid', '2026-06-17 14:27:30'),
(2, 1, 2, '2026-02-01', 1833.33, '2026-02-01', 'paid', '2026-06-17 14:27:30'),
(3, 1, 3, '2026-03-01', 1833.34, '2026-02-28', 'paid', '2026-06-17 14:27:30'),
(4, 2, 1, '2026-05-15', 2400.00, '2026-05-14', 'paid', '2026-06-17 14:27:30'),
(5, 2, 2, '2026-06-15', 2400.00, NULL, 'pending', '2026-06-17 14:27:30'),
(6, 2, 3, '2026-07-15', 2400.00, NULL, 'pending', '2026-06-17 14:27:30'),
(7, 3, 1, '2026-06-20', 3000.00, NULL, 'pending', '2026-06-17 14:27:30'),
(8, 3, 2, '2026-07-20', 3000.00, NULL, 'pending', '2026-06-17 14:27:30'),
(9, 3, 3, '2026-08-20', 3000.00, NULL, 'pending', '2026-06-17 14:27:30'),
(10, 3, 4, '2026-09-20', 3000.00, NULL, 'pending', '2026-06-17 14:27:30');

-- --------------------------------------------------------

--
-- 資料表結構 `installment_plans`
--

CREATE TABLE `installment_plans` (
  `installment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `total_months` int(11) NOT NULL,
  `monthly_amount` decimal(10,2) NOT NULL,
  `paid_months` int(11) DEFAULT 0,
  `status` enum('active','completed','defaulted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `installment_plans`
--

INSERT INTO `installment_plans` (`installment_id`, `order_id`, `total_months`, `monthly_amount`, `paid_months`, `status`, `created_at`) VALUES
(1, 3, 12, 1833.33, 12, 'completed', '2026-06-17 14:27:30'),
(2, 6, 3, 2400.00, 1, 'active', '2026-06-17 14:27:30'),
(3, 9, 6, 3000.00, 0, 'active', '2026-06-17 14:27:30'),
(4, 1, 3, 2933.33, 3, 'completed', '2026-06-17 14:27:30'),
(5, 2, 6, 750.00, 0, 'active', '2026-06-17 14:27:30'),
(6, 4, 2, 975.00, 2, 'completed', '2026-06-17 14:27:30'),
(7, 5, 3, 150.00, 3, 'completed', '2026-06-17 14:27:30'),
(8, 7, 6, 296.66, 0, 'active', '2026-06-17 14:27:30'),
(9, 8, 12, 800.00, 12, 'completed', '2026-06-17 14:27:30'),
(10, 10, 3, 800.00, 3, 'completed', '2026-06-17 14:27:30');

-- --------------------------------------------------------

--
-- 資料表結構 `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `order_type` enum('product','course','mixed') NOT NULL,
  `payment_method` enum('credit-card','line-pay','apple-pay','installment') NOT NULL,
  `payment_status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `installment_months` int(11) DEFAULT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `total_amount`, `order_type`, `payment_method`, `payment_status`, `installment_months`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-06-17 14:27:30', 8800.00, 'course', '', 'completed', NULL, 'completed', '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(2, 2, '2026-06-17 14:27:30', 4500.00, 'course', '', 'pending', NULL, 'pending', '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(3, 3, '2026-06-17 14:27:30', 22000.00, 'course', 'installment', 'completed', 12, 'completed', '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(4, 4, '2026-06-17 14:27:30', 1950.00, 'product', '', 'completed', NULL, 'completed', '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(5, 5, '2026-06-17 14:27:30', 450.00, 'product', '', 'completed', NULL, 'completed', '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(6, 6, '2026-06-17 14:27:30', 7200.00, 'course', 'installment', 'pending', 3, 'pending', '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(7, 7, '2026-06-17 14:27:30', 1780.00, 'product', '', 'failed', NULL, 'cancelled', '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(8, 8, '2026-06-17 14:27:30', 9600.00, 'course', '', 'completed', NULL, 'completed', '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(9, 9, '2026-06-17 14:27:30', 18000.00, 'course', 'installment', 'pending', 6, 'pending', '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(10, 10, '2026-06-17 14:27:30', 2400.00, 'product', '', 'completed', NULL, 'completed', '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(11, 1, '2026-06-17 16:55:10', 11200.00, 'product', 'credit-card', 'completed', NULL, 'completed', '2026-06-17 16:55:10', '2026-06-17 16:55:13'),
(12, 14, '2026-06-18 01:58:17', 29200.00, 'product', 'apple-pay', 'completed', NULL, 'completed', '2026-06-18 01:58:17', '2026-06-18 01:58:19'),
(13, 14, '2026-06-18 02:35:00', 3440.00, 'product', 'line-pay', 'completed', NULL, 'completed', '2026-06-18 02:35:00', '2026-06-18 02:35:02');

-- --------------------------------------------------------

--
-- 資料表結構 `order_courses`
--

CREATE TABLE `order_courses` (
  `order_course_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(1, 1, 2, 1, 8800.00, '2026-06-17 14:27:30'),
(2, 2, 1, 1, 4500.00, '2026-06-17 14:27:30'),
(3, 3, 3, 1, 22000.00, '2026-06-17 14:27:30'),
(4, 4, 5, 3, 650.00, '2026-06-17 14:27:30'),
(5, 5, 7, 1, 450.00, '2026-06-17 14:27:30'),
(6, 6, 6, 1, 7200.00, '2026-06-17 14:27:30'),
(7, 7, 9, 2, 890.00, '2026-06-17 14:27:30'),
(8, 8, 10, 1, 9600.00, '2026-06-17 14:27:30'),
(9, 9, 8, 1, 18000.00, '2026-06-17 14:27:30'),
(10, 10, 4, 2, 1200.00, '2026-06-17 14:27:30'),
(11, 11, 2, 1, 8800.00, '2026-06-17 16:55:10'),
(12, 11, 4, 2, 1200.00, '2026-06-17 16:55:10'),
(13, 12, 6, 1, 7200.00, '2026-06-18 01:58:17'),
(14, 12, 3, 1, 22000.00, '2026-06-18 01:58:17'),
(15, 13, 7, 3, 450.00, '2026-06-18 02:35:00'),
(16, 13, 4, 1, 1200.00, '2026-06-18 02:35:00'),
(17, 13, 9, 1, 890.00, '2026-06-18 02:35:00');

-- --------------------------------------------------------

--
-- 資料表結構 `payment_records`
--

CREATE TABLE `payment_records` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('credit-card','line-pay','apple-pay','installment') NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('success','failed','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `payment_records`
--

INSERT INTO `payment_records` (`payment_id`, `order_id`, `payment_date`, `amount`, `payment_method`, `transaction_id`, `status`, `created_at`) VALUES
(1, 1, '2026-06-17 14:27:30', 8800.00, '', NULL, 'success', '2026-06-17 14:27:30'),
(2, 3, '2026-06-17 14:27:30', 1833.33, '', NULL, 'success', '2026-06-17 14:27:30'),
(3, 3, '2026-06-17 14:27:30', 1833.33, '', NULL, 'success', '2026-06-17 14:27:30'),
(4, 4, '2026-06-17 14:27:30', 1950.00, '', NULL, 'success', '2026-06-17 14:27:30'),
(5, 5, '2026-06-17 14:27:30', 450.00, '', NULL, 'success', '2026-06-17 14:27:30'),
(6, 6, '2026-06-17 14:27:30', 2400.00, '', NULL, 'success', '2026-06-17 14:27:30'),
(7, 8, '2026-06-17 14:27:30', 9600.00, '', NULL, 'success', '2026-06-17 14:27:30'),
(8, 10, '2026-06-17 14:27:30', 2400.00, '', NULL, 'success', '2026-06-17 14:27:30'),
(9, 2, '2026-06-17 14:27:30', 4500.00, '', NULL, 'pending', '2026-06-17 14:27:30'),
(10, 7, '2026-06-17 14:27:30', 1780.00, '', NULL, 'failed', '2026-06-17 14:27:30'),
(11, 11, '2026-06-17 16:55:10', 11200.00, 'credit-card', NULL, 'success', '2026-06-17 16:55:10'),
(12, 12, '2026-06-18 01:58:17', 29200.00, 'apple-pay', NULL, 'success', '2026-06-18 01:58:17'),
(13, 13, '2026-06-18 02:35:00', 3440.00, 'line-pay', NULL, 'success', '2026-06-18 02:35:00');

-- --------------------------------------------------------

--
-- 資料表結構 `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `description`, `price`, `stock`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 0, '基礎墊上皮拉提斯 - 10堂體驗課', NULL, 4500.00, 50, NULL, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(2, 0, '核心覺醒：皮拉提斯夏令營單人票', NULL, 8800.00, 19, NULL, '2026-06-17 14:27:30', '2026-06-17 16:55:10'),
(3, 0, '專業器械 Reformer 一對一私人課', NULL, 22000.00, 14, NULL, '2026-06-17 14:27:30', '2026-06-18 01:58:17'),
(4, 0, '療癒防滑皮拉提斯專業墊 (粉色)', NULL, 1200.00, 97, NULL, '2026-06-17 14:27:30', '2026-06-18 02:35:00'),
(5, 0, '核心阻力訓練環 (魔力圈)', NULL, 650.00, 80, NULL, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(6, 0, '孕婦產前核心舒緩專班 - 6堂', NULL, 7200.00, 24, NULL, '2026-06-17 14:27:30', '2026-06-18 01:58:17'),
(7, 0, '皮拉提斯五趾防滑襪 (2雙入)', NULL, 450.00, 197, NULL, '2026-06-17 14:27:30', '2026-06-18 02:35:00'),
(8, 0, '脊椎側彎體態調整專班 - 12堂', NULL, 18000.00, 10, NULL, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(9, 0, '高密度核心平衡滾棒 (Foam Roller)', NULL, 890.00, 59, NULL, '2026-06-17 14:27:30', '2026-06-18 02:35:00'),
(10, 0, '進階穩定椅 Wunda Chair 團體課', NULL, 9600.00, 30, NULL, '2026-06-17 14:27:30', '2026-06-17 14:27:30');

-- --------------------------------------------------------

--
-- 資料表結構 `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `shopping_carts`
--

CREATE TABLE `shopping_carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `shopping_carts`
--

INSERT INTO `shopping_carts` (`cart_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(2, 2, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(3, 3, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(4, 4, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(5, 5, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(6, 6, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(7, 7, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(8, 8, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(9, 9, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(10, 10, '2026-06-17 14:27:30', '2026-06-17 14:27:30'),
(11, 14, '2026-06-18 01:57:58', '2026-06-18 01:57:58');

-- --------------------------------------------------------

--
-- 資料表結構 `trainers`
--

CREATE TABLE `trainers` (
  `trainer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `introduction` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `pending_introduction` text DEFAULT NULL,
  `updated_by_trainer_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_admin_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `trainers`
--

INSERT INTO `trainers` (`trainer_id`, `user_id`, `introduction`, `experience_years`, `specialization`, `photo_url`, `bio`, `approval_status`, `pending_introduction`, `updated_by_trainer_at`, `reviewed_by_admin_at`, `reviewed_by_admin_id`, `created_at`, `updated_at`) VALUES
(1, 1, '專精於器械核心雕塑與骨盆底肌群強化', 5, '器械皮拉提斯', NULL, '資深皮拉提斯指導員', 'approved', NULL, '2026-06-17 18:12:11', '2026-06-18 02:29:10', 10, '2026-06-17 14:54:13', '2026-06-18 02:29:10'),
(2, 2, '結合芭蕾律動與現代體態雕塑，打造優雅線條', 8, '芭蕾塑身', NULL, '前芭蕾舞團專職舞者', 'approved', NULL, NULL, NULL, NULL, '2026-06-17 14:54:13', '2026-06-17 14:54:13'),
(3, 3, '透過流暢的螺旋動作，延展脊椎並釋放壓力', 6, '器械禪柔', NULL, '禪柔認證指導老師', 'approved', NULL, NULL, NULL, NULL, '2026-06-17 14:54:13', '2026-06-17 14:54:13'),
(4, 4, '全方位體態調整，擅長一對一客製化訓練', 4, '體態調整', NULL, '專業私人教練', 'approved', NULL, NULL, NULL, NULL, '2026-06-17 14:54:13', '2026-06-17 14:54:13'),
(5, 5, '專注產後核心修復與孕婦產前舒緩訓練', 7, '孕婦核心修復', NULL, '婦產專科合作物理指導員', 'approved', NULL, NULL, NULL, NULL, '2026-06-17 14:54:13', '2026-06-17 14:54:13');

-- --------------------------------------------------------

--
-- 資料表結構 `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer','trainer') DEFAULT 'customer',
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_subscribed` tinyint(1) DEFAULT 1 COMMENT '是否訂閱每月課表推播：1=願意, 0=不願意'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `full_name`, `phone`, `created_at`, `updated_at`, `is_subscribed`) VALUES
(1, 'trainer_stott', 'meiling.lin@pilates.com', '123456', 'trainer', '林美玲', '0912-345678', '2026-06-17 17:26:29', '2026-06-17 17:27:19', 1),
(2, 'trainer_ballet', 'yating.chen@pilates.com', '123456', 'trainer', '陳雅婷', '0923-456789', '2026-06-17 17:26:29', '2026-06-17 17:27:28', 1),
(3, 'trainer_gyro', 'yuxuan.zhang@pilates.com', '123456', 'trainer', '張宇軒', '0934-567890', '2026-06-17 17:26:29', '2026-06-17 17:27:41', 1),
(4, 'trainer_core', 'bohan.huang@pilates.com', '123456', 'trainer', '黃柏翰', '0945-678901', '2026-06-17 17:26:29', '2026-06-17 17:28:32', 1),
(5, 'trainer_preg', 'shihan.liu@pilates.com', '123456', 'trainer', '劉詩涵', '0956-789012', '2026-06-17 17:26:29', '2026-06-17 17:28:32', 1),
(10, 'admin', 'admin@pilates.com', '000000', 'admin', '系統管理員', '0900-000000', '2026-06-17 17:26:29', '2026-06-17 17:28:32', 1),
(14, 'a111', 'ccc@gmail.com', '$2y$10$W1o25wogmammDIgD6NvozukdAkjTE/RpVF5Tor8LJhk5MTNVL8ElC', 'customer', '王小明', '0955142283', '2026-06-18 01:57:03', '2026-06-18 07:51:56', 1);

-- --------------------------------------------------------

--
-- 資料表結構 `user_health_logs`
--

CREATE TABLE `user_health_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `issue_tag` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_cart_items_cart` (`cart_id`);

--
-- 資料表索引 `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `idx_course_type` (`course_type_id`),
  ADD KEY `idx_course_trainer` (`trainer_id`);

--
-- 資料表索引 `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`schedule_id`,`user_id`),
  ADD KEY `idx_enrollment_user` (`user_id`),
  ADD KEY `idx_enrollment_schedule` (`schedule_id`);

--
-- 資料表索引 `course_schedules`
--
ALTER TABLE `course_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `on_duty_trainer_id` (`on_duty_trainer_id`),
  ADD KEY `idx_schedule_date` (`course_date`);

--
-- 資料表索引 `course_types`
--
ALTER TABLE `course_types`
  ADD PRIMARY KEY (`course_type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- 資料表索引 `installment_details`
--
ALTER TABLE `installment_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `installment_id` (`installment_id`);

--
-- 資料表索引 `installment_plans`
--
ALTER TABLE `installment_plans`
  ADD PRIMARY KEY (`installment_id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- 資料表索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_order_user` (`user_id`),
  ADD KEY `idx_order_date` (`order_date`);

--
-- 資料表索引 `order_courses`
--
ALTER TABLE `order_courses`
  ADD PRIMARY KEY (`order_course_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- 資料表索引 `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- 資料表索引 `payment_records`
--
ALTER TABLE `payment_records`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- 資料表索引 `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_product_category` (`category_id`);

--
-- 資料表索引 `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- 資料表索引 `shopping_carts`
--
ALTER TABLE `shopping_carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_cart_user` (`user_id`);

--
-- 資料表索引 `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`trainer_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by_admin_id` (`reviewed_by_admin_id`);

--
-- 資料表索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_role` (`role`);

--
-- 資料表索引 `user_health_logs`
--
ALTER TABLE `user_health_logs`
  ADD PRIMARY KEY (`id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `course_enrollments`
--
ALTER TABLE `course_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=209;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `course_schedules`
--
ALTER TABLE `course_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `course_types`
--
ALTER TABLE `course_types`
  MODIFY `course_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `installment_details`
--
ALTER TABLE `installment_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `installment_plans`
--
ALTER TABLE `installment_plans`
  MODIFY `installment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `order_courses`
--
ALTER TABLE `order_courses`
  MODIFY `order_course_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `payment_records`
--
ALTER TABLE `payment_records`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `shopping_carts`
--
ALTER TABLE `shopping_carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `trainers`
--
ALTER TABLE `trainers`
  MODIFY `trainer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_health_logs`
--
ALTER TABLE `user_health_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `shopping_carts` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- 資料表的限制式 `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`course_type_id`) REFERENCES `course_types` (`course_type_id`),
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`);

--
-- 資料表的限制式 `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD CONSTRAINT `course_enrollments_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `course_schedules` (`schedule_id`),
  ADD CONSTRAINT `course_enrollments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- 資料表的限制式 `course_schedules`
--
ALTER TABLE `course_schedules`
  ADD CONSTRAINT `course_schedules_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `course_schedules_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`),
  ADD CONSTRAINT `course_schedules_ibfk_3` FOREIGN KEY (`on_duty_trainer_id`) REFERENCES `trainers` (`trainer_id`);

--
-- 資料表的限制式 `installment_details`
--
ALTER TABLE `installment_details`
  ADD CONSTRAINT `installment_details_ibfk_1` FOREIGN KEY (`installment_id`) REFERENCES `installment_plans` (`installment_id`);

--
-- 資料表的限制式 `installment_plans`
--
ALTER TABLE `installment_plans`
  ADD CONSTRAINT `installment_plans_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- 資料表的限制式 `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- 資料表的限制式 `order_courses`
--
ALTER TABLE `order_courses`
  ADD CONSTRAINT `order_courses_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_courses_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `course_schedules` (`schedule_id`);

--
-- 資料表的限制式 `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- 資料表的限制式 `payment_records`
--
ALTER TABLE `payment_records`
  ADD CONSTRAINT `payment_records_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- 資料表的限制式 `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`);

--
-- 資料表的限制式 `shopping_carts`
--
ALTER TABLE `shopping_carts`
  ADD CONSTRAINT `shopping_carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `trainers`
--
ALTER TABLE `trainers`
  ADD CONSTRAINT `trainers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trainers_ibfk_2` FOREIGN KEY (`reviewed_by_admin_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
