<?php
 /***************************************************************************
 * $Id: Action.php 2 2008-04-24 13:41:58Z sven $
 ***************************************************************************/

#########################################################################################
##
## ACTION
##
if(isset($_SERVER["argv"][1]) AND substr($_SERVER["argv"][1], 0, 6) == "action")
{
    $action   = substr($_SERVER["argv"][1], 7);
}
elseif(isset($_GET["action"]))
{
    $action = $_GET["action"];
}
elseif(isset($_POST["action"]))
{
    $action = $_POST["action"];
}

if(isset($action))
{
    if(function_exists("action_".$action))
    {
        $ret = call_user_func("action_".$action);
    }
    else
    {
        user_error("function action_".$action." not found");
        $ret = action_default("");
    }
}
else
{
    $action = "default";
    $ret = action_default("");
}

#########################################################################################

?>