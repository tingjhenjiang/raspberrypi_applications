<?php
ini_set('display_errors', FALSE);
ini_set('display_errors', TRUE);
set_time_limit(1200);

include_once('functions.php');
$myownepgdoc = new DOMDocument('1.0', 'UTF-8');
$tvtag = $myownepgdoc->createElement('tv');

function get_processed_epgs() {
	$epgs = array(
		"http://epg.streamstv.me/epg/guide-australia.xml",
		"http://epg.streamstv.me/epg/guide-canada.xml",
		"http://epg.streamstv.me/epg/guide-france.xml",
		"http://epg.streamstv.me/epg/guide-germany.xml",
		"http://epg.streamstv.me/epg/guide-india.xml",
		"http://epg.streamstv.me/epg/guide-uk.xml",
		"http://epg.streamstv.me/epg/guide-usa.xml",
		"http://i.mjh.nz/nzau/epg.xml",
		"http://i.mjh.nz/za/DStv/epg.xml",
		"http://epg.51zmt.top:8000/e.xml"
	);
	$epgs = array_combine($epgs, $epgs);
	$epgsrcresults = getSslPages($epgs);
	$epgsrcresults = $epgsrcresults['html'];
	return($epgsrcresults);
}
#$epgsrcresults = array_merge($epgsrcresults, $local_epgs);
#$n_epgsrcresults = count($epgsrcresults);
#$epgsrcresults = array_map('mb_convert_encoding', $epgsrcresults, array_fill(0, $n_epgsrcresults, 'HTML-ENTITIES'), array_fill(0, $n_epgsrcresults, 'UTF-8') );

function get_xml_tv_from_xml($xmlepgs = array()) {
	$url_china_epg_source = "http://epg.51zmt.top:8000/e.xml";
	$targetDoc = new DOMDocument();
	foreach ($xmlepgs as $epgkey=>$epgsrcresult) {
		#continue;
		$sourceDoc = new DOMDocument();
		$sourceDoc->loadXML($epgsrcresult);
		$xpath = new DOMXPath($sourceDoc);
		$channels = $xpath->query('//channel');
		foreach ($channels as $channel) {
			$oldchannelid = $channel->attributes->getNamedItem('id')->nodeValue;
			$newchannelid = ($xmlepgs[$epgkey]==$url_china_epg_source) ? ($channel->textContent) : $oldchannelid; #http://epg.51zmt.top:8000/
			$channel->setAttribute('id', $newchannelid );
			if (strpos($newchannelid, " ")!==FALSE) {
				$newchannelid_underlined = str_replace(" ", "_", $newchannelid);
				$dispname2element = $sourceDoc->createElement('display-name', $newchannelid_underlined);
				$channel->appendChild($dispname2element);
			}
			if (preg_match("/\p{Han}+/u", $newchannelid)>0) {
				$newchannelid_tradchi = str_replace(" ", "_", $newchannelid);
				$newchannelid_tradchi = $zhconv->zhconversion_tw($newchannelid_tradchi);
				$dispname3element = $sourceDoc->createElement('display-name', $newchannelid_tradchi);
				$channel->appendChild($dispname3element);
			}
			$newchannel = $targetDoc->importNode($channel, true);
			$targetDoc->appendChild( $newchannel );
			$programmes = $xpath->query(sprintf("//programme[@channel='%s']", $oldchannelid));
			foreach ($programmes as $programme) {
				$programme->setAttribute( 'channel', $newchannelid );
				$newtitle = isset($programme->getElementsByTagName("title")->item(0)->nodeValue) ? trim($programme->getElementsByTagName("title")->item(0)->nodeValue) : NULL;
				$newdesc = isset($programme->getElementsByTagName("desc")->item(0)->nodeValue) ? trim($programme->getElementsByTagName("desc")->item(0)->nodeValue) : NULL;
				if ($xmlepgs[$epgkey]==$url_china_epg_source) {
					$newtitle = $zhconv->zhconversion_tw($newtitle);
					$newdesc = $zhconv->zhconversion_tw($newdesc);
				}
				if (!is_null($newtitle)) {
					$programme->getElementsByTagName("title")->item(0)->nodeValue = "";
					$programme->getElementsByTagName("title")->item(0)->appendChild($sourceDoc->createTextNode($newtitle));
				}
				if (!is_null($newdesc)) {
					$programme->getElementsByTagName("desc")->item(0)->nodeValue = "";
					$programme->getElementsByTagName("desc")->item(0)->appendChild($sourceDoc->createTextNode($newdesc));
				}
				$newprogramme = $targetDoc->importNode($programme, true);
				$targetDoc->appendChild( $newprogramme );
			}
		}
	}
	return($targetDoc);
}




include_once('epg_customized.php');
$epgclass = new Epg_base;
#dmpv(epg_nhk_chinese());exit;
#dmpv(get_custom_all_epgs());exit;

include_once('hamichannels.php');
$hamiclass = new Hamivideo_playlist_epg;

#$hamiDoc = $hamiDoc->saveXML();
foreach (array(
	get_xml_tv_from_xml(get_processed_epgs()),
	$epgclass->get_xml_tv(get_custom_all_epgs()),
	$hamiclass->get_xml_tv($hamiclass->get_all_epgs_in_days($hamiclass->hamivideochids, 7))
	) as $targetDoc) {
	foreach ( $targetDoc->childNodes as $node ) {
		$newnode = $myownepgdoc->importNode($node, TRUE);
		$tvtag->appendChild( $newnode );
	}
}

#$hamiDoc->preserveWhiteSpace = FALSE;
#$hamiDoc->formatOutput = TRUE;

$myownepgdoc->appendChild($tvtag);
$myownepgdoc->preserveWhiteSpace = FALSE;
$myownepgdoc->formatOutput = TRUE;
$myownepgdoc = $myownepgdoc->saveXML();
$myownepgdoc = new SimpleXMLElement($myownepgdoc);
$myownepgdoc = $myownepgdoc->asXML();

echo file_put_contents(dirname(__FILE__).'/epg.xml', $myownepgdoc);
echo "end writing epgs to ".dirname(__FILE__).'/epg.xml';
?>