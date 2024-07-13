#!/bin/bash

if [ "$1" == "" ]; then
	exit 1
fi

TMP_LOG=/tmp/cleanup.log
LOGFILE=../log/cleanup.log
ERROR=0

fail() {
	echo "Fail!" >> "$TMP_LOG"
	ERROR=1
}

run_script() {
	date >> "$TMP_LOG"
	echo "Running $1..." >> "$TMP_LOG"
	php "$1" "$2" &>> "$TMP_LOG" || fail
	echo "$1 completed" >> "$TMP_LOG"
	date >> "$TMP_LOG"
}

DIRNAME=`dirname "$0"`
cd "$DIRNAME"

rm -f "$TMP_LOG"

run_script duplicates.php "$1"
if [ "$ERROR" -eq "0" ]; then
	run_script cleanup.php "$1"
fi
#if [ "$ERROR" -eq "0" ]; then
#	run_script check.php "$1"
#fi
if [ "$ERROR" -eq "0" ]; then
	run_script delete.php "$1"
fi

cat "$TMP_LOG" >> "$LOGFILE"

if [ "$ERROR" -ne "0" ]; then
	cat "$TMP_LOG"
fi

rm -f "$TMP_LOG"

exit $ERROR

