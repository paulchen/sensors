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
  `display_name` text,
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
  `hide` tinyint NOT NULL DEFAULT 0,
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

