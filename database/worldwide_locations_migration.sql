-- ============================================================
-- Worldwide Locations Migration
-- ============================================================
-- This migration adds comprehensive worldwide location data
-- including countries, states/regions, provinces, and major cities
-- ============================================================

-- First, let's add major countries
INSERT INTO `locations` (`country_code`, `name`, `type`, `parent_code`) VALUES
-- Asia
('PH', 'Philippines', 'country', NULL),
('US', 'United States', 'country', NULL),
('CA', 'Canada', 'country', NULL),
('GB', 'United Kingdom', 'country', NULL),
('AU', 'Australia', 'country', NULL),
('JP', 'Japan', 'country', NULL),
('KR', 'South Korea', 'country', NULL),
('CN', 'China', 'country', NULL),
('IN', 'India', 'country', NULL),
('ID', 'Indonesia', 'country', NULL),
('TH', 'Thailand', 'country', NULL),
('VN', 'Vietnam', 'country', NULL),
('MY', 'Malaysia', 'country', NULL),
('SG', 'Singapore', 'country', NULL),
('HK', 'Hong Kong', 'country', NULL),
('TW', 'Taiwan', 'country', NULL),

-- Europe
('DE', 'Germany', 'country', NULL),
('FR', 'France', 'country', NULL),
('IT', 'Italy', 'country', NULL),
('ES', 'Spain', 'country', NULL),
('NL', 'Netherlands', 'country', NULL),
('BE', 'Belgium', 'country', NULL),
('CH', 'Switzerland', 'country', NULL),
('AT', 'Austria', 'country', NULL),
('SE', 'Sweden', 'country', NULL),
('NO', 'Norway', 'country', NULL),
('DK', 'Denmark', 'country', NULL),
('FI', 'Finland', 'country', NULL),
('PL', 'Poland', 'country', NULL),
('CZ', 'Czech Republic', 'country', NULL),
('HU', 'Hungary', 'country', NULL),
('RO', 'Romania', 'country', NULL),
('BG', 'Bulgaria', 'country', NULL),
('GR', 'Greece', 'country', NULL),
('PT', 'Portugal', 'country', NULL),
('IE', 'Ireland', 'country', NULL),
('RU', 'Russia', 'country', NULL),

-- Americas
('MX', 'Mexico', 'country', NULL),
('BR', 'Brazil', 'country', NULL),
('AR', 'Argentina', 'country', NULL),
('CL', 'Chile', 'country', NULL),
('CO', 'Colombia', 'country', NULL),
('PE', 'Peru', 'country', NULL),
('VE', 'Venezuela', 'country', NULL),
('EC', 'Ecuador', 'country', NULL),
('UY', 'Uruguay', 'country', NULL),
('PY', 'Paraguay', 'country', NULL),
('BO', 'Bolivia', 'country', NULL),

-- Africa
('ZA', 'South Africa', 'country', NULL),
('NG', 'Nigeria', 'country', NULL),
('EG', 'Egypt', 'country', NULL),
('KE', 'Kenya', 'country', NULL),
('MA', 'Morocco', 'country', NULL),
('GH', 'Ghana', 'country', NULL),
('ET', 'Ethiopia', 'country', NULL),
('TZ', 'Tanzania', 'country', NULL),
('UG', 'Uganda', 'country', NULL),

-- Middle East
('AE', 'United Arab Emirates', 'country', NULL),
('SA', 'Saudi Arabia', 'country', NULL),
('QA', 'Qatar', 'country', NULL),
('KW', 'Kuwait', 'country', NULL),
('BH', 'Bahrain', 'country', NULL),
('OM', 'Oman', 'country', NULL),
('JO', 'Jordan', 'country', NULL),
('LB', 'Lebanon', 'country', NULL),
('IL', 'Israel', 'country', NULL),
('TR', 'Turkey', 'country', NULL),
('IR', 'Iran', 'country', NULL),
('IQ', 'Iraq', 'country', NULL);

-- ============================================================
-- UNITED STATES - States and Major Cities
-- ============================================================

-- US States (Regions)
INSERT INTO `locations` (`country_code`, `region_code`, `name`, `type`, `parent_code`) VALUES
('US', 'AL', 'Alabama', 'region', 'US'),
('US', 'AK', 'Alaska', 'region', 'US'),
('US', 'AZ', 'Arizona', 'region', 'US'),
('US', 'AR', 'Arkansas', 'region', 'US'),
('US', 'CA', 'California', 'region', 'US'),
('US', 'CO', 'Colorado', 'region', 'US'),
('US', 'CT', 'Connecticut', 'region', 'US'),
('US', 'DE', 'Delaware', 'region', 'US'),
('US', 'FL', 'Florida', 'region', 'US'),
('US', 'GA', 'Georgia', 'region', 'US'),
('US', 'HI', 'Hawaii', 'region', 'US'),
('US', 'ID', 'Idaho', 'region', 'US'),
('US', 'IL', 'Illinois', 'region', 'US'),
('US', 'IN', 'Indiana', 'region', 'US'),
('US', 'IA', 'Iowa', 'region', 'US'),
('US', 'KS', 'Kansas', 'region', 'US'),
('US', 'KY', 'Kentucky', 'region', 'US'),
('US', 'LA', 'Louisiana', 'region', 'US'),
('US', 'ME', 'Maine', 'region', 'US'),
('US', 'MD', 'Maryland', 'region', 'US'),
('US', 'MA', 'Massachusetts', 'region', 'US'),
('US', 'MI', 'Michigan', 'region', 'US'),
('US', 'MN', 'Minnesota', 'region', 'US'),
('US', 'MS', 'Mississippi', 'region', 'US'),
('US', 'MO', 'Missouri', 'region', 'US'),
('US', 'MT', 'Montana', 'region', 'US'),
('US', 'NE', 'Nebraska', 'region', 'US'),
('US', 'NV', 'Nevada', 'region', 'US'),
('US', 'NH', 'New Hampshire', 'region', 'US'),
('US', 'NJ', 'New Jersey', 'region', 'US'),
('US', 'NM', 'New Mexico', 'region', 'US'),
('US', 'NY', 'New York', 'region', 'US'),
('US', 'NC', 'North Carolina', 'region', 'US'),
('US', 'ND', 'North Dakota', 'region', 'US'),
('US', 'OH', 'Ohio', 'region', 'US'),
('US', 'OK', 'Oklahoma', 'region', 'US'),
('US', 'OR', 'Oregon', 'region', 'US'),
('US', 'PA', 'Pennsylvania', 'region', 'US'),
('US', 'RI', 'Rhode Island', 'region', 'US'),
('US', 'SC', 'South Carolina', 'region', 'US'),
('US', 'SD', 'South Dakota', 'region', 'US'),
('US', 'TN', 'Tennessee', 'region', 'US'),
('US', 'TX', 'Texas', 'region', 'US'),
('US', 'UT', 'Utah', 'region', 'US'),
('US', 'VT', 'Vermont', 'region', 'US'),
('US', 'VA', 'Virginia', 'region', 'US'),
('US', 'WA', 'Washington', 'region', 'US'),
('US', 'WV', 'West Virginia', 'region', 'US'),
('US', 'WI', 'Wisconsin', 'region', 'US'),
('US', 'WY', 'Wyoming', 'region', 'US'),
('US', 'DC', 'District of Columbia', 'region', 'US');

-- Major US Cities
INSERT INTO `locations` (`country_code`, `region_code`, `city_code`, `name`, `type`, `parent_code`) VALUES
-- California
('US', 'CA', 'LA', 'Los Angeles', 'city', 'CA'),
('US', 'CA', 'SF', 'San Francisco', 'city', 'CA'),
('US', 'CA', 'SD', 'San Diego', 'city', 'CA'),
('US', 'CA', 'SJ', 'San Jose', 'city', 'CA'),
('US', 'CA', 'SAC', 'Sacramento', 'city', 'CA'),
('US', 'CA', 'OAK', 'Oakland', 'city', 'CA'),

-- New York
('US', 'NY', 'NYC', 'New York City', 'city', 'NY'),
('US', 'NY', 'BUF', 'Buffalo', 'city', 'NY'),
('US', 'NY', 'ROC', 'Rochester', 'city', 'NY'),
('US', 'NY', 'SYR', 'Syracuse', 'city', 'NY'),
('US', 'NY', 'ALB', 'Albany', 'city', 'NY'),

-- Texas
('US', 'TX', 'HOU', 'Houston', 'city', 'TX'),
('US', 'TX', 'DAL', 'Dallas', 'city', 'TX'),
('US', 'TX', 'SA', 'San Antonio', 'city', 'TX'),
('US', 'TX', 'AUS', 'Austin', 'city', 'TX'),
('US', 'TX', 'FW', 'Fort Worth', 'city', 'TX'),

-- Florida
('US', 'FL', 'MIA', 'Miami', 'city', 'FL'),
('US', 'FL', 'JAX', 'Jacksonville', 'city', 'FL'),
('US', 'FL', 'TB', 'Tampa', 'city', 'FL'),
('US', 'FL', 'ORL', 'Orlando', 'city', 'FL'),
('US', 'FL', 'TLH', 'Tallahassee', 'city', 'FL'),

-- Illinois
('US', 'IL', 'CHI', 'Chicago', 'city', 'IL'),
('US', 'IL', 'SPR', 'Springfield', 'city', 'IL'),
('US', 'IL', 'ROC', 'Rockford', 'city', 'IL'),

-- Other major cities
('US', 'WA', 'SEA', 'Seattle', 'city', 'WA'),
('US', 'WA', 'SPO', 'Spokane', 'city', 'WA'),
('US', 'OR', 'POR', 'Portland', 'city', 'OR'),
('US', 'CO', 'DEN', 'Denver', 'city', 'CO'),
('US', 'AZ', 'PHX', 'Phoenix', 'city', 'AZ'),
('US', 'NV', 'LV', 'Las Vegas', 'city', 'NV'),
('US', 'MA', 'BOS', 'Boston', 'city', 'MA'),
('US', 'PA', 'PHI', 'Philadelphia', 'city', 'PA'),
('US', 'MI', 'DET', 'Detroit', 'city', 'MI'),
('US', 'OH', 'CLE', 'Cleveland', 'city', 'OH'),
('US', 'GA', 'ATL', 'Atlanta', 'city', 'GA'),
('US', 'DC', 'WAS', 'Washington', 'city', 'DC');

-- ============================================================
-- CANADA - Provinces and Major Cities
-- ============================================================

-- Canadian Provinces/Territories
INSERT INTO `locations` (`country_code`, `region_code`, `name`, `type`, `parent_code`) VALUES
('CA', 'AB', 'Alberta', 'region', 'CA'),
('CA', 'BC', 'British Columbia', 'region', 'CA'),
('CA', 'MB', 'Manitoba', 'region', 'CA'),
('CA', 'NB', 'New Brunswick', 'region', 'CA'),
('CA', 'NL', 'Newfoundland and Labrador', 'region', 'CA'),
('CA', 'NS', 'Nova Scotia', 'region', 'CA'),
('CA', 'ON', 'Ontario', 'region', 'CA'),
('CA', 'PE', 'Prince Edward Island', 'region', 'CA'),
('CA', 'QC', 'Quebec', 'region', 'CA'),
('CA', 'SK', 'Saskatchewan', 'region', 'CA'),
('CA', 'NT', 'Northwest Territories', 'region', 'CA'),
('CA', 'NU', 'Nunavut', 'region', 'CA'),
('CA', 'YT', 'Yukon', 'region', 'CA');

-- Major Canadian Cities
INSERT INTO `locations` (`country_code`, `region_code`, `city_code`, `name`, `type`, `parent_code`) VALUES
('CA', 'ON', 'TOR', 'Toronto', 'city', 'ON'),
('CA', 'ON', 'OTT', 'Ottawa', 'city', 'ON'),
('CA', 'ON', 'HAM', 'Hamilton', 'city', 'ON'),
('CA', 'QC', 'MTL', 'Montreal', 'city', 'QC'),
('CA', 'QC', 'QUE', 'Quebec City', 'city', 'QC'),
('CA', 'BC', 'VAN', 'Vancouver', 'city', 'BC'),
('CA', 'BC', 'VIC', 'Victoria', 'city', 'BC'),
('CA', 'AB', 'CAL', 'Calgary', 'city', 'AB'),
('CA', 'AB', 'EDM', 'Edmonton', 'city', 'AB'),
('CA', 'MB', 'WIN', 'Winnipeg', 'city', 'MB'),
('CA', 'NS', 'HAL', 'Halifax', 'city', 'NS');

-- ============================================================
-- UNITED KINGDOM - Countries and Major Cities
-- ============================================================

-- UK Countries (Regions)
INSERT INTO `locations` (`country_code`, `region_code`, `name`, `type`, `parent_code`) VALUES
('GB', 'ENG', 'England', 'region', 'GB'),
('GB', 'SCT', 'Scotland', 'region', 'GB'),
('GB', 'WLS', 'Wales', 'region', 'GB'),
('GB', 'NIR', 'Northern Ireland', 'region', 'GB');

-- Major UK Cities
INSERT INTO `locations` (`country_code`, `region_code`, `city_code`, `name`, `type`, `parent_code`) VALUES
('GB', 'ENG', 'LON', 'London', 'city', 'ENG'),
('GB', 'ENG', 'MAN', 'Manchester', 'city', 'ENG'),
('GB', 'ENG', 'BIR', 'Birmingham', 'city', 'ENG'),
('GB', 'ENG', 'LIV', 'Liverpool', 'city', 'ENG'),
('GB', 'ENG', 'LEE', 'Leeds', 'city', 'ENG'),
('GB', 'ENG', 'SHE', 'Sheffield', 'city', 'ENG'),
('GB', 'ENG', 'BRI', 'Bristol', 'city', 'ENG'),
('GB', 'SCT', 'EDI', 'Edinburgh', 'city', 'SCT'),
('GB', 'SCT', 'GLA', 'Glasgow', 'city', 'SCT'),
('GB', 'WLS', 'CAR', 'Cardiff', 'city', 'WLS'),
('GB', 'NIR', 'BEL', 'Belfast', 'city', 'NIR');

-- ============================================================
-- AUSTRALIA - States and Major Cities
-- ============================================================

-- Australian States/Territories
INSERT INTO `locations` (`country_code`, `region_code`, `name`, `type`, `parent_code`) VALUES
('AU', 'NSW', 'New South Wales', 'region', 'AU'),
('AU', 'VIC', 'Victoria', 'region', 'AU'),
('AU', 'QLD', 'Queensland', 'region', 'AU'),
('AU', 'WA', 'Western Australia', 'region', 'AU'),
('AU', 'SA', 'South Australia', 'region', 'AU'),
('AU', 'TAS', 'Tasmania', 'region', 'AU'),
('AU', 'ACT', 'Australian Capital Territory', 'region', 'AU'),
('AU', 'NT', 'Northern Territory', 'region', 'AU');

-- Major Australian Cities
INSERT INTO `locations` (`country_code`, `region_code`, `city_code`, `name`, `type`, `parent_code`) VALUES
('AU', 'NSW', 'SYD', 'Sydney', 'city', 'NSW'),
('AU', 'VIC', 'MEL', 'Melbourne', 'city', 'VIC'),
('AU', 'QLD', 'BRI', 'Brisbane', 'city', 'QLD'),
('AU', 'WA', 'PER', 'Perth', 'city', 'WA'),
('AU', 'SA', 'ADE', 'Adelaide', 'city', 'SA'),
('AU', 'ACT', 'CAN', 'Canberra', 'city', 'ACT'),
('AU', 'TAS', 'HOB', 'Hobart', 'city', 'TAS'),
('AU', 'NT', 'DAR', 'Darwin', 'city', 'NT');

-- ============================================================
-- JAPAN - Prefectures and Major Cities
-- ============================================================

-- Japanese Regions (Major Prefectures)
INSERT INTO `locations` (`country_code`, `region_code`, `name`, `type`, `parent_code`) VALUES
('JP', 'TOK', 'Tokyo', 'region', 'JP'),
('JP', 'OSA', 'Osaka', 'region', 'JP'),
('JP', 'KYO', 'Kyoto', 'region', 'JP'),
('JP', 'YOK', 'Yokohama', 'region', 'JP'),
('JP', 'NAG', 'Nagoya', 'region', 'JP'),
('JP', 'SAP', 'Sapporo', 'region', 'JP'),
('JP', 'KOB', 'Kobe', 'region', 'JP'),
('JP', 'FUK', 'Fukuoka', 'region', 'JP'),
('JP', 'SEN', 'Sendai', 'region', 'JP'),
('JP', 'HIR', 'Hiroshima', 'region', 'JP');

-- Major Japanese Cities
INSERT INTO `locations` (`country_code`, `region_code`, `city_code`, `name`, `type`, `parent_code`) VALUES
('JP', 'TOK', 'TOK', 'Tokyo', 'city', 'TOK'),
('JP', 'OSA', 'OSA', 'Osaka', 'city', 'OSA'),
('JP', 'KYO', 'KYO', 'Kyoto', 'city', 'KYO'),
('JP', 'YOK', 'YOK', 'Yokohama', 'city', 'YOK'),
('JP', 'NAG', 'NAG', 'Nagoya', 'city', 'NAG'),
('JP', 'SAP', 'SAP', 'Sapporo', 'city', 'SAP'),
('JP', 'KOB', 'KOB', 'Kobe', 'city', 'KOB'),
('JP', 'FUK', 'FUK', 'Fukuoka', 'city', 'FUK'),
('JP', 'SEN', 'SEN', 'Sendai', 'city', 'SEN'),
('JP', 'HIR', 'HIR', 'Hiroshima', 'city', 'HIR');

-- ============================================================
-- GERMANY - States and Major Cities
-- ============================================================

-- German States (Länder)
INSERT INTO `locations` (`country_code`, `region_code`, `name`, `type`, `parent_code`) VALUES
('DE', 'BW', 'Baden-Württemberg', 'region', 'DE'),
('DE', 'BY', 'Bavaria', 'region', 'DE'),
('DE', 'BE', 'Berlin', 'region', 'DE'),
('DE', 'BB', 'Brandenburg', 'region', 'DE'),
('DE', 'HB', 'Bremen', 'region', 'DE'),
('DE', 'HH', 'Hamburg', 'region', 'DE'),
('DE', 'HE', 'Hesse', 'region', 'DE'),
('DE', 'MV', 'Mecklenburg-Vorpommern', 'region', 'DE'),
('DE', 'NI', 'Lower Saxony', 'region', 'DE'),
('DE', 'NW', 'North Rhine-Westphalia', 'region', 'DE'),
('DE', 'RP', 'Rhineland-Palatinate', 'region', 'DE'),
('DE', 'SL', 'Saarland', 'region', 'DE'),
('DE', 'SN', 'Saxony', 'region', 'DE'),
('DE', 'ST', 'Saxony-Anhalt', 'region', 'DE'),
('DE', 'SH', 'Schleswig-Holstein', 'region', 'DE'),
('DE', 'TH', 'Thuringia', 'region', 'DE');

-- Major German Cities
INSERT INTO `locations` (`country_code`, `region_code`, `city_code`, `name`, `type`, `parent_code`) VALUES
('DE', 'BE', 'BER', 'Berlin', 'city', 'BE'),
('DE', 'HH', 'HAM', 'Hamburg', 'city', 'HH'),
('DE', 'BY', 'MUN', 'Munich', 'city', 'BY'),
('DE', 'NW', 'COL', 'Cologne', 'city', 'NW'),
('DE', 'HE', 'FRA', 'Frankfurt', 'city', 'HE'),
('DE', 'NW', 'DUS', 'Düsseldorf', 'city', 'NW'),
('DE', 'BW', 'STU', 'Stuttgart', 'city', 'BW'),
('DE', 'NW', 'DOR', 'Dortmund', 'city', 'NW'),
('DE', 'NW', 'ESS', 'Essen', 'city', 'NW'),
('DE', 'SN', 'LEI', 'Leipzig', 'city', 'SN');

-- ============================================================
-- SINGAPORE - Districts
-- ============================================================

-- Singapore Regions (Districts)
INSERT INTO `locations` (`country_code`, `region_code`, `name`, `type`, `parent_code`) VALUES
('SG', 'CEN', 'Central Region', 'region', 'SG'),
('SG', 'EAS', 'East Region', 'region', 'SG'),
('SG', 'NOR', 'North Region', 'region', 'SG'),
('SG', 'NE', 'North-East Region', 'region', 'SG'),
('SG', 'WES', 'West Region', 'region', 'SG');

-- Singapore Areas
INSERT INTO `locations` (`country_code`, `region_code`, `city_code`, `name`, `type`, `parent_code`) VALUES
('SG', 'CEN', 'CBD', 'Central Business District', 'city', 'CEN'),
('SG', 'CEN', 'ORC', 'Orchard', 'city', 'CEN'),
('SG', 'EAS', 'TAM', 'Tampines', 'city', 'EAS'),
('SG', 'NOR', 'WOO', 'Woodlands', 'city', 'NOR'),
('SG', 'NE', 'SEN', 'Sengkang', 'city', 'NE'),
('SG', 'WES', 'JUR', 'Jurong', 'city', 'WES');

-- ============================================================
-- Add more countries as needed...
-- This is a comprehensive base that can be extended
-- ============================================================