#!/usr/bin/python3

#!/usr/bin/python -u

import serial
import sys
import os

port = '/dev/ttyUSB0'


def expect(parts, index, value):
    if parts[index] == value:
        return True

    print('Invalid value at position %s; expected %s, actual %s' % index, value, parts[index])
    return False


def check_value(parts, index, sensor, what, values):
    value = parts[index]
    if value == '':
        return

    if not sensor in values:
        values[sensor] = {}

    values[sensor][what] = value.replace(',', '.')


# open serial line
ser = serial.Serial(port, 9600)
if not ser.isOpen():
        print("Unable to open serial port %s" % port)
        sys.exit(1)

while True:
        line = ser.readline()
        line = line.strip()

        print(line)

        parts = str(line).split(';')
        if len(parts) != 25:
            print('Invalid number of values; expected 25, actual %s' % len(parts))
            continue

        if not expect(parts, 0, "b'$1") or not expect(parts, 1, '1') or not expect(parts, 2, '') or not expect(parts, 24, "0'"):
            continue

        values = {}

        for index in range(0,8):
            check_value(parts, index+3, index, 'temp', values)
            check_value(parts, index+11, index, 'humid', values)

        check_value(parts, 19, 8, 'temp', values)
        check_value(parts, 20, 8, 'humid', values)
        check_value(parts, 21, 8, 'wind', values)
        check_value(parts, 22, 8, 'rain_idx', values)

        print(values)


