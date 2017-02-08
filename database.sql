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

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `account_location`
--

CREATE TABLE `account_location` (
  `account` int(11) NOT NULL DEFAULT '0',
  `location` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `graph_group`
--

CREATE TABLE `graph_group` (
  `graph` int(11) NOT NULL DEFAULT '0',
  `group` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `group`
--

CREATE TABLE `group` (
  `id` int(11) NOT NULL,
  `location` int(11) NOT NULL,
  `name` text NOT NULL,
  `visible` tinyint(4) NOT NULL,
  `pos` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `location`
--

CREATE TABLE `location` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `visible` tinyint(4) NOT NULL,
  `pos` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensor_group`
--

CREATE TABLE `sensor_group` (
  `sensor` int(11) NOT NULL DEFAULT '0',
  `group` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `account_location`
--
ALTER TABLE `account_location`
  ADD PRIMARY KEY (`account`,`location`),
  ADD KEY `location` (`location`);

--
-- Indizes für die Tabelle `graph_group`
--
ALTER TABLE `graph_group`
  ADD PRIMARY KEY (`graph`,`group`),
  ADD KEY `group` (`group`);

--
-- Indizes für die Tabelle `group`
--
ALTER TABLE `group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `location` (`location`);

--
-- Indizes für die Tabelle `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `sensor_group`
--
ALTER TABLE `sensor_group`
  ADD PRIMARY KEY (`sensor`,`group`),
  ADD KEY `group` (`group`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `group`
--
ALTER TABLE `group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `location`
--
ALTER TABLE `location`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints der Tabelle `account_location`
--
ALTER TABLE `account_location`
  ADD CONSTRAINT `account_location_ibfk_2` FOREIGN KEY (`location`) REFERENCES `location` (`id`),
  ADD CONSTRAINT `account_location_ibfk_1` FOREIGN KEY (`account`) REFERENCES `api_accounts` (`id`);

--
-- Constraints der Tabelle `graph_group`
--
ALTER TABLE `graph_group`
  ADD CONSTRAINT `graph_group_ibfk_2` FOREIGN KEY (`group`) REFERENCES `group` (`id`),
  ADD CONSTRAINT `graph_group_ibfk_1` FOREIGN KEY (`graph`) REFERENCES `munin_graphs` (`id`);

--
-- Constraints der Tabelle `group`
--
ALTER TABLE `group`
  ADD CONSTRAINT `group_ibfk_1` FOREIGN KEY (`location`) REFERENCES `location` (`id`);

--
-- Constraints der Tabelle `sensor_group`
--
ALTER TABLE `sensor_group`
  ADD CONSTRAINT `sensor_group_ibfk_2` FOREIGN KEY (`group`) REFERENCES `group` (`id`),
  ADD CONSTRAINT `sensor_group_ibfk_1` FOREIGN KEY (`sensor`) REFERENCES `sensors` (`id`);

