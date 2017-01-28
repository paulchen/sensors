CREATE TABLE `cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server` text NOT NULL,
  `sensors` text NOT NULL,
  `whats` text NOT NULL,
  `values` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `cache_submitted_idx` (`submitted`),
  KEY `cache_timestamp_index` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

