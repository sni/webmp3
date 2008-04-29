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
  <title>WebMP3</title>
    <link rel="stylesheet" type="text/css" href="extjs/ext-all.css">
    <script type="text/javascript" src="extjs/ext-base.js"></script>
    <script type="text/javascript" src="extjs/ext-all.js"></script>
    <script type="text/javascript" src="extjs/slider.js"></script>
    <link rel="stylesheet" type="text/css" href="extjs/slider.css">
    <link rel="stylesheet" type="text/css" href="extjs/layout-browser.css">
</head>
<body id="docs">
<!--  <div id="loading-mask" style=""></div>
  <div id="loading">
    <div class="loading-indicator"><img src="extjs/waiting.gif" width=48 height=48 style="margin-right:8px;" alt="Loading..."/>Loading...</div>
  </div>
-->
<div id="border-layout"></div>
<div id="status"></div>
<div id="filesystem"></div>

<script type="text/javascript">
<!--

Ext.onReady(function(){


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
          var msg = Ext.get('status');
          msg.load({
              url: 'webmp3.php',
              params: 'action=setVolume&vol=' + slider.getValue(),
              text: 'setting volume...' + slider.getValue()
          });
          webmp3.lastChange = new Date();
        }
  });


/****************************************
 * Navigation
 ***************************************/

webmp3.navigation = new Ext.Toolbar({
    heightAuto: true,
	region:'north',
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
                  cls:"x-btn-text-icon",
                  icon: 'images/control_play_blue.png',
                }, '-', {
                  text: 'Pause',
                  tooltip: 'Pause',
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
                  cls:"x-btn-text-icon",
                  icon: 'images/sound_mute.png',
                }, '-',{
                  text: 'Quiet',
                  tooltip: 'Quiet',
                  cls:"x-btn-text-icon",
                  icon: 'images/sound_low.png',
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
 * Playlist
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
        bbar: [
                  {
                    text: 'Repeat is Off',
                    tooltip: 'Repeat',
                    cls:"x-btn-text-icon",
                    icon: 'images/control_repeat_blue.png',
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
		title: 'Filesystem',
		width: 500,
        height:400,
        bbar: [
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


// initialise layout
webmp3.border = new Ext.Panel({
    title: 'WebMP3',
    layout:'border',
    renderTo: 'border-layout',
    height: 600,
    defaults: {
        bodyStyle: 'padding:15px'
    },
    items: [ 
     webmp3.navigation
    ,webmp3.playlistGrid 
    ,fileGrid
    ]
});
// initialise current settings
//webmp3.slider.setValue('<!--php: volume -->', 1);

});
-->
</script>
</body>
</html>
