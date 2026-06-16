-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2026 at 08:47 AM
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
(782, 'PH', 'BARMM', 'SUL', 'JOL', NULL, 'Jolo', 'city', 'SUL', '2026-05-28 16:25:10'),
(783, 'PH', NULL, NULL, NULL, NULL, 'Philippines', 'country', NULL, '2026-06-02 08:44:57'),
(784, 'US', NULL, NULL, NULL, NULL, 'United States', 'country', NULL, '2026-06-02 08:44:57'),
(785, 'CA', NULL, NULL, NULL, NULL, 'Canada', 'country', NULL, '2026-06-02 08:44:57'),
(786, 'GB', NULL, NULL, NULL, NULL, 'United Kingdom', 'country', NULL, '2026-06-02 08:44:57'),
(787, 'AU', NULL, NULL, NULL, NULL, 'Australia', 'country', NULL, '2026-06-02 08:44:57'),
(788, 'JP', NULL, NULL, NULL, NULL, 'Japan', 'country', NULL, '2026-06-02 08:44:57'),
(789, 'KR', NULL, NULL, NULL, NULL, 'South Korea', 'country', NULL, '2026-06-02 08:44:57'),
(790, 'CN', NULL, NULL, NULL, NULL, 'China', 'country', NULL, '2026-06-02 08:44:57'),
(791, 'IN', NULL, NULL, NULL, NULL, 'India', 'country', NULL, '2026-06-02 08:44:57'),
(792, 'ID', NULL, NULL, NULL, NULL, 'Indonesia', 'country', NULL, '2026-06-02 08:44:57'),
(793, 'TH', NULL, NULL, NULL, NULL, 'Thailand', 'country', NULL, '2026-06-02 08:44:57'),
(794, 'VN', NULL, NULL, NULL, NULL, 'Vietnam', 'country', NULL, '2026-06-02 08:44:57'),
(795, 'MY', NULL, NULL, NULL, NULL, 'Malaysia', 'country', NULL, '2026-06-02 08:44:57'),
(796, 'SG', NULL, NULL, NULL, NULL, 'Singapore', 'country', NULL, '2026-06-02 08:44:57'),
(797, 'HK', NULL, NULL, NULL, NULL, 'Hong Kong', 'country', NULL, '2026-06-02 08:44:57'),
(798, 'TW', NULL, NULL, NULL, NULL, 'Taiwan', 'country', NULL, '2026-06-02 08:44:57'),
(799, 'DE', NULL, NULL, NULL, NULL, 'Germany', 'country', NULL, '2026-06-02 08:44:57'),
(800, 'FR', NULL, NULL, NULL, NULL, 'France', 'country', NULL, '2026-06-02 08:44:57'),
(801, 'IT', NULL, NULL, NULL, NULL, 'Italy', 'country', NULL, '2026-06-02 08:44:57'),
(802, 'ES', NULL, NULL, NULL, NULL, 'Spain', 'country', NULL, '2026-06-02 08:44:57'),
(803, 'NL', NULL, NULL, NULL, NULL, 'Netherlands', 'country', NULL, '2026-06-02 08:44:57'),
(804, 'BE', NULL, NULL, NULL, NULL, 'Belgium', 'country', NULL, '2026-06-02 08:44:57'),
(805, 'CH', NULL, NULL, NULL, NULL, 'Switzerland', 'country', NULL, '2026-06-02 08:44:57'),
(806, 'AT', NULL, NULL, NULL, NULL, 'Austria', 'country', NULL, '2026-06-02 08:44:57'),
(807, 'SE', NULL, NULL, NULL, NULL, 'Sweden', 'country', NULL, '2026-06-02 08:44:57'),
(808, 'NO', NULL, NULL, NULL, NULL, 'Norway', 'country', NULL, '2026-06-02 08:44:57'),
(809, 'DK', NULL, NULL, NULL, NULL, 'Denmark', 'country', NULL, '2026-06-02 08:44:57'),
(810, 'FI', NULL, NULL, NULL, NULL, 'Finland', 'country', NULL, '2026-06-02 08:44:57'),
(811, 'PL', NULL, NULL, NULL, NULL, 'Poland', 'country', NULL, '2026-06-02 08:44:57'),
(812, 'CZ', NULL, NULL, NULL, NULL, 'Czech Republic', 'country', NULL, '2026-06-02 08:44:57'),
(813, 'HU', NULL, NULL, NULL, NULL, 'Hungary', 'country', NULL, '2026-06-02 08:44:57'),
(814, 'RO', NULL, NULL, NULL, NULL, 'Romania', 'country', NULL, '2026-06-02 08:44:57'),
(815, 'BG', NULL, NULL, NULL, NULL, 'Bulgaria', 'country', NULL, '2026-06-02 08:44:57'),
(816, 'GR', NULL, NULL, NULL, NULL, 'Greece', 'country', NULL, '2026-06-02 08:44:57'),
(817, 'PT', NULL, NULL, NULL, NULL, 'Portugal', 'country', NULL, '2026-06-02 08:44:57'),
(818, 'IE', NULL, NULL, NULL, NULL, 'Ireland', 'country', NULL, '2026-06-02 08:44:57'),
(819, 'RU', NULL, NULL, NULL, NULL, 'Russia', 'country', NULL, '2026-06-02 08:44:57'),
(820, 'MX', NULL, NULL, NULL, NULL, 'Mexico', 'country', NULL, '2026-06-02 08:44:57'),
(821, 'BR', NULL, NULL, NULL, NULL, 'Brazil', 'country', NULL, '2026-06-02 08:44:57'),
(822, 'AR', NULL, NULL, NULL, NULL, 'Argentina', 'country', NULL, '2026-06-02 08:44:57'),
(823, 'CL', NULL, NULL, NULL, NULL, 'Chile', 'country', NULL, '2026-06-02 08:44:57'),
(824, 'CO', NULL, NULL, NULL, NULL, 'Colombia', 'country', NULL, '2026-06-02 08:44:57'),
(825, 'PE', NULL, NULL, NULL, NULL, 'Peru', 'country', NULL, '2026-06-02 08:44:57'),
(826, 'VE', NULL, NULL, NULL, NULL, 'Venezuela', 'country', NULL, '2026-06-02 08:44:57'),
(827, 'EC', NULL, NULL, NULL, NULL, 'Ecuador', 'country', NULL, '2026-06-02 08:44:57'),
(828, 'UY', NULL, NULL, NULL, NULL, 'Uruguay', 'country', NULL, '2026-06-02 08:44:57'),
(829, 'PY', NULL, NULL, NULL, NULL, 'Paraguay', 'country', NULL, '2026-06-02 08:44:57'),
(830, 'BO', NULL, NULL, NULL, NULL, 'Bolivia', 'country', NULL, '2026-06-02 08:44:57'),
(831, 'ZA', NULL, NULL, NULL, NULL, 'South Africa', 'country', NULL, '2026-06-02 08:44:57'),
(832, 'NG', NULL, NULL, NULL, NULL, 'Nigeria', 'country', NULL, '2026-06-02 08:44:57'),
(833, 'EG', NULL, NULL, NULL, NULL, 'Egypt', 'country', NULL, '2026-06-02 08:44:57'),
(834, 'KE', NULL, NULL, NULL, NULL, 'Kenya', 'country', NULL, '2026-06-02 08:44:57'),
(835, 'MA', NULL, NULL, NULL, NULL, 'Morocco', 'country', NULL, '2026-06-02 08:44:57'),
(836, 'GH', NULL, NULL, NULL, NULL, 'Ghana', 'country', NULL, '2026-06-02 08:44:57'),
(837, 'ET', NULL, NULL, NULL, NULL, 'Ethiopia', 'country', NULL, '2026-06-02 08:44:57'),
(838, 'TZ', NULL, NULL, NULL, NULL, 'Tanzania', 'country', NULL, '2026-06-02 08:44:57'),
(839, 'UG', NULL, NULL, NULL, NULL, 'Uganda', 'country', NULL, '2026-06-02 08:44:57'),
(840, 'AE', NULL, NULL, NULL, NULL, 'United Arab Emirates', 'country', NULL, '2026-06-02 08:44:57'),
(841, 'SA', NULL, NULL, NULL, NULL, 'Saudi Arabia', 'country', NULL, '2026-06-02 08:44:57'),
(842, 'QA', NULL, NULL, NULL, NULL, 'Qatar', 'country', NULL, '2026-06-02 08:44:57'),
(843, 'KW', NULL, NULL, NULL, NULL, 'Kuwait', 'country', NULL, '2026-06-02 08:44:57'),
(844, 'BH', NULL, NULL, NULL, NULL, 'Bahrain', 'country', NULL, '2026-06-02 08:44:57'),
(845, 'OM', NULL, NULL, NULL, NULL, 'Oman', 'country', NULL, '2026-06-02 08:44:57'),
(846, 'JO', NULL, NULL, NULL, NULL, 'Jordan', 'country', NULL, '2026-06-02 08:44:57'),
(847, 'LB', NULL, NULL, NULL, NULL, 'Lebanon', 'country', NULL, '2026-06-02 08:44:57'),
(848, 'IL', NULL, NULL, NULL, NULL, 'Israel', 'country', NULL, '2026-06-02 08:44:57'),
(849, 'TR', NULL, NULL, NULL, NULL, 'Turkey', 'country', NULL, '2026-06-02 08:44:57'),
(850, 'IR', NULL, NULL, NULL, NULL, 'Iran', 'country', NULL, '2026-06-02 08:44:57'),
(851, 'IQ', NULL, NULL, NULL, NULL, 'Iraq', 'country', NULL, '2026-06-02 08:44:57'),
(852, 'US', 'AL', NULL, NULL, NULL, 'Alabama', 'region', 'US', '2026-06-02 08:44:57'),
(853, 'US', 'AK', NULL, NULL, NULL, 'Alaska', 'region', 'US', '2026-06-02 08:44:57'),
(854, 'US', 'AZ', NULL, NULL, NULL, 'Arizona', 'region', 'US', '2026-06-02 08:44:57'),
(855, 'US', 'AR', NULL, NULL, NULL, 'Arkansas', 'region', 'US', '2026-06-02 08:44:57'),
(856, 'US', 'CA', NULL, NULL, NULL, 'California', 'region', 'US', '2026-06-02 08:44:57'),
(857, 'US', 'CO', NULL, NULL, NULL, 'Colorado', 'region', 'US', '2026-06-02 08:44:57'),
(858, 'US', 'CT', NULL, NULL, NULL, 'Connecticut', 'region', 'US', '2026-06-02 08:44:57'),
(859, 'US', 'DE', NULL, NULL, NULL, 'Delaware', 'region', 'US', '2026-06-02 08:44:57'),
(860, 'US', 'FL', NULL, NULL, NULL, 'Florida', 'region', 'US', '2026-06-02 08:44:57'),
(861, 'US', 'GA', NULL, NULL, NULL, 'Georgia', 'region', 'US', '2026-06-02 08:44:57'),
(862, 'US', 'HI', NULL, NULL, NULL, 'Hawaii', 'region', 'US', '2026-06-02 08:44:57'),
(863, 'US', 'ID', NULL, NULL, NULL, 'Idaho', 'region', 'US', '2026-06-02 08:44:57'),
(864, 'US', 'IL', NULL, NULL, NULL, 'Illinois', 'region', 'US', '2026-06-02 08:44:57'),
(865, 'US', 'IN', NULL, NULL, NULL, 'Indiana', 'region', 'US', '2026-06-02 08:44:57'),
(866, 'US', 'IA', NULL, NULL, NULL, 'Iowa', 'region', 'US', '2026-06-02 08:44:57'),
(867, 'US', 'KS', NULL, NULL, NULL, 'Kansas', 'region', 'US', '2026-06-02 08:44:57'),
(868, 'US', 'KY', NULL, NULL, NULL, 'Kentucky', 'region', 'US', '2026-06-02 08:44:57'),
(869, 'US', 'LA', NULL, NULL, NULL, 'Louisiana', 'region', 'US', '2026-06-02 08:44:57'),
(870, 'US', 'ME', NULL, NULL, NULL, 'Maine', 'region', 'US', '2026-06-02 08:44:57'),
(871, 'US', 'MD', NULL, NULL, NULL, 'Maryland', 'region', 'US', '2026-06-02 08:44:57'),
(872, 'US', 'MA', NULL, NULL, NULL, 'Massachusetts', 'region', 'US', '2026-06-02 08:44:57'),
(873, 'US', 'MI', NULL, NULL, NULL, 'Michigan', 'region', 'US', '2026-06-02 08:44:57'),
(874, 'US', 'MN', NULL, NULL, NULL, 'Minnesota', 'region', 'US', '2026-06-02 08:44:57'),
(875, 'US', 'MS', NULL, NULL, NULL, 'Mississippi', 'region', 'US', '2026-06-02 08:44:57'),
(876, 'US', 'MO', NULL, NULL, NULL, 'Missouri', 'region', 'US', '2026-06-02 08:44:57'),
(877, 'US', 'MT', NULL, NULL, NULL, 'Montana', 'region', 'US', '2026-06-02 08:44:57'),
(878, 'US', 'NE', NULL, NULL, NULL, 'Nebraska', 'region', 'US', '2026-06-02 08:44:57'),
(879, 'US', 'NV', NULL, NULL, NULL, 'Nevada', 'region', 'US', '2026-06-02 08:44:57'),
(880, 'US', 'NH', NULL, NULL, NULL, 'New Hampshire', 'region', 'US', '2026-06-02 08:44:57'),
(881, 'US', 'NJ', NULL, NULL, NULL, 'New Jersey', 'region', 'US', '2026-06-02 08:44:57'),
(882, 'US', 'NM', NULL, NULL, NULL, 'New Mexico', 'region', 'US', '2026-06-02 08:44:57'),
(883, 'US', 'NY', NULL, NULL, NULL, 'New York', 'region', 'US', '2026-06-02 08:44:57'),
(884, 'US', 'NC', NULL, NULL, NULL, 'North Carolina', 'region', 'US', '2026-06-02 08:44:57'),
(885, 'US', 'ND', NULL, NULL, NULL, 'North Dakota', 'region', 'US', '2026-06-02 08:44:57'),
(886, 'US', 'OH', NULL, NULL, NULL, 'Ohio', 'region', 'US', '2026-06-02 08:44:57'),
(887, 'US', 'OK', NULL, NULL, NULL, 'Oklahoma', 'region', 'US', '2026-06-02 08:44:57'),
(888, 'US', 'OR', NULL, NULL, NULL, 'Oregon', 'region', 'US', '2026-06-02 08:44:57'),
(889, 'US', 'PA', NULL, NULL, NULL, 'Pennsylvania', 'region', 'US', '2026-06-02 08:44:57'),
(890, 'US', 'RI', NULL, NULL, NULL, 'Rhode Island', 'region', 'US', '2026-06-02 08:44:57'),
(891, 'US', 'SC', NULL, NULL, NULL, 'South Carolina', 'region', 'US', '2026-06-02 08:44:57'),
(892, 'US', 'SD', NULL, NULL, NULL, 'South Dakota', 'region', 'US', '2026-06-02 08:44:57'),
(893, 'US', 'TN', NULL, NULL, NULL, 'Tennessee', 'region', 'US', '2026-06-02 08:44:57'),
(894, 'US', 'TX', NULL, NULL, NULL, 'Texas', 'region', 'US', '2026-06-02 08:44:57'),
(895, 'US', 'UT', NULL, NULL, NULL, 'Utah', 'region', 'US', '2026-06-02 08:44:57'),
(896, 'US', 'VT', NULL, NULL, NULL, 'Vermont', 'region', 'US', '2026-06-02 08:44:57'),
(897, 'US', 'VA', NULL, NULL, NULL, 'Virginia', 'region', 'US', '2026-06-02 08:44:57'),
(898, 'US', 'WA', NULL, NULL, NULL, 'Washington', 'region', 'US', '2026-06-02 08:44:57'),
(899, 'US', 'WV', NULL, NULL, NULL, 'West Virginia', 'region', 'US', '2026-06-02 08:44:57'),
(900, 'US', 'WI', NULL, NULL, NULL, 'Wisconsin', 'region', 'US', '2026-06-02 08:44:57'),
(901, 'US', 'WY', NULL, NULL, NULL, 'Wyoming', 'region', 'US', '2026-06-02 08:44:57'),
(902, 'US', 'DC', NULL, NULL, NULL, 'District of Columbia', 'region', 'US', '2026-06-02 08:44:57'),
(903, 'US', 'CA', NULL, 'LA', NULL, 'Los Angeles', 'city', 'CA', '2026-06-02 08:44:57'),
(904, 'US', 'CA', NULL, 'SF', NULL, 'San Francisco', 'city', 'CA', '2026-06-02 08:44:57'),
(905, 'US', 'CA', NULL, 'SD', NULL, 'San Diego', 'city', 'CA', '2026-06-02 08:44:57'),
(906, 'US', 'CA', NULL, 'SJ', NULL, 'San Jose', 'city', 'CA', '2026-06-02 08:44:57'),
(907, 'US', 'CA', NULL, 'SAC', NULL, 'Sacramento', 'city', 'CA', '2026-06-02 08:44:57'),
(908, 'US', 'CA', NULL, 'OAK', NULL, 'Oakland', 'city', 'CA', '2026-06-02 08:44:57'),
(909, 'US', 'NY', NULL, 'NYC', NULL, 'New York City', 'city', 'NY', '2026-06-02 08:44:57'),
(910, 'US', 'NY', NULL, 'BUF', NULL, 'Buffalo', 'city', 'NY', '2026-06-02 08:44:57'),
(911, 'US', 'NY', NULL, 'ROC', NULL, 'Rochester', 'city', 'NY', '2026-06-02 08:44:57'),
(912, 'US', 'NY', NULL, 'SYR', NULL, 'Syracuse', 'city', 'NY', '2026-06-02 08:44:57'),
(913, 'US', 'NY', NULL, 'ALB', NULL, 'Albany', 'city', 'NY', '2026-06-02 08:44:57'),
(914, 'US', 'TX', NULL, 'HOU', NULL, 'Houston', 'city', 'TX', '2026-06-02 08:44:57'),
(915, 'US', 'TX', NULL, 'DAL', NULL, 'Dallas', 'city', 'TX', '2026-06-02 08:44:57'),
(916, 'US', 'TX', NULL, 'SA', NULL, 'San Antonio', 'city', 'TX', '2026-06-02 08:44:57'),
(917, 'US', 'TX', NULL, 'AUS', NULL, 'Austin', 'city', 'TX', '2026-06-02 08:44:57'),
(918, 'US', 'TX', NULL, 'FW', NULL, 'Fort Worth', 'city', 'TX', '2026-06-02 08:44:57'),
(919, 'US', 'FL', NULL, 'MIA', NULL, 'Miami', 'city', 'FL', '2026-06-02 08:44:57'),
(920, 'US', 'FL', NULL, 'JAX', NULL, 'Jacksonville', 'city', 'FL', '2026-06-02 08:44:57'),
(921, 'US', 'FL', NULL, 'TB', NULL, 'Tampa', 'city', 'FL', '2026-06-02 08:44:57'),
(922, 'US', 'FL', NULL, 'ORL', NULL, 'Orlando', 'city', 'FL', '2026-06-02 08:44:57'),
(923, 'US', 'FL', NULL, 'TLH', NULL, 'Tallahassee', 'city', 'FL', '2026-06-02 08:44:57'),
(924, 'US', 'IL', NULL, 'CHI', NULL, 'Chicago', 'city', 'IL', '2026-06-02 08:44:57'),
(925, 'US', 'IL', NULL, 'SPR', NULL, 'Springfield', 'city', 'IL', '2026-06-02 08:44:57'),
(926, 'US', 'IL', NULL, 'ROC', NULL, 'Rockford', 'city', 'IL', '2026-06-02 08:44:57'),
(927, 'US', 'WA', NULL, 'SEA', NULL, 'Seattle', 'city', 'WA', '2026-06-02 08:44:57'),
(928, 'US', 'WA', NULL, 'SPO', NULL, 'Spokane', 'city', 'WA', '2026-06-02 08:44:57'),
(929, 'US', 'OR', NULL, 'POR', NULL, 'Portland', 'city', 'OR', '2026-06-02 08:44:57'),
(930, 'US', 'CO', NULL, 'DEN', NULL, 'Denver', 'city', 'CO', '2026-06-02 08:44:57'),
(931, 'US', 'AZ', NULL, 'PHX', NULL, 'Phoenix', 'city', 'AZ', '2026-06-02 08:44:57'),
(932, 'US', 'NV', NULL, 'LV', NULL, 'Las Vegas', 'city', 'NV', '2026-06-02 08:44:57'),
(933, 'US', 'MA', NULL, 'BOS', NULL, 'Boston', 'city', 'MA', '2026-06-02 08:44:57'),
(934, 'US', 'PA', NULL, 'PHI', NULL, 'Philadelphia', 'city', 'PA', '2026-06-02 08:44:57'),
(935, 'US', 'MI', NULL, 'DET', NULL, 'Detroit', 'city', 'MI', '2026-06-02 08:44:57'),
(936, 'US', 'OH', NULL, 'CLE', NULL, 'Cleveland', 'city', 'OH', '2026-06-02 08:44:57'),
(937, 'US', 'GA', NULL, 'ATL', NULL, 'Atlanta', 'city', 'GA', '2026-06-02 08:44:57'),
(938, 'US', 'DC', NULL, 'WAS', NULL, 'Washington', 'city', 'DC', '2026-06-02 08:44:57'),
(939, 'CA', 'AB', NULL, NULL, NULL, 'Alberta', 'region', 'CA', '2026-06-02 08:44:58'),
(940, 'CA', 'BC', NULL, NULL, NULL, 'British Columbia', 'region', 'CA', '2026-06-02 08:44:58'),
(941, 'CA', 'MB', NULL, NULL, NULL, 'Manitoba', 'region', 'CA', '2026-06-02 08:44:58'),
(942, 'CA', 'NB', NULL, NULL, NULL, 'New Brunswick', 'region', 'CA', '2026-06-02 08:44:58'),
(943, 'CA', 'NL', NULL, NULL, NULL, 'Newfoundland and Labrador', 'region', 'CA', '2026-06-02 08:44:58'),
(944, 'CA', 'NS', NULL, NULL, NULL, 'Nova Scotia', 'region', 'CA', '2026-06-02 08:44:58'),
(945, 'CA', 'ON', NULL, NULL, NULL, 'Ontario', 'region', 'CA', '2026-06-02 08:44:58'),
(946, 'CA', 'PE', NULL, NULL, NULL, 'Prince Edward Island', 'region', 'CA', '2026-06-02 08:44:58'),
(947, 'CA', 'QC', NULL, NULL, NULL, 'Quebec', 'region', 'CA', '2026-06-02 08:44:58'),
(948, 'CA', 'SK', NULL, NULL, NULL, 'Saskatchewan', 'region', 'CA', '2026-06-02 08:44:58'),
(949, 'CA', 'NT', NULL, NULL, NULL, 'Northwest Territories', 'region', 'CA', '2026-06-02 08:44:58'),
(950, 'CA', 'NU', NULL, NULL, NULL, 'Nunavut', 'region', 'CA', '2026-06-02 08:44:58'),
(951, 'CA', 'YT', NULL, NULL, NULL, 'Yukon', 'region', 'CA', '2026-06-02 08:44:58'),
(952, 'CA', 'ON', NULL, 'TOR', NULL, 'Toronto', 'city', 'ON', '2026-06-02 08:44:58'),
(953, 'CA', 'ON', NULL, 'OTT', NULL, 'Ottawa', 'city', 'ON', '2026-06-02 08:44:58'),
(954, 'CA', 'ON', NULL, 'HAM', NULL, 'Hamilton', 'city', 'ON', '2026-06-02 08:44:58'),
(955, 'CA', 'QC', NULL, 'MTL', NULL, 'Montreal', 'city', 'QC', '2026-06-02 08:44:58'),
(956, 'CA', 'QC', NULL, 'QUE', NULL, 'Quebec City', 'city', 'QC', '2026-06-02 08:44:58'),
(957, 'CA', 'BC', NULL, 'VAN', NULL, 'Vancouver', 'city', 'BC', '2026-06-02 08:44:58'),
(958, 'CA', 'BC', NULL, 'VIC', NULL, 'Victoria', 'city', 'BC', '2026-06-02 08:44:58'),
(959, 'CA', 'AB', NULL, 'CAL', NULL, 'Calgary', 'city', 'AB', '2026-06-02 08:44:58'),
(960, 'CA', 'AB', NULL, 'EDM', NULL, 'Edmonton', 'city', 'AB', '2026-06-02 08:44:58'),
(961, 'CA', 'MB', NULL, 'WIN', NULL, 'Winnipeg', 'city', 'MB', '2026-06-02 08:44:58'),
(962, 'CA', 'NS', NULL, 'HAL', NULL, 'Halifax', 'city', 'NS', '2026-06-02 08:44:58'),
(963, 'GB', 'ENG', NULL, NULL, NULL, 'England', 'region', 'GB', '2026-06-02 08:44:58'),
(964, 'GB', 'SCT', NULL, NULL, NULL, 'Scotland', 'region', 'GB', '2026-06-02 08:44:58'),
(965, 'GB', 'WLS', NULL, NULL, NULL, 'Wales', 'region', 'GB', '2026-06-02 08:44:58'),
(966, 'GB', 'NIR', NULL, NULL, NULL, 'Northern Ireland', 'region', 'GB', '2026-06-02 08:44:58'),
(967, 'GB', 'ENG', NULL, 'LON', NULL, 'London', 'city', 'ENG', '2026-06-02 08:44:58'),
(968, 'GB', 'ENG', NULL, 'MAN', NULL, 'Manchester', 'city', 'ENG', '2026-06-02 08:44:58'),
(969, 'GB', 'ENG', NULL, 'BIR', NULL, 'Birmingham', 'city', 'ENG', '2026-06-02 08:44:58'),
(970, 'GB', 'ENG', NULL, 'LIV', NULL, 'Liverpool', 'city', 'ENG', '2026-06-02 08:44:58'),
(971, 'GB', 'ENG', NULL, 'LEE', NULL, 'Leeds', 'city', 'ENG', '2026-06-02 08:44:58'),
(972, 'GB', 'ENG', NULL, 'SHE', NULL, 'Sheffield', 'city', 'ENG', '2026-06-02 08:44:58'),
(973, 'GB', 'ENG', NULL, 'BRI', NULL, 'Bristol', 'city', 'ENG', '2026-06-02 08:44:58'),
(974, 'GB', 'SCT', NULL, 'EDI', NULL, 'Edinburgh', 'city', 'SCT', '2026-06-02 08:44:58'),
(975, 'GB', 'SCT', NULL, 'GLA', NULL, 'Glasgow', 'city', 'SCT', '2026-06-02 08:44:58'),
(976, 'GB', 'WLS', NULL, 'CAR', NULL, 'Cardiff', 'city', 'WLS', '2026-06-02 08:44:58'),
(977, 'GB', 'NIR', NULL, 'BEL', NULL, 'Belfast', 'city', 'NIR', '2026-06-02 08:44:58'),
(978, 'AU', 'NSW', NULL, NULL, NULL, 'New South Wales', 'region', 'AU', '2026-06-02 08:44:58'),
(979, 'AU', 'VIC', NULL, NULL, NULL, 'Victoria', 'region', 'AU', '2026-06-02 08:44:58'),
(980, 'AU', 'QLD', NULL, NULL, NULL, 'Queensland', 'region', 'AU', '2026-06-02 08:44:58'),
(981, 'AU', 'WA', NULL, NULL, NULL, 'Western Australia', 'region', 'AU', '2026-06-02 08:44:58'),
(982, 'AU', 'SA', NULL, NULL, NULL, 'South Australia', 'region', 'AU', '2026-06-02 08:44:58'),
(983, 'AU', 'TAS', NULL, NULL, NULL, 'Tasmania', 'region', 'AU', '2026-06-02 08:44:58'),
(984, 'AU', 'ACT', NULL, NULL, NULL, 'Australian Capital Territory', 'region', 'AU', '2026-06-02 08:44:58'),
(985, 'AU', 'NT', NULL, NULL, NULL, 'Northern Territory', 'region', 'AU', '2026-06-02 08:44:58'),
(986, 'AU', 'NSW', NULL, 'SYD', NULL, 'Sydney', 'city', 'NSW', '2026-06-02 08:44:58'),
(987, 'AU', 'VIC', NULL, 'MEL', NULL, 'Melbourne', 'city', 'VIC', '2026-06-02 08:44:58'),
(988, 'AU', 'QLD', NULL, 'BRI', NULL, 'Brisbane', 'city', 'QLD', '2026-06-02 08:44:58'),
(989, 'AU', 'WA', NULL, 'PER', NULL, 'Perth', 'city', 'WA', '2026-06-02 08:44:58'),
(990, 'AU', 'SA', NULL, 'ADE', NULL, 'Adelaide', 'city', 'SA', '2026-06-02 08:44:58'),
(991, 'AU', 'ACT', NULL, 'CAN', NULL, 'Canberra', 'city', 'ACT', '2026-06-02 08:44:58'),
(992, 'AU', 'TAS', NULL, 'HOB', NULL, 'Hobart', 'city', 'TAS', '2026-06-02 08:44:58'),
(993, 'AU', 'NT', NULL, 'DAR', NULL, 'Darwin', 'city', 'NT', '2026-06-02 08:44:58'),
(994, 'JP', 'TOK', NULL, NULL, NULL, 'Tokyo', 'region', 'JP', '2026-06-02 08:44:58'),
(995, 'JP', 'OSA', NULL, NULL, NULL, 'Osaka', 'region', 'JP', '2026-06-02 08:44:58'),
(996, 'JP', 'KYO', NULL, NULL, NULL, 'Kyoto', 'region', 'JP', '2026-06-02 08:44:58'),
(997, 'JP', 'YOK', NULL, NULL, NULL, 'Yokohama', 'region', 'JP', '2026-06-02 08:44:58'),
(998, 'JP', 'NAG', NULL, NULL, NULL, 'Nagoya', 'region', 'JP', '2026-06-02 08:44:58'),
(999, 'JP', 'SAP', NULL, NULL, NULL, 'Sapporo', 'region', 'JP', '2026-06-02 08:44:58'),
(1000, 'JP', 'KOB', NULL, NULL, NULL, 'Kobe', 'region', 'JP', '2026-06-02 08:44:58'),
(1001, 'JP', 'FUK', NULL, NULL, NULL, 'Fukuoka', 'region', 'JP', '2026-06-02 08:44:58'),
(1002, 'JP', 'SEN', NULL, NULL, NULL, 'Sendai', 'region', 'JP', '2026-06-02 08:44:58'),
(1003, 'JP', 'HIR', NULL, NULL, NULL, 'Hiroshima', 'region', 'JP', '2026-06-02 08:44:58'),
(1004, 'JP', 'TOK', NULL, 'TOK', NULL, 'Tokyo', 'city', 'TOK', '2026-06-02 08:44:58'),
(1005, 'JP', 'OSA', NULL, 'OSA', NULL, 'Osaka', 'city', 'OSA', '2026-06-02 08:44:58'),
(1006, 'JP', 'KYO', NULL, 'KYO', NULL, 'Kyoto', 'city', 'KYO', '2026-06-02 08:44:58'),
(1007, 'JP', 'YOK', NULL, 'YOK', NULL, 'Yokohama', 'city', 'YOK', '2026-06-02 08:44:58'),
(1008, 'JP', 'NAG', NULL, 'NAG', NULL, 'Nagoya', 'city', 'NAG', '2026-06-02 08:44:58'),
(1009, 'JP', 'SAP', NULL, 'SAP', NULL, 'Sapporo', 'city', 'SAP', '2026-06-02 08:44:58'),
(1010, 'JP', 'KOB', NULL, 'KOB', NULL, 'Kobe', 'city', 'KOB', '2026-06-02 08:44:58'),
(1011, 'JP', 'FUK', NULL, 'FUK', NULL, 'Fukuoka', 'city', 'FUK', '2026-06-02 08:44:58'),
(1012, 'JP', 'SEN', NULL, 'SEN', NULL, 'Sendai', 'city', 'SEN', '2026-06-02 08:44:58'),
(1013, 'JP', 'HIR', NULL, 'HIR', NULL, 'Hiroshima', 'city', 'HIR', '2026-06-02 08:44:58'),
(1014, 'DE', 'BW', NULL, NULL, NULL, 'Baden-Württemberg', 'region', 'DE', '2026-06-02 08:44:58'),
(1015, 'DE', 'BY', NULL, NULL, NULL, 'Bavaria', 'region', 'DE', '2026-06-02 08:44:58'),
(1016, 'DE', 'BE', NULL, NULL, NULL, 'Berlin', 'region', 'DE', '2026-06-02 08:44:58'),
(1017, 'DE', 'BB', NULL, NULL, NULL, 'Brandenburg', 'region', 'DE', '2026-06-02 08:44:58'),
(1018, 'DE', 'HB', NULL, NULL, NULL, 'Bremen', 'region', 'DE', '2026-06-02 08:44:58'),
(1019, 'DE', 'HH', NULL, NULL, NULL, 'Hamburg', 'region', 'DE', '2026-06-02 08:44:58'),
(1020, 'DE', 'HE', NULL, NULL, NULL, 'Hesse', 'region', 'DE', '2026-06-02 08:44:58'),
(1021, 'DE', 'MV', NULL, NULL, NULL, 'Mecklenburg-Vorpommern', 'region', 'DE', '2026-06-02 08:44:58'),
(1022, 'DE', 'NI', NULL, NULL, NULL, 'Lower Saxony', 'region', 'DE', '2026-06-02 08:44:58'),
(1023, 'DE', 'NW', NULL, NULL, NULL, 'North Rhine-Westphalia', 'region', 'DE', '2026-06-02 08:44:58'),
(1024, 'DE', 'RP', NULL, NULL, NULL, 'Rhineland-Palatinate', 'region', 'DE', '2026-06-02 08:44:58'),
(1025, 'DE', 'SL', NULL, NULL, NULL, 'Saarland', 'region', 'DE', '2026-06-02 08:44:58'),
(1026, 'DE', 'SN', NULL, NULL, NULL, 'Saxony', 'region', 'DE', '2026-06-02 08:44:58'),
(1027, 'DE', 'ST', NULL, NULL, NULL, 'Saxony-Anhalt', 'region', 'DE', '2026-06-02 08:44:58'),
(1028, 'DE', 'SH', NULL, NULL, NULL, 'Schleswig-Holstein', 'region', 'DE', '2026-06-02 08:44:58'),
(1029, 'DE', 'TH', NULL, NULL, NULL, 'Thuringia', 'region', 'DE', '2026-06-02 08:44:58'),
(1030, 'DE', 'BE', NULL, 'BER', NULL, 'Berlin', 'city', 'BE', '2026-06-02 08:44:58'),
(1031, 'DE', 'HH', NULL, 'HAM', NULL, 'Hamburg', 'city', 'HH', '2026-06-02 08:44:58'),
(1032, 'DE', 'BY', NULL, 'MUN', NULL, 'Munich', 'city', 'BY', '2026-06-02 08:44:58'),
(1033, 'DE', 'NW', NULL, 'COL', NULL, 'Cologne', 'city', 'NW', '2026-06-02 08:44:58'),
(1034, 'DE', 'HE', NULL, 'FRA', NULL, 'Frankfurt', 'city', 'HE', '2026-06-02 08:44:58'),
(1035, 'DE', 'NW', NULL, 'DUS', NULL, 'Düsseldorf', 'city', 'NW', '2026-06-02 08:44:58'),
(1036, 'DE', 'BW', NULL, 'STU', NULL, 'Stuttgart', 'city', 'BW', '2026-06-02 08:44:58'),
(1037, 'DE', 'NW', NULL, 'DOR', NULL, 'Dortmund', 'city', 'NW', '2026-06-02 08:44:58'),
(1038, 'DE', 'NW', NULL, 'ESS', NULL, 'Essen', 'city', 'NW', '2026-06-02 08:44:58'),
(1039, 'DE', 'SN', NULL, 'LEI', NULL, 'Leipzig', 'city', 'SN', '2026-06-02 08:44:58'),
(1040, 'SG', 'CEN', NULL, NULL, NULL, 'Central Region', 'region', 'SG', '2026-06-02 08:44:58'),
(1041, 'SG', 'EAS', NULL, NULL, NULL, 'East Region', 'region', 'SG', '2026-06-02 08:44:58'),
(1042, 'SG', 'NOR', NULL, NULL, NULL, 'North Region', 'region', 'SG', '2026-06-02 08:44:58'),
(1043, 'SG', 'NE', NULL, NULL, NULL, 'North-East Region', 'region', 'SG', '2026-06-02 08:44:58'),
(1044, 'SG', 'WES', NULL, NULL, NULL, 'West Region', 'region', 'SG', '2026-06-02 08:44:58'),
(1045, 'SG', 'CEN', NULL, 'CBD', NULL, 'Central Business District', 'city', 'CEN', '2026-06-02 08:44:58'),
(1046, 'SG', 'CEN', NULL, 'ORC', NULL, 'Orchard', 'city', 'CEN', '2026-06-02 08:44:58'),
(1047, 'SG', 'EAS', NULL, 'TAM', NULL, 'Tampines', 'city', 'EAS', '2026-06-02 08:44:58'),
(1048, 'SG', 'NOR', NULL, 'WOO', NULL, 'Woodlands', 'city', 'NOR', '2026-06-02 08:44:58'),
(1049, 'SG', 'NE', NULL, 'SEN', NULL, 'Sengkang', 'city', 'NE', '2026-06-02 08:44:58'),
(1050, 'SG', 'WES', NULL, 'JUR', NULL, 'Jurong', 'city', 'WES', '2026-06-02 08:44:58');

-- --------------------------------------------------------

--
-- Table structure for table `platform_leads`
--

CREATE TABLE `platform_leads` (
  `id` int(11) NOT NULL,
  `source` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `company_location` varchar(255) DEFAULT NULL,
  `materials_quantity` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `platform_leads`
--

INSERT INTO `platform_leads` (`id`, `source`, `company_name`, `contact_person`, `contact_number`, `email_address`, `company_location`, `materials_quantity`, `created_by`, `created_at`, `updated_at`, `archived_at`, `archived_by`) VALUES
(1, 'PHILGEPS', 'TDT Powersteel Corporation', 'Elpidio S. Uy', '0910517712', 'admin@gmail.com', 'Manila', NULL, 1, '2026-06-03 06:26:16', '2026-06-03 06:26:16', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `priority_alerts`
--

CREATE TABLE `priority_alerts` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `alerted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `priority_alerts`
--

INSERT INTO `priority_alerts` (`id`, `project_id`, `alerted_at`) VALUES
(1, 9, '2026-06-03 09:39:55'),
(2, 8, '2026-06-03 09:40:05'),
(3, 10, '2026-06-03 09:56:31'),
(4, 11, '2026-06-03 11:00:12'),
(5, 12, '2026-06-03 11:07:52'),
(9, 13, '2026-06-03 11:24:48'),
(23, 28, '2026-06-05 13:41:50');

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
  `notice_reference_number` varchar(5) DEFAULT NULL COMMENT 'PHILGEPS Notice Reference Number - 5 digits only, required when source is PHILGEPS',
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
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  KEY `idx_archived_at` (`archived_at`),
  KEY `idx_archived_by` (`archived_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `contractor_id`, `project_id`, `contractor_name`, `contact_person`, `contact_number`, `project_name`, `project_value`, `status`, `source`, `notice_reference_number`, `publication_date`, `address`, `region`, `city_province`, `contract_country`, `contract_region`, `contract_province`, `contract_city`, `contract_barangay`, `contract_street`, `contract_blk_lot`, `contract_coordinates`, `project_country`, `project_region`, `project_province`, `project_city`, `project_barangay`, `project_street`, `project_blk_lot`, `project_coordinates`, `sheet_pile_type`, `sheet_pile_amount`, `drbs`, `drbs_value`, `accomplishment_rate`, `ms_plate`, `angle_bars`, `channel_bars`, `wide_flange`, `gi_bi`, `assigned_to`, `assigned_at`, `assigned_by`, `is_processed`, `processed_at`, `processed_by`, `encoded_by`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'ABC Construction Corp', 'Juan Dela Cruz', '+63 917 123 4567', 'Quezon City Road Expansion Project', 15000000.00, 'For Bidding', 'DPWH', NULL, '2024-02-15', NULL, 'NCR', NULL, 'Philippines', 'NCR', NULL, 'Manila', NULL, NULL, NULL, NULL, 'Philippines', 'NCR', NULL, 'Quezon City', NULL, NULL, NULL, NULL, 'Standard Z-Type Sheet Pile', NULL, 'DRBS Type B - High Strength Bars', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-05-28 16:14:50', '2026-06-02 10:25:14'),
(2, NULL, NULL, 'XYZ Infrastructure Inc', 'Maria Santos', '+63 918 987 6543', 'Laguna Bridge Construction', 25000000.00, 'For Execution', 'DPWH', NULL, '2024-02-10', NULL, 'IV-A', NULL, 'Philippines', 'IV-A', NULL, 'Bacoor', NULL, NULL, NULL, NULL, 'Philippines', 'IV-A', NULL, 'Laguna', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 3, '2026-06-02 16:40:14', NULL, 0, NULL, NULL, 1, '2026-05-28 16:14:50', '2026-06-02 16:40:14'),
(3, 'gdfh', '12345', 'fasfsa', 'fasfasf', '513515', 'TAWI-TAWI SPORTS DEVELOPMENT COMPLEX - PHASE 1', 2347457.00, 'For Execution', 'DPWH', NULL, '2024-05-28', NULL, 'VIII', 'TAC', 'PH', 'VIII', 'LEY', 'TAC', NULL, NULL, NULL, NULL, 'PH', 'CAR', 'BEN', 'BAG', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-05-28 16:26:27', '2026-06-02 09:56:42'),
(4, NULL, '123456789', '7JL HARDWARE & CONSTRUCTION SUPPLIES', 'Joel F. Bigno', '63 36 520 2802 / 63 915 971 0071/ (036) 520 2902', 'ACADEMIC BUILDING (4 CLASSROOMS) FOR SCHOOL - NEW - SINGLE STOREY', 199999.00, 'For Execution', 'BCI', NULL, '2024-05-28', NULL, 'V', 'SOR', 'PH', 'V', 'SOR', 'SOR', 'lubigan Sr.', NULL, NULL, NULL, 'PH', 'IX', 'ZAN', 'DAP', 'Brgy Tanza Roxas City', 'Joel F Bigno Building', 'Joel F Bigno Building', 'Joel F Bigno Building, Brgy Tanza Roxas City , Capiz 5800 , Philippines', NULL, 109999.00, NULL, NULL, 0.00, 10999999.00, 10099999.00, 10999999.00, 1099999999999.00, 10999999999999.00, NULL, NULL, NULL, 0, NULL, NULL, 4, '2026-05-28 16:57:37', '2026-06-02 09:56:42'),
(5, 'dasdasd', '35252', 'D.C. SANDIL CONSTRUCTION & REALTY DEVELOPMENT INC.', 'MR. TRISTAN JARVIS M. TOLENTINO', '36252136236t', 'TAWI-TAWI SPORTS DEVELOPMENT COMPLEX - PHASE 1', 3264621356.00, 'For Execution', 'BCI', NULL, '2024-05-29', NULL, 'BARMM', 'LAM', 'PH', 'BARMM', 'BAS', 'LAM', NULL, NULL, NULL, NULL, 'PH', 'VII', 'NEG', 'CAN', NULL, NULL, NULL, NULL, NULL, 5235235623.00, NULL, 253523.00, 0.00, 2352352.00, 52352352.00, 3523523523.00, 523523525.00, 2523525.00, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-05-29 15:41:10', '2026-06-02 09:56:42'),
(6, '12312312', NULL, 'D.C. SANDIL CONSTRUCTION & REALTY DEVELOPMENT INC.', '\"Engr Dennis C. Sandil \"', '\"63 2 8724 9288 \"', 'BACOLOD SANITARY LANDFILL (CELL NO. 5)', 440011814.49, 'For Execution', 'DPWH', NULL, '2024-06-02', NULL, 'VI', 'BAC', 'PH', 'VI', 'NEG', 'BAC', 'Felisa', NULL, NULL, NULL, 'PH', 'VI', 'NEG', 'BAC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 900000.00, 1300000.00, 2500000.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, 4, '2026-06-01 09:06:40', '2026-06-02 09:56:42'),
(7, '1rfc1325', '1234524', 'ED1SON DEVELOPMENT AND CONSTRUCT INC', 'Engr Dennis C. Sandil', '456536426746', 'BACOLOD SANITARY LANDFILL (CELL NO. 5)', 1234124.00, 'For Execution', 'BCI', NULL, '2026-06-01', NULL, 'V', 'LIG', 'PH', 'V', 'ALB', 'LIG', NULL, NULL, NULL, NULL, 'PH', 'VII', 'CEB', 'NAG', NULL, NULL, NULL, NULL, NULL, 151251.00, '214', NULL, 0.00, 1525.00, 1251512.00, 1515.00, 151515.00, 125152.00, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-06-01 15:58:14', '2026-06-01 15:58:14'),
(8, 'edft2342', '51225136', 'TJMT CONSTRUCTION', 'Elpidio S. Uy', '456536426746', 'CONSTRUCTION OF MULTI-PURPOSE FACILITIES (SPORTS COMPLEX), PHASE 1, BULANAO CENTRO, TABUK CITY, KALINGA', 53241.00, 'Priority', 'BCI', NULL, '2024-06-01', 'Block 1 Lot 5, Main Street, Bulanao Centro, Tabuk City, Kalinga, Philippines', 'CAR', 'Tabuk City, Kalinga', 'PH', 'CAR', 'KAL', 'TAB', 'Bulanao Centro', 'Main Street', 'Block 1 Lot 5', '17.6100,121.1000', 'PH', 'CAR', 'KAL', 'TAB', 'Bulanao Centro', 'Tabuk National Road', 'Lot 1-A', '17.4180,121.4450', 'PU-18W Hot Rolled Steel Sheet Pile', 52352.00, 'DRBS Type A - Standard Reinforcing Bars', 2352352.00, 23.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-06-02 08:45:07', '2026-06-02 10:23:38'),
(9, 'fsadtse2', '5325235', 'TJMT CONSTRUCTION', 'Engr Dennis C. Sandil', '4565 3642 746', 'BACOLOD SANITARY LANDFILL (CELL NO. 5)', 2415132.00, 'Priority', 'DPWH', NULL, NULL, NULL, 'CAL', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 67.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-06-03 09:39:08', '2026-06-03 09:39:08'),
(10, NULL, NULL, 'URGENT CONSTRUCTION CORPORATION', 'Emergency Project Manager', '+63 917 URGENT1', '🚨 PRIORITY: Emergency Bridge Repair - Immediate Action Required', 50000000.00, 'PRIORITY', 'DPWH', NULL, '2026-06-03', 'Rizal Bridge, Sta. Cruz, Manila', 'NCR', 'Manila City', 'Philippines', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Philippines', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 15500000.00, NULL, 8200000.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-06-03 09:56:26', '2026-06-03 09:56:26'),
(11, '2q523', '352', 'D.C. SANDIL CONSTRUCTION & REALTY DEVELOPMENT INC.', 'Elpidio S. Uy', '456536426746', 'BACOLOD SANITARY LANDFILL (CELL NO. 5)', 5325235.00, 'Priority', 'DPWH', NULL, NULL, NULL, 'SFE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-06-03 11:00:08', '2026-06-03 11:00:08'),
(12, 'CONTR-2024-001', 'PROJ-PRI-2024-001', 'PREMIUM STEEL CONSTRUCTION CORPORATION', 'Engr. Maria Elena Santos-Cruz', '+63 917 555 0123 / +63 2 8555 1234', '🚨 PRIORITY: Metro Manila Bridge Reinforcement & Seismic Retrofitting Project - Phase 1 (Critical Infrastructure)', 125000000.00, 'PRIORITY', 'DPWH', NULL, '2026-06-03', 'Comprehensive Project Address Here', 'NCR', 'Manila City', 'PH', 'NCR', 'Metro Manila', 'Manila', 'Ermita', 'Roxas Boulevard corner Kalaw Street', 'Block 15, Lot 8-12', '14.5794, 120.9767', 'PH', 'NCR', 'Metro Manila', 'Quezon City', 'Bagumbayan', 'EDSA Extension Bridge Area', 'Bridge Section B1-B5', '14.6760, 121.0437', 'PSM 280 Cold Formed Steel', 35000000.00, 'Deformed Reinforcing Bars - Grade 40, Various Sizes (10mm-25mm)', 28000000.00, 25.50, 15000000.00, 8500000.00, 12000000.00, 18000000.00, 7500000.00, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-06-03 11:07:47', '2026-06-03 11:07:47'),
(13, 'saf', '1241251', 'TJMT CONSTRUCTION', 'Engr Dennis C. Sandil', 'gsdfgdsfg', 'BACOLOD SANITARY LANDFILL (CELL NO. 5)', 6552345.00, 'Priority', 'BCI', NULL, NULL, NULL, 'CAL', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 85.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-06-03 11:14:18', '2026-06-03 11:14:18'),
(27, '14144124', '241313', 'TJMT CONSTRUCTION', 'Engr Dennis C. Sandil', '456536426746', 'BACOLOD SANITARY LANDFILL (CELL NO. 5)', 213123.00, 'For Execution', 'DPWH', NULL, '2026-06-03', NULL, 'National Capital Region (NCR)', 'Caloocan', 'Philippines', 'National Capital Region (NCR)', 'Metro Manila', 'Caloocan', NULL, NULL, NULL, NULL, 'Philippines', 'MIMAROPA (Region IV-B)', 'Oriental Mindoro', 'Matandang Naujan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-06-03 16:17:56', '2026-06-03 16:17:56'),
(28, NULL, NULL, 'TEST CONTRACTOR ENHANCED', 'Engr. Maria Santos', '+63-917-123-4567', 'ENHANCED PRIORITY TESTING PROJECT - MODAL DESIGN VERIFICATION', 15750000.00, 'PRIORITY', 'DPWH', NULL, '2026-06-05', 'Legacy Address Field', 'NCR', 'MET', 'Philippines', NULL, NULL, 'Quezon City', 'Barangay Kapitolyo', '456 EDSA Corner Shaw Boulevard', 'Unit 2B', NULL, 'Philippines', NULL, NULL, 'Manila', 'Barangay San Miguel', '123 Rizal Avenue', 'Block 5 Lot 10', NULL, 'Steel Grade A992', 2500000.00, 'Category III Bridge Construction', 1200000.00, 67.50, 850000.00, 320000.00, 180000.00, 450000.00, 95000.00, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-06-05 13:25:02', '2026-06-05 13:25:02');

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

--
-- Dumping data for table `project_images`
--

INSERT INTO `project_images` (`id`, `project_id`, `file_path`, `created_at`) VALUES
(1, 11, 'uploads/project_photos/11_1780455608_dd0d167e6c482218.png', '2026-06-03 11:00:08'),
(2, 11, 'uploads/project_photos/11_1780455608_d5322521cc9059fe.png', '2026-06-03 11:00:08'),
(3, 13, 'uploads/project_photos/13_1780456458_6d562b3d1ab977f1.png', '2026-06-03 11:14:18');

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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `contacted` enum('Yes','No') DEFAULT NULL COMMENT 'Has the project been contacted?',
  `quoted` enum('Yes','No') DEFAULT NULL COMMENT 'Has a quote been provided?',
  `to_win` enum('Yes','No') DEFAULT NULL COMMENT 'Is this project won?',
  `sales_qualified` enum('Yes','No') DEFAULT NULL COMMENT 'Is this a Sales Qualified Lead?',
  `wa_amount` decimal(18,2) DEFAULT 0.00,
  `tracking_status` enum('Not Started','In Progress','Complete') NOT NULL DEFAULT 'Not Started',
  `branch` varchar(100) DEFAULT NULL COMMENT 'Sales rep branch',
  `assigned_at` datetime DEFAULT NULL COMMENT 'When the project was assigned to this SR',
  `contacted_at` datetime DEFAULT NULL COMMENT 'When contacted was first set to Yes',
  `sales_qualified_at` datetime DEFAULT NULL COMMENT 'When sales_qualified was first set (Yes or No)',
  `quoted_at` datetime DEFAULT NULL COMMENT 'When quoted was first set to Yes',
  `to_win_at` datetime DEFAULT NULL COMMENT 'When to_win was first set to Yes with wa_amount'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_tracking`
--

INSERT INTO `sales_tracking` (`id`, `project_id`, `sales_rep_id`, `status`, `first_contact_date`, `last_contact_date`, `next_followup_date`, `contact_method`, `client_response`, `notes`, `probability_percentage`, `expected_close_date`, `updated_by`, `created_at`, `updated_at`, `contacted`, `quoted`, `to_win`, `sales_qualified`, `wa_amount`, `tracking_status`, `branch`) VALUES
(1, 6, 3, 'Assigned', NULL, NULL, NULL, NULL, NULL, 'ayaw', 0.00, NULL, 1, '2026-06-01 13:10:06', '2026-06-01 14:09:30', 'No', 'Yes', 'No', 'Yes', 0.00, 'Complete', 'Manila Branch'),
(2, 5, 1, 'Assigned', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 1, '2026-06-01 13:33:05', '2026-06-01 13:33:05', 'No', 'No', 'No', 'No', NULL, 'In Progress', NULL),
(3, 4, 1, 'Assigned', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 1, '2026-06-01 13:48:33', '2026-06-01 13:49:02', 'Yes', 'No', NULL, NULL, NULL, 'In Progress', NULL),
(4, 7, 3, 'Assigned', NULL, NULL, NULL, NULL, NULL, 'd', 0.00, NULL, 1, '2026-06-01 16:42:32', '2026-06-02 09:52:53', 'Yes', NULL, NULL, NULL, NULL, 'In Progress', 'Manila Branch'),
(5, 8, 3, 'Assigned', NULL, NULL, NULL, NULL, NULL, 'Done Contact', 0.00, NULL, 1, '2026-06-02 09:16:29', '2026-06-02 09:27:20', 'No', NULL, NULL, NULL, NULL, 'In Progress', 'Manila Branch');

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
(1, 'superadmin@tdtpowersteel.com', 'System Administrator', '$2y$12$Tj.fscI5lyErHx.JRysj/OLgbWVQFSkh2000qKYPCRK2HqEJuH82C', 'superadmin', NULL, NULL, 0, '2026-05-28 16:14:50', '2026-06-08 08:17:32'),
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
(9, 1, 'fomjiibve4n21lquhvv1lov0tr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:20:55', '2026-06-01 09:20:55'),
(10, 1, 'j034a08j21us54odpl2h62i2pu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 10:27:03', '2026-06-01 10:27:03'),
(11, 1, '7jol8r2d3uip3edbn37vpk38af', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 10:27:19', '2026-06-01 10:27:19'),
(12, 1, 'hq70mnkkl3t6ikgfcpf43fh8kt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 10:30:00', '2026-06-01 10:30:00'),
(13, 1, 'dc0bf6jgcfgp51repatm74t1j4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 10:30:05', '2026-06-01 10:30:05'),
(14, 1, 'goude2jg4gv2cdr892qca9np33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 11:09:03', '2026-06-01 11:09:03'),
(15, 1, 'b54ebd6q5nb63kkbtkea5o9j1p', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-06-01 11:45:31', '2026-06-01 11:45:31'),
(16, 1, 'as6phoonc1djfbvfh7ls7qmu3b', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 12:59:33', '2026-06-01 12:59:33'),
(17, 1, 'ilmn6hf6rt5cscl41n1kir7u35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 15:57:23', '2026-06-01 15:57:23'),
(18, 1, 'mimmtjbvsvdnps8hfa45r2anm2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 08:37:42', '2026-06-02 08:37:42'),
(19, 1, 'r2k2kfgajenc5o5kdk9hvurjjn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 08:37:46', '2026-06-02 08:37:46'),
(20, 1, 'a4ap2ivfc8287ip365urm3pmrg', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:55:19', '2026-06-02 12:55:19'),
(21, 1, 'i15smjden2o523fi1qp59f6r65', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 14:51:56', '2026-06-02 14:51:56'),
(22, 1, 'vqt171o8q1dn76rmok3j78ue1r', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 15:24:51', '2026-06-02 15:24:51'),
(23, 1, 'vhpd33684vcgfaap0escv4gb71', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 17:32:19', '2026-06-02 17:32:19'),
(24, 1, '6nvr47eql2ae1n7eradhgga2q6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 08:33:10', '2026-06-03 08:33:10'),
(25, 1, '0fenoq93qq9jmba9nu3200io1n', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 08:33:17', '2026-06-03 08:33:17'),
(26, 1, 'dtdpj5m2tc7va8m8p979f1grtf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 13:13:22', '2026-06-03 13:13:22'),
(27, 1, 'g2bdoo4ektoef9491i4k9edjsm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 13:13:30', '2026-06-03 13:13:30'),
(28, 1, 'lctt2p60mktk58of0oujbl3b94', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:31:14', '2026-06-03 15:31:14'),
(29, 1, '6rolmfeb4lhdf5n1r7u10ebnaf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 13:25:23', '2026-06-05 13:25:23'),
(30, 1, 'ptt9rl57lf8vuur9a1cmlah6i7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 15:50:27', '2026-06-05 15:50:27'),
(31, 1, 'eubie4452am2r534va48dru7s5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-08 08:17:32', '2026-06-08 08:17:32');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL COMMENT 'project, platform, user, etc.',
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `platform_leads`
--
ALTER TABLE `platform_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_source` (`source`),
  ADD KEY `idx_contact_person` (`contact_person`),
  ADD KEY `idx_email_address` (`email_address`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_archived_at` (`archived_at`);

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
  ADD KEY `idx_publication_date` (`publication_date`),
  ADD KEY `idx_notice_reference_number` (`notice_reference_number`);

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
  ADD KEY `idx_expected_close` (`expected_close_date`),
  ADD KEY `idx_sales_tracking_contacted` (`contacted`),
  ADD KEY `idx_sales_tracking_quoted` (`quoted`),
  ADD KEY `idx_sales_tracking_to_win` (`to_win`),
  ADD KEY `idx_sales_tracking_sales_qualified` (`sales_qualified`),
  ADD KEY `idx_sales_tracking_status` (`tracking_status`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1051;

--
-- AUTO_INCREMENT for table `platform_leads`
--
ALTER TABLE `platform_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `priority_alerts`
--
ALTER TABLE `priority_alerts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `project_images`
--
ALTER TABLE `project_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_tracking`
--
ALTER TABLE `sales_tracking`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
