#!/bin/bash

hamilink=$1
urlresult=$(php /home/pi/Documents/scripts/iptvmerge/hamichannels.php $hamilink)
if [[ $hamilink == *"hamivideo"* ]]; then
  ishamilink = "yes"
fi
#https://tvheadend.org/projects/tvheadend/wiki/Custom_MPEG-TS_Input
#ffmpeg -i """$urlresult""" -f mpegts -tune zerolatency pipe:1
#https://tvheadend.org/projects/tvheadend/wiki/Automatic_IPTV_Network
#https://tvheadend.org/projects/tvheadend/wiki/Custom_MPEG-TS_Input
#https://tvheadend.org/boards/5/topics/24125
#https://tvheadend.org/issues/4869
#https://tvheadend.org/boards/5/topics/33754
# -loglevel debug -report
# -re -fflags +genpts
# > /home/pi/Documents/scripts/iptvmerge/streaming.ts
#"|User-Agent=Mozilla/5.0&referer=https://hamivideo.hinet.net&origin=https://hamivideo.hinet.net"
ffmpeg -re -fflags +genpts -user-agent "Mozilla/5.0" -headers "origin: https://hamivideo.hinet.net" -headers "referer: https://hamivideo.hinet.net" -i """$urlresult""" -c copy -threads 4 -f mpegts -tune zerolatency pipe:1