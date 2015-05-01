<?php

function rewrite_xml($xml) {
	$new_xml = array();

	if($xml->attributes()->count() > 0) {
		foreach($xml->attributes() as $attribute) {
			$value = $attribute->__toString();
			if(is_numeric($value)) {
				$new_xml[$attribute->getName()] = doubleval($value);
			}
			else {
				$new_xml[$attribute->getName()] = $value;
			}
		}
	}
	$element_counts = array();
	foreach($xml->children() as $element) {
		if(!isset($element_counts[$element->getName()])) {
			$element_counts[$element->getName()] = 1;
		}
		else {
			$element_counts[$element->getName()]++;
		}
	}
	foreach($xml->children() as $element) {
		if($element_counts[$element->getName()] > 1) {
			if(!isset($new_xml[$element->getName()])) {
				$new_xml[$element->getName()] = array();
			}
			$new_xml[$element->getName()][] = rewrite_xml($element);
		}
		else {
			$new_xml[$element->getName()] = rewrite_xml($element);
		}
	}

	$value = (string) $xml;
	if(trim($value) != '') {
		$new_xml['value'] = $value;
	}

	return $new_xml;
}

header('Content-Type: application/json');
$xml = simplexml_load_string($data);
$xml = rewrite_xml($xml);
echo json_encode($xml);

