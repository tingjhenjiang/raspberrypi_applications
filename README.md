# Raspberry Pi applications README
settings and applications for utilizing KODI

## Requirements
* Apache
* PHP
* php(version)-curl, php(version)-xml.
* Kodi
* Tvheadend
* youtube-dl

## Environment settings
* Create home/pi/Documents/scripts/iptvmerge/youtube_api_key.txt and put your Youtube API key in it.
* Modify home/pi/Documents/scripts/iptvmerge/tw_yt_live_channels.php `$self_path_in_apache` to match your httpd server settings.

## Run
* Run `php stream.php` to update m3u8 playlist.
* Run `php epg.php` to update EPG.
* Make Tvheadend read iptvstream_tvheadend.m3u as IPTV source. Enable XMLTV epg grabber in tvheadend. Update EPG by running `sh xmltv_to_socket.sh`.