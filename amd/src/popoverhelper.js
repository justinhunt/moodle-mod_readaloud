define(['jquery','core/log','mod_readaloud/loader','theme_boost/popover'], function($,log) {
    "use strict"; // jshint ;_;

/*
This file is largely to handle recorder specific tasks, configuring it , loading it, its appearance
It should not be concerned with anything non recorder'ish like elements on the page around
Relationships between the recorder and the surrounding elements should be managed via event handlers in activity controller
 */

    log.debug('Readaloud Popover helper: initialising');

    return{

        lastitem: false,
        okbuttonclass: 'mod_readaloud_quickgrade_ok',
        ngbuttonclass: 'mod_readaloud_quickgrade_ng',
        dispose: false, //Bv4 = dispose ??? Bv3 = destroy ??

        init: function(){
            this.register_events();
        },

        register_events: function() {
            $(document).on('click','.' + this.okbuttonclass,this.onAccept);
            $(document).on('click','.' + this.ngbuttonclass,this.onReject);
        },

        disposeWord: function(){
            if(this.dispose){return this.dispose;}
            if($.fn.popover.Constructor.hasOwnProperty('VERSION')){
                var version = $.fn.popover.Constructor.VERSION.charAt(0);
            }else{
                var version ='3';
            }
            switch(version){
                case '4':
                    this.dispose='dispose';
                    break;
                case '3':
                default:
                    this.dispose='destroy';
                    break;
            }
            return this.dispose;
        },

        remove: function(item){
          if(item) {
              $(item).popover(this.disposeWord());
          }else if(this.lastitem) {
              $(this.lastitem).popover(this.disposeWord());
              this.lastitem=false;
          }
        },

        addQuickGrader: function(item){

            //dispose of previous popover, and remember this one
            if(this.lastitem && this.lastitem !== item) {
                $(this.lastitem).popover(this.disposeWord());
                this.lastitem=false;
            }
            this.lastitem = item;
            var that = this;

            var thefunc = function(){
                var wordnumber = $(this).attr("data-wordnumber");
                var oklabel = M.util.get_string('ok','mod_readaloud');
                var nglabel = M.util.get_string('ng','mod_readaloud');
                var okbutton = "<button type='button' class='btn " + that.okbuttonclass + "' data-wordnumber='" + wordnumber + "'><i class='fa fa-check'></i> " + oklabel + "</button>";
                var ngbutton = "<button type='button' class='btn " + that.ngbuttonclass + "' data-wordnumber='" + wordnumber + "'><i class='fa fa-close'></i> " + nglabel + "</button>";
                var container = "<div class='mod_readaloud_quickgrade_cont'>" + okbutton + ngbutton + "</div>";
                return container;
            };

            //lets add the popover
            $(item).popover({
                title: M.util.get_string('quickgrade','mod_readaloud'),
                content: thefunc,
                trigger: 'manual',
                placement: 'top',
                html: true
            });
            $(item).popover('show');
        },

        addTranscript: function(item,transcript){

            //if we are already showing this item then dispose of it, set last item to null and go home
            if(this.lastitem == item) {
                $(this.lastitem).popover(this.disposeWord());
                this.lastitem = false;
                return;
            }

            //dispose of previous popover, and remember this one
            if(this.lastitem) {
                $(this.lastitem).popover(this.disposeWord());
                this.lastitem=false;
            }
            this.lastitem = item;

            //lets add the popover
            $(item).popover({
                title: M.util.get_string('transcript','mod_readaloud'),
                content: transcript,
                trigger: 'manual',
                placement: 'top'
            });
            $(item).popover('show');
        },

        onAccept: function(){alert($(this).attr('data-wordnumber'))},
        onReject: function(){alert($(this).attr('data-wordnumber'))},

    };//end of return value
});