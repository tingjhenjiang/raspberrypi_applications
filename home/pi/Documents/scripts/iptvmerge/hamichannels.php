<?php
ini_set('display_errors', False);
ini_set('display_errors', True);
include_once("functions.php");
$playlist_template = (isset($playlist_template)) ? $playlist_template : <<<EOM
#EXTINF:-1 tvg-id="TVGID" tvg-name="TVGNAME" tvg-language="Chinese" tvg-logo="PNG" tvg-country="TW" tvg-url="" group-title="",CHANNELNAME
M3U8
EOM;


if ($argv[1] or $_GET['redirecthamilink']) {
    $hamilink = ($argv[1]) ? $argv[1] : $_GET['redirecthamilink'];
    $hamivideo_playlist_for_tvheadend_pyscript = "/home/pi/Documents/kodi_addons/plugin.video.hamivideo/resources/lib/hamivideo/api.py";
    $hamivideo_playlist_for_tvheadend_command = "/home/pi/Documents/Envs/kodi/bin/python ".$hamivideo_playlist_for_tvheadend_pyscript." --type hami --churl ".$hamilink;
    $streamingurl = exec($hamivideo_playlist_for_tvheadend_command, $return_var);
    fwrite(STDOUT, $streamingurl);
    exit();
}


class Hamivideo_playlist_epg extends Epg_base {
    function __construct() {
        $this->playlist_template = $playlist_template;
        $this->resolve_streaming_hamilink_prefix = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?redirecthamilink=";
        $this->hamihost = "https://hamivideo.hinet.net";
        $this->playlist_template = <<<EOM
#EXTINF:-1 tvg-id="TVGID" tvg-name="TVGNAME" tvg-language="Chinese" tvg-logo="PNG" tvg-country="TW" tvg-url="" group-title="",CHANNELNAME
M3U8
EOM;
        $this->get_hamichs_html();
        $this->get_hamichs_htmlelements();
        $this->get_channel_details();
    }
    function get_hamichs_html() {
        $hamichshtml = getSslPage($this->hamihost."/%E9%9B%BB%E8%A6%96%E9%A4%A8/%E5%85%A8%E9%83%A8.do");
        $this->hamichshtml = $hamichshtml;
    }
    function get_hamichs_htmlelements() {
        $doc = new DOMDocument();
        $doc->loadHTML($this->hamichshtml);
        $docxpath = new DOMXPath($doc);
        $this->channels_elements = $docxpath->query("//div[@class='tvListBlock']/div[@class='list_item']");
        $this->links_elements = $docxpath->query("//div[@class='tvListBlock']/div[@class='list_item']//h3/a/@onclick");
        $this->titles_elements = $docxpath->query("//div[@class='tvListBlock']/div[@class='list_item']//h3/a/text()");
        $this->channel_icon_elements = $docxpath->query("//div[@class='tvListBlock']/div[@class='list_item']//img/@src");
        $this->programtime_elements = $docxpath->query("//div[@class='tvListBlock']/div[@class='list_item']//div[@class='time']/text()");
    }
    function get_channel_details() {
        $hamilinks = array();
        $hamivideochids = array();
        $channel_icons = array();
        $chnames = array();
        foreach ($this->channels_elements as $key=>$channel) {
            $hamilink = $this->links_elements[$key]->nodeValue;
            $hamilink = preg_match("/sendUrl\(\'(.+\.do)\',/", $hamilink, $hamilink_matches);
            $hamilink = $this->hamihost.$hamilink_matches[1];
            $hamilinks[] = $hamilink;
            $hamivideochids[] = str_replace(".do", "", basename($hamilink));
            $channel_icons[] = $this->channel_icon_elements[$key]->nodeValue;
            $chnames[] = $this->titles_elements[$key]->nodeValue;
        }
        $this->hamilinks = $hamilinks;
        $this->hamivideochids = $hamivideochids;
        $this->channel_icons = $channel_icons;
        $this->chnames = $chnames;
    }
    function generate_hami_playlist() {
        $hamivideo_playlist_for_kodi = "";
        $hamivideo_playlist_for_tvheadend = "";
        foreach ($this->channels_elements as $key=>$channel) {
            $tp = $this->playlist_template;
            $tp = str_replace("PNG", $this->channel_icons[$key], $tp);
            $tp = str_replace("TVGID", $this->hamivideochids[$key], $tp);
            $tp = str_replace("TVGNAME", str_replace(" ", "_", $this->chnames[$key]), $tp);
            $tp = str_replace("CHANNELNAME", $this->chnames[$key], $tp);
            $hamivideo_playlist_for_kodi .= str_replace("M3U8", "plugin://plugin.video.hamivideo/play/hami/".urlencode($this->hamilinks[$key]), $tp)."\n";
            $hamivideo_playlist_for_tvheadend .= str_replace("M3U8", "pipe:///home/pi/Documents/scripts/iptvmerge/iptvffmpegsh.sh ".$this->hamilinks[$key], $tp)."\n";
        }
        $hamivideo_playlist_for_kodi = trim($hamivideo_playlist_for_kodi);
        $hamivideo_playlist_for_tvheadend = trim($hamivideo_playlist_for_tvheadend);
        return(array($hamivideo_playlist_for_kodi, $hamivideo_playlist_for_tvheadend));
    }
    function get_chepg_in_a_specific_time($hamivideochid, $retrieve_date=NULL) {
        $retrieve_date = is_null($retrieve_date) ? date("Y-m-d") : $retrieve_date;
        $epgposturls = 'https://hamivideo.hinet.net/channel/epg.do';
        #$epgch = $hamivideochids_to_channelnames[$hamivideochid];
        #$epgchs[] = $epgch;
        $hamiepg_req_opts = array(
            CURLOPT_REFERER=>'https://hamivideo.hinet.net/hamivideo/channel/'.$hamivideochid.".do",
            CURLOPT_SSL_VERIFYPEER=>FALSE
        );
        $epgpostdatas = array(
            'contentPk' => $hamivideochid,
            'date' => $retrieve_date
        );
        #$tp_epgresults = array();
        #$tp_epgresults[] 
        $tp_epgresult = getSslPage($epgposturls, $post=TRUE, $epgpostdatas, $hamiepg_req_opts);
        #$epgpostdatas['date'] = date("Y-m-d",time()+60*60*24);
        #$tp_epgresults[] = getSslPage($epgposturls, $post=TRUE, $epgpostdatas, $hamiepg_req_opts);
        #$tp_epgresults = array_map('json_decode', $tp_epgresults, array(TRUE,TRUE) );
        #$tp_epgresults = array_reduce($tp_epgresults, 'array_merge_recursive', array());
        #$epgresults[] = $tp_epgresults;
        $hamivideochname = array_combine($this->hamivideochids, $this->chnames);
        $hamivideochname = $hamivideochname[$hamivideochid];
        $tp_epgresult = json_decode($tp_epgresult, TRUE);
        $tp_epgresult = array_map2(function($chres, $chid, $chname) {
            $chres['chid'] = $chid;
            $chres['chname'] = $chname;
            foreach ($chres as $k=>$v) {
                $chres[$k] = htmlspecialchars($v);
            }
            return($chres);
        },$tp_epgresult,$hamivideochid,$hamivideochname);
        return($tp_epgresult);
    }
    function get_all_epgs_in_days($hamivideochids,$days=2) {
        #$get_single_epg_func = $this->get_chepg_in_a_specific_time;
        $all_epgs = array();
        for ($day=0;$day<$days;$day++) {
            $all_epgs = array_merge_recursive(array_map(
                array($this, 'get_chepg_in_a_specific_time'), #$get_single_epg_func,
                $hamivideochids,
                array_fill(0,count($hamivideochids), date("Y-m-d",time()+60*60*24*$day))
            ), $all_epgs);
        }
        $all_epgs = array_reduce($all_epgs, 'array_merge_recursive', array());
        return($all_epgs);
    }
}


#mobilelive-hamivideo.cdn.hinet.net
?>