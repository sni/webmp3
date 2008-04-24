<!-- $Id: index.tpl,v 1.25 2007-01-02 14:26:59 sven Exp $ -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta http-equiv="expires" content="0">
  <meta name="author" content="Sven Nierlein">
  <meta name="publisher" content="Sven Nierlein">
  <meta name="copyright" content="Sven Nierlein">
  <meta name="description" content="">
  <meta name="keywords" content="">
  <link rel="stylesheet" href="style.css">
  <title><!--php: pageTitle --></title>
    <link rel="stylesheet" type="text/css" href="extjs/ext-all.css">
    <script type="text/javascript" src="extjs/ext-base.js"></script>
    <script type="text/javascript" src="extjs/ext-all.js"></script>
    <script type="text/javascript" src="extjs/slider.js"></script>
    <link rel="stylesheet" type="text/css" href="extjs/slider.css">
    <link rel="stylesheet" type="text/css" href="extjs/examples.css">
</head>
<body>
<script type="text/javascript" src="extjs/examples.js"></script>
  <table>
  <tr>
  <td><!--php: playingPic --></td>
  <td>
  <table width="850">
    <tr>
      <td><a href="index.php?action=prev&amp;aktPath=<!--php: aktPath -->&amp;search=<!--php: search -->">prev</a></td>
      <td><a href="index.php?action=<!--php: play -->&amp;aktPath=<!--php: aktPath -->&amp;search=<!--php: search -->"><!--php: play --></a></td>
      <td><a href="index.php?action=next&amp;aktPath=<!--php: aktPath -->&amp;search=<!--php: search -->">next</a></td>
      <td>|</td>
      <td nowrap width=300 valign="top" align="center">
        <div id="volume-slider"></div>
      </td>
      <td>|</td>
      <td><a href="index.php?action=mute&amp;aktPath=<!--php: aktPath -->&amp;search=<!--php: search -->"><!--php: mute --></a></td>
      <td><a href="index.php?action=quiet&amp;aktPath=<!--php: aktPath -->&amp;search=<!--php: search -->"><!--php: quiet --></a></td>
    </tr>
  </table>

  <table id="Link" width="850">
    <tr>
      <td nowrap><b>status:</b></td>
      <td nowrap><div id="status" title="<!--php: filename -->"><!--php: status --></div></td>
      <td nowrap>&nbsp;</td>
      <td nowrap><b>track:</b></td>
      <td nowrap><!--php: title --></td>
      <td nowrap>&nbsp;</td>
      <td nowrap><b><!--php: remaining -->:</b></td>
      <td nowrap><span id="pre"></span><span id="remMin"><!--php: remMin --></span>:<span id="remSec"><!--php: remSec --></span> min</td>
  </table>

  </td><td><!--php: fileSystemBackground -->
  </tr></table>

  <form id="form1" action="index.php" method="post">
  <input type="hidden" name="action" id="action" value="changePlaylist">
  <input type="hidden" name="to" id="to" value="">
  <input type="hidden" name="aktPath" value="<!--php: aktPath -->">
  <table border=0>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>
        Search: <input type="text" name="search" value="<!--php: search -->"><input type="submit" name="action" value="search">
      </td>
    </tr>
    <tr>
      <td valign="top">
        <!-- PLAYLIST -->
        <b>playlist (total playtime: <!--php: totalTime -->)</b><br>
        <select size=30 name="playlist[]" multiple style="font-family:monospace;width:550px">
          <!--php_start: playlist --><option value="[token]">[status]|&nbsp;[length]&nbsp;|&nbsp;[display]</option>
          <!--php_end: playlist -->
        </select>
        <br>
      </td>
      <td>
        <input type="submit" name="add" value="&lt;&lt;"><br><br>
        <input type="submit" name="del" value="&gt;&gt;">
      </td>
      <td valign="top">
        <!-- FILESYSTEM -->
        <table cellspacing=0 cellpadding=0 border=0>
          <tr>
            <td><b><!--php: PathLinks --></b></td>
            <td align="right"><input type="submit" value="&gt;" onClick="checkDirChange()"></td>
          </tr>
          <tr>
            <td colspan=2>
              <select onMouseOver="this.focus()" size=30 id="files" name="files[]" multiple onDblClick="checkDirChange()" style="font-family:monospace;width:550px">
                <option value="..">..</option>
                <!--php_start: directory --><option value="[file]">[file]</option>
                <!--php_end: directory -->
                <option disabled>------------------------------------------------</option>
                <!--php_start: filesystem --><option value="[file]">[display]</option>
                <!--php_end: filesystem -->
              </select>
            </td>
          </tr>
        </table>
      </td>
      </tr>
      <tr>
      <td colspan=3>
        <table>
          <tr>
            <td><a href="index.php?action=repeat&amp;aktPath=<!--php: aktPath -->&amp;search=<!--php: search -->"><!--php: repeat --></a></td>
            <td><a href="index.php?action=clear&amp;aktPath=<!--php: aktPath -->&amp;search=<!--php: search -->">clear</a></td>
            <td><a href="index.php?action=sort&amp;aktPath=<!--php: aktPath -->&amp;search=<!--php: search -->">sort</a></td>
            <td><a href="index.php?action=shuffle&amp;aktPath=<!--php: aktPath -->&amp;search=<!--php: search -->">shuffle</a></td>
            <td><a href="index.php?action=savePl&amp;aktPath=<!--php: aktPath -->" onclick="window.open(this.href, 'save playlist', 'width=300,height=70, resizable=yes, scrollbars=yes');return false;">playlist save</a></td>
            <td><a href="index.php?action=loadPl&amp;aktPath=<!--php: aktPath -->" onclick="window.open(this.href, 'load playlist', 'width=500,height=500, resizable=yes, scrollbars=yes');return false;">playlist load</a></td>
            <td><a href="index.php?action=hitlist&amp;aktPath=<!--php: aktPath -->" onclick="window.open(this.href, 'hitlist', 'width=800,height=500, resizable=yes, scrollbars=yes');return false;">hitlist</a></td>
            <td><a href="index.php?action=addStream&amp;aktPath=<!--php: aktPath -->" onclick="window.open(this.href, 'streamadd', 'width=500,height=70, resizable=yes, scrollbars=yes');return false;">add stream</a></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  </form>

<script type="text/javascript">
<!--
Ext.onReady(function(){
  webmp3.slider.setValue(<!--php: volume -->, 1);
});
-->
</script>
</body>
</html>
