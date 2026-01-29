-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2025 at 05:06 AM
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
-- Database: `empowerher_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `chat_questions`
--

CREATE TABLE `chat_questions` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `content` text DEFAULT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `media_type` enum('image','video') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discussions`
--

CREATE TABLE `discussions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussions`
--

INSERT INTO `discussions` (`id`, `user_id`, `user_name`, `title`, `content`, `created_at`) VALUES
(1, 1, 'Joshua Andres', 'hi', 'gwapo ko', '2025-09-06 14:19:42'),
(2, 1, 'Joshua Andres', 'paano mag palaki', 'sada', '2025-09-06 14:27:14');

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `bug_report` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rating` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `user_id`, `category`, `message`, `bug_report`, `created_at`, `rating`) VALUES
(1, 1, 'General', 'maganda syempre system ko to eh', 1, '2025-09-12 09:37:13', 5),
(2, 1, 'General', 'wala syempre maganda system ko', 1, '2025-09-12 09:37:38', 5),
(3, 1, 'General', 'Ok lang', 1, '2025-09-16 04:18:48', 5);

-- --------------------------------------------------------

--
-- Table structure for table `friend_requests`
--

CREATE TABLE `friend_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `requester_id` int(10) UNSIGNED NOT NULL,
  `requestee_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `topic` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `media` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Others',
  `featured` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `user_name`, `title`, `content`, `topic`, `created_at`, `media`, `category`, `featured`) VALUES
(24, 1, 'Joshua Andres', 'Love ', 'Mainggit kayo mga hangal', 'Relationships & Support', '2025-09-07 02:54:36', 'uploads/posts/1757213676_d6cfeb81-937d-4ed9-bda6-f2c60e488cdd.jpg', 'Others', 0),
(25, 7, 'John Christopher De Guzman', 'Mga pakshet kayo', 'ulol', 'Parenting', '2025-09-07 03:00:14', 'uploads/posts/1757214014_10a54e74-4c7d-49e7-be9b-1cb7b04999f7 (1).jpg', 'Others', 0),
(27, 5, 'Justine Bulagui', 'GROW A GARDEN IS THE BEST!!!!!!!!!!!!!!!!', 'MGA HANGGAL', 'Others', '2025-09-07 06:00:51', 'uploads/posts/1757224851_Screenshot (569).png', 'Others', 0),
(28, 1, 'Joshua Andres', 'inanyo', 'oloolol', 'Education & Learning', '2025-09-07 06:45:25', 'uploads/posts/1757227525_babybath.jpg', 'Others', 0),
(29, 4, 'Cyril Vicente', 'Testing lang naman', 'hi guys testing ng website to ', 'Mental Health & Wellbeing', '2025-09-07 06:54:05', 'uploads/posts/1757228045_Screen Recording 2025-09-07 144723.mp4', 'Others', 0),
(31, 1, 'Joshua Andres', 'kyot', 'hotdog', 'Self-Care & Lifestyle', '2025-09-08 01:42:50', 'uploads/posts/1757295770_d7a757d7-2776-40d3-aca2-065ead73f319.jpg', 'Others', 0),
(32, 4, 'Cyril Vicente', 'Yay', 'hee', 'Relationships & Support', '2025-09-08 01:43:40', 'uploads/posts/1757295820_d6e854d2-cf97-4c38-a69a-4df1ff60d018.jpg', 'Others', 0),
(35, 4, 'Cyril Vicente', 'Billiard Girl', 'Panis nanaman si juswa', 'Career & Work-Life Balance', '2025-09-08 01:45:09', 'uploads/posts/1757295909_44573040-5621-4376-972d-3caf9b3e9856.jpg', 'Others', 0),
(36, 1, 'Joshua Andres', 'Rest In Peace little one', 'bye bye forever grief', 'Others', '2025-09-08 01:46:00', 'uploads/posts/1757295960_701063f2-e070-4dba-9986-f6cb74034ff0.jpg', 'Others', 0),
(37, 8, 'Kairi Irving', 'HELLO MGA PAKSHET', 'PAKERS', 'Others', '2025-09-11 02:13:57', 'uploads/posts/1757556837_Screenshot (350).png', 'Others', 0),
(83, 1, 'Joshua Andres', 'WAWA DAM', 'HEHE', 'Self-Care & Lifestyle', '2025-09-13 02:00:08', 'uploads/posts/1757728808_718372a1-2f37-4d23-b34d-d08e47170af2.jpg', 'Others', 0),
(84, 1, 'Joshua Andres', 'CAZA PEREGRINE', 'SOBRANG LAYO TAENA', 'Relationships & Support', '2025-09-13 02:02:06', 'uploads/posts/1757728926_bdf4d10e-ae9a-477c-adf6-f4f1a23c3bb3.jpg', 'Others', 0),
(85, 1, 'Joshua Andres', 'CHAMPION', 'PANES NANAMAN MGA PAKSHET', 'Career & Work-Life Balance', '2025-09-13 02:03:05', 'uploads/posts/1757728985_3b4bd4d5-0e07-44ea-a98a-58041dffd56b (1).jpg', 'Others', 0),
(88, 1, 'Joshua Andres', 'Fort Santiago', 'hehe', 'Relationships & Support', '2025-09-13 03:57:53', 'uploads/posts/1757735873_hehe.jpg', 'Others', 0),
(90, 1, 'Joshua Andres', 'MLBB 2nd place Fatima Tournament!!', 'Mga hangal', 'Career & Work-Life Balance', '2025-09-14 07:22:08', 'uploads/posts/1757834528_IMG_3457.jpeg', 'Others', 0),
(92, 1, 'Joshua Andres', 'In loving memories of my hammie!', 'Farewell🥺', 'Others', '2025-09-16 05:28:02', 'uploads/posts/1758000482_IMG_3475.jpeg', 'Others', 0),
(93, 1, 'Joshua Andres', 'RIP MY GOLDIE', 'Bye bye🥺', 'Others', '2025-09-16 05:29:45', 'uploads/posts/1758000585_B4DD5B39-F81A-4F95-8534-DA773F806EBB.jpeg', 'Others', 0),
(96, 1, 'Joshua Andres', 'Sad', '', 'Others', '2025-09-16 05:41:51', 'uploads/posts/1758001311_IMG_3349.mov', 'Others', 0);

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `user_id`) VALUES
(2, 12, 1),
(3, 12, 4);

-- --------------------------------------------------------

--
-- Table structure for table `post_reactions`
--

CREATE TABLE `post_reactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED DEFAULT NULL,
  `reply_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reaction_type` enum('like','love','laugh','sad','angry') NOT NULL DEFAULT 'like',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_reactions`
--

INSERT INTO `post_reactions` (`id`, `post_id`, `reply_id`, `user_id`, `reaction_type`, `created_at`) VALUES
(24, 24, NULL, 7, 'love', '2025-09-07 13:57:06'),
(25, 24, NULL, 4, 'love', '2025-09-07 13:57:20'),
(26, 24, NULL, 5, 'love', '2025-09-07 13:59:30'),
(27, 27, NULL, 1, 'laugh', '2025-09-07 14:02:01'),
(28, 24, NULL, 8, 'love', '2025-09-07 14:11:32'),
(29, NULL, 12, 4, 'love', '2025-09-07 14:52:59'),
(30, 29, NULL, 4, 'laugh', '2025-09-07 15:41:35'),
(32, 28, NULL, 4, 'angry', '2025-09-07 16:15:12'),
(35, NULL, 18, 1, 'laugh', '2025-09-08 08:50:10'),
(36, 29, NULL, 1, 'like', '2025-09-08 08:50:13'),
(37, 24, NULL, 1, 'love', '2025-09-08 09:38:11'),
(38, NULL, 17, 1, 'angry', '2025-09-08 09:48:21'),
(40, 35, NULL, 7, 'love', '2025-09-08 09:48:43'),
(41, 32, NULL, 7, 'sad', '2025-09-08 09:48:49'),
(42, 31, NULL, 7, 'laugh', '2025-09-08 09:48:58'),
(44, 35, NULL, 4, 'love', '2025-09-08 09:49:18'),
(46, NULL, 20, 1, 'like', '2025-09-09 12:22:12'),
(59, NULL, 57, 1, 'love', '2025-09-13 09:28:28'),
(65, 36, NULL, 4, 'love', '2025-09-13 09:51:27'),
(67, 36, NULL, 5, 'love', '2025-09-13 09:53:14'),
(68, 36, NULL, 7, 'love', '2025-09-13 09:53:31'),
(69, 36, NULL, 8, 'love', '2025-09-13 09:53:42'),
(73, 85, NULL, 1, 'laugh', '2025-09-13 11:56:30'),
(76, 83, NULL, 1, 'love', '2025-09-13 18:56:32'),
(79, 88, NULL, 9, 'love', '2025-09-15 15:49:12'),
(80, 24, NULL, 9, 'love', '2025-09-15 15:49:51'),
(84, 90, NULL, 1, 'angry', '2025-09-16 13:31:29'),
(85, 93, NULL, 1, 'love', '2025-09-17 20:13:51');

-- --------------------------------------------------------

--
-- Table structure for table `replies`
--

CREATE TABLE `replies` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `reply` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `media` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `replies`
--

INSERT INTO `replies` (`id`, `post_id`, `user_id`, `user_name`, `reply`, `created_at`, `media`, `is_read`) VALUES
(12, 24, 4, 'Cyril Vicente', 'i love you yieeeeeeeeeeeeeeeeeeeeeeeeeeee', '2025-09-07 02:55:24', '', 1),
(14, 25, 4, 'Cyril Vicente', 'ulol ka ina mo', '2025-09-07 03:00:38', '', 0),
(15, 29, 1, 'Joshua Andres', 'hi\r\n', '2025-09-07 06:56:55', '', 0),
(16, 29, 4, 'Cyril Vicente', 'gara talaga hahah', '2025-09-07 07:41:32', '', 0),
(17, 24, 7, 'John Christopher De Guzman', 'landi nyo', '2025-09-07 07:42:20', '', 1),
(18, 24, 7, 'John Christopher De Guzman', '', '2025-09-07 07:42:40', 'uploads/replies/1757230960_Screenshot (2).png', 1),
(19, 28, 4, 'Cyril Vicente', 'salbahe hotdog ka\r\n', '2025-09-07 08:15:07', '', 1),
(20, 36, 4, 'Cyril Vicente', 'sad', '2025-09-08 01:49:13', '', 1),
(21, 35, 1, 'Joshua Andres', 'wow calculator', '2025-09-08 01:49:58', '', 0),
(25, 36, 4, 'Cyril Vicente', 'hello lods\r\n', '2025-09-10 07:08:17', '', 1),
(26, 31, 4, 'Cyril Vicente', 'waw', '2025-09-10 07:35:49', '', 1),
(27, 28, 4, 'Cyril Vicente', 'so amazing\r\n', '2025-09-10 07:36:03', '', 1),
(28, 36, 5, 'Justine Bulagui', 'hi\r\n', '2025-09-12 08:29:45', '', 1),
(32, 36, 5, 'Justine Bulagui', 'try', '2025-09-12 09:43:52', '', 1),
(33, 36, 8, 'Kairi Irving', 'ano yun bat ganun\r\n', '2025-09-12 09:44:55', '', 1),
(57, 36, 1, 'Joshua Andres', 'try', '2025-09-13 00:27:44', NULL, 0),
(159, 93, 1, 'Joshua Andres', 'aw', '2025-09-17 12:14:02', '', 0),
(160, 96, 4, 'Cyril Vicente', 'kawawa\r\n', '2025-09-17 12:14:33', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT 'images/profile.jpg',
  `verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `created_at`, `profile_pic`, `verified`) VALUES
(1, 'Joshua Andres', 'jcandres7212val@student.fatima.edu.ph', '$2y$10$NiYXhIuYkbVAlLjnrJ7mLOl0rxMrm15ijePFb87kwxoXGLMyZYOde', '2025-09-03 13:33:28', 'images/profiles/profile_1.jpg', 0),
(4, 'Cyril Vicente', 'vicente@gmail.com', '$2y$10$Hpv/gvfD3qo7JT1aJNlAL.L5Wa7h43BlaqKFkF.Sn2OEaYxa5.tDe', '2025-09-03 13:51:20', 'uploads/1757227952_0f9f1487-836a-4bd9-b7d2-2ca43ef539ba.jpg', 1),
(5, 'Justine Bulagui', 'justinebulagui@gmail.com', '$2y$10$6GStlb7B76pmhIY6KMVi5u0cTPXi7etMvvwgiAFldUg60XHy6IKEi', '2025-09-03 13:52:01', 'images/profile.jpg', 1),
(7, 'John Christopher De Guzman', 'christopher@gmail.com', '$2y$10$j8K1rzrA5XDqlygvL1dAQedmkV1Zk4idVqVuv4t/Wa3ikPSfj2rDG', '2025-09-03 13:56:09', 'images/profile.jpg', 1),
(8, 'Kairi Irving', 'irving@gmail.com', '$2y$10$57WfNmrI6xFTYk4d9iEV5.LTb/0D8GPMsJ9TVL9DaGaY878RMf1Dq', '2025-09-07 06:11:00', 'images/profile.jpg', 1),
(9, 'Cyril Vicente', 'vcyril095@gmail.com', '$2y$10$9Y0sr/ct50ocSFthHtxu4uWn/ZsOdJp9AQhD0H/hRxemZrc8B2NtC', '2025-09-15 07:46:33', 'images/profile.jpg', 0),
(10, 'Charles Cabral', 'charlescabral00@gmail.com', '$2y$10$4HUDpAot.YFWHM8ju.evre6ysLOB38PfG7Yct6UV7m2kMvKa5ZjPW', '2025-09-17 08:29:07', 'images/profile.jpg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_lessons`
--

CREATE TABLE `user_lessons` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_lessons`
--

INSERT INTO `user_lessons` (`id`, `user_id`, `lesson_id`, `completed_at`) VALUES
(1, 4, 1, '2025-09-17 14:51:46'),
(2, 4, 2, '2025-09-17 14:51:49'),
(3, 4, 3, '2025-09-17 14:51:53'),
(4, 4, 4, '2025-09-17 14:51:56'),
(5, 5, 1, '2025-09-07 12:17:59'),
(6, 5, 2, '2025-09-07 12:18:07'),
(7, 4, 5, '2025-09-17 14:52:00'),
(8, 4, 6, '2025-09-17 14:52:05'),
(9, 4, 7, '2025-09-17 14:52:08'),
(10, 4, 8, '2025-09-17 14:52:12'),
(11, 4, 9, '2025-09-17 14:52:15'),
(12, 4, 10, '2025-09-17 14:52:19'),
(13, 4, 11, '2025-09-17 14:52:22'),
(14, 4, 12, '2025-09-16 11:11:04'),
(15, 4, 13, '2025-09-09 11:07:38'),
(16, 4, 14, '2025-09-09 11:07:41'),
(17, 4, 15, '2025-09-09 11:07:45'),
(18, 4, 16, '2025-09-16 12:20:58'),
(19, 4, 17, '2025-09-09 11:07:51'),
(20, 4, 18, '2025-09-09 11:07:54'),
(21, 4, 19, '2025-09-09 11:07:58'),
(22, 4, 20, '2025-09-07 13:11:29'),
(23, 4, 21, '2025-09-16 12:21:22'),
(24, 4, 22, '2025-09-09 11:08:10'),
(25, 4, 24, '2025-09-09 11:08:17'),
(26, 4, 25, '2025-09-09 11:08:25'),
(27, 4, 26, '2025-09-09 11:08:29'),
(28, 4, 27, '2025-09-09 11:08:33'),
(29, 4, 23, '2025-09-09 11:08:14'),
(30, 4, 28, '2025-09-09 11:08:38'),
(31, 4, 29, '2025-09-09 11:08:41'),
(32, 4, 30, '2025-09-09 11:08:46'),
(33, 4, 31, '2025-09-09 11:08:50'),
(34, 4, 32, '2025-09-09 11:08:58'),
(35, 1, 1, '2025-09-18 09:48:48'),
(36, 1, 2, '2025-09-18 09:48:38'),
(37, 7, 1, '2025-09-07 13:52:49'),
(38, 7, 2, '2025-09-07 13:52:51'),
(39, 7, 3, '2025-09-07 13:52:54'),
(40, 7, 4, '2025-09-07 13:53:05'),
(41, 7, 5, '2025-09-07 13:53:10'),
(42, 8, 1, '2025-09-07 14:11:17'),
(43, 1, 3, '2025-09-13 13:20:35'),
(44, 1, 4, '2025-09-11 08:41:58'),
(45, 1, 5, '2025-09-16 10:27:48'),
(46, 8, 2, '2025-09-11 09:22:36'),
(47, 8, 3, '2025-09-11 09:22:43'),
(48, 8, 4, '2025-09-11 09:22:46'),
(49, 8, 5, '2025-09-11 09:22:57'),
(50, 8, 6, '2025-09-11 09:23:00'),
(51, 8, 7, '2025-09-11 09:23:07'),
(52, 8, 8, '2025-09-11 09:23:10'),
(53, 8, 9, '2025-09-11 09:23:13'),
(54, 8, 10, '2025-09-11 09:23:24'),
(55, 8, 11, '2025-09-11 09:27:03'),
(56, 8, 12, '2025-09-11 09:31:15'),
(57, 8, 13, '2025-09-11 09:34:45'),
(58, 8, 14, '2025-09-11 09:38:05'),
(59, 8, 15, '2025-09-11 09:43:52'),
(60, 8, 16, '2025-09-11 09:49:42'),
(61, 8, 17, '2025-09-11 09:55:55'),
(62, 8, 18, '2025-09-11 10:02:00'),
(63, 8, 19, '2025-09-11 10:08:44'),
(64, 8, 20, '2025-09-11 10:14:57'),
(65, 8, 21, '2025-09-11 12:12:29'),
(66, 8, 22, '2025-09-11 12:18:46'),
(67, 8, 23, '2025-09-11 12:23:47'),
(68, 8, 24, '2025-09-11 12:28:54'),
(69, 8, 25, '2025-09-11 12:32:33'),
(70, 8, 26, '2025-09-11 12:39:41'),
(71, 8, 27, '2025-09-11 13:54:43'),
(72, 8, 28, '2025-09-11 14:00:35'),
(73, 8, 29, '2025-09-11 14:05:34'),
(74, 8, 30, '2025-09-11 14:09:57'),
(75, 8, 31, '2025-09-11 14:13:49'),
(76, 8, 32, '2025-09-11 14:19:12'),
(77, 5, 3, '2025-09-11 14:21:40'),
(78, 1, 6, '2025-09-14 14:47:51'),
(79, 5, 4, '2025-09-12 16:45:02'),
(80, 5, 5, '2025-09-12 16:45:33'),
(81, 1, 7, '2025-09-12 22:24:31'),
(82, 1, 8, '2025-09-15 09:16:14'),
(83, 9, 1, '2025-09-15 15:48:47'),
(84, 1, 9, '2025-09-17 15:36:03'),
(85, 10, 1, '2025-09-17 16:29:27'),
(86, 10, 2, '2025-09-17 16:29:36'),
(87, 10, 3, '2025-09-17 16:29:42'),
(88, 1, 10, '2025-09-18 11:05:25');

-- --------------------------------------------------------

--
-- Table structure for table `user_progress`
--

CREATE TABLE `user_progress` (
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_progress`
--

INSERT INTO `user_progress` (`user_id`, `lesson_id`, `completed`) VALUES
(1, 1, 0),
(1, 2, 0),
(1, 3, 0),
(1, 4, 0),
(1, 5, 0),
(1, 6, 0),
(1, 7, 0),
(1, 8, 0),
(1, 9, 0),
(1, 10, 0),
(4, 1, 0),
(4, 2, 0),
(4, 3, 0),
(4, 4, 0),
(4, 5, 0),
(4, 6, 0),
(4, 7, 0),
(4, 8, 0),
(4, 9, 0),
(4, 10, 0),
(4, 11, 0),
(4, 12, 0),
(4, 13, 0),
(4, 14, 0),
(4, 15, 0),
(4, 16, 0),
(4, 17, 0),
(4, 18, 0),
(4, 19, 0),
(4, 20, 0),
(4, 21, 0),
(4, 22, 0),
(4, 23, 0),
(4, 24, 0),
(4, 25, 0),
(4, 26, 0),
(4, 27, 0),
(4, 28, 0),
(4, 29, 0),
(4, 30, 0),
(4, 31, 0),
(4, 32, 0),
(5, 1, 0),
(5, 2, 0),
(5, 3, 0),
(5, 4, 0),
(5, 5, 0),
(7, 1, 0),
(7, 2, 0),
(7, 3, 0),
(7, 4, 0),
(7, 5, 0),
(8, 1, 0),
(8, 2, 0),
(8, 3, 0),
(8, 4, 0),
(8, 5, 0),
(8, 6, 0),
(8, 7, 0),
(8, 8, 0),
(8, 9, 0),
(8, 10, 0),
(8, 11, 0),
(8, 12, 0),
(8, 13, 0),
(8, 14, 0),
(8, 15, 0),
(8, 16, 0),
(8, 17, 0),
(8, 18, 0),
(8, 19, 0),
(8, 20, 0),
(8, 21, 0),
(8, 22, 0),
(8, 23, 0),
(8, 24, 0),
(8, 25, 0),
(8, 26, 0),
(8, 27, 0),
(8, 28, 0),
(8, 29, 0),
(8, 30, 0),
(8, 31, 0),
(8, 32, 0),
(9, 1, 0),
(10, 1, 0),
(10, 2, 0),
(10, 3, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_questions`
--
ALTER TABLE `chat_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user` (`user_id`);

--
-- Indexes for table `discussions`
--
ALTER TABLE `discussions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request` (`requester_id`,`requestee_id`),
  ADD KEY `requestee_id` (`requestee_id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`user_id`),
  ADD KEY `fk_likes_user` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`user_id`);

--
-- Indexes for table `post_reactions`
--
ALTER TABLE `post_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`post_id`,`reply_id`,`user_id`),
  ADD KEY `reply_id` (`reply_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `replies`
--
ALTER TABLE `replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `replies_ibfk_1` (`post_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_lessons`
--
ALTER TABLE `user_lessons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`user_id`,`lesson_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chat_questions`
--
ALTER TABLE `chat_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discussions`
--
ALTER TABLE `discussions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `friend_requests`
--
ALTER TABLE `friend_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `post_reactions`
--
ALTER TABLE `post_reactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `replies`
--
ALTER TABLE `replies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_lessons`
--
ALTER TABLE `user_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD CONSTRAINT `friend_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friend_requests_ibfk_2` FOREIGN KEY (`requestee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `fk_likes_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_reactions`
--
ALTER TABLE `post_reactions`
  ADD CONSTRAINT `post_reactions_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_reactions_ibfk_2` FOREIGN KEY (`reply_id`) REFERENCES `replies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_reactions_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `replies`
--
ALTER TABLE `replies`
  ADD CONSTRAINT `replies_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
