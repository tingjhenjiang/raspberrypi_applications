#!/bin/bash

#https://freetoairamerica.wordpress.com/2014/12/03/some-hints-for-getting-free-to-air-satellite-channels-into-the-electronic-program-guide-in-kodi-or-xbmc-or-another-frontend/
cat /home/pi/Documents/scripts/iptvmerge/epg.xml | socat - UNIX-CONNECT:/home/pi/.hts/tvheadend/epggrab/xmltv.sock
#cat /home/pi/Documents/scripts/iptvmerge/epg.xml | nc -w 5 -U /home/hts/.hts/tvheadend/epggrab/xmltv.sock
#cat /home/pi/Documents/scripts/iptvmerge/epg.xml | curl -d @- -m 5 -X POST –unix-socket /home/hts/.hts/tvheadend/epggrab/xmltv.sock http://127.0.0.1
