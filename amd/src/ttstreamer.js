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
        finalwords: [],
        ready: false,
        finaltext: '',
        lang: 'en-US',
        //samplerate of the pcm we send. ttaudiohelper builds the AudioContext at this rate.
        samplerate: 16000,
        //total audio sent to the streamer since recording started, in seconds.
        //this is our audio clock, and it does not reset when the socket does.
        audioseconds: 0,
        //the audio clock reading at the moment the current socket generation opened.
        //word timings arrive relative to session start, so we add this to get passage-relative times.
        sessionoffset: 0,
        //turns are numbered per session, so we shift them past any earlier generation's turns.
        turnbase: 0,
        //highest turn_order seen on the current socket generation.
        maxturn: -1,
        //set while finish() waits for the server to flush its last turn, see finish().
        onterminated: null,
        //how long finish() waits for the server's Termination message before giving up, in ms.
        terminatetimeout: 3000,

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        init: function (speechtoken, speechregion, theaudiohelper) {
            this.speechtoken = speechtoken;
            this.audiohelper = theaudiohelper;
            this.lang = theaudiohelper.therecorder.lang;
            this.finals = [];
            this.finalwords = [];
            this.finaltext = '';
            this.audioseconds = 0;
            this.sessionoffset = 0;
            this.turnbase = 0;
            this.maxturn = -1;
            this.preparesocket();
        },

        /*
        * A socket generation is one AssemblyAI session. A token refresh closes the socket and opens a new one,
        * and the new session restarts turn_order and word timings at zero. So before each new socket we park
        * the current audio clock as the offset for the incoming session, and push turn numbering past whatever
        * the previous generation used. Without this a reading longer than the token lifetime loses its earlier
        * turns (they get overwritten) and every word timing after the refresh points back at the start of the audio.
         */
        rollgeneration: function () {
            this.sessionoffset = this.audioseconds;
            this.turnbase = this.turnbase + this.maxturn + 1;
            this.maxturn = -1;
            log.debug('TT Streamer new generation. offset=' + this.sessionoffset + 's turnbase=' + this.turnbase);
        },

        preparesocket: function () {
            var that = this;

            this.rollgeneration();

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
                            //the server has sent everything it is going to send, see finish()
                            if (that.onterminated) {
                                that.onterminated();
                            }
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
                //note: we deliberately do NOT clear finals/finaltext here. On a token refresh this fires
                //again mid-reading, and clearing would discard everything read so far. init() does the reset.
                that.audiohelper.onSocketReady('fromsocketopen');
            };

            //These handlers must only act on their own socket. After a token refresh the old socket's close event
            //arrives once the new socket is already in place, and clearing that.socket then would silently stop
            //everything after the refresh from being transcribed.
            var thissocket = this.socket;
            this.socket.onerror = (event) => {
                log.debug(event);
                if (that.socket === thissocket) {
                    that.doclosesocket();
                    if (that.onterminated) {
                        that.onterminated();
                    }
                }
            };

            this.socket.onclose = (event) => {
                log.debug(event);
                if (that.socket === thissocket) {
                    that.socket = null;
                    if (that.onterminated) {
                        that.onterminated();
                    }
                }
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

            //advance the audio clock. we count every buffer we are handed, including the ones we buffer
            //as earlyaudio, so the clock tracks the recording rather than the socket.
            this.audioseconds += stereodata[0].length / this.samplerate;

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
            //Send it off !! (but never after we have told the server we are finished)
            if (that.socket && that.socket.readyState === WebSocket.OPEN && !that.onterminated) {
                that.socket.send(audiodata);
            }
        },

        finish: function (mimeType) {
            var that = this;

            //this would be an event that occurs after recorder has stopped lets just ignore it
            if (this.ready === undefined || !this.ready) {
                return;
            }
            //already finishing
            if (this.onterminated) {
                return;
            }
            log.debug('committing universal response');

            //Terminate asks the server to flush the turn in progress, which arrives after it, followed by a Termination
            //message. Closing the socket straight away (as we used to) threw that last turn away, so a student who
            //stopped right after speaking lost their last words. So we wait for Termination, the socket closing, or a
            //timeout, whichever comes first, and only then build the transcript.
            var timer = null;
            var complete = function () {
                if (that.onterminated !== complete) {
                    return;
                }
                that.onterminated = null;
                clearTimeout(timer);
                var finaltranscript = that.buildtranscript();
                var finalwords = that.buildwords();
                log.debug('sending final speech capture event with ' + finalwords.length + ' timed words');
                that.audiohelper.onfinalspeechcapture(finaltranscript, finalwords);
                that.cleanup();
            };
            this.onterminated = complete;

            if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                log.debug('sending Terminate and waiting for the last turn');
                this.socket.send(JSON.stringify({type: 'Terminate'}));
                timer = setTimeout(complete, this.terminatetimeout);
            } else {
                complete();
            }
        },

        cancel: function () {
            //a pending finish() must not deliver a transcript after we have been cancelled
            this.onterminated = null;
            this.ready = false;
            this.earlyaudio = [];
            this.finals = [];
            this.finalwords = [];
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

            //turn_order is per session, so shift it past any earlier socket generation
            var turnorder = payload.turn_order || 0;
            if (turnorder > that.maxturn) {
                that.maxturn = turnorder;
            }
            var turnindex = that.turnbase + turnorder;

             //process finals
            that.finals[turnindex] = thistranscript;
            that.finalwords[turnindex] = that.extractwords(payload);
            that.finaltext = this.buildtranscript();
            that.audiohelper.oninterimspeechcapture(thistranscript);
            log.debug('TT Streamer final transcript update (turn ' + turnindex + '): ' + thistranscript);
        },

        /*
        * Pull word level timings out of a Turn payload. AssemblyAI v3 gives us start/end in milliseconds
        * relative to the start of the current session, so we convert to seconds and add the generation offset
        * to get times relative to the start of the recording, which is what the audio file and the grading
        * UI are indexed against.
         */
        extractwords: function (payload) {
            var that = this;
            var words = [];
            if (!payload.words || !payload.words.length) {
                return words;
            }
            for (var i = 0; i < payload.words.length; i++) {
                var w = payload.words[i];
                var text = (w.text || '').trim();
                if (text === '') {
                    continue;
                }
                words.push({
                    content: text,
                    start_time: that.sessionoffset + ((w.start || 0) / 1000),
                    end_time: that.sessionoffset + ((w.end || 0) / 1000),
                    confidence: typeof w.confidence === 'number' ? w.confidence : 1
                });
            }
            return words;
        },

        /*
        * The flat, ordered word list for the whole recording. This is what gets posted to Moodle and
        * reshaped into the transcript json that utils::fetch_audio_points_json expects.
         */
        buildwords: function () {
            var all = [];
            for (var i = 0; i < this.finalwords.length; i++) {
                var turnwords = this.finalwords[i];
                if (turnwords && turnwords.length) {
                    all = all.concat(turnwords);
                }
            }
            return all;
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