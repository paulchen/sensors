CREATE TABLE `cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sensors` text NOT NULL,
  `whats` text NOT NULL,
  `values` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

