-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2026 at 03:45 AM
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
-- Database: `datas_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `custom_forms`
--

CREATE TABLE `custom_forms` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `subheading` varchar(500) DEFAULT NULL,
  `role_superadmin` tinyint(1) NOT NULL DEFAULT 1,
  `role_admin` tinyint(1) NOT NULL DEFAULT 0,
  `role_encoder` tinyint(1) NOT NULL DEFAULT 1,
  `role_sales_rep` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_forms`
--

INSERT INTO `custom_forms` (`id`, `title`, `slug`, `subheading`, `role_superadmin`, `role_admin`, `role_encoder`, `role_sales_rep`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Non-Priority Projects', 'non-priority', 'Standard project encoding form', 1, 1, 1, 0, 1, '2026-05-28 16:14:50', '2026-05-28 16:14:50'),
(2, 'Priority Projects', 'priority', 'Priority project encoding form', 1, 1, 1, 0, 1, '2026-05-28 16:14:50', '2026-05-28 16:14:50');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(10) UNSIGNED NOT NULL,
  `country_code` varchar(10) NOT NULL DEFAULT 'PH',
  `region_code` varchar(20) DEFAULT NULL,
  `province_code` varchar(20) DEFAULT NULL,
  `city_code` varchar(20) DEFAULT NULL,
  `barangay_code` varchar(20) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('country','region','province','city','barangay') NOT NULL,
  `parent_code` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `country_code`, `region_code`, `province_code`, `city_code`, `barangay_code`, `name`, `type`, `parent_code`, `created_at`) VALUES
(1, 'PH', NULL, NULL, NULL, NULL, 'Philippines', 'country', NULL, '2026-05-28 16:14:50'),
(537, 'PH', 'NCR', NULL, NULL, NULL, 'National Capital Region (NCR)', 'region', 'PH', '2026-05-28 16:25:10'),
(538, 'PH', 'CAR', NULL, NULL, NULL, 'Cordillera Administrative Region (CAR)', 'region', 'PH', '2026-05-28 16:25:10'),
(539, 'PH', 'I', NULL, NULL, NULL, 'Ilocos Region (Region I)', 'region', 'PH', '2026-05-28 16:25:10'),
(540, 'PH', 'II', NULL, NULL, NULL, 'Cagayan Valley (Region II)', 'region', 'PH', '2026-05-28 16:25:10'),
(541, 'PH', 'III', NULL, NULL, NULL, 'Central Luzon (Region III)', 'region', 'PH', '2026-05-28 16:25:10'),
(542, 'PH', 'IV-A', NULL, NULL, NULL, 'CALABARZON (Region IV-A)', 'region', 'PH', '2026-05-28 16:25:10'),
(543, 'PH', 'IV-B', NULL, NULL, NULL, 'MIMAROPA (Region IV-B)', 'region', 'PH', '2026-05-28 16:25:10'),
(544, 'PH', 'V', NULL, NULL, NULL, 'Bicol Region (Region V)', 'region', 'PH', '2026-05-28 16:25:10'),
(545, 'PH', 'VI', NULL, NULL, NULL, 'Western Visayas (Region VI)', 'region', 'PH', '2026-05-28 16:25:10'),
(546, 'PH', 'VII', NULL, NULL, NULL, 'Central Visayas (Region VII)', 'region', 'PH', '2026-05-28 16:25:10'),
(547, 'PH', 'VIII', NULL, NULL, NULL, 'Eastern Visayas (Region VIII)', 'region', 'PH', '2026-05-28 16:25:10'),
(548, 'PH', 'IX', NULL, NULL, NULL, 'Zamboanga Peninsula (Region IX)', 'region', 'PH', '2026-05-28 16:25:10'),
(549, 'PH', 'X', NULL, NULL, NULL, 'Northern Mindanao (Region X)', 'region', 'PH', '2026-05-28 16:25:10'),
(550, 'PH', 'XI', NULL, NULL, NULL, 'Davao Region (Region XI)', 'region', 'PH', '2026-05-28 16:25:10'),
(551, 'PH', 'XII', NULL, NULL, NULL, 'SOCCSKSARGEN (Region XII)', 'region', 'PH', '2026-05-28 16:25:10'),
(552, 'PH', 'XIII', NULL, NULL, NULL, 'Caraga (Region XIII)', 'region', 'PH', '2026-05-28 16:25:10'),
(553, 'PH', 'BARMM', NULL, NULL, NULL, 'Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)', 'region', 'PH', '2026-05-28 16:25:10'),
(554, 'PH', 'NCR', NULL, 'MNL', NULL, 'Manila', 'city', 'NCR', '2026-05-28 16:25:10'),
(555, 'PH', 'NCR', NULL, 'QC', NULL, 'Quezon City', 'city', 'NCR', '2026-05-28 16:25:10'),
(556, 'PH', 'NCR', NULL, 'CAL', NULL, 'Caloocan', 'city', 'NCR', '2026-05-28 16:25:10'),
(557, 'PH', 'NCR', NULL, 'LAS', NULL, 'Las Piñas', 'city', 'NCR', '2026-05-28 16:25:10'),
(558, 'PH', 'NCR', NULL, 'MAK', NULL, 'Makati', 'city', 'NCR', '2026-05-28 16:25:10'),
(559, 'PH', 'NCR', NULL, 'MAL', NULL, 'Malabon', 'city', 'NCR', '2026-05-28 16:25:10'),
(560, 'PH', 'NCR', NULL, 'MAN', NULL, 'Mandaluyong', 'city', 'NCR', '2026-05-28 16:25:10'),
(561, 'PH', 'NCR', NULL, 'MAR', NULL, 'Marikina', 'city', 'NCR', '2026-05-28 16:25:10'),
(562, 'PH', 'NCR', NULL, 'MUN', NULL, 'Muntinlupa', 'city', 'NCR', '2026-05-28 16:25:10'),
(563, 'PH', 'NCR', NULL, 'NAV', NULL, 'Navotas', 'city', 'NCR', '2026-05-28 16:25:10'),
(564, 'PH', 'NCR', NULL, 'PAR', NULL, 'Parañaque', 'city', 'NCR', '2026-05-28 16:25:10'),
(565, 'PH', 'NCR', NULL, 'PAS', NULL, 'Pasay', 'city', 'NCR', '2026-05-28 16:25:10'),
(566, 'PH', 'NCR', NULL, 'PAT', NULL, 'Pateros', 'city', 'NCR', '2026-05-28 16:25:10'),
(567, 'PH', 'NCR', NULL, 'SJU', NULL, 'San Juan', 'city', 'NCR', '2026-05-28 16:25:10'),
(568, 'PH', 'NCR', NULL, 'TAF', NULL, 'Taguig', 'city', 'NCR', '2026-05-28 16:25:10'),
(569, 'PH', 'NCR', NULL, 'VAL', NULL, 'Valenzuela', 'city', 'NCR', '2026-05-28 16:25:10'),
(570, 'PH', 'NCR', NULL, 'PSG', NULL, 'Pasig', 'city', 'NCR', '2026-05-28 16:25:10'),
(571, 'PH', 'I', 'ILN', NULL, NULL, 'Ilocos Norte', 'province', 'I', '2026-05-28 16:25:10'),
(572, 'PH', 'I', 'ILS', NULL, NULL, 'Ilocos Sur', 'province', 'I', '2026-05-28 16:25:10'),
(573, 'PH', 'I', 'LU', NULL, NULL, 'La Union', 'province', 'I', '2026-05-28 16:25:10'),
(574, 'PH', 'I', 'PAN', NULL, NULL, 'Pangasinan', 'province', 'I', '2026-05-28 16:25:10'),
(575, 'PH', 'I', 'ILN', 'LAO', NULL, 'Laoag', 'city', 'ILN', '2026-05-28 16:25:10'),
(576, 'PH', 'I', 'ILN', 'BAT', NULL, 'Batac', 'city', 'ILN', '2026-05-28 16:25:10'),
(577, 'PH', 'I', 'ILS', 'VIG', NULL, 'Vigan', 'city', 'ILS', '2026-05-28 16:25:10'),
(578, 'PH', 'I', 'ILS', 'CAN', NULL, 'Candon', 'city', 'ILS', '2026-05-28 16:25:10'),
(579, 'PH', 'I', 'LU', 'SFE', NULL, 'San Fernando', 'city', 'LU', '2026-05-28 16:25:10'),
(580, 'PH', 'I', 'PAN', 'ALA', NULL, 'Alaminos', 'city', 'PAN', '2026-05-28 16:25:10'),
(581, 'PH', 'I', 'PAN', 'DAG', NULL, 'Dagupan', 'city', 'PAN', '2026-05-28 16:25:10'),
(582, 'PH', 'I', 'PAN', 'SCA', NULL, 'San Carlos', 'city', 'PAN', '2026-05-28 16:25:10'),
(583, 'PH', 'I', 'PAN', 'URS', NULL, 'Urdaneta', 'city', 'PAN', '2026-05-28 16:25:10'),
(584, 'PH', 'II', 'BAT', NULL, NULL, 'Batanes', 'province', 'II', '2026-05-28 16:25:10'),
(585, 'PH', 'II', 'CAG', NULL, NULL, 'Cagayan', 'province', 'II', '2026-05-28 16:25:10'),
(586, 'PH', 'II', 'ISA', NULL, NULL, 'Isabela', 'province', 'II', '2026-05-28 16:25:10'),
(587, 'PH', 'II', 'NV', NULL, NULL, 'Nueva Vizcaya', 'province', 'II', '2026-05-28 16:25:10'),
(588, 'PH', 'II', 'QUI', NULL, NULL, 'Quirino', 'province', 'II', '2026-05-28 16:25:10'),
(589, 'PH', 'II', 'CAG', 'TUG', NULL, 'Tuguegarao', 'city', 'CAG', '2026-05-28 16:25:10'),
(590, 'PH', 'II', 'ISA', 'ILA', NULL, 'Ilagan', 'city', 'ISA', '2026-05-28 16:25:10'),
(591, 'PH', 'II', 'ISA', 'CAU', NULL, 'Cauayan', 'city', 'ISA', '2026-05-28 16:25:10'),
(592, 'PH', 'II', 'ISA', 'SAN', NULL, 'Santiago', 'city', 'ISA', '2026-05-28 16:25:10'),
(593, 'PH', 'III', 'AUR', NULL, NULL, 'Aurora', 'province', 'III', '2026-05-28 16:25:10'),
(594, 'PH', 'III', 'BAT', NULL, NULL, 'Bataan', 'province', 'III', '2026-05-28 16:25:10'),
(595, 'PH', 'III', 'BUL', NULL, NULL, 'Bulacan', 'province', 'III', '2026-05-28 16:25:10'),
(596, 'PH', 'III', 'NE', NULL, NULL, 'Nueva Ecija', 'province', 'III', '2026-05-28 16:25:10'),
(597, 'PH', 'III', 'PAM', NULL, NULL, 'Pampanga', 'province', 'III', '2026-05-28 16:25:10'),
(598, 'PH', 'III', 'TAR', NULL, NULL, 'Tarlac', 'province', 'III', '2026-05-28 16:25:10'),
(599, 'PH', 'III', 'ZAM', NULL, NULL, 'Zambales', 'province', 'III', '2026-05-28 16:25:10'),
(600, 'PH', 'III', 'BAT', 'BAL', NULL, 'Balanga', 'city', 'BAT', '2026-05-28 16:25:10'),
(601, 'PH', 'III', 'BUL', 'MAL', NULL, 'Malolos', 'city', 'BUL', '2026-05-28 16:25:10'),
(602, 'PH', 'III', 'BUL', 'MER', NULL, 'Meycauayan', 'city', 'BUL', '2026-05-28 16:25:10'),
(603, 'PH', 'III', 'BUL', 'SJD', NULL, 'San Jose del Monte', 'city', 'BUL', '2026-05-28 16:25:10'),
(604, 'PH', 'III', 'NE', 'CAB', NULL, 'Cabanatuan', 'city', 'NE', '2026-05-28 16:25:10'),
(605, 'PH', 'III', 'NE', 'GAP', NULL, 'Gapan', 'city', 'NE', '2026-05-28 16:25:10'),
(606, 'PH', 'III', 'NE', 'MUN', NULL, 'Muñoz', 'city', 'NE', '2026-05-28 16:25:10'),
(607, 'PH', 'III', 'NE', 'PAL', NULL, 'Palayan', 'city', 'NE', '2026-05-28 16:25:10'),
(608, 'PH', 'III', 'NE', 'SJO', NULL, 'San Jose', 'city', 'NE', '2026-05-28 16:25:10'),
(609, 'PH', 'III', 'PAM', 'ANG', NULL, 'Angeles', 'city', 'PAM', '2026-05-28 16:25:10'),
(610, 'PH', 'III', 'PAM', 'SFE', NULL, 'San Fernando', 'city', 'PAM', '2026-05-28 16:25:10'),
(611, 'PH', 'III', 'TAR', 'TAR', NULL, 'Tarlac', 'city', 'TAR', '2026-05-28 16:25:10'),
(612, 'PH', 'III', 'ZAM', 'OLO', NULL, 'Olongapo', 'city', 'ZAM', '2026-05-28 16:25:10'),
(613, 'PH', 'IV-A', 'BAT', NULL, NULL, 'Batangas', 'province', 'IV-A', '2026-05-28 16:25:10'),
(614, 'PH', 'IV-A', 'CAV', NULL, NULL, 'Cavite', 'province', 'IV-A', '2026-05-28 16:25:10'),
(615, 'PH', 'IV-A', 'LAG', NULL, NULL, 'Laguna', 'province', 'IV-A', '2026-05-28 16:25:10'),
(616, 'PH', 'IV-A', 'QUE', NULL, NULL, 'Quezon', 'province', 'IV-A', '2026-05-28 16:25:10'),
(617, 'PH', 'IV-A', 'RIZ', NULL, NULL, 'Rizal', 'province', 'IV-A', '2026-05-28 16:25:10'),
(618, 'PH', 'IV-A', 'BAT', 'BAT', NULL, 'Batangas', 'city', 'BAT', '2026-05-28 16:25:10'),
(619, 'PH', 'IV-A', 'BAT', 'LIP', NULL, 'Lipa', 'city', 'BAT', '2026-05-28 16:25:10'),
(620, 'PH', 'IV-A', 'BAT', 'TAN', NULL, 'Tanauan', 'city', 'BAT', '2026-05-28 16:25:10'),
(621, 'PH', 'IV-A', 'CAV', 'BAC', NULL, 'Bacoor', 'city', 'CAV', '2026-05-28 16:25:10'),
(622, 'PH', 'IV-A', 'CAV', 'CAV', NULL, 'Cavite City', 'city', 'CAV', '2026-05-28 16:25:10'),
(623, 'PH', 'IV-A', 'CAV', 'DAS', NULL, 'Dasmariñas', 'city', 'CAV', '2026-05-28 16:25:10'),
(624, 'PH', 'IV-A', 'CAV', 'GMA', NULL, 'General Mariano Alvarez', 'city', 'CAV', '2026-05-28 16:25:10'),
(625, 'PH', 'IV-A', 'CAV', 'GTR', NULL, 'General Trias', 'city', 'CAV', '2026-05-28 16:25:10'),
(626, 'PH', 'IV-A', 'CAV', 'IMU', NULL, 'Imus', 'city', 'CAV', '2026-05-28 16:25:10'),
(627, 'PH', 'IV-A', 'CAV', 'TAG', NULL, 'Tagaytay', 'city', 'CAV', '2026-05-28 16:25:10'),
(628, 'PH', 'IV-A', 'CAV', 'TRE', NULL, 'Trece Martires', 'city', 'CAV', '2026-05-28 16:25:10'),
(629, 'PH', 'IV-A', 'LAG', 'BIN', NULL, 'Biñan', 'city', 'LAG', '2026-05-28 16:25:10'),
(630, 'PH', 'IV-A', 'LAG', 'CAB', NULL, 'Cabuyao', 'city', 'LAG', '2026-05-28 16:25:10'),
(631, 'PH', 'IV-A', 'LAG', 'CAL', NULL, 'Calamba', 'city', 'LAG', '2026-05-28 16:25:10'),
(632, 'PH', 'IV-A', 'LAG', 'SAN', NULL, 'San Pablo', 'city', 'LAG', '2026-05-28 16:25:10'),
(633, 'PH', 'IV-A', 'LAG', 'SPE', NULL, 'San Pedro', 'city', 'LAG', '2026-05-28 16:25:10'),
(634, 'PH', 'IV-A', 'LAG', 'STA', NULL, 'Santa Rosa', 'city', 'LAG', '2026-05-28 16:25:10'),
(635, 'PH', 'IV-A', 'QUE', 'LUC', NULL, 'Lucena', 'city', 'QUE', '2026-05-28 16:25:10'),
(636, 'PH', 'IV-A', 'QUE', 'TAY', NULL, 'Tayabas', 'city', 'QUE', '2026-05-28 16:25:10'),
(637, 'PH', 'IV-A', 'RIZ', 'ANT', NULL, 'Antipolo', 'city', 'RIZ', '2026-05-28 16:25:10'),
(638, 'PH', 'IV-B', 'MAR', NULL, NULL, 'Marinduque', 'province', 'IV-B', '2026-05-28 16:25:10'),
(639, 'PH', 'IV-B', 'OCC', NULL, NULL, 'Occidental Mindoro', 'province', 'IV-B', '2026-05-28 16:25:10'),
(640, 'PH', 'IV-B', 'ORI', NULL, NULL, 'Oriental Mindoro', 'province', 'IV-B', '2026-05-28 16:25:10'),
(641, 'PH', 'IV-B', 'PAL', NULL, NULL, 'Palawan', 'province', 'IV-B', '2026-05-28 16:25:10'),
(642, 'PH', 'IV-B', 'ROM', NULL, NULL, 'Romblon', 'province', 'IV-B', '2026-05-28 16:25:10'),
(643, 'PH', 'IV-B', 'ORI', 'CAL', NULL, 'Calapan', 'city', 'ORI', '2026-05-28 16:25:10'),
(644, 'PH', 'IV-B', 'PAL', 'PPC', NULL, 'Puerto Princesa', 'city', 'PAL', '2026-05-28 16:25:10'),
(645, 'PH', 'V', 'ALB', NULL, NULL, 'Albay', 'province', 'V', '2026-05-28 16:25:10'),
(646, 'PH', 'V', 'CAN', NULL, NULL, 'Camarines Norte', 'province', 'V', '2026-05-28 16:25:10'),
(647, 'PH', 'V', 'CAS', NULL, NULL, 'Camarines Sur', 'province', 'V', '2026-05-28 16:25:10'),
(648, 'PH', 'V', 'CAT', NULL, NULL, 'Catanduanes', 'province', 'V', '2026-05-28 16:25:10'),
(649, 'PH', 'V', 'MAS', NULL, NULL, 'Masbate', 'province', 'V', '2026-05-28 16:25:10'),
(650, 'PH', 'V', 'SOR', NULL, NULL, 'Sorsogon', 'province', 'V', '2026-05-28 16:25:10'),
(651, 'PH', 'V', 'ALB', 'LEG', NULL, 'Legazpi', 'city', 'ALB', '2026-05-28 16:25:10'),
(652, 'PH', 'V', 'ALB', 'LIG', NULL, 'Ligao', 'city', 'ALB', '2026-05-28 16:25:10'),
(653, 'PH', 'V', 'ALB', 'TAB', NULL, 'Tabaco', 'city', 'ALB', '2026-05-28 16:25:10'),
(654, 'PH', 'V', 'CAS', 'IRI', NULL, 'Iriga', 'city', 'CAS', '2026-05-28 16:25:10'),
(655, 'PH', 'V', 'CAS', 'NAG', NULL, 'Naga', 'city', 'CAS', '2026-05-28 16:25:10'),
(656, 'PH', 'V', 'MAS', 'MAS', NULL, 'Masbate', 'city', 'MAS', '2026-05-28 16:25:10'),
(657, 'PH', 'V', 'SOR', 'SOR', NULL, 'Sorsogon', 'city', 'SOR', '2026-05-28 16:25:10'),
(658, 'PH', 'VI', 'AKL', NULL, NULL, 'Aklan', 'province', 'VI', '2026-05-28 16:25:10'),
(659, 'PH', 'VI', 'ANT', NULL, NULL, 'Antique', 'province', 'VI', '2026-05-28 16:25:10'),
(660, 'PH', 'VI', 'CAP', NULL, NULL, 'Capiz', 'province', 'VI', '2026-05-28 16:25:10'),
(661, 'PH', 'VI', 'GUI', NULL, NULL, 'Guimaras', 'province', 'VI', '2026-05-28 16:25:10'),
(662, 'PH', 'VI', 'ILO', NULL, NULL, 'Iloilo', 'province', 'VI', '2026-05-28 16:25:10'),
(663, 'PH', 'VI', 'NEG', NULL, NULL, 'Negros Occidental', 'province', 'VI', '2026-05-28 16:25:10'),
(664, 'PH', 'VI', 'ILO', 'ILO', NULL, 'Iloilo', 'city', 'ILO', '2026-05-28 16:25:10'),
(665, 'PH', 'VI', 'ILO', 'PAS', NULL, 'Passi', 'city', 'ILO', '2026-05-28 16:25:10'),
(666, 'PH', 'VI', 'NEG', 'BAC', NULL, 'Bacolod', 'city', 'NEG', '2026-05-28 16:25:10'),
(667, 'PH', 'VI', 'NEG', 'BIN', NULL, 'Bago', 'city', 'NEG', '2026-05-28 16:25:10'),
(668, 'PH', 'VI', 'NEG', 'CAD', NULL, 'Cadiz', 'city', 'NEG', '2026-05-28 16:25:10'),
(669, 'PH', 'VI', 'NEG', 'EBJ', NULL, 'E.B. Magalona', 'city', 'NEG', '2026-05-28 16:25:10'),
(670, 'PH', 'VI', 'NEG', 'HIM', NULL, 'Himamaylan', 'city', 'NEG', '2026-05-28 16:25:10'),
(671, 'PH', 'VI', 'NEG', 'KAB', NULL, 'Kabankalan', 'city', 'NEG', '2026-05-28 16:25:10'),
(672, 'PH', 'VI', 'NEG', 'LAC', NULL, 'La Carlota', 'city', 'NEG', '2026-05-28 16:25:10'),
(673, 'PH', 'VI', 'NEG', 'SAG', NULL, 'Sagay', 'city', 'NEG', '2026-05-28 16:25:10'),
(674, 'PH', 'VI', 'NEG', 'SAN', NULL, 'San Carlos', 'city', 'NEG', '2026-05-28 16:25:10'),
(675, 'PH', 'VI', 'NEG', 'SIL', NULL, 'Silay', 'city', 'NEG', '2026-05-28 16:25:10'),
(676, 'PH', 'VI', 'NEG', 'SIP', NULL, 'Sipalay', 'city', 'NEG', '2026-05-28 16:25:10'),
(677, 'PH', 'VI', 'NEG', 'TAL', NULL, 'Talisay', 'city', 'NEG', '2026-05-28 16:25:10'),
(678, 'PH', 'VI', 'NEG', 'VIC', NULL, 'Victorias', 'city', 'NEG', '2026-05-28 16:25:10'),
(679, 'PH', 'VII', 'BOH', NULL, NULL, 'Bohol', 'province', 'VII', '2026-05-28 16:25:10'),
(680, 'PH', 'VII', 'CEB', NULL, NULL, 'Cebu', 'province', 'VII', '2026-05-28 16:25:10'),
(681, 'PH', 'VII', 'NEG', NULL, NULL, 'Negros Oriental', 'province', 'VII', '2026-05-28 16:25:10'),
(682, 'PH', 'VII', 'SIQ', NULL, NULL, 'Siquijor', 'province', 'VII', '2026-05-28 16:25:10'),
(683, 'PH', 'VII', 'BOH', 'TAG', NULL, 'Tagbilaran', 'city', 'BOH', '2026-05-28 16:25:10'),
(684, 'PH', 'VII', 'CEB', 'BAL', NULL, 'Bogo', 'city', 'CEB', '2026-05-28 16:25:10'),
(685, 'PH', 'VII', 'CEB', 'CAR', NULL, 'Carcar', 'city', 'CEB', '2026-05-28 16:25:10'),
(686, 'PH', 'VII', 'CEB', 'CEB', NULL, 'Cebu', 'city', 'CEB', '2026-05-28 16:25:10'),
(687, 'PH', 'VII', 'CEB', 'DAN', NULL, 'Danao', 'city', 'CEB', '2026-05-28 16:25:10'),
(688, 'PH', 'VII', 'CEB', 'LAP', NULL, 'Lapu-Lapu', 'city', 'CEB', '2026-05-28 16:25:10'),
(689, 'PH', 'VII', 'CEB', 'MAN', NULL, 'Mandaue', 'city', 'CEB', '2026-05-28 16:25:10'),
(690, 'PH', 'VII', 'CEB', 'NAG', NULL, 'Naga', 'city', 'CEB', '2026-05-28 16:25:10'),
(691, 'PH', 'VII', 'CEB', 'TAL', NULL, 'Talisay', 'city', 'CEB', '2026-05-28 16:25:10'),
(692, 'PH', 'VII', 'CEB', 'TOL', NULL, 'Toledo', 'city', 'CEB', '2026-05-28 16:25:10'),
(693, 'PH', 'VII', 'NEG', 'BAY', NULL, 'Bayawan', 'city', 'NEG', '2026-05-28 16:25:10'),
(694, 'PH', 'VII', 'NEG', 'BAS', NULL, 'Bais', 'city', 'NEG', '2026-05-28 16:25:10'),
(695, 'PH', 'VII', 'NEG', 'CAN', NULL, 'Canlaon', 'city', 'NEG', '2026-05-28 16:25:10'),
(696, 'PH', 'VII', 'NEG', 'DUM', NULL, 'Dumaguete', 'city', 'NEG', '2026-05-28 16:25:10'),
(697, 'PH', 'VII', 'NEG', 'GUA', NULL, 'Guihulngan', 'city', 'NEG', '2026-05-28 16:25:10'),
(698, 'PH', 'VII', 'NEG', 'TAL', NULL, 'Tanjay', 'city', 'NEG', '2026-05-28 16:25:10'),
(699, 'PH', 'VIII', 'BIL', NULL, NULL, 'Biliran', 'province', 'VIII', '2026-05-28 16:25:10'),
(700, 'PH', 'VIII', 'EAS', NULL, NULL, 'Eastern Samar', 'province', 'VIII', '2026-05-28 16:25:10'),
(701, 'PH', 'VIII', 'LEY', NULL, NULL, 'Leyte', 'province', 'VIII', '2026-05-28 16:25:10'),
(702, 'PH', 'VIII', 'NOR', NULL, NULL, 'Northern Samar', 'province', 'VIII', '2026-05-28 16:25:10'),
(703, 'PH', 'VIII', 'SAM', NULL, NULL, 'Samar', 'province', 'VIII', '2026-05-28 16:25:10'),
(704, 'PH', 'VIII', 'SOU', NULL, NULL, 'Southern Leyte', 'province', 'VIII', '2026-05-28 16:25:10'),
(705, 'PH', 'VIII', 'EAS', 'BON', NULL, 'Borongan', 'city', 'EAS', '2026-05-28 16:25:10'),
(706, 'PH', 'VIII', 'LEY', 'BAY', NULL, 'Baybay', 'city', 'LEY', '2026-05-28 16:25:10'),
(707, 'PH', 'VIII', 'LEY', 'ORM', NULL, 'Ormoc', 'city', 'LEY', '2026-05-28 16:25:10'),
(708, 'PH', 'VIII', 'LEY', 'TAC', NULL, 'Tacloban', 'city', 'LEY', '2026-05-28 16:25:10'),
(709, 'PH', 'VIII', 'NOR', 'CAT', NULL, 'Catarman', 'city', 'NOR', '2026-05-28 16:25:10'),
(710, 'PH', 'VIII', 'SAM', 'CAL', NULL, 'Calbayog', 'city', 'SAM', '2026-05-28 16:25:10'),
(711, 'PH', 'VIII', 'SAM', 'CAT', NULL, 'Catbalogan', 'city', 'SAM', '2026-05-28 16:25:10'),
(712, 'PH', 'VIII', 'SOU', 'MAA', NULL, 'Maasin', 'city', 'SOU', '2026-05-28 16:25:10'),
(713, 'PH', 'IX', 'ZAN', NULL, NULL, 'Zamboanga del Norte', 'province', 'IX', '2026-05-28 16:25:10'),
(714, 'PH', 'IX', 'ZAS', NULL, NULL, 'Zamboanga del Sur', 'province', 'IX', '2026-05-28 16:25:10'),
(715, 'PH', 'IX', 'ZSI', NULL, NULL, 'Zamboanga Sibugay', 'province', 'IX', '2026-05-28 16:25:10'),
(716, 'PH', 'IX', 'ZAN', 'DIP', NULL, 'Dipolog', 'city', 'ZAN', '2026-05-28 16:25:10'),
(717, 'PH', 'IX', 'ZAN', 'DAP', NULL, 'Dapitan', 'city', 'ZAN', '2026-05-28 16:25:10'),
(718, 'PH', 'IX', 'ZAS', 'PAG', NULL, 'Pagadian', 'city', 'ZAS', '2026-05-28 16:25:10'),
(719, 'PH', 'IX', 'ZAS', 'ZAM', NULL, 'Zamboanga', 'city', 'ZAS', '2026-05-28 16:25:10'),
(720, 'PH', 'IX', 'ZSI', 'IPL', NULL, 'Ipil', 'city', 'ZSI', '2026-05-28 16:25:10'),
(721, 'PH', 'X', 'BUK', NULL, NULL, 'Bukidnon', 'province', 'X', '2026-05-28 16:25:10'),
(722, 'PH', 'X', 'CAM', NULL, NULL, 'Camiguin', 'province', 'X', '2026-05-28 16:25:10'),
(723, 'PH', 'X', 'LAN', NULL, NULL, 'Lanao del Norte', 'province', 'X', '2026-05-28 16:25:10'),
(724, 'PH', 'X', 'MIS', NULL, NULL, 'Misamis Occidental', 'province', 'X', '2026-05-28 16:25:10'),
(725, 'PH', 'X', 'MOR', NULL, NULL, 'Misamis Oriental', 'province', 'X', '2026-05-28 16:25:10'),
(726, 'PH', 'X', 'BUK', 'MAL', NULL, 'Malaybalay', 'city', 'BUK', '2026-05-28 16:25:10'),
(727, 'PH', 'X', 'BUK', 'VAL', NULL, 'Valencia', 'city', 'BUK', '2026-05-28 16:25:10'),
(728, 'PH', 'X', 'LAN', 'ILI', NULL, 'Iligan', 'city', 'LAN', '2026-05-28 16:25:10'),
(729, 'PH', 'X', 'MIS', 'OZA', NULL, 'Ozamiz', 'city', 'MIS', '2026-05-28 16:25:10'),
(730, 'PH', 'X', 'MIS', 'ORO', NULL, 'Oroquieta', 'city', 'MIS', '2026-05-28 16:25:10'),
(731, 'PH', 'X', 'MIS', 'TAN', NULL, 'Tangub', 'city', 'MIS', '2026-05-28 16:25:10'),
(732, 'PH', 'X', 'MOR', 'CAG', NULL, 'Cagayan de Oro', 'city', 'MOR', '2026-05-28 16:25:10'),
(733, 'PH', 'X', 'MOR', 'GIN', NULL, 'Gingoog', 'city', 'MOR', '2026-05-28 16:25:10'),
(734, 'PH', 'XI', 'COM', NULL, NULL, 'Compostela Valley', 'province', 'XI', '2026-05-28 16:25:10'),
(735, 'PH', 'XI', 'DAO', NULL, NULL, 'Davao del Norte', 'province', 'XI', '2026-05-28 16:25:10'),
(736, 'PH', 'XI', 'DAS', NULL, NULL, 'Davao del Sur', 'province', 'XI', '2026-05-28 16:25:10'),
(737, 'PH', 'XI', 'DAV', NULL, NULL, 'Davao Oriental', 'province', 'XI', '2026-05-28 16:25:10'),
(738, 'PH', 'XI', 'COM', 'NAB', NULL, 'Nabunturan', 'city', 'COM', '2026-05-28 16:25:10'),
(739, 'PH', 'XI', 'DAO', 'PAN', NULL, 'Panabo', 'city', 'DAO', '2026-05-28 16:25:10'),
(740, 'PH', 'XI', 'DAO', 'SAM', NULL, 'Samal', 'city', 'DAO', '2026-05-28 16:25:10'),
(741, 'PH', 'XI', 'DAO', 'TAG', NULL, 'Tagum', 'city', 'DAO', '2026-05-28 16:25:10'),
(742, 'PH', 'XI', 'DAS', 'DAV', NULL, 'Davao', 'city', 'DAS', '2026-05-28 16:25:10'),
(743, 'PH', 'XI', 'DAS', 'DIJ', NULL, 'Digos', 'city', 'DAS', '2026-05-28 16:25:10'),
(744, 'PH', 'XI', 'DAV', 'MAT', NULL, 'Mati', 'city', 'DAV', '2026-05-28 16:25:10'),
(745, 'PH', 'XII', 'COT', NULL, NULL, 'Cotabato', 'province', 'XII', '2026-05-28 16:25:10'),
(746, 'PH', 'XII', 'SAR', NULL, NULL, 'Sarangani', 'province', 'XII', '2026-05-28 16:25:10'),
(747, 'PH', 'XII', 'SCO', NULL, NULL, 'South Cotabato', 'province', 'XII', '2026-05-28 16:25:10'),
(748, 'PH', 'XII', 'SUL', NULL, NULL, 'Sultan Kudarat', 'province', 'XII', '2026-05-28 16:25:10'),
(749, 'PH', 'XII', 'COT', 'KID', NULL, 'Kidapawan', 'city', 'COT', '2026-05-28 16:25:10'),
(750, 'PH', 'XII', 'SCO', 'GEN', NULL, 'General Santos', 'city', 'SCO', '2026-05-28 16:25:10'),
(751, 'PH', 'XII', 'SCO', 'KOR', NULL, 'Koronadal', 'city', 'SCO', '2026-05-28 16:25:10'),
(752, 'PH', 'XII', 'SUL', 'TAC', NULL, 'Tacurong', 'city', 'SUL', '2026-05-28 16:25:10'),
(753, 'PH', 'XIII', 'AGU', NULL, NULL, 'Agusan del Norte', 'province', 'XIII', '2026-05-28 16:25:10'),
(754, 'PH', 'XIII', 'AGS', NULL, NULL, 'Agusan del Sur', 'province', 'XIII', '2026-05-28 16:25:10'),
(755, 'PH', 'XIII', 'DIN', NULL, NULL, 'Dinagat Islands', 'province', 'XIII', '2026-05-28 16:25:10'),
(756, 'PH', 'XIII', 'SUR', NULL, NULL, 'Surigao del Norte', 'province', 'XIII', '2026-05-28 16:25:10'),
(757, 'PH', 'XIII', 'SUS', NULL, NULL, 'Surigao del Sur', 'province', 'XIII', '2026-05-28 16:25:10'),
(758, 'PH', 'XIII', 'AGU', 'BUT', NULL, 'Butuan', 'city', 'AGU', '2026-05-28 16:25:10'),
(759, 'PH', 'XIII', 'AGU', 'CAB', NULL, 'Cabadbaran', 'city', 'AGU', '2026-05-28 16:25:10'),
(760, 'PH', 'XIII', 'AGS', 'BAY', NULL, 'Bayugan', 'city', 'AGS', '2026-05-28 16:25:10'),
(761, 'PH', 'XIII', 'SUR', 'SUR', NULL, 'Surigao', 'city', 'SUR', '2026-05-28 16:25:10'),
(762, 'PH', 'XIII', 'SUS', 'BIS', NULL, 'Bislig', 'city', 'SUS', '2026-05-28 16:25:10'),
(763, 'PH', 'XIII', 'SUS', 'TAN', NULL, 'Tandag', 'city', 'SUS', '2026-05-28 16:25:10'),
(764, 'PH', 'CAR', 'ABR', NULL, NULL, 'Abra', 'province', 'CAR', '2026-05-28 16:25:10'),
(765, 'PH', 'CAR', 'APY', NULL, NULL, 'Apayao', 'province', 'CAR', '2026-05-28 16:25:10'),
(766, 'PH', 'CAR', 'BEN', NULL, NULL, 'Benguet', 'province', 'CAR', '2026-05-28 16:25:10'),
(767, 'PH', 'CAR', 'IFU', NULL, NULL, 'Ifugao', 'province', 'CAR', '2026-05-28 16:25:10'),
(768, 'PH', 'CAR', 'KAL', NULL, NULL, 'Kalinga', 'province', 'CAR', '2026-05-28 16:25:10'),
(769, 'PH', 'CAR', 'MOU', NULL, NULL, 'Mountain Province', 'province', 'CAR', '2026-05-28 16:25:10'),
(770, 'PH', 'CAR', 'BEN', 'BAG', NULL, 'Baguio', 'city', 'BEN', '2026-05-28 16:25:10'),
(771, 'PH', 'CAR', 'IFU', 'LAM', NULL, 'Lamut', 'city', 'IFU', '2026-05-28 16:25:10'),
(772, 'PH', 'CAR', 'KAL', 'TAB', NULL, 'Tabuk', 'city', 'KAL', '2026-05-28 16:25:10'),
(773, 'PH', 'BARMM', 'BAS', NULL, NULL, 'Basilan', 'province', 'BARMM', '2026-05-28 16:25:10'),
(774, 'PH', 'BARMM', 'LAN', NULL, NULL, 'Lanao del Sur', 'province', 'BARMM', '2026-05-28 16:25:10'),
(775, 'PH', 'BARMM', 'MAG', NULL, NULL, 'Maguindanao', 'province', 'BARMM', '2026-05-28 16:25:10'),
(776, 'PH', 'BARMM', 'SUL', NULL, NULL, 'Sulu', 'province', 'BARMM', '2026-05-28 16:25:10'),
(777, 'PH', 'BARMM', 'TAW', NULL, NULL, 'Tawi-Tawi', 'province', 'BARMM', '2026-05-28 16:25:10'),
(778, 'PH', 'BARMM', 'BAS', 'ISA', NULL, 'Isabela', 'city', 'BAS', '2026-05-28 16:25:10'),
(779, 'PH', 'BARMM', 'BAS', 'LAM', NULL, 'Lamitan', 'city', 'BAS', '2026-05-28 16:25:10'),
(780, 'PH', 'BARMM', 'LAN', 'MAR', NULL, 'Marawi', 'city', 'LAN', '2026-05-28 16:25:10'),
(781, 'PH', 'BARMM', 'MAG', 'COT', NULL, 'Cotabato', 'city', 'MAG', '2026-05-28 16:25:10'),
(782, 'PH', 'BARMM', 'SUL', 'JOL', NULL, 'Jolo', 'city', 'SUL', '2026-05-28 16:25:10');

-- --------------------------------------------------------

--
-- Table structure for table `priority_alerts`
--

CREATE TABLE `priority_alerts` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `alerted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(10) UNSIGNED NOT NULL,
  `contractor_id` varchar(100) DEFAULT NULL,
  `project_id` varchar(100) DEFAULT NULL,
  `contractor_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `project_name` varchar(500) NOT NULL,
  `project_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'Prospect',
  `source` varchar(50) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city_province` varchar(150) DEFAULT NULL,
  `contract_country` varchar(100) DEFAULT 'Philippines',
  `contract_region` varchar(100) DEFAULT NULL,
  `contract_province` varchar(100) DEFAULT NULL,
  `contract_city` varchar(100) DEFAULT NULL,
  `contract_barangay` varchar(100) DEFAULT NULL,
  `contract_street` varchar(255) DEFAULT NULL,
  `contract_blk_lot` varchar(100) DEFAULT NULL,
  `contract_coordinates` varchar(255) DEFAULT NULL,
  `project_country` varchar(100) DEFAULT 'Philippines',
  `project_region` varchar(100) DEFAULT NULL,
  `project_province` varchar(100) DEFAULT NULL,
  `project_city` varchar(100) DEFAULT NULL,
  `project_barangay` varchar(100) DEFAULT NULL,
  `project_street` varchar(255) DEFAULT NULL,
  `project_blk_lot` varchar(100) DEFAULT NULL,
  `project_coordinates` varchar(255) DEFAULT NULL,
  `sheet_pile_type` varchar(100) DEFAULT NULL,
  `sheet_pile_amount` decimal(18,2) DEFAULT NULL,
  `drbs` text DEFAULT NULL,
  `drbs_value` decimal(18,2) DEFAULT NULL,
  `accomplishment_rate` decimal(5,2) DEFAULT 0.00,
  `ms_plate` decimal(18,2) DEFAULT NULL,
  `angle_bars` decimal(18,2) DEFAULT NULL,
  `channel_bars` decimal(18,2) DEFAULT NULL,
  `wide_flange` decimal(18,2) DEFAULT NULL,
  `gi_bi` decimal(18,2) DEFAULT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` int(10) UNSIGNED DEFAULT NULL,
  `encoded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `contractor_id`, `project_id`, `contractor_name`, `contact_person`, `contact_number`, `project_name`, `project_value`, `status`, `source`, `publication_date`, `address`, `region`, `city_province`, `contract_country`, `contract_region`, `contract_province`, `contract_city`, `contract_barangay`, `contract_street`, `contract_blk_lot`, `contract_coordinates`, `project_country`, `project_region`, `project_province`, `project_city`, `project_barangay`, `project_street`, `project_blk_lot`, `project_coordinates`, `sheet_pile_type`, `sheet_pile_amount`, `drbs`, `drbs_value`, `accomplishment_rate`, `ms_plate`, `angle_bars`, `channel_bars`, `wide_flange`, `gi_bi`, `assigned_to`, `assigned_at`, `assigned_by`, `is_processed`, `processed_at`, `processed_by`, `encoded_by`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'ABC Construction Corp', 'Juan Dela Cruz', '+63 917 123 4567', 'Quezon City Road Expansion Project', 15000000.00, 'For Bidding', 'DPWH', '2024-02-15', NULL, 'NCR', NULL, 'Philippines', 'NCR', NULL, 'Manila', NULL, NULL, NULL, NULL, 'Philippines', 'NCR', NULL, 'Quezon City', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-05-28 16:14:50', '2026-05-28 16:14:50'),
(2, NULL, NULL, 'XYZ Infrastructure Inc', 'Maria Santos', '+63 918 987 6543', 'Laguna Bridge Construction', 25000000.00, 'For Execution', 'DPWH', '2024-02-10', NULL, 'IV-A', NULL, 'Philippines', 'IV-A', NULL, 'Bacoor', NULL, NULL, NULL, NULL, 'Philippines', 'IV-A', NULL, 'Laguna', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-05-28 16:14:50', '2026-05-28 16:14:50'),
(3, 'gdfh', '12345', 'fasfsa', 'fasfasf', '513515', 'TAWI-TAWI SPORTS DEVELOPMENT COMPLEX - PHASE 1', 2347457.00, 'For Execution', 'DPWH', '2026-06-05', NULL, 'VIII', 'TAC', 'PH', 'VIII', 'LEY', 'TAC', NULL, NULL, NULL, NULL, 'PH', 'CAR', 'BEN', 'BAG', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-05-28 16:26:27', '2026-05-28 16:26:27'),
(4, NULL, '123456789', '7JL HARDWARE & CONSTRUCTION SUPPLIES', 'Joel F. Bigno', '63 36 520 2802 / 63 915 971 0071/ (036) 520 2902', 'ACADEMIC BUILDING (4 CLASSROOMS) FOR SCHOOL - NEW - SINGLE STOREY', 199999.00, 'For Execution', 'BCI', '2026-05-28', NULL, 'V', 'SOR', 'PH', 'V', 'SOR', 'SOR', 'lubigan Sr.', NULL, NULL, NULL, 'PH', 'IX', 'ZAN', 'DAP', 'Brgy Tanza Roxas City', 'Joel F Bigno Building', 'Joel F Bigno Building', 'Joel F Bigno Building, Brgy Tanza Roxas City , Capiz 5800 , Philippines', NULL, 109999.00, '109999', NULL, 0.00, 10999999.00, 10099999.00, 10999999.00, 1099999999999.00, 10999999999999.00, NULL, NULL, NULL, 0, NULL, NULL, 4, '2026-05-28 16:57:37', '2026-05-28 16:57:37'),
(5, 'dasdasd', '35252', 'D.C. SANDIL CONSTRUCTION & REALTY DEVELOPMENT INC.', 'MR. TRISTAN JARVIS M. TOLENTINO', '36252136236t', 'TAWI-TAWI SPORTS DEVELOPMENT COMPLEX - PHASE 1', 3264621356.00, 'For Execution', 'BCI', '2026-05-29', NULL, 'BARMM', 'LAM', 'PH', 'BARMM', 'BAS', 'LAM', NULL, NULL, NULL, NULL, 'PH', 'VII', 'NEG', 'CAN', NULL, NULL, NULL, NULL, NULL, 5235235623.00, '253523', NULL, 0.00, 2352352.00, 52352352.00, 3523523523.00, 523523525.00, 2523525.00, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-05-29 15:41:10', '2026-05-29 15:41:10'),
(6, '12312312', NULL, 'D.C. SANDIL CONSTRUCTION & REALTY DEVELOPMENT INC.', '\"Engr Dennis C. Sandil \"', '\"63 2 8724 9288 \"', 'BACOLOD SANITARY LANDFILL (CELL NO. 5)', 440011814.49, 'For Execution', 'DPWH', '2026-06-02', NULL, 'VI', 'BAC', 'PH', 'VI', 'NEG', 'BAC', 'Felisa', NULL, NULL, NULL, 'PH', 'VI', 'NEG', 'BAC', NULL, NULL, NULL, NULL, NULL, NULL, '5200000', NULL, 0.00, NULL, 900000.00, 1300000.00, 2500000.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, 4, '2026-06-01 09:06:40', '2026-06-01 09:06:40');

-- --------------------------------------------------------

--
-- Table structure for table `project_images`
--

CREATE TABLE `project_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_tracking`
--

CREATE TABLE `sales_tracking` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `sales_rep_id` int(10) UNSIGNED NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Assigned',
  `first_contact_date` date DEFAULT NULL,
  `last_contact_date` date DEFAULT NULL,
  `next_followup_date` date DEFAULT NULL,
  `contact_method` varchar(50) DEFAULT NULL,
  `client_response` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `probability_percentage` decimal(5,2) DEFAULT 0.00,
  `expected_close_date` date DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL DEFAULT '',
  `password_hash` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','encoder','sales_rep') NOT NULL DEFAULT 'encoder',
  `branch` varchar(100) DEFAULT NULL,
  `totp_secret` varchar(64) DEFAULT NULL,
  `reset_requested` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `full_name`, `password_hash`, `role`, `branch`, `totp_secret`, `reset_requested`, `created_at`, `updated_at`) VALUES
(1, 'admin@tdtpowersteel.com', 'System Administrator', '$2y$12$Tj.fscI5lyErHx.JRysj/OLgbWVQFSkh2000qKYPCRK2HqEJuH82C', 'superadmin', NULL, NULL, 0, '2026-05-28 16:14:50', '2026-06-01 09:20:55'),
(2, 'encoder@tdtpowersteel.com', 'Sample Encoder', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'encoder', NULL, NULL, 0, '2026-05-28 16:14:50', '2026-05-28 16:14:50'),
(3, 'sales@tdtpowersteel.com', 'Sample Sales Rep', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sales_rep', 'Manila Branch', NULL, 0, '2026-05-28 16:14:50', '2026-05-28 16:14:50'),
(4, 'akotosijek@gmail.com', 'Jaderick Austria', '$2y$12$PZHi8YaYKNDvihgoP1gznOT7AdTn3LLeNhknBHKy1ugaVGF4kY6yq', 'encoder', NULL, NULL, 0, '2026-05-28 16:48:27', '2026-06-01 09:00:53');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_activity` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `created_at`, `last_activity`) VALUES
(1, 4, 'udmfio06kqfjh0794q4mf28ngh', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:49:04', '2026-05-28 16:49:04'),
(2, 1, 'fan6p2s4gklg78ha6821j9nkut', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 17:22:45', '2026-05-28 17:22:45'),
(3, 1, '7aup1ahjv9f0jt6p3la2kvv2t1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 08:23:11', '2026-05-29 08:23:11'),
(4, 1, 't6phc5oboljggqmmf7n19qqq4k', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 08:23:16', '2026-05-29 08:23:16'),
(5, 1, 'nqte7b89kn0imfpjbm3lcmmqel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 15:39:01', '2026-05-29 15:39:01'),
(6, 1, 'tciik53vssk44e5l92hpj7hu33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 08:59:56', '2026-06-01 08:59:56'),
(7, 1, '77hjshn9s1u7ui1bbeh6uchrq2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:00:02', '2026-06-01 09:00:02'),
(8, 4, 'mpnjnded672lt3f4pl0u8cjcka', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:00:53', '2026-06-01 09:00:53'),
(9, 1, 'fomjiibve4n21lquhvv1lov0tr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:20:55', '2026-06-01 09:20:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `custom_forms`
--
ALTER TABLE `custom_forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_roles` (`role_superadmin`,`role_admin`,`role_encoder`,`role_sales_rep`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_country` (`country_code`),
  ADD KEY `idx_region` (`region_code`),
  ADD KEY `idx_province` (`province_code`),
  ADD KEY `idx_city` (`city_code`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_parent` (`parent_code`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `priority_alerts`
--
ALTER TABLE `priority_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_alerted_at` (`alerted_at`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_contractor_id` (`contractor_id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_region` (`region`),
  ADD KEY `idx_contract_region` (`contract_region`),
  ADD KEY `idx_project_region` (`project_region`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_is_processed` (`is_processed`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_encoded_by` (`encoded_by`),
  ADD KEY `idx_source` (`source`),
  ADD KEY `idx_publication_date` (`publication_date`);

--
-- Indexes for table `project_images`
--
ALTER TABLE `project_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`);

--
-- Indexes for table `sales_tracking`
--
ALTER TABLE `sales_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_sales_rep` (`project_id`,`sales_rep_id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_sales_rep_id` (`sales_rep_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_next_followup` (`next_followup_date`),
  ADD KEY `idx_expected_close` (`expected_close_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_branch` (`branch`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `custom_forms`
--
ALTER TABLE `custom_forms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=783;

--
-- AUTO_INCREMENT for table `priority_alerts`
--
ALTER TABLE `priority_alerts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `project_images`
--
ALTER TABLE `project_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_tracking`
--
ALTER TABLE `sales_tracking`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `custom_forms`
--
ALTER TABLE `custom_forms`
  ADD CONSTRAINT `custom_forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `priority_alerts`
--
ALTER TABLE `priority_alerts`
  ADD CONSTRAINT `priority_alerts_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`encoded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projects_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projects_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_images`
--
ALTER TABLE `project_images`
  ADD CONSTRAINT `project_images_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_tracking`
--
ALTER TABLE `sales_tracking`
  ADD CONSTRAINT `sales_tracking_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_tracking_ibfk_2` FOREIGN KEY (`sales_rep_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_tracking_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
