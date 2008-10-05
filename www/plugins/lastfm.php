<?php
#################################################################
#
# Copyright 2008 Sven Nierlein, <sven@nierlein.de>
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
#################################################################
#
# $Id$
#
#################################################################

#################################################################
# webmp3PluginLastFM
#
# connects to lastfm and submits the current playing song
# as described in: http://www.audioscrobbler.net/development/protocol/
# or http://www.lastfm.de/api/submissions
#
class webmp3PluginLastFM
{
    public function __call($type, $arguments)
    {
        global $config;
        if(   !isset($config["lastfm_user"]) or empty($config["lastfm_user"])
           or !isset($config["lastfm_pass"]) or empty($config["lastfm_pass"])
           or !isset($config["lastfm_url"])  or empty($config["lastfm_url"])
        ) {
            # no lastfm configuration found
            return($arguments);
        }

        if(!extension_loaded("curl")) {
            # required extension not loaded
            doPrint("lastfm: plugin requires curl extension", "WARNING");
            return($arguments);
        }

        if($type == "pre_playing_song") {
            $data = $arguments[0];
            if(isset($data['playingStream']) AND $data['playingStream'] == 0) {
                $this->sendSongToLastFM($data, 1);
            }
        }
        if($type == "post_playing_song" or $type == "user_pressed_stop" or $type == "user_pressed_next") {
            $data = $arguments[0];
            if(isset($data['playingStream']) AND $data['playingStream'] == 0) {
                $this->sendSongToLastFM($data);
            }
        }
        return $arguments;
    }

    function sendSongToLastFM($data, $nowplaying = 0) {
        $data = $this->lastFMHandshake($data);
        if(isset($data['lastfm_submission']) AND isset($data['lastfm_sessionid'])) {
            if(   !isset($data["title"])  or empty($data["title"])
               or !isset($data["artist"]) or empty($data["artist"])
               or !isset($data["album"])  or empty($data["album"])
            ) {
                return(1);
            }

            $length = $data["length"];
            $title  = $data["title"];
            $artist = $data["artist"];
            $album  = $data["album"];
            $track  = $data["track"];
            $time   = $data["gmtimestart"];

            $played_sec = gmdate("U") - $time;
            if($nowplaying == 0) {
                if($played_sec < 30) {
                    doPrint("lastfm: song must have been played for at least 30 seconds, this one has: ".$played_sec);
                    return(1);
                }
            }
            if($length < 30) {
                doPrint("lastfm: songs have to be at least 30 seconds long, this one has: ".$length);
                return(1);
            }
            if($nowplaying == 0) {
               doPrint("lastfm: sending last played song");
               $url = $data['lastfm_submission']."?a[0]=".urlencode($artist)."&t[0]=".urlencode($title)."&b[0]=".urlencode($album)."&l[0]=".$length."&n[0]=".$track."&i[0]=".$time."&o[0]=P&r[0]=&m=";
            } else {
               doPrint("lastfm: sending current playing song");
               $url = $data['lastfm_nowplaying']."?a=".urlencode($artist)."&t=".urlencode($title)."&b=".urlencode($album)."&l=".$length."&n=".$track."&m=";
            }
            $cont = explode("\n", $this->urlSend($url, 1, 0, $data["lastfm_sessionid"]));
            if(isset($cont[0]) AND $cont[0] == "OK") {
              doPrint("lastfm: submission ok");
           } else {
                doPrint("lastfm: submission failed: ".join("\n", $cont));
            }
        } else {
            doPrint("lastfm: cant send: handshake failed");
        }
    }

    function lastFMHandshake($data) {

        global $config;

        if(isset($data["lastfm_sessionid"])) {
            return($data);
        }

        if(isset($data["lastfm_last_error"])) {
            if(time() < $data["lastfm_last_error"] + $data["lastfm_error_count"] * 120) {
                doPrint("lastfm: last error was: ".formatDateTime($data["lastfm_last_error"]).", no trying again too soon.");
                return($data);
            }
        }

        if(   !isset($config["lastfm_user"]) or empty($config["lastfm_user"])
           or !isset($config["lastfm_pass"]) or empty($config["lastfm_pass"])
           or !isset($config["lastfm_url"])  or empty($config["lastfm_url"])
        ) {
            return($data);
        }

        $now  = time();
        $auth = md5(md5($config["lastfm_pass"]).$now);

        $url = $config["lastfm_url"]."?hs=true&p=1.2&c=tst&v=1.0&u=".urlencode($config["lastfm_user"])."&t=".$now."&a=".$auth;
        $cont = explode("\n", $this->urlSend($url, 0, 0, ""));
        if(isset($cont[0]) AND $cont[0] == "OK") {
            doPrint("lastfm: handshake ok");
            unset($data["lastfm_last_error"]);
            unset($data["lastfm_error_count"]);
            doPrint($cont);
            $data["lastfm_sessionid"] = $cont[1];
            $data["lastfm_nowplaying"] = $cont[2];
            $data["lastfm_submission"] = $cont[3];
            storeData($data);
        } else {
            doPrint("lastfm: handshake failed: ".join("\n", $cont), "ERROR");
            $data["lastfm_last_error"] = time();
            if(!isset($data["lastfm_error_count"])) { $data["lastfm_error_count"] = 0; }
            $data["lastfm_error_count"]++;
            unset($data["lastfm_sessionid"]);
            unset($data["lastfm_nowplaying"]);
            unset($data["lastfm_submission"]);
            storeData($data);
        }
        return($data);
    }

    function urlSend($url, $post = 0, $retries = 0, $session = "") {
        global $config;

        $new_url = $url;
        if($session != "") {
            $new_url = $url."&s=".$session;
        }
        doPrint("lastfm: url ".$new_url);

        $ch = curl_init($new_url);
        curl_setopt ($ch, "CURLOPT_USERAGENT", "Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
        curl_setopt ($ch, "CURLOPT_TIMEOUT",   "10");
        if(isset($config["lastfm_proxy"]) AND !empty($config["lastfm_proxy"])) {
          curl_setopt($ch, CURLOPT_PROXY, $config["lastfm_proxy"]);
        }
        if($post == 1) {
            $postOpts = preg_replace("/.*?\?/", "", $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postOpts);
        }

        ob_start();
        if(!curl_exec($ch)) {
            $err = curl_error($ch);
            doPrint("lastfm: Curl Error", "ERROR");
            doPrint("lastfm: ".curl_error($ch), "ERROR");
            return($err);
        }
        $cont = ob_get_contents();
        ob_end_clean();
        curl_close ($ch);

        $con = explode("\n", $cont);
        if($con[0] == "BADSESSION") {
          doPrint("lastfm: session expired, retry(".$retries.")");
          $data = getData();
          unset($data["lastfm_sessionid"]);
          unset($data["lastfm_nowplaying"]);
          unset($data["lastfm_submission"]);
          $data = $this->lastFMHandshake($data);
          if(isset($data['lastfm_submission']) AND isset($data['lastfm_sessionid']) AND $retries <= 0) {
            $retries++;
            doPrint("lastfm: trying once more");
            sleep(2);
            $this->urlSend($url, $post, $retries, $data["lastfm_sessionid"]);
          }
        }

        if(empty($cont)) {
            $cont = "UNKNOWN";
        }
        #doPrint("lastfm: Content:");
        #doPrint($cont);
        return($cont);
    }
}

#################################################################
# initialize this plugin and register it globally
$lastfm = new webmp3PluginLastFM();
register_plugin("lastfm", $lastfm);
#doPrint("lastfm: plugin loaded", "DEBUG");

?>
