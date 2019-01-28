-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.1.28-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win32
-- HeidiSQL Version:             9.5.0.5196
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Dumping database structure for myapi
CREATE DATABASE IF NOT EXISTS `myapi` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `myapi`;

-- Dumping structure for table myapi.branches
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `store_name` varchar(50) NOT NULL,
  `store_state` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

-- Dumping data for table myapi.branches: ~0 rows (approximately)
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` (`id`, `parent_id`, `store_name`, `store_state`) VALUES
	(1, 0, 'A', 'NSW'),
	(2, 0, 'B', 'VIC'),
	(3, 0, 'C', 'WA'),
	(4, 1, 'D', 'QLD'),
	(5, 1, 'E', 'NSW'),
	(6, 2, 'F', 'TAS'),
	(7, 2, 'G', 'NSW'),
	(8, 2, 'H', 'QLD'),
	(9, 3, 'I', 'NSW'),
	(10, 9, 'J', 'VIC'),
	(11, 9, 'K', 'NSW'),
	(12, 7, 'L', 'WA');
	
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
