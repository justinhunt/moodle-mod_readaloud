define(['jquery', 'core/log','mod_readaloud/definitions','core/str','core/ajax',
        'core/templates','core/notification','mod_readaloud/recorderhelper','mod_readaloud/ttrecorder',
        'mod_readaloud/ttbrowserrec'],
    function ($, log, def, str, Ajax,
              templates, notification, recorderhelper, ttrecorder, browserRec) {
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
        //streaming recorder state
        ttr: null,
        streaming: false,
        mediaurl: false,
        bloburl: false,
        speechresults: false,
        speechtext: '',
        submitted: false,
        //class definitions
        cd: {
            wordclass: def.wordclass,

        },

        //init the module
        init: function(opts){
            this.opts = opts;
            this.activitycontroller = opts.activitycontroller;
            this.mediaurl = false;
            this.bloburl = false;
            this.speechresults = false;
            this.speechtext = '';
            this.submitted = false;
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
            dd.streaming = dd.choose_recorder(activitydata);

            if (dd.streaming) {
                dd.init_streaming_recorder(activitydata, on_recording_start, on_recording_end);
            } else {
                dd.init_iframe_recorder(activitydata,
                    on_recording_start,
                    on_recording_end,
                    on_audio_processing,
                    on_speech
                );
            }
        },

        /*
        * Decide which of the two recorders on the page to use, and reveal it.
        *
        * Php renders both when the in page recorder is a candidate, because the deciding fact is only
        * available here: whether this browser has speech recognition. ttrecorder has three engines and
        * only two of them can transcribe a passage reading. Browser recognition restarts itself and
        * accumulates, and streaming rebases across token refreshes, so both cope. The third, the upload
        * transcriber, posts the whole recording in one request and is limited to around 30 seconds, so
        * it must never be what we land on. It is reached only when there is no browser recognition and
        * no streaming token, and that is exactly the case we hand to the iframe instead.
         */
        choose_recorder: function (activitydata) {
            var inpage = $('.' + def.inpagerecorder);
            var iframe = $('.' + def.iframerecorder);

            // Php did not offer the in page recorder at all, so there is nothing to choose.
            if (inpage.length === 0) {
                return false;
            }

            // The read step always saves the media for teacher grading. On android the platform
            // recogniser takes the microphone for itself, so browser recognition and a saved recording
            // cannot both happen, and ttrecorder skips browser recognition there.
            //
            // forcestreaming is the site saying third party recognition only, so ttrecorder will not
            // choose browser recognition even where it works. We have to agree with it here, or we
            // would hand it a recording it can only send to the upload transcriber.
            var button = $('#' + activitydata.readttrecorderid + '_recorderbutton');
            var isandroid = navigator.userAgent.indexOf('Android') > -1;
            var thirdpartyonly = button.data('forcestreaming') === 1 || button.data('forcestreaming') === true;
            var canbrowserrec = browserRec.will_work_ok() && !isandroid && !thirdpartyonly;

            // A streaming token is only issued when the engine can read this activity's language.
            var hastoken = !!button.data('speechtoken');

            var useinpage = canbrowserrec || hastoken;
            log.debug('Read: browser rec ' + canbrowserrec + ' (third party only ' + thirdpartyonly +
                '), streaming token ' + hastoken +
                ' -> ' + (useinpage ? 'in page recorder' : 'iframe recorder'));

            if (useinpage) {
                iframe.addClass('d-none');
                inpage.removeClass('d-none');
            } else {
                inpage.addClass('d-none');
                iframe.removeClass('d-none');
            }
            return useinpage;
        },

        /*
        * The cloud poodll iframe recorder. Only initialise it once its markup is on the page, because
        * read.init() also runs at page load, before the read template has been rendered, and
        * CloudPoodll.createRecorder() resolves its container by id.
         */
        init_iframe_recorder: function (activitydata, on_recording_start, on_recording_end,
                                        on_audio_processing, on_speech) {
            if ($('#' + activitydata.recorderid).length === 0) {
                log.debug('Read: iframe recorder not on the page yet, waiting for the read template');
                return;
            }
            recorderhelper.init(activitydata,
                on_recording_start,
                on_recording_end,
                on_audio_processing,
                on_speech
            );
        },

        /*
        * The in page streaming recorder, used in place of the cloud poodll iframe.
        *
        * Unlike the iframe, which only tells us about the submission once the audio is safely uploaded, here the
        * audio url and the transcript arrive separately: 'mediasaved' fires as soon as the upload is kicked off,
        * and 'speech' fires about a second after the recogniser closes its socket. So we collect both and submit
        * once we have them, rather than submitting on either one.
         */
        init_streaming_recorder: function (activitydata, on_recording_start, on_recording_end) {
            var dd = this;

            // init() is called twice: once from activitycontroller.setupread() at page load, and again from
            // renderMode() once the read template has been put on the page. Only the second one can build a
            // recorder, because ttrecorder reads all its configuration from data attributes on the button.
            // Without this guard the first call finds nothing, reads every data attribute as undefined
            // (including forcestreaming, so it picks browser rec), and then dies on the missing canvas.
            if ($('#' + activitydata.readttrecorderid + '_recorderbutton').length === 0) {
                log.debug('Read: streaming recorder not on the page yet, waiting for the read template');
                return;
            }

            var theCallback = function (message) {
                log.debug('Read: ttrecorder callback ' + message.type);
                switch (message.type) {
                    case 'recordingstarted':
                        on_recording_start(message);
                        break;

                    case 'recordingstopped':
                        on_recording_end(message);
                        // The recogniser may return nothing at all, in which case no speech event ever
                        // arrives and we would sit here forever. Submit anyway after a grace period and
                        // let the server fall back to transcribing the uploaded audio.
                        dd.start_submit_timeout();
                        break;

                    case 'mediasaved':
                        log.debug('Read: media saved at ' + message.mediaurl);
                        dd.mediaurl = message.mediaurl;
                        // The cloud copy is still uploading and transcoding, so it 404s for a while. Keep the
                        // local blob so the report can play the reading back straight away.
                        dd.bloburl = message.bloburl;
                        dd.maybe_submit();
                        break;

                    case 'speech':
                        log.debug('Read: speech captured');
                        // speechresults holds the word level timings, and is false when the recogniser gave
                        // us none. Browser speech recognition and the upload transcriber both return text
                        // only, so keep the text as well - without it the server has nothing to diff and
                        // would score the reading as silence.
                        dd.speechresults = message.speechresults ? message.speechresults : [];
                        dd.speechtext = message.capturedspeech ? message.capturedspeech : '';
                        dd.maybe_submit();
                        break;
                }
            };

            var opts = {};
            opts.uniqueid = activitydata.readttrecorderid;
            opts.callback = theCallback;
            opts.stt_guided = false;
            dd.ttr = ttrecorder.clone();
            dd.ttr.init(opts);
        },

        /*
        * Submit once we have the audio url and the transcript. Whichever arrives second triggers the send.
         */
        maybe_submit: function () {
            var dd = this;
            if (dd.submitted) {
                return;
            }
            if (dd.mediaurl === false || dd.speechresults === false) {
                return;
            }
            dd.do_streaming_submit();
        },

        /*
        * Backstop for a reading the recogniser returned nothing for. ttrecorder swallows an empty transcript
        * and never fires a speech event, so without this the student would be stuck on the recording screen.
         */
        start_submit_timeout: function () {
            var dd = this;
            setTimeout(function () {
                if (dd.submitted) {
                    return;
                }
                if (dd.speechresults === false) {
                    log.debug('Read: no speech results arrived, submitting without a transcript');
                    dd.speechresults = [];
                    dd.maybe_submit();
                }
            }, 8000);
        },

        do_streaming_submit: function () {
            var dd = this;
            dd.submitted = true;

            var now = new Date().getTime();
            var rectime = now - dd.rec_time_start;
            if (rectime > 0) {
                rectime = Math.ceil(rectime / 1000);
            }

            // Unlike the iframe path, the attempt is graded during this call. So only move the student on
            // once it has returned, otherwise the read report checks for a result that is not saved yet and
            // then sits through its retry delay for nothing.
            dd.send_streaming_submission(dd.mediaurl, rectime, dd.speechresults, dd.speechtext, function () {
                dd.on_complete({mediaurl: dd.mediaurl, bloburl: dd.bloburl});
            });
        },

        send_streaming_submission: function (filename, rectime, speechresults, speechtext, onfinished) {
            var that = this;
            var shadowing = (that.opts.stepshadow_enabled && that.opts.letsshadow) ? 1 : 0;
            var finished = false;
            // Whatever happens, move the student on. Being stuck on the recording screen is worse than
            // landing on a report that has to wait for its data.
            var finish = function () {
                if (finished) {
                    return;
                }
                finished = true;
                if (onfinished) {
                    onfinished();
                }
            };

            Ajax.call([{
                methodname: 'mod_readaloud_submit_streaming_attempt',
                args: {
                    cmid: that.opts.cmid,
                    filename: filename,
                    rectime: rectime,
                    awsresults: JSON.stringify({text: speechtext, words: speechresults}),
                    shadowing: shadowing
                },
                done: function (ajaxresult) {
                    var payloadobject = JSON.parse(ajaxresult);
                    if (payloadobject) {
                        if (payloadobject.success) {
                            log.debug('streaming submission accepted');
                        } else {
                            log.debug('streaming submission failure');
                            if (payloadobject.message) {
                                log.debug('message: ' + payloadobject.message);
                            }
                        }
                    }
                    finish();
                },
                fail: function (ex) {
                    finish();
                    notification.exception(ex);
                }
            }]);
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
            // Clear per attempt state either way, the student is going again.
            this.mediaurl = false;
            this.bloburl = false;
            this.speechresults = false;
            this.speechtext = '';
            this.submitted = false;

            // The in page recorder lives in the read template, which is re-rendered on the way back in,
            // and init() will build a fresh one. Nothing to reset here.
            if (this.streaming) {
                this.ttr = null;
                return;
            }

            recorderhelper.reset();
            //this.setup_recorder();
            this.register_events();
        },
    };//end of return value
});