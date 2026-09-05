-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0:3306
-- Время создания: Янв 06 2026 г., 19:57
-- Версия сервера: 8.0.43
-- Версия PHP: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `daily-planner`
--

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `combine_work_study` tinyint(1) DEFAULT '0',
  `daily_limit` int DEFAULT '15',
  `custom_daily_limit` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `last_name`, `first_name`, `middle_name`, `email`, `password`, `combine_work_study`, `daily_limit`, `custom_daily_limit`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'Пронькин', 'Альберт', 'Андреевич', 'pronkin.albert2017@yandex.ru', '$2y$10$Svl2h9tvf.nKtyhpUEHMIu2lNAxbfEApHIoOWEIa7OIB3THVwhGV6', 1, 30, NULL, 1, '2025-12-15 09:05:50', '2025-12-15 09:05:50'),
(5, 'Пронькин', 'Альберт', 'Андреевич', '2022102643@togudv.ru', '$2y$10$6AG.Jvbs6OREVi2O9oga4urhvmQ2sHU6GOyoxhsXVztojmP3nKjea', 1, 20, NULL, 1, '2025-12-15 10:01:13', '2026-01-06 03:34:12'),
(6, 'ку', 'ли', 'ол', 'poka@p.ru', '$2y$10$MAl7Q9wN0OuM8mZMSkriyuw7Hur5ycnfs5j60fKmuLPc0TbFyUpia', 1, 15, NULL, 1, '2025-12-15 12:00:06', '2025-12-23 02:42:29'),
(7, 'ку', 'ли', 'ол', 'poka2@p.ru', '$2y$10$5J7woNtvHenT5gsFElsov.EybKVjX6EATTP6kDbPTSwNkX7J.stjG', 0, 15, NULL, 1, '2025-12-16 09:46:12', '2025-12-16 09:46:12'),
(8, 'Кузнецова', 'Полина', '', '2022101836@togudv.ru', '$2y$10$gZrHf8FhOGlicIaGKHOKiuk7P710GiLR1RFszpBLhNbrGxxhUyNjC', 0, 15, NULL, 1, '2026-01-06 04:41:43', '2026-01-06 08:32:26');

-- --------------------------------------------------------

--
-- Структура таблицы `user_avatars`
--

CREATE TABLE `user_avatars` (
  `user_id` int NOT NULL,
  `avatar_number` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `user_avatars`
--

INSERT INTO `user_avatars` (`user_id`, `avatar_number`) VALUES
(5, 1),
(6, 4),
(7, 8),
(8, 12);

-- --------------------------------------------------------

--
-- Структура таблицы `user_fixed_tasks`
--

CREATE TABLE `user_fixed_tasks` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `user_fixed_tasks`
--

INSERT INTO `user_fixed_tasks` (`id`, `user_id`, `day_of_week`, `start_time`, `end_time`, `description`) VALUES
(4, 5, 'monday', '15:00:00', '19:00:00', NULL),
(5, 5, 'wednesday', '16:00:00', '19:00:00', NULL),
(6, 5, 'friday', '17:00:00', '20:00:00', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_stats`
--

CREATE TABLE `user_stats` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tasks_completed` int DEFAULT '0',
  `days_active` int DEFAULT '1',
  `last_active_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_stats`
--

INSERT INTO `user_stats` (`id`, `user_id`, `tasks_completed`, `days_active`, `last_active_date`, `created_at`, `updated_at`) VALUES
(1, 8, 0, 1, '2026-01-06', '2026-01-06 04:42:47', '2026-01-06 04:42:47');

-- --------------------------------------------------------

--
-- Структура таблицы `user_study_schedule`
--

CREATE TABLE `user_study_schedule` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `user_study_schedule`
--

INSERT INTO `user_study_schedule` (`id`, `user_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(10, 4, 'monday', '08:00:00', '13:00:00'),
(11, 4, 'tuesday', '07:00:00', '13:00:00'),
(12, 4, 'wednesday', '06:00:00', '13:00:00'),
(13, 4, 'thursday', '09:00:00', '14:00:00'),
(14, 4, 'friday', '10:00:00', '14:00:00'),
(15, 4, 'saturday', '11:00:00', '15:00:00'),
(16, 5, 'monday', '05:00:00', '12:00:00'),
(17, 5, 'tuesday', '12:00:00', '15:00:00'),
(18, 5, 'wednesday', '10:00:00', '12:00:00'),
(19, 5, 'thursday', '10:00:00', '12:00:00'),
(20, 5, 'friday', '13:00:00', '15:00:00'),
(21, 5, 'saturday', '12:00:00', '15:00:00');

-- --------------------------------------------------------

--
-- Структура таблицы `user_tasks`
--

CREATE TABLE `user_tasks` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `task_type` enum('analytical','creative','routine','social','research','physical','learning','planning') NOT NULL,
  `urgency` int NOT NULL DEFAULT '4',
  `importance` int NOT NULL DEFAULT '5',
  `expected_duration_minutes` int NOT NULL,
  `duration` decimal(4,1) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `preferred_time` enum('any','morning','day','evening') DEFAULT 'any',
  `preferred_day` varchar(20) DEFAULT 'any',
  `notes` text,
  `weight` decimal(5,1) DEFAULT '0.0',
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `scheduled_day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') DEFAULT NULL,
  `completed` tinyint(1) DEFAULT '0',
  `is_scheduled` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `user_tasks`
--

INSERT INTO `user_tasks` (`id`, `user_id`, `title`, `task_type`, `urgency`, `importance`, `expected_duration_minutes`, `duration`, `deadline`, `preferred_time`, `preferred_day`, `notes`, `weight`, `scheduled_date`, `scheduled_time`, `scheduled_day_of_week`, `completed`, `is_scheduled`, `created_at`, `updated_at`) VALUES
(52, 6, 'dfcgvhbjnkm', 'creative', 4, 5, 60, 1.0, NULL, 'any', 'monday', '', 8.4, '2025-12-26', '18:15:00', 'friday', 1, 1, '2025-12-23 04:13:27', '2025-12-23 04:14:22'),
(53, 6, 'cvbnm,', 'routine', 4, 5, 60, 1.0, NULL, 'any', 'monday', '', 5.7, '2025-12-22', '18:15:00', 'monday', 1, 1, '2025-12-23 04:13:46', '2025-12-23 04:14:19'),
(54, 6, 'dhxfgcjhvkjbknlm;,', 'creative', 4, 5, 60, 1.0, NULL, 'any', 'wednesday', '', 8.4, '2025-12-24', '18:15:00', 'wednesday', 1, 1, '2025-12-23 04:13:57', '2025-12-23 04:14:20'),
(55, 6, 'hdfgjh kjlbknlm;', 'routine', 4, 5, 60, 1.0, NULL, 'any', 'wednesday', '', 5.7, '2025-12-24', '18:15:00', 'wednesday', 1, 1, '2025-12-23 04:14:36', '2025-12-23 04:21:20'),
(56, 6, 'ghvjbknlm', 'learning', 4, 5, 120, 2.0, NULL, 'any', 'wednesday', '', 7.5, '2025-12-24', '18:15:00', 'wednesday', 1, 1, '2025-12-23 04:14:50', '2025-12-23 04:36:47'),
(57, 6, 'fgvhjbknlm;', 'research', 4, 5, 120, 2.0, NULL, 'any', 'wednesday', '', 9.3, '2025-12-22', '18:15:00', 'monday', 1, 1, '2025-12-23 04:21:29', '2025-12-23 04:21:36'),
(58, 6, 'vbnm,./', 'creative', 1, 2, 60, 1.0, NULL, 'any', 'wednesday', '', 4.8, '2025-12-24', '18:15:00', 'wednesday', 1, 1, '2025-12-23 04:21:48', '2025-12-23 04:28:39'),
(59, 6, 'чсмпролд', 'social', 4, 5, 60, 1.0, '2025-12-23', 'any', 'tuesday', '', 7.5, '2025-12-23', '18:15:00', 'tuesday', 1, 1, '2025-12-23 04:28:57', '2025-12-23 04:36:46'),
(60, 6, 'чапролд', 'routine', 4, 5, 60, 1.0, NULL, 'any', 'wednesday', '', 5.7, '2025-12-24', '20:30:00', 'wednesday', 1, 1, '2025-12-23 04:30:52', '2025-12-23 04:36:48'),
(61, 6, 'вапролдлорп', 'routine', 4, 5, 60, 1.0, NULL, 'any', 'any', '', 5.7, '2025-12-23', '18:15:00', 'tuesday', 1, 1, '2025-12-23 04:36:37', '2025-12-23 04:36:45'),
(62, 6, 'вапав', 'social', 4, 5, 60, 1.0, NULL, 'any', 'any', '', 7.5, '2025-12-23', '18:15:00', 'tuesday', 1, 1, '2025-12-23 04:36:57', '2025-12-23 04:38:03'),
(63, 6, 'ываперолд', 'physical', 4, 5, 60, 1.0, NULL, 'any', 'any', '', 6.6, '2025-12-23', '18:15:00', 'tuesday', 1, 1, '2025-12-23 04:39:32', '2025-12-23 04:39:48'),
(64, 6, 'апролд', 'social', 4, 5, 60, 1.0, NULL, 'any', 'thursday', '', 7.5, '2025-12-24', '18:15:00', 'wednesday', 1, 1, '2025-12-23 04:39:58', '2025-12-23 04:42:07'),
(65, 6, 'напролод', 'routine', 1, 2, 120, 2.0, NULL, 'any', 'thursday', '', 3.9, '2025-12-25', '18:15:00', 'thursday', 1, 1, '2025-12-23 04:40:22', '2025-12-23 04:41:23'),
(66, 6, 'ываып', 'routine', 4, 5, 60, 1.0, NULL, 'any', 'any', '', 5.7, '2025-12-23', '18:15:00', 'tuesday', 1, 1, '2025-12-23 04:41:32', '2025-12-23 04:41:36'),
(67, 5, 'пример 1', 'analytical', 4, 5, 60, 1.0, '2026-01-08', 'any', 'any', '', 3.9, '2026-01-05', '18:15:00', 'tuesday', 1, 1, '2026-01-06 03:31:32', '2026-01-06 03:38:01'),
(70, 8, 'wdwwd', 'routine', 4, 5, 60, 1.0, NULL, 'any', 'any', '', 5.7, NULL, NULL, NULL, 0, 0, '2026-01-06 04:41:55', '2026-01-06 04:41:55');

-- --------------------------------------------------------

--
-- Структура таблицы `user_task_energy`
--

CREATE TABLE `user_task_energy` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_type` enum('analytical','creative','routine','social','research','physical','learning','planning') DEFAULT NULL,
  `energy_level` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `user_task_energy`
--

INSERT INTO `user_task_energy` (`id`, `user_id`, `task_type`, `energy_level`) VALUES
(25, 4, 'analytical', 2),
(26, 4, 'creative', 9),
(27, 4, 'routine', 2),
(28, 4, 'social', 9),
(29, 4, 'research', 2),
(30, 4, 'physical', 9),
(31, 4, 'learning', 2),
(32, 4, 'planning', 9),
(33, 5, 'analytical', 1),
(34, 5, 'creative', 10),
(35, 5, 'routine', 1),
(36, 5, 'social', 10),
(37, 5, 'research', 1),
(38, 5, 'physical', 10),
(39, 5, 'learning', 1),
(40, 5, 'planning', 10),
(41, 6, 'analytical', 8),
(42, 6, 'creative', 6),
(43, 6, 'routine', 3),
(44, 6, 'social', 5),
(45, 6, 'research', 7),
(46, 6, 'physical', 4),
(47, 6, 'learning', 5),
(48, 6, 'planning', 2),
(49, 7, 'analytical', 8),
(50, 7, 'creative', 6),
(51, 7, 'routine', 3),
(52, 7, 'social', 5),
(53, 7, 'research', 7),
(54, 7, 'physical', 4),
(55, 7, 'learning', 5),
(56, 7, 'planning', 2),
(57, 8, 'analytical', 8),
(58, 8, 'creative', 6),
(59, 8, 'routine', 3),
(60, 8, 'social', 5),
(61, 8, 'research', 7),
(62, 8, 'physical', 4),
(63, 8, 'learning', 5),
(64, 8, 'planning', 2);

-- --------------------------------------------------------

--
-- Структура таблицы `user_work_schedule`
--

CREATE TABLE `user_work_schedule` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `user_work_schedule`
--

INSERT INTO `user_work_schedule` (`id`, `user_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 4, 'monday', '15:00:00', '18:00:00'),
(2, 4, 'tuesday', '00:00:00', '00:00:00'),
(3, 4, 'wednesday', '15:00:00', '18:00:00'),
(4, 4, 'thursday', '00:00:00', '00:00:00'),
(5, 4, 'friday', '00:00:00', '00:00:00'),
(6, 4, 'saturday', '15:00:00', '18:00:00'),
(7, 5, 'monday', '09:00:00', '18:00:00'),
(8, 5, 'tuesday', '09:00:00', '18:00:00'),
(9, 5, 'wednesday', '09:00:00', '18:00:00'),
(10, 5, 'thursday', '09:00:00', '18:00:00'),
(11, 5, 'friday', '09:00:00', '18:00:00'),
(12, 6, 'monday', '09:00:00', '18:00:00'),
(13, 6, 'tuesday', '09:00:00', '18:00:00'),
(14, 6, 'wednesday', '09:00:00', '18:00:00'),
(15, 6, 'thursday', '09:00:00', '18:00:00'),
(16, 6, 'friday', '09:00:00', '18:00:00');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Индексы таблицы `user_avatars`
--
ALTER TABLE `user_avatars`
  ADD PRIMARY KEY (`user_id`);

--
-- Индексы таблицы `user_fixed_tasks`
--
ALTER TABLE `user_fixed_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `user_stats`
--
ALTER TABLE `user_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Индексы таблицы `user_study_schedule`
--
ALTER TABLE `user_study_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_day` (`user_id`,`day_of_week`);

--
-- Индексы таблицы `user_tasks`
--
ALTER TABLE `user_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_scheduled` (`is_scheduled`),
  ADD KEY `scheduled_date` (`scheduled_date`);

--
-- Индексы таблицы `user_task_energy`
--
ALTER TABLE `user_task_energy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_task` (`user_id`,`task_type`);

--
-- Индексы таблицы `user_work_schedule`
--
ALTER TABLE `user_work_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_day` (`user_id`,`day_of_week`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `user_fixed_tasks`
--
ALTER TABLE `user_fixed_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `user_stats`
--
ALTER TABLE `user_stats`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `user_study_schedule`
--
ALTER TABLE `user_study_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT для таблицы `user_tasks`
--
ALTER TABLE `user_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT для таблицы `user_task_energy`
--
ALTER TABLE `user_task_energy`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT для таблицы `user_work_schedule`
--
ALTER TABLE `user_work_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `user_avatars`
--
ALTER TABLE `user_avatars`
  ADD CONSTRAINT `user_avatars_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_fixed_tasks`
--
ALTER TABLE `user_fixed_tasks`
  ADD CONSTRAINT `user_fixed_tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_stats`
--
ALTER TABLE `user_stats`
  ADD CONSTRAINT `user_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_study_schedule`
--
ALTER TABLE `user_study_schedule`
  ADD CONSTRAINT `user_study_schedule_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_tasks`
--
ALTER TABLE `user_tasks`
  ADD CONSTRAINT `user_tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_task_energy`
--
ALTER TABLE `user_task_energy`
  ADD CONSTRAINT `user_task_energy_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_work_schedule`
--
ALTER TABLE `user_work_schedule`
  ADD CONSTRAINT `user_work_schedule_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
