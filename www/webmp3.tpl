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
  <link rel="stylesheet" href="webmp3.css">
  <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
  <title>WebMP3</title>
    <link rel="stylesheet" type="text/css" href="extjs/ext-all.css">
    <script type="text/javascript" src="extjs/ext-base.js"></script>
    <script type="text/javascript" src="extjs/ext-all.js"></script>
    <script type="text/javascript" src="extjs/slider.js"></script>
    <link rel="stylesheet" type="text/css" href="extjs/slider.css">
</head>
<body>
<div id="viewport"></div>

<script type="text/javascript">
<!--

Ext.onReady(function(){

/****************************************
 * Event Handler
 ***************************************/
    function onButtonToggle(item, pressed){
        var msg = Ext.get('statustext');
        msg.load({
            url: 'webmp3.php',
            params: 'action=set'+item.text+'&param=' + pressed,
            text: 'setting '+item.text+' to '+pressed
        });
        if(item.text == "Mute") {
            item.setText("Unmute");
        } else if(item.text == "Unmute") {
            item.setText("Mute");
        }
        if(item.text == "Repeat") {
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
        webmp3.now=new Date();
        diff_time = webmp3.now.getTime() - webmp3.lastChange.getTime();
        if(diff_time > 300) {
            var msg = Ext.get('statustext');
            msg.load({
                url: 'webmp3.php',
                params: 'action=setVolume&vol=' + slider.getValue(),
                text: 'setting volume...' + slider.getValue()
            });
            webmp3.lastChange = new Date();
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
                    }, '-', {
                    text: 'Play',
                    tooltip: 'Play',
                    enableToggle: true,
                    toggleHandler: onButtonToggle,
                    cls:"x-btn-text-icon",
                    icon: 'images/control_play_blue.png',
                    }, '-', {
                    text: 'Pause',
                    tooltip: 'Pause',
                    enableToggle: true,
                    toggleHandler: onButtonToggle,
                    cls:"x-btn-text-icon",
                    icon: 'images/control_pause_blue.png',
                    }, '-',{
                    text: 'Next',
                    tooltip: 'Next',
                    cls:"x-btn-text-icon",
                    icon: 'images/control_fastforward_blue.png',
                    }, '-',
                    webmp3.slider
                    ,'-',{
                    text: 'Mute',
                    tooltip: 'Mute',
                    enableToggle: true,
                    toggleHandler: onButtonToggle,
                    cls:"x-btn-text-icon",
                    icon: 'images/sound_mute.png',
                    }, '-',{
                    text: 'Quiet',
                    enableToggle: true,
                    toggleHandler: onButtonToggle,
                    tooltip: 'Quiet',
                    cls:"x-btn-text-icon",
                    icon: 'images/sound_low.png',
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
                    xtype: 'tbtext',
                    text: 'blub band'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Album:'
                }, ' ', {
                    xtype: 'tbtext',
                    text: 'Sampler'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Track:'
                }, ' ', {
                    xtype: 'tbtext',
                    text: '01'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Title:'
                }, ' ', {
                    xtype: 'tbtext',
                    text: 'blub bla blah blah'
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
                    xtype: 'panel',
                    html: '',
                    border: false,
                    id: 'statustext',
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
                    xtype: 'tbtext',
                    text: 'Item 1'
                }, '-', {
                    xtype: 'tbtext',
                    text: 'Item 1'
                }
           ]
});
/****************************************
 * Playlist Data Store
 ***************************************/
    webmp3.navigation = new Ext.Panel({
        layout:'table',
        title: 'WebMP3',
        height: 156,
        region:'north',
        margins: '0 0 0 0',
        defaults: {
            // applied to each contained panel
            bodyStyle:'padding:0px'
        },
        layoutConfig: {
            // The total column count must be specified here
            columns: 3
        },
        items: [{
            height: 120,
            width: 120,
            html: '<img src="cache.jpg">',
            rowspan: 4
        },{
            items: [webmp3.navtoolbar]
        },{
            height: 120,
            width: 120,
            html: '<img src="cache.jpg">',
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
        baseParams:{action: "getPlaylist"},
        reader: new Ext.data.JsonReader({
            root: 'results',
            totalProperty: 'total',
            id: 'id'
        },[
            {name: 'artist',  mapping: 'artist',   type: 'string'},
            {name: 'album',   mapping: 'album',    type: 'string'},
            {name: 'nr',      mapping: 'tracknum', type: 'int'},
            {name: 'title',   mapping: 'title',    type: 'string'},
            {name: 'length',  mapping: 'length',   type: 'string'}
        ])
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
    webmp3.playlistGrid = new Ext.grid.GridPanel({
            collapsible: false,
                enableDragDrop: true,
                autoExpandColumn: 'title',
                sm: webmp3.psm,
        region:'center',
        margins: '5 0 0 0',
        store: webmp3.PlaylistDataStore,
        columns: [
            {header: 'Length', sortable: true, dataIndex: 'length', width: 30 },
            {header: 'Artist', sortable: true, dataIndex: 'artist'},
            {header: 'Album',  sortable: true, dataIndex: 'album'},
            {header: 'Nr',     sortable: true, dataIndex: 'nr', width: 30 },
            {header: 'Title',  sortable: true, dataIndex: 'title'},
        ],
        viewConfig: {
            forceFit: true
        },
        title: 'Playlist',
        width: 500,
        height:400,
        draggable: true,
        id: 'playlistGrid',
        tbar: [
                  {
                    text: 'Repeat',
                    tooltip: 'Repeat',
                    cls: 'x-btn-text-icon repeat-btn',
                    enableToggle: true,
                    toggleHandler: onButtonToggle,
                    //icon: 'images/control_norepeat_blue.png',
                  }, '-', {
                    text: 'Clear',
                    tooltip: 'Clear',
                  }, '-', {
                    text: 'Sort',
                    tooltip: 'Sort',
                  }, '-',{
                    text: 'Shuffle',
                    tooltip: 'Shuffle',
                  }, '-',{
                    text: 'Playlist',
                    tooltip: 'Playlist Actions',
                  }, '-',{
                    text: 'Hitlist',
                    tooltip: 'Hitlist',
                  }, '-',{
                    text: 'add Stream',
                    tooltip: 'add Stream',
                  }
              ]
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
        },

        validationEvent:false,
        validateOnBlur:false,
        trigger1Class:'x-form-clear-trigger',
        trigger2Class:'x-form-search-trigger',
        hideTrigger1:true,
        width:180,
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
            }
        },

        onTrigger2Click : function(){
            var v = this.getRawValue();
            if(v.length < 1){
                this.onTrigger1Click();
                return;
            }
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
    webmp3.FilesystemDataStore = new Ext.data.Store({
        id: 'FilesystemDataStore',
        autoLoad: true,
        proxy: new Ext.data.HttpProxy({
                url: 'webmp3.php',
                method: 'POST'
        }),
        baseParams:{action: "getFilesystem"},
        reader: new Ext.data.JsonReader({
            root: 'results',
            totalProperty: 'total',
            id: 'id'
        },[
            {name: 'file',    mapping: 'file',    type: 'string'},
            {name: 'display', mapping: 'display', type: 'string'},
            {name: 'type',    mapping: 'type',    type: 'string'}
        ])
    });

    var fileGrid = new Ext.grid.GridPanel({
        collapsible: true,
        region:'east',
        margins: '5 0 0 0',
        enableDragDrop: true,
        autoExpandColumn: 'file',
        sm: webmp3.fsm,
        store: webmp3.FilesystemDataStore,
        columns: [
            {header: 'Files & Directories', sortable: false, dataIndex: 'display'},
            {header: 'File',    sortable: false, hidden: true, hideable: false, dataIndex: 'file', width: '90%'},
            {header: 'Type',    sortable: false, hidden: true, hideable: false,  dataIndex: 'type'}
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
                //alert("dblclick: "+data);
                webmp3.FilesystemDataStore.load({
                    url: 'webmp3.php',
                    params: 'action=getFilesystem&aktPath=' + record.get('file'),
                    text: 'loading files for '+record.get('file')
                });
            }
        },
        title: 'Filesystem',
        width: 500,
        height:400,
        tbar: [
                  {
                    text: 'Add',
                    tooltip: 'Add selected files to the playlist',
                    icon:      'images/add.png',
                    iconCls:'add',                      // reference to our css
        //            handler: displayFormWindow
                  }, '-', {
                    text: 'Search',
                    tooltip: 'Advanced Search',
        //            handler: startAdvancedSearch,
                    iconCls:'search'
                  }, '-', new webmp3.SearchField({
                                        store: webmp3.FilesystemDataStore,
                                                        params: {start: 0, limit: 15},
                                        width: 120
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
        layout:'border',
        renderTo: 'viewport',
        margins: '0 0 0 0',
        defaults: {
            bodyStyle: 'padding:3px'
        },
        items: [ 
        webmp3.navigation
        ,webmp3.playlistGrid 
        ,fileGrid
        ]
    });

/****************************************
 * Initialization
 ***************************************/
    // initialize tool tips
    Ext.QuickTips.init();

    // initialize current settings
    //webmp3.slider.setValue('<!--php: volume -->', 1);
});
-->
</script>
</body>
</html>