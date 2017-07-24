#!/usr/bin/perl
use strict;
use warnings;

use Data::Dumper;
use DBI;
use Config::Properties;
use File::Basename;
use Time::Format;
use Time::Piece;
use LWP::UserAgent;
use HTML::TreeBuilder::XPath;

our $debug = 0;
our @debug_ids = ();

our $host;
our $port;
our $username;
our $password;
our $db;
our $https;

sub log_status{
	my ($msg) = @_;

	print Time::Piece::localtime->strftime('%Y/%m/%d %H:%M:%S');
	print " $msg\n";
}

sub fetch {
	my ($url) = @_;

	my $browser = LWP::UserAgent->new;
	my $req = HTTP::Request->new(GET => $url);
	$req->authorization_basic($username, $password);
	my $response = $browser->request($req);
	if(!$response->is_success) {
		print(Dumper($response));
		die "fail for $url!";
	}
	return $response->decoded_content;
}

sub parse_table {
	my ($index, $input) = @_;

	my $tree = HTML::TreeBuilder::XPath->new;
	$tree->parse_content($input);

	my @rownames = ();
	my @rows = ();

	my $tables = $tree->findnodes("//table");
	my $table = $tables->get_node($index);
	my $nodes = $table->findnodes(".//tr");
	foreach my $line ($nodes->get_nodelist) {
		my $subnodes = $line->findnodes('.//td');
		if($#rownames == -1) {
			for my $a (1 .. $subnodes->size()) {
				my $value = $subnodes->get_node($a)->string_value;
				$value =~ s/[^a-z0-9\.A-Z\/\%\- ]//g;
				$value =~ s/^ *//g;
				$value =~ s/ *$//g;
				push(@rownames, $value);
			}
			push(@rownames, 'Regen')
		}
		else {
			my %row = ();
			for my $a (1 .. $subnodes->size()) {
				my $value = $subnodes->get_node($a)->string_value;
				$value =~ s/[^a-z0-9\.A-Z\/\%\- ]//g;
				$value =~ s/^ *//g;
				$value =~ s/ *$//g;
				$row{$rownames[$a-1]} = $value;
				$a++;
			}

			$row{'Regen'} = 0;
			if($subnodes->get_node($subnodes->size())->string_value =~ m/#/) {
				$row{'Regen'} = 1;
			}

			push(@rows, \%row);
		}
	}

	return @rows;
}

sub format_timestamp {
	my ($input) = @_;

	if($input =~ /^([0-9]+) min +([0-9]+) sek$/) {
		return time() - $1*60 - $2;
	}

	die $input;
}

sub log_raw_data {
	my ($db, $data, $url) = @_;

#	my $stmt = $db->prepare('INSERT INTO raw_data (data, url) VALUES (?, ?)');
#	$stmt->execute(($data, $url));
#	$stmt->finish();

	if($debug) {
		log_status("Raw data:");
		log_status("$data\n");
	}
}

sub get_values {
	my ($sensor) = @_;

	log_status("Querying status for sensor $sensor");

	my ($prematch, $match);
	if(not $debug or $#debug_ids lt 0) {
		my $schema = ($https == '1') ? 'https' : 'http';
		my $url = "$schema://$host:$port/history$sensor.cgi";
		$prematch = fetch($url);
		log_raw_data($db, $prematch, $url);
	}
	else {
		$prematch = fetch_raw_from_db($db);
	}

	my @data = parse_table(1, $prematch);
	for my $a (0 .. $#data) {
		$data[$a]{'Zeitstempel'} = format_timestamp($data[$a]{'Zeitstempel'});
		$data[$a]{'Temperatur'} =~ s/[^0-9\.\-]//g;
		$data[$a]{'Luftfeuchtigkeit'} =~ s/[^0-9]//g;
		if(exists($data[$a]{'Windgeschwindigkeit'} )) {
			$data[$a]{'Windgeschwindigkeit'} =~ s/[^0-9\.\-]//g;
		}
		else {
			$data[$a]{'Windgeschwindigkeit'} = -1;
		}
		if(exists($data[$a]{'Regenmenge'} )) {
			$data[$a]{'Regenmenge'} =~ s/[^0-9\.\-]//g;
		}
		else {
			$data[$a]{'Regenmenge'} = -1;
		}
	}

	return reverse(@data);
}

sub fetch_raw_from_db {
	my ($db) = @_;

	my $id = shift(@debug_ids);
	log_status("Fetching raw data from database with id $id");

	my $stmt = $db->prepare("SELECT data FROM raw_data WHERE id = ?");
	$stmt->execute(($id));
	if($stmt->rows == 0) {
		log_status("Unknown id: $id");
		die();
	}
	my @result = $stmt->fetchrow_array();
	$stmt->finish();

	return $result[0];
}

log_status("Cronjob started");

chdir(dirname($0));

open my $fh, '<', 'config.properties' or die "Can't read properties file";
my $config = Config::Properties->new();
$config->load($fh);

$username = $config->getProperty('username');
$password = $config->getProperty('password');
$host = $config->getProperty('host');
$port = $config->getProperty('port');
$https = $config->getProperty('https');

my $sensors_mapping = $config->getProperty('sensors_mapping');
$sensors_mapping =~ s/^\s+//;

my @sensor_ids = split(/;/, $sensors_mapping);
if(@sensor_ids != 9) {
	die "Invalid value for 'sensors_mapping'";
}

my $time_margin = $config->getProperty('time_margin');

my $db_host = $config->getProperty('db_host');
my $db_username = $config->getProperty('db_username');
my $db_password = $config->getProperty('db_password');
my $db_database = $config->getProperty('db_database');

my $bullshit_threshold = 20;

my %attributes = (RaiseError => 1,
	AutoCommit => 0);
$db = DBI->connect("DBI:mysql:$db_database;host=$db_host", $db_username, $db_password, \%attributes) || die('Could not connect to database');

my $argc = @ARGV;
if($argc gt 0 and $ARGV[0] eq '--test') {
	log_status("Initiating debug mode...");

	$debug = 1;
	if($argc gt 2 and $ARGV[1] eq '--ids') {
		for my $a (2 .. $#ARGV) {
			push(@debug_ids, $ARGV[$a]);
		}

		log_status("Using entries from database table raw_data with IDs: ");
		log_status(Dumper(@debug_ids));
	}
}

my $stmt;

if(!$debug) {
	$stmt = $db->prepare('INSERT INTO cronjob_executions () VALUES ()');
	$stmt->execute();
	$stmt->finish();
}

$stmt = $db->prepare('SELECT id, short FROM sensor_values');
$stmt->execute();
my $value_ids = {};
while(my @result = ($stmt->fetchrow_array())) {
	$value_ids->{$result[1]} = $result[0];	
}
$stmt->finish();

log_status("Connecting");

my ($t, $prematch, $match);

if(not $debug or $#debug_ids lt 0) {
	my $schema = ($https == '1') ? 'https' : 'http';
	my $url = "$schema://$host:$port/ipwe.cgi";
	$prematch = fetch($url);
	log_raw_data($db, $prematch, $url);
}
else {
	$prematch = fetch_raw_from_db($db);
}
my @data = parse_table(2, $prematch);
if($debug) {
	log_status("Values parsed from table:");
	print(Dumper(@data));
}

for($a=0; $a<=$#data; $a++) {
	my @values;
	if($data[$a]{'Sensortyp'} ne '') {
		if(not $debug and $a eq 8) {
			my $stmt1;
			my $stmt2;
			if($data[$a]{'Regen'} eq '1') {
				log_status('Recording rain');
				$stmt1 = $db->prepare("INSERT INTO sensor_data (timestamp, sensor, what, value) VALUES (NOW(), 9, 7, 1)");
				$stmt2 = $db->prepare("INSERT INTO sensor_cache (timestamp, sensor, what, value) VALUES (NOW(), 9, 7, 1)");
			}
			else {
				log_status('Recording no rain');
				$stmt1 = $db->prepare("INSERT INTO sensor_data (timestamp, sensor, what, value) VALUES (NOW(), 9, 7, 0)");
				$stmt2 = $db->prepare("INSERT INTO sensor_cache (timestamp, sensor, what, value) VALUES (NOW(), 9, 7, 1)");
			}

			$stmt1->execute();
			$stmt1->finish();

			$stmt2->execute();
			$stmt2->finish();
		}

		@values = get_values($a);
		if($debug) {
			log_status("Values parsed from table:");
			print(Dumper(@values));
			next;
		}
		for my $value (@values) {
			my $sensor;
			if($a eq 8) { # Kombisensor
				$sensor = 8;
			}
			else {
			       	$sensor = $data[$a]{'Adresse'};
			}
			my $timestamp = $value->{'Zeitstempel'};
			my $type = $data[$a]{'Sensortyp'};
			my $description = $data[$a]{'Beschreibung'};

			my $sensor_id = $sensor_ids[$sensor];

			$stmt = $db->prepare('SELECT COUNT(*) `count` FROM sensor_cache WHERE sensor = ? AND UNIX_TIMESTAMP(timestamp) > ? AND UNIX_TIMESTAMP(timestamp) < ?');
			$stmt->execute(($sensor_id, $timestamp-$time_margin, $timestamp+$time_margin));
			my @result = $stmt->fetchrow_array();
			$stmt->finish();
			if($result[0] == 0) {
				$stmt = $db->prepare('SELECT value, UNIX_TIMESTAMP(timestamp) timestamp FROM sensor_cache WHERE sensor = ? AND what = ? ORDER BY id DESC LIMIT 0, 1');

				my $stmt1 = $db->prepare('INSERT INTO sensor_data (timestamp, sensor, what, value) VALUES (FROM_UNIXTIME(?), ?, ?, ?)');
				my $stmt2 = $db->prepare('INSERT INTO sensor_cache (timestamp, sensor, what, value) VALUES (FROM_UNIXTIME(?), ?, ?, ?)');

				$stmt->execute(($sensor_id, $value_ids->{'temp'}));
				@result = $stmt->fetchrow_array();
				my $value_outdated = (time() - $result[1]) > 3600; # TODO magic number
				if(!@result or abs($value->{'Temperatur'}-$result[0]) < $bullshit_threshold or $value_outdated) {
					$stmt1->execute(($timestamp, $sensor_id, $value_ids->{'temp'}, $value->{'Temperatur'}));
					$stmt2->execute(($timestamp, $sensor_id, $value_ids->{'temp'}, $value->{'Temperatur'}));
				}
				else {
					log_status('Value of temperature (' . $value->{'Temperatur'} . ') ignored due to bullshit threshold');
				}

				$stmt->execute(($sensor_id, $value_ids->{'humid'}));
				@result = $stmt->fetchrow_array();
				if(!@result or abs($value->{'Luftfeuchtigkeit'}-$result[0]) < $bullshit_threshold or $value_outdated) {
					$stmt1->execute(($timestamp, $sensor_id, $value_ids->{'humid'}, $value->{'Luftfeuchtigkeit'}));
					$stmt2->execute(($timestamp, $sensor_id, $value_ids->{'humid'}, $value->{'Luftfeuchtigkeit'}));
				}
				else {
					log_status('Value of humidity (' . $value->{'Luftfeuchtigkeit'} . ') ignored due to bullshit threshold');
				}

				if($value->{'Windgeschwindigkeit'} > -1) {
#					$stmt->execute(($sensor_id, $value_ids->{'Wind speed'}));
#					@result = $stmt->fetchrow_array();
#					if(!@result or abs($value->{'Windgeschwindigkeit'}-$result[0]) < $bullshit_threshold) {
					if($value->{'Windgeschwindigkeit'} < 150) {
						$stmt1->execute(($timestamp, $sensor_id, $value_ids->{'wind'}, $value->{'Windgeschwindigkeit'}));
						$stmt2->execute(($timestamp, $sensor_id, $value_ids->{'wind'}, $value->{'Windgeschwindigkeit'}));
					}
					else {
						log_status('Value of wind speed (' . $value->{'Windgeschwindigkeit'} . ') ignored due to bullshit threshold');
					}
				}
				if($value->{'Regenmenge'} > -1) {
					my $cur = $value->{'Regenmenge'};
					my $stmt3 = $db->prepare('SELECT ROUND(MIN(value), 7) min, ROUND(MAX(value), 7) max FROM sensor_cache WHERE sensor = ? AND what = ? AND DATE_ADD(timestamp, INTERVAL 1 HOUR) > NOW()');
					log_status($sensor_id . " " . $value_ids->{'rain'});
					$stmt3->execute(($sensor_id, $value_ids->{'rain'}));
					@result = $stmt3->fetchrow_array();
					log_status(Dumper(@result));
					my $min = (defined($result[0]) and $result[0]<$cur) ? $result[0] : $cur; # minimum value in the last hour
					my $max = (not defined($result[1]) or $result[1]<$cur) ? $cur : $result[1]; # maximum value in the last hour
					$stmt3->finish();

					$stmt3 = $db->prepare('SELECT value FROM sensor_cache WHERE sensor = ? AND what = ? AND DATE_ADD(timestamp, INTERVAL 1 HOUR) > NOW() ORDER BY id ASC LIMIT 0, 1');
					$stmt3->execute(($sensor_id, $value_ids->{'rain'}));
					@result = $stmt3->fetchrow_array();
					my $first = defined($result[0]) ? $result[0] : $cur; # first value in the last hour

					log_status("$first - $cur - $min - $max");
					my $rain_value;
					if($min < $first) { # value of precipitation has been reset in the last hour
						# the amount of precipitation before the reset (that's $max-$first as $max was the last value before the reset)
						# and the amount after the reset (that's $cur-$min as $min was the first value after the reset)
						$rain_value = $cur+$max-$first-$min;
					}
					else {
						$rain_value = $max-$min;
					}
					if($rain_value < 0) {
						$rain_value = 0;
					}

					if($rain_value < 100) {
						$stmt1->execute(($timestamp, $sensor_id, $value_ids->{'rain'}, $rain_value));
						$stmt2->execute(($timestamp, $sensor_id, $value_ids->{'rain'}, $value->{'Regenmenge'}));
					}
					else {
						log_status('Calculated value of precipitation (' . $rain_value . ') ignored due to bullshit threshold');
					}

				}

				$stmt1->finish();
				$stmt2->finish();
				$stmt->finish();
			}
		}
	}
}

$stmt = $db->prepare('DELETE FROM sensor_cache WHERE DATE_SUB(NOW(), INTERVAL 1 DAY) > timestamp');
$stmt->execute();
$stmt->finish();

$db->commit();

$db->disconnect();

log_status("Cronjob finished");

