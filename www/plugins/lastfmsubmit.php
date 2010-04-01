<?php
#################################################################
#
# Copyright 2010 Danijel Tasov, <dt@korn.shell.la>
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

#################################################################
# webmp3PluginLastFM
#
# connects to lastfm and submits the current playing song
# as described in: http://www.audioscrobbler.net/development/protocol/
# or http://www.lastfm.de/api/submissions
#
class webmp3PluginLastFMSubmit
{
    protected $command = "/usr/lib/lastfmsubmitd/lastfmsubmit";

    public function __call($type, $arguments) {
        global $config;
        if( isset($config["lastfmsubmit_cmd"]) ) {
            $this->command = $config["lastfmsubmit_cmd"];
        }

        if( ! is_executable($this->command) ) {
            doPrint($this->command." is not executable");
            return(1);
        }

        if($type == "post_playing_song" or $type == "user_pressed_stop" or $type == "user_pressed_next") {
            $data = $arguments[0];
            if(isset($data['playingStream']) AND $data['playingStream'] == 0) {
                $this->sendSongToLastFM($data);
            }
        }
        return $arguments;
    }

    function sendSongToLastFM($data, $nowplaying = 0) {
        global $config;
        if(   !isset($data["title"])  or empty($data["title"])
           or !isset($data["artist"]) or empty($data["artist"])
           or !isset($data["album"])  or empty($data["album"])
        ) {
            return(1);
        }

        $length = $data["length"];
        $title  = $data["title"];
        $artist = $data["artist"];
        $album  = $data["album"];
        $track  = $data["track"];
            // ???
        $time   = $data["gmtimestart"]; 

        $played_sec = gmdate("U") - $time;
        if($nowplaying == 0) {
            if($played_sec < 30) {
            doPrint("lastfm: song must have been played for at least 30 seconds, this one has: ".$played_sec);
            return(1);
            }
        }
        if($length < 30) {
            doPrint("lastfm: songs have to be at least 30 seconds long, this one has: ".$length);
            return(1);
        }
        if($nowplaying == 0) {
           doPrint("lastfm: sending last played song");
           exec(
             $this->command
               .' --album '   .escapeshellarg($album)
               .' --artist '  .escapeshellarg($artist)
               .' --title '   .escapeshellarg($title)
               .' --length '  .escapeshellarg($length)
               //.' --time '    .escapeshellarg($time)
             , $output=array(), $return
           );
           if($return != 0) {
             doPrint("lastfm: $output");
             return(1);
           }
        } else {
           doPrint("lastfm: sending current playing song not implemented yet");
        }
    }
}

#################################################################
# initialize this plugin and register it globally
$lastfm = new webmp3PluginLastFMSubmit();
register_plugin("lastfmsubmit", $lastfm);
#doPrint("lastfm: plugin loaded", "DEBUG");

?>
