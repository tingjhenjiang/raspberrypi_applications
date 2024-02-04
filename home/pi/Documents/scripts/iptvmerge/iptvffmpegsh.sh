#!/bin/bash

if [ $# -ne 2 ]; then
  echo "Please provide two arguments: a streaming url and a service name text"
  exit 1
fi

streaming_link=$1
servicename=$2
urlresult=$(/home/tj/Documents/Envs/kodi/bin/python /home/tj/Documents/scripts/kodi_addons/plugin.video.hamivideo/resources/lib/hamivideo/api.py --type hami --churl $streaming_link)
if [[ $urlresult == *"https"* ]] && [[ $? -eq 0 ]]; then
  echo "Command Executed Successfully"
  # ishamilink=$(if [[ $hamilink == *"hamivideo"* ]]; then echo "yes"; else echo "no"; fi)
  # [[ $hamilink == *"hamivideo"* ]] && ishamilink="yes" || ishamilink="no"
  metadata_servicename="service_name=$servicename"
  if [ $streaming_link == *"hamivideo"* ]; then
    metadata_service_provider="service_provider=Hamivideo"
    ffmpeg -loglevel warning -fflags +genpts -user_agent "Mozilla/5.0" -headers "origin: https://hamivideo.hinet.net" -headers "referer: https://hamivideo.hinet.net" -i """$urlresult""" -vcodec copy -acodec copy -threads 4 -f hls -tune zerolatency -metadata "$metadata_servicename" -metadata "$metadata_service_provider" -analyzeduration 5G pipe:1
  else
    metadata_service_provider="service_provider=Other Provider"
    ffmpeg -loglevel warning -fflags +genpts -user_agent "Mozilla/5.0" -i """$urlresult""" -vcodec copy -acodec copy -threads 4 -f hls -tune zerolatency -metadata "$metadata_servicename" -metadata "$metadata_service_provider" -analyzeduration 5G pipe:1
  fi
else
  echo "Command Failed"
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
