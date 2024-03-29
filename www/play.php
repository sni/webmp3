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

error_reporting(2047);
declare(ticks = 1);

### INCLUDES ###
include("config.php");
$config["searchPath"] = realpath($config["searchPath"]);
include("include/common.php");
include("plugins.php");
include("include/Action.php");
checkForUptodateTagCache();

#################################################################
#
# action_default()
#
#################################################################

function action_default()
{
    global $config;
    $data = getData();

    if(isset($data["ppid"])) {
        $data = killChild($data);
    }

    if(isset($data["curTrack"]) AND isset($data["playlist"][$data["curTrack"]])) {
        $track = $data["playlist"][$data["curTrack"]];
    } else {
        $tmp = $data["playlist"];
        $track = array_shift($tmp);
        $data["curTrack"] = $track["token"];
    }
    doPrint("playing: ".$track["filename"]);

    # clean up playlist
    if($data['partymode'] == 1) {
        # clean everything up to 3 songs after the current one
        $x = 0;
        $num = 0;
        foreach($data['playlist'] as $token => $tr) {
            if($tr['token'] == $track["token"]) {
                $num = $x;
                continue;
            }
            $x++;
        }
        $num = $num -3;
        $num = max(0, $num);
        for($x = 0; $x < $num; $x++) {
            array_shift($data["playlist"]);
        }
    }
    elseif($data['partymode'] == 2) {
        # clean everything up to the first song of the cur album
        $newPlaylist = array();
        $found = 0;
        foreach($data['playlist'] as $token => $tr) {
            if($tr['token'] == $track["token"] or $tr['album'] == $track['album']) {
                $found = 1;
            }
            if($found == 1) {
                $newPlaylist[$token] = $tr;
            }
        }
        $data['playlist'] = $newPlaylist;
    }

    $data["start"]       = time();
    $data["length"]      = $track["lengths"];
    $data["title"]       = $track["title"];

    $data["artist"]      = $track["artist"];
    $data["album"]       = $track["album"];
    $data["track"]       = $track["tracknum"];
    $data["token"]       = $track["token"];
    $data["filename"]    = $track["filename"];
    if(!isset($track["bitrate"])) { $track["bitrate"] = ""; }
    $data["bitrate"]     = $track["bitrate"];
    $data["play"]        = 1;
    $data["gmtimestart"] = gmdate("U");
    $data["playingPic"]  = getPictureForPath(dirname($track["filename"]));

    if(isset($config["notifyCommand"])) {
        $tmp = $config["notifyCommand"];
        $tmpTrack  = $track["tracknum"];
        $tmpArtist = $track["artist"];
        $tmpTitle  = $track["title"];
        $tmpArtist = str_replace('\'', '', $tmpArtist);
        $tmpArtist = str_replace('\"', '', $tmpArtist);
        $tmpArtist = str_replace(';',  '', $tmpArtist);
        $tmpTitle = str_replace('\'', '', $tmpTitle);
        $tmpTitle = str_replace('\"', '', $tmpTitle);
        $tmpTitle = str_replace(';',  '', $tmpTitle);
        $tmpTrack = str_replace('\'', '', $tmpTrack);
        $tmpTrack = str_replace('\"', '', $tmpTrack);
        $tmpTrack = str_replace(';',  '', $tmpTrack);
        $tmp = str_replace("%#", $tmpTrack, $tmp);
        $tmp = str_replace("%T", $tmpTitle, $tmp);
        $tmp = str_replace("%A", $tmpArtist, $tmp);
        doPrint("notify: ".$tmp);
        $output = "";
        $rc     = 0;
        exec($tmp, $output, $rc);
        if($rc != 0) {
          doPrint("notify failed: ".join("\n", $output));
        }
    }

    $data["ppid"] = getmypid();

    # most played
    addFileToHitlist($track["filename"]);

    if(strpos($track["filename"], "http://") === 0 || strpos($track["filename"], "https://") === 0) {
        doPrint("playing stream");
        $options = $config["playStrOpt"];
        $playBin = $config["streamBin"];
        if(!isset($config["streamUrlPre"])) { $config["streamUrlPre"] = ""; }
        $track["filename"] = $config["streamUrlPre"].$track["filename"];
        $data["playingStream"] = 1;
    } else {
        doPrint("playing normal file");
        $tmp = explode(".", $track["filename"]);
        $ext = "." . array_pop($tmp);
        if(isset($config["ext"][$ext])) {
            $playBin = $config["ext"][$ext]["binary"];
            $options = $config["ext"][$ext]["option"];
        } else {
            doPrint("Extension $ext not supported");
        }
        $data["playingStream"] = 0;
    }
    $size = count($options);
    $options[$size-1] .= " '".$track["filename"]."'";
    #$options[] = $track["filename"];

    $data["aktBin"] = $playBin;
    storeData($data);

    #$options = array_map("myescapeshellarg", $options);
    setlocale(LC_CTYPE, "en_US.UTF-8");
    $options = array_map("escapeshellarg", $options);

    $data = brokerPlugin("pre_playing_song", $data);
    doPrint("executing: ".$playBin." ".join(" ", $options));
    system($playBin." ".join(" ", $options).' >> '.$config["logfile"].' 2>&1');

    doPrint("finished playing");

    $data = getData();
    $data = brokerPlugin("post_playing_song", $data);

    unset($data["ppid"]);
    unset($data["start"]);
    unset($data["length"]);
    unset($data["title"]);
    unset($data["track"]);
    unset($data["artist"]);
    unset($data["album"]);
    unset($data["aktBin"]);
    unset($data["playingPic"]);
    $lastToken = $track["token"];
    $track = "";
    storeData($data);

    if($data["play"]) {
        $data["play"] = 0;
        $track = getNextTrack($data["playlist"], $lastToken);
        if($track) {
            $data["curTrack"] = $track;
            storeData($data);
            action_default();
        }
    }
}

#################################################################
function myescapeshellarg($arg){
  return "'".str_replace("'","\\'",$arg)."'";
}

#################################################################

?>
