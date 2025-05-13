#!/usr/bin/python
# -*- coding: UTF-8 -*-

""" 
usage: tvh_addfile.py

registers a local file in tvheadend as recorded by sending json formed conf info via http api:
http://user:pass@localhost:9981/api/dvr/entry/create?conf={"enabled": true, "start": 1000, <other json info>}

If successful returns the uuid of the created timer
""" 

import json, urllib, time, datetime, subprocess

def datestr2num(string_date):
    """Convert Date&Time string YYYY-MM-DD HH:MM:SS to Unix timestamp; use default date on error""" 

    try:
        dt=time.mktime(datetime.datetime.strptime(string_date, "%Y-%m-%d %H:%M:%S").timetuple())
    except:
        defaultdate = '2000-01-01 12:00:00'
        print("ERROR in datestr2num: Date as String: '{}'".format(string_date))
        print("                      replacing with: '{}'".format(defaultdate))
        dt=time.mktime(datetime.datetime.strptime(defaultdate, "%Y-%m-%d %H:%M:%S").timetuple())

    return dt

def videoDuration(video_file_path):
    """Get video duration in sec from a ffprobe call, using json output""" 

    #command is:  ffprobe -loglevel quiet -print_format json -show_format /full/path/to/videofile
    command     = ["ffprobe", "-loglevel", "quiet", "-print_format", "json", "-show_format",  video_file_path]
    pipe        = subprocess.Popen(command, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    out, err    = pipe.communicate()
    js          = json.loads(out)

    return  int(float(js['format']['duration']) + 1.)

############# enter your data here ############################################
video_storage       = "/home/pi/Videos/recordings/"        # must end with "/" 
video_name          = "Extreme Trek S3(6).TS"                    # your video name with
                                                       # proper extension!
video_title         = "my title"                       # your text (any)
video_description   = "my description of my video"     # your text (any)
video_starttime     = "2017-10-25 00:00:00"            # your start time (any)
###############################################################################

video_path          = video_storage + video_name
video_subtitle      = "filename: " + video_name
video_startstmp     = datestr2num(video_starttime)
video_stopstmp      = video_startstmp + videoDuration(video_path)

mask = """{
    "enabled": true,
    "start": 1000,
    "stop":  2000,
    "channelname": "local file",
    "title": {
        "ger": "my title" 
    },
    "subtitle": {
        "ger": "filename: my video" 
    },
    "description": {
        "ger": "my description" 
    },
    "comment": "added by tvh_addfile.py",
    "files": [
        {
            "filename": "/full/path/to/videofile.ts" 
        }
    ]
}""" 
mask = mask.replace("\n", "")                          # remove the line feeds

new_mask                         = json.loads(mask)
new_mask['files'][0]['filename'] = video_path
new_mask['title']['ger']         = video_title
new_mask['subtitle']['ger']      = video_subtitle
new_mask['description']['ger']   = video_description
new_mask['start']                = video_startstmp
new_mask['stop']                 = video_stopstmp

print( "New File Info: \n", json.dumps(new_mask, sort_keys = True, indent = 4) )

tvhid = ''
tvhpwd = ''
api_url     = 'http://{}:{}@localhost:9981/api/dvr/entry/create'.format(tvhid, tvhpwd)
post        = 'conf=' + json.dumps(new_mask)
print(post, new_mask)
#filehandle  = urllib.urlopen(api_url + "?" + post)
#print "Server Answer:", filehandle.read()
