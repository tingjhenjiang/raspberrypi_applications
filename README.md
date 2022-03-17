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
* Change permission of iptvffmpegsh.sh xmltv_to_socket.sh youtube-dl, making them executable.
* Make youtube-dl system-wide executable (e.g., ln -s ./youtube-dl ./usr/sbin/youtube-dl).
* Enable Apache http server support of SSL and proxypass for tvheadend via command: sudo a2enmod ssl; sudo a2enmod proxy; sudo a2enmod proxy_http; sudo a2enmod proxy_connect, and finally enable proxy site for tvheadend via sudo a2ensite.

## Run
* Run `php stream.php` to update m3u8 playlist file(outputs stream.m3u8).
* Run `php epg.php` to update EPG file(outputs epg.xml).
* Make Tvheadend read iptvstream_tvheadend.m3u as IPTV source. Enable XMLTV epg grabber in tvheadend. Update EPG by running `sh xmltv_to_socket.sh`.
