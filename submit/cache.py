#!/usr/bin/python3

import os, requests, logging, time, subprocess, threading, configparser, MySQLdb, urllib3

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

logfile = path + '../log/cache.log'


logger = logging.getLogger()
handler = logging.FileHandler(logfile)
handler.setFormatter(logging.Formatter('%(asctime)s %(name)-12s %(levelname)-8s %(message)s'))
logger.addHandler(handler)
logger.setLevel(logging.DEBUG)

db_settings = settings['database']
db = MySQLdb.connect(host=db_settings['hostname'], user=db_settings['username'], passwd=db_settings['password'], db=db_settings['database'], autocommit=True)

curs = db.cursor(MySQLdb.cursors.DictCursor)
curs2 = db.cursor()

logger.info('Searching for rows that have not been submitted')

# fix for fucking DST problems
# see https://stackoverflow.com/questions/5748547/mysql-date-sub-date-add-that-accounts-for-dst/42478811#42478811
curs.execute("SELECT `id`, `server`, `sensors`, `whats`, `values`, UNIX_TIMESTAMP(`timestamp`) AS `timestamp` FROM cache WHERE submitted IS NULL AND `timestamp` < CONVERT_TZ(DATE_SUB(CONVERT_TZ(NOW(), @@session.time_zone, '+0:00'), INTERVAL 10 MINUTE), '+0:00', @@session.time_zone) ORDER BY `id` ASC LIMIT 100")
for row in curs:
    server = servers[row['server']]
    url = server['url'] + '/api/'
    s = requests.session()
    s.auth = (server['username'], server['password'])

    logger.info('Sending sensors %s, whats %s, values %s, timestamp %s to server %s; row id: %s', row['sensors'], row['whats'], row['values'], row['timestamp'], row['server'], row['id'])

    try:
        resp = s.get(url, params={'action': 'submit', 'sensors': row['sensors'], 'whats': row['whats'], 'values': row['values'], 'timestamp': row['timestamp']}, timeout=30)

        content = resp.text
        if content == 'ok':
            logger.info('Setting row %s to submitted', row['id'])
            curs2.execute('UPDATE cache SET `submitted` = NOW() WHERE `id` = %s', (row['id'], ))
        elif resp.status_code == 422:
            logger.info('Setting row %s to submitted (was ignored by server)', row['id'])
            curs2.execute('UPDATE cache SET `submitted` = NOW() WHERE `id` = %s', (row['id'], ))

    except urllib3.exceptions.ConnectTimeoutError:
        logger.error('Timeout during update')

    except urllib3.exceptions.ReadTimeoutError:
        logger.error('Timeout during update')

    except requests.exceptions.RequestException:
        logger.error('Error during update')


curs2.close()

logger.info('Deleting outdated rows')

curs.execute('DELETE FROM cache WHERE submitted IS NOT NULL AND `timestamp` < DATE_SUB(NOW(), INTERVAL 14 DAY)')

curs.close()
db.close()

logger.info('Execution completed')

