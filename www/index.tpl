<!-- $Id$ -->
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

  <script type="text/javascript" src="LibCrossBrowser.js"></script>
  <script type="text/javascript" src="EventHandler.js"></script>
  <script type="text/javascript" src="Bs_FormUtil.lib.js"></script>
  <script type="text/javascript" src="Bs_Slider.class.js"></script>

  <script LANGUAGE="JavaScript">
  <!--
  function checkDirChange()
  {
    document.getElementById("action").value = "changeDir";
    document.getElementById("to").value = document.getElementById("files").options[document.getElementById("files").selectedIndex].value;
    document.getElementById("form1").submit();
  }

  function checkPlayFile()
  {
    var track = document.getElementById("playlist").options[document.getElementById("playlist").selectedIndex].value;
    location.href="index.php?action=play&track="+track+"&aktPath=<!--php: aktPath -->";
  }

  function viewTime()
  {
    var remMin=document.getElementById('remMin');
    var remSec=document.getElementById('remSec');
    var preMin=document.getElementById('pre');

    if(<!--php: stream -->) {
        // playing stream
        now=new Date();
        var started=<!--php: started -->;
        diff = Math.round(now.getTime()/1000 - started);

        remMin.innerHTML = Math.floor(diff/60);

        sec=diff%60;
        pre_s=((sec<10)?"0":"");
        remSec.innerHTML = pre_s+sec;
    } else {
        // playing file
        preMin.innerHTML = "-";

        if(remSec.innerHTML == 1 && remMin.innerHTML == "0") {
          window.setTimeout("location.reload()",3000);
          remSec.innerHTML = "";
          remMin.innerHTML = "";
          return(0);
        }
        if(remSec.innerHTML == "" && remMin.innerHTML == "") {
          return(0);
        }

        remSec.innerHTML = remSec.innerHTML -1;

        if(remSec.innerHTML < 0) {
          remSec.innerHTML = 59;
          remMin.innerHTML = remMin.innerHTML - 1;
        }

        pre_s=((remSec.innerHTML<10)?"0":"");
        remSec.innerHTML = pre_s+remSec.innerHTML;

        if(remMin.innerHTML < 0) {
          window.setTimeout("location.reload()",3000);
        }
    }

    window.setTimeout("viewTime()",999);
  }
  window.setTimeout("location.reload()",360000);

  function init(){
    mySlider3 = new Bs_Slider();
    mySlider3.attachOnChange(setVolChange);
    mySlider3.attachOnSlideEnd(setVolEnd);
    mySlider3.fieldName     = 'volume';
    mySlider3.width         = 200;
    mySlider3.height        = 15;
    mySlider3.minVal        = 1;
    mySlider3.maxVal        = 100;
    mySlider3.valueDefault  = <!--php: volume -->;
    mySlider3.valueInterval = 1;
    mySlider3.setBackgroundImage('./bg.png', 'no-repeat');
    mySlider3.setSliderIcon('./knob.png', 10, 21);
    mySlider3.useInputField = 1;
    mySlider3.draw('volDiv');

    lastChange=new Date();
  }

  function setVolChange(sliderObj, val, newPos) {

    now=new Date();
    diff_time = now.getTime() - lastChange.getTime();

    // allow updates only every 500ms
    if(diff_time > 500) {
      lastChange=new Date();
      pic = document.getElementById('volPic');
      pic.src="index.php?action=setVolume&vol="+val;
    }
  }

  function setVolEnd(sliderObj, val, newPos) {
    pic = document.getElementById('volPic');
    pic.src="index.php?action=setVolume&vol="+val;
  }

  // -->
  </script>
</head>
<body onLoad="init(); viewTime()">
  <table></tr>
  <td><!--php: playingPic --></td>
  <td>
  <table id="navigation" width="850">
    <tr>
      <td><a href="index.php?action=prev&aktPath=<!--php: aktPath -->&search=<!--php: search -->">prev</a></td>
      <td><a href="index.php?action=<!--php: play -->&aktPath=<!--php: aktPath -->&search=<!--php: search -->"><!--php: play --></a></td>
      <td><a href="index.php?action=next&aktPath=<!--php: aktPath -->&search=<!--php: search -->">next</a></td>
      <td>|</td>
      <td nowrap width=300 valign="top" align="center">
        <div id="volDiv">
        Volume
        <a href="index.php?action=volDown&vol=5&aktPath=<!--php: aktPath -->">&lt;&lt;</a>
        <a href="index.php?action=volDown&vol=1&aktPath=<!--php: aktPath -->">&lt;</a>
        <!--php: volume -->
        <a href="index.php?action=volUp&vol=1&aktPath=<!--php: aktPath -->">&gt;</a>
        <a href="index.php?action=volUp&vol=5&aktPath=<!--php: aktPath -->">&gt;&gt;</a>
        </div>
      </td>
      <td>|</td>
      <td><a href="index.php?action=mute&aktPath=<!--php: aktPath -->&search=<!--php: search -->"><!--php: mute --></a></td>
      <td><a href="index.php?action=quiet&aktPath=<!--php: aktPath -->&search=<!--php: search -->"><!--php: quiet --></a></td>
    </tr>
  </table>

  <table id="Link" width="850">
    <tr>
      <td nowrap><b>status:</b></td>
      <td nowrap><div title="<!--php: filename -->"><!--php: status --></div></td>
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
        <b>playlist (total playtime: <!--php: totalTime -->)<b><br>
        <select onMouseOver="this.focus()" size=30 id="playlist" id="playlist" name="playlist[]" multiple onDblClick="checkPlayFile()" style="font-family:monospace;width:550px">
          <!--php_start: playlist --><option value="[token]">[status]|&nbsp;[length]&nbsp;|&nbsp;[display]</option>
          <!--php_end: playlist -->
        </select><br>
      </td>
      <td>
        <input type="submit" name="add" value="&lt;&lt;"><br><br>
        <input type="submit" name="del" value="&gt;&gt;">
      </td>
      <td valign="top">
        <!-- FILESYSTEM -->
        <table cellspacing=0 cellpadding=0 border=0>
          <tr>
            <td><b><!--php: PathLinks --><b></td>
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
        <table id="navigation">
          <tr>
            <td><a href="index.php?action=repeat&aktPath=<!--php: aktPath -->&search=<!--php: search -->"><!--php: repeat --></a></td>
            <td><a href="index.php?action=clear&aktPath=<!--php: aktPath -->&search=<!--php: search -->">clear</a></td>
            <td><a href="index.php?action=sort&aktPath=<!--php: aktPath -->&search=<!--php: search -->">sort</a></td>
            <td><a href="index.php?action=shuffle&aktPath=<!--php: aktPath -->&search=<!--php: search -->">shuffle</a></td>
            <td><a href="index.php?action=savePl&aktPath=<!--php: aktPath -->" onclick="window.open(this.href, 'save playlist', 'width=300,height=70, resizable=yes, scrollbars=yes');return false;">playlist save</a></td>
            <td><a href="index.php?action=loadPl&aktPath=<!--php: aktPath -->" onclick="window.open(this.href, 'load playlist', 'width=500,height=500, resizable=yes, scrollbars=yes');return false;">playlist load</a></td>
            <td><a href="index.php?action=hitlist&aktPath=<!--php: aktPath -->" onclick="window.open(this.href, 'hitlist', 'width=800,height=500, resizable=yes, scrollbars=yes');return false;">hitlist</a></td>
            <td><a href="index.php?action=addStream&aktPath=<!--php: aktPath -->" onclick="window.open(this.href, 'streamadd', 'width=500,height=70, resizable=yes, scrollbars=yes');return false;">add stream</a></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  </form>
  <img src="bg.png" widht=0 height=0 id="volPic">
</body>
</html>
