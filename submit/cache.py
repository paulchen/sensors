#!/usr/bin/python3

import os, requests, logging, time, subprocess, threading, configparser, oursql

path = os.path.dirname(os.path.abspath(__file__)) + '/'

settings = configparser.ConfigParser()
settings.read(path + 'submit.ini')

servers = {}
for key in settings['servers']:
    server_name = settings['servers'][key]
    server_section = settings[server_name]
    server_info = {}
    for name in server_section:
        server_info[name] = server_section[name]
    servers[server_name] = server_info

logfile = path + 'cache.log'


logger = logging.getLogger()
handler = logging.FileHandler(logfile)
handler.setFormatter(logging.Formatter('%(asctime)s %(name)-12s %(levelname)-8s %(message)s'))
logger.addHandler(handler)
logger.setLevel(logging.DEBUG)

db_settings = settings['database']
db = oursql.connect(host=db_settings['hostname'], user=db_settings['username'], passwd=db_settings['password'], db=db_settings['database'])

curs = db.cursor(oursql.DictCursor)
curs2 = db.cursor()

logger.info('Searching for rows that have not been submitted')

curs.execute('SELECT `id`, `server`, `sensors`, `whats`, `values`, UNIX_TIMESTAMP(`timestamp`) AS `timestamp` FROM cache WHERE submitted = 0 AND `timestamp` < DATE_SUB(NOW(), INTERVAL 10 MINUTE) ORDER BY `id` ASC LIMIT 100')
while(1):
    row = curs.fetchone()
    if row == None:
        break
    server = servers[row['server']]
    url = server['url'] + '/api/'
    s = requests.session()
    s.auth = (server['username'], server['password'])

    logger.info('Sending sensors %s, whats %s, values %s, timestamp %s to server %s; row id: %s', row['sensors'], row['whats'], row['values'], row['timestamp'], row['server'], row['id'])

    resp = s.get(url, params={'action': 'submit', 'sensors': row['sensors'], 'whats': row['whats'], 'values': row['values'], 'timestamp': row['timestamp']}, timeout=30)

    content = resp.text
    if content == 'ok':
        logger.info('Setting row %s to submitted', row['id'])
        curs2.execute('UPDATE cache SET `submitted` = 1 WHERE `id` = ?', (row['id'], ))

curs2.close()

logger.info('Deleting outdated rows')

curs.execute('DELETE FROM cache WHERE submitted = 1 AND `timestamp` < DATE_SUB(NOW(), INTERVAL 14 DAY)')

curs.close()
db.close()

logger.info('Execution completed')

