<?php
ini_set('display_errors', FALSE);
ini_set('display_errors', TRUE);
include_once("functions.php");
set_time_limit(1200);
$hami_ips = ["hamivideo.hinet.net",
    "static-hamivideo.cdn.hinet.net",
    "apl-hamivideo.cdn.hinet.net",
    "weblive-hamivideo.cdn.hinet.net",
    "member.emome.net",
    "member.hamicloud.net",
    "tjhome.crabdance.com",
];
$hami_ips = array_map(function($ip) {
    exec(sprintf("host %s | grep \"has address\" | sed 's/has address/-/g'", $ip), $output, $return_var);
    return($output);
}, $hami_ips);
$hami_ips = array_reduce($hami_ips, 'array_merge', array());
$hami_ips = array_map(function ($x) {return("route = ".trim(explode(" - ", $x)[1])."/255.255.255.255");}, $hami_ips);
array_unshift($hami_ips, "route = 192.168.1.0/255.255.255.0");
array_unshift($hami_ips, "route = 192.168.10.0/255.255.255.0");
array_unshift($hami_ips, "explicit-ipv4 = 192.168.1.202");
array_unshift($hami_ips, "interface = vpns+");
$hami_ips = implode("\n", $hami_ips);
file_put_contents("/home/tj/Documents/scripts/iptvmerge/hami", $hami_ips);
file_put_contents("/etc/ocserv/config-per-user/hami", $hami_ips);
dumpv($hami_ips);
?>
