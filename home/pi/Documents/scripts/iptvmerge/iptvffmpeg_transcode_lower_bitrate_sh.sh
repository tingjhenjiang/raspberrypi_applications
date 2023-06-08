#!/bin/bash
# ffmpeg -analyzeduration 1M -probesize 1M -i pipe:0 -bitrate 3000k -bufsize 3000k -c:v libx264 -preset superfast -c:a aac -c:s copy -f mpegts pipe:1
# ffmpeg -n -i pipe: -s 640x360 -c:v h264_v4l2m2m -b:v 400k -c:a copy -b:a 128k -filter:v fps=30 -c:s copy -bufsize 3000k -f mpegts pipe:
ffmpeg -n -i pipe: -s 1280x720 -c:v h264_v4l2m2m -b:v 1500k -c:a copy -b:a 128k -filter:v fps=28 -c:s copy -bufsize 3000k -f mpegts pipe: