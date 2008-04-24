/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

Ext.onReady(function(){
Ext.namespace("webmp3");
webmp3.slider = new Ext.Slider({
        renderTo: 'volume-slider',
        width: 214,
        value: 0,
        minValue: 0,
        maxValue: 100,
        keyIncrement: 5,
        plugins: new Ext.ux.SliderTip()
    });
});


/**
 * @class Ext.ux.SliderTip
 * @extends Ext.Tip
 * Simple plugin for using an Ext.Tip with a slider to show the slider value
 */
Ext.ux.SliderTip = Ext.extend(Ext.Tip, {
    minWidth: 10,
    offsets : [0, -10],
    init : function(slider){
        slider.on('dragstart', this.onSlide, this);
        slider.on('drag', this.onSlide, this);
        slider.on('dragend', this.hide, this);
        slider.on('destroy', this.destroy, this);
        slider.on('dragend', this.onDragEnd, this);
        slider.on('change', this.onChange, this);
        this.lastChange = new Date();
    },

    onSlide : function(slider){
        this.show();
        this.body.update(this.getText(slider));
        this.doAutoWidth();
        this.el.alignTo(slider.thumb, 'b-t?', this.offsets);
    },

    onChange : function(slider){
        this.now=new Date();
        diff_time = this.now.getTime() - this.lastChange.getTime();
        if(diff_time > 500) {
          var msg = Ext.get('status');
          msg.load({
              url: 'index.php',
              params: 'action=setVolume&vol=' + slider.getValue(),
              text: 'setting volume...' + slider.getValue()
          });
          this.lastChange = new Date();
        }
    },

    onDragEnd : function(slider){
        var msg = Ext.get('status');
        msg.load({
            url: 'index.php',
            params: 'action=setVolume&vol=' + slider.getValue(),
            text: 'setting volume...' + slider.getValue()
        });
    },

    getText : function(slider){
        return slider.getValue();
    }
});
