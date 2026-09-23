-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Creato il: Set 21, 2026 alle 20:26
-- Versione del server: 8.0.46
-- Versione PHP: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `numismat`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `coin`
--

CREATE TABLE `coin` (
  `id` int NOT NULL,
  `typeID` int NOT NULL,
  `number` int NOT NULL,
  `year` int NOT NULL,
  `grade` enum('g','vg','f','vf','xf','au','unc') NOT NULL,
  `value` int NOT NULL,
  `position` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `coin_type`
--

CREATE TABLE `coin_type` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `numista_id` int NOT NULL,
  `value_id` int NOT NULL,
  `numeric_value` int NOT NULL,
  `min_year` int NOT NULL,
  `max_year` int NOT NULL,
  `type_id` int NOT NULL,
  `img_obverse` text NOT NULL,
  `img_reverse` int NOT NULL,
  `desc_obverse` text NOT NULL,
  `desc_reverse` text NOT NULL,
  `comments` text NOT NULL,
  `issuer` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `currency`
--

CREATE TABLE `currency` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `type`
--

CREATE TABLE `type` (
  `id` int NOT NULL,
  `name` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `user`
--

CREATE TABLE `user` (
  `name` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `coin`
--
ALTER TABLE `coin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `typeID` (`typeID`);

--
-- Indici per le tabelle `coin_type`
--
ALTER TABLE `coin_type`
  ADD PRIMARY KEY (`id`),
  ADD KEY `value_id` (`value_id`),
  ADD KEY `numista_id` (`numista_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indici per le tabelle `currency`
--
ALTER TABLE `currency`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `type`
--
ALTER TABLE `type`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`name`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `coin`
--
ALTER TABLE `coin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `coin_type`
--
ALTER TABLE `coin_type`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `currency`
--
ALTER TABLE `currency`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `type`
--
ALTER TABLE `type`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `coin`
--
ALTER TABLE `coin`
  ADD CONSTRAINT `coin_ibfk_1` FOREIGN KEY (`typeID`) REFERENCES `coin_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `coin_type`
--
ALTER TABLE `coin_type`
  ADD CONSTRAINT `coin_type_ibfk_1` FOREIGN KEY (`value_id`) REFERENCES `currency` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `coin_type_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `type` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
