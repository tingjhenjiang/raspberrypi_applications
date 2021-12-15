<?php
ini_set('display_errors', FALSE);
#ini_set('display_errors', TRUE);
set_time_limit(1200);
include_once("functions.php");

function ret_streams_for_a_list($streams) {
    $streams = array_combine($streams, $streams);
    $tvgs = getSslPages($streams);
    $tvgs = array_map('process_m3u8_content', $tvgs['html']);
    return($tvgs);
}

function process_m3u8_content($m3u8_content) {
    $m3u8_content = preg_split("/(\r|\n){1,2}/", $m3u8_content);
    $m3u8_content = array_map('trim', $m3u8_content);
    $m3u8_content = array_filter($m3u8_content); #, function ($x) { return ($x!=""); }
    $m3u8_content = array_slice($m3u8_content,1);
    for ($i=count($m3u8_content)-2;$i>=0;$i=$i-2) {
        $m3u8_content[$i] = $m3u8_content[$i]."\n".$m3u8_content[$i+1];
        unset($m3u8_content[$i+1]);
    }
    $m3u8_content = array_map('replace_tvgnameinfo', $m3u8_content);
    $m3u8_content = implode("\n", $m3u8_content);
    return(trim($m3u8_content));
}

$output_ytm3u8_list = TRUE;
#$output_ytm3u8_list = FALSE;
include_once("tw_yt_live_channels.php");
include_once("hamichannels.php");
$hamiclass = new Hamivideo_playlist_epg;
list($hamivideo_playlist_for_kodi, $hamivideo_playlist_for_tvheadend) = $hamiclass->generate_hami_playlist();

$streams = ret_streams_for_a_list([
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/tw.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/hk.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/mo.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/sg.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/int.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/us.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/jp.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/kr.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/au.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/nz.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/uk.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/cn.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/za.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/fr.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/de.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/ph.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/es.m3u",
"https://raw.githubusercontent.com/iptv-org/iptv/master/channels/nl.m3u",

#"https://iptv-org.github.io/iptv/languages/zh.m3u" #,
#"https://iptv-org.github.io/iptv/languages/en.m3u",
#"https://iptv-org.github.io/iptv/languages/fr.m3u",
#"https://iptv-org.github.io/iptv/languages/de.m3u",
#"https://iptv-org.github.io/iptv/languages/ko.m3u",
#"https://iptv-org.github.io/iptv/languages/ja.m3u",
#"https://iptv-org.github.io/iptv/languages/undefined.m3u"
]);

$kodi_streams = ret_streams_for_a_list(["http://i.mjh.nz/nzau/kodi-tv.m3u8",]);
$tvheadend_streams = ret_streams_for_a_list(["http://i.mjh.nz/nzau/tvh-tv.m3u8",]);
$tw_yt_live_videos_m3u8_infos = generate_tw_yt_live_videos_m3u8_infos();

$m3u_firstline = "#EXTM3U\n";
#string type data must be included in an array first(array_merge targets at array)
$final_m3u = array_merge([$m3u_firstline, $hamivideo_playlist_for_kodi, $tw_yt_live_videos_m3u8_infos['kodi']],
    #arrayInsertAfterKey($streams, "https://raw.githubusercontent.com/iptv-org/iptv/master/channels/kr.m3u", $kodi_streams)
    $streams
    );
$final_m3u = array_filter($final_m3u);
$final_m3u = array_map('trim', $final_m3u);
$final_m3u = implode("\n", $final_m3u);
#dumpv($final_m3u);exit;
#dumpv(dirname(__FILE__).'/iptvstream.m3u');exit;
echo file_put_contents(dirname(__FILE__).'/iptvstream.m3u', $final_m3u);


$final_m3u = array_merge([$m3u_firstline, $hamivideo_playlist_for_tvheadend, $tw_yt_live_videos_m3u8_infos['tvheadend']],
    #arrayInsertAfterKey($streams, "https://raw.githubusercontent.com/iptv-org/iptv/master/channels/kr.m3u", $tvheadend_streams)
    $streams
    );
$final_m3u = array_map('trim', $final_m3u);
$final_m3u = implode("\n", $final_m3u);
echo file_put_contents(dirname(__FILE__).'/iptvstream_tvheadend.m3u', $final_m3u);

?>