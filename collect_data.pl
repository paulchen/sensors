#!/usr/bin/perl
use strict;
use warnings;

use Net::Telnet;
use Data::Dumper;
use DBI;
use Config::Properties;
use File::Basename;
use Time::Format;

sub log_status{
	my ($msg) = @_;
	print "$time{'yyyy/mm/dd hh:mm:ss'} $msg\n";
}

sub telnet_command {
	my ($t, $cmd) = @_;
	for(my $a=0; $a<length($cmd); $a++) {
		$t->put(substr($cmd, $a, 1));
		select(undef, undef, undef, .1);
	}
	$t->print('');
}

sub parse_table {
	my ($input) = @_;
	my @lines = split("\n", $input);
	my @rownames = ();
	my @rows = ();

	foreach my $line (@lines) {
		if($line =~ m/^ \|[^-].*[^-]\|[^\|]*/) {
			my @cells = split(/\|/, $line);
			shift @cells;

			if($#rownames == -1) {
				for my $a (0 .. $#cells) {
					my $value = $cells[$a];
					$value =~ s/[^a-z0-9\.A-Z\/\%\- ]//g;
					$value =~ s/^ *//g;
					$value =~ s/ *$//g;
					push(@rownames, $value);
				}
			}
			else {
				my %row = ();
				for my $a (0 .. $#cells) {
					my $value = $cells[$a];
					$value =~ s/[^a-z0-9\.A-Z\/\%\- ]//g;
					$value =~ s/^ *//g;
					$value =~ s/ *$//g;
					$row{$rownames[$a]} = $value;
				}

				push(@rows, \%row);
			}
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
	my ($db, $data) = @_;

	my $stmt = $db->prepare('INSERT INTO raw_data (data) VALUES (?)');
	$stmt->execute(($data, ));
}

sub get_values {
	my ($t, $sensor, $db) = @_;

	log_status("Querying status for sensor $sensor");

	telnet_command($t, "sensor $sensor");
	my ($prematch, $match) = $t->waitfor('/IPWE1> /');
	log_raw_data($db, $prematch);
	my @data = parse_table($prematch);
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

log_status("Cronjob started");

chdir(dirname($0));

open my $fh, '<', 'config.properties' or die "Can't read properties file";
my $config = Config::Properties->new();
$config->load($fh);

my $username = $config->getProperty('username');
my $password = $config->getProperty('password');
my $host = $config->getProperty('host');
my $port = $config->getProperty('port');

my $time_margin = $config->getProperty('time_margin');

my $db_host = $config->getProperty('db_host');
my $db_username = $config->getProperty('db_username');
my $db_password = $config->getProperty('db_password');
my $db_database = $config->getProperty('db_database');

my $bullshit_threshold = 95;

my $db = DBI->connect("DBI:mysql:$db_database;host=$db_host", $db_username, $db_password) || die('Could not connect to database');

my $stmt = $db->prepare('INSERT INTO cronjob_executions () VALUES ()');
$stmt->execute();
$stmt->finish();

$stmt = $db->prepare('SELECT id, name FROM sensor_values');
$stmt->execute();
my $value_ids = {};
while(my @result = ($stmt->fetchrow_array())) {
	$value_ids->{$result[1]} = $result[0];	
}
$stmt->finish();

log_status("Connecting");

my $t = new Net::Telnet(Timeout => 10);
$t->open(Host => $host, Port => $port);

log_status("Logging in");

$t->waitfor('/Username: /');
telnet_command($t, $username);
$t->waitfor('/Password: /');
telnet_command($t, $password);
$t->waitfor('/IPWE1> /');

log_status("Querying status");

telnet_command($t, 'status');
my ($prematch, $match) = $t->waitfor('/IPWE1> /');
log_raw_data($db, $prematch);
my @data = parse_table($prematch);

for($a=0; $a<=$#data; $a++) {
	my @values;
	if($data[$a]{'Typ'} ne '') {
		@values = get_values($t, $a, $db);
		for my $value (@values) {
			my $sensor = $data[$a]{'Adresse'};
			my $timestamp = $value->{'Zeitstempel'};
			my $type = $data[$a]{'Typ'};
			my $description = $data[$a]{'Beschreibung'};

			$stmt = $db->prepare('SELECT id FROM sensors WHERE sensor = ? AND type = ? AND description = ?');
			$stmt->execute(($sensor, $type, $description));
			my @result = $stmt->fetchrow_array();
			$stmt->finish();
			if(!@result) {
				$stmt = $db->prepare('INSERT INTO sensors (sensor, type, description) VALUES (?, ?, ?)');
				$stmt->execute(($sensor, $type, $description));
				$stmt->finish();
				$stmt = $db->prepare('SELECT id FROM sensors WHERE sensor = ? AND type = ? AND description = ?');
				$stmt->execute(($sensor, $type, $description));
				@result = $stmt->fetchrow_array();
				$stmt->finish();
			}
			my $sensor_id = $result[0];

			$stmt = $db->prepare('SELECT COUNT(*) `count` FROM sensor_data WHERE sensor = ? AND UNIX_TIMESTAMP(timestamp) > ? AND UNIX_TIMESTAMP(timestamp) < ?');
			$stmt->execute(($sensor_id, $timestamp-$time_margin, $timestamp+$time_margin));
			@result = $stmt->fetchrow_array();
			$stmt->finish();
			if($result[0] == 0) {
				$stmt = $db->prepare('INSERT INTO sensor_data (timestamp, sensor, what, value) VALUES (FROM_UNIXTIME(?), ?, ?, ?)');

				if($value->{'Temperatur'} < $bullshit_threshold || $value->{'Luftfeuchtigkeit'} < $bullshit_threshold) {
					$stmt->execute(($timestamp, $sensor_id, $value_ids->{'Temperature'}, $value->{'Temperatur'}));
					$stmt->execute(($timestamp, $sensor_id, $value_ids->{'Humidity'}, $value->{'Luftfeuchtigkeit'}));
					if($value->{'Windgeschwindigkeit'} > -1) {
						$stmt->execute(($timestamp, $sensor_id, $value_ids->{'Wind speed'}, $value->{'Windgeschwindigkeit'}));
					}
					if($value->{'Regenmenge'} > -1) {
						$stmt->execute(($timestamp, $sensor_id, $value_ids->{'Precipitation'}, $value->{'Regenmenge'}));
					}
				}
				$stmt->finish();
			}
		}
	}
}

$db->disconnect();

log_status("Cronjob finished");

