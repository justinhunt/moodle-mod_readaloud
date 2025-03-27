define(['jquery', 'core/log'], function ($, log) {
    "use strict"; // jshint ;_;
    /*
    This file is the streamer to assembly ai
     */

    log.debug('TT Streamer initialising');

    return {

        speechtoken: null,
        socket: null,
        audiohelper: null,
        earlyaudio: [],
        partials: [],
        finals: [],
        ready: false,
        finaltext: '',

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        init: function(speechtoken, theaudiohelper) {
            this.speechtoken = speechtoken;
            this.audiohelper = theaudiohelper;
            this.preparesocket();
        },

        preparesocket: async function(){
            var that = this;

            // establish wss with AssemblyAI (AAI) at 16000 sample rate
            switch(this.audiohelper.region){
                case 'frankfurt':
                case 'london':
                case 'dublin':
                    //did not work
               //     this.socket = await new WebSocket(
               //         `wss://api.eu.assemblyai.com/v2/realtime/ws?sample_rate=16000&encoding=pcm_s16le&token=${this.speechtoken}`,
                //    );
                //    break;
                default:
                    this.socket = await new WebSocket(
                        `wss://api.assemblyai.com/v2/realtime/ws?sample_rate=16000&encoding=pcm_s16le&token=${this.speechtoken}`,
                    );
            }
            log.debug('TT Streamer socket prepared');
            

            // handle incoming messages which contain the transcription
            this.socket.onmessage= function(message) {
                let msg = "";
                const res = JSON.parse(message.data);
                switch(res.message_type){
                    case 'PartialTranscript':
                        that.partials[res.audio_start] = res.text;
                        var keys = Object.keys(that.partials);
                        keys.sort((a, b) => a - b);
                        for (const key of keys) {
                            if (that.partials[key]) {
                                msg += ` ${that.partials[key]}`;
                            }
                        }
                        that.audiohelper.oninterimspeechcapture(that.finaltext + ' ' + msg);
                        break;

                    case 'FinalTranscript':
                        //clear partials if we have a final
                        that.partials = [];
                        //process finals
                        that.finals[res.audio_start] = res.text;
                        var keys = Object.keys(that.finals);
                        keys.sort((a, b) => a - b);
                        for (const key of keys) {
                            if (that.finals[key]) {
                                msg += ` ${that.finals[key]}`;
                            }
                        }
                        that.finaltext = msg;
                        //we do not send final speech capture event until the speaking session ends
                        //that.audiohelper.onfinalspeechcapture(msg);
                        that.audiohelper.oninterimspeechcapture(msg);
                        log.debug('interim (final) transcript: ' + msg);
                        break;
                    case 'SessionBegins':
                            log.debug('TT Streamer session begins');
                            that.ready = true;
                            break;      
                    case 'SessionEnds':
                            break;    
                    case 'SessionInformation':
                        break;
                    case 'RealtimeError':
                        log.debug(res.error);
                        break;    
                    default:
                        break;
                }
                log.debug(msg);
            };

            this.socket.onopen = (event) => {
                log.debug('TT Streamer socket opened');
                that.partials = [];
                that.finals = [];
                that.audiohelper.onSocketReady('fromsocketopen');
            };

            this.socket.onerror = (event) => {
                log.debug(event);
                that.socket.close();
            };

            this.socket.onclose = (event) => {
                log.debug(event);
                that.socket = null;
            };
        },

        audioprocess: function(stereodata) {
            var that = this;
            const base64data = this.binarytobase64(stereodata[0]);

            //this would be an event that occurs after recorder has stopped or before we are ready
            //session opening can be slower than socket opening, so store audio data until session is open
            if(this.ready===undefined || !this.ready){
                log.debug('TT Streamer storing base64 audio');
                this.earlyaudio.push(base64data);

            //session opened after we collected audio data, send earlyaudio first
            }else if(this.earlyaudio.length > 0 ){
                for (var i=0; i < this.earlyaudio.length; i++) {
                    this.sendaudio(this.earlyaudio[i]);
                }
                //clear earlyaudio and send the audio we just got
                this.earlyaudio = [];
                this.sendaudio(base64data);

            }else{
                //just send the audio we got
                log.debug('TT Streamer sending current audiodata');
                this.sendaudio(base64data);
            }
        },

        binarytobase64: function(monoaudiodata) {
            var that = this;

            //convert to 16 bit pcm
            var tempbuffer = []
            for (let i = 0; i < monoaudiodata.length; i++) {
                const sample = Math.max(-1, Math.min(1, monoaudiodata[i]))
                const intSample = sample < 0 ? sample * 0x8000 : sample * 0x7fff
                tempbuffer.push(intSample & 0xff)
                tempbuffer.push((intSample >> 8) & 0xff)
            }
            var sendbuffer = new Uint8Array(tempbuffer)

            // Encode binary string to base64
            var binary = '';
            for (var i = 0; i < sendbuffer.length; i++) {
                binary += String.fromCharCode(sendbuffer[i]);
            }
            var base64 = btoa(binary);
            return base64;
        },

        sendaudio: function(base64) {
            var that = this;
            //Send it off !!
            if (that.socket) {
                that.socket.send(
                    JSON.stringify({
                        audio_data: base64,
                    }),
                );
            }
        },

        finish: function(mimeType) {
            var that = this;

            //this would be an event that occurs after recorder has stopped lets just ignore it
            if(this.ready===undefined || !this.ready){
                return;
            }
            log.debug('forcing end utterance');
            //get any remanining transcription
            if (that.socket) {
                that.socket.send(
                    JSON.stringify({
                        force_end_utterance: true,
                    }),
                );
            }
            log.debug('timing out');
            setTimeout(function() {
                var msg = "";
                var sets = [that.finals,that.partials];
                for (const set of sets) {
                    var keys = Object.keys(set);
                    keys.sort((a, b) => a - b);
                    for (const key of keys) {
                        if (set[key]) {
                            msg += ` ${set[key]}`;
                        }
                    }
                }
                log.debug('sending final speech capture event');
                that.audiohelper.onfinalspeechcapture(msg);
                that.cleanup();
            }, 1000);
        },

        cancel: function() {
           this.ready = false;
           this.earlyaudio = [];
           this.partials = [];
           this.finals = [];
           this.finaltext = '';
           if(this.socket){
               this.socket.close();
           }
        },

        cleanup: function() {
            this.cancel();
        }

     };//end of return value

});