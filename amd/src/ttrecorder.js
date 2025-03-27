define(['jquery', 'core/log','core/notification', 'mod_readaloud/ttaudiohelper','mod_readaloud/ttbrowserrec',
    'core/str','mod_readaloud/timer','mod_readaloud/ttmsspeech'],
    function ($, log, notification, audioHelper, browserRec, str, timer, msspeech) {
    "use strict"; // jshint ;_;
    /*
    *  The TT recorder
     */

    log.debug('TT Recorder: initialising');

    return {
        waveHeight: 75,
        audio: {
            stream: null,
            blob: null,
            dataURI: null,
            start: null,
            end: null,
            isRecording: false,
            isRecognizing: false,
            isWaiting: false,
            transcript: null
        },
        submitting: false,
        owner: '',
        controls: {},
        uniqueid: null,
        audio_updated: null,
        maxtime: 0,
        passagehash: null,
        region: null,
        asrurl: null,
        lang: null,
        browserrec: null,
        usebrowserrec: false,
        currentTime: 0,
        stt_guided: false,
        currentPrompt: false,
        speechtoken: '',
        speechtokentype: '',
        forcestreaming: false,
        is_streaming: false,
        using_msspeech: false,
        strings: {},

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        init: function(opts){

            var that = this;
            this.uniqueid=opts['uniqueid'];
            this.callback=opts['callback'];
            this.stt_guided = opts['stt_guided'] ? opts['stt_guided'] : false;
            this.init_strings();
            this.prepare_html();
            this.controls.recordercontainer.show();
            this.register_events();

            //token check
            this.using_msspeech = this.can_msspeech();
            if(this.using_msspeech){
                var referencetext = opts['referencetext'];
                msspeech.init(this.speechtoken, this.region, this.lang, referencetext);
            }

            // Callback: Timer updates.
            var handle_timer_update = function(){
                var displaytime = that.timer.fetch_display_time();
                that.controls.timerstatus.html(displaytime);
                log.debug('timer_seconds: ' + that.timer.seconds);
                log.debug('displaytime: ' + displaytime);
                if (that.timer.seconds == 0 && that.timer.initseconds > 0) {
                    that.update_audio('isRecognizing', true);
                    if(that.usebrowserrec){
                        that.browserrec.stop();
                    }else{
                        that.audiohelper.stop();
                    }
                }
            };

            // Callback: Recorder device errors.
            var on_error = function(error) {
                switch (error.name) {
                    case 'PermissionDeniedError':
                    case 'NotAllowedError':
                        notification.alert("Error",that.strings.allowmicaccess, "OK");
                        break;
                    case 'DevicesNotFoundError':
                    case 'NotFoundError':
                        notification.alert("Error",that.strings.nomicdetected, "OK");
                        break;
                    default:
                        //other errors, like from Edge can fire repeatedly so a notification is not a good idea
                        //notification.alert("Error", error.name, "OK");
                        log.debug("Error", error.name);
                }
            };

            // Callback: Recording stopped.
            var on_stopped = function(blob) {
                that.timer.stop()

                //if the blob is undefined then the user is super clicking or something
                if(blob===undefined){
                    return;
                }

                //Update our current audio object
                var newaudio = {
                    blob: blob,
                    dataURI: URL.createObjectURL(blob),
                    end: new Date(),
                    isRecording: false,
                    length: Math.round((that.audio.end - that.audio.start) / 1000),
                };
                that.update_audio(newaudio);

                //if we are not streaming then upload_transcribe (ie send to poodll servers)
                if(!that.is_streaming){
                    if(that.using_msspeech){
                        that.do_msspeech(that.audio.blob, function(response){
                            that.gotMSResults(response);
                            that.update_audio('isRecognizing',false);
                        });
                    }else{
                        that.upload_transcribe(that.audio.blob, function(response){
                            log.debug(response);
                            if(response.data.result==="success" && response.data.transcript){
                                that.gotRecognition(response.data.transcript.trim());
                            } else {
                                notification.alert("Information",that.strings.speechnotrecognized, "OK");
                            }
                            that.update_audio('isRecognizing',false);
                        });
                    }
                }

            };

            // Callback: Recorder device got stream - start recording
            var on_gotstream=  function(stream) {
                var newaudio={stream: stream, isRecording: true, isWaiting: false};
                that.update_audio(newaudio);
            };

            //If browser rec (Chrome Speech Rec) 
            if(browserRec.will_work_ok() && ! this.stt_guided && !this.forcestreaming && !this.using_msspeech){
                //Init browserrec
                log.debug("using browser rec");
                this.browserrec = browserRec.clone();
                this.browserrec.init(this.lang,this.waveHeight,this.uniqueid);
                this.usebrowserrec=true;

                //set up events
                that.browserrec.onerror = on_error;
                that.browserrec.onend = function(){
                        //do something here
                };
                that.browserrec.onstart = function(){
                    //do something here
                };
                that.browserrec.onfinalspeechcapture=function(speechtext){
                    that.gotRecognition(speechtext);
                    that.update_audio('isRecording',false);
                    that.update_audio('isRecognizing',false);
                };

                that.browserrec.oninterimspeechcapture=function(speechtext){
                    that.gotInterimRecognition(speechtext);
                };

            //If we have a streaming token
            }else if( this.can_stream() && !this.stt_guided ) {
                this.is_streaming = true;
                //Init streaming audio helper
                log.debug("using audio helper and streaming rec");
                this.audiohelper =  audioHelper.clone();
                this.audiohelper.init(this.waveHeight,this.uniqueid, this);

                that.audiohelper.onError = on_error;
                that.audiohelper.onStop = on_stopped;
                that.audiohelper.onStream = on_gotstream;
                that.audiohelper.onfinalspeechcapture = function(speechtext){
                    that.gotRecognition(speechtext);
                    that.update_audio('isRecording',false);
                    that.update_audio('isRecognizing',false);
                };
                that.audiohelper.oninterimspeechcapture = function(speechtext){
                    that.gotInterimRecognition(speechtext);
                };
                
            //If upload_transcriber
            } else {
                //set up upload_transcriber
                log.debug("using upload_transcriber");
                this.audiohelper =  audioHelper.clone();
                this.audiohelper.init(this.waveHeight,this.uniqueid,this);

                that.audiohelper.onError = on_error;
                that.audiohelper.onStop = on_stopped;
                that.audiohelper.onStream = on_gotstream;

            }//end of setting up recorders

            // Setting up timer.
            this.timer = timer.clone();
            this.timer.init(this.maxtime, handle_timer_update);
            // Init the timer readout
            handle_timer_update();
        },

        can_stream: function( ){
            return (this.speechtoken && this.speechtoken !== 'false'&& this.speechtokentype == 'assemblyai' && !this.stt_guided);
        },

        can_msspeech: function( ){
            return (this.speechtoken && this.speechtoken !== 'false' && this.speechtokentype === 'msspeech');
        },

        blobToArrayBuffer: function (blob) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    resolve(event.target.result);
                };
                reader.onerror = function(error) {
                    reject(error);
                };
                reader.readAsArrayBuffer(blob);
            });
        },

        init_strings: function(){
            var that=this;
            str.get_strings([
                { "key": "allowmicaccess", "component": 'mod_readaloud'},
                { "key": "nomicdetected", "component": 'mod_readaloud'},
                { "key": "speechnotrecognized", "component": 'mod_readaloud'},

            ]).done(function (s) {
                var i = 0;
                that.strings.allowmicaccess = s[i++];
                that.strings.nomicdetected = s[i++];
                that.strings.speechnotrecognized = s[i++];
            });
        },

        prepare_html: function(){
            this.controls.recordercontainer =$('#ttrec_container_' + this.uniqueid);
            this.controls.recorderbutton = $('#' + this.uniqueid + '_recorderdiv');
            this.controls.waveform = $('#' + this.uniqueid + '_waveform');
            this.controls.timerstatus = $('.timerstatus_' + this.uniqueid);
            this.passagehash = this.controls.recorderbutton.data('passagehash');
            this.region=this.controls.recorderbutton.data('region');
            this.lang=this.controls.recorderbutton.data('lang');
            this.asrurl=this.controls.recorderbutton.data('asrurl');
            this.speechtoken=this.controls.recorderbutton.data('speechtoken');
            this.speechtokentype=this.controls.recorderbutton.data('speechtokentype');
            this.forcestreaming=this.controls.recorderbutton.data('forcestreaming');
            this.maxtime=this.controls.recorderbutton.data('maxtime');
            this.waveHeight=this.controls.recorderbutton.data('waveheight');
        },

        silence_detected: function(){
            if(this.audio.isRecording){
                this.toggleRecording();
            }
        },

        update_audio: function(newprops,val){
            if (typeof newprops === 'string') {
                log.debug('update_audio:' + newprops + ':' + val);
                if (this.audio[newprops] !== val) {
                    this.audio[newprops] = val;
                    this.audio_updated();
                }
            }else{
                for (var theprop in newprops) {
                    this.audio[theprop] = newprops[theprop];
                    log.debug('update_audio:' + theprop + ':' + newprops[theprop]);
                }
                this.audio_updated();
            }
        },

        register_events: function(){
            var that = this;
            this.controls.recordercontainer.click(function(){
                that.toggleRecording();
            });

            this.audio_updated=function() {
                //pointer
                if (that.audio.isRecognizing || that.audio.isWaiting ) {
                    that.show_recorder_pointer('none');
                } else {
                    that.show_recorder_pointer('auto');
                }
                //the color
                //we no longer swap out colors for waiting .. its too fast and a bit jarring
                if(that.audio.isRecognizing || that.audio.isRecording || that.audio.isWaiting){
                    this.controls.recorderbutton.removeClass('ttrec_ready');
                    this.controls.recorderbutton.removeClass('ttrec_waiting');
                    this.controls.waveform.removeClass('ttrec_waiting');
                    this.controls.recorderbutton.addClass('ttrec_engaged');
                }else if (that.audio.isWaiting && false) {
                    this.controls.recorderbutton.removeClass('ttrec_engaged');
                    this.controls.recorderbutton.removeClass('ttrec_ready');
                    this.controls.recorderbutton.addClass('ttrec_waiting');
                    this.controls.waveform.addClass('ttrec_waiting');
                }else{
                    this.controls.recorderbutton.removeClass('ttrec_engaged');
                    this.controls.recorderbutton.removeClass('ttrec_waiting');
                    this.controls.waveform.removeClass('ttrec_waiting');
                    this.controls.recorderbutton.addClass('ttrec_ready');
                }

                //the font awesome spinner/mic/square
                that.controls.recorderbutton.html(that.recordBtnContent());
            };

        },

        show_recorder_pointer: function(show){
            if(show) {
                this.controls.recorderbutton.css('pointer-events', 'none');
            }else{
                this.controls.recorderbutton.css('pointer-events', 'auto');
            }

        },

        gotMSResults:function(results){
            log.debug(results);
            var message={};
            message.type='pronunciation_results';
            message.results = results;
            this.callback(message);
        },

        gotRecognition:function(transcript){
            log.debug('transcript:' + transcript);
            if(transcript.trim()==''){return;}
            var message={};
            message.type='speech';
            message.capturedspeech = transcript;
            this.callback(message);
        },

        gotInterimRecognition:function(transcript){
            var message={};
            message.type='interimspeech';
            message.capturedspeech = transcript;
           //POINT
            this.callback(message);
        },

        cleanWord: function(word) {
            return word.replace(/['!"#$%&\\'()\*+,\-\.\/:;<=>?@\[\\\]\^_`{|}~']/g,"").toLowerCase();
        },

        recordBtnContent: function() {

            if(!this.audio.isRecognizing){

                if (this.audio.isRecording) {
                    return '<i class="fa fa-stop">';

                } else if(this.audio.isWaiting) {
                    return '<i class="fa fa-solid fa-cog fa-spin">';

                } else {
                    return '<i class="fa fa-microphone">';
                }
            } else {
                return '<i class="fa fa-spinner fa-spin">';
            }
        },
        toggleRecording: function() {
            var that =this;

            //If we are recognizing, then we want to discourage super click'ers
            if (this.audio.isRecognizing || this.audio.isWaiting) {
                return;
            }

            //If we are currently recording
            if (this.audio.isRecording) {
                that.timer.stop();

                //If using Browser Rec (chrome speech)
                if(this.usebrowserrec){
                    that.update_audio('isRecording',false);
                    that.update_audio('isRecognizing',true);
                    this.browserrec.stop();
                //If using upload_transcriber or streaming
                }else{
                    this.update_audio('isRecognizing',true);
                    this.audiohelper.stop();
                }

             //If we are NOT currently recording
            } else {
                // Run the timer
                that.currentTime = 0;
                that.timer.reset();
                that.timer.start();
                

                //If using Browser Rec (chrome speech)
                if(this.usebrowserrec){
                    this.update_audio('isRecording',true);
                    this.browserrec.start();

                //If using Audio helper for upload_transcriber or streaming
                }else {
                    var newaudio = {
                        stream: null,
                        blob: null,
                        dataURI: null,
                        start: new Date(),
                        end: null,
                        isRecording: false,
                        isRecognizing: false,
                        isWaiting: true,
                        transcript: null
                    };
                    this.update_audio(newaudio);
                    this.audiohelper.start();
                }
            }
        },

        upload_transcribe: function(blob, callback) {
            var bodyFormData = new FormData();
            var blobname = this.uniqueid + Math.floor(Math.random() * 100) +  '.wav';
            bodyFormData.append('audioFile', blob, blobname);
            bodyFormData.append('scorer', this.passagehash);
            if(this.stt_guided) {
                bodyFormData.append('strictmode', 'false');
            }else{
                bodyFormData.append('strictmode', 'true');
            }
            //prompt is used by whisper and other transcibers down the line
            if(this.currentPrompt!==false){
                bodyFormData.append('prompt', this.currentPrompt);
            }
            bodyFormData.append('lang', this.lang);
            bodyFormData.append('wwwroot', M.cfg.wwwroot);

            var oReq = new XMLHttpRequest();
            oReq.open("POST", this.asrurl, true);
            oReq.onUploadProgress= function(progressEvent) {};
            oReq.onload = function(oEvent) {
                if (oReq.status === 200) {
                    callback(JSON.parse(oReq.response));
                } else {
                    callback({data: {result: "error"}});
                    log.debug(oReq.error);
                }
            };
            try {
                oReq.send(bodyFormData);
            }catch(err){
                callback({data: {result: "error"}});
                log.debug(err);
            }
        },

        do_msspeech: function(blob, callback) {
            msspeech.recognize(blob,callback)
        },

    };//end of return value

});