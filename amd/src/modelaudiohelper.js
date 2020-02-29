define(['jquery', 'core/log','mod_readaloud/definitions','mod_readaloud/recorderhelper','mod_readaloud/modelaudiokaraoke'], function ($, log, def, recorderhelper, karaoke) {
    "use strict"; // jshint ;_;
    /*
    This file helps you get Polly URLs at runtime
     */

    log.debug('Model Audio helper: initialising');

    return {
        controls: {},
        currentmode: 'modeling', //previewing //stopped
        breaks: [],
        goturl: false,

        //class definitions
        cd: {
            audioplayerclass: def.modelaudioplayerclass,
            wordclass: def.wordclass,
            spaceclass: def.spaceclass,
            endspaceclass: def.endspaceclass,
            passagecontainer: def.passagecontainer,
            breaksfield: def.modelaudiobreaksfield,
            urlfield: def.modelaudiourlfield,
            modeltranscriptbutton: def.modeltranscriptbutton,
            modeltranscript: def.modeltranscript

        },

        //init the module
        init: function(props){

            //pick up opts from html
            var theid = '#amdopts_' + props.widgetid;
            var configcontrol = $(theid).get(0);
            if (configcontrol) {
                var opts = JSON.parse(configcontrol.value);
                $(theid).remove();
            } else {
                //if there is no config we might as well give up
                log.debug('Read Aloud model audio Controller: No config found on page. Giving up.');
                return;
            }

            if(opts.breaks) {
                this.breaks=JSON.parse(opts.breaks);
            }

            //register the controls
            this.register_controls();
            //register the events
            this.register_events();
            //markup passage
            this.markup_passage();
            //load recorder
            this.init_recorder(opts);

            //init karaoke
            this.init_karaoke();

            //init model audio transcript check
            var audiourl = this.controls.audioplayer.attr('src');
            if(audiourl != null) {
                this.check_modelaudio_transcript_ready(audiourl, 5000)
            }
        },

        init_karaoke: function(){
          var karaoke_opts={audioplayerclass: this.cd.audioplayerclass}
          karaoke.init(karaoke_opts);
          karaoke.set_breaks(this.breaks);
        },

        init_recorder: function(opts){
            var that =this;
            var on_recording_start=function(eventdata){
                that.goturl=false;
            };
            var on_recording_end=function(eventdata){};
            var on_audio_processing=function(eventdata){
                if(!that.goturl) {
                    that.controls.urlfield.val(eventdata.mediaurl);
                    that.goturl = true;
                }
            };

            //init the recorder
            recorderhelper.init(opts,
                on_recording_start,
                on_recording_end,
                on_audio_processing);
        },

        //load all the controls so we do not have to do it later
        register_controls: function(){
            this.controls.audioplayer = $('#' + this.cd.audioplayerclass);
            this.controls.eachword = $('.' + this.cd.wordclass);
            this.controls.eachspace = $('.' + this.cd.spaceclass);
            this.controls.passagecontainer = $("." + this.cd.passagecontainer);
            this.controls.breaksfield = $("#" + this.cd.breaksfield);
            this.controls.urlfield = $("#" + this.cd.urlfield);
            this.controls.modeltranscript = $("#" + this.cd.modeltranscript);
            this.controls.modeltranscriptbutton = $("#" + this.cd.modeltranscriptbutton);
        },

        //attach the various event handlers we need
        register_events: function(){
            var that = this;

            var clickhandler = function () {

                if (that.currentmode === 'modeling') {
                    var wordnumber = parseInt($(this).attr('data-wordnumber'));
                    var nextspace = $('#' + that.cd.spaceclass + '_' + wordnumber);
                    if(nextspace.hasClass(that.cd.endspaceclass)){
                        that.remove_break(wordnumber);
                        nextspace.removeClass(that.cd.endspaceclass);
                    }else {
                        nextspace.addClass(that.cd.endspaceclass);
                        var theplayer = that.controls.audioplayer[0];
                        var audiotime = theplayer.currentTime;
                        that.register_break(wordnumber, audiotime);
                    }
                }
            };

            //set break points
            this.controls.eachword.click(clickhandler);
            this.controls.eachspace.click(clickhandler);

            this.controls.modeltranscriptbutton.click(function(){
                $(this).hide();
                that.controls.modeltranscript.show();
            });
        },
        remove_break: function(wordnumber)
        {
            for(var i=0; i<this.breaks.length; i++) {
                if(this.breaks[i].wordnumber==wordnumber){
                    this.breaks.splice(i,1);
                    break;
                }
            }
            this.controls.breaksfield.val(JSON.stringify(this.breaks));
            log.debug(this.breaks);
        },

        register_break: function(wordnumber, audiotime){
            this.breaks.push({'wordnumber': wordnumber, 'audiotime': audiotime});
            this.controls.breaksfield.val(JSON.stringify(this.breaks));
            log.debug(this.breaks);
        },

        markup_passage: function(){
            for(var i=0; i<this.breaks.length; i++) {
                var wordnumber =this.breaks[i].wordnumber;
                var space =$('#' + this.cd.spaceclass + '_' + wordnumber);
                space.addClass(this.cd.endspaceclass);
            }

        },

        player_get_time: function(){
            var theplayer = this.controls.audioplayer[0];
            return theplayer.currentTime;
        },

        check_modelaudio_transcript_ready: function(audiourl,waitms){
            //we commence a series of ping and retries until the recorded file is available
            var that = this;
            $.ajax({
                url: audiourl + '.txt',
                method: 'HEAD',
                cache: false,
                error: function () {
                    //We get here if its a 404 or 403. So settimout here and wait for file to arrive
                    //we increment the timeout period each time to prevent bottlenecks
                    log.debug('403 errors are normal here, till the file arrives back from transcriptoin');
                    setTimeout(function () {
                        that.check_modelaudio_transcript_ready(audiourl, waitms + 5000);
                    }, waitms);
                },
                success: function (data, textStatus, xhr) {
                    switch (xhr.status) {
                        case 200:
                            that.controls.modeltranscript.load(audiourl + '.txt');
                            that.controls.modeltranscriptbutton.show();
                            break;
                        default:
                            setTimeout(function () {
                                that.check_modelaudio_transcript_ready(audiourl, waitms + 5000);
                            }, waitms);
                    }

                }
            });
        },

        do_transcription_complete: function(){

        }

    };//end of return value
});