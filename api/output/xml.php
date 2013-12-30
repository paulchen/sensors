<?php

$tidy = new tidy();
$tidy->parseString($data, array('indent' => true, 'input-xml' => true, 'wrap' => 1000), 'utf8');
$tidy->cleanRepair();

header('Content-Type: application/xml');
echo $tidy;

