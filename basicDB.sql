-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 29, 2025 at 11:23 AM
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
-- Database: `cibdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `branch_info`
--

CREATE TABLE `branch_info` (
  `BR_CODE` varchar(20) NOT NULL,
  `BRANCH_NAME` varchar(200) NOT NULL,
  `BRANCH_ADDRESS` varchar(200) DEFAULT NULL,
  `BRANCH_CONTACT` varchar(500) DEFAULT NULL,
  `ORG_CODE` varchar(20) NOT NULL,
  `AUTHORIZED_STATUS` char(1) DEFAULT NULL,
  `AUTHORIZED_USER` varchar(100) DEFAULT NULL,
  `AUTHORIZED_DATE` date DEFAULT NULL,
  `ENTRY_DATE` date DEFAULT NULL,
  `ENTRY_USER` varchar(200) DEFAULT NULL,
  `EDIT_DATE` date DEFAULT NULL,
  `EDIT_USER` varchar(200) DEFAULT NULL,
  `DELETE_USER` varchar(200) DEFAULT NULL,
  `DELETE_DATE` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch_info`
--

INSERT INTO `branch_info` (`BR_CODE`, `BRANCH_NAME`, `BRANCH_ADDRESS`, `BRANCH_CONTACT`, `ORG_CODE`, `AUTHORIZED_STATUS`, `AUTHORIZED_USER`, `AUTHORIZED_DATE`, `ENTRY_DATE`, `ENTRY_USER`, `EDIT_DATE`, `EDIT_USER`, `DELETE_USER`, `DELETE_DATE`) VALUES
('100-100', 'HEAD OFFICE', NULL, NULL, '100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('100-101', 'Regional Office', 'Dhaka', '', '100', NULL, NULL, NULL, '2025-10-13', 'raju', '2025-10-14', 'raju', NULL, NULL),
('101-101', 'Diginala', 'Diginala, Khagrachori', '', '101', NULL, NULL, NULL, '2025-10-15', 'softadmin', '2025-10-15', 'softadmin', NULL, NULL),
('101-102', 'Khagrachori', '', '', '101', NULL, NULL, NULL, '2025-10-15', 'softadmin', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `menu_info`
--

CREATE TABLE `menu_info` (
  `MENU_ID` int(11) NOT NULL,
  `MENU_NAME` varchar(100) NOT NULL,
  `MENU_LINK` varchar(255) DEFAULT NULL,
  `PARENT_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_info`
--

INSERT INTO `menu_info` (`MENU_ID`, `MENU_NAME`, `MENU_LINK`, `PARENT_ID`) VALUES
(1, 'CONFIGURATION', 'dashboard.php', 0),
(2, 'SETUP', NULL, NULL),
(4, 'Reports', 'reports.php', 0),
(5, 'Monthly Report', 'monthly_report.php', 4),
(26, 'SUPPLIER', 'supplier.php', 2),
(27, 'product catagory', 'product_category.php', 2),
(28, 'USER ADMINISTRATION', '', NULL),
(30, 'ADD PERMISSION', 'add_permission.php', 28),
(31, 'PERMISSION', 'menu_permission.php', 4),
(32, 'USER TYPE', 'user_type.php', 1),
(33, 'ADD MENU', 'add_menu.php', 1),
(34, 'ADD ORGANIZATION', 'add_org.php', 1),
(35, 'USER MENU PERMISSION', 'user_menu_permission.php', 1),
(37, 'ADD BRANCH', 'add_branch.php', 1),
(40, 'USER CREATE', 'create_user.php', 1),
(41, 'Add product model', 'product_model.php', 2),
(42, 'ENTRY FORM', NULL, NULL),
(43, 'STOCK ENTRY', 'stock_entry.php', 42),
(44, 'Add Distributor', 'distributor.php', 2),
(46, 'USER ACTION PERMISSION', 'user_action_permission.php', 1);

-- --------------------------------------------------------

--
-- Table structure for table `organization_info`
--

CREATE TABLE `organization_info` (
  `ORG_CODE` varchar(20) NOT NULL,
  `ORGANIZATION_NAME` varchar(200) NOT NULL,
  `ORGANIZATION_ADDRESS` varchar(200) DEFAULT NULL,
  `ORGANIZATION_CONTACT` varchar(500) DEFAULT NULL,
  `AUTHORIZED_STARUS` varchar(1) DEFAULT NULL,
  `AUTHORIZED_USER` varchar(100) DEFAULT NULL,
  `AUTHORIZED_DATE` date DEFAULT NULL,
  `ENTRY_DATE` date DEFAULT NULL,
  `ENTRY_USER` varchar(200) DEFAULT NULL,
  `EDIT_DATE` date DEFAULT NULL,
  `EDIT_USER` varchar(200) DEFAULT NULL,
  `DELETE_USER` varchar(200) DEFAULT NULL,
  `DELETE_DATE` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organization_info`
--

INSERT INTO `organization_info` (`ORG_CODE`, `ORGANIZATION_NAME`, `ORGANIZATION_ADDRESS`, `ORGANIZATION_CONTACT`, `AUTHORIZED_STARUS`, `AUTHORIZED_USER`, `AUTHORIZED_DATE`, `ENTRY_DATE`, `ENTRY_USER`, `EDIT_DATE`, `EDIT_USER`, `DELETE_USER`, `DELETE_DATE`) VALUES
('0001', 'BRAC', 'DHAKA', '', 'Y', NULL, NULL, '2025-10-12', 'raju', '2025-10-12', 'raju', 'softadmin', '2025-10-15'),
('100', 'Software Admin Org', 'DHAKA', '', 'Y', NULL, NULL, NULL, NULL, '2025-10-14', 'raju', NULL, NULL),
('101', 'RS ELECTRONICS', 'CHITTAGONG', '', 'Y', NULL, NULL, NULL, NULL, '2025-10-15', 'softadmin', NULL, NULL);

--
-- Triggers `organization_info`
--
DELIMITER $$
CREATE TRIGGER `before_insert_organization` BEFORE INSERT ON `organization_info` FOR EACH ROW BEGIN
    DECLARE nextCode INT;
    DECLARE newOrgCode VARCHAR(20);

    -- Only generate if not provided manually
    IF NEW.ORG_CODE IS NULL OR NEW.ORG_CODE = '' THEN
        SELECT IFNULL(MAX(CAST(SUBSTRING(ORG_CODE, 4) AS UNSIGNED)), 0) + 1
        INTO nextCode
        FROM organization_info;

        SET newOrgCode = CONCAT(LPAD(nextCode, 4, '0'));
        SET NEW.ORG_CODE = newOrgCode;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_action_permission`
--

CREATE TABLE `user_action_permission` (
  `permission_id` int(11) NOT NULL,
  `user_type_id` int(11) NOT NULL,
  `can_insert` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_action_permission`
--

INSERT INTO `user_action_permission` (`permission_id`, `user_type_id`, `can_insert`, `can_edit`, `can_delete`) VALUES
(3, 1, 1, 1, 1),
(6, 2, 1, 1, 1),
(7, 3, 0, 0, 0),
(8, 4, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_login_info`
--

CREATE TABLE `user_login_info` (
  `USER_ID` varchar(200) NOT NULL,
  `USER_PASSWORD` varchar(200) NOT NULL,
  `EMAIL` varchar(100) DEFAULT NULL,
  `PHONE` varchar(20) DEFAULT NULL,
  `ENTRY_DATE` date DEFAULT NULL,
  `AUTHORIZED_STATUS` char(1) DEFAULT 'N',
  `USER_NAME` varchar(200) DEFAULT NULL,
  `USER_TYPE_ID` int(10) DEFAULT NULL,
  `BR_CODE` varchar(20) DEFAULT NULL,
  `ORG_CODE` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_login_info`
--

INSERT INTO `user_login_info` (`USER_ID`, `USER_PASSWORD`, `EMAIL`, `PHONE`, `ENTRY_DATE`, `AUTHORIZED_STATUS`, `USER_NAME`, `USER_TYPE_ID`, `BR_CODE`, `ORG_CODE`) VALUES
('raju', '$2y$10$l5soIuG1p9yJbJcBrJuTAuIxMwF.i.v1eheXHxvYI5RpBQTxdvCrG', 'rajucsecu@hotmail.com', '', '2025-10-15', 'Y', 'Raju Das', 1, '100-100', '100'),
('rasel', '$2y$10$h7e9mNQchs0dcLizEtpM/ejVAENkiiFKXljRBj.gv0DLOoTRj04gm', '', '', '2025-10-15', 'Y', 'rasel', 3, '101-101', '101'),
('riton', '$2y$10$629v4JKsD2Koe9QAYzGKeu.RZoo7HJv4AmePphdXyUlLe79Lz68u6', '', '', '2025-10-15', 'Y', 'Riton Das', 2, '101-101', '101'),
('sales', '$2y$10$aJK.RXRBtWI2UjGgJcH06OmIIG6/WOmZQgTIiWvvhyGHImFgZ0yp.', '', '', '2025-10-28', 'Y', 'sales', 4, '101-101', '101'),
('softadmin', '$2y$10$ry0hs3xBOzo/bji9oNQNqe9w84cvxONkZSGW/M4lewdhJUbDzX.Ma', '', '', '2025-10-15', 'Y', 'software Admin', 1, '100-100', '100');

-- --------------------------------------------------------

--
-- Table structure for table `user_menu_view_permission`
--

CREATE TABLE `user_menu_view_permission` (
  `PERMISSION_ID` varchar(200) NOT NULL,
  `USER_TYPE_ID` int(11) NOT NULL,
  `MENU_ID` int(11) NOT NULL,
  `CAN_VIEW` tinyint(1) DEFAULT 0,
  `AUTHORIZED_STATUS` varchar(1) DEFAULT NULL,
  `AUTHORIZED_USER` varchar(100) DEFAULT NULL,
  `AUTHORIZED_DATE` date DEFAULT NULL,
  `ENTRY_USER` varchar(200) NOT NULL,
  `ENTRY_DATE` date NOT NULL DEFAULT curdate(),
  `EDIT_USER` varchar(200) DEFAULT NULL,
  `EDIT_DATE` date DEFAULT NULL,
  `DELETE_USER` varchar(200) DEFAULT NULL,
  `DELETE_DATE` date DEFAULT NULL,
  `ORG_CODE` varchar(20) DEFAULT NULL,
  `BR_CODE` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_menu_view_permission`
--

INSERT INTO `user_menu_view_permission` (`PERMISSION_ID`, `USER_TYPE_ID`, `MENU_ID`, `CAN_VIEW`, `AUTHORIZED_STATUS`, `AUTHORIZED_USER`, `AUTHORIZED_DATE`, `ENTRY_USER`, `ENTRY_DATE`, `EDIT_USER`, `EDIT_DATE`, `DELETE_USER`, `DELETE_DATE`, `ORG_CODE`, `BR_CODE`) VALUES
('r001', 1, 35, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r002', 1, 34, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r003', 1, 33, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r004', 1, 1, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r007', 1, 4, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r008', 1, 5, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r009', 1, 32, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r010', 1, 37, 1, 'Y', NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r012', 1, 2, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r013', 3, 37, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r014', 3, 33, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r015', 3, 34, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r016', 3, 30, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r017', 3, 1, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r018', 3, 5, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r019', 3, 31, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r020', 3, 27, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r021', 3, 4, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r022', 3, 2, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r023', 3, 26, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r025', 3, 28, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r026', 3, 35, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r027', 3, 32, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r031', 1, 30, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r034', 1, 31, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r035', 1, 27, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r038', 1, 26, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r040', 1, 28, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r043', 2, 37, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r044', 2, 33, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r045', 2, 34, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r046', 2, 30, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r047', 2, 1, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r048', 2, 5, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r049', 2, 31, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r050', 2, 27, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r051', 2, 4, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r052', 2, 2, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r053', 2, 26, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r055', 2, 28, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r056', 2, 35, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r057', 2, 32, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r060', 1, 40, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r062', 2, 40, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r063', 3, 40, 0, NULL, NULL, NULL, 'raju', '2025-10-15', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r064', 4, 37, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r065', 4, 33, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r066', 4, 34, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r067', 4, 30, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r068', 4, 1, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r069', 4, 5, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r070', 4, 31, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r071', 4, 27, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r072', 4, 4, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r073', 4, 2, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r074', 4, 26, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r076', 4, 28, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r077', 4, 40, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r078', 4, 35, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r079', 4, 32, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r080', 1, 41, 1, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r081', 2, 41, 1, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r082', 1, 42, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100'),
('r083', 1, 43, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r084', 2, 42, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r085', 2, 43, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-28', NULL, NULL, '101', '101-101'),
('r086', 3, 41, 0, NULL, NULL, NULL, 'softadmin', '2025-10-23', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r087', 3, 42, 1, NULL, NULL, NULL, 'softadmin', '2025-10-23', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r088', 3, 43, 1, NULL, NULL, NULL, 'softadmin', '2025-10-23', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r089', 1, 44, 1, NULL, NULL, NULL, '', '0000-00-00', NULL, NULL, NULL, NULL, '100', '100-100'),
('r090', 2, 44, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-28', NULL, NULL, '101', '101-101'),
('r091', 4, 44, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r092', 4, 41, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r093', 4, 42, 1, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r094', 4, 43, 1, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r095', 3, 44, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r100', 1, 46, 1, NULL, NULL, NULL, '', '0000-00-00', NULL, NULL, NULL, NULL, '100', '100'),
('r101', 2, 46, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r102', 4, 46, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', NULL, NULL, NULL, NULL, '100', '100-100'),
('r103', 3, 46, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', NULL, NULL, NULL, NULL, '100', '100-100');

--
-- Triggers `user_menu_view_permission`
--
DELIMITER $$
CREATE TRIGGER `trg_permission_autogen` BEFORE INSERT ON `user_menu_view_permission` FOR EACH ROW BEGIN
    DECLARE last_id VARCHAR(10);
    DECLARE new_num INT;

    -- Only generate if PERMISSION_ID is NULL
    IF NEW.PERMISSION_ID IS NULL OR NEW.PERMISSION_ID = '' THEN
        -- Get the last inserted PERMISSION_ID
        SELECT PERMISSION_ID 
        INTO last_id
        FROM user_menu_view_permission
        WHERE PERMISSION_ID LIKE 'r%' 
        ORDER BY PERMISSION_ID DESC
        LIMIT 1;

        -- Generate new number
        IF last_id IS NOT NULL THEN
            SET new_num = CAST(SUBSTRING(last_id,2) AS UNSIGNED) + 1;
        ELSE
            SET new_num = 1;
        END IF;

        -- Set new PERMISSION_ID with R prefix
        SET NEW.PERMISSION_ID = CONCAT('r', LPAD(new_num, 3, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_type_info`
--

CREATE TABLE `user_type_info` (
  `USER_TYPE_ID` int(11) NOT NULL,
  `USER_TYPE_NAME` varchar(100) NOT NULL,
  `USER_TYPE_CODE` varchar(50) NOT NULL,
  `ROLE_LEVEL` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_type_info`
--

INSERT INTO `user_type_info` (`USER_TYPE_ID`, `USER_TYPE_NAME`, `USER_TYPE_CODE`, `ROLE_LEVEL`) VALUES
(1, 'SUPER ADMIN', 'SUPER_ADMIN', 1),
(2, 'ADMIN USER', 'ADMIN', 2),
(3, 'GENERAL USER', 'USER', 3),
(4, 'END USER', 'END USER', 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branch_info`
--
ALTER TABLE `branch_info`
  ADD PRIMARY KEY (`BR_CODE`),
  ADD KEY `FK_BRANCH_ORG` (`ORG_CODE`);

--
-- Indexes for table `menu_info`
--
ALTER TABLE `menu_info`
  ADD PRIMARY KEY (`MENU_ID`);

--
-- Indexes for table `organization_info`
--
ALTER TABLE `organization_info`
  ADD PRIMARY KEY (`ORG_CODE`);

--
-- Indexes for table `user_action_permission`
--
ALTER TABLE `user_action_permission`
  ADD PRIMARY KEY (`permission_id`),
  ADD KEY `fk_action_perm_user_type` (`user_type_id`);

--
-- Indexes for table `user_login_info`
--
ALTER TABLE `user_login_info`
  ADD PRIMARY KEY (`USER_ID`),
  ADD KEY `fk_user_login_user_type` (`USER_TYPE_ID`);

--
-- Indexes for table `user_menu_view_permission`
--
ALTER TABLE `user_menu_view_permission`
  ADD PRIMARY KEY (`PERMISSION_ID`),
  ADD KEY `fk_menu_perm_user_type` (`USER_TYPE_ID`),
  ADD KEY `fk_menu` (`MENU_ID`);

--
-- Indexes for table `user_type_info`
--
ALTER TABLE `user_type_info`
  ADD PRIMARY KEY (`USER_TYPE_ID`),
  ADD UNIQUE KEY `USER_TYPE_CODE` (`USER_TYPE_CODE`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menu_info`
--
ALTER TABLE `menu_info`
  MODIFY `MENU_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `user_action_permission`
--
ALTER TABLE `user_action_permission`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_type_info`
--
ALTER TABLE `user_type_info`
  MODIFY `USER_TYPE_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `branch_info`
--
ALTER TABLE `branch_info`
  ADD CONSTRAINT `FK_BRANCH_ORG` FOREIGN KEY (`ORG_CODE`) REFERENCES `organization_info` (`ORG_CODE`);

--
-- Constraints for table `user_action_permission`
--
ALTER TABLE `user_action_permission`
  ADD CONSTRAINT `fk_action_perm_user_type` FOREIGN KEY (`user_type_id`) REFERENCES `user_type_info` (`USER_TYPE_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `user_action_permission_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `user_type_info` (`USER_TYPE_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_login_info`
--
ALTER TABLE `user_login_info`
  ADD CONSTRAINT `fk_user_login_user_type` FOREIGN KEY (`USER_TYPE_ID`) REFERENCES `user_type_info` (`USER_TYPE_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `user_menu_view_permission`
--
ALTER TABLE `user_menu_view_permission`
  ADD CONSTRAINT `fk_menu` FOREIGN KEY (`MENU_ID`) REFERENCES `menu_info` (`MENU_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_menu_perm_user_type` FOREIGN KEY (`USER_TYPE_ID`) REFERENCES `user_type_info` (`USER_TYPE_ID`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
