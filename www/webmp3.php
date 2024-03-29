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

define("WEBMP3_VERSION", "1.1");

error_reporting(2047);

### INCLUDES ###
include("config.php");
if($config["accControl"] == 1 AND isset($_SERVER["REMOTE_ADDR"]) AND !in_array($_SERVER["REMOTE_ADDR"], $config["allowedIPs"])) {
    die($_SERVER["REMOTE_ADDR"]." ist nicht zugelassen");
}

if(isset($_SERVER["REMOTE_ADDR"])) {
    ob_start("ob_gzhandler");
}

include("include/common.php");
include("include/Template.php");
include("include/getid3/getid3.php");
include("plugins.php");
include("include/Action.php");

#################################################################
#
# action_default()
# action_setVolume()
# action_pic()
# action_savePlaylist()
# action_getPlaylists()
# action_doDelete()
# action_updateTagCache()
# action_getFilesystem()
# action_getPlaylist()
# action_setToggle()
# action_getPath()
# action_getCurStatus()
# action_getHitlist()
#
#################################################################

function action_default()
{
    global $config;
    $data = getData();

    $data = fillInDefaults($data);

    $playText  = "Play";
    $pageTitle = "WebMP3";
    if($data["play"]) {
        $pageTitle = $data['track']. " - ".$data['title'];
        $playText = "Stop";
    }
    $muteText = "Mute";
    if($data["mute"]) {
        $muteText = "Unmute";
    }

    list($remMin, $remSec, $remaining, $stream, $started) = getRemaining($data);
    $pre = "-";
    if($stream) {
      $pre = "";
    }

    $data = brokerPlugin("main_page_loading", $data);

    $t = new template();
    $t -> main("include/templates/webmp3.tpl");
    $t -> code(array(
        "version"       => "WebMP3 ".WEBMP3_VERSION,
        "pageTitle"     => htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8"),
        "volume"        => getVolume(),
        "repeat"        => $data["repeat"],
        "quiet"         => $data["quiet"],
        "muteText"      => $muteText,
        "mute"          => $data["mute"],
        "playText"      => $playText,
        "play"          => $data["play"],
        "pause"         => $data["pause"],
        "artist"        => htmlspecialchars($data["artist"], ENT_QUOTES, "UTF-8"),
        "album"         => htmlspecialchars($data["album"], ENT_QUOTES, "UTF-8"),
        "track"         => $data["track"],
        "title"         => htmlspecialchars($data["title"], ENT_QUOTES, "UTF-8"),
        "token"         => $data["token"],
        "partymode"     => $data["partymode"],

        "pre"           => $pre,
        "remMin"        => $remMin,
        "remSec"        => $remSec,
        "remaining"     => $remaining,
        "stream"        => $stream,
        "started"       => $started,
    ));
    $temp = $t -> return_template();
    print $temp;
}

#################################################################

function action_setVolume()
{
    global $config;
    if(!isset($_REQUEST["vol"])) {
      print "no params??";
      exit;
    }
    if(isset($_REQUEST["reset"]) AND $_REQUEST["reset"] == 1) {
        $data = getData();
        $data["mute"]  = 0;
        $data["quiet"] = 0;
        if(isset($data['origVolume'])) {
           unset($data['origVolume']);
        }
        storeData($data);
    }
    exec($config["volumeBin"]." ".escapeshellarg($_REQUEST["vol"]));

    doPrint("setting volume to ".$_REQUEST["vol"]);
}

#################################################################

function action_pic() {
    global $config;

    $data = getData();

    $dst_w = 120;
    $dst_h = 120;
    if(isset($_REQUEST['token']) AND isset($data["curTrack"])) {
        if($data["curTrack"] == $_REQUEST['token']) {
            $dir = dirname($data["filename"]);
            $dir = str_replace($config["searchPath"], "", $dir);
            $_GET["pic"] = $dir;

        } elseif(isset($data['playlist'][$_REQUEST['token']])) {
            $dir = dirname($data['playlist'][$_REQUEST['token']]['filename']);
            $dir = str_replace($config["searchPath"], "", $dir);
            $_GET["pic"] = $dir;
        } else {
            $_GET["pic"] = "-1";
        }
    }

    if(!isset($_GET["pic"]) OR empty($_GET["pic"])) {
        $_GET["pic"] = "-1";
    }

    $url = $config["searchPath"].getPath($_GET["pic"]);
    $url = preg_replace("/\/+/", "/", $url);
    $url = preg_replace("/\/$/", "", $url);
    doPrint("got pic request for: ".$url);

    # search a folder icon
    $url = getPictureForPath($url);

    if(file_exists($url)) {
        if(isset($_GET["full"]) AND $_GET["full"] == "yes") {
            $tmp = explode(".", $url);
            $ext = "." . array_pop($tmp);
            $ct = "text/plain";
            if($ext == ".png") {
                $ct = "image/png";
            }
            if($ext == "jpeg") {
                $ct = "image/jpg";
            }
            if($ext == ".jpg") {
                $ct = "image/jpg";
            }
            if($ext == ".gif") {
                $ct = "image/gif";
            }
            header("Content-type: ".$ct);
            readfile($url);
            exit();
        }

        if(isset($data["cachedPic"]) AND $url == $data["cachedPic"] AND is_file("./var/cache.jpg")) {
            # is there a cached one?
            doPrint("got pic from cache");
            header("Content-type: image/jpeg");
            readfile("./var/cache.jpg");
            exit();
        }

        switch(exif_imagetype($url)) {
            case 1: $img = imagecreatefromgif($url);
                    break;
            case 2: $img = imagecreatefromjpeg($url);
                    break;
            case 3: $img = imagecreatefrompng($url);
                    break;
            case 4: $img = imagecreatefromwbmp($url);
                    break;
        }
        list($w, $h) = getimagesize($url);
        $dst = imagecreatetruecolor($dst_w, $dst_h);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $dst_w, $dst_h, $w, $h);

        header("Content-type: image/jpeg");
        imagejpeg($dst);

        #doPrint("-".$data["playingPic"]."-");
        #doPrint("-".$url."-");
        if(isset($data["playingPic"]) AND $data["playingPic"] == $url) {
            doPrint("saved pic to cache");
            imagejpeg($dst, "./var/cache.jpg");
            $data["cachedPic"] = $url;
            storeData($data);
        }
    } else {
        print $url." is not a file";
    }
}

#################################################################

function action_savePlaylist()
{
    global $config;

    if(!isset($_REQUEST["name"]) OR empty($_REQUEST["name"])) {
        action_getCurStatus("saving playlist failed");
        exit;
    }

    $_REQUEST["name"] = str_replace(".", "", $_REQUEST["name"]);
    $_REQUEST["name"] = preg_replace("/[^\s\d\w]/", "", $_REQUEST["name"]);

    if(empty($_REQUEST["name"])) {
        action_getCurStatus("saving playlist failed");
        exit;
    }

    doPrint("saving playlist: ".$_REQUEST["name"]);
    //doPrint($_REQUEST);

    # check if our playlist dir exists
    if(!file_exists($config["plDir"])) {
      @mkdir($config["plDir"]);
    }

    $data = getData();

    $file = "";
    foreach($data["playlist"] as $entry) {
        if(isset($entry["stream"]) AND $entry["stream"] == 1) {
            $file .= "STREAM::".str_replace($config["searchPath"], "", $entry["filename"])."\n";
        } else {
            $file .= str_replace($config["searchPath"], "", $entry["filename"])."\n";
        }
    }

    $_REQUEST["name"] .= " - ".$data["totalTime"]."min ".count($data["playlist"])." files.playlist";

    $saveFile = $config["plDir"]."/".$_REQUEST["name"];
    $saveFile = preg_replace("/\/+/", "/", $saveFile);

    $fp = fopen($saveFile, "a+") or user_error("cannot open file");
    fputs($fp, $file);
    fclose($fp);

    action_getCurStatus("saved playlist to ".$saveFile);
}

#################################################################

function action_getPlaylists()
{
    global $config;
    #doPrint($_REQUEST);

    $start = 0;
    $limit = 20;
    if(isset($_REQUEST['start']) AND is_numeric($_REQUEST['start'])) { $start = $_REQUEST['start']; }
    if(isset($_REQUEST['limit']) AND is_numeric($_REQUEST['limit'])) { $limit = $_REQUEST['limit']; }
    #doPrint("got json playlist load request (".$start."/".$limit.")");

    $list = array();

    if(!is_dir($config["plDir"])) {
        echo '({"total":"0", "results":""})';
        exit;
    }

    if($handle = opendir($config["plDir"])) {
        while (false !== ($file = readdir($handle))) {
            if (is_file($config["plDir"].$file) AND $file != "." AND $file != "..") {
                list($fileName,$meta) = split(" - ", $file);
                $fileName = utf8_encode($fileName);
                $meta     = utf8_encode($meta);
                $meta = str_replace(".playlist", "", $meta);
                $list[$fileName] = array(
                            "file"  => $fileName,
                            "info"  => $meta,
                            "ctime" => date("d:m:Y H:i", filectime($config["plDir"].$file)),
                );
            }
        }
        closedir($handle);
    }
    ksort($list);
    $list = array_values($list);

    $count = count($list);
    $list = array_slice($list, $start, $limit);

    if(count($list) > 0) {
        $data = json_encode($list);
        echo '({"total":"'.$count.'","results":'.$data.'})';
    } else {
        echo '({"total":"0", "results":""})';
    }
}

#################################################################

function action_deletePlaylist()
{
    global $config;

    doPrint("deleting playlist: ".$_REQUEST["name"]);
    if(isset($_REQUEST["name"]) AND is_file($config["plDir"].$_REQUEST["name"])) {
        doPrint("deleting playlist: ".$_REQUEST["name"]);

        if(!preg_match("/\w+ - .*\.playlist/", $_REQUEST["name"])) {
          doPrint("invalid playlist: ".$_REQUEST["name"]);
          exit;
        }

        unlink($config["plDir"].$_REQUEST["name"]);
    }
    action_getPlaylists();
}

#################################################################

function action_updateTagCache()
{
    global $config;
    doPrint("starting tag cache update...");
    print formatDateTime()."\n";

    $data = getData();
    $data["lastTagUpdate"] = time();
    storeData($data);

    $oldTags = array();
    foreach(file($config["tagCache"]) as $tag) {
        $tag = trim($tag);
        $tagArray = explode(";-;", $tag);
        if(!isset($tagArray[0]) or count($tagArray) != 8) {
            doPrint("removed invalid entry in tagcache: ".count($tagArray));
            doPrint($tagArray);
        } else {
            $oldTags[$tagArray[0]] = $tagArray;
        }
    }
    doPrint("got old tag cache");

    $tagCache = array();

    $files = getFilesForDirectory($config["searchPath"]);

    $new     = 0;
    $old     = 0;
    $updates = 0;

    $fp = fopen($config["tagCache"], "w+") or die("cannot open tagCache File for writing");
    foreach($files as $file) {
        #if(substr($file, -5) == '.flac') {
        #    continue;
        #}
        $scan = 0;
        $newTime = filemtime($config["searchPath"]."/".$file);
        if(!isset($oldTags[$file])) {
            doPrint("file is new: ".$file);
            $scan = 1;
            $new++;
        } elseif($newTime != $oldTags[$file][7]) {
            #doPrint("file changed and needs update: ".$file);
            $scan = 1;
            $updates++;
        } else {
          #doPrint("file unchanged: ".$file);
          $old++;
        }
        if(!$scan and isset($oldTags[$file])) {
          $fileinfo = $oldTags[$file];
        } else {
          $fileinfo = getTag($config["searchPath"]."/".$file);
          array_push($fileinfo, $newTime);
          array_unshift($fileinfo, $file);
        }
        fwrite($fp, join(";-;", $fileinfo)."\n");
    }

    $data = getData();
    $data["lastTagUpdate"] = time();
    storeData($data);

    fclose($fp);
    print "wrote tag cache\n";
    print formatDateTime()."\n";
    doPrint("finished tag cache update...");
    doPrint("new:    ".$new);
    doPrint("update: ".$updates);
    doPrint("old:    ".$old);
}

#################################################################

function action_getFilesystem()
{
    global $config;
    //doPrint($_REQUEST);

    if(!isset($_REQUEST['aktPath'])) {
        $aktPath = "";
    } else {
        $aktPath = $_REQUEST['aktPath'];
    }
    if(!isset($_REQUEST['append'])) {
        $append = "";
    } else {
        $append = $_REQUEST['append'];
    }

    $newAktPath = getPath($aktPath, $append);
    doPrint("got json filesystem get request for ('".$aktPath."', '".$append."'): ".$newAktPath);
    $aktPath = $newAktPath;

    if(!file_exists($config["searchPath"].$aktPath)) {
        doPrint("file does not exist: ".$config["searchPath"].$aktPath);
        return(0);
    }

    $allowed = getAccess($aktPath);

    $filesystem = array();
    $files = array();
    $dirs  = array();

    if(!isset($_REQUEST['query']) or empty($_REQUEST['query'])) {
        # get Files from Filesystem
        if ($handle = opendir($config["searchPath"].$aktPath)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if(is_dir($config["searchPath"].$aktPath."/".$file)) {
                        $dirs[] = $file;
                    } else {
                        $tmp = explode(".", $file);
                        $ext = "." . array_pop($tmp);
                        if(in_array($ext, array_keys($config["ext"]))) {
                            $files[] = $file;
                        }
                    }
                }
            }
            closedir($handle);
        }
    } else {
        # get Files from Search
        $search = $_REQUEST['query'];
        $start = 0;
        $limit = 20;
        if(isset($_REQUEST['start']) AND is_numeric($_REQUEST['start'])) { $start = $_REQUEST['start']; }
        if(isset($_REQUEST['limit']) AND is_numeric($_REQUEST['limit'])) { $limit = $_REQUEST['limit']; }
        doPrint("searched for: ".$search." (".$start."/".$limit.")");
        $contents = "";
        $handle = popen("grep -i ".escapeshellarg($search)." ".$config["tagCache"], "r");
        while (!feof($handle)) {
            $contents .= fread($handle, 8192);
        }
        $filesArr = explode("\n", $contents);
        foreach($filesArr as $tmpFile) {
            $tmpFile   = trim($tmpFile);
            $fileArray = explode(";-;", $tmpFile);
            if(!empty($fileArray[0])) {
                $filesystem[] = array("file" => $fileArray[0], "type" => "F", "icon" => "images/music.png");
            }
        }
        sort($filesystem);
        $count = count($filesystem);
        $filesystem = array_slice($filesystem, $start, $limit);
    }
    natcasesort($dirs);
    natcasesort($files);

    if(!$allowed) {
      $filesystem = array();
      $files      = array();
      $dirs       = array();
    }
    if($aktPath != "" AND $aktPath != "/") {
        array_unshift($filesystem, array("file" => "..",  "type" => "D", "icon" => "images/spacer.png"));
    }

    foreach($dirs as $dir) {
        $filesystem[] = array("file" => $dir,  "type" => "D", "icon" => "images/folder.png");
    }
    foreach($files as $file) {
        $filesystem[] = array("file" => $file, "type" => "F", "icon" => "images/music.png");
    }

    # add aktPath Info
    array_unshift($filesystem, array("file" => $aktPath,  "type" => "A", "icon" => ""));

    if(!isset($count)) {
        $count = count($filesystem);
    }

    if(count($filesystem) > 0) {
        $data = json_encode($filesystem);
        echo '({"total":"'.$count.'","results":'.$data.'})';
    } else {
        echo '({"total":"0", "results":""})';
    }
}

#################################################################

function action_getPlaylist()
{
    doPrint("got json playlist request");
    //doPrint($_REQUEST);

    global $config;
    $data = getData();

    if(isset($_REQUEST["move"]) AND is_array($_REQUEST["move"])) {
        doPrint("reorderd playlist");
        $newPlaylist = array();
        foreach($_REQUEST['move'] as $key)
        {
            $track = $data['playlist'][$key];
            unset($data['playlist'][$key]);
            $newPlaylist[$key] = $track;
        }
        #add not moved ones to the end
        foreach($data['playlist'] as $key => $track) {
            $newPlaylist[$key] = $track;
        }
        $data["playlist"] = $newPlaylist;
        storeData($data);
    }

    if(isset($_REQUEST["add"]) AND is_array($_REQUEST["add"])) {
        if(!isset($_REQUEST["aktPath"])) { $_REQUEST["aktPath"] = ""; }
        $aktPath = strip_tags($_REQUEST["aktPath"]);
        $aktPath = stripslashes($_REQUEST["aktPath"]);
        foreach($_REQUEST["add"] as $file) {
            $file = stripslashes($file);
            $file = trim($file);
            $allowed = getAccess($aktPath."/".$file);

            if(!$allowed) {
              doPrint("ip is not allowed to add ".$aktPath."/".$file);
            } elseif(file_exists($config["searchPath"].$aktPath."/".$file)) {
                doPrint("added file ".$aktPath."/".$file);
                $data["playlist"] = playlistAdd($data["playlist"], $config["searchPath"].$aktPath."/".$file);
            }
            elseif(strpos($file, "http://") === 0 || strpos($file, "https://") === 0) {
                doPrint("added stream ".$file);
                $data["playlist"] = playlistAdd($data["playlist"], $file);
            } else {
              doPrint("action_getPlaylist(): dont know what to do with: ".$config["searchPath"].$aktPath."/".$file);
            }
        }
        $data = recalcTotalPlaytime($data);
        storeData($data);
    }

    if(isset($_REQUEST["remove"]) AND is_array($_REQUEST["remove"])) {
        foreach($_REQUEST["remove"] as $token) {
            if(isset($data["playlist"][$token])) {
                doPrint("removed file ".$data["playlist"][$token]['title']);
                unset($data["playlist"][$token]);
            }
        }
        $data = recalcTotalPlaytime($data);
        storeData($data);
    }

    if(isset($_REQUEST["clear"])) {
        doPrint("pressed clear");
        $data["playlist"]   = array();
        $data["totalTime"]  = "0";
        $data["cachedPic"]  = "";
        $data["playingPic"] = "";
        $data["curTrack"]   = "";
        storeData($data);
    }
    if(isset($_REQUEST["shuffle"])) {
        doPrint("pressed shuffle");
        shuffle($data["playlist"]);
        $newData = array();
        foreach($data["playlist"] as $blah)
        {
            $newData[$blah["token"]] = $blah;
        }
        $data["playlist"] = $newData;
        storeData($data);
    }

    if(isset($_REQUEST["sort"])) {
        doPrint("pressed sort");
        $tmp = sortMultiArray($data["playlist"], "filename");
        $newData = array();
        foreach($tmp as $blah)
        {
            $newData[$blah["token"]] = $blah;
        }
        $data["playlist"] = $newData;
        storeData($data);
    }

    if(isset($_REQUEST["loadPlaylist"]) AND is_file($config["plDir"].$_REQUEST["loadPlaylist"])) {
        doPrint("loading playlist: ".$_REQUEST["loadPlaylist"]);

        if(!preg_match("/\w+ - .*\.playlist/", $_REQUEST["loadPlaylist"])) {
          doPrint("invalid playlist: ".$_REQUEST["loadPlaylist"]);
          exit;
        }

        $files = file($config["plDir"].$_REQUEST["loadPlaylist"]);
        $playlist = array();
        foreach($files as $file) {
            $file = trim($file);
            if(substr($file, 0, 8) == "STREAM::") {
                $playlist = playlistAdd($playlist, substr($file, 8));
            } else {
                $playlist = playlistAdd($playlist, $config["searchPath"].$file);
            }
        }
        $data["playlist"] = $playlist;
        storeData($data);
    }

    $playlist = array();
    foreach($data['playlist'] as $key => $entry) {
        if(empty($key) or !isset($entry["filename"])) {
          continue;
        }
        $playlist[] = array(
            "tracknum"  => $entry['tracknum'],
            "artist"    => utf8_encode($entry['artist']),
            "album"     => utf8_encode($entry['album']),
            "title"     => utf8_encode($entry['title']),
            "length"    => $entry['length'],
            "token"     => $entry['token'],
            "file"      => utf8_encode($entry['filename']),
        );
    }

    if(count($playlist) > 0) {
        $data = json_encode($playlist);
        echo '({"total":"'.count($playlist).'","results":'.$data.'})';
    } else {
        echo '({"total":"0", "results":""})';
    }
}

#################################################################

function action_setToggle()
{
    global $config;

    if(!isset($_REQUEST['param'])) {
        print "missing parameter: param!";
        return(1);
    }
    if(!isset($_REQUEST['button'])) {
        print "missing parameter: button!";
        return(1);
    }
    doPrint("got json toggle request ('".$_REQUEST['button']."', '".$_REQUEST['param']."')");
    # doPrint($_REQUEST);

    $param = 1;
    if($_REQUEST['param'] == "false") {
        $param = 0;
    }

    $data = getData();

    # Repeat
    if($_REQUEST['button'] == "Repeat") {
        $data["repeat"] = $param;
        print "Set Repeat to: ".$param;
        storeData($data);
    }

    # Play
    if($_REQUEST['button'] == "Play") {
        $data["play"]  = 1;
        $data["pause"] = 0;
        if(isset($_REQUEST["token"])) {
            $data = brokerPlugin("user_pressed_next", $data);
            $data["curTrack"] = $_REQUEST["token"];
            $data = killChild($data);
        }
        system($config["cliPHPbinary"].' play.php >> '.$config["logfile"].' 2>&1 &');
        # wait until play.php started up
        for($x = 0; $x <= 30; $x++) {
          usleep(50000);
          $data = getData();
          #doPrint("check: ".$x);
          if(isset($data['aktBin'])) {
            $x = 100;
          }
        }
        action_getPlaylist();
    }

    # Stop
    if($_REQUEST['button'] == "Stop") {
        doPrint("pressed stop");
        $data = brokerPlugin("user_pressed_stop", $data);
        killChild();
        action_getPlaylist();
    }

    # Pause
    if($_REQUEST['button'] == "Pause") {
        doPrint("pressed pause");
        $signal = 17;
        $data["pause"] = 1;
        if($param == "false") {
            $data["pause"] = 0;
            $signal = 19;
        }
        # get child pids
        if(isset($data["ppid"])) {
            $pids = getChildPids($data["ppid"]);
            foreach($pids as $pid) {
                posix_kill($pid, $signal);
            }
            if($data["pause"]) {
                $data["pauseStart"] = time();
            } else {
                $data["start"] = $data["start"] + (time() - $data["pauseStart"]);
                unset($data["pauseStart"]);
            }
        }
        storeData($data);
    }

    # Mute
    if($_REQUEST['button'] == "Mute") {
        $data["mute"]  = $param;
        $data["quiet"] = 0;
        doPrint("pressed mute");
        $data["origVolume"] = getVolume();
        $_REQUEST["vol"] = 0;
        action_setVolume();
        print "mute set to true";
        storeData($data);
    }
    if($_REQUEST['button'] == "Unmute") {
        $data["quiet"] = 0;
        $data["mute"] = $param;
        doPrint("pressed unmute");
        $_REQUEST["vol"] = $data['origVolume'];
        unset($data["origVolume"]);
        action_setVolume();
        print "mute set to false";
        storeData($data);
    }

    # Quiet
    if($_REQUEST['button'] == "Quiet") {
        $data["mute"] = 0;
        $data["quiet"] = $param;
        doPrint("pressed quiet");
        if($param) {
            $data["origVolume"] = getVolume();
            $_REQUEST["vol"] = $config["quietVol"];
            action_setVolume();
        } else {
            $_REQUEST["vol"] = $data["origVolume"];
            unset($data["origVolume"]);
            action_setVolume();
        }
        print "quiet set to ".$param;
        storeData($data);
    }

    if($_REQUEST['button'] == "partymode") {
        $data["partymode"] = $_REQUEST['param'];
        print "partymode set to ".$_REQUEST['param'];
        storeData($data);
    }
}

#################################################################

function action_getPath()
{
    global $config;

    if(!isset($_REQUEST['aktPath'])) {
        $aktPath = "";
    } else {
        $aktPath = $_REQUEST['aktPath'];
    }
    $orig = $aktPath;
    if(!isset($_REQUEST['append'])) {
        $append = "";
    } else {
        $append = $_REQUEST['append'];
    }

    $aktPath = getPath($aktPath, $append);

    doPrint("got json getPath request: getPath('".$orig."', '".$append."') => ".$aktPath);

    print $aktPath;
}

#################################################################

function action_getCurStatus($msg = "")
{
    global $config;

    doPrint("got json status request");
    $data = getData();
    $data = fillInDefaults($data);

    $text = "idle";
    if(isset($data['ppid'])) {
        $file = $data['filename'];
        $file = str_replace($config["searchPath"], "", $file);
        if($data['playingStream'] == 0 AND strpos($file, "/") !== 0) { $file = "/".$file; }
        $bitrate = "";
        if(isset($data["bitrate"]) AND !empty($data["bitrate"])) {
          $bitrate = " (".$data["bitrate"]."kb)";
        }
        if($data['pause']) {
            $text = "paused (pid: ".$data['ppid']."): ".$file.$bitrate;
        } else {
            $text = "playing (pid: ".$data['ppid']."): ".$file.$bitrate;
        }
    } else {
        $data['playingStream'] = 0;
    }

    list($remMin, $remSec, $remaining, $stream, $started) = getRemaining($data);
    $pre = "-";
    if($stream == 1 or (empty($remSec) and empty($remMin))) {
      $pre = " ";
    }

    if(!empty($msg)) {
      $text = $msg;
    }

    $status[] = array(
            'artist'    => utf8_encode($data['artist']),
            'album'     => utf8_encode($data['album']),
            'nr'        => $data['track'],
            'title'     => utf8_encode($data['title']),
            'length'    => $data['length'],
            'token'     => $data['token'],
            'volume'    => getVolume(),
            'status'    => utf8_encode($text),
            'remMin'    => $remMin,
            'remSec'    => $remSec,
            'pre'       => $pre,
            'play'      => $data['play'],
            'pause'     => $data['pause'],
            'repeat'    => $data['repeat'],
            'mute'      => $data['mute'],
            'quiet'     => $data['quiet'],
            'totalTime' => $data['totalTime'],
            "stream"    => $data['playingStream'],
            "version"   => "WebMP3 ".WEBMP3_VERSION,
            "partymode" => $data['partymode'],
            'thumbnail' => utf8_encode($data['thumbnailurl']),
    );

    if(isset($_REQUEST['debug'])) {
        print "<pre>time: ".time()."\n\nstatus:";
        print_r($status);
        print "\ndata:\n";
        print_r($data);
    }

    if(isset($config['lastError']) AND !empty($config['lastError'])) {
        header("HTTP/1.0 508 Application Error");
        print($config['lastError']);
        doPrint("Error: ".$config['lastError']);
        exit(1);
    }

    $jsonstatus = json_encode($status);
    echo '({"total":"'.count($status).'","results":'.$jsonstatus.'})';
}

#################################################################

function action_getHitlist()
{
    global $config;
    #doPrint($_REQUEST);

    $start = 0;
    $limit = 20;
    if(isset($_REQUEST['start']) AND is_numeric($_REQUEST['start'])) { $start = $_REQUEST['start']; }
    if(isset($_REQUEST['limit']) AND is_numeric($_REQUEST['limit'])) { $limit = $_REQUEST['limit']; }
    doPrint("got json hitlist request (".$start."/".$limit.")");


    $songs = file($config["hitlist"]) or die("cannot open hitlist file");
    $hitlist = array();
    foreach($songs as $song) {
        list($num, $track) = explode(",", $song);
        $hitlist[$num][] = $track;
    }
    krsort($hitlist);

    $newHitlist = array();
    $x = 1;
    foreach($hitlist as $num => $tracks) {
        foreach($tracks as $track) {
            $newHitlist[] = array(
                "nr"    => $x,
                "file"  => $track,
                "count" => $num,
            );
            $x++;
        }
    }
    $count = count($newHitlist);
    $newHitlist = array_slice($newHitlist, $start, $limit);

    if(count($newHitlist) > 0) {
        $data = json_encode($newHitlist);
        echo '({"total":"'.$count.'","results":'.$data.'})';
    } else {
        echo '({"total":"0", "results":""})';
    }
}

#################################################################

?>
