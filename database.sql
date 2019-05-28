-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Wersja serwera:               10.1.35-MariaDB - mariadb.org binary distribution
-- Serwer OS:                    Win32
-- HeidiSQL Wersja:              10.1.0.5464
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Zrzut struktury bazy danych sklep
CREATE DATABASE IF NOT EXISTS `sklep` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `sklep`;

-- Zrzut struktury tabela sklep.category
CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key_pl` varchar(90) NOT NULL,
  `sub` int(8) unsigned NOT NULL,
  `level` int(5) unsigned NOT NULL,
  `sub_count` int(5) unsigned NOT NULL,
  `name_pl` varchar(200) NOT NULL,
  `sort` int(5) unsigned NOT NULL,
  `visible_pl` enum('','on') NOT NULL,
  `group` int(5) unsigned NOT NULL,
  `cdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_pl` (`key_pl`),
  KEY `visible_pl_sub_group_sort` (`visible_pl`,`sub`,`group`,`sort`),
  KEY `sub_group_sort` (`sub`,`group`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.
-- Zrzut struktury tabela sklep.order
CREATE TABLE IF NOT EXISTS `order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(5) unsigned NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `basket_amount` float(8,2) NOT NULL,
  `basket_amount_net` float(8,2) NOT NULL,
  `order_amount` float(8,2) NOT NULL,
  `adress` text NOT NULL,
  `ip` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.
-- Zrzut struktury tabela sklep.order_product
CREATE TABLE IF NOT EXISTS `order_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(8) unsigned NOT NULL,
  `order_id` int(5) unsigned NOT NULL,
  `name` text NOT NULL,
  `quantity` int(3) unsigned NOT NULL,
  `pricen` decimal(12,2) NOT NULL,
  `priceg` decimal(12,2) NOT NULL,
  `vat` int(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.
-- Zrzut struktury tabela sklep.product
CREATE TABLE IF NOT EXISTS `product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key_pl` varchar(80) NOT NULL,
  `symbol` varchar(50) NOT NULL,
  `name_pl` varchar(255) DEFAULT NULL,
  `desc_pl` text NOT NULL,
  `visible_pl` enum('','on') NOT NULL DEFAULT 'on',
  `basket` enum('','on') NOT NULL DEFAULT '',
  `stock` int(5) unsigned NOT NULL,
  `vat` int(3) unsigned NOT NULL,
  `priceAn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `priceBn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `priceAg` decimal(12,2) NOT NULL DEFAULT '0.00',
  `priceBg` decimal(12,2) NOT NULL DEFAULT '0.00',
  `fotos` varchar(40) NOT NULL,
  `fotom` varchar(40) NOT NULL DEFAULT '',
  `fotob` varchar(40) NOT NULL DEFAULT '',
  `purchase_count` int(8) unsigned NOT NULL DEFAULT '0',
  `view_count` int(8) unsigned NOT NULL DEFAULT '0',
  `view_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `cdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_pl` (`key_pl`),
  KEY `symbol` (`symbol`),
  KEY `name_pl` (`name_pl`),
  KEY `visible_pl` (`visible_pl`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.
-- Zrzut struktury tabela sklep.product_connection
CREATE TABLE IF NOT EXISTS `product_connection` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(8) unsigned NOT NULL,
  `cid` int(5) unsigned NOT NULL,
  `sort` int(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pid` (`pid`,`cid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- Data exporting was unselected.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
