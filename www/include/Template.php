<?php
#################################################################
#
# Template Modul v2.4
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

error_reporting(2047);
class Template
{
    var $template_source;

    #########################################################################################
    /* function main() :
     *
     * needs   : filename : $file
     *
     * returns : 1 if successful
     */
    function main($file)
    {
        $this->template_source = join("",file($file)) or user_error("couldn't open file: $file");
        return(1);
    }

    #########################################################################################
    /* function code() :
     *
     * needs   : $input : array with the replacements
     *
     * returns : 1 is successful
     */
    function code($input)
    {
        #global $this;

        # first we make the mysql things
        while (strpos($this->template_source, "<!--php_query:") !== false)
        {
            $this->template_source = $this -> replace_query($this->template_source, $input);
        }

        # replaces the <!--php's
        while (strpos($this->template_source, "<!--php:") !== false)
        {
            $this->template_source = $this -> replace_php($this->template_source, $input);
        }

        while (strpos($this->template_source, "<!--php_start:") !== false)
        {
            $this->template_source = $this -> extract_loop_source($this->template_source, $input);
        }

        return(1);
    }

    #########################################################################################
    /* function t_print() :
     *
     * prints the template
     */
    function t_print()
    {
        #$this -> $template_source = str_replace("  ", "", $this -> $template_source);
        print $this->template_source;
    }

    #########################################################################################
    /* function return_file() :
     *
     * writes the template to a given filename
     */
    function write_file($filename)
    {
        if(!is_writable($filename)) {
            print("error: cannot open ".$filename." for writing");
            return(1);
        }
        $fp = fopen ($filename, "w");
        if(fwrite($fp, $this->template_source))
        {
            return(0);
        }
        else
        {
            return(1);
        }
    }

    #########################################################################################
    function return_template()
    {
        return($this->template_source);
    }


    #########################################################################################
    #########################################################################################
    /*
     * CLASS INTERN FUNCTIONS
     */

    /* function replace_php() :
     *
     * needs a single html code row :           $code_row
     *       the array with the replacements :  $input
     *
     * returns :
     * the replaced row
     */
    function replace_php($code_row, &$input)
    {
        # gets the variable name
        $pos1 = strpos($code_row, "<!--php:");
        $pos2 = strpos($code_row, " -->", $pos1);
        $key  = substr($code_row, $pos1 + 8, $pos2 - 8 - $pos1);

        # removes whithespaces
        $key  = trim($key);

        #replaces this
        if(isset($input{$key}))
        {
        }else
        {
            $input{$key} = "";
        }

        if(strpos($code_row, "<!--php: ".$key." -->") === false)
        {
            user_error("invalid key: ".$key);
        }
        else
        {
            return(str_replace("<!--php: $key -->", $input{$key}, $code_row ));
        }
    }

    #########################################################################################
    /* function do_loop() :
     *
     * needs $loop_source: html source with the comments to do a loop
     *       $loop_name  : name of the loop ( <!--php_start: name --> )
     *       $input      : array{loop_name} = array of hashes to to the loop, if not set, nothing is returned
     *
     * returns :
     * array with html code rows
     */
    function do_loop(&$loop_source, &$loop_name, &$input)
    {
        $return = "";
        if(isset($input{$loop_name}) AND is_array($input{$loop_name}))
        {
            foreach($input{$loop_name} as $name => $row_array)
            {
                $temp_source = $loop_source;
                # checks for other loops
                while (strpos($temp_source, "<!--php_start:") !== false)
                {
                    $temp_source = $this -> extract_loop_source($temp_source, $row_array);
                }
                if(is_array($row_array))
                {
                    foreach($row_array as $key => $value)
                    {
                        if(!(is_array($value)))
                        {
                            $temp_source = str_replace("[".$key."]", $value, $temp_source );
                        }
                    }
                }
                $return .= $temp_source;
            }
        }
        return($return);
    }

    #########################################################################################
    /* function extract_loop_source() :
     *
     * needs html source:                $code_source
     *       array with the replacments: $input
     *
     * returns :
     * returns the replaced html code source
     */
    function extract_loop_source(&$code_source, &$input)
    {
        $pos  = strpos($code_source, "<!--php_start:");
        $pos2 = strpos($code_source, "-->", $pos);
        $loopname = substr($code_source, $pos + 14, $pos2 - $pos - 14);
        $loopname = trim($loopname);

        if($pos !== false AND $pos2 !== false)
        {
            $temp = explode("<!--php_start: $loopname -->", $code_source);
            if(isset($temp[1]))
            {
                $befor_loop = $temp[0];
                $rest       = $temp[1];
                if(isset($temp[2]))
                {
                    array_shift($temp);
                    array_shift($temp);
                    $rest .= "<!--php_start: $loopname -->".join("<!--php_start: $loopname -->", $temp);
                }
            }
            else
            {
                $befor_loop = "";
                $rest       = $temp[0];
            }
            if(strpos($code_source ,"<!--php_end: $loopname -->") === false)
            {
                user_error("never ending loop found: $loopname");
            }
            $temp = explode("<!--php_end: $loopname -->", $rest);
            if(isset($temp[1]))
            {
                $after_loop = $temp[1];
                $loopsource = $temp[0];
                if(isset($temp[2]))
                {
                    array_shift($temp);
                    array_shift($temp);
                    $after_loop .= "<!--php_end: $loopname -->".join("<!--php_end: $loopname -->", $temp);
                }
            }
            else
            {
                $after_loop = "";
                $loopsource = $temp[0];
            }

            return($befor_loop.$this -> do_loop($loopsource, $loopname, $input).$after_loop);
        }
        else
        {
            return($code_source);
        }
    }

    #########################################################################################
    /* function do_mysql_query() :
     *
     * needs   :   Replacment Array in this form
                    array(
                        "con_id"    => MySQL Connection ID                    # the result of a mysql_connect()
                        "query"     => MySQL Query                            # ex.: SELECT 5+5 as TableHeaderName

                        "options"   => array(
                                       "numbered"    => "&nbsp;",             # Should the table be numbered
                                       "border"      => "0",                  # border size
                                       "cellpadding" => "0",                  # table cellpadding
                                       "cellspacing" => "0",                  # table cellspacing
                                       "table_wrap"  => 200,                  # wraps the tables after n rows
                                       "width"       => 900,                  # Width of Table
                                       "height"      => 200,                  # Height of Table
                                        ),
                        "field"     => array(
                                     2  =>  array(                            # number of the field
                                              "align"    => "center"              # specifies the align of a table cell
                                              "valign"   => "top"                 # specifies the valign of a table cell
                                              "nowrap"   => 1,                    # if 1, a nowrap will be inserted
                                              "view"     => 1,                    # if 1, field is shown, else not, default is 1
                                              "pre"      => "<img src=\"",        # text, which is displayed before
                                              "aft"      => "\" alt=\"flagge\">", # text, which is displayed after
                                              "func"     => "function"            # function which is called with the fielddata as parameter
                                              "func_row" => "function"            # function which is called with the hole row array as parameter
                                              "addlink"  => "?sort=2"             # create a link around the table header and appends this text at the end of the link
                                                 ),
                                        ),
                         )
     *
     * returns :   The MySQL result set
     *
     */
     function do_mysql_query($replacement)
     {
        # check if Query is a SELECT Statement
        $query = trim($replacement["query"]);
        $query = str_replace("\n", "", $query);
        $query = str_replace("\r", "", $query);

        # Wraps checken
        $wrap = 0;
        if(isset($replacement["options"]["table_wrap"]) AND is_numeric($replacement["options"]["table_wrap"]) AND $replacement["options"]["table_wrap"] >= 1)
        {
            if(strpos(strtolower($query), "limit") > 0)
            {
                print "Querys with wraps must not have a Limit";
                return("");
            }
            else
            {
                $wrap     = $replacement["options"]["table_wrap"];
                $num_rows = mysql_num_rows(mysql_query($query, $replacement["con_id"]));
                if($wrap >= $num_rows)
                {
                    $wrap = "";
                }
            }
        }

        if(strcasecmp(substr($query, 0 , 7),"explain") == 0)
        {
            $replacement["options"]["border"]   = 1;
            unset($replacement["options"]["numbered"]);
        }

        if(strcasecmp(substr($query, 0 , 6),"select") != 0 AND strcasecmp(substr($query, 0 , 7),"explain") != 0)
        {
            print("Query must be a SELECT or EXPLAIN Statement: <br>".$query);
        }
        else
        {
            # Border
            if(isset($replacement["options"]["border"]))
            {
                $border = " border=\"".$replacement["options"]["border"]."\"";
            } else {
                $border = "";
            }

            # Cellpadding
            if(isset($replacement["options"]["cellpadding"]))
            {
                $cellpadding = " cellpadding=\"".$replacement["options"]["cellpadding"]."\"";
            } else {
                $cellpadding = "";
            }

            # Cellspacing
            if(isset($replacement["options"]["cellspacing"]))
            {
                $cellspacing = " cellspacing=\"".$replacement["options"]["cellspacing"]."\"";
            } else {
                $cellspacing = "";
            }

            # Width
            if(isset($replacement["options"]["width"]))
            {
                $width = " width=\"".$replacement["options"]["width"]."\"";
            } else {
                $width = "";
            }

            # Height
            if(isset($replacement["options"]["height"]))
            {
                $height = " height=\"".$replacement["options"]["height"]."\"";
            } else {
                $height = "";
            }

            $return = "<table".$border.$width.$height.$cellpadding.$cellspacing.">\n<tr>\n";

            # Wrap
            if($wrap)
            {
                # richtige Url zusammenbauen
                $add_url = "";
                foreach($_GET as $get => $val) {
                    if($get != "tmp_start") {
                        $add_url .= "&amp;".$get."=".$val;
                    }
                }
                if(empty($add_url)) {
                    $add_url = $_SERVER["PHP_SELF"]."?";
                } else {
                    $add_url = $_SERVER["PHP_SELF"]."?".substr($add_url,5)."&amp;";
                }

                # Werte für die Pfeile bestimmen
                if(isset($_GET["tmp_start"]))
                {
                    $start = $_GET["tmp_start"];
                    $prev  = $_GET["tmp_start"] - $wrap;
                    $next  = $_GET["tmp_start"] + $wrap;
                    if($start == "") { $start = 0; }
                }
                else
                {
                    $start = 0;
                    $prev  = 0;
                    $next  = $wrap;
                }
                $next = max($next, $wrap);
                $prev = max(0, $prev);

                $query    = $query." LIMIT ".$start.",".$replacement["options"]["table_wrap"];
            }

            $i = 0;
            # Execute Query
            $result = mysql_query($query, $replacement["con_id"]) OR user_error("<b><br>MySQL FEHLER:<br><pre>".$query."</pre><br>MySQL: ".mysql_error()."</b>");

            $num_fields = mysql_num_fields ($result);

            # If wrap, then print the << and >> links
            if($wrap)
            {
                $tmp_anzahl = floor($num_rows/$wrap) + 1;
                if(isset($replacement["options"]["numbered"])) {
                    $colspan = ($num_fields) + 1;
                } else {
                    $colspan = ($num_fields);
                }

                $return .= "<td nowrap colspan=".$colspan." align=\"center\"><table width=\"100%\"><tr>\n";
                $jump_to = 0;
                $return .= "<td align=\"left\"><a href=\"".$add_url."tmp_start=".$prev."\"><b>&lt;&lt;</b></a></td>\n";

                $return .= "<td align=\"center\">\n";
                for($x = 1; $x <= $tmp_anzahl; $x++)
                {
                    $jump_to = ($x - 1) * $wrap;
                    if($jump_to == $start) {
                        if(!isset($replacement["options"]["wrap_highlight"])) { $replacement["options"]["wrap_highlight"] = "#FF0000"; }
                        $font = "<font color=\"".$replacement["options"]["wrap_highlight"]."\">"; $font2 = "</font>\n";
                    }
                    else{ $font = ""; $font2 = ""; }
                    $return .= "<a href=\"".$add_url."tmp_start=".$jump_to."\"><b>".$font.$x.$font2."</b></a>\n";
                }
                $return .= "</td>\n";
                $return .= "<td align=\"right\"><a href=\"".$add_url."tmp_start=".$next."\"><b>&gt;&gt;</b></a></td>\n";
                $return .= "</tr></table></td></tr><tr>\n";
            }

            if(isset($replacement["options"]["numbered"]))
            {
                $return .= "<th>".$replacement["options"]["numbered"]."</th>\n";
            }
            while ($i < $num_fields)
            {
                $meta = mysql_fetch_field ($result);
                if (!$meta)
                {
                    print "This was not a SELECT Statement";
                    return(0);
                }
                else
                {
                    # Print Table Headers
                    if($meta -> name == "") { $meta -> name = "&nbsp;"; }
                    if(!isset($replacement["field"][$i]["view"]) OR $replacement["field"][$i]["view"] == 1)
                    {
                        if(isset($replacement["field"][$i]["addlink"])) {
                            # richtige Url zusammenbauen
                            $add_url = "";
                            foreach($_GET as $get => $val) {
                                if($get != "tmp_start") {
                                    $add_url .= "&amp;".$get."=".$val;
                                }
                            }
                            if(empty($add_url)) {
                                $add_url = $_SERVER["PHP_SELF"]."?";
                            } else {
                                $add_url = $_SERVER["PHP_SELF"]."?".substr($add_url,5)."&amp;";
                            }
                            $return .= "<th><a href=\"".$add_url.$replacement["field"][$i]["addlink"]."\">".$meta -> name."</a></th>\n";
                        } else {
                            $return .= "<th>".$meta -> name."</th>\n";
                        }
                    }
                }
                $i++;
            }
            $return .= "</tr>\n";
            $row_num = 1;
            while($row = mysql_fetch_row($result))
            {
                $return .= "<tr>\n";
                if(isset($replacement["options"]["numbered"]))
                {
                    if($wrap) {
                        $return .= "<td>".($start+$row_num)."</td>\n";
                    } else {
                        $return .= "<td>$row_num</td>\n";
                    }
                }
                $i = 0;
                    foreach($row as $field)
                    {
                        if(!isset($replacement["field"][$i]["view"]) OR $replacement["field"][$i]["view"] == 1)
                        {
                            if(isset($replacement["field"][$i]["func"]))
                            {
                                if(isset($replacement["field"][$i]["tab1"]))
                                {
                                    $replacement["field"][$i]["args"] = $row[$replacement["field"][$i]["tab1"]];
                                }
                                if(isset($replacement["field"][$i]["args"]))
                                {
                                    $field = call_user_func($replacement["field"][$i]["func"], $field, $replacement["field"][$i]["args"]);
                                }
                                else
                                {
                                    $field = call_user_func($replacement["field"][$i]["func"], $field);
                                }
                            }
                            if(isset($replacement["field"][$i]["func_row"]))
                            {
                                $field = call_user_func($replacement["field"][$i]["func_row"], $row);
                            }
                            if(isset($replacement["field"][$i]["pre"]))
                            {
                                $field = $replacement["field"][$i]["pre"].$field;
                            }
                            if(isset($replacement["field"][$i]["aft"]))
                            {
                                $field = $field.$replacement["field"][$i]["aft"];
                            }
                            if(isset($replacement["field"][$i]["nowrap"]))
                            {
                                $nowrap = " nowrap";
                            }
                            else
                            {
                                $nowrap = "";
                            }
                            if(isset($replacement["field"][$i]["align"])) {
                                $align = " align=\"".$replacement["field"][$i]["align"]."\"";
                            } else {
                                $align = "";
                            }
                            if(isset($replacement["field"][$i]["valign"])) {
                                $valign = " valign=\"".$replacement["field"][$i]["valign"]."\"";
                            } else {
                                $valign = "";
                            }
                            $field = trim($field);
                            if($field !== "0" AND empty($field)) { $field = "&nbsp;"; }
                            $return .= "<td".$nowrap.$align.$valign.">\n";
                            $return .= $field;
                            $return .= "</td>\n";
                        }
                        $i++;
                    }
                $row_num++;
                $return .= "</tr>\n";
            }
            $return .= "</table>\n";
        }
        return($return);
     }

    #########################################################################################
    /* function replace_query() :
     *
     * needs con_id:   MySQL Connection ID
     *       query :   MySQL Query
     *
     * returns     :   Table with a formated output of the MySQL Query
     *
     */
     function replace_query(&$template_source, &$replacements)
     {
        # gets the variable name
        $pos1 = strpos($template_source, "<!--php_query:");
        $pos2 = strpos($template_source, " -->", $pos1);
        $key  = substr($template_source, $pos1 + 14, $pos2 - 14 - $pos1);

        # removes whithespaces
        $key  = trim($key);

        #replaces this
        if(isset($replacements{$key}) AND is_array($replacements{$key}))
        {
        }
        else
        {
            return(str_replace("<!--php_query: $key -->", "", $template_source ));
        }

        if(strpos($template_source, "<!--php_query: ".$key." -->") === false)
        {
            user_error("invalid Query Key: ".$key);
        }
        else
        {
            if(!isset($replacements[$key]["con_id"]))
            {
                $template_source = str_replace("<!--php_query: $key -->", "No Connection ID given", $template_source );
            }
            elseif(!isset($replacements[$key]["query"]))
            {
                $template_source = str_replace("<!--php_query: $key -->", "No MySQL Query given", $template_source );
            }
            else
            {
                $template_source = str_replace("<!--php_query: $key -->", $this -> do_mysql_query($replacements[$key]), $template_source );
            }
        }

        return($template_source);
     }
}
?>
