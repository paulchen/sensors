#!/usr/bin/python3

import sys
import Adafruit_DHT


# Parse command line parameters.
sensor_args = { '11': Adafruit_DHT.DHT11,
                '22': Adafruit_DHT.DHT22,
                '2302': Adafruit_DHT.AM2302 }
if len(sys.argv) == 3 and sys.argv[1] in sensor_args:
    sensor = sensor_args[sys.argv[1]]
    pin = sys.argv[2]
else:
    print('usage: sudo ./adafruit_dht.py [11|22|2302] GPIOpin#')
    print('example: sudo ./adafruit_dht.py 2302 4 - Read from an AM2302 connected to GPIO #4')
    sys.exit(1)

humidity1, temperature1 = Adafruit_DHT.read_retry(sensor, pin)
if humidity1 is None or temperature1 is None:
    sys.exit(1)

humidity2, temperature2 = Adafruit_DHT.read_retry(sensor, pin)
if humidity2 is None or temperature2 is None:
    sys.exit(1)


humidity_difference = abs(float(humidity1) - float(humidity2))
temperature_difference = abs(float(temperature1) - float(temperature2))

if temperature_difference > 1 or humidity_difference > 5:
    sys.exit(2)


print("{0:0.1f}\n{1:0.1f}".format(temperature1, humidity1))

