<?php

$config = array();

$config["logfile"]      = "/tmp/webmp3.log";                      # place for our logfile
$config["cliPHPbinary"] = "/Applications/MAMP/bin/php5/bin/php";  # where is the command php binary?

#$config["ext"][".mp3"]["binary"] = "/usr/bin/mpg321";
#$config["ext"][".mp3"]["option"] = array("-q");
$config["ext"][".mp3"]["binary"] = "/sw/bin/mpg123";
$config["ext"][".mp3"]["option"] = array("-quiet", "-really-quiet");
$config["ext"][".ogg"]["binary"] = "/usr/bin/ogg123";
$config["ext"][".ogg"]["option"] = array("-q");
$config["ext"][".wma"]["binary"] = "/usr/bin/mplayer";
$config["ext"][".wma"]["option"] = array("-quiet", "-really-quiet");

$config["streamBin"]  = "/usr/bin/mplayer";                  # binary for playing streams
$config["playStrOpt"] = array("-quiet", "-really-quiet");    # additional options
$config["streamUrlPre"] = "http_proxy://localhost:8001/";    # requiered for proxy support

#$config["aumixBin"]   = "/usr/bin/aumix";       # executable to change volume
$config["aumixBin"]   = "/usr/bin/true";       # executable to change volume

$config["searchPath"] = "/Users/sven/Music/";             # path with your mp3/ogg files
#$config["searchPath"] = "/opt/projects/webmp3/musik/";   # path with your mp3/ogg files

$config["playlist"]    = "./var/playlist.dat";         # a file with your playlist and other temporary data
$config["hitlist"]     = "./var/hitlist.dat";          # a file with your played songs
$config["picWidth"]    = 120;                    # width of preview picture
$config["picHeight"]   = 120;                    # height of the preview picture
$config["plDir"]       ="./var/playlists/";          # folder for your saved playlists
$config["quietVol"]    = "25";                   # volume to set if someone presses the quiet button
$config["tagCache"]    = "./var/tagCache.dat";

# Notification when playing a song, %T Displayed Text
$config["notifyCommand"] = "echo '%T' | /usr/bin/nc localhost 23052";

# ip adresses which are allow to use this tool
$config["accControl"] = 0;                      # if 1, then ip based access will be used
$config["allowedIPs"] = array("127.0.0.1", "192.168.0.4", "192.168.0.2", "192.168.0.3");

?>
