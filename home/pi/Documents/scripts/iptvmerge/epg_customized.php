<?php
ini_set('display_errors', FALSE);
ini_set('display_errors', TRUE);
set_time_limit(1200);
include_once("functions.php");

$olympicch_guide_finalprograms = array();
$nat_geo_guide_finalprograms = array();
//national geographic http://natgeotv.com/asia/listings/weekly/ngc/160420/4
//nat geo people http://natgeotv.com/asia/listings/weekly/people/160420/4
//nat geo wild 

function ret_pagehtml_for_a_list($webpages) {
    $webpages = array_combine($webpages, $webpages);
    $webpages = getSslPages($webpages);
    $webpages = $webpages['html'];
    return($webpages);
}
function ch5_8_epgjson_to_array($epgjson) {
    global $zhconv;
    $epgjson = $zhconv->zhconversion_tw(($epgjson));
    $epgjson = json_decode($epgjson, TRUE)["programs"]; //[0]["start"];
    $epgjson = array_combine(
        array_map('trim', array_column($epgjson, 'title')),
        array_map('trim', array_column($epgjson, 'start')),
    );
    return($epgjson);
}
function outerHTML($e) {
    $doc = new DOMDocument();
    $doc->appendChild($doc->importNode($e, true));
    return $doc->saveHTML();
}

function epg_rthk() {
    /*
    Host: www.rthk.hk
    Referer: https://www.rthk.hk/tv
    港台電視 https://www.rthk.hk/timetable/main_timetable/20200422
    https://www.rthk.hk/main/update_view?no=0.5841747574377516
    https://www.rthk.hk/tv
    https://www.rthk.hk/tv/get_dtt_programme_info
    */
    $rthkepg_fetch_days = [0,1,2,3,4,5,6,7];
    $rthkepgs = array_map(function ($day_interval) {
        return(mktime()+60*60*24*$day_interval);
    }, $rthkepg_fetch_days);
    $rthkepgs = array_map(function ($time) {
        return(    sprintf("https://www.rthk.hk/timetable/tv_timetable/%s", date("Ymd", $time) ) );
    }, $rthkepgs);
    $rthkepgs = array_combine($rthkepgs, $rthkepgs);
    $rthkepgs = getSslPages($rthkepgs, FALSE, [], array(
            CURLOPT_HTTPHEADER => array(
                'host: www.rthk.hk'
                /*
                'sec-fetch-dest: document',
                'sec-fetch-mode: navigate',
                'sec-fetch-site: none',
                'sec-fetch-user: ?1',
                'upgrade-insecure-requests: 1',
                'accept-language: zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7'
                */
            ),
            //CURLOPT_PROXY=>"14.207.125.216:8080",//"14.207.125.216:8080",
            CURLOPT_REFERER=>'https://www.rthk.hk/tv',
            CURLOPT_SSL_VERIFYPEER=>FALSE
        ));
    $rthkepgs = $rthkepgs['html'];
    function generate_rthk_epgs($rthkepgkey, $rthkepg) {
        $fetchbasisdate = str_replace("https://www.rthk.hk/timetable/tv_timetable/","",$rthkepgkey);
        $fetchbasisdate = strtotime($fetchbasisdate);
        $rthkepg = json_decode($rthkepg, TRUE, JSON_UNESCAPED_UNICODE)["result"];
        $rthkepg = array_map(function ($x) {return('<?xml version="1.0" encoding="UTF-8"?>'.$x);}, $rthkepg);
        
        //JSON_UNESCAPED_UNICODE JSON_UNESCAPED_SLASHES
        $rthkepg_src_ch_key_name_arr = array(
            "dtt31"=> "港台電視31",
            "dtt32"=> "港台電視32",
        );
        $rthk_timearea = " +0800";
        $rthk_xpath_array = array(
            'programtitle'=>".//div[@class='showTit']/a/text()",
            'programsubtitle'=>".//a[@class='showEpi']/@title",
            'programlongtitle'=>".//a[@class='showEpi']/@title",
            'programdesc'=>".//a[@class='showEpi']/text()",
            'programlink'=>".//a[@class='showEpi']/@href",
            'programdate'=>".//self::node()/@data-date",
            'programimg'=>".//self::node()/@data-p",
            'programstdtime_start'=>".//div[@class='timeRow']/text()",
            'programstdtime_stop'=>".//div[@class='timeRow']/text()",
        );
        $programs = array();
        foreach ($rthkepg_src_ch_key_name_arr as $rthkepg_src_ch_key=>$chname) {
            $epgsrcresult = $rthkepg[$rthkepg_src_ch_key];
            //dumpv(htmlspecialchars($epgsrcresult));exit;
            $xmlDoc = new DOMDocument();
            $xmlDoc->loadHTML($epgsrcresult);
            $xpath = new DOMXPath($xmlDoc);
            $programnodes = $xpath->query("//div[contains(@data-id,'programme_')]");
            $programnodes = iterator_to_array($programnodes);
            foreach ($programnodes as $programnode) {
                $program['programchannelid'] = $chname;
                $program['programchannelname'] = $chname;
                foreach ($rthk_xpath_array as $program_key => $program_attr_xpath) {
                    $program[$program_key] = trim($xpath->query($program_attr_xpath,$programnode)->item(0)->textContent);
                }
                $program['programdate'] = date("Y-m-d", $fetchbasisdate);//有一些節目有時間設定錯誤，所以用抓資料的時間當基準
                $program['programimg'] = 'https://www.rthk.hk'.$program['programimg'];
                $program['programlongtitle'] = ($program['programlongtitle']!='') ? $program['programtitle']." : ".$program['programlongtitle'] : $program['programlongtitle'];
                $program['programstdtime_start'] = date("YmdHis", strtotime($program['programdate']." ".explode("-",$program['programstdtime_start'])[0])).$rthk_timearea;
                $program['programstdtime_stop'] = date("YmdHis", strtotime($program['programdate']." ".explode("-",$program['programstdtime_stop'])[1])).$rthk_timearea;
                $programs[] = $program;
            }
        }
        return($programs);
    }
    $rthkepgs = array_map('generate_rthk_epgs', array_keys($rthkepgs), array_values($rthkepgs) );
    $rthkepgs = array_reduce($rthkepgs, 'array_merge', array());
    return($rthkepgs);
}

function epg_channel_5_8() {
    global $zhconv;
    //https://www.mewatch.sg/en/channelguide/182/18-04-2020
    $channel_5_8_homepage_guide_current_program_starttime = ret_pagehtml_for_a_list(["https://tv.mewatch.sg/blueprint/servlet/toggle/tvGuide?type=tvSchedule&region=181&timezone=&mcTvGuideId=5010382&rootId=5006610",
        "https://tv.mewatch.sg/blueprint/servlet/toggle/tvGuide?type=tvSchedule&region=182&timezone=&mcTvGuideId=5010382&rootId=5006610",
        "https://tv.mewatch.sg/blueprint/servlet/toggle/tvGuide?type=tvSchedule&region=183&timezone=&mcTvGuideId=5010382&rootId=5006610",
        "https://tv.mewatch.sg/blueprint/servlet/toggle/tvGuide?type=tvSchedule&region=191&timezone=&mcTvGuideId=5010382&rootId=5006610",]);
    $channel_5_8_homepage_guide_current_program_starttime = array_map('ch5_8_epgjson_to_array', $channel_5_8_homepage_guide_current_program_starttime);
    $channel_5_8_homepage_guide_current_program_starttime = array_reduce($channel_5_8_homepage_guide_current_program_starttime, array_merge, array());
    $channel_5_8_epgs_fetch_days = [0,1,2,3,4,5,6,7];
    $channel_5_8_epgs_times = array_map(function ($day_interval) {
        return(mktime()+60*60*24*$day_interval);
    }, $channel_5_8_epgs_fetch_days);
    /*channel 5
    https://cdn.mewatch.sg/api/schedules?channels=97098&date=2021-01-15&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all
    channel 8
    https://cdn.mewatch.sg/api/schedules?channels=97104&date=2021-01-15&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all
    channel U
    https://cdn.mewatch.sg/api/schedules?channels=97129&date=2021-01-15&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all
    suria
    https://cdn.mewatch.sg/api/schedules?channels=97084&date=2021-01-15&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all
    vasantham
    https://cdn.mewatch.sg/api/schedules?channels=97096&date=2021-01-15&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all
    cna
    https://cdn.mewatch.sg/api/schedules?channels=97072&date=2021-01-15&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all
    */
    $channel_5_8_epgs = array_map(function ($time) {
        return( array(
            'https://cdn.mewatch.sg/api/schedules?channels=97098&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all&date='.sprintf('%s', date("Y-m-d", $time) ),
            'https://cdn.mewatch.sg/api/schedules?channels=97104&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all&date='.sprintf('%s', date("Y-m-d", $time) ),
            'https://cdn.mewatch.sg/api/schedules?channels=97129&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all&date='.sprintf('%s', date("Y-m-d", $time) ),
            'https://cdn.mewatch.sg/api/schedules?channels=97084&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all&date='.sprintf('%s', date("Y-m-d", $time) ),
            'https://cdn.mewatch.sg/api/schedules?channels=97096&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all&date='.sprintf('%s', date("Y-m-d", $time) ),
            'https://cdn.mewatch.sg/api/schedules?channels=97072&duration=24&ff=idp%2Cldp%2Crpt%2Ccd&hour=16&intersect=true&lang=en&segments=all&date='.sprintf('%s', date("Y-m-d", $time) ),
        ));
    }, $channel_5_8_epgs_times);
    $channel_5_8_epgs = array_reduce($channel_5_8_epgs, 'array_merge', array());
    $channel_5_8_chids = array_fill(
        0, count($channel_5_8_epgs_fetch_days), array("Channel 5", "Channel 8", "Channel U", "Suria", "Vasantham", "Channel NewsAsia"),
        );
    $channel_5_8_epgs_urls = $channel_5_8_epgs;
    $channel_5_8_chids = array_reduce($channel_5_8_chids, 'array_merge', array());
    $channel_5_8_epgs_times = array_map(function ($time) {
        return( array($time,$time,$time,$time) );
    }, $channel_5_8_epgs_times);
    $channel_5_8_epgs_times = array_reduce($channel_5_8_epgs_times, 'array_merge', array());
    $channel_5_8_epgs = ret_pagehtml_for_a_list($channel_5_8_epgs);
    $channel_5_8_homepage_guide_current_program_starttimes = array_fill(0, count($channel_5_8_epgs), $channel_5_8_homepage_guide_current_program_starttime);
    $channel_5_8_epgs = array_map(function ($epgsrcresult, $timebasis, $chid, $channel_5_8_homepage_guide_current_program_starttime,$channel_5_8_epgs_url) {
        global $zhconv;
        $epgsrcresult = json_decode($epgsrcresult, true);
        $timearea = " +0800";
        $programs = array();
        for ($i=0;$i<count($epgsrcresult[0]["schedules"]);$i++) {
            $program = array(
                "programtitle" => $epgsrcresult[0]["schedules"][$i]["item"]["title"],
                "programstdtime_start" => $epgsrcresult[0]["schedules"][$i]["startDate"],
                "programstdtime_stop" => $epgsrcresult[0]["schedules"][$i]["endDate"],
                "programlink" => "",
                "programchannelid" => $chid,
                "programchannelname" => $chid,
                "programdesc" => $epgsrcresult[0]["schedules"][$i]["item"]["description"],
                "programsubtitle" => $epgsrcresult[0]["schedules"][$i]["item"]["episodeTitle"],
                "programimg" => $epgsrcresult[0]["schedules"][$i]["item"]["images"]["wallpaper"]
            );
            $program["programlongtitle"] = ($epgsrcresult[0]["schedules"][$i]["item"]["episodeTitle"]) ? $epgsrcresult[0]["schedules"][$i]["item"]["title"].", ".$epgsrcresult[0]["schedules"][$i]["item"]["episodeTitle"] : $program["programtitle"];
            $programs[] = $program;
        }
        /*
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadHTML($epgsrcresult);
        $xpath = new DOMXPath($xmlDoc);
        $programnodes = $xpath->query("//div[contains(@class,'epg--channel__item')]");
        $programnodes = iterator_to_array($programnodes);
        $xpath_array = array(
            'programtitle'=>".//h4/a/text()",
            'programlongtitle'=>".//h4/a/text()",
            'programstdtime_start'=>".//div[@class='epg--channel__time']/span/text()",
            'programdesc'=>".//div[@class='epg--channel__info']//p[@class='epg__desc']/text()",
        );
        for ($i=0;$i<count($programnodes);$i++) {
            $program = array();
            foreach ($xpath_array as $key=>$program_attr_xpath) {
                $program[$key] = $zhconv->zhconversion_tw(trim($xpath->query($program_attr_xpath,$programnodes[$i])->item(0)->textContent));
            }
            $program["programstdtime_start"] = (preg_match("/\d+/", $program["programstdtime_start"])!=1) ? strtotime($channel_5_8_homepage_guide_current_program_starttime[$program["programtitle"]]) : strtotime(date("Ymd", $timebasis)." ".$program["programstdtime_start"]);
            $program["programstdtime_start"] = date("YmdHis", $program["programstdtime_start"]).$timearea;
            $program['programchannelid'] = $chid;
            $program['programchannelname'] = $chid;
            $program['programlink'] = '';
            $programs[] = $program;
        }
        */
        return($programs);
    }, $channel_5_8_epgs, $channel_5_8_epgs_times, $channel_5_8_chids, $channel_5_8_homepage_guide_current_program_starttimes,$channel_5_8_epgs_urls);
    $channel_5_8_epgs = array_reduce($channel_5_8_epgs, 'array_merge', array());
    return($channel_5_8_epgs);
}


function epg_nhk_chinese() {
    global $zhconv;
    $nhk_chinese_epgs = "https://api.nhk.or.jp/nhkworld/zepg/v8/world/all.json?apikey=EJfK8jdS57GqlupFgAfAAwr573q01y6k";
    $nhk_chinese_epgs = file_get_contents($nhk_chinese_epgs);
    $nhk_chinese_epgs = json_decode($nhk_chinese_epgs, TRUE)["channel"]["item"];
    $nhk_chinese_epgs = array_map(function($x) {
        global $zhconv;
        $x = array(
            'programchannelid' => 'NHKChineseWorld',
            'programchannelname' => 'NHK 华语视界',
            'programtitle'=>$zhconv->zhconversion_tw(trim($x['title'])),
            'programsubtitle'=>$zhconv->zhconversion_tw(trim($x['subtitle'])),
            'programlongtitle'=>$zhconv->zhconversion_tw(trim($x['title'].$x["subtitle"])),
            'programdesc'=>$zhconv->zhconversion_tw(trim($x['title'].$x["subtitle"])),
            'programlink'=>'https://www3.nhk.or.jp'.$x["vod_program_url"],
            'programimg'=>'https://www3.nhk.or.jp'.$x['thumbnail'],
            'programstdtimedate'=>date("Ymd", intval($x["pubDate"]/1000)),
            'programstdtime_start'=>date("YmdHis", intval($x["pubDate"]/1000))." +0000",
            'programstdtime_stop'=>date("YmdHis", intval($x["endDate"]/1000))." +0000",
            );
        return($x);
    }, $nhk_chinese_epgs);
    return($nhk_chinese_epgs);
}

function epg_olympic_channel() {
    $olympicchepgs = array_map(function ($day_interval) {
        return( sprintf('https://www.olympicchannel.com/en/api/v1/live/video/%s/epglist?_=%s',
            date("Y-n-j", mktime()+60*60*24*$day_interval),
            strval(mktime()+60*60*24*$day_interval).'000'
            ) );
    }, [0,3,6,9]);
    $olympicchepgs = ret_pagehtml_for_a_list($olympicchepgs);
    $olympicchepgs = array_map(function ($x) {return(json_decode($x,TRUE));}, $olympicchepgs);
    $olympicchepgs = array_map(function ($x) {
        $programs = $x["modules"][0]["content"];
        $programs = array_map(function ($program) {
            $program = array(
                    'programchannelid'=>$program['channelId'],
                    'programchannelname'=>'Olympic Channel 1',
                    'programtitle'=>$program['title'],
                    'programlongtitle'=>$program['title'],
                    'programdesc'=>$program['description'],
                    'programstdtime_start'=>date("YmdHis +0000", strtotime($program['startTime'])),
                    'programstdtime_stop'=>date("YmdHis +0000", strtotime($program['endTime'])),
                    'programlink'=>"https://www.olympicchannel.com".$program['url'],
                );
            return($program);
        }, $programs);
        return($programs);
    }, $olympicchepgs);
    $olympicchepgs = array_reduce($olympicchepgs, 'array_merge', array());
    $olympicch_guide_channels = array_combine(
        array_column($olympicchepgs, "programchannelid"),
        array_column($olympicchepgs, "programchannelname")
        );
    return($olympicchepgs);
}

function epg_viutv() {
    //function viutv() { index=$(curl -s 'http://api.viu.now.com/p8/1/getLiveURL' --data '{"channelno":"099","mode":"prod","deviceId":"0000anonymous_user","format":"HLS"}' | python -c 'import json,sys; print(json.load(sys.stdin)["asset"]["hls"]["adaptive"][0])') && curl -s $index > /dev/null && base=$(echo $index | sed "s/\/index.*//g") && mpv "$base/Stream(04)/index.m3u8" --audio-file "$base/Stream(06)/index.m3u8" }
    $viutv_epg_srcs = array('https://api.viu.tv/production/epgs/99','https://api.viu.tv/production/epgs/96');
    $viutv_epg_srcs = ret_pagehtml_for_a_list($viutv_epg_srcs);
    $viutv_epg_srcs = array_map(function($x) {return(json_decode($x, TRUE));},$viutv_epg_srcs);
    $viutv_epg_srcs = array_reduce($viutv_epg_srcs, 'array_merge', array())["epgs"];
    $viutv_epg_srcs = array_map(function($program) {
        $viutv_std_seconds_diff = 60*60*24*9366-60*60*4-60*5;
        $program_start_timestamp = strtotime($program['date'].$program['startTime']);
        $program_duration = (intval($program['end'])-intval($program['start']))/1000;
        $program_end_timestamp = $program_start_timestamp+$program_duration;
        $program = array(
            'programchannelid'=>"ViuTV（".strval($program['channelId'])."）",
            'programchannelname'=>"ViuTV（".strval($program['channelId'])."）",
            'programtitle'=>$program['program_title'],
            'programlongtitle'=>$program['title'].$program['episode_title'],
            'programdesc'=>$program['episode_title'],
            'programstdtime_start'=>date("YmdHis", $program_start_timestamp)." +0000",
            'programstdtime_stop'=>date("YmdHis", $program_end_timestamp)." +0000",
            'programlink'=>""
        );
        return($program);
    }, $viutv_epg_srcs);
    return($viutv_epg_srcs);
}

function epg_nat_geo() {
    $nat_geo_guide_weekday_key = [
        'monday this week',
        'tuesday this week',
        'wednesday this week',
        'thursday this week',
        'friday this week',
        'saturday this week',
        'sunday this week',
        'monday next week',
        'tuesday next week',
        'wednesday next week',
        'thursday next week',
        'friday next week',
        'saturday next week',
        'sunday next week',
    ];
    
    $nat_geo_guide_weekday_timestamp = array_map('strtotime', $nat_geo_guide_weekday_key);
    array_push( $nat_geo_guide_weekday_timestamp, endc($nat_geo_guide_weekday_timestamp)+60*60*24 );
    $nat_geo_guide_weekday_date = array_map( function($x) {return(date('Ymd',$x));},  $nat_geo_guide_weekday_timestamp);
    $nat_geo_guide_weekday_date_thiswk = array_slice($nat_geo_guide_weekday_date, 0 , 8);
    $nat_geo_guide_weekday_date_nextwk = array_slice($nat_geo_guide_weekday_date, 7 , NULL);
    $nat_geo_guide_targeturl_guide_day = date("dmY");
    $nat_geo_guide_targeturl_guide_day_nextwk = date("dmY",mktime()+60*60*24*7);
    //select http://www.natgeotv.com/international
    $nat_geo_guide_webpages = ret_pagehtml_for_a_list([
    "http://natgeotv.com/asia/listings/weekly/ngc/$nat_geo_guide_targeturl_guide_day/1",
    "http://natgeotv.com/asia/listings/weekly/ngc/$nat_geo_guide_targeturl_guide_day/2",
    "http://natgeotv.com/asia/listings/weekly/ngc/$nat_geo_guide_targeturl_guide_day/3",
    "http://natgeotv.com/asia/listings/weekly/ngc/$nat_geo_guide_targeturl_guide_day/4",
    "http://natgeotv.com/asia/listings/weekly/ngc/$nat_geo_guide_targeturl_guide_day_nextwk/1",
    "http://natgeotv.com/asia/listings/weekly/ngc/$nat_geo_guide_targeturl_guide_day_nextwk/2",
    "http://natgeotv.com/asia/listings/weekly/ngc/$nat_geo_guide_targeturl_guide_day_nextwk/3",
    "http://natgeotv.com/asia/listings/weekly/ngc/$nat_geo_guide_targeturl_guide_day_nextwk/4",
    "http://natgeotv.com/asia/listings/weekly/people/$nat_geo_guide_targeturl_guide_day/1",
    "http://natgeotv.com/asia/listings/weekly/people/$nat_geo_guide_targeturl_guide_day/2",
    "http://natgeotv.com/asia/listings/weekly/people/$nat_geo_guide_targeturl_guide_day/3",
    "http://natgeotv.com/asia/listings/weekly/people/$nat_geo_guide_targeturl_guide_day/4",
    "http://natgeotv.com/asia/listings/weekly/people/$nat_geo_guide_targeturl_guide_day_nextwk/1",
    "http://natgeotv.com/asia/listings/weekly/people/$nat_geo_guide_targeturl_guide_day_nextwk/2",
    "http://natgeotv.com/asia/listings/weekly/people/$nat_geo_guide_targeturl_guide_day_nextwk/3",
    "http://natgeotv.com/asia/listings/weekly/people/$nat_geo_guide_targeturl_guide_day_nextwk/4",
    ]);
    $nat_geo_guide_finalprograms = array();
    foreach ($nat_geo_guide_webpages as $webpages_k=>$epgsrcresult) {
        $in_next_wk = (preg_match(sprintf("/%s/", $nat_geo_guide_targeturl_guide_day_nextwk), $webpages_k)==1) ? TRUE : FALSE;
        $nat_geo_guide_weekday_date_need = ($in_next_wk==TRUE) ? $nat_geo_guide_weekday_date_nextwk : $nat_geo_guide_weekday_date_thiswk;
        $guide_timeinterval = substr($webpages_k, -1);
        if (preg_match("/natgeotv.com\/asia\/listings\/weekly\/ngc/", $webpages_k)==1) {
            $programchannelid = "NationalGeographic_asia_time_adj_for_tvanywhere";
            $programchannelname = "National Geographic Asia";
            $epg_time_gap = '+0400';
        } elseif (preg_match("/natgeotv.com\/asia\/listings\/weekly\/people/", $webpages_k)==1) {
            $programchannelid = "Nat_Geo_People_asia_time_adj_for_tvanywhere";
            $programchannelname = 'Nat Geo People Asia';
            $epg_time_gap = '+0400';
        } else {
            $programchannelid = '';
            $programchannelname = '';
            $epg_time_gap = '';
        }
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadHTML($epgsrcresult);
        $xpath = new DOMXPath($xmlDoc);
        $programtable_dates = $xpath->query("//table[contains(@class,'WeeklyDiv WidthXL')]/tbody//th/text()");
        $programtable_dates = iterator_to_array($programtable_dates);
        $programtable_dates = array_map(function ($x) {return(trim($x->textContent));}, $programtable_dates);
        $programtable_weekdays = array_map(function ($x) {preg_match("/([\w]+)/", $x, $matches); return($matches[0]); }, $programtable_dates);
        $programtable_days = array_map(function ($x) {preg_match("/([\d]+)/", $x, $matches); return($matches[0]); }, $programtable_dates);
        $programtable_trs = $xpath->query("//table[contains(@class,'WeeklyDiv WidthXL')]/tbody/tr[position()>1]");
        $xpath_array = array(
            'programtitle'=>".//span[@class='Bold']/text()",
            //'programlongtitle'=>".//span[contains(@class,'FloatLeft')]/*[not(descendant-or-self::script)][not(descendant-or-self::b)][not(descendant-or-self::br)][not(descendant-or-self::input)][not(descendant-or-self::img)][not(contains(.,'email'))][not(contains(.,'Email'))][not(contains(.,'Visit Site'))]",
            //'programdesc'=>".//span[contains(@class,'FloatLeft')]/*[not(descendant-or-self::script)][not(descendant-or-self::b)][not(descendant-or-self::br)][not(descendant-or-self::input)][not(descendant-or-self::img)][not(contains(.,'email'))][not(contains(.,'Email'))][not(contains(.,'Visit Site'))]",
            'programlink'=>".//a/@href",
            'programstdtime_start'=>".//b/text()",
        );
        foreach ($programtable_trs as $programtable_tr_key=>$programtable_tr) {
            $tds = $xpath->query(".//td[@rowspan='1']", $programtable_tr);
            $tds = iterator_to_array($tds);
            foreach ($tds as $td_key=>$td) {
                $program = array();
                foreach ($xpath_array as $xpathkey=>$program_attr_xpath) {
                    $program[$xpathkey] = trim($xpath->query($program_attr_xpath, $td)->item(0)->textContent);
                }
                if ($program['programtitle']=='') { continue; }
                $program['programchannelid'] = $programchannelid;
                $program['programchannelname'] = $programchannelname;
                $program['programlink'] = ($program['programlink']==NULL) ? NULL : 'http://natgeotv.com'.$program['programlink'];
                $program['programtitle'] = str_replace(":", "", $program['programtitle']);
                preg_match("/(\d{2}:\d{2})/", $program['programstdtime_start'], $programtime);
                $program['programstdtime_start'] = ($guide_timeinterval=='4' and $programtable_tr_key!=0) ? $nat_geo_guide_weekday_date_need[$td_key+1] : $nat_geo_guide_weekday_date_need[$td_key];
                $program['programstdtime_start'] = $program['programstdtime_start'].str_replace(":", "", $programtime[1])."00 ".$epg_time_gap;
                $programlongtitle = $xpath->query(".//span[contains(@class,'FloatLeft')]/*[not(descendant-or-self::script)][not(descendant-or-self::b)][not(descendant-or-self::br)][not(descendant-or-self::input)][not(descendant-or-self::img)][not(contains(.,'email'))][not(contains(.,'Email'))][not(contains(.,'Visit Site'))]", $td);
                $programlongtitle = iterator_to_array($programlongtitle);
                $programlongtitle = array_map(function ($x) {return($x->textContent);}, $programlongtitle);
                $programlongtitle = array_filter($programlongtitle, function ($x) {return($x!="Close");} );
                $programlongtitle = implode(" ", $programlongtitle);
                $program['programlongtitle'] = $programlongtitle;
                $program['programdesc'] = $programlongtitle;
                /*
                $programtime = $xpath->query(".//b/text()", $td)->item(0)->nodeValue;
                $programday = $programtable_days[$td_key];
                $programweekday = $programtable_weekdays[$td_key];
                /descendant::node()[not(contains(@class,'ReminderOverlay'))]
                /descendant::text()[not(ancestor::div/@class='infobox')]
                [not(self::div[@class='remindme-form'])]
                $programname = str_replace($time, "", $programname->item(0)->textContent);
                */
                $nat_geo_guide_finalprograms[] = $program;
            }
            //break;
        }
        //break;
    }
    return($nat_geo_guide_finalprograms);
}


//epg_rthk()
//epg_nhk_chinese()
//epg_channel_5_8()
//epg_olympic_channel()
//epg_nat_geo()
//dmpv(epg_rthk());
function get_custom_all_epgs() {
    $finalprograms = array_merge(epg_rthk(), epg_nhk_chinese(), epg_olympic_channel(), epg_viutv());
    //array_merge(epg_rthk(), epg_nhk_chinese(), epg_channel_5_8(), epg_olympic_channel(), epg_nat_geo(), epg_viutv());
    //array_multisort(array_column($finalprograms, 'programstdtime_start'), SORT_ASC, $finalprograms );
    return($finalprograms);
}


/*

$myownepgdoc = new DOMDocument('1.0', 'UTF-8');
$tvtag = $myownepgdoc->createElement('tv');


foreach ($guide_channels as $guide_channel_id=>$guide_channel_name) {
    $channelelement = $myownepgdoc->createElement('channel');
    $channelelement->setAttribute('id', $guide_channel_id );
    
    $channelelement_displayname = $myownepgdoc->createElement('display-name',$guide_channel_name);
    $channelelement->appendChild($channelelement_displayname);
    if ( in_array($guide_channel_id, array_keys($channel_additional_display_name ))) {
        foreach ($channel_additional_display_name[$guide_channel_id] as $guide_channel_name) {
            $channelelement_displayname = $myownepgdoc->createElement('display-name',$guide_channel_name);
            $channelelement->appendChild($channelelement_displayname);
        }
    }
    $tvtag->appendChild($channelelement);
    $matched_programs = array_filter($finalprograms, function($x) use($guide_channel_id) {return($x['programchannelid']==$guide_channel_id);} );
    $matched_programs = array_slice($matched_programs, 0, NULL, FALSE);
    $matched_programs_starttimes = array_column($matched_programs, 'programstdtime_start');
    $matched_programs_starttimes = array_slice($matched_programs_starttimes, 0, NULL, FALSE);
    foreach ($matched_programs as $mpkey=>$matched_program) {
        $programmeelement = $myownepgdoc->createElement('programme');
        $programmeelement->setAttribute('start', $matched_program['programstdtime_start'] );
        if (in_array($guide_channel_id,['NationalGeographic_asia_time_adj_for_tvanywhere', 'Nat_Geo_People_asia_time_adj_for_tvanywhere', 'Channel 5', 'Channel 8', 'Channel U', 'Channel NewsAsia']) and $matched_programs_starttimes[$mpkey+1]) {
            $programmeelement->setAttribute('stop', $matched_programs_starttimes[$mpkey+1] );
        } else {
            $programmeelement->setAttribute('stop', $matched_program['programstdtime_stop'] );
        }
        $programmeelement->setAttribute('channel', $guide_channel_id );
        $titleelement = $myownepgdoc->createElement('title', $matched_program['programtitle']);
        $descelement = $myownepgdoc->createElement('desc', $matched_program['programdesc']);
        $programmeelement->appendChild($titleelement);
        $programmeelement->appendChild($descelement);
        $tvtag->appendChild($programmeelement);
    }
}
$myownepgdoc->appendChild($tvtag);
$myownepgdoc->preserveWhiteSpace = FALSE;
$myownepgdoc->formatOutput = TRUE;
$myownepgdoc = $myownepgdoc->saveXML();
$myownepgdoc = new SimpleXMLElement($myownepgdoc);
$myownepgdoc = $myownepgdoc->asXML();


echo file_put_contents(dirname(__FILE__).'/myown_epg_natgeo.xml', $myownepgdoc);
$local_epgs = array_map(function($x){return(dirname(__FILE__).$x);}, ["/myown_epg.xml"]);
$local_epgs = array_combine($local_epgs, [$myownepgdoc]);
*/
?>