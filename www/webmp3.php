<?php
#################################################################
# $Id:$
#################################################################

error_reporting(2047);

### INCLUDES ###
include("config.php");
#$config["searchPath"] = realpath($config["searchPath"]);
if($config["accControl"] == 1 AND isset($_SERVER["REMOTE_ADDR"]) AND !in_array($_SERVER["REMOTE_ADDR"], $config["allowedIPs"])) {
    die($_SERVER["REMOTE_ADDR"]." ist nicht zugelassen"); 
}

if(isset($_SERVER["REMOTE_ADDR"])) {
    ob_start("ob_gzhandler");
}

include("common.php");
include("Template.php");
include("getid3/getid3.php");
include("Action.php");

#################################################################
#
# action_default()
# action_setVolume()
# action_mute()
# action_quiet()
# action_next()
# action_prev()
# action_pic()
# action_savePl()
# action_loadPl()
# action_doLoad()
# action_doDelete()
# action_hitlist()
# action_clearHitlist()
# action_addStream()
# action_doAddStream()
# action_updateTagCache()
#
# action_getFilesystem()
# action_getPlaylist()
# action_setToggle()
# action_getPath()
# action_addPlaylist()
#
#################################################################

function action_default()
{
    global $config;
    $data = getData();

    if(!isset($data["mute"]))   { $data["mute"]   = 0; }
    if(!isset($data["repeat"])) { $data["repeat"] = 0; }
    if(!isset($data["length"])) { $data["length"] = ""; }
    if(!isset($data["start"]))  { $data["start"]  = ""; }
    if(!isset($data["title"]))  { $data["title"]  = ""; }
    if(!isset($data["volume"])) { $data["volume"] = getVolume(); }
    if(!isset($data["quiet"]))  { $data["quiet"]  = 0; }
    if(!isset($data["play"]))   { $data["play"]   = 0; }
    if(!isset($data["pause"]))  { $data["pause"]  = 0; }

    if(!isset($data["artist"])) { $data["artist"] = " "; }
    if(!isset($data["album"]))  { $data["album"]  = " "; }
    if(!isset($data["track"]))  { $data["track"]  = " "; }
    if(!isset($data["title"]))  { $data["title"]  = " "; }

    if(empty($data["artist"]))  { $data["artist"] = " "; }
    if(empty($data["album"]))   { $data["album"]  = " "; }
    if(empty($data["track"]))   { $data["track"]  = " "; }
    if(empty($data["title"]))   { $data["title"]  = " "; }

    $playText = "Play";
    if($data["play"]) {
        $playText = "Stop";
    }
    $muteText = "Mute";
    if($data["mute"]) {
        $muteText = "Unmute";
    }

    $t = new template();
    $t -> main("webmp3.tpl");
    $t -> code(array(
        "volume"    => getVolume(),
        "repeat"    => $data["repeat"],
        "quiet"     => $data["quiet"],
        "muteText"  => $muteText,
        "mute"      => $data["mute"],
        "playText"  => $playText,
        "play"      => $data["play"],
        "pause"     => $data["pause"],
        "artist"    => $data["artist"],
        "album"     => $data["album"],
        "track"     => $data["track"],
        "title"     => $data["title"],
        "token"     => $data["token"],
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
    exec($config["aumixBin"]." -v ".escapeshellarg($_REQUEST["vol"]));

    doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." setting volume to ".$_REQUEST["vol"]);
}

#################################################################

function action_mute() {
    global $config;

    doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed mute");

    $data = getData();

    if(!isset($data["mute"]) OR $data["mute"] == 0) {
        exec($config["aumixBin"]." -v 0");
        $data["mute"]  = 1;
        $data["quiet"] = 0;
    } else {
        exec($config["aumixBin"]." -v ".$data["volume"]);
        $data["mute"]  = 0;
        $data["quiet"] = 0;
    }

    storeData($data);

    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("webmp3.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("webmp3.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_quiet() {
    global $config;

    doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed quiet");

    $data = getData();

    if(!isset($data["quiet"]) OR $data["quiet"] == 0) {
        $data["volume"] = getVolume();
        $data["quiet"] = 1;
        $data["mute"]  = 0;
        exec($config["aumixBin"]." -v ".$config["quietVol"]);
    } else {
        $data["quiet"] = 0;
        $data["mute"]  = 0;
        exec($config["aumixBin"]." -v ".$data["volume"]);
    }

    storeData($data);

    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("webmp3.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("webmp3.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_next()
{
    global $config;
    doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed next");

    $data = getData();

    if(!isset($data["curTrack"])) { $data["curTrack"] = ""; }

    $track = getNextTrack($data["playlist"], $data["curTrack"], 1);
    if($track) {
        if(isset($data["ppid"])) {
            posix_kill($data["ppid"], 15);
        }

        $data["curTrack"] = $track;
        storeData($data);
        system($config["cliPHPbinary"].' play.php >> '.$config["logfile"].' 2>&1 &');
    }

    sleep(2);
    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("webmp3.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("webmp3.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_prev()
{
    global $config;
    doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed prev");

    $data = getData();

    $track = getPrevTrack($data["playlist"], $data["curTrack"]);

    if($track) {
        if(isset($data["ppid"])) {
            posix_kill($data["ppid"], 15);
        }

        $data["curTrack"] = $track;
        storeData($data);
        system($config["cliPHPbinary"].' play.php >> '.$config["logfile"].' 2>&1 &');
    }

    sleep(2);
    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("webmp3.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("webmp3.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_pic() {
    global $config;

    $data = getData();

    $dst_w = $config["picWidth"];
    $dst_h = $config["picHeight"];

    if(!isset($_GET["pic"]) OR empty($_GET["pic"])) {
        return(1);
    }

    $url = $config["searchPath"].getPath($_GET["pic"]);
    doPrint("got pic request for: ".$url);

    # search a

    if(file_exists($url)) {
        if(isset($_GET["full"]) AND $_GET["full"] == "yes") {
            header("Content-type: ".mime_content_type($url));
            readfile($url);
            exit();
        }

        if(isset($data["cachedPic"]) AND $url == $data["cachedPic"] AND is_file("cache.jpg")) {
            # is there a cached one?
            header("Content-type: image/jpeg");
            readfile("cache.jpg");
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

        if(isset($data["playingPic"]) AND urldecode($data["playingPic"]) == str_replace($config["searchPath"],"", $url)) {
            imagejpeg($dst, "cache.jpg");
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

    doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." save playlist: ".$_POST["name"]);

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
            $playlist = action_doAddStream(substr($file, 8), $playlist);
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

function action_hitlist()
{
    global $config;

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
            if((!isset($_GET["view"]) OR $_GET["view"] != "all") AND  $x > 20) { break; }
            $newHitlist[] = array(
                "name"  => str_replace($config["searchPath"], "", $track),
                "num"   => $num,
                "x"     => $x,
            );
            $x++;
        }
    }

    $reload = "";
    if(isset($_GET["reload"]) AND $_GET["reload"] == 1) {
        $reload = " onLoad='window.opener.location.reload()'";
    }

    $t = new template();
    $t -> main("hitlist.tpl");
    $t -> code(array(
        "hitlist"  => $newHitlist,
        "reload"   => $reload,
    ));
    $temp = $t -> return_template();
    print $temp;
}

#################################################################

function action_addFile()
{
    global $config;

    if(strpos($_GET["file"], "http://") === 0) {
        $data = getData();
        $display = $_GET["file"];
        $token = md5(uniqid(rand(), true));
        $newFile = array(
            "display"   => crossUrlDecode($display),
            "filename"  => $display,
            "token"     => $token,
            "status"    => "&nbsp;",
            "album"     => "",
            "title"     => "",
            "artist"    => "",
            "tracknum"  => "",
            "lengths"   => "1",
            "stream"    => "1",
            "length"    => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",
        );
        $data["playlist"][$token] = $newFile;
        storeData($data);
    } else {
        if(!is_file($config["searchPath"].$_GET["file"])) { user_error("file does not exist"); }
        $file = $config["searchPath"].$_GET["file"];

        $data = getData();
        $data["playlist"] = playlistAdd($data["playlist"], $file);
        storeData($data);
    }
    redirect("webmp3.php?action=hitlist&reload=1");
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

function action_addStream()
{
    $t = new template();
    $t -> main("addStream.tpl");
    $t -> code(array(
    ));
    $temp = $t -> return_template();
    print $temp;
}

#################################################################

function action_doAddStream($name = "", $playlist = "")
{
    $data = getData();

    if($name != "") {
        $display = $name;
        $data["playlist"] = $playlist;
    } else {
        $display = $_POST["name"];
    }

    $token = md5(uniqid(rand(), true));
    $newFile = array(
        "display"   => crossUrlDecode($display),
        "filename"  => $display,
        "token"     => $token,
        "status"    => "&nbsp;",
        "album"     => "",
        "title"     => "",
        "artist"    => "",
        "tracknum"  => "",
        "lengths"   => "1",
        "stream"    => "1",
        "length"    => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",
    );
    $data["playlist"][$token] = $newFile;

    if($name != "") {
        return($data["playlist"]);
    }

    storeData($data);
    print "<html><head></head><body onLoad='window.opener.location.reload();window.close()'><center>stream added<br><a href='#' onClick='window.close()'>close</a></body></html>";
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
    # Filesystem    
    $files = array();
    $dirs  = array();
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
    natcasesort($dirs);
    natcasesort($files);

    if($aktPath != "") {
        array_unshift($filesystem, array("display" => "..",  "file" => "..",  "type" => "D", "icon" => "images/spacer.png"));
    }

    foreach($dirs as $dir) {
        $filesystem[] = array("display" => crossUrlDecode($dir),  "file" => $dir,  "type" => "D", "icon" => "images/folder.png");
    }
    foreach($files as $file) {
        $filesystem[] = array("display" => crossUrlDecode($file), "file" => $file, "type" => "F", "icon" => "images/music.png");
    }

    if(count($filesystem) > 0) {
        $data = json_encode($filesystem);
        echo '({"total":"'.count($filesystem).'","results":'.$data.'})';
    } else {
        echo '({"total":"0", "results":""})';
    }
}

#################################################################

function action_getPlaylist()
{
    doPrint("got json playlist request");

    global $config;
    $data = getData();

    if(isset($_REQUEST["add"]) AND is_array($_REQUEST["add"])) {
        doPrint($_REQUEST);
        if(!isset($_REQUEST["aktPath"])) { $_REQUEST["aktPath"] = ""; }
        $aktPath = strip_tags($_REQUEST["aktPath"]);
        foreach($_REQUEST["add"] as $file) {
            if(file_exists($config["searchPath"].$aktPath."/".$file)) {
                doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." added file ".$aktPath."/".$file);
                $data["playlist"] = playlistAdd($data["playlist"], $config["searchPath"].$aktPath."/".$file);
            }
        }
        $data = recalcTotalPlaytime($data);
        storeData($data);
    }


    if(isset($_REQUEST["clear"])) {
        doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed clear");
        $data["playlist"]   = array();
        $data["totalTime"]  = "0";
        $data["cachedPic"]  = "";
        $data["playingPic"] = "";
        $data["curTrack"]   = "";
        storeData($data);
    }
    if(isset($_REQUEST["shuffle"])) {
        doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed shuffle");
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
        doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed sort");
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
        $playlist[] = array(
            "tracknum"  => $entry['tracknum'],
            "artist"    => $entry['artist'],
            "album"     => $entry['album'],
            "title"     => $entry['title'],
            "length"    => $entry['length'],
            "token"     => $entry['token'],
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

    doPrint("got json toggle request");
    doPrint($_REQUEST);
    if(!isset($_REQUEST['param'])) {
        print "missing parameter: param!";
        return(1);
    }
    if(!isset($_REQUEST['button'])) {
        print "missing parameter: button!";
        return(1);
    }

    $data = getData();

    # Repeat
    if($_REQUEST['button'] == "Repeat") {
        $data["repeat"] = $_REQUEST['param'];
        print "Set Repeat to: ".$_REQUEST['param'];
    }

    # Play
    if($_REQUEST['button'] == "Play") {
        if(isset($_REQUEST["token"])) {
            $data["curTrack"] = $_REQUEST["token"];
            storeData($data);
        }
        system($config["cliPHPbinary"].' play.php >> '.$config["logfile"].' 2>&1 &');
    }

    # Stop
    if($_REQUEST['button'] == "Stop") {
        doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed stop");
        killChild();
        usleep(500);
        if(isset($data["ppid"])) {
            posix_kill($data["ppid"], 2);
        }
        $data["play"] = 0;    
        unset($data["ppid"]);
        storeData($data);
    }

    # Pause
    if($_REQUEST['button'] == "Pause") {
        doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed pause");
        $signal = 17;
        $data["pause"] = 1;
        if($_REQUEST['param'] == "true") {
            $data["pause"] = 0;
            $signal = 19;
        }
        # get child pids
        if(isset($data["ppid"])) {
            exec("ps -o pid,ppid -ax | grep ".$data["ppid"] , $pids);
            foreach($pids as $pid) {
                foreach(preg_split("/\s+/", $pid) as $pid) {
                    if(empty($pid)) { continue; }
                    if($pid == 1)   { continue; }
                    doPrint("kill -$signal $pid");
                    print $pid."<br>";
                    posix_kill($pid, $signal);
                }
            }
        }
        storeData($data);
    }

    storeData($data);
}

#################################################################

function action_getPath()
{
    doPrint("got json getPath request");
    global $config;

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

    print $aktPath;
}

#################################################################

?>