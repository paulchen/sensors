#!/usr/bin/python3

import serial, sys, os, configparser, threading, requests, time


port = '/dev/ttyUSB0'


path = os.path.dirname(os.path.abspath(__file__)) + '/'

settings = configparser.ConfigParser()
settings.read(path + 'submit.ini')

servers = []
for key in settings['servers']:
    server_section = settings[settings['servers'][key]]
    server_info = {}
    for name in server_section:
        server_info[name] = server_section[name]
    servers.append(server_info)

sensor_mapping = settings['sensor_mapping']['mapping'].split(';')



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


def submit_value(server, sensor_string, what_string, value_string):
    start_time = time.time()

    url = server['url'] + '/api/'
    s = requests.session()
    s.auth = (server['username'], server['password'])

    try:
        resp = s.get(url, params={'action': 'submit', 'sensors': sensor_string, 'whats': what_string, 'values': value_string}, timeout=30)
        content = resp.text
        if content != 'ok':
            raise requests.exceptions.RequestException
    except requests.exceptions.RequestException:
#        logger.error('Error during update')
        return

    end_time = time.time()

#    index = 0
#    for what in whats:
#        value = values[index]
#        logger.info('Submitted %s %s of sensor %s to %s successfully in %s seconds', what, value, sensor['id'], url, end_time-start_time)
#        index = index + 1



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
        if len(sensor_mapping) <= sensor:
            continue
        mapped_sensor = sensor_mapping[sensor]

        for what, value in data.items():
            sensor_parts.append(str(mapped_sensor))
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

    threads = []
    for server in servers:
        t = threading.Thread(target = submit_value, args = (server, sensor_string, what_string, value_string))
        t.start()
        threads.append(t)

    for t in threads:
        t.join()

