#!/usr/bin/python3

import serial, sys, os, configparser, threading, requests, time, logging, oursql


port = '/dev/ttyUSB0'


path = os.path.dirname(os.path.abspath(__file__)) + '/'

settings = configparser.ConfigParser()
settings.read(path + 'submit.ini')

servers = []
for key in settings['servers']:
    server_name = settings['servers'][key]
    server_section = settings[server_name]
    server_info = {'name' : server_name}
    for name in server_section:
        server_info[name] = server_section[name]
    servers.append(server_info)

logfile = path + 'usb-wde1.log'


logger = logging.getLogger()
handler = logging.FileHandler(logfile)
handler.setFormatter(logging.Formatter('%(asctime)s %(name)-12s %(levelname)-8s %(message)s'))
logger.addHandler(handler)
logger.setLevel(logging.DEBUG)


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


def submit_value(server, sensor_parts, what_parts, value_parts):
    start_time = time.time()

    sensor_mapping = server['mapping'].split(';')

    mapped_sensors = []

    for sensor in sensor_parts:
        mapped_sensors.append(str(sensor_mapping[sensor]))

    sensor_string = ';'.join(mapped_sensors)
    what_string = ';'.join(what_parts)
    value_string = ';'.join(value_parts)

    url = server['url'] + '/api/'
    s = requests.session()
    s.auth = (server['username'], server['password'])

    try:
        db_settings = settings['database']
        db = oursql.connect(host=db_settings['hostname'], user=db_settings['username'], passwd=db_settings['password'], db=db_settings['database'])

        curs = db.cursor()
        curs.execute('INSERT INTO cache (`server`, `sensors`, `whats`, `values`) VALUES (?, ?, ?, ?)', (server['name'], sensor_string, what_string, value_string))
        rowid = curs.lastrowid

        logger.info('Submitting values: sensors=%s, whats=%s, values=%s', sensor_string, what_string, value_string)
        resp = s.get(url, params={'action': 'submit', 'sensors': sensor_string, 'whats': what_string, 'values': value_string}, timeout=30)
        content = resp.text
        if content != 'ok':
            raise requests.exceptions.RequestException

        curs.execute('UPDATE cache SET submitted = NOW() WHERE id = ?', (rowid, ))
        curs.close()
        db.close()
        
    except urllib3.exceptions.ConnectTimeoutError:
        logger.error('Timeout during update')
        return

    except urllib3.exceptions.ReadTimeoutError:
        logger.error('Timeout during update')
        return

    except requests.exceptions.RequestException:
        logger.error('Error during update')
        return

    end_time = time.time()



# open serial line
ser = serial.Serial(port, 9600)
if not ser.isOpen():
    logger.error("Unable to open serial port %s", port)
    sys.exit(1)

logger.info('Program startup completed, waiting for datagram')

while True:
    line = ser.readline()
    line = line.strip()

    logger.info('Received datagram: %s', line)

    parts = str(line).split(';')
    if len(parts) != 25:
        logger.error('Invalid number of values; expected 25, actual %s', len(parts))
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

    for sensor, data in values.items():
        for what, value in data.items():
            sensor_parts.append(sensor)
            what_parts.append(what)
            value_parts.append(value)

    if len(sensor_parts) == 0:
        continue

    threads = []
    for server in servers:
        t = threading.Thread(target = submit_value, args = (server, sensor_parts, what_parts, value_parts))
        t.start()
        threads.append(t)

    for t in threads:
        t.join()

    logger.info('Work done here, waiting for next datagram')

