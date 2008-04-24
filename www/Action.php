<?php
 /***************************************************************************
 * $Id: Action.php,v 1.1.1.1 2005/05/06 11:58:57 sven Exp $
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