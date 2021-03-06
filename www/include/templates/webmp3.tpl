<!--
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
-->
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
  <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
  <title><!--php: pageTitle --></title>
  <script type="text/javascript" src="include/extjs/ext-base.js"></script>
  <script type="text/javascript" src="include/extjs/ext-all.js"></script>
  <link rel="stylesheet" type="text/css" href="include/extjs/ext-all.css">
  <link rel="stylesheet" type="text/css" href="include/extjs/slider.css">
  <link rel="stylesheet" type="text/css" href="images/webmp3.css">
</head>
<body>
<div id="viewport"></div>
<script type="text/javascript">
<!--

/* send debug output to firebug console */
function debug(str) {
    if (window.console != undefined) {
        console.debug(str);
    }
}

Ext.onReady(function(){
    Ext.namespace("webmp3");
    webmp3.version              = '<!--php: version -->';
    webmp3.sliderInit           = 1;
    webmp3.lastSliderUpdate     = new Date();
    webmp3.lastStatusUpdate     = new Date();
    webmp3.lastexception        = "";
    webmp3.token                = "<!--php: token -->";
    webmp3.pause                = <!--php: pause -->;
    webmp3.stream               = <!--php: stream -->;
    webmp3.lastHighlightedToken = "";
    webmp3.lastSearch           = "";
    webmp3.taskDelay            = new Ext.util.DelayedTask();
    webmp3.disablePlayMask      = false;
    webmp3.pathButtons          = new Array();
    webmp3.pathBeforeSearch     = "";
    webmp3.partymode            = <!--php: partymode -->;

/****************************************
 * Functions
 ***************************************/

  webmp3.jumpToFile = function(key, event) {
    var index = webmp3.FilesystemDataStore.find("file", String.fromCharCode(event.getKey()), 0, false, false);
    if(index != -1) {
      webmp3.fsm.selectRow(index);
      // helps to scroll to the right position
      webmp3.fsm.selectNext();
      webmp3.fsm.selectPrevious();
    }
  }

  webmp3.jumpUp = function(key, event) {
    webmp3.fsm.selectPrevious();
  }
  webmp3.jumpDown = function(key, event) {
    webmp3.fsm.selectNext();
  }

  webmp3.setpartymode = function() {
    if(webmp3.noTogggleEvents == 1) {
      return(1);
    }
    var msg = Ext.get('statustext');
    msg.load({
        url: 'webmp3.php',
        params: 'action=setToggle&button=partymode&param=' + webmp3.partymode,
        text: 'setting partymode to '+webmp3.partymode
    });
  }

  webmp3.partymodeMenuHandler = function(item, checked) {
    webmp3.partymode = item.inputValue;
    if(webmp3.noTogggleEvents == 1) {
      return(1);
    }
    webmp3.taskDelay.delay(50, webmp3.setpartymode, "partymode");
  }

  webmp3.saveToolbarBtnClicker = function() {
    webmp3.savePlaylistWindow.show();
    Ext.ComponentMgr.get('nameField').setValue('');
    Ext.ComponentMgr.get('nameField').focus(1, 100);
  }

  webmp3.loadToolbarBtnClicker = function() {
    webmp3.playlistLoadWindow.show();
    webmp3.playlistLoadWindow.center();
    webmp3.playlistsLoadDataStore.load();
  }

  webmp3.pathClickHandler = function(button, event) {
    webmp3.loadPath(button.initialConfig.tooltip);
  }

  webmp3.loadPath = function(path) {
    webmp3.FilesystemDataStore.load({
      url: 'webmp3.php',
      params: 'action=getFilesystem&aktPath=/&append='+webmp3.urlencode(path),
      text: 'loading files for '+ path
    });
    var blub = webmp3.aktPath;
    webmp3.aktPath = "/";
    updateFilePic(path);
    webmp3.aktPath = blub;
    Ext.ComponentMgr.get('filesearch').reset();
    Ext.ComponentMgr.get('filesearch').triggers[0].hide();
    webmp3.fileGrid.getColumnModel().getColumnById(0).hidden = true;
    webmp3.fileGrid.getBottomToolbar().hide();
    webmp3.lastSearch = "";
    webmp3.fileGrid.syncSize();
    webmp3.border.doLayout();
  }

  webmp3.fireException = function(el ,exception) {
    if(webmp3.lastexception != exception) {
      webmp3.lastexception = exception;
      var what = "";
      if(typeof( el ) == 'string') {
        what = el;
      } else {
        what = el.id;
      }
      Ext.Msg.show({
        title:'ERROR?',
        msg: "*** " + what + ": " + exception,
        icon: Ext.MessageBox.ERROR,
        buttons: Ext.Msg.OK
     });
    }
  }
    // play this token
    webmp3.playToken = function(token) {
        webmp3.PlaylistDataStore.load({
            url: 'webmp3.php',
            params: 'action=setToggle&button=Play&param=true&token=' + token,
            text: 'playing tack ' + token
        });
    }

    // replace all html entities to pass them via url
    webmp3.urlencode = function(s)
    {
      if(s == "") { return(""); }
      s = s.replace(/&/g, "%26");
      s = Ext.util.Format.htmlEncode(s);
      s = s.replace(/ /g, "+");
      return(s);
    }

    webmp3.updateTime = function() {
        var remMin=document.getElementById('remMin');
        var remSec=document.getElementById('remSec');
        var preMin=document.getElementById('pre');

        // refresh status every 5 minutes
        now=new Date();
        diff_time = now.getTime() - webmp3.lastStatusUpdate.getTime();
        if(diff_time > 300000) {
            webmp3.lastStatusUpdate = new Date();
            webmp3.disablePlayMask = true;
            webmp3.PlaylistDataStore.load();
        }

        if(webmp3.pause == true) {
            return(0);
        }

        if(webmp3.stream == true) {
            // playing stream
            preMin.innerHTML = " ";

            sec = new Number(remSec.innerHTML) + 1;
            min = new Number(remMin.innerHTML);

            if(sec > 59) {
                sec = 0;
                min = min + 1;
            }
            pre_s=((sec<10)?"0":"");
            remSec.innerHTML = pre_s+sec;
            remMin.innerHTML = min;
        } else {
            // playing file
            if((remSec.innerHTML == "" || remMin.innerHTML  == "") || (remSec.innerHTML == "01" && remMin.innerHTML  == "0")) {
              preMin.innerHTML = " ";
            } else {
              preMin.innerHTML = "-";
            }

            if(remSec.innerHTML == "01" && remMin.innerHTML == "0") {
                webmp3.disablePlayMask = true;
                window.setTimeout(webmp3.refreshPlaylist,2000);
                remSec.innerHTML = "";
                remMin.innerHTML = "";
                return(0);
            }
            if(remSec.innerHTML == "" && remMin.innerHTML == "") {
                return(0);
            }
            if(remSec.innerHTML == "00" && remMin.innerHTML == "0") {
                return(0);
            }

            remSec.innerHTML = remSec.innerHTML -1;

            if(remSec.innerHTML < 0) {
                remSec.innerHTML = 59;
                remMin.innerHTML = remMin.innerHTML - 1;
            }

            pre_s=((remSec.innerHTML<10)?"0":"");
            remSec.innerHTML = pre_s+remSec.innerHTML;
            remMin.innerHTML = remMin.innerHTML;
        }
    }

    webmp3.highlightCurrentSong = function() {
        // unset last highlighted Row
        last = document.getElementById("currentSong-"+webmp3.lastHighlightedToken);
        if(last) {
            last.style.backgroundColor = "";
        }
        token = webmp3.token;
        var index = webmp3.PlaylistDataStore.find("token", token);
        if(index != "-1") {
            webmp3.playlistGrid.getView().getRow(index).id = "currentSong-"+token;
            document.getElementById("currentSong-"+token).style.backgroundColor = "rgb(250,250,150)";
            webmp3.lastHighlightedToken = token;
        }
    }

    function updateFilePic(append) {
        if(document.getElementById('filePic').src == 'webmp3.php?action=pic&pic='+webmp3.urlencode(Ext.util.Format.stripTags(webmp3.aktPath)+"/"+append)) {
          return(1);
        }
        document.getElementById('filePic').src = 'webmp3.php?action=pic&pic='+webmp3.urlencode(Ext.util.Format.stripTags(webmp3.aktPath)+"/"+append);
    }

    function updatePlayPic() {
        if(document.getElementById('playPic').src == 'webmp3.php?action=pic&token='+webmp3.token) {
          return(1);
        }
        document.getElementById('playPic').src = 'webmp3.php?action=pic&token='+webmp3.token;
    }

    webmp3.ucFirst = function(s) {
      var words   = s.split(" ");
      var newWord = "";
      for(i=0;i<words.length;i++) {
        word = words[i];
        newWord = newWord + word.substr(0,1).toUpperCase() + word.substr(1,word.length).toLowerCase() + " ";
      }
      newWord = newWord.substr(0,newWord.length -1);
      return(newWord);
    };


    webmp3.refreshStatusData = function() {
        record = webmp3.StatusDataStore.getAt(0);

        // check server version against client version
        version = record.get('version');
        if(version != webmp3.version) {
          //Ext.Msg.show({
          //  title:'new version available',
          //  msg: 'server version differs from client<br>-&gt; reload required.<br><br><br><pre>client:'+webmp3.version+'<br>server:'+version+'<\/pre>',
          //  icon: Ext.MessageBox.WARNING,
          //  buttons: Ext.Msg.OK,
          //  minWidth: 450,
          //  fn: function(btn, text){
          //    if (btn == 'ok'){
                document.location.reload();
          //    }
          //  }
          //});
        }

        // set stream status
        webmp3.stream = record.get('stream');

        // set current token
        webmp3.token = record.get('token');

        // set Buttons
        webmp3.noTogggleEvents = 1;
        webmp3.pause = record.get('pause');
        Ext.ComponentMgr.get('pauseBtn').toggle(record.get('pause'));
        Ext.ComponentMgr.get('repeatBtn').toggle(record.get('repeat'));
        Ext.ComponentMgr.get('playBtn').toggle(record.get('play'));
        if(record.get('play') == 1) {
          Ext.ComponentMgr.get('playBtn').setText('Stop');
        } else {
          Ext.ComponentMgr.get('playBtn').setText('Play');
        }
        Ext.ComponentMgr.get('muteBtn').toggle(record.get('mute'));
        Ext.ComponentMgr.get('quietBtn').toggle(record.get('quiet'));

        // set current status
        document.getElementById('statusbar').innerHTML = record.get('status');

        // set current track data
        artist = webmp3.ucFirst(record.get('artist'));
        wikilink   = '';
        lastfmlink = '';
        if(artist != ' ') {
          wikilink   = '&nbsp;<a href="http://en.wikipedia.org/wiki/'+artist.replace(/ /g, "_")+'" target="_blank">'+artist+'<\/a>';
          lastfmlink = '&nbsp;<a href="http://www.lastfm.de/music/'+artist.replace(/ /g, "%20")+'" target="_blank"><img src="images/lastfm.png"><\/a>';
        }
        document.getElementById('artistText').innerHTML = wikilink + lastfmlink;
        document.getElementById('albumText').innerHTML = "&nbsp;" + record.get('album');
        document.getElementById('trackText').innerHTML = "&nbsp;" + record.get('nr');
        document.getElementById('titleText').innerHTML = "&nbsp;" + record.get('title');

        document.getElementById('remMin').innerHTML = record.get('remMin');
        document.getElementById('remSec').innerHTML = record.get('remSec');
        document.getElementById('pre').innerHTML = record.get('pre');

        // set total play time
        webmp3.playlistGrid.setTitle("Playlist  -  Files: "+webmp3.PlaylistDataStore.getTotalCount()+"  -  Total: " + record.get('totalTime'));

        // set partymode
        Ext.ComponentMgr.get('partymode-item'+record.get('partymode')).setChecked(true);

        // set volume
        webmp3.sliderInit = 1;
        webmp3.slider.setValue(record.get('volume'), 1);
        webmp3.sliderInit = 0;

        // set title
        if(record.get('play')) {
            if(webmp3.stream == true) {
              document.title = record.get('title');
            } else {
              document.title = record.get('nr') + ' - ' + record.get('title');
            }
        } else {
            document.title = 'WebMP3';
        }

        webmp3.noTogggleEvents = 0;

        webmp3.highlightCurrentSong();
        updatePlayPic();
        webmp3.fixButtonIcons();
    }
    webmp3.refreshStatusStore = function() {
        webmp3.lastStatusUpdate = new Date();
        webmp3.StatusDataStore.load({
            url: 'webmp3.php',
            params: 'action=getCurStatus',
            text: 'loading current status'
        });
    }
    webmp3.refreshPlaylist = function() {
        if(webmp3.disablePlayMask == true) {
          webmp3.playlistLoadingMask.disable();
        }
        webmp3.PlaylistDataStore.load();
    }

    webmp3.addSelectedToPlaylist = function() {
        var selects = webmp3.fsm.getSelections();
        var files = "";
        for(i=0;i<selects.length;i++)
        {
            files = files + "&add[]=" + webmp3.urlencode(selects[i].get('file'));
        }
        aktPath = webmp3.urlencode(webmp3.aktPath);
        webmp3.PlaylistDataStore.load({
            url: 'webmp3.php',
            params: 'action=getPlaylist&aktPath=' + aktPath + files,
            text: 'added files to playlist'
        });
        webmp3.fsm.clearSelections();
    }

/****************************************
 * Event Handler
 ***************************************/
    function onButtonToggle(item, pressed){
        if(webmp3.noTogggleEvents == 1) {
          return(1);
        }
        if(item.text == "Pause") {
            webmp3.pause = pressed;
        }

        if(item.text == "Play" || item.text == "Stop") {
            webmp3.PlaylistDataStore.load({
                url: 'webmp3.php',
                params: 'action=setToggle&button='+ item.text +'&param='+pressed,
                text: 'setting '+item.text+' to '+pressed
            });

        } else {
            var msg = Ext.get('statustext');
            msg.load({
                url: 'webmp3.php',
                params: 'action=setToggle&button='+item.text+'&param=' + pressed,
                text: 'setting '+item.text+' to '+pressed
            });
            webmp3.taskDelay.delay(1000, webmp3.refreshPlaylist, "refresh");
        }
        if(item.text == "Mute") {
          webmp3.noTogggleEvents = 1;
          Ext.ComponentMgr.get('quietBtn').toggle(0);
          webmp3.noTogggleEvents = 0;
          item.setText("Unmute");
        } else if(item.text == "Unmute") {
          webmp3.noTogggleEvents = 1;
          Ext.ComponentMgr.get('quietBtn').toggle(0);
          webmp3.noTogggleEvents = 0;
            item.setText("Mute");
        }
        if(item.text == "Play") {
            item.setText("Stop");
            webmp3.stream = false;
        } else if(item.text == "Stop") {
            item.setText("Play");
        }

        if(item.text == "Quiet") {
          webmp3.noTogggleEvents = 1;
          Ext.ComponentMgr.get('muteBtn').toggle(0);
          Ext.ComponentMgr.get('muteBtn').setText("Mute");
          webmp3.noTogggleEvents = 0;
        }

     webmp3.fixButtonIcons();
    }

    webmp3.fixButtonIcons = function() {
      btn = Ext.ComponentMgr.get('repeatBtn');
      if(btn.text == "Repeat") {
        if(btn.pressed == true) {
          btn.el.child('button:first').dom.style.backgroundImage = 'url(images/control_repeat_blue.png)';
        } else {
          btn.el.child('button:first').dom.style.backgroundImage = 'url(images/control_norepeat_blue.png)';
        }
      }

      btn = Ext.ComponentMgr.get('playBtn');
      if(btn.text == "Play") {
        btn.el.child('button:first').dom.style.backgroundImage = 'url(images/control_play_blue.png)';
      }
      else {
        btn.el.child('button:first').dom.style.backgroundImage = 'url(images/control_stop_blue.png)';
      }
    }

/****************************************
 * Volume Slider
 ***************************************/
    webmp3.slider = new Ext.Slider({
        width: 214,
        value: 0,
        minValue: 0,
        maxValue: 100,
        keyIncrement: 5
    });

    webmp3.resetQuietAndMuteButton = function() {
      var reset = "";
      if(Ext.ComponentMgr.get('muteBtn').pressed || Ext.ComponentMgr.get('quietBtn').pressed) {
        reset = "&reset=1";
        webmp3.noTogggleEvents = 1;
        Ext.ComponentMgr.get('muteBtn').toggle(0);
        Ext.ComponentMgr.get('muteBtn').setText("Mute");
        Ext.ComponentMgr.get('quietBtn').toggle(0);
        webmp3.noTogggleEvents = 0;
      }
      return(reset);
    }

    webmp3.slider.on("change", function(slider, value) {
        if(webmp3.sliderInit == 0) {
            var reset = webmp3.resetQuietAndMuteButton();
            webmp3.now=new Date();
            diff_time = webmp3.now.getTime() - webmp3.lastSliderUpdate.getTime();
            if(diff_time > 300) {
                var msg = Ext.get('statustext');
                msg.load({
                    url: 'webmp3.php',
                    params: 'action=setVolume'+reset+'&vol=' + slider.getValue(),
                    text: 'setting volume...' + slider.getValue()
                });
                webmp3.lastSliderUpdate = new Date();
            }
        }
    });
    webmp3.slider.on("dragend", function(slider, value) {
        if(webmp3.sliderInit == 0) {
            var reset = webmp3.resetQuietAndMuteButton();
            var msg = Ext.get('statustext');
            msg.load({
                url: 'webmp3.php',
                params: 'action=setVolume'+reset+'&vol=' + slider.getValue(),
                text: 'setting volume...' + slider.getValue()
            });
            webmp3.lastSliderUpdate = new Date();
        }
    });


/****************************************
 * Top Navigation Toolbar
 ***************************************/
    webmp3.navtoolbar = new Ext.Toolbar({
        margins: '0 0 0 0',
        items: [
                    {
                    text: 'Prev',
                    tooltip: 'Prev',
                    cls:"x-btn-text-icon",
                    icon: 'images/control_rewind_blue.png',
                    id: 'prevBtn'
                    }, '-', {
                    text: '<!--php: playText -->',
                    tooltip: 'Play',
                    enableToggle: true,
                    toggleHandler: onButtonToggle,
                    cls:"x-btn-text-icon",
                    id: 'playBtn',
                    pressed: <!--php: play -->
                    }, '-', {
                    text: 'Pause',
                    tooltip: 'Pause',
                    enableToggle: true,
                    toggleHandler: onButtonToggle,
                    cls:"x-btn-text-icon",
                    icon: 'images/control_pause_blue.png',
                    id: 'pauseBtn',
                    pressed: <!--php: pause -->
                    }, '-',{
                    text: 'Next',
                    tooltip: 'Next',
                    cls:"x-btn-text-icon",
                    icon: 'images/control_fastforward_blue.png',
                    id: 'nextBtn'
                    }, '-',
                    webmp3.slider
                    ,'-',{
                    text: '<!--php: muteText -->',
                    tooltip: 'Mute',
                    enableToggle: true,
                    toggleHandler: onButtonToggle,
                    cls:"x-btn-text-icon",
                    icon: 'images/sound_mute.png',
                    minWidth: 70,
                    id: 'muteBtn',
                    pressed: <!--php: mute -->
                    }, '-',{
                    text: 'Quiet',
                    enableToggle: true,
                    toggleHandler: onButtonToggle,
                    tooltip: 'Quiet',
                    cls:"x-btn-text-icon",
                    icon: 'images/sound_low.png',
                    minWidth: 70,
                    id: 'quietBtn',
                    pressed: <!--php: quiet -->
                    }
                ]
    });

/****************************************
 * Navigation Title Toolbar
 ***************************************/
webmp3.titlebar = new Ext.Toolbar({
    margins: '0 0 0 0',
    items: [
                {
                    xtype: 'tbtext',
                    text: 'Artist:'
                }, ' ', {
                    xtype: 'label',
                    html: '<!--php: artist -->',
                    width: 150,
                    id: 'artistText'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Album:'
                }, ' ', {
                    xtype: 'label',
                    html: '<!--php: album -->',
                    width: 150,
                    id: 'albumText'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Track:'
                }, ' ', {
                    xtype: 'label',
                    text: '<!--php: track -->',
                    width: 150,
                    id: 'trackText'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Title:'
                }, ' ', {
                    xtype: 'label',
                    html: '<!--php: title -->',
                    width: 150,
                    id: 'titleText'
                }, '-' ,{
                    xtype: 'tbtext',
                    text: 'Remaining:'
                }, ' ', {
                    xtype: 'label',
                    html: '<!--php: pre -->',
                    id: 'pre'
                },{
                    xtype: 'label',
                    html: '<!--php: remMin -->',
                    id: 'remMin'
                },{
                    xtype: 'tbtext',
                    text: ':'
                },{
                    xtype: 'label',
                    html: '<!--php: remSec -->',
                    id: 'remSec'
                },{
                    xtype: 'tbtext',
                    text: ' min'
                }
           ]
});

/****************************************
 * Navigation Status Toolbar
 ***************************************/
webmp3.statusbar = new Ext.Toolbar({
    margins: '0 0 0 0',
    height: 26,
    ctCls: 'vertical-align: middle',
    cls: 'vertical-align: middle',
    style: 'vertical-align: middle',
    items: [
                {
                    tooltip: 'Refresh status information',
                    cls: 'x-btn-icon',
                    id: 'refreshBtn',
                    icon: 'images/reload.png'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Status:'
                }, '-', {
                    xtype: 'label',
                    text: '',
                    id: 'statustext'
                }
            ]
});

/****************************************
 * Navigation Playing Toolbar
 ***************************************/
webmp3.playingbar = new Ext.Toolbar({
    margins: '0 0 0 0',
    items: [
                {
                    tooltip: 'Information about this application',
                    cls: 'x-btn-icon',
                    id: 'infoBtn',
                    icon: 'images/information.png'
                }, '-', {
                    xtype: 'panel',
                    text: '',
                    border: false,
                    id: 'statusbar'
                }
           ]
});
/****************************************
 * Playlist Navigation
 ***************************************/
    webmp3.navigation = new Ext.Panel({
        layout:'table',
        title: 'WebMP3',
        height: 156,
        region:'north',
        margins: '0 0 0 0',
        defaults: {
            bodyStyle:'padding:0px'
        },
        layoutConfig: {
            columns: 3
        },
        items: [{
            height: 120,
            width: 120,
            html: '<a href="#" onClick="return(false);"><img border="0" id="playPic" src="webmp3.php?action=pic&token='+webmp3.token+'"><\/a>',
            rowspan: 4
        },{
            items: [webmp3.navtoolbar]
        },{
            height: 120,
            width: 120,
            html: '<a href="#" onClick="return(false);"><img border="0" id="filePic" src="webmp3.php?action=pic&pic='+webmp3.aktPath+'"><\/a>',
            rowspan: 4
        },{
            items: [webmp3.statusbar]
        },{
            items: [webmp3.playingbar]
        },{
            items: [webmp3.titlebar]
        }
        ]
    });

/****************************************
 * Playlist Data Store
 ***************************************/
    webmp3.PlaylistDataStore = new Ext.data.Store({
        id: 'PlaylistDataStore',
        autoLoad: true,
        proxy: new Ext.data.HttpProxy({
                url: 'webmp3.php',
                method: 'POST'
        }),
        ddGroup : 'playlistDD',
        baseParams:{action: "getPlaylist"},
        reader: new Ext.data.JsonReader({
            root: 'results',
            totalProperty: 'total'
        },[
            {name: 'artist',  mapping: 'artist',   type: 'string'},
            {name: 'album',   mapping: 'album',    type: 'string'},
            {name: 'nr',      mapping: 'tracknum', type: 'string'},
            {name: 'title',   mapping: 'title',    type: 'string'},
            {name: 'length',  mapping: 'length',   type: 'string'},
            {name: 'token',   mapping: 'token',    type: 'string'}
        ]),
        listeners: {
            load: function(store, records, options) {
                      webmp3.highlightCurrentSong();
                      webmp3.refreshStatusStore();
                      webmp3.playlistLoadingMask.enable();
                  },
            loadexception: function(o, arg, e){
                var exception = e.status+' ' +e.statusText+': ' + e.responseText;
                webmp3.fireException(this, exception);
            }
        }
    });

/****************************************
 * Playlist CheckboxSelectionModel
 ***************************************/
    webmp3.psm = new Ext.grid.CheckboxSelectionModel({
        listeners: {
          beforerowselect : function (sm, rowIndex, keep, rec) {

            if (this.deselectingFlag && this.grid.enableDragDrop){
              this.deselectingFlag = false;
              this.deselectRow(rowIndex);
              return this.deselectingFlag;
            }

            return keep;
          }
        },
        onMouseDown : function(e, t){
            if (e.button === 0 ){
                e.stopEvent();
                var row = e.getTarget('.x-grid3-row');
                if(row){
                    var index = row.rowIndex;
                    if(this.isSelected(index)){
                        if (!this.grid.enableDragDrop)
                          this.deselectRow(index);
                        else
                          this.deselectingFlag = true;
                    }else{
                        if (this.grid.enableDragDrop)
                          this.deselectingFlag = false;
                        this.selectRow(index, true);
                    }
                }
            }
        }
    });

/****************************************
 * Playlist Grid
 ***************************************/
    webmp3.trackRenderer = function(nr) {
      if(nr == "") { return(""); }
      if(nr == null) { return(""); }
      var pre="";
      if(nr.length == 1) {
        pre="0";
      }
      return(pre+nr);
    }
    webmp3.playlistColModel = new Ext.grid.ColumnModel([
        {header: 'Length', sortable: true, dataIndex: 'length', align: 'right', width: 30 },
        {header: 'Artist', sortable: true, dataIndex: 'artist'},
        {header: 'Album',  sortable: true, dataIndex: 'album'},
        {header: 'Nr',     sortable: true, dataIndex: 'nr', width: 15, renderer: webmp3.trackRenderer },
        {header: 'Title',  sortable: true, dataIndex: 'title' },
        {header: 'Token',  sortable: false, hidden: true, hideable: false, dataIndex: 'token' }
     ]);
    webmp3.removeFromPlaylist = function() {
        var selects = webmp3.psm.getSelections();
        var tokens = "";
        for(i=0;i<selects.length;i++)
        {
            tokens = tokens + "&remove[]=" + selects[i].get('token');
        }
        webmp3.PlaylistDataStore.load({
            url: 'webmp3.php',
            params: 'action=getPlaylist' + tokens,
            text: 'removed items from playlist'
        });
    }
    webmp3.DropGridPanel = Ext.extend(Ext.grid.GridPanel, {
        droppedItem: function(dd, e, data) {
            var selects = data.selections;
            webmp3.s = selects;
            var files   = "";
            if(data.grid.id == "playlistGrid") {
              // drag&drop in our playlist
              var ds = webmp3.PlaylistDataStore;
              var rows=webmp3.psm.getSelections();
              // first remove them
              var keep = new Array();
              for(i = rows.length-1; i >= 0; i--) {
                  rowData=ds.getById(rows[i].id);
                  keep[i] = rowData;
                  ds.remove(ds.getById(rows[i].id));
              };
              var cindex=dd.getDragData(e).rowIndex;
              if (typeof cindex == "undefined") {
                // move to the end of the list
                cindex = ds.getCount();
              }
              // and then add them
              for(i = rows.length-1; i >= 0; i--) {
                  rowData=keep[i];
                  ds.insert(cindex,rowData);
              };
              // commit new sort order
              files = "";
              for(i = 0; i < ds.getCount(); i++) {
                token = ds.getAt(i).get('token');
                files = files + "&move[]="+token;
              }
              if(files != "") {
                webmp3.PlaylistDataStore.load({
                    url: 'webmp3.php',
                    params: 'action=getPlaylist' + files,
                    text: 'moved files in playlist'
                });
              }
              webmp3.fsm.clearSelections();
            }
            if(data.grid.title == "Filesystem") {
              // drag&drop from the filesystem
              if(selects.length == 0 && data.rowIndex >= 0) {
                files   = "&add[]=" + webmp3.urlencode(webmp3.FilesystemDataStore.getAt(data.rowIndex).get('file'));
              }
              for(i=0;i<selects.length;i++)
              {
                if(selects[i].get('file') != "") {
                  files = files + "&add[]=" + webmp3.urlencode(selects[i].get('file'));
                }
              }
              if(files != "") {
                webmp3.PlaylistDataStore.load({
                    url: 'webmp3.php',
                    params: 'action=getPlaylist&aktPath=' + webmp3.urlencode(webmp3.aktPath) + files,
                    text: 'added files to playlist'
                });
              }
              webmp3.fsm.clearSelections();
            }
        },
        onRender: function() {
          webmp3.DropGridPanel.superclass.onRender.apply(this, arguments);
          webmp3.dropZone = new Ext.dd.DropTarget(this.id, {
              ddGroup : 'playlistDD',
              notifyDrop : this.droppedItem
          });
        }
    });

    webmp3.playlistGrid = new webmp3.DropGridPanel({
        collapsible: false,
        enableDragDrop: true,
        autoExpandColumn: 4,
        viewConfig: {
            forceFit: true
        },
        stripeRows: true,
        sm: webmp3.psm,
        region:'center',
        margins: '5 0 0 0',
        store: webmp3.PlaylistDataStore,
        cm: webmp3.playlistColModel,
        //loadMask: {
        //  store: webmp3.PlaylistDataStore
        //},
        title: 'Playlist',
        width: 500,
        height:400,
        draggable: true,
        enableDrop: true,
        ddGroup : 'playlistDD',
        id: 'playlistGrid',
        keys: [
                {
                  key: Ext.EventObject.BACKSPACE,
                  fn: webmp3.removeFromPlaylist,
                  scope: this
                },
                {
                  key: Ext.EventObject.DELETE,
                  fn: webmp3.removeFromPlaylist,
                  scope: this
                }
        ],
        tbar: [
                  {
                    text: 'Repeat',
                    tooltip: 'Repeat',
                    cls: 'x-btn-text-icon',
                    enableToggle: true,
                    pressed: <!--php: repeat -->,
                    toggleHandler: onButtonToggle,
                    id: 'repeatBtn'
                  }, '-', {
                    text: 'Clear',
                    tooltip: 'Clear',
                    id: 'clearBtn'
                  }, '-', {
                    text: 'Sort',
                    tooltip: 'Sort',
                    id: 'sortBtn'
                  }, '-',{
                    text: 'Shuffle',
                    tooltip: 'Shuffle',
                    id: 'shuffleBtn'
                  }, '-',{
                    text: 'Playlists',
                    tooltip: 'Playlist Actions',
                    menu:{
                          id: 'playlistMenu',
                          items: [
                            {
                              xtype: 'button',
                              text: 'Load',
                              cls: 'x-btn-text-icon',
                              icon: 'images/folder_go.png',
                              handler: webmp3.loadToolbarBtnClicker
                            },
                            {
                              xtype: 'button',
                              text: 'Save',
                              cls: 'x-btn-text-icon',
                              icon: 'images/disk.png',
                              handler: webmp3.saveToolbarBtnClicker
                            }
                            ]}
                  }, '-',{
                    text: 'Hitlist',
                    tooltip: 'Hitlist',
                    id: 'hitlistBtn'
                  }, '-',{
                    text: 'Add Stream',
                    tooltip: 'add Stream',
                    id: 'addStream'
                  }, '-',{
                    text: 'Partymode',
                    tooltip: 'when enabled, played songs are automatically removed from the playlist',
                    menu:{
                          id: 'partymodeMenu',
                          items: [
                            {
                              text: "don't remove songs from the playlist",
                              checked: true,
                              group: 'partymode',
                              checkHandler: webmp3.partymodeMenuHandler,
                              id: 'partymode-item0',
                              inputValue: '0'
                            },
                            {
                              text: 'keep last 3 played songs',
                              group: 'partymode',
                              checked: false,
                              checkHandler: webmp3.partymodeMenuHandler,
                              id: 'partymode-item1',
                              inputValue: '1'
                            },
                            {
                              text: 'keep current album',
                              group: 'partymode',
                              checked: false,
                              checkHandler: webmp3.partymodeMenuHandler,
                              id: 'partymode-item2',
                              inputValue: '2'
                            }
                            ]}
                  },
                  '->',{
                    text: 'Remove from Playlist',
                    tooltip: 'Remove selected Items from the Playlist',
                    cls: 'x-btn-text-icon',
                    id: 'removeBtn',
                    icon: 'images/delete.png'
                  }
            ],
        listeners: {
            rowdblclick : function ( grid, rowIndex, event ) {
                                var token = webmp3.PlaylistDataStore.getAt(rowIndex).get('token');
                                webmp3.highlightCurrentSong();
                                webmp3.noTogggleEvents = 1;
                                Ext.ComponentMgr.get('playBtn').toggle(1);
                                Ext.ComponentMgr.get('playBtn').setText('Stop');
                                webmp3.noTogggleEvents = 0;
                                webmp3.PlaylistDataStore.load({
                                        url: 'webmp3.php',
                                        params: 'action=setToggle&button=Play&param=true&token=' + token,
                                        text: 'playing tack nr. ' + rowIndex
                                });
            }
        }
    });

/****************************************
 * Filesystem Searchfield
 ***************************************/
    webmp3.SearchField = Ext.extend(Ext.form.TwinTriggerField, {
        initComponent : function(){
            webmp3.SearchField.superclass.initComponent.call(this);
            this.on('specialkey', function(f, e){
                if(e.getKey() == e.ENTER){
                    this.onTrigger2Click();
                }
            }, this);
            this.on('keydown', function ( textField, e ) {
                webmp3.abcMap.disable();
            }, this);
            this.on('keyup', function ( textField, e ) {
              if(textField.getValue().length == 0 || (textField.getValue().length >= 2 && webmp3.lastSearch != textField.getValue())) {
                webmp3.lastSearch = textField.getValue();
                this.onTrigger2Click();
                webmp3.abcMap.enable();
              }
            }, this);
        },
        validationEvent:false,
        validateOnBlur:false,
        trigger1Class:'x-form-clear-trigger',
        trigger2Class:'x-form-search-trigger',
        hideTrigger1:true,
        width:180,
        enableKeyEvents: true,
        hasSearch : false,
        paramName : 'query',

        onTrigger1Click : function(){
            if(this.hasSearch){
                if(webmp3.pathBeforeSearch != "") {
                  webmp3.aktPath = webmp3.pathBeforeSearch;
                }
                this.el.dom.value = '';
                var o = {aktPath: webmp3.aktPath};
                this.store.baseParams = this.store.baseParams || {};
                this.store.baseParams[this.paramName] = '';
                this.store.reload({params:o});
                this.triggers[0].hide();
                this.hasSearch = false;
                webmp3.lastSearch = "";
                webmp3.fileGrid.getColumnModel().getColumnById(0).hidden = true;
                webmp3.fileGrid.getBottomToolbar().hide();
                webmp3.fileGrid.syncSize();
                webmp3.border.doLayout();
            }
        },

        onTrigger2Click : function(){
            if(webmp3.aktPath != "/") {
              webmp3.pathBeforeSearch = webmp3.aktPath;
            }
            webmp3.aktPath = "/";
            var v = this.getValue();
            if(v.length < 1){
                this.onTrigger1Click();
                return;
            }
            webmp3.fileGrid.getColumnModel().setColumnWidth(2, '90%');
            webmp3.fileGrid.getColumnModel().getColumnById(0).hidden = false;
            webmp3.fileGrid.getBottomToolbar().show();
            webmp3.fileGrid.syncSize();
            webmp3.border.doLayout();
            var o = {start: 0, limit: 100};
            this.store.baseParams = this.store.baseParams || {};
            this.store.baseParams[this.paramName] = v;
            this.store.reload({params:o});
            this.hasSearch = true;
            this.triggers[0].show();
        }
    });

/****************************************
 * Filesystem CheckboxSelectionModel
 ***************************************/
    webmp3.fsm = new Ext.grid.CheckboxSelectionModel({
        listeners: {
             beforerowselect : function (sm, rowIndex, keep, rec) {
                if (this.deselectingFlag && this.grid.enableDragDrop){
                    this.deselectingFlag = false;
                    this.deselectRow(rowIndex);
                    return this.deselectingFlag;
                }
                return keep;
            }
        },
        onMouseDown : function(e, t){
            if (e.button === 0 ){
                e.stopEvent();
                var row = e.getTarget('.x-grid3-row');
                if(row){
                    var index = row.rowIndex;
                    if(this.isSelected(index)){
                        if (!this.grid.enableDragDrop)
                          this.deselectRow(index);
                        else
                          this.deselectingFlag = true;
                    }else{
                        if (this.grid.enableDragDrop)
                          this.deselectingFlag = false;
                        this.selectRow(index, true);
                    }
                }
            }
        }
    });

/****************************************
 * Filesystem
 ***************************************/
    webmp3.jumpRenderer = function(src, p, record) {
      p.attr = 'ext:qtip="jump to this directory" ext:qtitle="Quick Dir Change!"';
      return("<img width=16 height=16 src='images/arrow_right.png'>");
    }
    webmp3.iconRenderer = function(src) {
      return("<img width=16 height=16 src='"+src+"'>");
    }
    webmp3.FilesystemDataStore = new Ext.data.Store({
        id: 'FilesystemDataStore',
        autoLoad: true,
        proxy: new Ext.data.HttpProxy({
                url: 'webmp3.php',
                method: 'POST'
        }),
        baseParams:{action: "getFilesystem"},
        ddGroup : 'playlistDD',
        reader: new Ext.data.JsonReader({
            root: 'results',
            totalProperty: 'total'
        },[
            {name: 'file',    mapping: 'file',    type: 'string'},
            {name: 'type',    mapping: 'type',    type: 'string'},
            {name: 'icon',    mapping: 'icon',    type: 'string'}
        ]),
        listeners: {
            load: function(store, records, options) {
                record = store.getAt(0);
                webmp3.aktPath = record.get('file');
                store.remove(record);
                document.getElementById('statustext').innerHTML = "change path to " + webmp3.aktPath;
                // remove old buttons
                if(Ext.ComponentMgr.get('pathButton')) {
                  Ext.ComponentMgr.get('pathButton').destroy();
                }
                for(i=0;i<webmp3.pathButtons.length;i++) {
                  if(webmp3.pathButtons[i]) {
                    Ext.ComponentMgr.get(webmp3.pathButtons[i]).destroy();
                  }
                }
                // remove last two elements (fill el. and update tag cache)
                if(webmp3.fileGrid.getTopToolbar().items) {
                  webmp3.fileGrid.getTopToolbar().items.removeAt(webmp3.fileGrid.getTopToolbar().items.length-1).destroy();
                  webmp3.fileGrid.getTopToolbar().items.removeAt(webmp3.fileGrid.getTopToolbar().items.length-1).destroy();
                }
                webmp3.pathButtons = new Array();

                // add new buttons
                webmp3.fileGrid.getTopToolbar().addButton({
                  text: '/',
                  tooltip: '/',
                  handler: webmp3.pathClickHandler,
                  id: 'pathButton',
                  cls: 'pathButton'
                });
                var tmpPath   = "/";
                var allPaths  = webmp3.aktPath.split("/");
                for(i=0;i<allPaths.length;i++) {
                  path = allPaths[i];
                  if(!Ext.isEmpty(path)) {
                    tmpPath   += path+"/";
                    btn = new Ext.Toolbar.Button({
                      text: path + "/",
                      tooltip: tmpPath,
                      handler: webmp3.pathClickHandler,
                      id: 'pathButton-'+i,
                      cls: 'pathButton'
                    });
                    webmp3.pathButtons[i] = 'pathButton-'+i;
                    webmp3.fileGrid.getTopToolbar().add(btn);
                  }
                }

                // add update tag cache btn
                webmp3.fileGrid.getTopToolbar().addFill();
                btn = new Ext.Toolbar.Button({
                    tooltip: 'Update Tag Cache',
                    cls: 'x-btn-icon',
                    id: 'tagCacheUpdateBtn',
                    icon: 'images/reload.png',
                    listeners: {
                        click: function() {
                            var updateBtn = this;
                            updateBtn.el.select('BUTTON').setStyle('backgroundImage', 'url(images/loading-icon.gif)');
                            updateBtn.disable();
                            Ext.Ajax.request({
                               url: 'webmp3.php?action=updateTagCache',
                               success: function() {
                                    updateBtn.el.select('BUTTON').setStyle('backgroundImage', 'url(images/reload.png)');
                                    updateBtn.enable();
                               },
                               failure: function() {
                                    updateBtn.el.select('BUTTON').setStyle('backgroundImage', 'url(images/reload.png)');
                                    updateBtn.enable();
                               },
                               params: {}
                            });
                        }
                    }
                });
                webmp3.fileGrid.getTopToolbar().add(btn);
            },
            loadexception: function(o, arg, e){
                var exception = e.status+' ' +e.statusText+': ' + e.responseText;
                webmp3.fireException(this, exception);
            }
        }
    });

    webmp3.fileGrid = new Ext.grid.GridPanel({
        collapsible: true,
        region:'east',
        margins: '5 0 0 0',
        enableDragDrop: true,
        ddGroup : 'playlistDD',
        autoExpandColumn: 'file',
        sm: webmp3.fsm,
        id: 'fileGridPanel',
        store: webmp3.FilesystemDataStore,
        loadMask: {
          store: webmp3.FilesystemDataStore
        },
        columns: [
            {header: 'Jump', sortable: false, hidden: true, hideable: false,  dataIndex: 'file', width: 25, renderer: webmp3.jumpRenderer },
            {header: ' ', dataIndex: 'icon', renderer: webmp3.iconRenderer, width: 5, menuDisabled: true },
            {header: 'Files & Directories', dataIndex: 'file', width: 125, menuDisabled: true },
            {header: 'Type', sortable: false, hidden: true, hideable: false,  dataIndex: 'type'}
        ],
        viewConfig: {
            forceFit: true
        },
        listeners: {
            cellclick : function ( grid, rowIndex, columnIndex, event ) {
                if(columnIndex == 0) {
                  var record = grid.getStore().getAt(rowIndex);
                  var data = record.get('file');
                  webmp3.loadPath(data);
                }
            },
            celldblclick: function(grid, rowIndex, columnIndex, e) {
                var record = grid.getStore().getAt(rowIndex);
                var fieldName = grid.getColumnModel().getDataIndex(columnIndex);
                var data = record.get('file');
                updateFilePic(record.get('file'));
                if(record.get('type') == "D") {
                    webmp3.FilesystemDataStore.load({
                        url: 'webmp3.php',
                        params: 'action=getFilesystem&aktPath=' + webmp3.urlencode(webmp3.aktPath) + '&append=' + webmp3.urlencode(record.get('file')),
                        text: 'loading files for '+record.get('file')
                    });
                } else {
                    webmp3.PlaylistDataStore.load({
                        url: 'webmp3.php',
                        params: 'action=getPlaylist&aktPath=' + webmp3.urlencode(webmp3.aktPath) + "&add[]="+record.get('file'),
                        text: 'added files to playlist'
                    });
                }
            }
        },
        title: 'Filesystem',
        width: 500,
        height:400,
        keys: [
                {
                  key: Ext.EventObject.ENTER,
                  fn: webmp3.addSelectedToPlaylist,
                  scope: this
                },
                {
                  key: Ext.EventObject.DOWN,
                  fn: webmp3.jumpDown,
                  scope: this
                },
                {
                  key: Ext.EventObject.UP,
                  fn: webmp3.jumpUp,
                  scope: this
                }
        ],
        tbar: [
                  {
                    text: 'Add',
                    tooltip: 'Add selected files to the playlist',
                    icon:      'images/add.png',
                    iconCls: 'add',
                    id: 'addBtn'
                  }, '-',
                  new webmp3.SearchField({
                                        store: webmp3.FilesystemDataStore,
                                                        params: {start: 0, limit: 20},
                                        width: 80,
                                        id: 'filesearch'
                            }),
                  ' ', '-', ' ', {
                    xtype: 'panel',
                    html: '',
                    border: false,
                    id: 'filestatus'
                  }
              ],
        bbar: [
          new Ext.PagingToolbar({
            pageSize: 100,
            autoHeight: true,
            width: 500,
            hideParent: true,
            store: webmp3.FilesystemDataStore,
            displayInfo: true,
            id: 'webmp3.fileSystemSearchPagingToolbar'
          })
        ]
    });

/****************************************
 * Main Border Layout Panel
 ***************************************/
    webmp3.border = new Ext.Viewport({
        layout: 'border',
        defaults: {
            activeItem: 0
        },
        title: 'WebMP3',
        renderTo: 'viewport',
        margins: '0 0 0 0',
        defaults: {
            bodyStyle: 'padding:3px'
        },
        items: [
        webmp3.navigation
        ,webmp3.playlistGrid
        ,webmp3.fileGrid
        ]
    });

/****************************************
 * Playlist Loading Mask
 ***************************************/
  webmp3.playlistLoadingMask = new Ext.LoadMask('playlistGrid', {
    store: webmp3.PlaylistDataStore
  });

/****************************************
 * Playlist Button EventHandler
 ***************************************/
    Ext.get('clearBtn').on("click", function(button, event) {
      // first select all, then remove the selected
      // so we can be sure, to only clear only actual view of playlist items
      webmp3.psm.selectRange(0, webmp3.PlaylistDataStore.getCount()-1);
      webmp3.removeFromPlaylist();
    });
    Ext.get('shuffleBtn').on("click", function(button, event) {
        webmp3.PlaylistDataStore.load({
            url: 'webmp3.php',
            params: 'action=getPlaylist&shuffle=1',
            text: 'cleared playlist'
        });
    });
    Ext.get('sortBtn').on("click", function(button, event) {
        webmp3.PlaylistDataStore.load({
            url: 'webmp3.php',
            params: 'action=getPlaylist&sort=1',
            text: 'cleared playlist'
        });
    });

    Ext.get('removeBtn').on("click", function(button, event) {
      webmp3.removeFromPlaylist();
    });

    Ext.get('refreshBtn').on("click", function(button, event) {
      webmp3.refreshPlaylist();
    });
    Ext.get('infoBtn').on("click", function(button, event) {
        Ext.Msg.show({
          title:'About WebMP3',
          msg: 'WebMP3<br><br>Copyright 2008 Sven Nierlein, sven@nierlein.de<br><br>License: GPL v3<br>Download it at <a href="https://github.com/sni/webmp3" target="_blank">https://github.com/sni/webmp3<\/a><br><br>Version: '+webmp3.version,
          icon: Ext.MessageBox.INFO,
          buttons: Ext.Msg.OK
        });
        webmp3.PlaylistDataStore.load();
    });
    Ext.get('nextBtn').on("click", function(button, event) {
        token = webmp3.token;
        var index = webmp3.PlaylistDataStore.find("token", token);
        index++;
        record = webmp3.PlaylistDataStore.getAt(index)
        if(record) {
            token = record.get('token');
            webmp3.token = token;
            webmp3.highlightCurrentSong();
            webmp3.taskDelay.delay(1000, webmp3.playToken, "playButton", [ token ]);
        }
    });
    Ext.get('prevBtn').on("click", function(button, event) {
        token = webmp3.token;
        var index = webmp3.PlaylistDataStore.find("token", token);
        index--;
        if(index == "-1") {
            return(1);
        }
        token = webmp3.PlaylistDataStore.getAt(index).get('token');
        if(token) {
            webmp3.token = token;
            webmp3.highlightCurrentSong();
            webmp3.taskDelay.delay(1000, webmp3.playToken, "playButton", [ token ]);
        }
    });

/****************************************
 * initially hide our paging tool bar
 ***************************************/
    webmp3.fileGrid.getBottomToolbar().hide();
    webmp3.fileGrid.syncSize();
    webmp3.border.doLayout();

/****************************************
 * saving playlists
 ***************************************/
  webmp3.savePlaylist = function() {
    var value=webmp3.urlencode(Ext.ComponentMgr.get('nameField').getValue());
    webmp3.StatusDataStore.load({
        url: 'webmp3.php',
        params: 'action=savePlaylist&name=' + value,
        text: 'saving playlist'
    });
    webmp3.savePlaylistWindow.hide();
  }
  webmp3.playlistSaveForm = new Ext.form.FormPanel({
        height: 24,
        width: 350,
        items: [
                {
            xtype: 'textfield',
            hideLabel: true,
            name: 'name',
            allowBlank: false,
            id: 'nameField',
            value: '',
            width: 350,
            height: 24,
            msgTarget: 'side',
            selectOnFocus: true
          }
        ]
  });
  webmp3.savePlaylistWindow = new Ext.Window({
        title: 'save playlist',
        height: 'auto',
        width: 400,
        resizable: false,
        bodyStyle:'padding:15px',
        border: true,
        closable: true,
        modal: true,
        closeAction: 'hide',
        buttonAlign: 'center',
        items: [webmp3.playlistSaveForm],
        buttons: [{
            text: 'Save',
            id: 'savePlaylistAddBtn'
        },{
            text: 'Cancel',
            id: 'savePlaylistCloseBtn'
        }]
    });

    Ext.ComponentMgr.get('savePlaylistAddBtn').on("click", function(button, event) {
      webmp3.savePlaylist();
    });
    Ext.ComponentMgr.get('savePlaylistCloseBtn').on("click", function(button, event) {
      webmp3.savePlaylistWindow.hide();
    });

/****************************************
 * adding Streams
 ***************************************/
  webmp3.addStream = function() {
    webmp3.enterMap.disable();
    var value=Ext.ComponentMgr.get('urlField').getValue();
    webmp3.PlaylistDataStore.load({
            url: 'webmp3.php',
            params: 'action=getPlaylist&add[]=' + value,
            text: 'added stream to the playlist'
        });
    webmp3.streamAddWindow.hide();
  }
  webmp3.streamForm = new Ext.form.FormPanel({
        height: 24,
        width: 350,
        items: [
                {
            xtype: 'textfield',
            hideLabel: true,
            name: 'url',
            allowBlank: false,
            id: 'urlField',
            value: 'http://',
            width: 350,
            height: 24,
            msgTarget: 'side',
            selectOnFocus: true
          }
        ]
  });
  webmp3.enterMap = new Ext.KeyMap(document, {
    key: Ext.EventObject.ENTER,
    fn: webmp3.addStream,
    scope: 'urlField'
  });
  webmp3.enterMap.disable();
  webmp3.streamAddWindow = new Ext.Window({
        title: 'add Stream',
        height: 'auto',
        width: 400,
        resizable: false,
        bodyStyle:'padding:15px',
        border: true,
        closable: true,
        modal: true,
        closeAction: 'hide',
        buttonAlign: 'center',
        items: [webmp3.streamForm ],
        buttons: [{
            text: 'Add',
            id: 'addStreamAddBtn'
        },{
            text: 'Cancel',
            id: 'addStreamCloseBtn'
        }]
    });

    Ext.get('addStream').on("click", function(button, event) {
      webmp3.streamAddWindow.show();
      webmp3.enterMap.enable();
      Ext.ComponentMgr.get('urlField').setValue('http://');
      Ext.ComponentMgr.get('urlField').focus(1, 100);
    });
    Ext.ComponentMgr.get('addStreamAddBtn').on("click", function(button, event) {
      webmp3.addStream();
    });
    Ext.ComponentMgr.get('addStreamCloseBtn').on("click", function(button, event) {
      webmp3.streamAddWindow.hide();
      webmp3.enterMap.disable();
    });

/****************************************
 * Picture Window
 ***************************************/
    webmp3.showPictureWindow = function(url, title) {
        var urlFull = url + "&full=yes";
        webmp3.pictureWindow = new Ext.Window({
            title: title,
            height: '120',
            width: '120',
            buttonAlign: 'center',
            //layout:'fit',
            items: [{
              xtype: 'panel',
              id: 'picPanel',
              border: false,
              html: '<img width=120 height=120 id="folderPicHuge" src="">'
            }],
            buttons: [
                      {
                        text: 'Close',
                        id: 'picWindowCloseBtn'
                      }
                  ]
        });
        webmp3.preload = Ext.DomHelper.append(document.body, {tag:"img", src:urlFull, id:"fullImg", style:"display:none"}, true);
        webmp3.preload.on('load', function() {
        //  //loaded
          webmp3.preload.show();
          Ext.get('folderPicHuge').replaceWith(webmp3.preload);
          webmp3.pictureWindow.setWidth(Ext.get('fullImg').getWidth() + 15);
          Ext.ComponentMgr.get('picPanel').setSize(Ext.get('fullImg').getSize());
          webmp3.pictureWindow.center();
        });
        webmp3.pictureWindow.show();
        Ext.get('picWindowCloseBtn').on("click", function(button, event) {
          webmp3.pictureWindow.hide(1);
          webmp3.pictureWindow.close();
          webmp3.pictureWindow.destroy();
        });
        webmp3.preload.on("click", function(button, event) {
          webmp3.pictureWindow.hide(1);
          webmp3.pictureWindow.close();
          webmp3.pictureWindow.destroy();
        });
      }
    Ext.get('playPic').on("click", function(button, event) {
      var index  = webmp3.PlaylistDataStore.find("token", webmp3.token);
      var album  = "";
      var artist = "";
      if(index >= 0) {
        album  = webmp3.PlaylistDataStore.getAt(index).get('album');
        artist = webmp3.PlaylistDataStore.getAt(index).get('artist');
      }
      webmp3.showPictureWindow(document.getElementById('playPic').src, artist+' - '+album);
    });
    Ext.get('filePic').on("click", function(button, event) {
      webmp3.showPictureWindow(document.getElementById('filePic').src, webmp3.aktPath);
    });

/****************************************
 * Filesystem Button EventHandler
 ***************************************/
    Ext.get('addBtn').on("click", function(button, event) {
      webmp3.addSelectedToPlaylist();
    });

/****************************************
 * Current Status DataStore
 ***************************************/
    webmp3.StatusDataStore = new Ext.data.Store({
        id: 'StatusDataStore',
        autoLoad: false,
        proxy: new Ext.data.HttpProxy({
                url: 'webmp3.php',
                method: 'POST'
        }),
        baseParams:{action: "getCurStatus"},
        reader: new Ext.data.JsonReader({
            root: 'results',
            totalProperty: 'total'
        },[
            {name: 'artist',    mapping: 'artist',    type: 'string'},
            {name: 'album',     mapping: 'album',     type: 'string'},
            {name: 'nr',        mapping: 'nr',        type: 'string'},
            {name: 'title',     mapping: 'title',     type: 'string'},
            {name: 'length',    mapping: 'length',    type: 'string'},
            {name: 'token',     mapping: 'token',     type: 'string'},
            {name: 'volume',    mapping: 'volume',    type: 'int'},
            {name: 'status',    mapping: 'status',    type: 'string'},
            {name: 'remMin',    mapping: 'remMin',    type: 'string'},
            {name: 'remSec',    mapping: 'remSec',    type: 'string'},
            {name: 'pre',       mapping: 'pre',       type: 'string'},
            {name: 'play',      mapping: 'play',      type: 'int'},
            {name: 'pause',     mapping: 'pause',     type: 'int'},
            {name: 'repeat',    mapping: 'repeat',    type: 'int'},
            {name: 'mute',      mapping: 'mute',      type: 'int'},
            {name: 'quiet',     mapping: 'quiet',     type: 'int'},
            {name: 'stream',    mapping: 'stream',    type: 'int'},
            {name: 'version',   mapping: 'version',   type: 'string'},
            {name: 'totalTime', mapping: 'totalTime', type: 'string'},
            {name: 'partymode', mapping: 'partymode', type: 'int'}
        ]),
        listeners: {
            load: function(store, records, options) {
                    webmp3.refreshStatusData();
            },
            loadexception: function(o, arg, e){
                var exception = e.status+' ' +e.statusText+': ' + e.responseText;
                webmp3.fireException(this, exception);
            }

        }
    });

/****************************************
 * Hitlist
 ***************************************/
    webmp3.HitlistDataStore = new Ext.data.Store({
        id: 'HitlistDataStore',
        autoLoad: false,
        proxy: new Ext.data.HttpProxy({
                url: 'webmp3.php',
                method: 'POST'
        }),
        baseParams:{action: "getHitlist"},
        reader: new Ext.data.JsonReader({
            root: 'results',
            totalProperty: 'total'
        },[
            {name: 'nr',     mapping: 'nr',     type: 'int'},
            {name: 'file',   mapping: 'file',   type: 'string'},
            {name: 'count',  mapping: 'count',  type: 'int'}
        ]),
        listeners: {
            loadexception: function(o, arg, e){
                var exception = e.status+' ' +e.statusText+': ' + e.responseText;
                webmp3.fireException(this, exception);
            }

        }
    });

    webmp3.hitlistColModel = new Ext.grid.ColumnModel([
       {header: " ",    menuDisabled: true, dataIndex: 'nr',    width:10 },
       {header: "#",    menuDisabled: true, dataIndex: 'count', width:10 },
       {header: "File", menuDisabled: true, dataIndex: 'file'            }
    ]);

    webmp3.hitlistGrid = new Ext.grid.GridPanel({
        cm: webmp3.hitlistColModel,
        store: webmp3.HitlistDataStore,
        viewConfig: {
            forceFit: true
        },
        width: 580,
        height:550,
        bbar: [
          new Ext.PagingToolbar({
            pageSize: 20,
            autoHeight: true,
            width: '580',
            hideParent: true,
            store: webmp3.HitlistDataStore,
            displayInfo: true,
            id: 'webmp3.hitlistPagingToolbar'
          })
        ],
        listeners: {
            rowdblclick : function ( grid, rowIndex, event ) {
                                var file = webmp3.HitlistDataStore.getAt(rowIndex).get('file');
                                webmp3.PlaylistDataStore.load({
                                        url: 'webmp3.php',
                                        params: 'action=getPlaylist&add[]=' + webmp3.urlencode(file),
                                        text: 'added tack nr. ' + rowIndex
                                });
            }
        }
    });
    webmp3.hitlistWindow = new Ext.Window({
        title: "Hitlist",
        height: '550',
        width: '600',
        buttonAlign: 'center',
        layout:'fit',
        closeAction: 'hide',
        items: [ webmp3.hitlistGrid ],
        buttons: [
                  {
                    text: 'Close',
                    id: 'hitlistWindowCloseBtn'
                  }
              ]
    });
    Ext.get('hitlistBtn').on("click", function(button, event) {
      webmp3.HitlistDataStore.load();
      webmp3.hitlistWindow.show();
      webmp3.hitlistWindow.center();
    });
    Ext.ComponentMgr.get('hitlistWindowCloseBtn').on("click", function(button, event) {
      webmp3.hitlistWindow.hide();
    });

/****************************************
 * Playlist Load Window
 ***************************************/
    webmp3.playlistLoadAddRemoveBtn = function(config){
      Ext.apply(this, config);
      if(this.rowspan){
          this.renderer = this.renderer.createDelegate(this);
      }
    };
    webmp3.playlistLoadAddRemoveBtn.prototype = {
      header: "",
      width: 23,
      sortable: false,
      fixed:true,
      menuDisabled:true,
      dataIndex: '',
      id: 'remove',
      renderer : function(v, p, record, rowIndex){
          return "<img src='images/delete.png'>";
      }
    };
    webmp3.playlistLoadAddLoadBtn = function(config){
      Ext.apply(this, config);
      if(this.rowspan){
          this.renderer = this.renderer.createDelegate(this);
      }
    };
    webmp3.playlistLoadAddLoadBtn.prototype = {
      header: "",
      width: 23,
      sortable: false,
      fixed:true,
      menuDisabled:true,
      dataIndex: '',
      id: 'load',
      renderer : function(v, p, record, rowIndex){
          return "<img src='images/folder_go.png'>";
      }
    };

    webmp3.playlistsLoadDataStore = new Ext.data.Store({
        id: 'PlaylistLoadDataStore',
        autoLoad: false,
        proxy: new Ext.data.HttpProxy({
                url: 'webmp3.php',
                method: 'POST'
        }),
        baseParams:{action: "getPlaylists"},
        reader: new Ext.data.JsonReader({
            root: 'results',
            totalProperty: 'total'
        },[
            {name: 'file',   mapping: 'file',   type: 'string'},
            {name: 'info',   mapping: 'info',   type: 'string'},
            {name: 'ctime',  mapping: 'ctime',  type: 'string'}
        ]),
        listeners: {
            loadexception: function(o, arg, e){
                var exception = e.status+' ' +e.statusText+': ' + e.responseText;
                webmp3.fireException(this, exception);
            }

        }
    });

    webmp3.playlistLoadColModel = new Ext.grid.ColumnModel([
       {header: "File", menuDisabled: true, dataIndex: 'file'  },
       {header: "Info", menuDisabled: true, dataIndex: 'info'  },
       {header: "Date", menuDisabled: true, dataIndex: 'ctime' },
       new webmp3.playlistLoadAddRemoveBtn,
       new webmp3.playlistLoadAddLoadBtn
    ]);

    webmp3.playlistLoadGrid = new Ext.grid.GridPanel({
        cm: webmp3.playlistLoadColModel,
        store: webmp3.playlistsLoadDataStore,
        viewConfig: {
            forceFit: true
        },
        width: 580,
        height:550,
        loadMask: {
            store: webmp3.playlistsLoadDataStore
        },
        bbar: [
          new Ext.PagingToolbar({
            pageSize: 20,
            autoHeight: true,
            width: '580',
            hideParent: true,
            store: webmp3.playlistsLoadDataStore,
            displayInfo: true,
            id: 'webmp3.playlistLoadPagingToolbar'
          })
        ],
        listeners: {
            celldblclick : function ( grid, rowIndex, columnIndex, event ) {
                                if(columnIndex <= 2) {
                                  var file = webmp3.playlistsLoadDataStore.getAt(rowIndex).get('file');
                                  var info = webmp3.playlistsLoadDataStore.getAt(rowIndex).get('info')
                                  file = file+" - "+info+".playlist";
                                  webmp3.PlaylistDataStore.load({
                                          url: 'webmp3.php',
                                          params: 'action=getPlaylist&loadPlaylist=' + webmp3.urlencode(file),
                                          text: 'loaded file nr. ' + rowIndex
                                  });
                                  webmp3.playlistLoadWindow.hide();
                                };
                              },
            cellclick : function ( grid, rowIndex, columnIndex, event ) {
                                if(columnIndex == 4) {
                                  var file = webmp3.playlistsLoadDataStore.getAt(rowIndex).get('file');
                                  var info = webmp3.playlistsLoadDataStore.getAt(rowIndex).get('info')
                                  file = file+" - "+info+".playlist";
                                  webmp3.PlaylistDataStore.load({
                                          url: 'webmp3.php',
                                          params: 'action=getPlaylist&loadPlaylist=' + webmp3.urlencode(file),
                                          text: 'loaded file nr. ' + rowIndex
                                  });
                                  webmp3.playlistLoadWindow.hide();
                                }
                                if(columnIndex == 3) {
                                  var file = webmp3.playlistsLoadDataStore.getAt(rowIndex).get('file');
                                  Ext.Msg.confirm('delete this playlist?', 'really delete '+file+'?', function(btn, text){
                                    if (btn == 'yes'){
                                      var file = webmp3.playlistsLoadDataStore.getAt(rowIndex).get('file');
                                      var info = webmp3.playlistsLoadDataStore.getAt(rowIndex).get('info')
                                      file = file+" - "+info+".playlist";
                                      webmp3.playlistsLoadDataStore.load({
                                      //webmp3.PlaylistDataStore.load({
                                          url: 'webmp3.php',
                                          params: 'action=deletePlaylist&name=' + webmp3.urlencode(file),
                                          text: 'deleting playlist'
                                      });
                                    }
                                  });
                                }
                              }
        }
    });
    webmp3.playlistLoadWindow = new Ext.Window({
        title: "load a playlist",
        height: '600',
        width: '600',
        buttonAlign: 'center',
        layout:'fit',
        closeAction: 'hide',
        items: [ webmp3.playlistLoadGrid ],
        buttons: [
                  {
                    text: 'Close',
                    id: 'playlistLoadWindowCloseBtn'
                  }
              ]
    });
    Ext.ComponentMgr.get('playlistLoadWindowCloseBtn').on("click", function(button, event) {
      webmp3.playlistLoadWindow.hide();
    });

  webmp3.abcMap = new Ext.KeyMap("fileGridPanel", {
      key: '1234567890abcdefghijklmnopqrstuvwxyz',
      fn: webmp3.jumpToFile
  });


/****************************************
 * Initialization
 ***************************************/
    // start timer
    window.setInterval(webmp3.updateTime,999);

    // initialize tool tips
    Ext.QuickTips.init();

    // initialize current settings
    webmp3.slider.setValue('<!--php: volume -->', 1);
    webmp3.sliderInit = 0;

    // fix buttons in ie
    webmp3.fixButtonIcons();

    window.setTimeout(webmp3.refreshPlaylist,360000);

});
-->
</script>
<noscript>
  WebMP3 only works with enabled JavaScript
</noscript>
</body>
</html>
