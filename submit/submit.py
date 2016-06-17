#!/usr/bin/python3

import os, requests, logging, time, subprocess, threading, configparser

path = os.path.dirname(os.path.abspath(__file__)) + '/'

settings = configparser.ConfigParser()
settings.read(path + 'submit_new.ini')

servers = []
for key in settings['servers']:
    server_section = settings[settings['servers'][key]]
    server_info = {}
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


logfile = path + 'submit_py.log'


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


def submit_value(sensor, value, server, what):
    start_time = time.time()

    url = server['url'] + '/api/'
    s = requests.session()
    s.auth = (server['username'], server['password'])
    try:
        resp = s.get(url, params={'action': 'submit', 'sensor': sensor['id'], 'what': what, 'value': value}, timeout=30)
        content = resp.text
        if content != 'ok':
            raise requests.exceptions.RequestException
    except requests.exceptions.RequestException:
        logger.error('Error while updating %s %s of sensor %s to %s', 'temp', value, sensor['id'], url)
        return

    end_time = time.time()
    logger.info('Submitted %s %s of sensor %s to %s successfully in %s seconds', what, value, sensor['id'], url, end_time-start_time)


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
    for value in values:
        if not is_value_valid(value):
            return

    whats = sensor['values'].split(',')

    threads = []
    for server in servers:
        index = 0
        # TODO multithreading
        for value in values:
            what = whats[index]
            t = threading.Thread(target = submit_value, args = (sensor, value, server, what))
            t.start()
            threads.append(t)
            index = index + 1
    
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



