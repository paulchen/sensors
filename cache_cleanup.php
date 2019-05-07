<?php

require_once(dirname(__FILE__) . '/common.php');

db_query('DELETE FROM sensor_cache WHERE DATE_SUB(NOW(), INTERVAL 1 DAY) > timestamp');

