<?php
ini_set('display_errors', False);
ini_set('display_errors', False);
set_time_limit(1200);
include_once("functions.php");
$tw_yt_video_playlist_template = $playlist_template;
$kodi_yt_addon_url_prefix = 'plugin://plugin.video.youtube/play/?video_id=';

$base_web_url_to_scripts = file_get_contents("base_web_url_to_scripts.txt"); #要在這個檔案裡面填入連結到這個script的http server基礎網頁連結例如 https://XXX.com/iptvmerge/
$self_path_in_apache = $base_web_url_to_scripts.'tw_yt_live_channels.php';
$self_path_in_cli = $_SERVER["PWD"].'/'.$_SERVER["PHP_SELF"];
$youtube_api_key = file_get_contents("youtube_api_key.txt"); #要在這個檔案裡面填入youtube api key

//if (isset($output_ytm3u8_list) and $output_ytm3u8_list==TRUE) {
function generate_tw_yt_live_videos_m3u8_infos() {
    global $tw_yt_video_playlist_template;
    $tw_live_channelids = array(
        '公共電視(YT)'=>'UCXgIO9jJVsX5_2ideiSkfvA',
        '公視台語台(YT)'=>'UCX6SRupi5lTDbIFJEOpReCQ',
        '客家電視台(YT)'=>'UCdqg95tSArchPfQaAWquDwg', #UCyGUGDvCeXapYlu8FYZHFag
        '華視新聞(YT)'=>'UCA_hK5eRICBdSOLlXKESvEg',#4,
        #'台視直播(YT)'=>'UCzZe-zMu-YgVFQfDmsFG_VQ',
        '台視新聞(YT)'=>'UCzZe-zMu-YgVFQfDmsFG_VQ',
        '民視新聞(YT)'=>'UClIfopQZlkkSpM1VgCFLRJA',#6, UC2VmWn8dAqkzlQqvy02E1PA
        'TVBS新聞(YT)'=>'UC5nwNW4KdC0SzrhF9BXEYOQ',#7,
        '東森新聞(YT)'=>'UCR3asjvr_WAaxwJYEDV_Bfw',#8,
        '東森財經新聞(YT)'=>'UCuzqko_GKcj9922M1gUo__w',
        '東森美洲電視(YT)'=>'UCnPFekXZy67zHjX8p3uyGdw',
        '中視新聞(YT)'=>'UCmH4q-YjeazayYCVHHkGAMA',
        '中天新聞(YT)'=>'UC5l1Yto5oOIgRXlI4p4VKbw',#11,
        '三立新聞(YT)'=>'UC2TuODJhC03pLgd6MpWP0iw',#12, UC2TuODJhC03pLgd6MpWP0iw
        '三立iNews(YT)'=>'UCoNYj9OFHZn3ACmmeRCPwbA',
        'YOYO TV(YT)'=>'UCiWRSesvSYmY7YOyz0tv_zQ',
        '華視綜藝(YT)'=>'UCdpxNQgqL3276yjrK03gMXA',
        '民視戲劇館(YT)'=>'UCiMiEL1XRXANaypB2wEdr5w',
        '愛爾達綜合台(YT)'=>'UC1OmzW062Tci8eDdjrX-OHg',
        '愛爾達戲劇台(YT)'=>'UC42G_aqGPKozJq5clSG4vuA',
        '大愛(YT)'=>'UClrEYreVkBee7ZQYei_6Jqg',
        'Mr. Bean(YT)'=>'UCkAGrHCLFmlK3H2kd6isipg',
        'Mr. Bean Cartoon Network(YT)'=>'UCzoFjzSkbrDD1GHsz2YNLig',
        'WB Kids(YT)'=>'UC9trsD1jCTXXtN3xIOIU8gg',
        #14
    );
    $tw_live_videos_matching_epgnames = array(
        '公視'=>'/(公共電視|PTS Live)/',
        '公視2'=>'/(公視台語台|PTS Taigi)/',
        '客家電視台'=>'/客家電視/',
        '台視新聞台'=>'/台視新聞/',
        '民視新聞台'=>'/民視新聞/',
        'TVBS新聞台'=>'/TVBS新聞/',
        '東森新聞台'=>'/東森新聞/',
        '東森財經新聞台'=>'/東森財經新聞/',
        '東森美洲電視'=>'/東森美洲/',
        '中視新聞'=>'/中視新聞/',
        '中天新聞台'=>'/中天新聞/',
        '三立新聞台'=>'/(三立新聞|三立LIVE新聞|SET Live NEWS|SET LIVE)/',
        '三立財經新聞台'=>'/(三立iNews|iNEWS 最正新聞台|SET iNEWS)/',
        '大愛一台'=>'/大愛一/',
        '大愛二台'=>'/大愛二/',
        '大愛海外'=>'/大愛海外/',
        '東森幼幼台'=>'/YOYO TV/',
        '華視綜藝'=>'/華視綜藝/',
        '民視戲劇館'=>'/民視戲劇/',
        '愛爾達綜合台'=>'/愛爾達綜合/',
        '愛爾達戲劇台'=>'/愛爾達戲劇/',
    );
    $tw_livestreams_of_yt_channels = array_map(function($channelid) {
        global $youtube_api_key;
        return("https://www.googleapis.com/youtube/v3/search?part=snippet&eventType=live&maxResults=4&order=date&type=video&key=".$youtube_api_key."&channelId=".$channelid);
    }, $tw_live_channelids);
    $tw_livestreams_of_yt_channels = array_map(function($x) { global $getSslPage; sleep(0.5); $r=getSslPage($x); return($r); }, $tw_livestreams_of_yt_channels);
    $tw_livestreams_of_yt_channels = array_map('json_decode', $tw_livestreams_of_yt_channels, array_fill(0, count($tw_live_channelids), TRUE));

    $tw_livestreams_of_yt_channels = array_map(function ($x,$key) {
        global $kodi_yt_addon_url_prefix, $tw_live_videos_matching_epgnames, $self_path_in_apache, $self_path_in_cli;
        if (array_key_exists('items', $x) and count($x['items'])>0) {
            $lives_of_a_channels = array();
            foreach ($x['items'] as $item) {
                foreach ($tw_live_videos_matching_epgnames as $others_made_epg_chname=>$pattern) {
                    $matchresult = preg_match($pattern, $item["snippet"]["channelTitle"].$item["snippet"]["title"].$item["snippet"]["description"].$item["snippet"]["channelTitle"], $matches);
                    $match_chepg_name = ($matchresult==1) ? $others_made_epg_chname : $item["snippet"]["title"];
                    if ($matchresult==1) break;
                }
                $lives_vids_of_a_channel[] = array(
                    'videoid'=>$item['id']["videoId"],
                    'etag'=>$item['etag'],
                    'myownchkey'=>$key,
                    'channelid'=>$item["snippet"]["channelId"],
                    'title'=>$item["snippet"]["title"],
                    'description'=>$item["snippet"]["description"],
                    'thumbnails_small'=>$item["snippet"]["thumbnails"]["default"]["url"],
                    'thumbnails_large'=>$item["snippet"]["thumbnails"]["high"]["url"],
                    'channeltitle'=>$item["snippet"]["channelTitle"],
                    'liveBroadcastContent'=>$item["snippet"]["liveBroadcastContent"],
                    'match_chepg_name'=>$match_chepg_name,
                    'kodi_ytplugin_url'=>'plugin://plugin.video.youtube/play/?video_id='.$item['id']["videoId"],
                    'path_in_apache_from_tvheadend'=>$self_path_in_apache.'?mode=tvheadend&video_id='.$item['id']["videoId"],
                    'path_in_cli'=>$self_path_in_cli." --video_id ".$item['id']["videoId"],
                    'ytdlphp'=>$self_path_in_apache."?mode=ytdlphp&video_id=".$item['id']["videoId"],
                );
            }
            return($lives_vids_of_a_channel);
        } else {
            return("");
        }
    }, $tw_livestreams_of_yt_channels, array_keys($tw_live_channelids) );
    $tw_livestreams_of_yt_channels = array_filter($tw_livestreams_of_yt_channels);
    $tw_livestreams_of_yt_channels = array_reduce($tw_livestreams_of_yt_channels, 'array_merge_recursive', array());

    #generating m3u8 content
    $tw_yt_live_videos_m3u8_infos_for_kodi = $tw_yt_live_videos_m3u8_infos_for_tvheadend = array();
    foreach ($tw_livestreams_of_yt_channels as $key=>$videoarr) {
        $tp = str_replace("PNG", $videoarr['thumbnails_large'], $tw_yt_video_playlist_template);
        $tp = str_replace("TVGID", $videoarr['videoid'], $tp);
        $tp = str_replace("TVGNAME", $videoarr['match_chepg_name'], $tp);
        $tp = str_replace("CHANNELNAME", $videoarr['title'], $tp);
        $tp_kodi = str_replace("M3U8", $videoarr['ytdlphp'], $tp);
        $tp_tvheadend = str_replace("M3U8", $videoarr['ytdlphp'], $tp);
        $tw_yt_live_videos_m3u8_infos_for_kodi[$videoarr['videoid']] = $tp_kodi;
        $tw_yt_live_videos_m3u8_infos_for_tvheadend[$videoarr['videoid']] = $tp_tvheadend;
    }
    $tw_yt_live_videos_m3u8_infos_for_kodi = array_unique($tw_yt_live_videos_m3u8_infos_for_kodi);
    $tw_yt_live_videos_m3u8_infos_for_kodi = trim(implode("\n", $tw_yt_live_videos_m3u8_infos_for_kodi));
    $tw_yt_live_videos_m3u8_infos_for_tvheadend = array_unique($tw_yt_live_videos_m3u8_infos_for_tvheadend);
    $tw_yt_live_videos_m3u8_infos_for_tvheadend = trim(implode("\n", $tw_yt_live_videos_m3u8_infos_for_tvheadend));
    return array(
        'channels_array'=>$tw_livestreams_of_yt_channels,
        'kodi'=>$tw_yt_live_videos_m3u8_infos_for_kodi,
        'tvheadend'=>$tw_yt_live_videos_m3u8_infos_for_tvheadend
    );
    
}

if (isset($_GET['video_id']) && $_GET['video_id'] ) {

    if ($_GET['mode']=='ytdlphp') {
        $source_yt_url = "https://www.youtube.com/watch?v=".$_GET['video_id'];
        $referer_yt_m3u8_command = "youtube-dl -f best -g ".$source_yt_url;
        $referer_yt_m3u8 = trim(shell_exec($referer_yt_m3u8_command));
        #dmpv($referer_yt_m3u8);exit;
        #$base_web_url_to_scripts/tw_yt_live_channels.php?mode=ytdlphp&video_id=JAzRXylm3M0
        header("Referrer-Policy: no-referrer");
        header("Location: ".$referer_yt_m3u8);
        exit;
    }

    $sample_req_opts = array(
        CURLOPT_HTTPHEADER => array(
            'sec-fetch-dest: document',
            'sec-fetch-mode: navigate',
            'sec-fetch-site: none',
            'sec-fetch-user: ?1',
            'upgrade-insecure-requests: 1',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            #'accept-encoding: gzip, deflate, br',
            'accept-language: zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7'
        ),
        #CURLOPT_PROXY=>"14.207.125.216:8080",#"14.207.125.216:8080",
        CURLOPT_REFERER=>'https://www.youtube.com',
        CURLOPT_SSL_VERIFYPEER=>FALSE
    );
    $tw_yt_live_videos_infos = 'https://www.youtube.com/get_video_info?&video_id='.$_GET['video_id'];

    exit;
    #http://localhost:62555/?
    if (!empty($tw_yt_live_videos_streamingurl) and !is_null($tw_yt_live_videos_streamingurl)) {
        if ($_GET['video_id']) {
            header("Location: ".$tw_yt_live_videos_streamingurl);
        }
    }
}

?>