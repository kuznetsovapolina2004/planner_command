-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Дек 16 2025 г., 13:25
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `last_name`, `first_name`, `middle_name`, `email`, `password`, `combine_work_study`, `daily_limit`, `custom_daily_limit`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'Пронькин', 'Альберт', 'Андреевич', 'pronkin.albert2017@yandex.ru', '$2y$10$Svl2h9tvf.nKtyhpUEHMIu2lNAxbfEApHIoOWEIa7OIB3THVwhGV6', 1, 30, NULL, 1, '2025-12-15 09:05:50', '2025-12-15 09:05:50'),
(5, 'Пронькин', 'Альберт', 'Андреевич', '2022102643@togudv.ru', '$2y$10$6AG.Jvbs6OREVi2O9oga4urhvmQ2sHU6GOyoxhsXVztojmP3nKjea', 1, 30, NULL, 1, '2025-12-15 10:01:13', '2025-12-16 07:26:12'),
(6, 'ку', 'ли', 'ол', 'poka@p.ru', '$2y$10$MAl7Q9wN0OuM8mZMSkriyuw7Hur5ycnfs5j60fKmuLPc0TbFyUpia', 0, 15, NULL, 1, '2025-12-15 12:00:06', '2025-12-15 12:00:06'),
(7, 'ку', 'ли', 'ол', 'poka2@p.ru', '$2y$10$5J7woNtvHenT5gsFElsov.EybKVjX6EATTP6kDbPTSwNkX7J.stjG', 0, 15, NULL, 1, '2025-12-16 09:46:12', '2025-12-16 09:46:12');

-- --------------------------------------------------------

--
-- Структура таблицы `user_avatars`
--

CREATE TABLE `user_avatars` (
  `user_id` int NOT NULL,
  `avatar_number` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_avatars`
--

INSERT INTO `user_avatars` (`user_id`, `avatar_number`) VALUES
(5, 1),
(7, 8);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_fixed_tasks`
--

INSERT INTO `user_fixed_tasks` (`id`, `user_id`, `day_of_week`, `start_time`, `end_time`, `description`) VALUES
(4, 5, 'monday', '15:00:00', '19:00:00', NULL),
(5, 5, 'wednesday', '16:00:00', '19:00:00', NULL),
(6, 5, 'friday', '17:00:00', '20:00:00', NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
-- Структура таблицы `user_task_energy`
--

CREATE TABLE `user_task_energy` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_type` enum('analytical','creative','routine','social','research','physical','learning','planning') DEFAULT NULL,
  `energy_level` int DEFAULT NULL
) ;

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
(56, 7, 'planning', 2);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(11, 5, 'friday', '09:00:00', '18:00:00');

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
-- Индексы таблицы `user_study_schedule`
--
ALTER TABLE `user_study_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_day` (`user_id`,`day_of_week`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `user_fixed_tasks`
--
ALTER TABLE `user_fixed_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `user_study_schedule`
--
ALTER TABLE `user_study_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT для таблицы `user_task_energy`
--
ALTER TABLE `user_task_energy`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `user_work_schedule`
--
ALTER TABLE `user_work_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
-- Ограничения внешнего ключа таблицы `user_study_schedule`
--
ALTER TABLE `user_study_schedule`
  ADD CONSTRAINT `user_study_schedule_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
