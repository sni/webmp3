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

include("include/common.php");
include("include/Template.php");
include("include/Action.php");

#################################################################
#
# action_default()
#
#################################################################

function action_default()
{
    global $config;

    $failed = 0;

    print "<html><head><title>WebMP3 configuration check...</title></head><body>\n";
    print "doing some sanity checks...<br>";

    # check search path
    if(!isset($config["searchPath"])) {
        print "ERROR: music directory option missing, please set searchPath in the config.php<br>\n";
        $failed = 1;
    }
    elseif(!is_dir($config["searchPath"])) {
        print "ERROR: ".$config["searchPath"]." is not a valid directory<br>\n";
        $failed = 1;
    }

    # check var path
    if(!isset($config["plDir"])) {
        print "ERROR: playlists directory option missing, please set plDir in the config.php<br>\n";
        $failed = 1;
    }
    elseif(!file_exists($config["plDir"])) {
        print "INFO: trying to create ".$config["plDir"]."<br>\n";
        mkdir($config['plDir']);
        $failed = 1;
    }
    elseif(!is_dir($config["plDir"])) {
        print "INFO: playlist directory ".$config["plDir"]." is not a valid directory<br>\n";
        $failed = 1;
    }

    # check binarys
    if(!isset($config["ext"]) or !is_array($config["ext"])) {
        print 'ERROR: no extensions configured, $config["ext"] is not a vaild array<br>\n';
        $failed = 1;
    } else {
        foreach($config["ext"] as $name => $ext) {
            if(!isset($ext['binary'])) {
                print "ERROR: Extension ".$name." has no binary configured<br>\n";
                $failed = 1;
            }
            elseif(!file_exists($ext['binary'])) {
                print "ERROR: ".$ext['binary']." does not exist<br>\n";
                $failed = 1;
            }
            elseif(!is_executable($ext['binary'])) {
                print "ERROR: ".$ext['binary']." is not executable<br>\n";
                $failed = 1;
            }
        }
    }

    # check the php binary
    if(!isset($config["cliPHPbinary"])) {
        print "ERROR: php binary option missing, please set cliPHPbinary in the config.php<br>\n";
        $failed = 1;
    }
    elseif(!file_exists($config["cliPHPbinary"])) {
        print "ERROR: ".$config["cliPHPbinary"]." does not exist<br>\n";
        $failed = 1;
    }
    elseif(!is_executable($config["cliPHPbinary"])) {
        print "ERROR: ".$config["cliPHPbinary"]." is not executable<br>\n";
        $failed = 1;
    }

    # check the stream binary
    if(!isset($config["streamBin"])) {
        print "ERROR: stream binary option missing, please set streamBin in the config.php<br>\n";
        $failed = 1;
    }
    elseif(!file_exists($config["streamBin"])) {
        print "ERROR: ".$config["streamBin"]." does not exist<br>\n";
        $failed = 1;
    }
    elseif(!is_executable($config["streamBin"])) {
        print "ERROR: ".$config["streamBin"]." is not executable<br>\n";
        $failed = 1;
    }

    if(!isset($config["aumixBin"])) {
        print "ERROR: aumix binary option missing, please set aumixBin in the config.php<br>\n";
        $failed = 1;
    }
    elseif(!is_executable($config["aumixBin"])) {
        print "ERROR: ".$config["aumixBin"]." is not executable<br>\n";
        $failed = 1;
    } else {
        $vol = getVolume();
        if(!is_numeric($vol)) {
            print "ERROR: failed to get volume<br>\n";
            print "ERROR: please check your aumixBin: ".$config["aumixBin"]."<br>\n";
            print "ERROR: also check if your webserver user is in group audio<br>\n";
            $failed = 1;
        }
    }

    if(!$failed) {
        print "everything is fine...<br>\n";
        print "lets go to <a href='webmp3.php'>webmp3</a><br>\n";
        print '<script type="text/javascript">
               <!--
               document.location = "webmp3.php";
               -->
               </script>
               ';
    } else {
        print "<br>\n";
        print "please correct the errors above and <a href='.'>retry.</a><br>\n";
        print "configuration can be changed in the config.php<br>\n";
        print "<br>\n";
        print "or give it a try <a href='webmp3.php'>webmp3</a><br>\n";
    }

    print "</body></html>\n";
    //redirect("webmp3.php");
}

#################################################################

?>
