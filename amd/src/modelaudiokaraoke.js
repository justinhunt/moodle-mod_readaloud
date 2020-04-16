define(['jquery', 'core/log','mod_readaloud/definitions'], function ($, log, def) {
    "use strict"; // jshint ;_;
    /*
    This file helps you get Polly URLs at runtime
     */

    log.debug('Model Audio helper: initialising');

    return {
        controls: {},
        breaks: [],
        endwordnumber: 0,
        currentstartbreak: false,

        //class definitions
        cd: {
            audioplayerclass: def.audioplayerclass,
            wordplayerclass: def.wordplayerclass,
            wordclass: def.wordclass,
            spaceclass: def.spaceclass,
            endspaceclass: def.endspaceclass,
            passagecontainer: def.passagecontainer,
            activesentence: def.activesentence,
            stopbutton: 'mod_readaloud_button_stop',
            playbutton: 'mod_readaloud_button_play'
        },

        //init the module
        init: function(opts){
            if(opts.breaks) {
                this.breaks=JSON.parse(opts.breaks);
            }
            if(opts.audioplayerclass) {
                this.cd.audioplayerclass=opts.audioplayerclass;
            }

            //register the controls
            this.register_controls();

            //register the end word number
            this.endwordnumber = this.controls.eachword.length;

            //register the events
            this.register_events();
        },

        set_breaks: function(breaks){
            this.breaks=breaks;
            this.sort_breaks();
        },

        sort_breaks: function(){
            this.breaks.sort(function(a, b){return a.audiotime - b.audiotime});
        },

        //load all the controls so we do not have to do it later
        register_controls: function(){
            this.controls.audioplayer = $('#' + this.cd.audioplayerclass);
            this.controls.eachword = $('.' + this.cd.wordclass);
            this.controls.eachspace = $('.' + this.cd.spaceclass);
            this.controls.eachwordorspace = $('.' + this.cd.spaceclass + ',.' + this.cd.wordclass);
            this.controls.passagecontainer = $("." + this.cd.passagecontainer);
            this.controls.stopbutton = $('#' + this.cd.stopbutton);
            this.controls.playbutton = $('#' + this.cd.playbutton);
        },

        //attach the various event handlers we need
        register_events: function() {
            var that = this;

            // Get the audio element
            var aplayer = this.controls.audioplayer[0];
          
            this.controls.playbutton.on('click',function(){
              aplayer.play();
            });
          
            this.controls.stopbutton.on('click',function(){
              aplayer.pause();
            });

            this.controls.eachwordorspace.on('click',function(){
                var wordnumber = parseInt($(this).attr('data-wordnumber'));
                var nearest_start_break=false;
                for (var i = 0; i < that.breaks.length; i++) {
                    if(that.breaks[i].wordnumber < wordnumber) {
                        nearest_start_break = that.breaks[i];
                    }else{
                        //exit the loop;
                        break;
                    }
                }
                if(!nearest_start_break){
                    //start from beginning OR do nothing
                }else{
                    aplayer.pause();
                    aplayer.currentTime=nearest_start_break.audiotime;
                    aplayer.play();
                }
            });

            //Player events (onended, onpause, ontimeupdate)
            var ended = function(){
                that.controls.eachword.removeClass(that.cd.activesentence);
                that.controls.eachspace.removeClass(that.cd.activesentence);
                that.currentstartbreak = false;
            };

            aplayer.onended= ended;
            aplayer.onpause= ended;

            aplayer.ontimeupdate = function () {
                var currentTime = aplayer.currentTime;
                var startbreak = false;
                var nextbreak = false;
                for (var i = 0; i < that.breaks.length; i++) {
                    //if this is the last marked break (ie flow till end)
                    if (currentTime >= that.breaks[i].audiotime && i + 1 === that.breaks.length) {
                        startbreak = that.breaks[i];
                        nextbreak = {wordnumber: that.endwordnumber + 1, audiotime: 0};
                        //if its just between two breaks (yay)
                    } else if (currentTime >= that.breaks[i].audiotime && currentTime < that.breaks[i + 1].audiotime) {
                        startbreak = that.breaks[i];
                        nextbreak = that.breaks[i + 1];
                        break;
                     //this is the first section
                    } else if(i===0 && currentTime < that.breaks[i].audiotime && currentTime > 0){
                        startbreak = {wordnumber: 0, audiotime: 0};
                        nextbreak = that.breaks[i];

                    }
                }
                //nothing changed since last time
                if (that.currentstartbreak == startbreak) {
                    //do nothing
                    //oooh, new current break!!
                } else {
                    that.currentstartbreak = startbreak;
                    that.controls.eachword.removeClass(that.cd.activesentence);
                    that.controls.eachspace.removeClass(that.cd.activesentence);
                    if (startbreak !== false && nextbreak !== false) {
                        for (var thewordnumber = startbreak.wordnumber+1; thewordnumber <= nextbreak.wordnumber; thewordnumber++) {
                            $('#' + that.cd.spaceclass + '_' + thewordnumber).addClass((that.cd.activesentence));
                            $('#' + that.cd.wordclass + '_' + thewordnumber).addClass((that.cd.activesentence));
                        }
                    }
                }
            };
        }//end of register events

    };//end of return value
});