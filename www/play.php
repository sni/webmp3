<?php

error_reporting(2047);
declare(ticks = 1);

### INCLUDES ###
include("config.php");
$config["searchPath"] = realpath($config["searchPath"]);
include("common.php");
include("Template.php");

#################################################################
#
# action_default()
# sig_handler()
#
#################################################################

function action_default()
{
    global $config;
    $data = getData();

    if(isset($data["ppid"])) {
        killChild();
    }

    if(isset($data["curTrack"]) AND isset($data["playlist"][$data["curTrack"]])) {
        $track = $data["playlist"][$data["curTrack"]];
    } else {
        $tmp = $data["playlist"];
        $track = array_shift($tmp);
        $data["curTrack"] = $track["token"];
    }
    doPrint("playing: ".$track["filename"]);

    $data["start"]  = time();
    $data["length"] = $track["lengths"];
    $data["title"]  = $track["title"];

    $data["artist"]   = $track["artist"];
    $data["album"]    = $track["album"];
    $data["track"]    = $track["tracknum"];
    $data["token"]    = $track["token"];
    $data["play"]     = 1;

    $data["playingPic"] = getPictureForPath(dirname($track["filename"]));

    if(isset($config["notifyCommand"])) {
        $tmp = $config["notifyCommand"];
        $tmpDisp = $track["display"];
        $tmpDisp = str_replace('\'', '', $tmpDisp);
        $tmpDisp = str_replace('\"', '', $tmpDisp);
        $tmpDisp = str_replace(';', '', $tmpDisp);
        $tmp = str_replace("%T", $tmpDisp, $tmp);
        doPrint("notify: ".$tmp);
        system($tmp) or doPrint("Err: ".$php_errormsg);
    }

    $data["ppid"] = getmypid();

    # most played
    addFileToHitlist($track["filename"]);

    if(strpos($track["filename"], "http://") === 0) {
        doPrint("playing stream");
        $options = $config["playStrOpt"];
        $playBin = $config["streamBin"];
        $track["filename"] = $config["streamUrlPre"].$track["filename"];
        $data["playingStream"] = 1;
    } else {
        doPrint("playing normal file");
        $ext = substr($track["filename"], -4);
        if(isset($config["ext"][$ext])) {
            $playBin = $config["ext"][$ext]["binary"];
            $options = $config["ext"][$ext]["option"];
        } else {
            doPrint("Extension $ext not supported");
        }
        $data["playingStream"] = 0;
    }
    $options[] = "'".$track["filename"]."'";

    $data["aktBin"] = $playBin;
    storeData($data);
    doPrint("executing: ".$playBin." ".join(" ", $options));
    system($playBin." ".join(" ", $options).' >> '.$config["logfile"].' 2>&1');

    doPrint("finished playing");

    $data = getData();
    unset($data["ppid"]);
    unset($data["start"]);
    unset($data["length"]);
    unset($data["title"]);
    unset($data["track"]);
    unset($data["artist"]);
    unset($data["album"]);
    $data["play"]     = 0;
    $track = getNextTrack($data["playlist"], $track["token"]);
    if($track) {
        $data["curTrack"] = $track;
        storeData($data);
        action_default();
    } else {
        storeData($data);
    }
}

#################################################################

include("Action.php");

?>