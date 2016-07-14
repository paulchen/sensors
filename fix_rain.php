<?php

function calculate_total_rain($rain_index) {
	if(count($rain_index) < 2) {
		return 0;
	}

	$overflows = 0;
	for($i=1; $i<count($rain_index); $i++) {
		if($rain_index[$i-1] > $rain_index[$i]) {
			$overflows++;
		}
	}

	print("$overflows\n");

	$first = $rain_index[0];
	$last = $rain_index[count($rain_index) - 1];
	return $last - $first + $overflows * 4096;
}

function test($rain_index, $expected) {
	$rain = calculate_total_rain($rain_index);

	print("$rain $expected\n");
}

test(array(), 0);
test(array(1), 0);
test(array(1, 1), 0);
test(array(1, 0), 4095);
test(array(1, 0, 1), 4096);
test(array(2, 0, 1), 4095);
test(array(2, 0, 1, 0, 1), 8191);
test(array(716, 992, 225, 760, 3311, 5, 87), 7563);

die();

