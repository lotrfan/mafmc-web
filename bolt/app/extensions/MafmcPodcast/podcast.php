<?php
//require( '/home/jeffrey/public_html/mafmcwww/wp-load.php' );

header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);

if (file_exists(sermon_list_get_root(SERMON_LIST_CACHE_INVALID)) && file_exists(sermon_list_get_root(SERMON_LIST_CACHE_PODCAST))) {
    $cache_time = filemtime(sermon_list_get_root(SERMON_LIST_CACHE_PODCAST));
    if (filemtime(__FILE__) < $cache_time) {
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $cache_time) {
                header('HTTP/1.0 304 Not Modified');
                exit;
            }
        if (filemtime(sermon_list_get_root(SERMON_LIST_CACHE_INVALID)) < $cache_time) {
            readfile(sermon_list_get_root(SERMON_LIST_CACHE_PODCAST));
            exit;
        }
    }
}

require_once 'getid3/getid3.php';
require_once 'getid3/extension.cache.sqlite3.php';
$getID3 = new getID3_cached_sqlite3('getid3_cache', true);
$getID3->encoding = 'UTF-8';

$xml = new DOMDocument("1.0", get_option('blog_charset'));

$rss = $xml->createElement("rss");
$rss->setAttribute("xmlns:itunes", "http://www.itunes.com/dtds/podcast-1.0.dtd");
$rss->setAttribute("xmlns:atom", "http://www.w3.org/2005/Atom");
$rss->setAttribute("version", "2.0");

$channel = $xml->createElement("channel");

$channel_infos = array(
    "title" => get_bloginfo("name"),
    "link" => get_bloginfo("siteurl"),
    "description" => get_bloginfo("description"),
    "itunes:author" => get_bloginfo("name"),
    "copyright" => "© " . get_bloginfo("name"),
    "language" => "en-us",
    "pubDate" => date(DATE_RSS),
    "lastBuildDate" => date(DATE_RSS),
    //"itunes:category" => "FIXME",
    "itunes:explicit" => "No",
    "itunes:subtitle" => get_bloginfo("name") . " Sermons",
    "itunes:summary" => get_bloginfo("description"),
);

foreach ($channel_infos as $key => $val) {
    $channel->appendChild($xml->createElement($key, $val));
}

//$element = $xml->createElement("itunes:image");
//$element->setAttribute("href", "FIXME");
//$channel->appendChild($element);

$element = $xml->createElement("atom:link");
$element->setAttribute("href", $_SERVER["SCRIPT_URI"]);
$element->setAttribute("rel", "self");
$element->setAttribute("type", "application/rss+xml");
$channel->appendChild($element);

$element = $xml->createElement("itunes:owner");
$element->appendChild($xml->createElement("itunes:name", get_bloginfo("description")));
$element->appendChild($xml->createElement("itunes:email", "mafmcmail@mattisave.org"));
$channel->appendChild($element);


$sermons = get_sermon_list('date');
foreach ($sermons as $s) {
    if (!$s["audio"] || !file_exists(sermon_list_get_root($s["audio"]))) continue;
    $title = $s["title"];
    if ($s["series"]) { $title = $s["series"] . ": " . $title; }
    $desc = $s["description"];
    $date = intval($s["date"]);
    $info = $getID3->analyze(sermon_list_get_root($s["audio"]));

    $item = $xml->createElement("item");
    $item->appendChild($xml->createElement("itunes:author", $s["speaker"]));
    $item->appendChild($xml->createElement("itunes:keywords"));
    $item->appendChild($xml->createElement("itunes:duration", @$info["playtime_string"]));
    $item->appendChild($xml->createElement("title", $title));
    $item->appendChild($xml->createElement("description", $desc));
    //$item->appendChild($xml->createElement("category", "FIXME"));
    $item->appendChild($xml->createElement("pubDate", date(DATE_RSS, $date)));

    $element = $xml->createElement("guid");
    $element->appendChild($xml->createTextNode(sermon_list_get_guid($s)));
    $element->setAttribute("isPermaLink", "true");
    $item->appendChild($element);

    $element = $xml->createElement("enclosure");
    $element->setAttribute("length", @$info['filesize']);
    $element->setAttribute("url", sermon_list_get_audio($s));
    $element->setAttribute("type", "audio/mpeg");
    $item->appendChild($element);

    $channel->appendChild($item);
}


$rss->appendChild($channel);
$xml->appendChild($rss);

$xml->save(sermon_list_get_root(SERMON_LIST_CACHE_PODCAST));

print $xml->saveXML();


exit;
?>
