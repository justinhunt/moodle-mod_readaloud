/* jshint ignore:start */
define(['jquery', 'core/log'], function ($, log) {

    "use strict"; // jshint ;_;

    log.debug('speech_browser: initialising');

    return {

        recognition: null,
        recognizing: false,
        ignore_onend: false,
        final_transcript: '',
        start_timestamp: 0,
        lang: 'en-US',
        interval: 0,


        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        will_work_ok: function(opts){
            return 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window;
        },

        init: function (lang,waveheight,uniqueid) {
            var SpeechRecognition = SpeechRecognition || webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            this.lang = lang;
            this.waveHeight = waveheight;
            this.uniqueid = uniqueid;
            this.prepare_html();
            this.register_events();
        },

        prepare_html: function(){
            this.canvas =$('#' + this.uniqueid + "_waveform");
            this.canvasCtx = this.canvas[0].getContext("2d");
        },

        set_grammar: function (grammar) {
            var SpeechGrammarList = SpeechGrammarList || webkitSpeechGrammarList;
            if (SpeechGrammarList) {
                var speechRecognitionList = new SpeechGrammarList();
                speechRecognitionList.addFromString(grammar, 1);
                this.recognition.grammars = speechRecognitionList;
            }
        },

        start: function () {
            var that =this;

            //If we already started ignore this
            if (this.recognizing) {
                return;
            }
            this.recognizing = true;
            this.final_transcript = '';
            this.recognition.lang = this.lang;//select_dialect.value;
            this.recognition.start();
            this.ignore_onend = false;
            this.start_timestamp = Date.now();//event.timeStamp;

            //kick off animation
            that.interval = setInterval(function() {
                that.drawWave();
            }, 100);


        },
        stop: function () {
            this.recognizing = false;
            this.recognition.stop();
            clearInterval(this.interval);
            this.canvasCtx.clearRect(0, 0, this.canvas.width()*2, this.waveHeight * 2);

        },

        register_events: function () {

            var recognition = this.recognition;
            var that = this;

            recognition.onstart = function () {
                that.recognizing = true;
                that.onstart();

            };
            recognition.onerror = function (event) {
                if (event.error == 'no-speech') {
                    log.debug('info_no_speech');
                    that.ignore_onend = true;
                }
                if (event.error == 'audio-capture') {
                    log.debug('info_no_microphone');
                    that.ignore_onend = true;
                }
                if (event.error == 'not-allowed') {
                    if (event.timeStamp - that.start_timestamp < 100) {
                        log.debug('info_blocked');
                    } else {
                        log.debug('info_denied');
                    }
                    that.ignore_onend = true;
                }
                that.onerror();
            };
            recognition.onend = function () {
                //that.recognizing = false;

                // we restart by default
                // we might need to be more clever here
                if (that.recognizing == false) {
                    return;
                }
                if (that.ignore_onend) {
                    that.recognizing = false;
                } else {
                    recognition.start();
                }
                that.onend();

            };
            recognition.onresult = function (event) {
                var interim_transcript = '';
                for (var i = event.resultIndex; i < event.results.length; ++i) {
                    if (event.results[i].isFinal) {
                        that.final_transcript += event.results[i][0].transcript;
                        that.onfinalspeechcapture(that.final_transcript,JSON.stringify(event.results));
                        that.final_transcript = '';
                    } else {
                        interim_transcript += event.results[i][0].transcript;
                        that.oninterimspeechcapture(interim_transcript);
                    }
                }

            };
        },//end of register events

        drawWave: function() {

            var width = this.canvas.width() * 2;
            var bufferLength=4096;

            this.canvasCtx.fillStyle = 'white';
            this.canvasCtx.fillRect(0, 0, width, this.waveHeight*2);

            this.canvasCtx.lineWidth = 5;
            this.canvasCtx.strokeStyle = 'gray';
            this.canvasCtx.beginPath();

            var slicewaveWidth = width / bufferLength;
            var x = 0;

            for (var i = 0; i < bufferLength; i++) {

                var v = ((Math.random() * 64) + 96) / 128.0;
                var y = v * this.waveHeight;

                if (i === 0) {
                    // this.canvasCtx.moveTo(x, y);
                } else {
                    this.canvasCtx.lineTo(x, y);
                }
                x += slicewaveWidth;
            }

            this.canvasCtx.lineTo(width, this.waveHeight);
            this.canvasCtx.stroke();

        },

        onstart: function () {
            log.debug('started');
        },
        onerror: function () {
            log.debug('error');
        },
        onend: function () {
            log.debug('end');
        },
        onfinalspeechcapture: function (speechtext,speechresults) {
            log.debug(speechtext);
        },
        oninterimspeechcapture: function (speechtext) {
            // log.debug(speechtext);
        }

    };//end of returned object
});//total end