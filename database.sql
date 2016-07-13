-- phpMyAdmin SQL Dump
-- version 3.4.5deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 17. Feb 2012 um 22:04
-- Server Version: 5.1.58
-- PHP-Version: 5.3.6-13ubuntu3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Datenbank: `sensor_data`
--

-- --------------------------------------------------------

--
-- Table structure for table `api_accounts`
--

CREATE TABLE IF NOT EXISTS `api_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` text NOT NULL,
  `hash` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `battery_changes`
--

CREATE TABLE IF NOT EXISTS `battery_changes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sensor` (`sensor`),
  KEY `sensor_2` (`sensor`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensors`
--

CREATE TABLE IF NOT EXISTS `sensors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor` int(11) NOT NULL,
  `type` text NOT NULL,
  `description` text NOT NULL,
  `hide` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sensor` (`sensor`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

--
-- Daten für Tabelle `sensors`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensor_cache`
--

CREATE TABLE IF NOT EXISTS `sensor_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `sensor` int(11) NOT NULL,
  `what` int(11) NOT NULL,
  `value` float NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sensor` (`sensor`),
  KEY `what` (`what`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

--
-- Daten für Tabelle `sensor_cache`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensor_data`
--

CREATE TABLE IF NOT EXISTS `sensor_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `sensor` int(11) NOT NULL,
  `what` int(11) NOT NULL,
  `value` float NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sensor` (`sensor`),
  KEY `what` (`what`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

--
-- Daten für Tabelle `sensor_data`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensor_limits`
--

CREATE TABLE IF NOT EXISTS `sensor_limits` (
  `sensor` int(11) NOT NULL,
  `value` int(30) NOT NULL,
  `low_crit` float NOT NULL,
  `low_warn` float NOT NULL,
  `high_warn` float NOT NULL,
  `high_crit` float NOT NULL,
  PRIMARY KEY (`sensor`,`value`),
  KEY `value` (`value`),
  KEY `sensor` (`sensor`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `sensor_limits`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensor_values`
--

CREATE TABLE IF NOT EXISTS `sensor_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `short` varchar(20) NOT NULL,
  `unit` text NOT NULL,
  `format` text NOT NULL,
  `min` float DEFAULT NULL,
  `max` float DEFAULT NULL,
  `decimals` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

--
-- Daten für Tabelle `sensor_values`
--

INSERT INTO `sensor_values` (`id`, `name`, `short`, `unit`, `format`, `min`, `max`, `decimals`) VALUES
(1, 'Temperature', 'temp', 'Celsius', '%s °C', NULL, NULL, 1),
(2, 'Humidity', 'humid', 'Percent', '%s %', 0, 100, 0),
(3, 'Wind speed', 'wind', 'km/h', '%s km/h', 0, NULL, 0),
(4, 'Precipitation', 'rain', 'Millimetres', '%s mm', 0, NULL, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `raw_data`
--

CREATE TABLE IF NOT EXISTS `raw_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cronjob_executions`
--

CREATE TABLE IF NOT EXISTS `cronjob_executions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `munin_graphs`
--

CREATE TABLE IF NOT EXISTS `munin_graphs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` text NOT NULL,
  `row` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `width` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

--
-- Table structure for table `languages`
--

CREATE TABLE IF NOT EXISTS `languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language` varchar(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `language`) VALUES
(1, 'en'),
(2, 'de');

-- --------------------------------------------------------

--
-- Table structure for table `translations`
--

CREATE TABLE IF NOT EXISTS `translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` text NOT NULL,
  `language` int(11) NOT NULL,
  `translation` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lang` (`language`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=54 ;

--
-- Dumping data for table `translations`
--

INSERT INTO `translations` (`id`, `source`, `language`, `translation`) VALUES
(1, 'Sensor status', 2, 'Sensorstatus'),
(2, 'Current sensor state', 2, 'Aktueller Sensorstatus'),
(3, 'Last cronjob run: ', 2, 'Letze Datenaktualisierung: '),
(4, 'Last successful cronjob run: ', 2, 'Letzte erfolgreiche Datenaktualisierung:'),
(5, 'Last page load: ', 2, 'Letzte Seitenaktualisierung: '),
(6, 'Loading...', 2, 'Laden...'),
(7, 'Sensor', 2, 'Sensor'),
(8, 'Value', 2, 'Wert'),
(9, 'Current state', 2, 'Aktueller Zustand'),
(10, 'Current value', 2, 'Aktueller Wert'),
(11, 'Maximum value (24 hours)', 2, 'Höchstwert (24 Stunden)'),
(12, 'Minimum value (24 hours)', 2, 'Tiefstwert (24 Stunden)'),
(13, 'Average value (24 hours)', 2, 'Mittelwert (24 Stunden)'),
(14, 'Current tendency', 2, 'Aktuelle Tendenz'),
(15, 'Temperature', 2, 'Temperatur'),
(16, 'OK', 2, 'OK'),
(17, 'decreasing', 2, 'fallend'),
(18, 'Humidity', 2, 'Humidity'),
(19, 'stable', 2, 'stabil'),
(20, 'Sensor limits', 2, 'Sensorlimits'),
(21, 'Critical', 2, 'Kritisch'),
(22, 'Warning', 2, 'Warnung'),
(23, 'Battery changes', 2, 'Batteriewechsel'),
(24, 'Last battery change', 2, 'Letzer Batteriewechsel'),
(25, 'Days', 2, 'Tage'),
(26, 'Change battery', 2, 'Batterie wechseln'),
(30, 'increasing', 2, 'steigend'),
(31, 'UNKNOWN (no limits set)', 2, 'UNBEKANNT (keine Limits festgelegt)'),
(32, 'UNKNOWN (most recent value is too old)', 2, 'UNBEKANNT (letzter Wert ist zu alt)'),
(33, 'CRITICAL (below limit of %s)', 2, 'KRITISCH (unter %s)'),
(34, 'WARNING (below limit of %s)', 2, 'WARNUNG (unter %s)'),
(35, 'CRITICAL (above limit of %s)', 2, 'KRITISCH (über %s)'),
(36, 'WARNING (above limit of %s)', 2, 'WARNUNG (über %s)'),
(50, '%s day(s)', 2, '%s Tag(e)');

-- --------------------------------------------------------

--
-- Table structure for table `sensor_display_names`
--

CREATE TABLE IF NOT EXISTS `sensor_display_names` (
  `sensor` int(11) NOT NULL,
  `language` int(11) NOT NULL,
  `name` text NOT NULL,
  PRIMARY KEY (`sensor`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sensor_display_names`
--

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `sensor_cache`
--
ALTER TABLE `sensor_cache`
  ADD CONSTRAINT `sensor_cache_ibfk_2` FOREIGN KEY (`what`) REFERENCES `sensor_values` (`id`),
  ADD CONSTRAINT `sensor_cache_ibfk_1` FOREIGN KEY (`sensor`) REFERENCES `sensors` (`id`);

--
-- Constraints der Tabelle `sensor_data`
--
ALTER TABLE `sensor_data`
  ADD CONSTRAINT `sensor_data_ibfk_2` FOREIGN KEY (`what`) REFERENCES `sensor_values` (`id`),
  ADD CONSTRAINT `sensor_data_ibfk_1` FOREIGN KEY (`sensor`) REFERENCES `sensors` (`id`);

--
-- Constraints der Tabelle `sensor_limits`
--
ALTER TABLE `sensor_limits`
  ADD CONSTRAINT `sensor_limits_ibfk_2` FOREIGN KEY (`value`) REFERENCES `sensor_values` (`id`),
  ADD CONSTRAINT `sensor_limits_ibfk_1` FOREIGN KEY (`sensor`) REFERENCES `sensors` (`id`);

--
-- Constraints for table `battery_changes`
--
ALTER TABLE `battery_changes`
  ADD CONSTRAINT `battery_changes_ibfk_1` FOREIGN KEY (`sensor`) REFERENCES `sensors` (`id`);

--
-- Constraints for table `translations`
--
ALTER TABLE `translations`
  ADD CONSTRAINT `translations_ibfk_1` FOREIGN KEY (`language`) REFERENCES `languages` (`id`);

--
-- Constraints for table `sensor_display_names`
--
ALTER TABLE `sensor_display_names`
  ADD CONSTRAINT `sensor_display_names_ibfk_2` FOREIGN KEY (`language`) REFERENCES `languages` (`id`),
  ADD CONSTRAINT `sensor_display_names_ibfk_1` FOREIGN KEY (`sensor`) REFERENCES `sensors` (`id`);

