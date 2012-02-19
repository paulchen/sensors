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
-- Tabellenstruktur für Tabelle `sensors`
--

CREATE TABLE IF NOT EXISTS `sensors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor` int(11) NOT NULL,
  `type` text NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sensor` (`sensor`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

--
-- Daten für Tabelle `sensors`
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

--
-- Daten für Tabelle `sensor_values`
--

INSERT INTO `sensor_values` (`id`, `name`) VALUES
(1, 'Temperature'),
(2, 'Humidity'),
(3, 'Wind speed'),
(4, 'Precipitation');

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

--
-- Constraints der exportierten Tabellen
--

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
  ADD CONSTRAINT `sensor_limits_ibfk_1` FOREIGN KEY (`sensor`) REFERENCES `sensors` (`sensor`);

