<?php
#################################################################
# $Id:$
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
