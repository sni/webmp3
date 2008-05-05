<!-- $Id: index.tpl 2 2008-04-24 13:41:58Z sven $ -->
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
  <link rel="stylesheet" href="images/webmp3.css">
  <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
  <title><!--php: pageTitle --></title>
    <script type="text/javascript" src="include/extjs/ext-base.js"></script>
    <script type="text/javascript" src="include/extjs/ext-all.js"></script>
    <link rel="stylesheet" type="text/css" href="include/extjs/ext-all.css">
    <link rel="stylesheet" type="text/css" href="include/extjs/slider.css">
</head>
<body>
<div id="viewport"></div>

<script type="text/javascript">
<!--

Ext.onReady(function(){
    Ext.namespace("webmp3");
    webmp3.sliderInit           = 1;
    webmp3.lastSliderUpdate     = new Date();
    webmp3.lastStatusUpdate     = new Date();
    webmp3.token                = "<!--php: token -->";
    webmp3.pause                = <!--php: pause -->;
    webmp3.stream               = <!--php: stream -->;
    webmp3.lastHighlightedToken = "";
    webmp3.lastSearch           = "";
    webmp3.taskDelay            = new Ext.util.DelayedTask();

/****************************************
 * Functions
 ***************************************/

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
      s = Ext.util.Format.htmlEncode(s);
      s = s.replace(/ /g, "+");
      return(s);
    }

    webmp3.updateTime = function()
    {
        var remMin=document.getElementById('remMin');
        var remSec=document.getElementById('remSec');
        var preMin=document.getElementById('pre');

        // refresh status every 5 minutes
        now=new Date();
        diff_time = now.getTime() - webmp3.lastStatusUpdate.getTime();
        if(diff_time > 300000) {
            webmp3.PlaylistDataStore.load();
        }

        if(webmp3.pause == true) {
            window.setTimeout(webmp3.updateTime, 999);
            return(0);
        }

        if(webmp3.stream == true) {
            // playing stream
            preMin.innerHTML = "";

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
            preMin.innerHTML = "-";

            if(remSec.innerHTML == 1 && remMin.innerHTML == "0") {
                window.setTimeout(webmp3.refreshPlaylist,1000);
                remSec.innerHTML = "";
                remMin.innerHTML = "";
                window.setTimeout(webmp3.updateTime, 999);
                return(0);
            }
            if(remSec.innerHTML == "" && remMin.innerHTML == "") {
                window.setTimeout(webmp3.updateTime, 999);
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

            if(remMin.innerHTML < 0) {
                window.setTimeout(webmp3.PlaylistDataStore.load,1000);
            }
        }
        window.setTimeout(webmp3.updateTime, 999);
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
        if(document.getElementById('filePic').src == 'webmp3.php?action=pic&pic='+Ext.util.Format.stripTags(webmp3.aktPath)+"/"+append) {
          return(1);
        }
        document.getElementById('filePic').src = 'webmp3.php?action=pic&pic='+Ext.util.Format.stripTags(webmp3.aktPath)+"/"+append;
    }

    function updatePlayPic() {
        if(document.getElementById('playPic').src == 'webmp3.php?action=pic&token='+webmp3.token) {
          return(1);
        }
        document.getElementById('playPic').src = 'webmp3.php?action=pic&token='+webmp3.token;
    }

    webmp3.refreshStatusData = function() {
        record = webmp3.StatusDataStore.getAt(0);

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
        webmp3.noTogggleEvents = 0;

        // set status text
        document.getElementById('statusbar').innerHTML = record.get('status');

        // set current track data
        document.getElementById('artistText').innerHTML = record.get('artist');
        document.getElementById('albumText').innerHTML = record.get('album');
        document.getElementById('trackText').innerHTML = record.get('nr');
        document.getElementById('titleText').innerHTML = record.get('title');

        document.getElementById('remMin').innerHTML = record.get('remMin');
        document.getElementById('remSec').innerHTML = record.get('remSec');
        document.getElementById('pre').innerHTML = record.get('pre');


        // set volume
        webmp3.sliderInit = 1;
        webmp3.slider.setValue(record.get('volume'), 1);
        webmp3.sliderInit = 0;

        // set title
        if(record.get('play')) {
            document.title = record.get('nr') + ' - ' + record.get('title');
        } else {
            document.title = 'WebMP3';
        }

        webmp3.highlightCurrentSong();
        updatePlayPic();
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
        webmp3.PlaylistDataStore.load();
    }

    webmp3.addSelectedToPlaylist = function() {
        var selects = webmp3.fsm.getSelections();
        var files = "";
        for(i=0;i<selects.length;i++)
        {
            files = files + "&add[]=" + selects[i].get('file');
        }
        webmp3.PlaylistDataStore.load({
            url: 'webmp3.php',
            params: 'action=getPlaylist&aktPath=' + Ext.util.Format.stripTags(document.getElementById('filestatus').innerHTML) + files,
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
            item.setText("Unmute");
        } else if(item.text == "Unmute") {
            item.setText("Mute");
        }
        if(item.text == "Play") {
            item.setText("Stop");
        } else if(item.text == "Stop") {
            item.setText("Play");
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

    webmp3.slider.on("change", function(slider, value) {
        if(webmp3.sliderInit == 0) {
            webmp3.now=new Date();
            diff_time = webmp3.now.getTime() - webmp3.lastSliderUpdate.getTime();
            if(diff_time > 300) {
                var msg = Ext.get('statustext');
                msg.load({
                    url: 'webmp3.php',
                    params: 'action=setVolume&vol=' + slider.getValue(),
                    text: 'setting volume...' + slider.getValue()
                });
                webmp3.lastSliderUpdate = new Date();
            }
        }
    });
    webmp3.slider.on("dragend", function(slider, value) {
        if(webmp3.sliderInit == 0) {
            var msg = Ext.get('statustext');
            msg.load({
                url: 'webmp3.php',
                params: 'action=setVolume&vol=' + slider.getValue(),
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
                    cls:"x-btn-text-icon play-btn",
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
                    id: 'muteBtn',
                    pressed: <!--php: mute -->
                    }, '-',{
                    text: 'Quiet',
                    enableToggle: true,
                    toggleHandler: onButtonToggle,
                    tooltip: 'Quiet',
                    cls:"x-btn-text-icon",
                    icon: 'images/sound_low.png',
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
                    id: 'artistText'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Album:'
                }, ' ', {
                    xtype: 'label',
                    html: '<!--php: album -->',
                    id: 'albumText'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Track:'
                }, ' ', {
                    xtype: 'label',
                    text: '<!--php: track -->',
                    id: 'trackText'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Title:'
                }, ' ', {
                    xtype: 'label',
                    html: '<!--php: title -->',
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
                    text: 'Info',
                    tooltip: 'Wikipedia information for this artist',
                    cls: 'x-btn-text-icon',
                    id: 'infoBtn',
                    icon: 'images/information.png'
                }, '-', {
                    xtype: 'panel',
                    html: '',
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
            html: '<img id="playPic" src="webmp3.php?action=pic&token='+webmp3.token+'">',
            rowspan: 4
        },{
            items: [webmp3.navtoolbar]
        },{
            height: 120,
            width: 120,
            html: '<img id="filePic" src="webmp3.php?action=pic&pic='+webmp3.aktPath+'">',
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
            totalProperty: 'total',
            id: 'id'
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
                  },
            loadexception: function(o, arg, e){
                alert('** - PlaylistDataStore fired (loadexception) '+e.status+' ' +e.statusText+': ' + e.responseText);
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
      pre=((nr.length==1)?"0":"");
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
            // store them to take a look via firebug
            webmp3.data = data;
            webmp3.dd   = dd;
            webmp3.e    = e;
            var selects = data.selections;
            var files   = "";
            if(data.grid.title == "Playlist") {
              // drag&drop in our playlist
              var ds = webmp3.PlaylistDataStore;
              var rows=webmp3.psm.getSelections();
              var cindex=dd.getDragData(e).rowIndex;
              if (typeof cindex == "undefined") {
                // move to the end of the list
                cindex = ds.getCount() - 1;
              }
              for(i = rows.length-1; i >= 0; i--) {
                  rowData=ds.getById(rows[i].id);
                  ds.remove(ds.getById(rows[i].id));
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
                    params: 'action=getPlaylist&aktPath=' + webmp3.urlencode(Ext.util.Format.stripTags(document.getElementById('filestatus').innerHTML)) + files,
                    text: 'added files to playlist'
                });
              }
              webmp3.fsm.clearSelections();
            }
        },
        onRender: function() {
            webmp3.DropGridPanel.superclass.onRender.apply(this, arguments);
            try {
                webmp3.dropZone = new Ext.dd.DropTarget(this.id, {
                    ddGroup : 'playlistDD',
                    notifyDrop : this.droppedItem
//                    notifyOver : this.notifyOver,
//                    notifyOut  : this.notifyOut
                });
            } catch (e) {
              alert('** - onRender fired exception '+e.status+' ' +e.statusText+': ' + e.responseText);
            }
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
                    cls: 'x-btn-text-icon repeat-btn',
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
                    text: 'Playlist',
                    tooltip: 'Playlist Actions'
                  }, '-',{
                    text: 'Hitlist',
                    tooltip: 'Hitlist'
                  }, '-',{
                    text: 'add Stream',
                    tooltip: 'add Stream',
                    id: 'addStream'
                  }, '-', ' ',{
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
            this.on('keyup', function ( textField, e ) {
              if(textField.getValue().length == 0 || (textField.getValue().length >= 2 && webmp3.lastSearch != textField.getValue())) {
                webmp3.lastSearch = textField.getValue();
                this.onTrigger2Click();
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
                this.el.dom.value = '';
                var o = {start: 0, limit: 15};
                this.store.baseParams = this.store.baseParams || {};
                this.store.baseParams[this.paramName] = '';
                this.store.reload({params:o});
                this.triggers[0].hide();
                this.hasSearch = false;
                webmp3.fileGrid.getBottomToolbar().hide();
                webmp3.fileGrid.syncSize();
                webmp3.border.doLayout();
            }
        },

        onTrigger2Click : function(){
            document.getElementById('filestatus').innerHTML = "";
            webmp3.aktPath = "";
            var v = this.getRawValue();
            if(v.length < 1){
                this.onTrigger1Click();
                return;
            }
            webmp3.fileGrid.getBottomToolbar().show();
            webmp3.fileGrid.syncSize();
            webmp3.border.doLayout();
            var o = {start: 0, limit: 15};
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
            totalProperty: 'total',
            id: 'id'
        },[
            {name: 'file',    mapping: 'file',    type: 'string'},
            {name: 'type',    mapping: 'type',    type: 'string'},
            {name: 'icon',    mapping: 'icon',    type: 'string'}
        ]),
        listeners: {
            loadexception: function(o, arg, e){
                alert('** - FilesystemDataStore fired (loadexception) '+e.status+' ' +e.statusText+': ' + e.responseText);
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
        store: webmp3.FilesystemDataStore,
        columns: [
            {header: ' ', dataIndex: 'icon', renderer: webmp3.iconRenderer, width: 5, menuDisabled: true  },
            {header: 'Files & Directories', dataIndex: 'file', width: 125, menuDisabled: true },
            {header: 'Type', sortable: false, hidden: true, hideable: false,  dataIndex: 'type'}
        ],
        viewConfig: {
            forceFit: true
        },
        listeners: {
            celldblclick: function(grid, rowIndex, columnIndex, e) {
                var record = grid.getStore().getAt(rowIndex);  // Get the Record
                var fieldName = grid.getColumnModel().getDataIndex(columnIndex); // Get field name
                //var data = record.get(fieldName);
                var data = record.get('file');
                var filestatus = Ext.get('filestatus');
                webmp3.aktPath = Ext.util.Format.stripTags(document.getElementById('filestatus').innerHTML);
                updateFilePic(record.get('file'));
                filestatus.load({
                    url: 'webmp3.php',
                    params: 'action=getPath&aktPath=' + webmp3.aktPath + '&append=' + record.get('file'),
                    text: 'loading files for '+record.get('file')
                });
                if(record.get('type') == "D") {
                    webmp3.FilesystemDataStore.load({
                        url: 'webmp3.php',
                        params: 'action=getFilesystem&aktPath=' + webmp3.aktPath + '&append=' + record.get('file'),
                        text: 'loading files for '+record.get('file')
                    });
                } else {
                    webmp3.PlaylistDataStore.load({
                        url: 'webmp3.php',
                        params: 'action=getPlaylist&aktPath=' + webmp3.aktPath + "&add[]="+record.get('file'),
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
                                                        params: {start: 0, limit: 15},
                                        width: 80
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
            pageSize: 20,
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
 * Playlist Button EventHandler
 ***************************************/
    Ext.get('clearBtn').on("click", function(button, event) {
        webmp3.PlaylistDataStore.load({
            url: 'webmp3.php',
            params: 'action=getPlaylist&clear=1',
            text: 'cleared playlist'
        });
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

    Ext.get('infoBtn').on("click", function(button, event) {
        webmp3.PlaylistDataStore.load();
        //webmp3.refreshStatusStore();
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
    Ext.get('playPic').on("click", function(button, event) {
      webmp3.pictureWindow = new Ext.Window({
          title: 'Folder Image',
          height: '100',
          width: '100',
          resizable: false,
          bodyStyle:'padding:0px',
          border: true,
          closable: true,
          buttonAlign: 'center',
              items: [{
                  xtype: 'panel',
                  height: 'auto',
                  width: 'auto',
                  html: '<img id="folderPicHuge" src="webmp3.php?action=pic&full=yes&token='+webmp3.token+'">'
              }],
          buttons: [
                    {
                      text: 'Close',
                      id: 'picWindowCloseBtn'
                    }
                ]
      });
      webmp3.pictureWindow.show();
      Ext.get('picWindowCloseBtn').on("click", function(button, event) {
        webmp3.pictureWindow.destroy();
      });
    });
    Ext.get('playPic').on("click", function(button, event) {
      webmp3.pictureWindow.show();
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
            totalProperty: 'total',
            id: 'id'
        },[
            {name: 'artist',  mapping: 'artist',   type: 'string'},
            {name: 'album',   mapping: 'album',    type: 'string'},
            {name: 'nr',      mapping: 'nr',       type: 'string'},
            {name: 'title',   mapping: 'title',    type: 'string'},
            {name: 'length',  mapping: 'length',   type: 'string'},
            {name: 'token',   mapping: 'token',    type: 'string'},
            {name: 'volume',  mapping: 'volume',   type: 'int'},
            {name: 'status',  mapping: 'status',   type: 'string'},
            {name: 'remMin',  mapping: 'remMin',   type: 'string'},
            {name: 'remSec',  mapping: 'remSec',   type: 'string'},
            {name: 'pre',     mapping: 'pre',      type: 'string'},
            {name: 'play',    mapping: 'play',     type: 'int'},
            {name: 'pause',   mapping: 'pause',    type: 'int'},
            {name: 'repeat',  mapping: 'repeat',   type: 'int'},
            {name: 'mute',    mapping: 'mute',     type: 'int'},
            {name: 'quiet',   mapping: 'quiet',    type: 'int'},
            {name: 'stream',  mapping: 'stream',   type: 'int'}
        ]),
        listeners: {
            load: function(store, records, options) {
                    webmp3.refreshStatusData();
            },
            loadexception: function(o, arg, e){
                alert('** - StatusDataStore fired (loadexception) '+e.status+' ' +e.statusText+': ' + e.responseText);
            }

        }
    });

/****************************************
 * Initialization
 ***************************************/
    // start timer
    webmp3.updateTime();

    // initialize tool tips
    Ext.QuickTips.init();

    // initialize current settings
    webmp3.slider.setValue('<!--php: volume -->', 1);
    webmp3.sliderInit = 0;

    window.setTimeout(webmp3.refreshPlaylist,360000);
});
-->
</script>
</body>
</html>
