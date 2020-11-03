define(['jquery', 'core/log', 'mod_readaloud/ttaudiohelper', 'core/notification'], function ($, log, audioHelper, notification) {
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
            transcript: null
        },
        submitting: false,
        owner: '',
        controls: {},
        uniqueid: null,
        audio_updated: null,
        maxTime: 15000,
        passagehash: null,
        region: null,
        asrurl: null,

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        init: function(opts){

            var that = this;

            this.uniqueid=opts['uniqueid'];
            this.callback=opts['callback'];
            this.prepare_html();

            this.controls.recordercontainer.show();

            this.register_events();
            this.audiohelper =  audioHelper.clone();
            this.audiohelper.init(this.waveHeight,this.uniqueid,this);

            var on_gotstream=  function(stream) {

                var newaudio={stream: stream, isRecording: true};
                that.update_audio(newaudio);
                that.currentTime = 0;

                that.interval = setInterval(function() {
                    if (that.currentTime < that.maxTime) {
                        that.currentTime += 10;
                    } else {
                        that.update_audio('isRecognizing',true);
                        // vm.isRecognizing = true;
                        that.audiohelper.stop();
                    }
                }, 10);

            };

            var on_error = function(error) {
                switch (error.name) {
                    case 'PermissionDeniedError':
                    case 'NotAllowedError':
                        notification.alert("Error",'Please allow access to your microphone!', "OK");
                        break;
                    case 'DevicesNotFoundError':
                    case 'NotFoundError':
                        notification.alert("Error",'No microphone detected!', "OK");
                        break;
                }
            };

            var on_stopped = function(blob) {
                clearInterval(that.interval);

                var newaudio = {
                    blob: blob,
                    dataURI: URL.createObjectURL(blob),
                    end: new Date(),
                    isRecording: false,
                    length: Math.round((that.audio.end - that.audio.start) / 1000),
                };
                that.update_audio(newaudio);

                that.deepSpeech2(that.audio.blob, function(response){
                    log.debug(response);
                    that.update_audio('isRecognizing',false);
                    if(response.data.result==="success" && response.data.transcript){
                        that.gotRecognition(response.data.transcript.trim());
                    } else {
                        notification.alert("Information","We could not recognize your speech.", "OK");
                    }
                });

            };

            that.audiohelper.onError = on_error;
            that.audiohelper.onStop = on_stopped;
            that.audiohelper.onStream = on_gotstream;
        },

        prepare_html: function(){
            this.controls.recordercontainer =$('#ttrec_container_' + this.uniqueid);
            this.controls.recorderbutton = $('#ttrec_' + this.uniqueid + '_recorderdiv');
            this.passagehash =this.controls.recorderbutton.data('passagehash');
            this.region=this.controls.recorderbutton.data('region');
            this.asrurl=this.controls.recorderbutton.data('asrurl');
            this.maxTime=this.controls.recorderbutton.data('maxtime');
            this.waveHeight=this.controls.recorderbutton.data('waveheight');
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
                if (that.audio.isRecognizing || that.isComplete()) {
                    that.show_recorder_pointer('none');
                } else {
                    that.show_recorder_pointer('auto');
                }

                //background WHEN?
                if (that.isComplete()) {
                    that.show_recorder_complete(true);
                } else {
                    that.show_recorder_complete(false);
                }

                //div content WHEN?
                that.controls.recorderbutton.html(that.recordBtnContent());
            }

        },

        show_recorder_pointer: function(show){
            if(show) {
                this.controls.recorderbutton.css('pointer-events', 'none');
            }else{
                this.controls.recorderbutton.css('pointer-events', 'auto');
            }

        },
        show_recorder_complete: function(show){
            if(show) {
                this.controls.recorderbutton.css('background', 'green');
            }else{
                this.controls.recorderbutton.css('background', '#e52');
            }
        },

        gotRecognition:function(transcript){
            log.debug('transcript');
            var message={};
            message.type='speech';
            message.capturedspeech = transcript;
           //POINT
            this.callback(message);
        },

        cleanWord: function(word) {
            return word.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
        },

        recordBtnContent: function() {

            if(!this.audio.isRecognizing){
                if (!this.isComplete()) {
                    if (this.audio.isRecording) {
                        return '<i class="fa fa-stop">';
                    } else {
                        return '<i class="fa fa-microphone">';
                    }
                } else {
                    return '<i class="fa fa-check">';
                }
            } else {
                return '<i class="fa fa-spinner fa-spin">';
            }
        },
        toggleRecording: function() {
            if (this.audio.isRecording) {
                this.update_audio('isRecognizing',true);
                this.audiohelper.stop();
            } else {
                this.start();
            }
        },

        start: function() {
            var newaudio = {
                stream: null,
                blob: null,
                dataURI: null,
                start: new Date(),
                end: null,
                isRecording: false,
                isRecognizing:false,
                transcript: null
            };
            this.update_audio(newaudio);
            this.audiohelper.start();
        },



        deepSpeech2: function(blob, callback) {
            var bodyFormData = new FormData();
            var blobname = this.uniqueid + Math.floor(Math.random() * 100) +  '.wav';
            bodyFormData.append('audioFile', blob, blobname);
            bodyFormData.append('scorer', this.passagehash);

            var oReq = new XMLHttpRequest();
            oReq.open("POST", this.asrurl, true);
            oReq.onUploadProgress= function(progressEvent) {};
            oReq.onload = function(oEvent) {
                if (oReq.status === 200) {
                    callback(JSON.parse(oReq.response));
                } else {
                    console.error(oReq.error);
                }
            };
            oReq.send(bodyFormData);

        },

        //not really OK here, this is something else
        isComplete: function() {
            return false;
        },
    };//end of return value

});