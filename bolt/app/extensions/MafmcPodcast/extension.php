<?php
/**
 * MafmcPodcast extension for Bolt.
 *
 * @author Jacob Tolar <jacob@sheckel.net>
 * @author Jeffrey Tolar <jeffrey@tolarnet.us>
 */

namespace MafmcPodcast;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

define('SERMON_LIST_BASE', 'sermons/data'); # Relative to extension root
define('SERMON_LIST_CACHE', 'cache.html'); # (Relative to SERMON_LIST_BASE)
define('SERMON_LIST_CACHE_PODCAST', 'podcast.cache.xml'); # (Relative to SERMON_LIST_BASE)
define('SERMON_LIST_CACHE_INVALID', 'lastUpload.txt'); # (Relative to SERMON_LIST_BASE). Should be updated each time a file is uploaded (signals a rebuild of the cache)

class Extension extends \Bolt\BaseExtension
{

    public function info()
    {
        return array(
            'name' => "MafmcPodcast",
            'description' => "Sermons for MAFMC",
            'author' => "Jacob Tolar",
            'link' => "http://bolt.cm",
            'version' => "0.1",
            'required_bolt_version' => "1.2.0",
            'highest_bolt_version' => "1.4.0",
            'type' => "General",
            'first_releasedate' => null,
            'latest_releasedate' => null,
            'priority' => 10
        );
    }

    public function initialize()
    {
        $this->app['htmlsnippets'] = true;

        /*
        if (empty($this->config['color'])) {
            $this->config['color'] = "red";
        }
        */
        $this->addTwigFunction('sermons', 'sermonList');

        return;
    }

    public function sermons(Request $request)
    {
        return "Sermons 123";
    }

    public function sermonList()
    {
        // Probably doesn't belong here, but...
        $this->addJquery();
        $this->addJavascript( "external/mediaelement/build/mediaelement-and-player.min.js", true );
        $this->addJavascript( "external/ScrollToFixed/jquery-scrolltofixed-min.js", true );
        $this->addJavascript( "external/jquery.browser/dist/jquery.browser.min.js", true );
        $this->addJavascript( "js/sermon_list.js", true );
        $this->addCSS("external/mediaelement/build/mediaelementplayer.min.css");
        $this->addCSS("css/sermon_list.css");

        // example of accessing config; unused
        // $color = $this->config['color'];

        $sermons_basepath = $this->basepath . '/' . SERMON_LIST_BASE;

        $cache_path = $sermons_basepath . '/' . SERMON_LIST_CACHE;
        $iv_path = $sermons_basepath . '/' . SERMON_LIST_CACHE_INVALID;

        // return cached file if possible
        if (file_exists($iv_path) && file_exists($cache_path)) {
            $cache_time = filemtime($cache_path);
            if (filemtime(__FILE__) < $cache_time && filemtime($iv_path) < $cache_time) {
                return new \Twig_Markup(file_get_contents($cache_path), 'UTF-8');
            }
        }

        $file_path = $sermons_basepath . '/sermons.json';

        $sermons = $this->get_sermon_list($file_path);

        # filter out those which don't have files
        $sermons = array_filter($sermons, function(&$v)  use($sermons_basepath) {
            $result = !(!$v['audio'] || !file_exists($sermons_basepath . '/' . $v['audio']));
            return !(!$v['audio'] || !file_exists($sermons_basepath . '/' . $v['audio']));
        });
        $sermons = array_values($sermons);

        // FIXME: We shouldn't have to hardcode the path here
        array_walk($sermons, function(&$v, $k) {
            $v['audio'] = '/bolt/app/extensions/MafmcPodcast/sermons/data/' . $v['audio'];
        });


        $this->app['twig.loader.filesystem']->addPath(__DIR__, 'MafmcSermons');
        $html = $this->app['render']->render("@MafmcSermons/sermons-template.twig", array(
            'sermons' => $sermons
        ));

       // FIXME: add caching
       return new \Twig_Markup($html, 'UTF-8');

    }

    /* Will return sermons in order by series, newest series first */
    public function get_sermon_list($json_file = 'sermons.json') {

        // Load file
        $file_content = file_get_contents($json_file);
        $json = json_decode($file_content, true);
        $sermons = $json['sermons'];

        // reverse sort $sermons: newest date on top
        usort($sermons, function($a, $b) {
            $x = $a['date'];
            $y = $b['date'];
            return ($x == $y ? 0 : ($x < $y ? 1 : -1));
        });

        return $sermons;
    }
}
