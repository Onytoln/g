-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Počítač: localhost
-- Vytvořeno: Pon 06. čen 2022, 15:55
-- Verze serveru: 10.3.22-MariaDB-log
-- Verze PHP: 7.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `gryl00`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `location`
--

CREATE TABLE `location` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Vypisuji data pro tabulku `location`
--

INSERT INTO `location` (`id`, `name`) VALUES
(262, 'Gas'),
(258, 'hah'),
(261, 'has'),
(10, 'Hihi2'),
(6, 'Nádraží Hoho'),
(8, 'Nádraží nevim'),
(2, 'Nádraží Olomouc'),
(7, 'Nádraží xddd'),
(4, 'Praha - Hlavní Nádraží'),
(263, 'test');

-- --------------------------------------------------------

--
-- Struktura tabulky `passwd_reset_codes`
--

CREATE TABLE `passwd_reset_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_czech_ci NOT NULL,
  `sent_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Vypisuji data pro tabulku `passwd_reset_codes`
--

INSERT INTO `passwd_reset_codes` (`id`, `user_id`, `code`, `sent_time`, `expiry_time`) VALUES
(44, 13, 'l1icfb', '2022-06-05 18:00:57', '2022-06-06 18:00:57'),
(45, 13, 'PdsuHh', '2022-06-05 18:02:01', '2022-06-06 18:02:01'),
(46, 13, 'o3NshQ', '2022-06-05 18:05:08', '2022-06-06 18:05:08'),
(47, 13, 'x1BSUv', '2022-06-05 18:08:18', '2022-06-06 18:08:18');

-- --------------------------------------------------------

--
-- Struktura tabulky `ticket`
--

CREATE TABLE `ticket` (
  `id` int(11) NOT NULL,
  `time_schedule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `seat_count` int(11) NOT NULL,
  `seats` varchar(2000) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `ticket_types` varchar(500) COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `total_price` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Vypisuji data pro tabulku `ticket`
--

INSERT INTO `ticket` (`id`, `time_schedule_id`, `user_id`, `name`, `seat_count`, `seats`, `ticket_types`, `total_price`) VALUES
(22, 16, 13, 'Leon Grytsak', 3, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Struktura tabulky `ticket_type`
--

CREATE TABLE `ticket_type` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `price` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Vypisuji data pro tabulku `ticket_type`
--

INSERT INTO `ticket_type` (`id`, `name`, `price`) VALUES
(1, 'Student/Dítě', 30),
(2, 'Senior', 30),
(3, 'Dospělý', 60);

-- --------------------------------------------------------

--
-- Struktura tabulky `time_schedule`
--

CREATE TABLE `time_schedule` (
  `id` int(11) NOT NULL,
  `train_id` int(11) NOT NULL,
  `seats` varchar(2000) COLLATE utf8mb4_czech_ci NOT NULL,
  `from_location` int(11) NOT NULL,
  `to_location` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Vypisuji data pro tabulku `time_schedule`
--

INSERT INTO `time_schedule` (`id`, `train_id`, `seats`, `from_location`, `to_location`, `start_time`, `end_time`) VALUES
(12, 1, '1:0;2:0;3:0;4:0;5:0;6:0;7:0;8:0;9:0;10:0;11:0;12:0;13:0;14:0;15:0;16:0;17:0;18:0;19:0;20:0;21:0;22:0;23:0;24:0;25:0;26:0;27:0;28:0;29:0;30:0;31:0;32:0;33:0;34:0;35:0;36:0;37:0;38:0;39:0;40:0;41:0;42:0;43:0;44:0;45:0;46:0;47:0;48:0;49:0;50:0;51:0;52:0;53:0;54:0;55:0;56:0;57:0;58:0;59:0;60:0;61:0;62:0;63:0;64:0;65:0;66:0;67:0;68:0;69:0;70:0;71:0;72:0;73:0;74:0;75:0;76:0;77:0;78:0;79:0;80:0;81:0;82:0;83:0;84:0;85:0;86:0;87:0;88:0;89:0;90:0;91:0;92:0;93:0;94:0;95:0;96:0;97:0;98:0;99:0;100:0;101:0;102:0;103:0;104:0;105:0;106:0;107:0;108:0;109:0;110:0;111:0;112:0;113:0;114:0;115:0;116:0;117:0;118:0;119:0;120:0;', 262, 4, '2022-06-18 14:50:00', '2022-06-23 14:50:00'),
(13, 13, '1:0;2:0;3:0;4:0;5:0;6:0;7:0;8:0;9:0;10:0;11:0;12:0;13:0;14:0;15:0;16:0;17:0;18:0;19:0;20:0;21:0;22:0;23:0;24:0;25:0;26:0;27:0;28:0;29:0;30:0;31:0;32:0;33:0;34:0;35:0;36:0;37:0;38:0;39:0;40:0;41:0;42:0;43:0;44:0;45:0;46:0;47:0;48:0;49:0;50:0;51:0;52:0;53:0;54:0;55:0;56:0;57:0;58:0;59:0;60:0;61:0;62:0;63:0;64:0;65:0;66:0;67:0;68:0;69:0;70:0;71:0;72:0;73:0;74:0;75:0;76:0;77:0;78:0;79:0;80:0;81:0;82:0;83:0;84:0;85:0;86:0;87:0;88:0;89:0;90:0;91:0;92:0;93:0;94:0;95:0;96:0;97:0;98:0;99:0;100:0;101:0;102:0;103:0;104:0;105:0;106:0;107:0;108:0;109:0;110:0;111:0;112:0;113:0;114:0;115:0;116:0;117:0;118:0;119:0;120:0;', 4, 10, '2022-06-29 16:25:00', '2022-07-09 16:25:00'),
(16, 12, '1:0;2:0;3:0;4:0;5:0;6:0;7:0;8:0;9:0;10:0;11:0;12:0;13:0;14:0;15:0;16:0;17:0;18:0;19:0;20:0;', 261, 10, '2022-06-10 14:04:00', '2022-06-14 14:04:00'),
(17, 12, '1:0;2:0;3:0;4:0;5:0;6:0;7:0;8:0;9:0;10:0;11:0;12:0;13:0;14:0;15:0;16:0;17:0;18:0;19:0;20:0;', 10, 261, '2022-06-15 11:07:00', '2022-06-15 14:07:00');

-- --------------------------------------------------------

--
-- Struktura tabulky `trains`
--

CREATE TABLE `trains` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `seat_count` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Vypisuji data pro tabulku `trains`
--

INSERT INTO `trains` (`id`, `name`, `seat_count`, `active`) VALUES
(1, 'Vlak ABC', 120, 1),
(5, 'Vlak X', 60, 0),
(12, 'GH', 20, 1),
(13, 'Vlak XC', 120, 1),
(14, 'Vlax *xcb', 50, 0);

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `password` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  `role` varchar(20) COLLATE utf8_czech_ci NOT NULL DEFAULT 'user',
  `active` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující uživatelské účty';

--
-- Vypisuji data pro tabulku `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `active`) VALUES
(9, 'xname', 'xname@vse.cz', '$2y$10$Jp2.VQ.ecpwQk7Rs6O20Nuad2uLQ.delHfCgToDCy9oLVMARckzEm', 'user', 1),
(10, 'xadmin', 'xadmin@vse.cz', '$2y$10$7y1iNg56lD57m0zXc9tq4eedcxp3HBUjvHihagr0cIwQ394Bg/D7G', 'admin', 1),
(11, 'text', 'test01@t.cz', '$2y$10$tSq3GkaOzYLwl4jWGy2yOOZSjB/ccYIjTmJSmvZOLiZRcMtye9lbC', 'user', 1),
(12, 'test02', 'test02@seznam.cz', '$2y$10$8H4urRqSjdqTik.XqGc.g..NZFGGPEyNO8X4gsgjjBCwDksh2eIpa', 'user', 1),
(13, 'leo', 'gryl00@vse.cz', '$2y$10$V6U29SbJ5.lQXoR8lYnz3uFsbk82vHwXQIbrcO3s28ob4iX5JojRy', 'user', 1);

--
-- Klíče pro exportované tabulky
--

--
-- Klíče pro tabulku `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Klíče pro tabulku `passwd_reset_codes`
--
ALTER TABLE `passwd_reset_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Klíče pro tabulku `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `time_schedule_id` (`time_schedule_id`);

--
-- Klíče pro tabulku `ticket_type`
--
ALTER TABLE `ticket_type`
  ADD PRIMARY KEY (`id`);

--
-- Klíče pro tabulku `time_schedule`
--
ALTER TABLE `time_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `train_id` (`train_id`),
  ADD KEY `from_location` (`from_location`,`to_location`),
  ADD KEY `time_schedule_ibfk_3` (`to_location`);

--
-- Klíče pro tabulku `trains`
--
ALTER TABLE `trains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Klíče pro tabulku `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `location`
--
ALTER TABLE `location`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=264;

--
-- AUTO_INCREMENT pro tabulku `passwd_reset_codes`
--
ALTER TABLE `passwd_reset_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT pro tabulku `ticket`
--
ALTER TABLE `ticket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pro tabulku `ticket_type`
--
ALTER TABLE `ticket_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pro tabulku `time_schedule`
--
ALTER TABLE `time_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pro tabulku `trains`
--
ALTER TABLE `trains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pro tabulku `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `passwd_reset_codes`
--
ALTER TABLE `passwd_reset_codes`
  ADD CONSTRAINT `passwd_reset_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ticket_ibfk_2` FOREIGN KEY (`time_schedule_id`) REFERENCES `time_schedule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `time_schedule`
--
ALTER TABLE `time_schedule`
  ADD CONSTRAINT `time_schedule_ibfk_1` FOREIGN KEY (`train_id`) REFERENCES `trains` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `time_schedule_ibfk_2` FOREIGN KEY (`from_location`) REFERENCES `location` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `time_schedule_ibfk_3` FOREIGN KEY (`to_location`) REFERENCES `location` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
