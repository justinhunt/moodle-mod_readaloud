/* jshint ignore:start */
define(['jquery', 'core/log', "core/str",'mod_readaloud/definitions',
        'mod_readaloud/recorderhelper', 'mod_readaloud/modelaudiokaraoke',
        'core/ajax','core/notification','mod_readaloud/smallreporthelper',
        'mod_readaloud/listenandrepeat','mod_readaloud/quizhelper','mod_readaloud/clicktohear'
    ],
    function ($, log, str,def, recorderhelper, modelaudiokaraoke,
              Ajax, notification, smallreporthelper, landr, quizhelper, clicktohear) {

    "use strict"; // jshint ;_;

    log.debug('Activity controller: initialising');

     // Disable click event.
     function disableClick(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
    }

    return {

        cmid: null,
        activitydata: null,
        holderid: null,
        recorderid: null,
        playerid: null,
        sorryboxid: null,
        controls: null,
        ra_recorder: null,
        rec_time_start: 0,
        steps_enabled: {},
        steps_open: {},
        steps_complete: {},
        letsshadow: false,
        strings: {},


        //CSS in this file
        passagefinished: def.passagefinished,

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
        init: function (props) {
            var dd = this.clone();
/*
            log.debug('Steps enabled:', props.stepsenabled);
            log.debug('Steps open:', props.stepsopen);
            log.debug('Complete:', props.stepscomplete);
            log.debug('Steps enabled:', props.stepsenabled);
            log.debug('props:');
            log.debug(props);
            log.debug(props,'props:');
            */

            //pick up opts from html
            var theid = '#amdopts_' + props.widgetid;
            var configcontrol = $(theid).get(0);
            if (configcontrol) {
                dd.activitydata = JSON.parse(configcontrol.value);
                $(theid).remove();
            } else {
                //if there is no config we might as well give up
                log.debug('Read Aloud Test Controller: No config found on page. Giving up.');
                return;
            }

            dd.cmid = props.cmid;
            dd.holderid = props.widgetid + '_holder';
            dd.recorderid = props.widgetid + '_recorder';
            dd.playerid = props.widgetid + '_player';
            dd.sorryboxid = props.widgetid + '_sorrybox';

            //if the browser doesn't support html5 recording.
            //then warn and do not go any further
            if (!dd.is_browser_ok()) {
                $('#' + dd.sorryboxid).show();
                return;
            }

            // Set up steps - enabled and open
            //TO DO make these usable in JS (here) and not just in mustache
            dd.steps_enabled = dd.activitydata.stepsenabled; // this is not passed through to JS (only mustache)
            dd.steps_open = dd.activitydata.stepsopen;// this is not passed through to JS (only mustache)
            dd.steps_complete = dd.activitydata.stepscomplete;// this is not passed through to JS (only mustache)
            
            // Set up model audio.
            dd.setupmodelaudio();

            // Set up listen and repeat.
            dd.setuplandr();

            // Init recorder and html and events.
            dd.setup_recorder();
            dd.process_html(dd.activitydata);

            dd.register_events();
            dd.setup_strings();

            // Set up quiz.
            dd.setupquiz();

            //Set up click to hear
            dd.setupclicktohear();

            //Set up small report helper
            smallreporthelper.init(dd.activitydata);

            // Set initial mode.
            // We used to check the settings but now we just show the non-options greyed out
            if(dd.stepshadow_enabled || dd.steplisten_enabled || true){
                dd.domenulayout();
            } else {
                dd.doreadinglayout();
            }
        },

        setup_strings: function(){
            var dd = this;
            // Set up strings
            str.get_strings([
                { "key": "confirm_cancel_recording", "component": def.component},
                { "key": "confirm_read_again", "component": def.component},
                //more strings here
            ]).done(function (s) {
                var i = 0;
                dd.strings.confirm_cancel_recording = s[i++];
                dd.strings.confirm_read_again = s[i++];
                //more strings here
            });
        },

        setupmodelaudio: function(){
            var karaoke_opts={breaks:this.activitydata.breaks, audioplayerclass:this.activitydata.audioplayerclass };
            modelaudiokaraoke.init(karaoke_opts);
        },

        setuplandr: function(){
            var dd = this;
            var landr_opts={modelaudiokaraoke: modelaudiokaraoke, cmid: this.cmid, language: this.activitydata.language,
                region: this.activitydata.region, phonetics: this.activitydata.phonetics, stt_guided: this.activitydata.stt_guided};
            landr.init(landr_opts);
            //set the callback function to complete the activity
            landr.on_complete = function(){
                // Complete the current step (update server and ui)
                dd.update_activity_step(dd.activitydata.steps.step_practice);
            }
        },

        setupclicktohear: function(){
            var dd = this;
            var clicktohear_opts={token: dd.activitydata.token, ttsvoice: dd.activitydata.ttsvoice,
                region: dd.activitydata.region, owner: dd.activitydata.owner};
            clicktohear.init(clicktohear_opts);
        },

        setupquiz: function(){
            var dd = this;
            //hack TO DO - get the real attempt id
            dd.attemptid = 1;

            quizhelper.init(dd.activitydata,
                dd.cmid,
                dd.attemptid);

            //set the callback function to complete the quiz
            quizhelper.on_complete = function(){
                // Complete the current step (update server and ui)
                dd.update_activity_step(dd.activitydata.steps.step_quiz);
            }
        },

        process_html: function (opts) {
            //these css classes/ids are all passed in from php in
            //renderer.php::fetch_activity_amd should maybe just simplify and declare them in definitions.js
            var controls = {
                hider: $('.' + opts['hider']),
                introbox: $('.' + 'mod_intro_box'),
                progresscontainer: $('.' + opts['progresscontainer']),
                feedbackcontainer: $('.' + opts['feedbackcontainer']),
                errorcontainer: $('.' + opts['errorcontainer']),
                passagecontainer: $('.mod_readaloud_readingcontainer ' +  '.' + opts['passagecontainer']),
                reviewpassagecontainer: $('.mod_readaloud_studentreportpassage ' +  '.' + opts['passagecontainer']),
                recordingcontainer: $('.' + opts['recordingcontainer']),
                dummyrecorder: $('.' + opts['dummyrecorder']),
                recordercontainer: $('.' + opts['recordercontainer']),
                menubuttonscontainer: $('.' + opts['menubuttonscontainer']),
                menuinstructionscontainer: $('.' + opts['menuinstructionscontainer']),
                previewinstructionscontainer: $('.' + opts['previewinstructionscontainer']),
                landrinstructionscontainer: $('.' + opts['landrinstructionscontainer']),
                activityinstructionscontainer: $('.' + opts['activityinstructionscontainer']),
                recinstructionscontainerright: $('.' + opts['recinstructionscontainerright']),
                recinstructionscontainerleft: $('.' + opts['recinstructionscontainerleft']),
                allowearlyexit: $('.' + opts['allowearlyexit']),
                wheretonextcontainer: $('.' + opts['wheretonextcontainer']),
                modelaudioplayer: $('#' + opts['modelaudioplayer']),
                homebutton: $('#' + opts['homebutton']),
                startlistenbutton: $('#' + opts['menubuttonscontainer'] + ' .mode-chooser[data-step="' + opts.steps.step_listen + '"]'),
                startpracticebutton: $('#' + opts['menubuttonscontainer'] + ' .mode-chooser[data-step="' + opts.steps.step_practice + '"]'),
                startreadbutton: $('#' + opts['menubuttonscontainer'] + ' .mode-chooser[data-step="' + opts.steps.step_read + '"]'),
                startshadowbutton: $('#' + opts['menubuttonscontainer'] + ' .mode-chooser[data-step="' + opts.steps.step_shadow + '"]'),
                startquizbutton: $('#' + opts['menubuttonscontainer'] + ' .mode-chooser[data-step="' + opts.steps.step_quiz + '"]'),
                readagainbutton: $('#' + opts['readagainbutton']),
                startreportbutton: $('#' + opts['startreportbutton']),
                returnmenubutton: $('#' + opts['returnmenubutton']),
                stopandplay: $('#' + opts['stopandplay']),
                smallreportcontainer: $('#' + opts['smallreportcontainer']),
                fullreportcontainer: $('#' + opts['fullreportcontainer']),
                readingcontainer: $('#' + def.readingcontainer),
                modeimagecontainer: $('#' + opts['modeimagecontainer']),
                modejourneycontainer: $('#' + opts['modejourneycontainer']),
                quizcontainer: $('.' +  opts['quizcontainer']),
                quizplaceholder: $('.' +  opts['quizplaceholder']),
                quizresultscontainer: $("." + opts['quizresultscontainer']),
                homecontainer: $('.' +  opts['homecontainer']),
            };
            this.controls = controls;
        },

        is_browser_ok: function () {
            return (navigator && navigator.mediaDevices
                && navigator.mediaDevices.getUserMedia);
        },

        reset_recorder: function () {
            recorderhelper.reset();
            this.setup_recorder();
        },

        setup_recorder: function () {
            var dd = this;

            //after the recorder reports that it has (really) started this functuon is called.
            var beginall= function(){
                dd.passagerecorded = true;
                if(dd.stepshadow_enabled && dd.letsshadow){
                    dd.controls.modelaudioplayer[0].play();
                }
            };

            var on_speech = function (eventdata) {
                var speech = eventdata.capturedspeech;
                var speechresults = eventdata.speechresults;
            };

            //originates from the recording:started event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_start = function (eventdata) {
                dd.rec_time_start = new Date().getTime();
                dd.dopassagelayout();

               // dd.controls.passagecontainer.show(1000, beginall);
                dd.controls.passagecontainer.show(500,beginall);
                dd.controls.passagecontainer[0].scrollIntoView({behaviour: "smooth", block: "start", inline: "nearest"});

                /*
                var scrollparent = $("#page");
                var newtop = scrollparent.scrollTop() + dd.controls.passagecontainer.offset().top - scrollparent.offset().top;
                if(newtop<0) {newtop=0;}
                scrollparent.animate({scrollTop: newtop}, 500,beginall);
                */

            };

            //originates from the recording:ended event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_end = function (eventdata) {
                //its a bit hacky but the rec end event can arrive immed. somehow probably when the mic test ends
                var now = new Date().getTime();
                if ((now - dd.rec_time_start) < 3000) {
                    return;
                }
                dd.douploadlayout();
                //if we are shadowing we should stop the audio player.
                if(dd.stepshadow_enabled && dd.letsshadow){
                    dd.controls.modelaudioplayer[0].currentTime=0;
                    dd.controls.modelaudioplayer[0].pause();
                }
            };

            //data sent here originates from the awaiting_processing event
            //See https://api.poodll.com
            var on_audio_processing = function (eventdata) {
                //at this point we know the submission has been uploaded and we know the fileURL
                //so we send the submission
                var now = new Date().getTime();
                var rectime = now - dd.rec_time_start;
                if (rectime > 0) {
                    rectime = Math.ceil(rectime / 1000);
                }

                dd.send_submission(eventdata.mediaurl, rectime);

                // Complete the current step (update server and ui)
                dd.update_activity_step(dd.activitydata.steps.step_read);

                // Send user to the finished report immediately
                smallreporthelper.update_filename(eventdata.mediaurl);
                smallreporthelper.start_check_for_results();
                dd.doreportlayout();
            };

            //init the recorder
            recorderhelper.init(dd.activitydata,
                on_recording_start,
                on_recording_end,
                on_audio_processing,
                on_speech);
        },

        register_events: function () {
            var dd = this;

            $('.mode-chooser.no-click').each(function() {
                this.addEventListener('click', disableClick, true);
                this.addEventListener('keypress', disableClick, true);
            });

            dd.controls.startlistenbutton.click(function(e){
                dd.dopreviewlayout();
                  // TO DO: where to set this properly?
                  // Complete the current step (update server and ui).
                  dd.update_activity_step(dd.activitydata.steps.step_listen);
            });
            dd.controls.startlistenbutton.keypress(function(e){
                if (e.which == 32 || e.which == 13 ) {
                    dd.dopreviewlayout();
                    e.preventDefault();
                   // TO DO: where to set this properly?
                  // Complete the current step (update server and ui).
                  dd.update_activity_step(dd.activitydata.steps.step_listen);
                }
            });
            dd.controls.startpracticebutton.click(function(e){
                dd.dolandrlayout();
            });
            dd.controls.startpracticebutton.keypress(function(e){
                if (e.which == 32 || e.which == 13 ) {
                    dd.dolandrlayout();
                    e.preventDefault();
                }
            });
            dd.controls.startreadbutton.click(function(e){
                if(dd.steps_complete.step_read){
                    dd.doreportlayout();   
                }else{
                    dd.letsshadow=false;
                    dd.doreadinglayout();
                }
            });
            dd.controls.startreadbutton.keypress(function(e){
                if (e.which == 32 || e.which == 13) {
                    if(dd.steps_complete.step_read){
                        dd.doreportlayout();   
                    }else{
                        dd.letsshadow=false;
                        dd.doreadinglayout();
                    }
                    e.preventDefault();
                }
            });

            dd.controls.readagainbutton.click(function(e){
                var result = confirm(dd.strings.confirm_read_again);
                    //exit if they dont want to
                    if (!result) {
                        return;
                    }
                //reset the recorder and start again    
                dd.reset_recorder();    
                dd.letsshadow=false;
                dd.doreadinglayout();
            });

            dd.controls.readagainbutton.keypress(function(e){
                if (e.which == 32 || e.which == 13) {
                    dd.letsshadow=false;
                    dd.doreadinglayout();
                    e.preventDefault();
                }
            });

            dd.controls.startshadowbutton.click(function(e){
                //landr shadowing
                //dd.dolandrlayout();
                // landr.shadow=true;

                dd.letsshadow=true;
                dd.doreadinglayout();
            });
            dd.controls.startshadowbutton.keypress(function(e){
                if (e.which == 32 || e.which == 13) {
                    //dd.dolandrlayout();
                    //landr.shadow=true;

                    dd.letsshadow=true;
                    dd.doreadinglayout();
                    e.preventDefault();
                }
            });
            dd.controls.returnmenubutton.click(function(e){
                //in most cases ajax hide show is ok, but L&R stuffs up android for normal readaloud so we reload
                if(dd.isandroid() && dd.controls.landrinstructionscontainer.is(":visible")) {
                    location.reload();
                }else if(dd.controls.readingcontainer.is(":visible")
                    && dd.controls.passagecontainer.hasClass('readmode')
                    && dd.controls.passagecontainer.is(":visible")){
                    // Display a confirmation dialog
                    var result = confirm(dd.strings.confirm_cancel_recording);
                    //there is no way to stop the recorder early, so just reload the page, brutal
                    if (result) {
                        location.reload();
                    }
                }else {
                    dd.controls.modelaudioplayer[0].currentTime = 0;
                    dd.controls.modelaudioplayer[0].pause();
                    dd.domenulayout();
                }
            });

            dd.controls.startreportbutton.click(function(e){
                dd.doreportlayout();
            });
            dd.controls.startreportbutton.keypress(function(e){
                if (e.which == 32 || e.which == 13 ) {
                    dd.doreportlayout();
                }
            });

            dd.controls.startquizbutton.click(function(e){
                dd.doquizlayout();
            });
            dd.controls.startquizbutton.keypress(function(e){
                if (e.which == 32 || e.which == 13 ) {
                    dd.doquizlayout();
                    e.preventDefault();
                }
            });
            dd.controls.homebutton.click(function(e){
                dd.domenulayout();
            });
        },

        // when a step is completed, we update the activity completion on the server
        // and open the next step
        update_activity_step: function (step) {
            var that = this;
            Ajax.call([{
                methodname: 'mod_readaloud_report_activitystep_completion',
                args: {
                    cmid: that.cmid,
                    step:  step
                },
                done: function(ajaxresult){
                    var success = JSON.parse(ajaxresult);
                    switch (success) {
                        case true:
                            var adata = that.activitydata;
                            for (var key in adata.steps) {
                                var thestep = adata.steps[key];
                                if (thestep === step) {
                                    that.activitydata.stepscomplete[key] = true;
                                } else {
                                    continue;
                                }
                            }
                            that.updateModeStatuses();
                            that.updateBigButtonMenuModeStatus();
                            that.open_next_step(step);

                            break;
                        case false:
                        default:
                            log.debug('step ' + step + ' update failed');
                    }

                },
                fail: notification.exception
            }]);
        },

        open_next_step: function(oldstep){
            var that = this;
            var adata = this.activitydata;
            //loop through adata.steps array
            //this looks like['step_listen': 1, 'step_practice': 2, 'step_shadow': 4, 'step_read': 8, 'step_quiz': 16]
            for (var key in adata.steps) {
                var thestep = adata.steps[key];
                //if the looped step is less than or equal to the old step, skip
                if (thestep <= oldstep) {
                    continue;
                } else {
                    //if the looped step is enabled (present on page), open it
                    var step_chooser = $('#' + adata['menubuttonscontainer'] + ' .mode-chooser[data-step="' + thestep + '"]');
                    log.debug(step_chooser);
                    if(step_chooser.length){
                        step_chooser.removeClass('no-click');
                        step_chooser[0].removeEventListener('click', disableClick, true);

                        // (Hacky) show report if read step was done
                        if (oldstep == adata.steps.step_read) {
                            that.controls.startreportbutton.removeClass('no-click');
                        }

                        // Record the newly opened step as 'open' for client side use
                        //TO DO-  get this working
                       // that.steps_open[key] = true;
                        break;
                    }
                }
            }
        },

        send_submission: function (filename, rectime) {
            var that = this;
            var shadowing = (that.stepshadow_enabled && that.letsshadow) ? 1 : 0;
            Ajax.call([{
                methodname: 'mod_readaloud_submit_regular_attempt',
                args: {
                    cmid: that.cmid,
                    filename:  filename,//encodeURIComponent(filename),
                    rectime: rectime,
                    shadowing: shadowing
                },
                done: function(ajaxresult){
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
/*
            return;

            //set up our ajax request
            var xhr = new XMLHttpRequest();
            var that = this;

            //set up our handler for the response
            xhr.onreadystatechange = function (e) {
                if (this.readyState === 4) {
                    if (xhr.status === 200) {
                        log.debug('ok we got an attempt submission response');
                        //get a yes or forgetit or tryagain
                        var payload = xhr.responseText;
                        var payloadobject = JSON.parse(payload);
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
                    } else {
                        log.debug('Not 200 response:' + xhr.status);
                    }
                }
            };

            //to get through mod_security environments
            filename = filename.replace(/^https:\/\//i, 'https___');
            var params = "cmid=" + that.cmid + "&filename=" + encodeURIComponent(filename) + "&rectime=" + rectime;
            xhr.open("POST", M.cfg.wwwroot + '/mod/readaloud/ajaxhelper.php', true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.setRequestHeader("Cache-Control", "no-cache");
            xhr.send(params);
*/
        },

        domenulayout: function () {
            var m = this;

            // Hide.
            m.controls.activityinstructionscontainer.hide();
            m.controls.feedbackcontainer.hide();
            m.controls.hider.hide();
            m.controls.landrinstructionscontainer.hide();
            landr.deactivate();
            m.controls.modelaudioplayer.hide();
            m.controls.previewinstructionscontainer.hide();
            m.controls.progresscontainer.hide();
            m.controls.passagecontainer.hide();
            m.controls.quizcontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.returnmenubutton.hide();
            m.controls.smallreportcontainer.hide();
            m.controls.wheretonextcontainer.hide();

            // Show.
            m.controls.homecontainer.show();
            m.controls.introbox.show();
            m.controls.menuinstructionscontainer.show();
            m.controls.menubuttonscontainer.show();

            m.controls.stopandplay.removeClass('visible').addClass('hidden');
            m.controls.readingcontainer.removeClass(def.containerfillscreen);
            m.controls.modeimagecontainer.removeClass('d-block');
            m.controls.modeimagecontainer.addClass('d-none');

            modelaudiokaraoke.modeling=true;

            // Update the mode statuses (completed, in-progress, upcoming).
            m.updateModeStatuses();
        },

        doreadinglayout: function () {
            var m = this;

            // Hide.
            m.controls.feedbackcontainer.hide();
            m.controls.homecontainer.hide();
            m.controls.introbox.hide();
            m.controls.menuinstructionscontainer.hide();
            m.controls.menubuttonscontainer.hide();
            m.controls.passagecontainer.hide();
            m.controls.progresscontainer.hide();
            m.controls.quizcontainer.hide();
            m.controls.smallreportcontainer.hide();
            m.controls.wheretonextcontainer.hide();
            m.controls.passagecontainer.removeClass(m.passagefinished);

            // Show.
            m.controls.recordingcontainer.show();
            m.controls.returnmenubutton.show();
            m.controls.hider.fadeOut('fast');
            m.controls.activityinstructionscontainer.show();

            m.controls.passagecontainer.removeClass('previewmode shadowmode reviewmode nothingmode');
            m.controls.passagecontainer.addClass('readmode');
            m.controls.stopandplay.removeClass('visible').addClass('hidden');
            m.controls.modeimagecontainer.removeClass('fa-comment fa-comments fa-headphones fa-circle-question fa-chart-simple');
            m.controls.modeimagecontainer.addClass('fa-book-open-reader d-block');

            modelaudiokaraoke.modeling=true;
        },

        dopreviewlayout: function () {
            var m = this;

            // Hide.
            m.controls.activityinstructionscontainer.hide();
            m.controls.feedbackcontainer.hide();
            m.controls.hider.hide();
            m.controls.homecontainer.hide();
            m.controls.introbox.hide();
            m.controls.landrinstructionscontainer.hide();
            m.controls.menuinstructionscontainer.hide();
            m.controls.modelaudioplayer.hide();
            m.controls.progresscontainer.hide();
            m.controls.quizcontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.smallreportcontainer.hide();
            m.controls.stopandplay.show();
            m.controls.wheretonextcontainer.hide();

            // Show.
            m.controls.menubuttonscontainer.show();
            m.controls.passagecontainer.show();
            m.controls.previewinstructionscontainer.show();
            m.controls.returnmenubutton.show();

            m.controls.passagecontainer.removeClass('readmode shadowmode reviewmode nothingmode');
            m.controls.passagecontainer.addClass('previewmode');
            m.controls.stopandplay.removeClass('hidden').addClass('visible');
            m.controls.modeimagecontainer.removeClass('fa-comment fa-comments fa-book-open-reader fa-circle-question fa-chart-simple');
            m.controls.modeimagecontainer.addClass('fa-headphones d-block');

            modelaudiokaraoke.modeling=false;
        },

        dolandrlayout: function () {
            var m = this;

            // Hide.
            m.controls.activityinstructionscontainer.hide();
            m.controls.feedbackcontainer.hide();
            m.controls.homecontainer.hide();
            m.controls.hider.hide();
            m.controls.introbox.hide();
            m.controls.modelaudioplayer.hide();
            m.controls.previewinstructionscontainer.hide();
            m.controls.progresscontainer.hide();
            m.controls.quizcontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.smallreportcontainer.hide();
            m.controls.wheretonextcontainer.hide();

            // Show.
            m.controls.returnmenubutton.show();
            m.controls.menubuttonscontainer.show();
            m.controls.landrinstructionscontainer.show();
            m.controls.passagecontainer.show();

            m.controls.passagecontainer.removeClass('readmode shadowmode reviewmode nothingmode');
            m.controls.passagecontainer.addClass('previewmode');
            m.controls.stopandplay.removeClass('hidden').addClass('visible');
            m.controls.modeimagecontainer.removeClass('fa-headphones fa-comments fa-book-open-reader fa-circle-question fa-chart-simple');
            m.controls.modeimagecontainer.addClass('fa-comment d-block');

            landr.activate();

            modelaudiokaraoke.modeling=false;
        },

        dopassagelayout: function () {
            var m = this;

            // Hide.
            m.controls.introbox.hide();

            m.controls.readingcontainer.addClass(def.containerfillscreen);
        },

        douploadlayout: function () {
            var m = this;

            m.controls.passagecontainer.addClass(m.passagefinished);
            m.controls.hider.fadeIn('fast');
            m.controls.progresscontainer.fadeIn('fast');
        },


        dofinishedlayout: function () {
            var m = this;

            // Hide.
            m.controls.activityinstructionscontainer.hide();
            m.controls.passagecontainer.hide();
            m.controls.quizcontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.returnmenubutton.hide();
            m.controls.smallreportcontainer.hide();

            // Show.
            m.controls.menubuttonscontainer.show();
            m.controls.feedbackcontainer.show();
            m.controls.wheretonextcontainer.show();

            m.controls.readingcontainer.removeClass(def.containerfillscreen);

            m.controls.hider.fadeOut('fast');
            m.controls.progresscontainer.fadeOut('fast');

        },
        doerrorlayout: function () {
            var m = this;

            // Hide.
            m.controls.passagecontainer.hide();
            m.controls.quizcontainer.hide();
            m.controls.recordingcontainer.hide();

            // Show.
            m.controls.menubuttonscontainer.show();
            m.controls.errorcontainer.show();
            m.controls.wheretonextcontainer.show();

            m.controls.readingcontainer.removeClass(def.containerfillscreen);

            m.controls.hider.fadeOut('fast');
            m.controls.progresscontainer.fadeOut('fast');
        },
        doreportlayout: function () {
            var m = this;

            // Hide.
            m.controls.activityinstructionscontainer.hide();
            m.controls.homecontainer.hide();
            m.controls.landrinstructionscontainer.hide();
            m.controls.passagecontainer.hide();
            m.controls.previewinstructionscontainer.hide();
            m.controls.menuinstructionscontainer.hide();
            m.controls.quizcontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.stopandplay.removeClass('visible').addClass('hidden');

            //clean up upload layout
            m.controls.readingcontainer.removeClass(def.containerfillscreen);
            m.controls.hider.fadeOut('fast');
            m.controls.progresscontainer.fadeOut('fast');

            // Show.
            m.controls.menubuttonscontainer.show();
            m.controls.returnmenubutton.show();
            m.controls.smallreportcontainer.show();

            m.controls.modeimagecontainer.removeClass('fa-headphones fa-comment fa-comments fa-book-open-reader fa-circle-question');
            m.controls.modeimagecontainer.addClass('fa-chart-simple d-block');
        },
        doquizlayout: function(){
            var m = this;

            // Hide.
            m.controls.activityinstructionscontainer.hide();
            m.controls.homecontainer.hide();
            m.controls.landrinstructionscontainer.hide();
            m.controls.menubuttonscontainer.hide();
            m.controls.passagecontainer.hide();
            m.controls.previewinstructionscontainer.hide();
            m.controls.quizplaceholder.hide();
            m.controls.recordingcontainer.hide();
            m.controls.smallreportcontainer.hide();

            // Show.
            m.controls.quizcontainer.show();

            m.controls.stopandplay.removeClass('visible').addClass('hidden');
            m.controls.modeimagecontainer.removeClass('fa-headphones fa-comment fa-comments fa-book-open-reader fa-chart-simple');
            m.controls.modeimagecontainer.addClass('fa-circle-question d-block');
        },

        updateModeStatuses: function() {
            var dd = this;

            var stepsOrder = [
                'step_listen',
                'step_practice',
                'step_shadow',
                'step_read',
                'step_quiz',
                'step_report'
            ];

            var stepsComplete = dd.activitydata.stepscomplete || {};

            var $container = dd.controls.modejourneycontainer;
            if (!$container || !$container.length) {
                console.error('Mode journey container not found.');
                return;
            }

            // Flag to ensure only the first incomplete step is marked in-progress.
            var foundIncomplete = false;

            $container.find('.mode').each(function(index, modeElem) {
                var $mode = $(modeElem);
                var stepName = stepsOrder[index];

                $mode.removeClass('completed in-progress upcoming');

                var isComplete = (stepsComplete[stepName] === true || stepsComplete[stepName] === 'true');

                // If the step is complete, mark it complete.
                if (isComplete) {
                    $mode.addClass('completed');
                }
                // If it's not complete and no incomplete step has been marked yet, mark it as in-progress.
                else if (!foundIncomplete) {
                    $mode.addClass('in-progress');
                    foundIncomplete = true;
                }
                // Otherwise, mark it as upcoming.
                else {
                    $mode.addClass('upcoming');
                }
            });
        },

        updateBigButtonMenuModeStatus: function() {
            var dd = this;
            var stepsOrder = [
                'step_listen',
                'step_practice',
                'step_shadow',
                'step_read',
                'step_quiz',
                'step_report'
            ];

            var stepsComplete = dd.activitydata.stepscomplete || {};

            var thecontainer = dd.controls.menubuttonscontainer;
            if (!thecontainer || !thecontainer.length) {
                console.error('Menu buttons container not found.');
                return;
            }

            // Flag to ensure only the first incomplete step is marked "in-progress".
            var foundIncomplete = false;

            thecontainer.find('li.mode-chooser').each(function(index, liElem) {
                var $li = $(liElem);
                var stepName = stepsOrder[index];

                var isComplete = (stepsComplete[stepName] === true || stepsComplete[stepName] === 'true');
                var status;
                if (isComplete) {
                    status = 'completed';
                } else if (!foundIncomplete) {
                    status = 'in-progress';
                    foundIncomplete = true;
                } else {
                    status = 'upcoming';
                }

                // Update the Font Awesome icon.
                var $iconSpan = $li.find('.nav-completion-icon');
                if ($iconSpan.length) {
                    if (status === 'completed') {
                        $iconSpan.html('<i class="fa-solid fa-circle-check text-success" title="Complete"></i>');
                    } else if (status === 'in-progress') {
                        $iconSpan.html('<i class="fa-regular fa-circle text-warning" title="In progress"></i>');
                    } else { // upcoming.
                        $iconSpan.html('<i class="fa-solid fa-lock text-secondary" title="Locked"></i>');
                    }
                }
            });
        },

        isandroid: function() {
                if (/Android/i.test(navigator.userAgent)) {
                    return true;
                } else {
                    return false;
                }
        }
    };//end of returned object
});//total end
