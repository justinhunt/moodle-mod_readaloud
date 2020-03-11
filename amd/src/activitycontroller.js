/* jshint ignore:start */
define(['jquery', 'jqueryui', 'core/log', 'mod_readaloud/definitions',
        'mod_readaloud/recorderhelper', 'mod_readaloud/modelaudiokaraoke',
        'mod_readaloud/transcriber-lazy','core/ajax','core/notification'],
    function ($, jqui, log, def, recorderhelper, modelaudiokaraoke, transcriber, Ajax, notification) {

    "use strict"; // jshint ;_;

    log.debug('Activity controller: initialising');

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
        enableshadow: false,
        enablepreview: false,
        letsshadow: false,
        streamingresults: false,

        //CSS in this file
        passagefinished: def.passagefinished,

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
        init: function (props) {
            var dd = this.clone();

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

            //set up model audio
            dd.enableshadow =dd.activitydata.enableshadow;
            dd.enablepreview =dd.activitydata.enablepreview;
            dd.setupmodelaudio();

            //init streaming transcriber
            var opts={};
            opts['language']=dd.activitydata.language;
            opts['region']=dd.activitydata.region;
            opts['accessid']=dd.activitydata.accessid;
            opts['secretkey']=dd.activitydata.secretkey;
            opts['transcriber']=dd.activitydata.transcriber;
            if(opts['transcriber'] == def.transcriber_amazonstreaming) {
                transcriber.init(opts);
                transcriber.onFinalResult = function (transcript, result) {
                    dd.streamingresults.push(result);
                    //if recording over deal with final result
                    //if(!transcriber.active){
                    log.debug(dd.streamingresults);
                    //}

                    // theCallback(message);
                };
                transcriber.onPartialResult = function (transcript, result) {
                    //do nothing
                };
            }

            //init recorder and html and events
            dd.setup_recorder();
            dd.process_html(dd.activitydata);
            dd.register_events();

            //set initial mode
            if(dd.enableshadow || dd.enablepreview){
                dd.domenulayout();
            }else{
                dd.doreadinglayout();
            }
        },

        setupmodelaudio: function(){
            var karaoke_opts={breaks:this.activitydata.breaks, audioplayerclass:this.activitydata.audioplayerclass };
            modelaudiokaraoke.init(karaoke_opts);
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
                passagecontainer: $('.' + opts['passagecontainer']),
                recordingcontainer: $('.' + opts['recordingcontainer']),
                dummyrecorder: $('.' + opts['dummyrecorder']),
                recordercontainer: $('.' + opts['recordercontainer']),
                menubuttonscontainer: $('.' + opts['menubuttonscontainer']),
                menuinstructionscontainer: $('.' + opts['menuinstructionscontainer']),
                activityinstructionscontainer: $('.' + opts['activityinstructionscontainer']),
                recinstructionscontainerright: $('.' + opts['recinstructionscontainerright']),
                recinstructionscontainerleft: $('.' + opts['recinstructionscontainerleft']),
                allowearlyexit: $('.' + opts['allowearlyexit']),
                wheretonextcontainer: $('.' + opts['wheretonextcontainer']),
                modelaudioplayer: $('#' + opts['modelaudioplayer']),
                startpreviewbutton: $('#' + opts['startpreviewbutton']),
                startreadingbutton: $('#' + opts['startreadingbutton']),
                startshadowbutton: $('#' + opts['startshadowbutton']),
                returnmenubutton: $('#' + opts['returnmenubutton'])
            };
            this.controls = controls;
        },

        is_browser_ok: function () {
            return (navigator && navigator.mediaDevices
                && navigator.mediaDevices.getUserMedia);
        },

        setup_recorder: function () {
            var dd = this;

            //after the recorder reports that it has (really) started this functuon is called.
            var beginall= function(){
                dd.passagerecorded = true;
                if(dd.enableshadow && dd.letsshadow){
                    dd.controls.modelaudioplayer[0].play();
                }
            };

            //originates from the recording:started event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_start = function (eventdata) {
                dd.rec_time_start = new Date().getTime();
                dd.dopassagelayout();
                dd.controls.passagecontainer.show(1000, beginall);

                //start streaming transcriber
                if(dd.activitydata.transcriber == def.transcriber_amazonstreaming) {
                    if (transcriber.active) {
                        return;
                    }
                    //init our streamingresults
                    dd.streamingresults = [];
                    // first we get the microphone input from the browser (as a promise)...
                    window.navigator.mediaDevices.getUserMedia({
                        video: false,
                        audio: true,
                    }).then(function (stream) {
                        transcriber.start(stream, transcriber)
                    }).catch(function (error) {
                            log.debug(error);
                            log.debug('There was an error streaming your audio to Amazon Transcribe. Please try again.');
                        }
                    );
                }//end of if amazonstreaming
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
                if(dd.enableshadow && dd.letsshadow){
                    dd.controls.modelaudioplayer[0].currentTime=0;
                    dd.controls.modelaudioplayer[0].pause();
                }

                //stop streaming transcriber
                if(dd.activitydata.transcriber == def.transcriber_amazonstreaming) {
                    if (!transcriber.active) {
                        return;
                    }
                    transcriber.closeSocket();
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

                if(dd.activitydata.transcriber == def.transcriber_amazonstreaming &&
                    dd.streamingresults &&
                    dd.streamingresults.length > 0){
                    dd.send_streaming_submission(eventdata.mediaurl, rectime, dd.streamingresults);
                }else {
                    dd.send_submission(eventdata.mediaurl, rectime);
                }
                //and let the user know that they are all done
                dd.dofinishedlayout();
            };

            //init the recorder
            recorderhelper.init(dd.activitydata,
                on_recording_start,
                on_recording_end,
                on_audio_processing);
        },

        register_events: function () {
            var dd = this;
            dd.controls.startpreviewbutton.click(function(){
                dd.dopreviewlayout();
            });
            dd.controls.startreadingbutton.click(function(){
                dd.letsshadow=false;
                dd.doreadinglayout();
            });
            dd.controls.startshadowbutton.click(function(){
                dd.letsshadow=true;
                dd.doreadinglayout();
            });
            dd.controls.returnmenubutton.click(function(){
                dd.controls.modelaudioplayer[0].currentTime=0;
                dd.controls.modelaudioplayer[0].pause();
                dd.domenulayout();
            });
        },

        send_streaming_submission: function (filename, rectime, streamingresults) {
            var that = this;
            Ajax.call([{
                methodname: 'mod_readaloud_submit_streaming_attempt',
                args: {
                    cmid: that.cmid,
                    filename: filename,//encodeURIComponent(filename),
                    rectime: rectime,
                    awsresults: JSON.stringify(streamingresults)
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

        send_submission: function (filename, rectime) {
            var that = this;
            Ajax.call([{
                methodname: 'mod_readaloud_submit_regular_attempt',
                args: {
                    cmid: that.cmid,
                    filename:  filename,//encodeURIComponent(filename),
                    rectime: rectime
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

        doreadinglayout: function () {
            var m = this;
            m.controls.hider.fadeOut('fast');
            m.controls.activityinstructionscontainer.show();
            m.controls.recordingcontainer.show();
            m.controls.menuinstructionscontainer.hide();
            m.controls.menubuttonscontainer.hide();
            m.controls.returnmenubutton.hide();
            m.controls.progresscontainer.hide();
            m.controls.passagecontainer.hide();
            m.controls.feedbackcontainer.hide();
            m.controls.wheretonextcontainer.hide();
        },

        domenulayout: function () {
            var m = this;
            m.controls.menuinstructionscontainer.show();
            m.controls.menubuttonscontainer.show();
            m.controls.activityinstructionscontainer.hide();
            m.controls.returnmenubutton.hide();
            m.controls.progresscontainer.hide();
            m.controls.passagecontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.feedbackcontainer.hide();
            m.controls.wheretonextcontainer.hide();
            m.controls.modelaudioplayer.hide();
            m.controls.hider.hide();
        },

        dopreviewlayout: function () {
            var m = this;
            m.controls.passagecontainer.show();
            m.controls.returnmenubutton.show();
            m.controls.modelaudioplayer.show();
            m.controls.menubuttonscontainer.hide();
            m.controls.hider.hide();
            m.controls.progresscontainer.hide();
            m.controls.menuinstructionscontainer.hide();
            m.controls.activityinstructionscontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.feedbackcontainer.hide();
            m.controls.wheretonextcontainer.hide();
        },


        dopassagelayout: function () {
            var m = this;
            m.controls.introbox.hide();
        },

        douploadlayout: function () {
            var m = this;
            m.controls.passagecontainer.addClass(m.passagefinished);
            m.controls.hider.fadeIn('fast');
            m.controls.progresscontainer.fadeIn('fast');
        },

        dofinishedlayout: function () {
            var m = this;
            m.controls.hider.fadeOut('fast');
            m.controls.progresscontainer.fadeOut('fast');
            m.controls.activityinstructionscontainer.hide();
            m.controls.passagecontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.feedbackcontainer.show();
            m.controls.wheretonextcontainer.show();
        },
        doerrorlayout: function () {
            var m = this;
            m.controls.hider.fadeOut('fast');
            m.controls.progresscontainer.fadeOut('fast');
            m.controls.passagecontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.errorcontainer.show();
            m.controls.wheretonextcontainer.show();
        }
    };//end of returned object
});//total end
