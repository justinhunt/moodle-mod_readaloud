define(['jquery', 'core/log','mod_readaloud/definitions','core/str','core/ajax',
        'core/templates','core/notification','mod_readaloud/recorderhelper'],
    function ($, log, def, str, Ajax,
              templates, notification, recorderhelper) {
    "use strict"; // jshint ;_;
    /*
    This file handle the reading step
     */

    log.debug('Read: initialising');

    return {
        //controls
        controls: {},
        opts: {},
        activitycontroller: null,
        passagerecorded: false,
        rec_time_start: 0,
        //class definitions
        cd: {
            wordclass: def.wordclass,

        },

        //init the module
        init: function(opts){
            this.opts = opts;
            this.activitycontroller = opts.activitycontroller;
            this.init_strings();
            this.register_controls();
            this.register_events();
        },

        init_strings: function(){
          var that =this;
          str.get_string('checking','mod_readaloud').done(function(s){that.checking=s;});
        },

        // Callback for when the reading step is complete
        on_complete: function(eventdata) {},

        //load all the controls so we do not have to do it later
        register_controls: function(){
            var controls = {
                passagecontainer: $('.mod_readaloud_readingcontainer ' + '.' + this.opts['passagecontainer']),
                modelaudioplayer: $('#' + this.opts['modelaudioplayer']),
                introbox: $('.' + 'mod_intro_box'),
                readingcontainer: $('#' + def.readingcontainer),
                hider: $('.' + this.opts['hider']),
            };
            this.controls = controls;
        },

        //attach the various event handlers we need
        register_events: function () {
            var dd = this;

            // After the recorder reports that it has (really) started, this function is called.
            var beginall = function () {
                dd.passagerecorded = true;
                if (dd.opts.stepshadow_enabled && dd.opts.letsshadow) {
                    dd.controls.modelaudioplayer[0].play();
                }
            };

            var on_speech = function (eventdata) {
                var speech = eventdata.capturedspeech;
                var speechresults = eventdata.speechresults;
            };

            // Originates from the recording:started event.
            // Contains no meaningful data.
            // See https://api.poodll.com.
            var on_recording_start = function (eventdata) {
                dd.rec_time_start = new Date().getTime();
                dd.dopassagelayout();

                // dd.controls.passagecontainer.show(1000, beginall);
                dd.controls.passagecontainer.show(500, beginall);
                dd.controls.passagecontainer[0].scrollIntoView({ behaviour: "smooth", block: "start", inline: "nearest" });

                /*
                var scrollparent = $("#page");
                var newtop = scrollparent.scrollTop() + dd.controls.passagecontainer.offset().top - scrollparent.offset().top;
                if(newtop<0) {newtop=0;}
                scrollparent.animate({scrollTop: newtop}, 500,beginall);
                */

            };

            // Originates from the recording:ended event.
            // Contains no meaningful data.
            // See https://api.poodll.com.
            var on_recording_end = function (eventdata) {
                // Its a bit hacky but the rec end event can arrive immed. somehow probably when the mic test ends.
                var now = new Date().getTime();
                if ((now - dd.rec_time_start) < 3000) {
                    return;
                }
                dd.douploadlayout();
                // If we are shadowing we should stop the audio player.
                if (dd.opts.stepshadow_enabled && dd.letsshadow) {
                    dd.controls.modelaudioplayer[0].currentTime = 0;
                    dd.controls.modelaudioplayer[0].pause();
                }
            };

            // Data sent here originates from the awaiting_processing event.
            // See https://api.poodll.com.
            var on_audio_processing = function (eventdata) {
                // At this point we know the submission has been uploaded and we know the fileURL.
                // So we send the submission.
                var now = new Date().getTime();
                var rectime = now - dd.rec_time_start;
                if (rectime > 0) {
                    rectime = Math.ceil(rectime / 1000);
                }
                dd.send_submission(eventdata.mediaurl, rectime);
                dd.on_complete(eventdata);
            };

            // Init the recorder.
            var activitydata = dd.activitycontroller.get_activity_data();
            recorderhelper.init(activitydata,
                on_recording_start,
                on_recording_end,
                on_audio_processing,
                on_speech,
            );
        },

        send_submission: function (filename, rectime) {
            var that = this;
            var shadowing = (that.opts.stepshadow_enabled && that.letsshadow) ? 1 : 0;
            Ajax.call([{
                methodname: 'mod_readaloud_submit_regular_attempt',
                args: {
                    cmid: that.opts.cmid,
                    filename: filename,// encodeURIComponent(filename),
                    rectime: rectime,
                    shadowing: shadowing
                },
                done: function (ajaxresult) {
                    var payloadobject = JSON.parse(ajaxresult);
                    if (payloadobject) {
                        switch (payloadobject.success) {
                            case true:
                                log.debug('attempted submission accepted');
                                break;
                            case false:
                            default:
                                log.debug('attempted item evaluation failure');
                                if (payloadobject.message) {
                                    log.debug('message: ' + payloadobject.message);
                                }
                        }
                    }
                },
                fail: notification.exception
            }]);
        },

        dopassagelayout: function () {
            var m = this;

            // Hide.
            m.controls.introbox.hide();
            m.controls.readingcontainer.addClass(def.containerfillscreen);
        },

        douploadlayout: function () {
            var m = this;
            m.controls.passagecontainer.addClass(m.opts.passagefinished);
            m.controls.hider.fadeIn('fast');
        },

        reset_recorder: function () {
            recorderhelper.reset();
            //this.setup_recorder();
            this.register_events();
        },
    };//end of return value
});