#!/usr/bin/env python3
# -*- coding: UTF-8 -*-
#Import recordings or add missing recordings
import json, urllib, time, datetime, subprocess, os, re, sys, uuid
reload(sys)
sys.setdefaultencoding('utf-8')
#Variables
oldlogdir = "/storage/log/"
url = "https://localhost/tv"
recordingsdir = "/home/pi/Videos/recordings"
#write log
timestr = time.strftime("%Y-%m-%d")
logfile = open("TVHImport"+timestr+".log","a")
Newmask = """{
	"start": 1000,
	"stop": 1000,
	"enabled": true,
	"channel": "",
    "channelname": "imported",
	"creator": "imported",
    "title": {
        "ger": "my title"
    },
	"subtitle": {
		"ger": ""
	},
	"description": {
		"ger": ""
	},
	"errors": 0,
	"data_errors": 0,
	"playposition": 0,
	"playcount": 0,
	"noresched": true,
    "comment": "added by ImportAddRecords.py",
    "files": [
        {
            "filename": "/full/path/to/videofile.ts",
            "start": 1000,
            "stop": 1000
        }
    ]
}"""
Newmask = Newmask.replace("\n", "")     # remove the line feeds
def log(Text):
	logtime = time.strftime("%H:%M:%S")
	logfile.write(logtime+" "+Text+"\n")
def askyn(Text):
	selection = raw_input(Text + " (y/n) ")
	if selection == "n":
		return False
	elif selection == "y":
		return True
	else:
		askyn(Text)
def testOldLogDir():
	files = os.listdir(oldlogdir)
	print str(len(files)) + " files in " + oldlogdir
	log(str(len(files)) + " files in " + oldlogdir)
	if not askyn("use this directory?"):
		print ("Please edit the script!")
		exit(1)
		
def testRecordingDir():
	isExist = os.path.exists(recordingsdir)
	if isExist:
		print recordingsdir	
		log("Directory for recordings "+recordingsdir)
		if not askyn("Is this the Directory for the recordings?"):
			print ("Please edit the script!")
			exit(1)
	else:
		log("The recording directory does not exist! " + recordingsdir)
		print ("The recording directory does not exist!")
		print ("Please edit the script!")
		exit(1)
def getExistingRecordings():
	apiurl = url+"api/dvr/entry/grid_finished?&limit=9999999"
	filehandle = urllib.urlopen(apiurl)
	Recordings = filehandle.read()
	new_mask = json.loads(Recordings)
	#print(json.dumps(new_mask, indent=4, sort_keys=True))
	list = []
	for entry in new_mask['entries']:
		if 'files' in entry:
			if 'filename' in entry['files'][0]:
				list.append(entry['files'][0]['filename'])
		if 'filename' in entry:
			list.append(entry['filename'])
	
	print "Found "+str(len(list))+" finsihed recordings"
	log("Found "+str(len(list))+" finsihed recordings")
	return list
def ImportRecordings():
	print "1 - Check if recording exist"
	print "2 - No check if recording exist"
	selection = raw_input("Please select ")
	
	finishedRecordings = getExistingRecordings()
	
	files = os.listdir(oldlogdir)
	firstok = False
	firstimportok = False
	for reclog in files:
		f = open(oldlogdir+reclog,"r")
		print "--------------------------------"
		print "file:"
		print reclog
		log("--------------------------------")
		log("file:")
		log(reclog)
		
		mask = f.read()
		old_mask = json.loads(mask)
	
		new_mask = json.loads(Newmask)
		filename = ""
	
		if 'enabled' in old_mask:
			new_mask['enabled'] = old_mask['enabled']
		if 'start' in old_mask:
			new_mask['start'] = old_mask['start']
		if 'stop' in old_mask:
			new_mask['stop'] = old_mask['stop']
		#if 'channel' in old_mask:
		#	new_mask['channel'] = old_mask['channel']
		if 'channelname' in old_mask:
			new_mask['channelname'] = old_mask['channelname']
		if 'creator' in old_mask:
			new_mask['creator'] = old_mask['creator']
		if 'title' in old_mask:
			new_mask['title'] = old_mask['title']
		if 'subtitle' in old_mask:
			new_mask['subtitle'] = old_mask['subtitle']
		if 'description' in old_mask:
			new_mask['description'] = old_mask['description']
		if 'errors' in old_mask:
			new_mask['errors'] = old_mask['errors']
		if 'data_errors' in old_mask:
			new_mask['data_errors'] = old_mask['data_errors']
		if 'playposition' in old_mask:
			new_mask['playposition'] = old_mask['playposition']
		if 'playcount' in old_mask:
			new_mask['playcount'] = old_mask['playcount']
		if 'comment' in old_mask:
			new_mask['comment'] = old_mask['comment']
		
		if 'files' in old_mask:
			new_mask['files'] = old_mask['files']
			filename = old_mask['files'][0]['filename']
			if 'start' in old_mask['files'][0]:
				new_mask['start'] = old_mask['files'][0]['start']
			if 'stop' in old_mask['files'][0]:
				new_mask['stop'] = old_mask['files'][0]['stop']
		else:	
			if 'filename' in old_mask:
				new_mask['files'][0]['filename'] = old_mask['filename']
				filename = old_mask['filename']
			if 'start' in old_mask:
				new_mask['files'][0]['start'] = old_mask['start']
			if 'stop' in old_mask:
				new_mask['files'][0]['stop'] = old_mask['stop']
		#check if recording exist
		if filename in finishedRecordings:
			print "File "+ filename + " already exist as Recording"
			log("File "+ filename + " already exist as Recording")
			continue
		finishedRecordings.append(filename)
		
		#check if file exist
		if selection == "1":
			filename = new_mask['files'][0]['filename']
			isExist = os.path.exists(filename)
			if isExist == False:
				print "File " + filename + " does not exist!"
				log("File " + filename + " does not exist!")
				continue
		if firstok == False:
			print(json.dumps(new_mask, indent=4, sort_keys=True))
			
			if not askyn("OK?"):
				print ("Please edit the script!")
				exit(1)
			firstok = True
		
		#Import
		apiurl = url+"api/dvr/entry/create?conf="
		api_string = apiurl + json.dumps(new_mask).encode('utf8')
		filehandle = urllib.urlopen(api_string)
		ServerAnswer=filehandle.read()
		print "Server Answer:"+ServerAnswer
		log("Server Answer:"+ServerAnswer)
		
		if firstimportok == False:
			print ("Imported to TVH")
			print ("Please check if import is succesfully")
			if not askyn("OK?"):
				print ("Please edit the script!")
				exit(1)
			firstimportok = True	
		
def getExistingVideoFiles():
	list = []
	for dirpath, dirnames, filenames in os.walk(recordingsdir):
		for filename in [f for f in filenames if f.endswith(".ts")]:
			list.append(os.path.join(dirpath, filename))
	print "Found "+str(len(list))+"  Video files"
	log("Found "+str(len(list))+"  Video files")
	return list
	
def getStarttime(file):
	try:
		#find DateTime in File
		match = re.search('\d{4}-\d{2}-\d{2}.\d{2}-\d{2}', file)
		dt = datetime.datetime.strptime(match.group(), '%Y-%m-%d.%H-%M')
		print dt
		dt = int(time.mktime(dt.timetuple()))
		#dt = int(time.mktime(datetime.datetime.strptime(match.group(), '%Y-%m-%d.%H-%M').timetuple()))
	except:
		print("Unexpected error:", sys.exc_info()[0])
		log("Can't find time in filename")
		print "Filename starts with Datetime?"
		"""Convert filename that starts with 'YYYY-MM-DDTHH-MM' to a unix timestamp; use cdate, i.e. last inode change time not creation, on error""" 
		try:
			dt = int(time.mktime(datetime.datetime.strptime(filepath.split("/")[-1][0:15], "%Y-%m-%dT%H-%M").timetuple()))
		except:
			print "no...file name doesn't start with 'YYYY-MM-DDTHH-MM.ts'. Use Inode Change Time instead."
			log("use Filetime as Time")
			dt = int(os.stat(file).st_ctime)
	return dt
	
def ImportMissingRecords():
	testRecordingDir()
	existingRecords = getExistingRecordings()
	VideoFiles = getExistingVideoFiles()
	
	list = []
	for file in VideoFiles:
		if file in existingRecords:
			continue
		list.append(file)
	print "Found "+str(len(list))+" missing recordings"
	log("Found "+str(len(list))+" missing recordings")
	
	firstok = False	
	firstimportok = False
	for rec in list:
		new_mask = json.loads(Newmask)
		print "--------------------------------"
		print "file:"
		print rec
		log("--------------------------------")
		log("file:")
		log(rec)
		
		#Directory name = title
		title = os.path.basename(os.path.dirname(rec))
		if recordingsdir.endswith(title + "/"):
			title = os.path.splitext(os.path.basename(rec))[0]
		print "title: " + title
		#Subtitle = Filename without extension
		subtitle = os.path.splitext(os.path.basename(rec))[0]
		print "subtitle: " + subtitle
		start = getStarttime(rec)
		print "start: " + datetime.datetime.utcfromtimestamp(start).strftime('%Y-%m-%dT%H:%M:%SZ')
		stop = start + 1	
		print "stop: " + datetime.datetime.utcfromtimestamp(stop).strftime('%Y-%m-%dT%H:%M:%SZ')
		
		new_mask['title']['ger'] = title
		new_mask['subtitle']['ger'] = subtitle
		new_mask['files'][0]['filename'] = rec
		new_mask['files'][0]['start'] = start
		new_mask['files'][0]['stop'] = stop
		new_mask['start'] = start
		new_mask['stop'] = stop
		new_mask['description'] = subtitle
		
		if firstok == False:
			print(json.dumps(new_mask, indent=4, sort_keys=True))
			if not askyn("OK?"):
				print ("Please edit the script!")
				exit(1)
			firstok = True
		
		apiurl = url+"api/dvr/entry/create?conf="
		api_string = apiurl + json.dumps(new_mask)
		filehandle = urllib.urlopen(api_string)
		serveranswer=filehandle.read()
		print "Server Answer:"+serveranswer
		log("Server Answer:"+serveranswer)
		
		if firstimportok == False:
			print ("Imported to TVH")
			print ("Please check if import is succesfully")
			if not askyn("OK?"):
				print ("Please edit the script!")
				exit(1)
			firstimportok = True
def listMissingRecordings():
	existingRecords = getExistingRecordings()
	testRecordingDir()
	VideoFiles = getExistingVideoFiles()
	
	list = []
	for file in VideoFiles:
		if file in existingRecords:
			continue
		list.append(file)
	print ("Missing records:")
	print("\n".join(list))
	print "Found "+str(len(list))+" missing recordings"
	log("Missing records:")
	for i in list:
		log(i)
	log("Found "+str(len(list))+" missing recordings")
log("Start")
print "1 - import from oldLogDir"
print "2 - find and import missing records"
print "3 - list missing records"
selection = input("Please select ")
if selection == 1:
	print "1"
	log("import from oldLogDir")
	testOldLogDir()
	ImportRecordings()
elif selection == 2:
	print "2"
	log("find and import missing records")
	ImportMissingRecords()
elif selection == 3:
	print "3"
	log("list missing records")
	listMissingRecordings()
else:
	print "Invalid selection"
