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
# $Id:$
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

    # TODO:
    # 
    # do some consistency checks
    #  - check var write permissions
    #  - check binarys in config

    redirect("webmp3.php");
}

#################################################################

?>
