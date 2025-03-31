define(['jquery', 'core/log','mod_readaloud/definitions', 'mod_readaloud/pollyhelper'], function ($, log, def,pollyhelper) {
    "use strict"; // jshint ;_;
    /*
    This file helps you get Polly URLs at runtime
     */

    log.debug('Click to hear: initialising');

    return {
        //controls
        controls: {},
        ttsvoice: 'Amy',

        //class definitions
        cd: {
            hiddenplayerclass: def.hiddenplayer,
            wordclass: def.wordclass,
            passagecontainer: def.passagecontainer,
            fullreportcontainer: def.fullreportcontainer,
        },

        //init the module
        init: function(opts){
            pollyhelper.init(opts.token,opts.region,opts.owner);
            this.ttsvoice=opts.ttsvoice;
            this.register_controls();
            this.register_events();
        },

        //load all the controls so we do not have to do it later
        register_controls: function(){
            this.controls.audioplayer = $('#' + this.cd.hiddenplayerclass);
            this.controls.component = $('#' + def.component);
            this.controls.fullreportcontainer = $("." + this.cd.fullreportcontainer);
        },

        //attach the various event handlers we need
        register_events: function() {
            var that = this;

            // Get the audio element
            var aplayer = this.controls.audioplayer;
            pollyhelper.onnewpollyurl=function(theurl){
                aplayer.attr('src',theurl);
                aplayer[0].play();
            };

            //register click listener
            //this is a delegated event handler because the elements are not there when we start
            this.controls.component.on('click', "." + this.cd.fullreportcontainer +' .' + this.cd.wordclass, function () {
                var wordnumber = parseInt($(this).attr('data-wordnumber'));
                var word = $('#' + that.cd.wordclass + '_' + wordnumber);
                var text = word.text();
                pollyhelper.request_polly_url(text,"text",that.ttsvoice);
            });
        }//end of register events

    };//end of return value
});