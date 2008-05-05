<?php

error_reporting(2047);

#################################################################
#
# getVolume()
# getData()
# storeData()
# redirect()
# playlistAdd()
# doPrint()
# insertSortedInMultiArray()
# sortMultiArray()
# crossUrlDecode()
# getPictureForPath()
# killChild()
# getNextTrack()
# recalcTotalPlaytime()
# addFileToHitlist()
# getCaller()
# getTag()
# formatDateTime()
# getPath()
# myRealpath($path)
# getChildPids($pid)
# fillInDefaults($data)
# getRemaining($data)
# getPidData($pid)
#
#################################################################

function getVolume()
{
    global $config;
    $erg = exec($config["aumixBin"]." -vq");
    if(empty($erg))
    {
        #$erg = " couldn&quot;t get volume, perhabs ".getmyuid()." is not a member of group audio";
        $erg = "50";
    } else {
        list($blah, $vol) = explode(",", $erg);
        $erg = trim($vol);
    }
    return($erg);
}

#################################################################

function getData($called = 0, $errMsg = "") {
    global $config;
    global $data;

    $called++;
    if($called > 1) { sleep(1); doPrint($called." try to get data"); }
    if($called == 10) { die($errMsg); }

    if(file_exists($config["playlist"])) {
        $tmp  = file($config["playlist"]);
        if(!isset($tmp[0])) { return(getData($called, "error in getData(), playlist corrupt?")); }
        $data = unserialize($tmp[0]);
    } else {
        $data = fillInDefaults(array());
    }

    # check playlist for empty entries
    $playlist = array();
    foreach($data['playlist'] as $key => $track) {
      if(!is_array($track)) {
      } elseif(!isset($track['filename'])) {
      } else {
        $playlist[$key] = $track;
      }
    }
    $data['playlist'] = $playlist;

    return($data);
}

#################################################################

function storeData($data) {
    global $config;

    getCaller();

    if(empty($data) OR !is_array($data) OR count($data) == 0) { die("cannot save nothing"); }

    # remove old crap data
    if(isset($data["mostPlayed"])) { unset($data["mostPlayed"]); }
    if(isset($data["pid"]))        { unset($data["pid"]); }

    $ser = serialize($data) or die("cannot serialze data");
    if(!empty($ser)) {
        $fp = fopen($config["playlist"], "w+") or user_error("cannot open playlist");
        fwrite($fp, serialize($data)."\n");
        fclose($fp);
        doPrint("wrote data");
    }
}

#################################################################

function redirect($url)
{
    header ("Location: $url");
}

#################################################################

function playlistAdd($playlist, $toAdd)
{
    global $config;

    if(strpos($toAdd, "http://") === 0) {
        $token = md5(uniqid(rand(), true));
        $newFile = array(
            "display"   => $toAdd,
            "filename"  => $toAdd,
            "token"     => $token,
            "album"     => "",
            "title"     => $toAdd,
            "artist"    => "",
            "tracknum"  => "",
            "lengths"   => "1",
            "stream"    => "1",
            "length"    => "&infin;",
        );
        $playlist[$token] = $newFile;
    } elseif(is_file($toAdd)) {
        $toAdd = preg_replace("/\/+/", "/", $toAdd);

        list($artist,$album,$title,$tracknum,$playtime_string) = getTag($toAdd);

        $display = $artist." - ".$album." - ".$tracknum." - ".$title;
        if(empty($title)) {
            $title   = basename($toAdd);
            $display = $title;
        }

        $playtime_seconds = 0;
        list($min,$sec) = explode(":", $playtime_string);
        $playtime_seconds = $min * 60 + $sec;

        $token = md5(uniqid(rand(), true));
        $newFile = array(
            "display"   => utf8_encode($display),
            "filename"  => utf8_encode($toAdd),
            "token"     => $token,
            "album"     => utf8_encode($album),
            "title"     => utf8_encode($title),
            "artist"    => utf8_encode($artist),
            "tracknum"  => $tracknum,
            "lengths"   => floor($playtime_seconds),
            "length"    => $playtime_string,
        );

        $playlist[$token] = $newFile;
    } elseif(is_dir($toAdd)) {
        $toAdd = preg_replace("/\/+/", "/", $toAdd);
        $files = array();
        $dirs  = array();

        if($handle = opendir($toAdd)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if(is_dir($toAdd."/".$file)) {
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
            $playlist = playlistAdd($playlist, $toAdd."/".$dir);
        }
        foreach($files as $file) {
            $playlist = playlistAdd($playlist, $toAdd."/".$file);
        }
    } else {
        doPrint("playlistAdd() : ".$toAdd." is whether file nor dir nor stream");
    }
    return($playlist);
}

#################################################################

function doPrint($data)
{
    global $config;

    $script = basename($_SERVER["PHP_SELF"]);
    $pid    = getmypid();
    $date   = date("H:i:s");
    $ip     = "";
    if(isset($_SERVER["REMOTE_ADDR"])) {
         $ip = $_SERVER["REMOTE_ADDR"];
    }
    $info   = $date." ".$script."[".$pid."][".$ip."]: ";

    if(is_string($data)) {
        $text = $info.$data;
    }
    else
    {
        ob_start();
        print_r($data);
        $buffer = ob_get_contents();
        ob_end_clean();
        $text = $info.$buffer;
    }

    $fp = fopen($config["logfile"], "a");
    fwrite($fp, $text."\n");
    fclose($fp);
}

#################################################################

function insertSortedInMultiArray($mAr, $ar, $col)
{
    if (is_array($mAr) == false)
    die ("Error : function insertSortedInMultiArray, Parameter 0 is not an array");

    if (is_array($ar) == false)
    die ("Error : function insertSortedInMultiArray, Parameter 1 is not an array");

    $retArray = array();

    $mArCount = count($mAr);

    // Multiarray noch leer?
    if ($mArCount == 0)
    {
        $retArray[0] = $ar;
    }
    else
    {
        // leider nicht leer
        $num = $ar[$col];
        $offset = 0;

        for ($i = 0; $i < $mArCount; $i++)
        {
            $tmpAr = $mAr[$i];

            $tmpNum = $tmpAr[$col];

            if ( $offset == 0 )
            {
                if ($tmpNum <= $num)
                {
                    $retArray[$i] = $tmpAr;
                }
                else
                {
                    $retArray[$i] = $ar;
                    $offset = 1;
                    $retArray[$i+$offset] = $tmpAr;
                }
            }
            else
            {
                $retArray[$i+$offset] = $tmpAr;
            }
        }

        if ($offset == 0)
        {
            $retArray[$i] = $ar;
        }

    }
    return $retArray;
}

#########################################################################################

function sortMultiArray($mAr, $col)
{
    $retArray = array();

    foreach($mAr as $key => $ar)
    {
        $retArray = insertSortedInMultiArray($retArray, $ar, $col);
    }

    return $retArray;
}

#########################################################################################

function crossUrlDecode($source) {
   $decodedStr = '';
   $pos = 0;
   $len = strlen($source);

   while ($pos < $len) {
       $charAt = substr ($source, $pos, 1);
       if ($charAt == 'Ã') {
           $char2 = substr($source, $pos, 2);
           $decodedStr .= htmlentities(utf8_decode($char2),ENT_QUOTES,'ISO-8859-1');
           $pos += 2;
       }
       elseif(ord($charAt) > 127) {
           $decodedStr .= "&#".ord($charAt).";";
           $pos++;
       }
       elseif($charAt == '%') {
           $pos++;
           $hex2 = substr($source, $pos, 2);
           $dechex = chr(hexdec($hex2));
           if($dechex == 'Ã') {
               $pos += 2;
               if(substr($source, $pos, 1) == '%') {
                   $pos++;
                   $char2a = chr(hexdec(substr($source, $pos, 2)));
                   $decodedStr .= htmlentities(utf8_decode($dechex . $char2a),ENT_QUOTES,'ISO-8859-1');
               }
               else {
                   $decodedStr .= htmlentities(utf8_decode($dechex));
               }
           }
           else {
               $decodedStr .= $dechex;
           }
           $pos += 2;
       }
       else {
           $decodedStr .= $charAt;
           $pos++;
       }
   }

   return $decodedStr;
}

#########################################################################################

function getNextTrack($playlist, $token, $repeat = 0)
{
    if(!is_array($playlist) OR count($playlist) == 0) {
        return("");
    }
    $found = 0;
    foreach($playlist as $key => $blah) {
        if($found == 1) {
            return($key);
        }
        if($token == $key) {
            $found = 1;
        }
    }

    $data = getData();
    if($repeat OR $data["repeat"] != "false") {
        $track = array_shift($playlist);
        return($track["token"]);
    } else {
        return("");
    }
}

#########################################################################################

function getPrevTrack($playlist, $token)
{
    if(!is_array($playlist) OR count($playlist) == 0) {
        return("");
    }
    foreach($playlist as $key => $blah) {
        if($token == $key) {
            if(!isset($prev)) {
                $track = array_pop($playlist);
                return($track["token"]);
            }
            return($prev);
        }
        $prev = $key;
    }
    if(!isset($prev)) {
        $track = array_pop($playlist);
        return($track["token"]);
    }
}

#########################################################################################

function getPictureForPath($path)
{
    if(file_exists($path."/folder.gif")) {
        $return = $path."/folder.gif";
    }
    elseif(file_exists($path."/folder.jpg")) {
        $return = $path."/folder.jpg";
    }
    elseif(file_exists($path."/folder.png")) {
        $return = $path."/folder.png";
    }
    elseif(is_dir($path) AND $handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            $ext = substr($file, -4);
            if($ext == ".png" OR $ext == ".jpg" OR $ext == ".bmp" OR $ext == ".gif") {
                $return = $path."/".$file;
            }
        }
    }

    if(empty($return)) {
        $return = "images/white.png";
    }

    $return = preg_replace("/\/+/", "/", $return);
    return($return);
}

#########################################################################################

function killChild($data = "") {

    if(empty($data)) {
      $data = getData();
    }

    if(isset($data["ppid"])) {
        $oridPid = $data["ppid"];
        $pids = getChildPids($data["ppid"]);
        posix_kill($data["ppid"], 2);
        foreach($pids as $pid) {
             posix_kill($pid, 2);
        }
        posix_kill($data["ppid"], 19);
        foreach($pids as $pid) {
             posix_kill($pid, 19);
        }
        posix_kill($data["ppid"], 9);
        foreach($pids as $pid) {
             doPrint("killed -9: ".getPidData($pid));
             posix_kill($pid, 9);
        }
    }

    $stopFailed = 0;
    if(isset($origPid) and is_numeric($origPid)) {
        $out = getPidData($origPid);
        if(!empty($out)) {
            $stopFailed = 1;
            doPrint(getPidData($out));
        }
        $pids = getChildPids($origPid);
        foreach($pids as $pid) {
            $out = getPidData($origPid);
            if(!empty($out)) {
                $stopFailed = 1;
                doPrint(getPidData($out));
            }
        }
    }
    if($stopFailed == 1) {
        doPrint("stop failed!");
    }

    unset($data["ppid"]);
    unset($data["start"]);
    unset($data["length"]);
    unset($data["title"]);
    unset($data["track"]);
    unset($data["artist"]);
    unset($data["album"]);
    unset($data["aktBin"]);
    unset($data["playingPic"]);
    $data["play"]     = 0;
    $data["pause"]    = 0;
    storeData($data);

    if(file_exists("./var/var/cache.jpg")) {
        unlink("./var/cache.jpg");
    }

    return($data);
}

#########################################################################################

function recalcTotalPlaytime($data)
{
    $totalSeconds = 0;
    $totalTime    = "";
    foreach($data["playlist"] as $track) {
        $totalSeconds += $track["lengths"];
    }
    $totalHours   = floor($totalSeconds/3600);
    $totalSeconds = $totalSeconds%3600;
    $totalMinutes = floor($totalSeconds/60);
    $totalSeconds = $totalSeconds%60;

    if($totalHours > 0)   { $totalTime .= $totalHours.":"; }
    if($totalMinutes > 0) { $totalTime .= sprintf("%02s", $totalMinutes).":"; }
    $totalTime .= sprintf("%02s", $totalSeconds);

    $data["totalTime"] = $totalTime;

    return($data);
}

#########################################################################################

function addFileToHitlist($file)
{
    global $config;

    # read data
    if(!file_exists($config["hitlist"])) {
      $tmp = array();
    } else {
      $tmp  = file($config["hitlist"]) or die("cannot open hitlist");
    }
    $mostPlayed = array();
    foreach($tmp as $row) {
        $row = trim($row);
        if(!empty($row)) {
            $blah = explode(",", $row, 2);
            if(isset($blah[0]) AND is_numeric($blah[0]) AND isset($blah[1])) {
                $mostPlayed[$blah[1]] = $blah[0];
            }
        }
    }

    # increment count or add new song
    if(isset($mostPlayed[$file])) {
        $mostPlayed[$file] = $mostPlayed[$file] + 1;
    } else {
        $mostPlayed[$file] = 1;
    }

    # save hitlist
    $fp = fopen($config["hitlist"], "w+") or user_error("cannot open hitlist");
    foreach($mostPlayed as $track => $nr) {
        fwrite($fp, $nr.",".$track."\n");
    }
    fclose($fp);
}

#########################################################################################

function getCaller()
{
    $backtrace = debug_backtrace();
}

#########################################################################################

function getFilesForDirectory($dir) {
    global $config;

    $files = array();
    if($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if(is_link($dir."/".$file)) {
                    # skip links
                }
                elseif(is_dir($dir."/".$file)) {
                    $files = array_merge($files, getFilesForDirectory($dir."/".$file));
                } else {
                    $ext = substr($file, -4);
                    if(in_array($ext, array_keys($config["ext"]))) {
                        $files[] = str_replace($config["searchPath"], "", $dir."/".$file);
                    }
                }
            }
        }
        closedir($handle);
    }
    return($files);
}

#########################################################################################

function getTag($file) {
    global $getID3;
    if(!isset($getID3)) {
      $getID3 = new getID3;
      $getID3->encoding = 'UTF-8';
    }
    $fileinfo = $getID3->analyze($file);
    //getid3_lib::CopyTagsToComments($fileinformation);
    doPrint($fileinfo);

    $neededTags = array("artist", "album", "title", "track");
    foreach($neededTags as $tag) {
      if(isset($fileinfo["comments"][$tag][0]) AND !empty($fileinfo["comments"][$tag][0])) {
        $$tag = $fileinfo["comments"][$tag][0];
      } else {
        $$tag = "";
      }
    }

    if(!isset($fileinfo["playtime_string"]))  { $fileinfo["playtime_string"] = ""; }

    # track should be at least 2 chars width
    if(strlen($track) == 1) {
        $track = "0".$track;
    }

    return(array($artist,$album,$title,$track,$fileinfo["playtime_string"]));
}

#########################################################################################

function formatDateTime($time = 0)
{
    if($time == 0) { $time = time(); }
    return(date("m.d.Y H:i:s", $time));
}

#########################################################################################
# returns relative path from search directory
function getPath($path = "", $append = "") {
    global $config;

    # doPrint("getPath('".$path."', '".$append."')");
    $path   = strip_tags($path);
    $append = strip_tags($append);

    # strip of trailing /
    $config["searchPath"] = preg_replace("/\/$/", "", $config["searchPath"]);

    #doPrint("getPath('".$path."', '".$append."')");
    if($path == "") { $path = "/"; }

    $origRequest = "/".$path."/".$append;
    #doPrint("0: ".$origRequest);
    $origRequest = preg_replace("/\/+/", "/", $origRequest);
    #doPrint("1: ".$origRequest);

    if(is_file($config["searchPath"]."/".$path."/".$append)) {
        $aktPath = dirname($config["searchPath"]."/".$path);
        $origRequest = "/".$path;
    } else {
        $aktPath = $config["searchPath"]."/".$path."/".$append;
    }

    $aktPath = preg_replace("/\/+/", "/", $aktPath);
    #doPrint("2: ".$aktPath);

    #$aktPath = realpath($aktPath);
    # do the realpath thing...
    $origRequest = myRealpath($origRequest);
    $aktPath     = myRealpath($aktPath);
    #doPrint("3: ".$aktPath);

    if(is_file($aktPath)) {
        $aktPath = dirname($aktPath);
        #doPrint("4: ".$aktPath);
    }
    if(!is_dir($aktPath)) {
        $aktPath = "";
        #doPrint("5: ".$aktPath);
    }
    #doPrint("6: ".$aktPath);

    if(strpos($aktPath, $config["searchPath"]) !== 0) {
        $origRequest = "/";
        $aktPath = $config["searchPath"];
        #doPrint("6.1: wrong path, resetting");
    }

    if(strpos($origRequest, "/") !== 0) {
        $origRequest = "/".$origRequest;
    }
    $origRequest = preg_replace("/\/+/", "/", $origRequest);
    #doPrint("7: ".$origRequest);
    return $origRequest;
}

#########################################################################################

function myRealpath($path) {
    while(strpos($path, "..") !== false) {
        $pathElems = split("/", $path);
        $key = array_search('..', $pathElems);
        array_splice($pathElems, $key -1 , 2);
        $path = join("/", $pathElems);
    }
    return($path);
    $path = str_replace("/.", "", $path);
}

#########################################################################################

function getChildPids($pid)
{
    $pid = trim($pid);
    doPrint("getChildPids(".$pid.")");
    if(empty($pid) or !is_numeric($pid)) {
        return(array());
    }
    $pids = array();
    $return = array();
    exec("ps -o pid,ppid -ax | grep ".$pid, $pids);
    foreach($pids as $pidStr) {
        $pidStr = trim($pidStr);
        list($cpid,$egal) = preg_split("/\s+/", $pidStr, 2);
        $cpid = trim($cpid);
        if($cpid == $pid) { continue; }
        if(empty($cpid))  { continue; }
        $return = array_merge(array($cpid), getChildPids($cpid));
    }
    return($return);
}

#########################################################################################

function fillInDefaults($data) {
    if(!isset($data["mute"]))           { $data["mute"]   = 0; }
    if(!isset($data["repeat"]))         { $data["repeat"] = 0; }
    if(!isset($data["length"]))         { $data["length"] = ""; }
    if(!isset($data["start"]))          { $data["start"]  = ""; }
    if(!isset($data["title"]))          { $data["title"]  = ""; }
    if(!isset($data["volume"]))         { $data["volume"] = getVolume(); }
    if(!isset($data["quiet"]))          { $data["quiet"]  = 0; }
    if(!isset($data["play"]))           { $data["play"]   = 0; }
    if(!isset($data["pause"]))          { $data["pause"]  = 0; }
    if(!isset($data["playingStream"]))  { $data["playingStream"]  = 0; }
    if(!isset($data["filename"]))       { $data["filename"]  = ""; }

    if(!isset($data["artist"])) { $data["artist"] = " "; }
    if(!isset($data["album"]))  { $data["album"]  = " "; }
    if(!isset($data["track"]))  { $data["track"]  = " "; }
    if(!isset($data["title"]))  { $data["title"]  = " "; }

    if(empty($data["artist"]))  { $data["artist"] = " "; }
    if(empty($data["album"]))   { $data["album"]  = " "; }
    if(empty($data["track"]))   { $data["track"]  = " "; }
    if(empty($data["title"]))   { $data["title"]  = " "; }
    if(empty($data["token"]))   { $data["token"]  = " "; }

    if(!isset($data["playlist"]) or !is_array($data["playlist"]))   { $data["playlist"] = array(); }

    return($data);
}

#########################################################################################

function getRemaining($data)
{
    if(isset($data["start"])) {
        $start = $data["start"];
    }
    if(isset($data["pause"]) and isset($start) and isset($data["pauseStart"])) {
        $start = $start + (time() - $data["pauseStart"]);
    }

    $remaining = "remaining";
    $stream    = "false";
    if(isset($data["playingStream"]) AND $data["playingStream"] == 1 AND isset($data["ppid"])) {
        $stream = "true";
    }
    if(isset($data["curTrack"])
       AND !empty($data["cpid"])
       AND isset($data["playlist"][$data["curTrack"]]["stream"])
       AND $data["playlist"][$data["curTrack"]]["stream"] == 1) {

        $remaining = "playing";
    }
    $started = 0;
    if(isset($start) AND !empty($start)) {
        $started = $start;
    }

    $remMin = "";
    $remSec = "";
    if(!empty($data["length"])) {
        $data["length"] = $data["length"] - (time() - $start);
        $remMin = floor($data["length"] / 60);
        $remSec = floor($data["length"] % 60);
    }
    if($stream == "true") {
        $remMin = -$remMin;
        $remSec = -$remSec;
    }
    if(strlen($remSec) == 1) {
        $remSec = "0".$remSec;
    }

    return(array($remMin, $remSec, $remaining, $stream, $started));
}

#########################################################################################

function getPidData($pid) {
  if(!is_numeric($pid)) {
    return("");
  }
  ob_start();
  system("ps -p ".$pid." | grep -v 'PID' | tail -1");
  $return = ob_get_contents();
  ob_end_clean();
  return($return);
}

#########################################################################################

?>
