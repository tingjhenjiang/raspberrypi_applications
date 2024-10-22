
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Access-Control-Allow-Origin: https://rpi4');
header('Access-Control-Allow-Origin: http://rpi4');
header('Access-Control-Allow-Origin: https://localhost');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Origin: https://192.168.1.200');
header('Access-Control-Allow-Origin: http://192.168.1.200');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept');
// etherwake -b -i 'eth1' -D 10:7B:44:7A:31:B2
function get_remote_json_with_args($url,$payload=NULL,$headers=NULL) {
    $this_header = array(
        "Referer: https://cleaner.epb.taichung.gov.tw/",
        "content-type: application/json; charset=UTF-8"
    );
    if (!is_null($headers)) {
        foreach ($headers as $key=>$value) {
            if (in_array($key,array("Authorization","User-Agent","Accept","Accept-Encoding","Connection"))) {
                array_unshift($this_header, $key.": ".$value);
            }
        }
    }
    if (is_array($payload)) {
        $payload = json_encode($payload);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    if (!is_null($payload)) {
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
    }
    // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array("abc"=>"123", "def"=>"456"))); 
    $output = curl_exec($ch);
    if ($output==FALSE) {
        echo 'Curl error: ' . curl_error($ch);
        echo 'Payload: ';
        var_dump($payload);
    }
    curl_close($ch);
    return $output;
}
function get_trash_cleaner_json(
        $url = "https://cleaner.epb.taichung.gov.tw/WebService/WsSkyeyes.asmx/NewgetCarsinfo",
        $payload = NULL,
    ) {
    $jsondata = get_remote_json_with_args($url,$payload);
    $jsondata = trim($jsondata);
    $jsondata = json_decode($jsondata,true);
    $jsondata = $jsondata['d'];
    $jsondata = json_decode($jsondata,true);
    $jsondata = $jsondata['DATA'];
    return $jsondata;
}
$_POST = file_get_contents('php://input');
if (!empty($_POST)) {
    header('Content-Type: application/json; charset=utf-8');
    $_POST = json_decode($_POST, true);
    if (array_key_exists('jsonrpc',$_POST) AND array_key_exists('method',$_POST)) {
        $jsondata = get_remote_json_with_args("http://rpi4:8080/jsonrpc",$_POST,getallheaders());
        echo( $jsondata);
    }
    if (array_key_exists('getcleanersjson',$_POST)) {
        $content = get_trash_cleaner_json();
        $content = json_encode($content);
        echo $content;
    }
    if (array_key_exists('getlocinfojson',$_POST)) {
        $content = get_trash_cleaner_json(
            "https://cleaner.epb.taichung.gov.tw/WebService/WsSkyeyes.asmx/Newgetlocation",
            '{"x":"120.61827954298732","y":"24.18570508403912","meter":"100"}'
        );
        $content = json_encode($content);
        echo $content;
    }
    if (array_key_exists('controlrpi',$_POST)) {
        file_put_contents('controlrpi.txt', $_POST['controlrpi']);
        echo '{"complete":"TRUE"}';
    }
    // echo '{"test":"TRUE"}';
} else {
?><html>
<head>
    <meta meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js" integrity="sha512-BwHfrr4c9kmRkLw6iXFdzcdWV/PGkVgiIyIWLLlTSXzWQzxuSg4DiQUCpauz/EWjgk5TYQqX/kvn9pG1NpYfqg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        #map { height: 550px; clear:both; }
        #controls a { text-decoration: underline; cursor: pointer; padding-right:10px; }
        #pad { float: right; }
        #pad table { overflow-x:auto; }
        #pad td.singlechar, span.singlechar { font-size: 50px; text-align:center; font-weight: bold; padding:5px; }
        #pad td.multiplechar, span.multiplechar { font-size: 15px; text-align:center; font-weight: bold; padding:5px; }
        .active { color:red; }
    </style>
    <script type="text/javascript">
        function make_base_auth(user, password) {
            var tok = user + ':' + password;
            var hash = Base64.encode(tok);
            return "Basic " + hash;
        }
    </script>
</head>
<body>
    <div id="map"></div>
    <div id="pad">
        <table>
            <caption>Kodi Pad User <input id="kodi_auth_user" size="5" type="user" value="kodi" /> PW <input id="kodi_auth_pass" size="5" type="password" value="" /> <br />Stream URL <br /><textarea id="kodi_input_text" size="15" type="text"></textarea><input type="button" id="to_clear_kodi_input_text" value="clear" /></caption>
            <tr>
                <td class="singlechar" id="Player.PlayPause">&#x23f8;</td><td class="singlechar" id="Input.SendText">T</td><td class="singlechar" id="Application.Quit">Q</td><td class="multiplechar" id="Playlist.Insert">Insert</td>
            </tr>
            <tr>
                <td class="singlechar" id="Player.Stop">&#x20e0;</td><td class="singlechar" id="Player.GoToPrevious">&#x23EE;</td><td class="singlechar" id="Input.Up">&#x23eb;</td><td class="singlechar" id="Player.GoToNext">&#x23ED;</td>
            </tr>
            <tr>
                <td class="singlechar" id="Player.Open.Stream">&#x1F4C2;</td><td class="singlechar" id="Input.Left">&#x23ea;</td><td class="singlechar" id="Input.Select">&#9608;</td><td class="singlechar" id="Input.Right">&#x23e9;</td>
            </tr>
            <tr>
                <td class="singlechar" id="Input.ShowOSD">O</td><td class="singlechar" id="Input.Back">↙</td><td class="singlechar" id="Input.Down">&#x23ec;</td><td class="singlechar" id="Input.Info">Ⅰ</td>
            </tr>
            <tr>
                <td class="multiplechar" id="Input.ContextMenu">ContxM</td><td class="singlechar" id="Player.SeekBackward"><span class="multiplechar">BWD</span><br />◃</td><td class="singlechar" id="PVR.Record">&#9673;</td><td class="singlechar" id="Player.SeekForward"><span class="multiplechar">FWD</span><br />▹</td>
            </tr>
            <tr>
                <td class="singlechar" id="System.Reboot">RB</td><td class="multiplechar"></td><td class="singlechar" id="Player.SetSubtitle.On">&#x1F50B;<br /><span class="multiplechar">subtitle</span></td><td class="singlechar" id="Player.SetSubtitle.Off">&#x1F50C;<br /><span class="multiplechar">subtitle</span></td>
            </tr>
        </table>
        <script type="text/javascript">
            var timeoutKodiId = 0;
            var mousedownLoopBreak = true;
            document.getElementById("to_clear_kodi_input_text").addEventListener("click", function() {
                document.getElementById("kodi_input_text").value = "";
            });
            async function sendreqtokodi(sendMethod="",otherparam=null) {
                switch (sendMethod) {
                    case "Input.SendText":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": sendMethod, "id":1, "params":[otherparam[0], true] });
                        break;
                    case "Player.Open.Stream":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.Open", "id":1, "params":{
                            "item": {
                                "file": "plugin://plugin.video.hamivideo/play/direct/"+encodeURIComponent(otherparam[0])
                            }
                        }});
                        break;
                    case "Playlist.GetItems":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": sendMethod, "id":1, "params":[1,["title"]] });
                        break;
                    case "Player.GetProperties.NowPlaying":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.GetProperties", "id":1, "params":{"playerid":1,"properties":["currentaudiostream"]} });
                        break;
                    case "Playlist.Insert":
                        payloadToSend = null;
                        jsonObject = null;
                        jsonObject = await sendreqtokodi("Player.GetProperties.NowPlaying");
                        now_playing = jsonObject["result"]["currentaudiostream"]!=null;
                        queueFile = "plugin://plugin.video.hamivideo/play/direct/"+encodeURIComponent(otherparam[0]);
                        console.log("now_playing is "+now_playing);
                        if (now_playing) {
                            jsonObject = await sendreqtokodi("Playlist.GetItems");
                            loopi = 1;
                            while (true) {
                                // console.log("within loop "+loopi);
                                loopi += 1;
                                if (jsonObject !== null) {
                                    break;
                                }
                            }
                            numCurrentListItems = jsonObject["result"]["limits"]["end"]
                            payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": sendMethod, "id":1, "params":[1,numCurrentListItems,{"file":queueFile}] });
                        } else {                            
                            payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.Open", "id":1, "params":{
                                "item": {
                                    "file": "plugin://plugin.video.hamivideo/play/direct/"+encodeURIComponent(otherparam[0])
                                }
                            }});
                        }
                        break;
                    case "Player.Stop":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.Stop", "id":1, "params":{"playerid": 1}});
                        break;
                    case "Player.PlayPause":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.PlayPause", "id":1, "params":{"playerid": 1}});
                        break;
                    case "Player.GoToPrevious":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.GoTo", "id":1, "params":{"playerid": 1,"to":"previous"}});
                        break;
                    case "Player.GoToNext":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.GoTo", "id":1, "params":{"playerid": 1,"to":"next"}});
                        break;
                    case "Player.SeekForward":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.Seek", "id":1, "params":{"playerid":1,"value":{"seconds":5}}});
                        break;
                    case "Player.SeekBackward":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.Seek", "id":1, "params":{"playerid":1,"value":{"seconds":-13}}});
                        break;
                    case "Player.SetSubtitle.On":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.SetSubtitle", "id":1, "params":{"playerid":1,"subtitle":"on"}});
                        break;
                    case "Player.SetSubtitle.Off":
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": "Player.SetSubtitle", "id":1, "params":{"playerid":1,"subtitle":"off"}});
                        break;
                    default:
                        payloadToSend = JSON.stringify({"jsonrpc": "2.0", "method": sendMethod, "id":1, "params":{} } );
                        break;
                }
                req_kodi_jsonrpc_url = "/kodijsonrpc"
                return new Promise((resolve, reject) => {
                    $.ajax({
                        type: 'POST',
                        url: req_kodi_jsonrpc_url, // Replace with your server endpoint
                        data: payloadToSend, // Your JSON payload //
                        headers: {
                            "Access-Control-Allow-Origin": "*",
                            "Access-Control-Allow-Methods": "DELETE, POST, GET, OPTIONS",
                            "Access-Control-Allow-Headers": "Content-Type, Authorization, X-Requested-With",
                            // "Access-Control-Request-Headers": "x-requested-with",
                            "Authorization": "Basic " + btoa($( "#kodi_auth_user" ).val() + ":" + $( "#kodi_auth_pass" ).val())
                        },
                        contentType: 'application/json; charset=utf-8',
                        dataType: 'json',
                        success: function(response, textStatus) {
                            console.log( textStatus+" completes." );
                            resolve(response);
                        },
                        error: function(xhr) {
                            alert("發生錯誤: " + xhr.status + " " + xhr.statusText);
                            reject("發生錯誤: " + xhr.status + " " + xhr.statusText);
                        }
                    });
                });
            }
            function createFilterArrowsFunction(index, element, inverse=false) {
                filter_res = element.id.match(/(Up|Left|Right|Down)/);
                filter_res = (inverse) ? filter_res===null : !(filter_res===null);
                // /^((?!(Up|Left|Right|Down)).)*$/
                return filter_res;
            }
            $( "#pad" ).find("td[id]").filter( function(index,element) {return createFilterArrowsFunction(index,element,true);} ).on("touchstart mousedown", function(event) {
                event.preventDefault();
                sendreqtokodi(this.id,  [$("#kodi_input_text").val().replace(/^\s+|\s+$/g, '')]  );
                $(this).addClass('active');
            }).bind('touchend mouseup mouseleave mouseleave onmouseout', function(event) {
                $(this).removeClass('active');
            });
            $( "#pad" ).find("td[id]").filter( function(index,element) {return createFilterArrowsFunction(index,element,false);} ).on( "mousedown touchstart", function(event) {
                mousedownLoopBreak = false;
                $(this).addClass('active');
                for (var i = 0; i < 1001; i++) {
                    if (mousedownLoopBreak==false) {
                        if (i <= 1000) {
                            console.log('in down loop '+i);
                        }
                    }
                }
                timeoutKodiId = setInterval(function (x,y) {sendreqtokodi(x,y)}, 100, this.id, $("#kodi_input_text").val() );
            }).bind('mouseup mouseleave touchend mouseleave onmouseout', function(event) {
                $(this).removeClass('active');
                mousedownLoopBreak = true;
                clearInterval(timeoutKodiId);
            });
        </script>
    </div>
    <div id="controls">
        <a id="reboot">REBOOT</a>
        <a href="https://rpi4/tv/">TVHeadend[1]</a>
        <a href="https://192.168.1.200/tv/">TVHeadend[2]</a>
        <script type="text/javascript">
            $( "#controls" ).find("a[id]").on( "click", function() {

                $.ajax({
                    type: 'POST',
                    url: './cleaner.php', // Replace with your server endpoint
                    data: JSON.stringify({'controlrpi':this.id}), // Your JSON payload //
                    headers: {},
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json',
                    success: function(response, textStatus) {
                        alert( textStatus+" completes." );
                    },
                    error: function(xhr) {
                        alert("發生錯誤: " + xhr.status + " " + xhr.statusText);
                    }
                });

            } );
        </script>
    </div>
    <script type="text/javascript">
    var trashTruck = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/7823/7823532.png',

        iconSize:     [40, 40], // size of the icon
        // iconAnchor:   [22, 94], // point of the icon which will correspond to marker's location
        // popupAnchor:  [-3, -76] // point from which the popup should open relative to the iconAnchor
    });
    var recycleTruck = L.icon({
        iconUrl: 'https://cleaner.epb.taichung.gov.tw/img/car/recycle_o03.png',

        iconSize:     [40, 40], // size of the icon
        // iconAnchor:   [22, 94], // point of the icon which will correspond to marker's location
        // popupAnchor:  [-3, -76] // point from which the popup should open relative to the iconAnchor
    });
    // 建立 Leaflet 地圖
    var map = L.map('map');

    // 設定經緯度座標
    map.setView(new L.LatLng(24.18590508403912, 120.61967954298732), 18);
    // 設定圖資來源
    var osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    var osm = new L.TileLayer(osmUrl, {minZoom: 8, maxZoom: 19});
    map.addLayer(osm);

    </script>
    <div id="info">
    </div>
    <script type="text/javascript">
        var targetElement = '#info';
        var trucklist = [];
        var newTrucklist = [];
        const check_trucks = [ "052-Q3", "113-VP"];
        var sleepSetTimeout_ctrl;
        // if (Date.now()%10==0) {
        timerId = setInterval(function(){
            $.ajax({
                type: 'POST',
                url: './cleaner.php', // Replace with your server endpoint
                data: JSON.stringify({'getcleanersjson':'true'}), // Your JSON payload //
                headers: {},
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(response) {
                    // Handle the response here
                    var $ul = $('<ul>');
                    newTrucklist = [];
                    $.each(response, function(index, item) {
                        var $li = $('<li>').text(item.car_licence + ': ' + item.caption + ', ' + item.dt);
                        // Create a list item for the current element
                        if (check_trucks.includes(item.car_licence)) { //check_trucks.includes(item.car_licence)
                            $li.addClass('active');
                        }
                        // Append the list item to the unordered list
                        $ul.append($li);
                        if (item.cartype=="R") {
                            truckIcon = recycleTruck;
                        } else {
                            truckIcon = trashTruck;
                        }
                        newTrucklist.push([
                            L.marker([item.y, item.x], {icon: truckIcon}),
                            item.caption,
                            item.car_licence
                        ]);
                    });
                    for (i in trucklist) {
                        map.removeLayer(trucklist[i][0]);
                    }
                    trucklist = newTrucklist;
                    for (i in trucklist) {
                        trucklist[i][0].addTo(map).bindPopup("<strong>"+trucklist[i][2]+"</strong><br>"+trucklist[i][1]);
                    }
                    $(targetElement).empty();
                    $(targetElement).html($ul);
                },
                error: function(xhr) {
                    alert("發生錯誤: " + xhr.status + " " + xhr.statusText);
                }
            });
        }, 10000)

    </script>
</body>
</html>
<?php
}
?>
