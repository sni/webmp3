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
#
#################################################################

function getVolume()
{
    global $config;
    $erg = exec($config["aumixBin"]." -vq");
    if(empty($erg))
    {
        $erg = " couldn't get volume, perhabs ".getmyuid()." is not a member of group audio";
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

    $tmp  = file($config["playlist"]);
    if(!isset($tmp[0])) { return(getData($called, "error in getData(), playlist corrupt?")); }
    $data = unserialize($tmp[0]);

    if(!is_array($data)) {
        $data = array("playlist" => array());
    }

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

    $toAdd = stripslashes($toAdd);

    if(is_file($toAdd)) {
        $display = basename($toAdd);

        list($artist,$album,$title,$tracknum,$playtime_string) = getTag($toAdd);

        $display = basename($toAdd);
        if(!empty($title) AND !empty($artist)) {
            $display = $artist." - ".$album." - ".$tracknum." - ".$title;
        }

        $playtime_seconds = 0;
        list($min,$sec) = explode(":", $playtime_string);
        $playtime_seconds = $min * 60 + $sec;

        $display = str_replace("ü", "ue", $display);
        $display = str_replace("Ü", "Ue", $display);
        $display = str_replace("ö", "oe", $display);
        $display = str_replace("Ö", "Oe", $display);
        $display = str_replace("ä", "ae", $display);
        $display = str_replace("Ä", "Ae", $display);
        $display = str_replace("ß", "ss", $display);

        $token = md5(uniqid(rand(), true));
        $newFile = array(
            "display"   => crossUrlDecode($display),
            "filename"  => $toAdd,
            "token"     => $token,
            "status"    => "&nbsp;",
            "album"     => $album,
            "title"     => $title,
            "artist"    => $artist,
            "tracknum"  => $tracknum,
            "lengths"   => floor($playtime_seconds),
            "length"    => str_replace(" ", "&nbsp;", str_pad($playtime_string, 6, " ", STR_PAD_LEFT)),
        );

        $playlist[$token] = $newFile;
    } elseif(is_dir($toAdd)) {
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
        print $toAdd." is weder file noch dir";
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
    $info   = $date." ".$script."[".$pid."]: ";

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
    if($repeat OR (isset($data["repeat"]) AND $data["repeat"] == 1)) {
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

    elseif ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            $ext = substr($file, -4);
            if($ext == ".png" OR $ext == ".jpg" OR $ext == ".bmp" OR $ext == ".gif") {
                $return = $path."/".$file;
            }
        }
    }

    if(!empty($return)) {
        global $config;
        $return = str_replace($config["searchPath"] ,"",$return);
        $return = urlencode($return);
        return($return);
    }

    return("");
}

#########################################################################################

function killChild() {
    $data = getData();

    if(isset($data["ppid"])) {
        posix_kill($data["ppid"], 15);
    }
    if(isset($data["cpid"])) {
        posix_kill($data["cpid"], 15);
    }
    if(isset($data["aktBin"])) {
        system("killall ".basename($data["aktBin"]));
    }

    if(file_exists("cache.jpg")) {
        unlink("cache.jpg");
    }
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
    $tmp  = file($config["hitlist"]) or die("cannot open hitlist");
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
    $getID3 = new getID3;
    $fileinfo = $getID3->analyze($file);
    getid3_lib::CopyTagsToComments($fileinformation);

    $artist = "";
    if(isset($fileinfo["tags"]["id3v2"]["artist"][0]) AND !empty($fileinfo["tags"]["id3v2"]["artist"][0])) {
        $artist = $fileinfo["tags"]["id3v2"]["artist"][0];
    }
    elseif(isset($fileinfo["tags"]["id3v1"]["artist"][0]) AND !empty($fileinfo["tags"]["id3v1"]["artist"][0])) {
        $artist = $fileinfo["tags"]["id3v1"]["artist"][0];
    }
    elseif(isset($fileinfo["tags"]["vorbiscomment"]["artist"][0]) AND !empty($fileinfo["tags"]["vorbiscomment"]["artist"][0])) {
        $artist = $fileinfo["tags"]["vorbiscomment"]["artist"][0];
    }

    $album = "";
    if(isset($fileinfo["tags"]["id3v2"]["album"][0]) AND !empty($fileinfo["tags"]["id3v2"]["album"][0])) {
        $album = $fileinfo["tags"]["id3v2"]["album"][0];
    }
    elseif(isset($fileinfo["tags"]["id3v1"]["album"][0]) AND !empty($fileinfo["tags"]["id3v1"]["album"][0])) {
        $album = $fileinfo["tags"]["id3v1"]["album"][0];
    }
    elseif(isset($fileinfo["tags"]["vorbiscomment"]["album"][0]) AND !empty($fileinfo["tags"]["vorbiscomment"]["album"][0])) {
        $album = $fileinfo["tags"]["vorbiscomment"]["album"][0];
    }
    
    $title = "";
    if(isset($fileinfo["tags"]["id3v2"]["title"][0]) AND !empty($fileinfo["tags"]["id3v2"]["title"][0])) {
        $title = $fileinfo["tags"]["id3v2"]["title"][0];
    }
    elseif(isset($fileinfo["tags"]["id3v1"]["title"][0]) AND !empty($fileinfo["tags"]["id3v1"]["title"][0])) {
        $title = $fileinfo["tags"]["id3v1"]["title"][0];
    }
    elseif(isset($fileinfo["tags"]["vorbiscomment"]["title"][0]) AND !empty($fileinfo["tags"]["vorbiscomment"]["title"][0])) {
        $title = $fileinfo["tags"]["vorbiscomment"]["title"][0];
    }
    
    $tracknum = "";
    if(isset($fileinfo["tags"]["id3v2"]["tracknum"][0]) AND !empty($fileinfo["tags"]["id3v2"]["tracknum"][0])) {
        $tracknum = $fileinfo["tags"]["id3v2"]["tracknum"][0];
    }
    elseif(isset($fileinfo["tags"]["id3v2"]["track"][0]) AND !empty($fileinfo["tags"]["id3v2"]["track"][0])) {
        $tracknum = $fileinfo["tags"]["id3v2"]["track"][0];
    }
    elseif(isset($fileinfo["tags"]["id3v1"]["tracknum"][0]) AND !empty($fileinfo["tags"]["id3v1"]["tracknum"][0])) {
        $tracknum = $fileinfo["tags"]["id3v1"]["tracknum"][0];
    }
    elseif(isset($fileinfo["tags"]["id3v1"]["track"][0]) AND !empty($fileinfo["tags"]["id3v1"]["track"][0])) {
        $tracknum = $fileinfo["tags"]["id3v1"]["track"][0];
    }
    elseif(isset($fileinfo["tags"]["vorbiscomment"]["tracknumber"][0]) AND !empty($fileinfo["tags"]["vorbiscomment"]["tracknumber"][0])) {
        $tracknum = $fileinfo["tags"]["vorbiscomment"]["tracknumber"][0];
    }
    elseif(isset($fileinfo["tags"]["vorbiscomment"]["track"][0]) AND !empty($fileinfo["tags"]["vorbiscomment"]["track"][0])) {
        $tracknum = $fileinfo["tags"]["vorbiscomment"]["track"][0];
    }
    
    if(!isset($fileinfo["playtime_string"]))  { $fileinfo["playtime_string"] = "0:0"; }

    return(array($artist,$album,$title,$tracknum,$fileinfo["playtime_string"]));
}

#########################################################################################

function formatDateTime($time = 0)
{
    if($time == 0) { $time = time(); }
    return(date("m.d.Y H:i:s", $time));
}

#########################################################################################

?>
