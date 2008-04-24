<?php

error_reporting(2047);

### INCLUDES ###
include("config.php");
$config["searchPath"] = realpath($config["searchPath"]);
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
# action_changeDir()
# action_changePlaylist()
# action_volUp()
# action_volDown()
# action_setVolume()
# action_mute()
# action_quiet()
# action_play()
# action_stop()
# action_next()
# action_prev()
# action_clear()
# action_sort()
# action_repeat()
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

    if(isset($_GET["aktPath"])) {
        $_POST["aktPath"] = $_GET["aktPath"];
    }
    if(!isset($_POST["aktPath"])) {
        $aktPath = "";
    } else {
        $aktPath = $_POST["aktPath"];
    }

    # 2 strips needed
    $aktPath = stripslashes($aktPath);
    $aktPath = stripslashes($aktPath);

#    $aktPath = realpath($config["searchPath"].$aktPath);
#    $aktPath = str_replace($config["searchPath"], "", $aktPath);

    if(is_file($config["searchPath"].$aktPath)) {
        $aktPath = dirname($config["searchPath"].$aktPath);
        $aktPath = str_replace($config["searchPath"], "", $aktPath);
    }
    if(!is_dir($config["searchPath"].$aktPath)) {
        $aktPath = "";
    }

    $filesystem           = array();
    $directory            = array();
    $fileSystemBackground = "";
    $search               = "";
    $PathLinks = "<a href=\"?aktPath=/\">filesystem: /</a>";
    if((isset($_POST["search"]) AND !empty($_POST["search"])) OR (isset($_GET["search"]) AND !empty($_GET["search"]))) {
        if(isset($_POST["search"])) {
            $search = $_POST["search"];
        } else {
            $search = $_GET["search"];
        }

        doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." searched for: ".$search);
        $contents = "";
        $handle = popen("grep -i ".escapeshellarg($search)." ".$config["tagCache"]. "| sort ", "r");
        while (!feof($handle)) {
            $contents .= fread($handle, 8192);
        }
        $files = explode("\n", $contents);
        # print $contents;
        foreach($files as $file) {
            $fileArray = explode(";-;", $file);
            $filesystem[] = array("display" => crossUrlDecode($fileArray[0]), "file" => htmlentities($fileArray[0]));
        }
        $PathLinks .= " - <a href=\"?search=".$search."\">Search Result for: ".$search."</a>";
    } else {
        # Filesystem
        $fileSystemBackground = getPictureForPath($config["searchPath"].$aktPath);
        if(!empty($fileSystemBackground)) { $fileSystemBackground = "<a href=\"index.php?action=pic&pic=".$fileSystemBackground."&full=yes\"><img src='index.php?action=pic&pic=".$fileSystemBackground."' border=0 width='".$config["picWidth"]."' height='".$config["picHeight"]."'></a>"; }
    
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
        foreach($dirs as $dir) {
            $directory[] = array("file" => $dir);
        }
        foreach($files as $file) {
            $filesystem[] = array("display" => crossUrlDecode($file), "file" => htmlentities($file));
        }
    }

    # Path Links
    $tmpPath   = "/";
    $allPaths  = explode("/", $aktPath);
    foreach($allPaths as $path) {
        if(!empty($path)) {
            $tmpPath   .= $path."/";
            $PathLinks .= "<a href=\"?aktPath=".$tmpPath."\">$path/</a>";
        }
    }

    # check if its really playing
    if(isset($data["cpid"]) AND !empty($data["cpid"])) {
        ob_start();
        $test = system("ps -p ".$data["cpid"]." | grep -v PID");
        ob_end_clean();
        if(empty($test)) {
            sleep(2);
            action_next();
        }
    }

    $mute = "mute";
    if($data["mute"] == 1) {
        $mute = "unmute";
        $data["volume"] = 0;
    }
    $quiet = "quiet";
    if(isset($data["quiet"]) AND $data["quiet"] == 1) {
        $quiet = "unquiet";
        $data["volume"] = $config["quietVol"];
    }

    $play = "play";
    $status = "not playing";
    if(!empty($data["cpid"])) {
        $play = "stop";
        $status = "playing, pid(".$data["cpid"].")";
    }

    if(!isset($data["curTrack"])) { $data["curTrack"] = ""; }
    foreach($data["playlist"] as $key => $entry) {
        if($key == $data["curTrack"]) {
            $data["playlist"][$key]["status"] = "*";
        } else {
            $data["playlist"][$key]["status"] = "&nbsp;";
        }
    }

    $remaining = "remaining";
    $stream    = "false";
    if(isset($data["playingStream"]) AND $data["playingStream"] == 1 AND isset($data["cpid"])) {
        $stream = "true";
    }
    if(isset($data["curTrack"])
       AND !empty($data["cpid"])
       AND isset($data["playlist"][$data["curTrack"]]["stream"])
       AND $data["playlist"][$data["curTrack"]]["stream"] == 1) {

        $remaining = "playing";
    }
    $started = 0;
    if(isset($data["start"]) AND !empty($data["start"])) {
        $started = $data["start"];
    }

    $repeat = "repeat is off";
    if($data["repeat"] == 1) {
        $repeat = "repeat is on";
    }

    $remMin = "";
    $remSec = "";
#print "<pre>"; print_r($data); print "</pre>";
    if(!empty($data["length"])) {
        $data["length"] = $data["length"] - (time() - $data["start"]);
        $remMin = floor($data["length"] / 60);
        $remSec = floor($data["length"] % 60);
    }

    $playingPic = "";
    if($play == "stop" AND isset($data["playingPic"]) AND !empty($data["playingPic"])) {
        $playingPic = "<a href=\"index.php?action=pic&pic=".$data["playingPic"]."&full=yes\"><img src=\"index.php?action=pic&pic=".$data["playingPic"]."\" border=0width='".$config["picWidth"]."' height='".$config["picHeight"]."'></a>";
    }

    if(!isset($data["totalTime"])) {
        $data["totalTime"] = "0:00";
    }

    $filename  = "";
    if(isset($data["curTrack"]) AND isset($data["playlist"][$data["curTrack"]])) {

        if(isset($data["playlist"][$data["curTrack"]]["artist"])) {
            $artist = $data["playlist"][$data["curTrack"]]["artist"];
            $wikiLink = "<a href=\"http://en.wikipedia.org/wiki/".str_replace(" ", "_", $artist)."\">".$artist."</a>";
            $data["title"] = str_replace($artist, $wikiLink, $data["title"]);
        }
        $filename  = str_replace($config["searchPath"], "", $data["playlist"][$data["curTrack"]]["filename"]);
    }

    $pageTitle = "WebMP3s";
    if($play == "stop") {
        if(     isset($data["playlist"][$data["curTrack"]]["tracknum"]) 
            AND !empty($data["playlist"][$data["curTrack"]]["tracknum"])
            AND isset($data["playlist"][$data["curTrack"]]["title"])
            AND !empty($data["playlist"][$data["curTrack"]]["title"])
        ) {
            $pageTitle = $data["playlist"][$data["curTrack"]]["tracknum"]." - ".$data["playlist"][$data["curTrack"]]["title"];
        } else {
            $pageTitle = $filename;
        }
    }

    # Debug
    # print "<pre>"; print_r($data); print "</pre>";

    $t = new template();
    $t -> main("index.tpl");
    $t -> code(array(
        "aktPath"       => $aktPath,
        "playlist"      => $data["playlist"],
        "filesystem"    => $filesystem,
        "PathLinks"     => $PathLinks,
        "fileSystemBackground"  => $fileSystemBackground,
        "playingPic"    => $playingPic,
        "directory"     => $directory,
        "volume"        => getVolume(),
        "mute"          => $mute,
        "quiet"         => $quiet,
        "play"          => $play,
        "status"        => $status,
        "repeat"        => $repeat,
        "title"         => $data["title"],
        "remMin"        => $remMin,
        "remSec"        => $remSec,
        "totalTime"     => $data["totalTime"],
        "remaining"     => $remaining,
        "stream"        => $stream,
        "started"       => $started,
        "filename"      => $filename,
        "pageTitle"     => $pageTitle,
        "search"        => $search,
    ));
    $temp = $t -> return_template();
    print $temp;
}

#################################################################

function action_changeDir()
{
    global $config;
    $data = getData();

    #$_POST["to"] = stripslashes(urldecode($_POST["to"]));
    $_POST["to"] = stripslashes($_POST["to"]);

    if(is_file($config["searchPath"].$_POST["aktPath"]."/".$_POST["to"])) {
        doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." added file ".$_POST["aktPath"]."/".$_POST["to"]);
        $data["playlist"] = playlistAdd($data["playlist"], $config["searchPath"].$_POST["aktPath"]."/".$_POST["to"]);
        $data = recalcTotalPlaytime($data);
        storeData($data);
    } else {
        $_POST["aktPath"] = $_POST["aktPath"]."/".$_POST["to"];
    }

    redirect("index.php?aktPath=".urlencode($_POST["aktPath"])."/");
}

#################################################################

function action_changePlaylist()
{
    global $config;
    $data = getData();

    if(!isset($data["playlist"]) OR !is_array($data["playlist"])) { $data["playlist"] = array(); }
    $aktPath = $_POST["aktPath"];

    if(isset($_POST["add"]) AND $_POST["add"] == "<<")
    {
        if(isset($_POST["search"]) AND !empty($_POST["search"])) {
            $aktPath = "";
        }
        foreach($_POST["files"] as $file) {
            $tmp = $config["searchPath"].$aktPath."/".$file;

            doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." added directory ".$tmp);
            $data["playlist"] = playlistAdd($data["playlist"], $tmp);
        }
        $data = recalcTotalPlaytime($data);
        storeData($data);
    }
    elseif(isset($_POST["del"]) AND $_POST["del"] == ">>")
    {
        if(!isset($_POST["playlist"]) OR !is_array($_POST["playlist"])) { $_POST["playlist"] = array(); }
        foreach($_POST["playlist"] as $key) {
            unset($data["playlist"][$key]);
        }
        $data = recalcTotalPlaytime($data);
        storeData($data);
    }
    else
    {
        print "<pre>"; print_r($_POST); print "</pre>";
        print "unknown action";
    }

    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("index.php?aktPath=".$_POST["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_POST["aktPath"]);
    }
}

#################################################################

function action_volUp() {
    global $config;
    exec($config["aumixBin"]." -v ".escapeshellarg("+".$_GET["vol"]));

    $data = getData();
    $data["mute"]  = 0;
    $data["quiet"] = 0;
    $data["volume"] = getVolume();
    storeData($data);

    redirect("index.php?aktPath=".$_GET["aktPath"]);
}

#################################################################

function action_volDown() {
    global $config;
    exec($config["aumixBin"]." -v ".escapeshellarg("-".$_GET["vol"]));

    $data = getData();
    $data["mute"]  = 0;
    $data["quiet"] = 0;
    $data["volume"] = getVolume();
    storeData($data);

    redirect("index.php?aktPath=".$_GET["aktPath"]);
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
    $data = getData();
    $data["mute"]  = 0;
    $data["quiet"] = 0;
    $data["volume"] = getVolume();
    storeData($data);
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
        redirect("index.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_GET["aktPath"]);
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
        redirect("index.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_play()
{
    $data = getData();
    if(isset($_GET["track"])) {
        $data["curTrack"] = $_GET["track"];
        storeData($data);
        sleep(1);
    }

    system('$(which php5) play.php >> /tmp/webmp3play.log 2>&1 &');

    sleep(1);
    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("index.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_stop()
{
    doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed stop");

    killChild();

    usleep(500);

    $data = getData();
    if(isset($data["ppid"])) {
        posix_kill($data["ppid"], 2);
    }
    if(isset($data["cpid"])) {
        posix_kill($data["cpid"], 2);
    }

    unset($data["ppid"]);
    unset($data["cpid"]);
    storeData($data);

    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("index.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_next()
{
    doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed next");

    $data = getData();

    if(!isset($data["curTrack"])) { $data["curTrack"] = ""; }

    $track = getNextTrack($data["playlist"], $data["curTrack"], 1);
    if($track) {
        if(isset($data["ppid"])) {
            posix_kill($data["ppid"], 15);
        }
        if(isset($data["cpid"])) {
            posix_kill($data["cpid"], 15);
        }

        $data["curTrack"] = $track;
        storeData($data);
        system('$(which php5) play.php >> /tmp/webmp3play.log 2>&1 &');
    }

    sleep(2);
    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("index.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_prev()
{
    doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed prev");

    $data = getData();

    $track = getPrevTrack($data["playlist"], $data["curTrack"]);

    if($track) {
        if(isset($data["cpid"])) {
            posix_kill($data["cpid"], 15);
        }

        $data["curTrack"] = $track;
        storeData($data);
        system('$(which php5) play.php >> /tmp/webmp3play.log 2>&1 &');
    }

    sleep(2);
    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("index.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_clear()
{
    doPrint("Client: ".$_SERVER["REMOTE_ADDR"]." pressed clear");

    $data = getData();

    $data["playlist"]   = array();
    $data["totalTime"]  = "0";
    $data["cachedPic"]  = "";
    $data["playingPic"] = "";
    $data["curTrack"]   = "";
    storeData($data);

    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("index.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_sort()
{
    $data = getData();

    $tmp = sortMultiArray($data["playlist"], "filename");
    $newData = array();
    foreach($tmp as $blah)
    {
        $newData[$blah["token"]] = $blah;
    }
    $data["playlist"] = $newData;
    storeData($data);

    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("index.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_shuffle()
{
    $data = getData();

    shuffle($data["playlist"]);
    $newData = array();
    foreach($data["playlist"] as $blah)
    {
        $newData[$blah["token"]] = $blah;
    }
    $data["playlist"] = $newData;
    storeData($data);

    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("index.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_GET["aktPath"]);
    }
}

#################################################################

function action_repeat()
{
    $data = getData();

    if(!isset($data["repeat"])) { $data["repeat"] = 0; }

    if($data["repeat"] == 1) {
        $data["repeat"] = 0;
    } else {
        $data["repeat"] = 1;
    }
    storeData($data);

    if(isset($_REQUEST["search"]) AND !empty($_REQUEST["search"])) {
        redirect("index.php?aktPath=".$_GET["aktPath"]."&search=".$_REQUEST["search"]);
    } else {
        redirect("index.php?aktPath=".$_GET["aktPath"]);
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

    $url = $config["searchPath"].$_GET["pic"];
    #$url = urldecode($url);
    $url = stripslashes($url);
    $url = stripslashes($url);

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
    redirect("index.php?action=hitlist&reload=1");
}

#################################################################

function action_clearHitlist()
{
    $data = getData();
    $data["mostPlayed"] = array();
    storeData($data);

    redirect("index.php?action=hitlist&reload=1");
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

?>
