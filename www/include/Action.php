<?php
#################################################################
#
# Action Modul
# Copyright 2002 Sven Nierlein, <sven@nierlein.de>
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