-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: n8n_mysql:3306
-- Generation Time: Oct 29, 2025 at 07:45 PM
-- Server version: 9.5.0
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ROBERTA`
--

-- --------------------------------------------------------

--
-- Table structure for table `entrantes`
--

CREATE TABLE `entrantes` (
  `CPF` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `BENEFICIO` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `NOME` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DDB` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `VALOR_BENEFICIO` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DATA_NASCIMENTO` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `IDADE` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CODIGO_ESPECIE` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CIDADE` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `UF` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `LEMIT1` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `LEMIT2` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `LEMIT3` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `margem_35` decimal(10,2) DEFAULT NULL,
  `cartao_rcc` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `entrantes`
--
DELIMITER $$
CREATE TRIGGER `atualizar_margens` BEFORE UPDATE ON `entrantes` FOR EACH ROW BEGIN
  SET NEW.margem_35  = CAST(NEW.VALOR_BENEFICIO AS DECIMAL(10,2)) * 0.35;
  SET NEW.cartao_rcc = CAST(NEW.VALOR_BENEFICIO AS DECIMAL(10,2)) * 0.05;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `calcular_margens` BEFORE INSERT ON `entrantes` FOR EACH ROW BEGIN
  SET NEW.margem_35  = CAST(NEW.VALOR_BENEFICIO AS DECIMAL(10,2)) * 0.35;
  SET NEW.cartao_rcc = CAST(NEW.VALOR_BENEFICIO AS DECIMAL(10,2)) * 0.05;
END
$$
DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
