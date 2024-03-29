#!/usr/bin/python3

import os, requests, logging, time, subprocess, threading, configparser, MySQLdb, urllib3

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

sensors = []
for key in settings['sensors']:
    sensor_section = settings[settings['sensors'][key]]
    sensor_info = {}
    for name in sensor_section:
        sensor_info[name] = sensor_section[name]
    sensors.append(sensor_info)


logfile = path + '../log/submit_py.log'


logger = logging.getLogger()
handler = logging.FileHandler(logfile)
handler.setFormatter(logging.Formatter('%(asctime)s %(name)-12s %(levelname)-8s %(message)s'))
logger.addHandler(handler)
logger.setLevel(logging.DEBUG)


def get_sensor_value_w1(path):
    f = open(path, 'r')
    for line in f:
        if 't=' in line:
            f.close()
            pos = line.find('t=')
            return str(float(line[pos+2:]) / 1000)
    f.close()
    return None


def get_sensor_value_external(external):
    return subprocess.Popen(external, shell=True, stdout=subprocess.PIPE).stdout.read().decode('UTF-8').strip()


def split_values(data):
    # TODO what about None?
    return data.splitlines()


def get_sensor_value(sensor):
    logger.debug('Querying sensor %s', sensor['id'])
    if 'path' in sensor:
        return split_values(get_sensor_value_w1(sensor['path']))
    if 'external' in sensor:
        return split_values(get_sensor_value_external(sensor['external']))
    return None


def submit_value(sensor, values, server, whats):
    start_time = time.time()

    url = server['url'] + '/api/'
    s = requests.session()
    s.auth = (server['username'], server['password'])

    sensors = ';'.join([sensor['id']] * len(values))
    db_settings = settings['database']

    use_mysql = False
    try:
        db = MySQLdb.connect(host=db_settings['hostname'], user=db_settings['username'], passwd=db_settings['password'], db=db_settings['database'], autocommit=True)
        use_mysql = True

    except:
        logger.error('Unable to connect to MySQL, working without database')

    try:
        if use_mysql:
            curs = db.cursor()
            curs.execute('INSERT INTO cache (`server`, `sensors`, `whats`, `values`) VALUES (%s, %s, %s, %s)', (server['name'], sensors, ';'.join(whats), ';'.join(values)))
            rowid = curs.lastrowid

        resp = s.get(url, params={'action': 'submit', 'sensors': sensors, 'whats': ';'.join(whats), 'values': ';'.join(values)}, timeout=30)

        content = resp.text
        ignored = False
        if content != 'ok' and resp.status_code != 422:
            raise requests.exceptions.RequestException
        if resp.status_code == 422:
            ignored = True

        if use_mysql:
            curs.execute('UPDATE cache SET submitted = NOW() WHERE id = %s', (rowid, ))
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

    index = 0
    for what in whats:
        value = values[index]
        total_time = end_time - start_time
        if ignored:
            logger.info(f"Submitted {what} {value} of sensor {sensor['id']} to {url} in {total_time:.2f} seconds, but value was ignored by the server")
        else:
            logger.info(f"Submitted {what} {value} of sensor {sensor['id']} to {url} successfully in {total_time:.2f} seconds")
        index = index + 1


def is_float(value):
    try:
        float(value)
        return True
    except ValueError:
        return False


def is_value_valid(value):
    if value is None:
        return False
    if not is_float(value):
        return False
    return True


def process_sensor(sensor, servers):
    logger.debug('Processing sensor %s', sensor['id'])
    values = get_sensor_value(sensor)
    if not values:
        logger.debug('No value determined for sensor %s, aborting', sensor['id'])
        return
    for value in values:
        if not is_value_valid(value):
            logger.debug('Value %s for sensor %s is invalid, aborting', value, sensor['id'])
            return

    whats = sensor['values'].split(',')

    if len(values) != len(whats):
        logger.debug('Invalid number of values determined for sensor %s; expected %s, actual %s', sensor['id'], len(whats), len(values))
        return

    threads = []
    for server in servers:
        t = threading.Thread(target = submit_value, args = (sensor, values, server, whats))
        t.start()
        threads.append(t)
    
    for t in threads:
        t.join()

    logger.debug('Processing sensor %s completed', sensor['id'])


logger.debug('Program startup')

threads = []
for sensor in sensors:
    t = threading.Thread(target = process_sensor, args = (sensor, servers))
    t.start()
    threads.append(t)

for t in threads:
    t.join()

logger.debug('Execution completed')



