#w_scan -f t -c 'TW' -C 'UTF-16' -T 1 -R 1 -E 0 -L > ~/Documents/scripts/testscan.txt
#outdated dvbscan -out channels ~/Documents/scripts/testscan.txt /usr/share/dvb/dvb-t/tw-All
#outdated scan -v -a 0 -f 0 -d 0 -A 1 -c /usr/share/dvb/dvb-t/tw-All
#dvbv5-scan -a 0 -C TW -d 0 -f 0 -o ~/Documents/scripts/testscan.txt -O CHANNEL /usr/share/dvb/dvb-t/tw-All -v
#dvbv5-scan -a 0 -C TW -d 0 -f 0 -o ~/Documents/scripts/testscan.txt -O CHANNEL /usr/share/dvb/dvb-t/tw-All
dvbv5-scan -a 0 -d 0 -f 0 -o testscanned_autotw_channels.conf -O CHANNEL /usr/share/dvb/dvb-t/auto-Taiwan
dvbv5-scan -a 0 -d 0 -f 0 -o testscanned_twall_channels.conf -O DVBV5 /usr/share/dvb/dvb-t/tw-All
