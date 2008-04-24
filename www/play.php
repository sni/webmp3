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

    $ppid = getmypid();

    if(isset($data["cpid"])) {
        killChild();
    }

    if(isset($data["curTrack"]) AND isset($data["playlist"][$data["curTrack"]])) {
        $track = $data["playlist"][$data["curTrack"]];
    } else {
        $tmp = $data["playlist"];
        $track = array_shift($tmp);
        $data["curTrack"] = $track["token"];
    }
    $pid = pcntl_fork();
    if ($pid == -1) {
         die('could not fork');
    } elseif ($pid) {
         // we are the parent

        $erg = pcntl_waitpid($pid, $status, WNOHANG);
        while($status == 0 AND $erg == 0) {
            $erg = pcntl_waitpid($pid, $status, WNOHANG);
            usleep(300);
        }

        doPrint("finished playing");

        # play next track
        $data = getData();
        unset($data["cpid"]);
        unset($data["ppid"]);
        unset($data["start"]);
        unset($data["length"]);
        unset($data["title"]);
        $track = getNextTrack($data["playlist"], $track["token"]);
        if($track) {
            $data["curTrack"] = $track;
            storeData($data);
            system('$(which php5) play.php >> /tmp/webmp3play.log 2>&1 &');
        } else {
            storeData($data);
        }

        exit();
    }
    else
    {
        // we are the child
        doPrint("playing: ".$track["filename"]);
        #doPrint($track);
        #$data = getData();
        $data["start"]  = time();
        $data["length"] = $track["lengths"];
        $data["title"]  = $track["display"];
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

        $data["cpid"] = getmypid();
        $data["ppid"] = $ppid;

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
        $options[] = $track["filename"];

        $data["aktBin"] = $playBin;
        storeData($data);
        doPrint("executing: ".$playBin." ".join(" ", $options));
        pcntl_exec($playBin, $options);

        # process already died, but for readability reasons
        exit();
    }
}

#################################################################

function sig_handler($signo)
{
    doPrint("got signal:".$signo);

    switch($signo)
    {
        case 15:
            // handle shutdown tasks
            $data = getData();
            if(isset($data["cpid"])) {
                posix_kill($data["cpid"], 15);
                doPrint("killed child: ".$data["cpid"]);
            }
            unset($data["cpid"]);
            unset($data["ppid"]);
            unset($data["start"]);
            unset($data["length"]);
            unset($data["title"]);
            unset($data["aktBin"]);
            storeData($data);
            doPrint("exiting...");
            exit;
            break;
        default:
        // handle all other signals
     }

}

#################################################################

// setup signal handlers
pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGUSR1, "sig_handler");
pcntl_signal(SIGUSR2, "sig_handler");

include("Action.php");

?>
