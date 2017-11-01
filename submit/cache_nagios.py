#!/usr/bin/python3

import os, requests, MySQLdb, sys, configparser

path = os.path.dirname(os.path.abspath(__file__)) + '/'
settings = configparser.ConfigParser()
settings.read(path + 'submit.ini')

db_settings = settings['database']
db = MySQLdb.connect(host=db_settings['hostname'], user=db_settings['username'], passwd=db_settings['password'], db=db_settings['database'], autocommit=True)

curs = db.cursor(MySQLdb.cursors.DictCursor)

curs.execute('SELECT COUNT(*) number FROM cache WHERE submitted IS NULL and `timestamp` < DATE_SUB(NOW(), INTERVAL 20 MINUTE)');
row = curs.fetchone()
rows = row['number']
curs.close()
db.close()

print('%s row(s) that have not yet been submitted' % (rows)) 
if row['number'] > 10:
    sys.exit(2)
if row['number'] > 0:
    sys.exit(1)

