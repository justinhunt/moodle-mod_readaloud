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
        finals: [],
        ready: false,
        finaltext: '',
        lang: 'en-US',

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        init: function (speechtoken, speechregion, theaudiohelper) {
            this.speechtoken = speechtoken;
            this.audiohelper = theaudiohelper;
            this.lang = theaudiohelper.therecorder.lang;
            this.preparesocket();
        },

        preparesocket: function () {
            var that = this;

            // establish wss with AssemblyAI Universal Streaming at 16000 sample rate
            var basehost = 'wss://streaming.assemblyai.com';
            switch (this.audiohelper.region) {
                case 'frankfurt':
                case 'london':
                case 'dublin':
                    basehost = 'wss://streaming.eu.assemblyai.com';
                    break;
                default:
                    basehost = 'wss://streaming.assemblyai.com';
            }

            // First two chars of lang code
            // Assembly AI is english or autodetect ... urgh
            var themodel = "universal-streaming-english";
            var shortlang = this.lang.slice(0, 2);
            switch(shortlang){
                case "en":
                    themodel = "universal-streaming-english";
                    break;
                default:
                    themodel = "universal-streaming-multilingual";
            }

            var query = 'sample_rate=16000&encoding=pcm_s16le&speech_model=' + themodel + '&token=' + this.speechtoken;
            //encodeURIComponent(this.speechtoken);
            var url = `${basehost}/v3/ws?${query}`;
            this.ready = false;
            this.socket = new WebSocket(url);
            log.debug('TT Streamer socket prepared');


            // handle incoming messages which contain the transcription
            this.socket.onmessage = function (message) {
                try {    
                    const payload = JSON.parse(message.data);
                    const eventType = payload.type || payload.message_type;
                    log.debug('TT Streamer message type: ' + eventType);
                    switch (eventType) {
                        //case 'session.created':
                        case 'Begin':
                            that.handlesessioncreated();
                            break;

                        case 'Turn':
                            that.handlefinalresponse(payload);
                            break;
                        case 'Termination':
                            //Do something on termination if we need to
                            break;    
            
                        default:
                            break;
                    }
                } catch (error) {
                    log.debug(`\nError handling message: ${error}`);
                    log.debug(`Message data: ${message}`);
                }
            };

            this.socket.onopen = (event) => {
                log.debug('TT Streamer socket opened');
                that.finaltext = '';
                that.finals = [];
                that.audiohelper.onSocketReady('fromsocketopen');
            };

            this.socket.onerror = (event) => {
                log.debug(event);
                that.doclosesocket();
            };

            this.socket.onclose = (event) => {
                log.debug(event);
                that.socket = null;
            };
        },

        updatetoken: function (newtoken) {
            var that = this;
            if (that.socket) {
                that.doclosesocket();
            }
            that.speechtoken = newtoken;
            that.preparesocket();
        },

        audioprocess: function (stereodata) {
            var that = this;
            var int16data = this.convertflattoint16(stereodata[0]);

            //this would be an event that occurs after recorder has stopped or before we are ready
            //session opening can be slower than socket opening, so store audio data until session is open
            if (this.ready === undefined || !this.ready) {
                log.debug('TT Streamer storing audio');
                this.earlyaudio.push(int16data);

                //session opened after we collected audio data, send earlyaudio first
            } else if (this.earlyaudio.length > 0) {
                for (var i = 0; i < this.earlyaudio.length; i++) {
                    this.sendaudio(this.earlyaudio[i]);
                }
                //clear earlyaudio and send the audio we just got
                this.earlyaudio = [];
                this.sendaudio(int16data);

            } else {
                //just send the audio we got
                // log.debug('TT Streamer sending current audiodata');
                this.sendaudio(int16data);
            }
        },

        convertflattoint16: function (monoaudiodata) {
            var that = this;

            //convert to 16 bit pcm
            var tempbuffer = []
            for (let i = 0; i < monoaudiodata.length; i++) {
                const sample = Math.max(-1, Math.min(1, monoaudiodata[i]))
                const intSample = sample < 0 ? sample * 0x8000 : sample * 0x7fff
                tempbuffer.push(intSample & 0xff)
                tempbuffer.push((intSample >> 8) & 0xff)
            }
            return new Uint8Array(tempbuffer);
        },

        sendaudio: function (audiodata) {
            var that = this;
            //Send it off !!
            if (that.socket && that.socket.readyState === WebSocket.OPEN) {
                that.socket.send(audiodata);
            }
        },

        finish: function (mimeType) {
            var that = this;

            //this would be an event that occurs after recorder has stopped lets just ignore it
            if (this.ready === undefined || !this.ready) {
                return;
            }
            log.debug('committing universal response');
            
            this.doclosesocket();
              
           
            log.debug('setting time out to build transcript');
            setTimeout(function () {
                var finaltranscript = that.buildtranscript();
                log.debug('sending final speech capture event');
                that.audiohelper.onfinalspeechcapture(finaltranscript);
                that.cleanup();
            }, 1000);
        },

        cancel: function () {
            this.ready = false;
            this.earlyaudio = [];
            this.finals = {};
            this.finaltext = '';
            if (this.socket) {
                this.doclosesocket();
            }
        },

        cleanup: function () {
            this.cancel();
        },

        doclosesocket: function (){
              var that = this;
             // Close WebSocket connection if it's open
            if (that.socket && [WebSocket.OPEN, WebSocket.CONNECTING].includes(that.socket.readyState)) {
                try {
                    // Send termination message if possible
                    if (that.socket.readyState === WebSocket.OPEN) {
                        const terminateMessage = { type: "Terminate" };
                        console.log(
                        `Sending termination message: ${JSON.stringify(terminateMessage)}`
                        );
                        that.socket.send(JSON.stringify(terminateMessage));
                    }
                    that.socket.close();
                } catch (error) {
                    console.error(`Error closing WebSocket: ${error}`);
                }
                that.socket = null;
            }
        },

        handlesessioncreated: function () {
            var that = this;
            log.debug('TT Streamer session created');
            this.ready = true;
            if (this.earlyaudio.length > 0) {
                for (var i = 0; i < this.earlyaudio.length; i++) {
                    this.sendaudio(this.earlyaudio[i]);
                }
                this.earlyaudio = [];
            }
            this.audiohelper.onSocketReady('fromsessioncreated');
        },


        handlefinalresponse: function (payload) {
            var that = this;
            var thistranscript = payload.transcript || "";
             //process finals
            that.finals[payload.turn_order] = thistranscript;
            that.finaltext = this.buildtranscript();
            that.audiohelper.oninterimspeechcapture(thistranscript);
            log.debug('TT Streamer final transcript update: ' + thistranscript);
        },


        

        buildtranscript: function () {
            var combined = '';
            for (var i = 0; i < this.finals.length; i++) {
                var text = this.finals[i];
                if (text) {
                    combined += (combined ? ' ' : '') + text;
                }
            }
            return combined.trim();
        }

    };//end of return value

});