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
include("include/Action.php");

#################################################################
#
# action_default()
# action_setVolume()
# action_pic()
# action_savePl()
# action_loadPl()
# action_doLoad()
# action_doDelete()
# action_clearHitlist()
# action_updateTagCache()
#
# action_getFilesystem()
# action_getPlaylist()
# action_setToggle()
# action_getPath()
# action_addPlaylist()
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

    $t = new template();
    $t -> main("include/templates/webmp3.tpl");
    $t -> code(array(
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
    exec($config["aumixBin"]." -v ".escapeshellarg($_REQUEST["vol"]));

    doPrint("setting volume to ".$_REQUEST["vol"]);
}

#################################################################

function action_pic() {
    global $config;

    $data = getData();

    $dst_w = $config["picWidth"];
    $dst_h = $config["picHeight"];

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
    doPrint("got pic request for: ".$url);

    # search a folder icon
    $url = getPictureForPath($url);

    if(file_exists($url)) {
        if(isset($_GET["full"]) AND $_GET["full"] == "yes") {
            $ext = substr($url, -4);
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

function action_savePl()
{
    $t = new template();
    $t -> main("savePl.tpl");
    $t -> code(array(
    ));
    $temp = $t -> return_template();
    print $temp;
}

#################################################################

function action_doSave()
{
    global $config;

    $data = getData();

    $file = "";
    foreach($data["playlist"] as $entry) {
        if(isset($entry["stream"]) AND $entry["stream"] == 1) {
            $file .= "STREAM::".str_replace($config["searchPath"], "", $entry["filename"])."\n";
        } else {
            $file .= str_replace($config["searchPath"], "", $entry["filename"])."\n";
        }
    }

    $_POST["name"] .= " ".$data["totalTime"]."min ".count($data["playlist"])." files";

    $fp = fopen($config["plDir"].$_POST["name"], "a+") or user_error("cannot open file");
    fputs($fp, $file);
    fclose($fp);

    doPrint("save playlist: ".$_POST["name"]);

    print "<center>saved<br><a href='#' onClick='window.close()'>close</a>";
}

#################################################################

function action_loadPl()
{
    global $config;
    $list = array();

    if ($handle = opendir($config["plDir"])) {
        while (false !== ($file = readdir($handle))) {
            if (is_file($config["plDir"].$file) AND $file != "." AND $file != "..") {
                $list[] = array(
                            "name"  => $file,
                            "ctime" => date("d:m:Y H:i", filectime($config["plDir"].$file)),
                );
            }
        }
        closedir($handle);
    }

    $t = new template();
    $t -> main("loadPl.tpl");
    $t -> code(array(
        "list"  => $list,
    ));
    $temp = $t -> return_template();
    print $temp;
}

#################################################################

function action_doLoad()
{
    global $config;

    if(!is_file($config["plDir"].$_GET["file"])) { user_error("file does not exist"); }
    $files = file($config["plDir"].$_GET["file"]);

    $playlist = array();
    foreach($files as $file) {
        $file = trim($file);
        if(substr($file, 0, 8) == "STREAM::") {
            $playlist = playlistAdd($playlist, substr($file, 8));
        } else {
            $playlist = playlistAdd($playlist, $config["searchPath"].$file);
        }
    }

    $data = getData();
    $data["playlist"] = $playlist;
    storeData($data);

    print "<html><head></head><body onLoad='window.opener.location.reload()'><center>playlist loaded<br><a href='#' onClick='window.close()'>close</a></body></html>";
}

#################################################################

function action_doDelete()
{
    global $config;

    $file = $config["plDir"].$_GET["file"];

    unlink($file);

    print "<html><head></head><body><center>file deleted<br><a href='#' onClick='window.close()'>close</a></body></html>";
}

#################################################################

function action_clearHitlist()
{
    $data = getData();
    $data["mostPlayed"] = array();
    storeData($data);

    redirect("webmp3.php?action=hitlist&reload=1");
}

#################################################################

function action_updateTagCache()
{
    global $config;
    doPrint("starting tag cache update...");
    print formatDateTime()."\n";

    $tagCache = array();

    #$files = getFilesForDirectory($config["searchPath"]."/stonerrock/dozer");
    $files = getFilesForDirectory($config["searchPath"]);

    $fp = fopen($config["tagCache"], "w+");
    foreach($files as $file) {
        $fileinfo = getTag($config["searchPath"]."/".$file);
        #$tagCache[$file] = $fileinfo;
        fwrite($fp, $file.";-;".join(";-;", $fileinfo)."\n");
    }

    fclose($fp);
    print "wrote tag cache\n";
    print formatDateTime()."\n";
    doPrint("finished tag cache update...");
}

#################################################################

function action_search()
{
    $_POST["aktPath"] = "";
    $_GET["aktPath"] = "";
    action_default();
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

    $aktPath = getPath($aktPath, $append);
    doPrint("got json filesystem get request for: ".$aktPath);

    if(!file_exists($config["searchPath"].$aktPath)) {
        doPrint("file does not exist: ".$config["searchPath"].$aktPath);
        return(0);
    }

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
                        $ext = substr($file, -4);
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
        $count = count($filesystem);
        $filesystem = array_slice($filesystem, $start, $limit);
    }
    natcasesort($dirs);
    natcasesort($files);

    if($aktPath != "" AND $aktPath != "/") {
        array_unshift($filesystem, array("file" => "..",  "type" => "D", "icon" => "images/spacer.png"));
    }

    foreach($dirs as $dir) {
        $filesystem[] = array("file" => $dir,  "type" => "D", "icon" => "images/folder.png");
    }
    foreach($files as $file) {
        $filesystem[] = array("file" => $file, "type" => "F", "icon" => "images/music.png");
    }

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
            if(file_exists($config["searchPath"].$aktPath."/".$file)) {
                doPrint("added file ".$aktPath."/".$file);
                $data["playlist"] = playlistAdd($data["playlist"], $config["searchPath"].$aktPath."/".$file);
            }
            elseif(strpos($file, "http://") === 0) {
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

    $playlist = array();
    foreach($data['playlist'] as $key => $entry) {
        if(empty($key) or !isset($entry["filename"])) {
          continue;
        }
        $playlist[] = array(
            "tracknum"  => $entry['tracknum'],
            "artist"    => $entry['artist'],
            "album"     => $entry['album'],
            "title"     => $entry['title'],
            "length"    => $entry['length'],
            "token"     => $entry['token'],
            "file"      => $entry['filename'],
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
            $data["curTrack"] = $_REQUEST["token"];
            $data = killChild($data);
        }
        system($config["cliPHPbinary"].' play.php >> '.$config["logfile"].' 2>&1 &');
        # wait until play.php started up
        for($x = 0; $x <= 30; $x++) {
          usleep(50000);
          $data = getData();
          doPrint("check: ".$x);
          if(isset($data['aktBin'])) {
            $x = 100;
          }
        }
        action_getPlaylist();
    }

    # Stop
    if($_REQUEST['button'] == "Stop") {
        doPrint("pressed stop");
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

function action_getCurStatus()
{
    global $config;

    doPrint("got json status request");
    $data = getData();
    $data = fillInDefaults($data);

    # get client version
    $client = join("", file("include/templates/webmp3.tpl"));
    $pos1   = strpos($client, '$Id:', $client);
    $pos2   = strpos($client, '$', $pos1 + 1);
    $pos2   = $pos2 + 1;
    $version = str_replace("webmp3.tpl", "WebMP3", substr($client, $pos1, $pos2 - $pos1));

    $text = "idle";
    if(isset($data['ppid'])) {
        $file = $data['filename'];
        $file = str_replace($config["searchPath"], "", $file);
        if($data['playingStream'] == 0 AND strpos($file, "/") !== 0) { $file = "/".$file; }
        if($data['pause']) {
            $text = "paused (pid: ".$data['ppid']."): ".$file;
        } else {
            $text = "playing (pid: ".$data['ppid']."): ".$file;
        }
    } else {
        $data['playingStream'] = 0;
    }

    list($remMin, $remSec, $remaining, $stream, $started) = getRemaining($data);
    $pre = "-";
    if($stream == 1) {
      $pre = "";
    }

    $status[] = array(
            'artist'  => $data['artist'],
            'album'   => $data['album'],
            'nr'      => $data['track'],
            'title'   => $data['title'],
            'length'  => $data['length'],
            'token'   => $data['token'],
            'volume'  => getVolume(),
            'status'  => $text,
            'remMin'  => $remMin,
            'remSec'  => $remSec,
            'pre'     => $pre,
            'play'    => $data['play'],
            'pause'   => $data['pause'],
            'repeat'  => $data['repeat'],
            'mute'    => $data['mute'],
            'quiet'   => $data['quiet'],
            "stream"  => $data['playingStream'],
            "version" => $version,
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
