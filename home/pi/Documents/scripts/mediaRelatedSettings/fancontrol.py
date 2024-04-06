import RPi.GPIO as GPIO
import argparse

parser = argparse.ArgumentParser()
parser.add_argument("on_or_off", help="Fan on or off in INT",
                    type=int)
args = parser.parse_args()

GPIO.setmode(GPIO.BCM)
GPIO.setup(17, GPIO.OUT)
print(f"fan would be {args.on_or_off}")
GPIO.output(17, args.on_or_off)