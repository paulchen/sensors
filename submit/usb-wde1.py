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

        # T/F sensors
        for index in range(0,8):
            check_value(parts, index+3, index, 'temp', values)
            check_value(parts, index+11, index, 'humid', values)

        # combination sensor
        check_value(parts, 19, 8, 'temp', values)
        check_value(parts, 20, 8, 'humid', values)
        check_value(parts, 21, 8, 'wind', values)
        check_value(parts, 22, 8, 'rain_idx', values)
        check_value(parts, 23, 8, 'rain_cur', values)

        sensor_parts = []
        what_parts = []
        value_parts = []

        print(values)

        for sensor, data in values.items():
            for what, value in data.items():
                # TODO sensor mapping?
                sensor_parts.append(str(sensor))
                what_parts.append(what)
                value_parts.append(value)

        if len(sensor_parts) == 0:
            continue

        sensor_string = ';'.join(sensor_parts)
        what_string = ';'.join(what_parts)
        value_string = ';'.join(value_parts)

        print(sensor_string)
        print(what_string)
        print(value_string)


