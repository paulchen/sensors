#!/usr/bin/python3
import Adafruit_BMP.BMP085 as BMP085
import math, sys

if len(sys.argv) != 2:
    sys.exit(1)

altitude = sys.argv[1]

sensor = BMP085.BMP085()

print('{0:0.2f}'.format(sensor.read_temperature()))
print('{0:0.2f}'.format(sensor.read_pressure()/math.exp(-float(altitude)/7990)/100))


