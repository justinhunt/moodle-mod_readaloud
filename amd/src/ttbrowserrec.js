/* jshint ignore:start */
define(['jquery', 'core/log', 'mod_readaloud/ttwavencoder'], function ($, log, wavencoder) {

    "use strict"; // jshint ;_;

    log.debug('mod_readaloud browser speech rec: initialising');

    return {

        recognition: null,
        recognizing: false,
        final_transcript: '',
        interim_transcript: '',
        start_timestamp: 0,
        lang: 'en-US',
        interval: 0,
        browsertype: '',
        is_android: false,
        usestream: true,
        restarts: 0,
        maxrestarts: 3,
        lastresultindex: -1,

        audioContext: null,
        processor: null,
        microphone: null,
        encoder: null,
        listener: null,

        // wav config for encoding to wav
        wavconfig: {
            bufferLen: 4096,
            numChannels: 2,
            desiredSampleRate: 48000,
            mimeType: 'audio/wav'
        },


        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        will_work_ok: function (opts) {
            //let's check if we are in an iframe
            var is_iframe = false;
            if (window.self !== window.top) {
                is_iframe = true;
            }

            //is mobileapp ?
            var is_mobileapp = false;
            if (navigator.userAgent.indexOf("MoodleMobile") > -1) {
                is_mobileapp = true;
            }

            //Brave looks like it does speech rec, but it doesn't
            var brave = typeof navigator.brave !== 'undefined';
            if (brave) {
                this.browsertype = 'brave';
            }

            //Edge may or may not work, but its hard to tell from the browser agent
            var edge = navigator.userAgent.toLowerCase().indexOf("edg/") > -1;
            if (edge && this.browsertype === '') {
                this.browsertype = 'edge';
            }

            //Safari may or may not work, but its hard to tell from the browser agent
            var has_chrome = navigator.userAgent.indexOf('Chrome') > -1;
            var has_safari = navigator.userAgent.indexOf("Safari") > -1;
            var is_ios = (navigator.userAgent.indexOf("iPhone") > -1 ||
                navigator.userAgent.indexOf("iPad") > -1);
            var safari = has_safari && !has_chrome;
            if (safari && this.browsertype === '') {
                this.browsertype = 'safari';
            }

            //This is feature detection, and for chrome it can be trusted.
            var is_android = navigator.userAgent.indexOf("Android") > -1;
            var hasspeechrec = ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window);
            if (hasspeechrec && this.browsertype === '' && has_chrome) {
                this.browsertype = 'chrome';
            }

            //This is feature detection, and for chrome it can be trusted.
            // The others might say they do speech rec, but that does not mean it works
            // we know safari in webapp does not so we nix that here
            if (is_mobileapp && is_ios) {
                return false;
            } else if (this.browsertype === 'brave') {
                return false;
            } else {
                return hasspeechrec;
            }
        },

        init: function (lang, waveheight, uniqueid) {
            var SpeechRecognition = SpeechRecognition || webkitSpeechRecognition;
            this.is_android = navigator.userAgent.indexOf("Android") > -1;
            //On android chrome the web speech api is served by the android platform recognizer, which takes the
            //microphone for itself. A concurrent getUserMedia capture then returns silence, and worse, it stops
            //the recognizer hearing anything either. So on android we run speech rec on its own, with no stream.
            //Everywhere else the stream stays, so the wav and the live waveform are unchanged.
            this.usestream = !this.is_android;
            this.recognition = new SpeechRecognition();
            this.recognition.continuous = true;
            //a bug in android chrome means it reverses isfinal true and false, so we cant use interim results
            this.recognition.interimResults = !this.is_android;
            this.lang = lang;
            this.waveHeight = waveheight;
            this.uniqueid = uniqueid;
            this.prepare_html();
            this.register_events();
            window.AudioContext = window.AudioContext || window.webkitAudioContext;
        },

        onStop: function () { },

        prepare_html: function () {
            this.canvas = $('#' + this.uniqueid + "_waveform");
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
            var that = this;

            //If we already started ignore this
            if (this.recognizing) {
                return;
            }
            this.recognizing = true;
            this.final_transcript = '';
            this.interim_transcript = '';
            this.restarts = 0;
            this.recognition.lang = this.lang;//select_dialect.value;
            this.start_recognition();
            this.start_timestamp = Date.now();//event.timeStamp;
            that.onstart();

            //Without a capture stream there is no wav to encode and no analyser to draw from,
            //so we animate a stand in waveform and leave the microphone to the recognizer.
            if (!this.usestream) {
                this.interval = setInterval(function () {
                    that.drawFakeWave();
                }, 100);
                return;
            }

            // Audio context
            this.audioContext = new AudioContext(
                {
                    sampleRate: this.wavconfig.desiredSampleRate
                });

            this.processor = this.audioContext.createScriptProcessor(
                this.wavconfig.bufferLen,
                this.wavconfig.numChannels,
                this.wavconfig.numChannels);

            this.processor.connect(this.audioContext.destination);

            var gotStreamMethod = function (stream) {
                if (!that.recognizing) {
                    stream.getTracks().forEach(track => track.stop());
                    return;
                }
                if (that.audioContext.state === 'suspended') {
                    that.audioContext.resume();
                }
                that.tracks = stream.getTracks();

                // Create a MediaStreamAudioSourceNode for the microphone
                that.microphone = that.audioContext.createMediaStreamSource(stream);

                // Connect the AudioBufferSourceNode to the gainNode
                that.microphone.connect(that.processor);

                // Init WAV encoder
                that.encoder = wavencoder.clone();
                that.encoder.init(that.audioContext.sampleRate, that.wavconfig.numChannels);

                // Give the node a function to process audio events
                that.processor.onaudioprocess = function (event) {
                    var thebuffers = that.getBuffers(event);
                    that.encoder.audioprocess(thebuffers);
                };

                that.listener = that.audioContext.createAnalyser();
                that.microphone.connect(that.listener);
                that.listener.fftSize = 256;

                that.bufferLength = that.listener.frequencyBinCount;
                that.analyserData = new Uint8Array(that.bufferLength);

                //kick off animation
                that.interval = setInterval(function () {
                    that.drawWave();
                }, 100);
            };

            // Mic permission
            navigator.mediaDevices.getUserMedia({
                audio: true,
                video: false
            }).then(gotStreamMethod).catch(function (error) {
                log.debug(error);
                that.onerror(error);
            });
        },

        stop: function () {
            var that = this;
            this.recognizing = false;
            this.recognition.stop();
            clearInterval(this.interval);

            if (this.audioContext !== null && this.audioContext.state !== "closed") {
                this.audioContext.close();
            }
            if (this.processor) {
                this.processor.disconnect();
            }
            if (this.tracks) {
                this.tracks.forEach(track => track.stop());
            }
            //With no encoder there is no audio to hand back, but the listener still needs to know we stopped
            if (this.encoder) {
                this.onStop(this.encoder.finish());
            } else {
                this.onStop();
            }

            this.canvasCtx.clearRect(0, 0, this.canvas.width() * 2, this.waveHeight * 2);
            setTimeout(function () {
                that.onfinalspeechcapture(that.final_transcript);
            }, 1000);
            this.onend();
        },

        register_events: function () {

            var recognition = this.recognition;
            var that = this;

            recognition.onerror = function (event) {
                if (event.error == 'no-speech') {
                    log.debug('info_no_speech');
                }
                if (event.error == 'audio-capture') {
                    log.debug('info_no_microphone');
                }
                if (event.error == 'not-allowed') {
                    if (event.timeStamp - that.start_timestamp < 100) {
                        log.debug('info_blocked');
                    } else {
                        log.debug('info_denied');
                    }
                }
                that.onerror({ error: { name: event.error } });
            };

            recognition.onend = function () {
                if (!that.recognizing) {
                    return;
                }
                //Android ignores "continuous" and ends the session after each utterance. Every restart
                //sounds the platform earcon and re-grabs the microphone, so we only restart while we have
                //heard nothing at all, to give a slow starter a second chance, and only a few times.
                if (that.is_android) {
                    if (that.final_transcript !== '' || that.restarts >= that.maxrestarts) {
                        return;
                    }
                    that.restarts++;
                }
                that.start_recognition();
            };

            recognition.onresult = function (event) {
                for (var i = event.resultIndex; i < event.results.length; ++i) {

                    // a bug on android chrome means it reverses isfinal true and false. Everything it sends us
                    // is really final, so we take the lot, skipping any index we have already banked.
                    if (that.is_android) {
                        if (i <= that.lastresultindex) {
                            continue;
                        }
                        that.lastresultindex = i;
                        that.final_transcript += event.results[i][0].transcript + ' ';
                        that.oninterimspeechcapture(that.final_transcript);
                        continue;
                    }

                    if (event.results[i].isFinal) {
                        that.final_transcript += event.results[i][0].transcript + ' ';
                    } else {
                        var provisional_transcript = that.final_transcript + event.results[i][0].transcript;
                        //the interim and final events do not arrive in sequence, we dont want the length going down, its weird
                        //so just dont respond when the sequence is wonky
                        if (provisional_transcript.length < that.interim_transcript.length) {
                            return;
                        } else {
                            that.interim_transcript = provisional_transcript;
                        }
                        that.oninterimspeechcapture(that.interim_transcript);
                    }
                }

            };
        },//end of register events

        //Each recognition session numbers its results from zero, so the seen-index guard resets with it.
        start_recognition: function () {
            this.lastresultindex = -1;
            try {
                this.recognition.start();
            } catch (e) {
                //Starting again before the previous session has finished shutting down throws, and is harmless
                log.debug('browser speech rec could not start: ' + e.name);
            }
        },

        getBuffers: function (event) {
            var buffers = [];
            for (var ch = 0; ch < this.wavconfig.numChannels; ++ch) {
                buffers[ch] = event.inputBuffer.getChannelData(ch);
            }
            return buffers;
        },

        drawWave: function () {

            var width = this.canvas.width() * 2;
            this.listener.getByteTimeDomainData(this.analyserData);
            // Set canvas white
            // transparent sadly, doesn't clear the previous stroke 'rgba(0, 0, 0, 0)';
            this.canvasCtx.fillStyle = '#FFFFFF';
            this.canvasCtx.fillRect(0, 0, width, this.waveHeight * 2);

            this.canvasCtx.lineWidth = 5;
            this.canvasCtx.strokeStyle = 'gray';
            this.canvasCtx.beginPath();

            var slicewaveWidth = width / this.bufferLength;
            var x = 0;

            for (var i = 0; i < this.bufferLength; i++) {

                var v = this.analyserData[i] / 128.0;
                var y = v * this.waveHeight;

                if (i === 0) {
                    this.canvasCtx.moveTo(x, y);
                } else {
                    this.canvasCtx.lineTo(x, y);
                }
                x += slicewaveWidth;
            }

            this.canvasCtx.lineTo(width, this.waveHeight);
            this.canvasCtx.stroke();

        },

        //Used when we have no microphone stream of our own to read levels from. It shows the recorder is live,
        //it just can not show how loud the speaker is.
        drawFakeWave: function () {

            var width = this.canvas.width() * 2;
            var elapsed = (Date.now() - this.start_timestamp) / 1000;

            this.canvasCtx.fillStyle = '#FFFFFF';
            this.canvasCtx.fillRect(0, 0, width, this.waveHeight * 2);

            this.canvasCtx.lineWidth = 5;
            this.canvasCtx.strokeStyle = 'gray';
            this.canvasCtx.beginPath();

            var steps = 64;
            var stepwidth = width / steps;
            var x = 0;

            for (var i = 0; i <= steps; i++) {
                //a travelling sine, tapered at both ends so it sits inside the canvas
                var taper = Math.sin((i / steps) * Math.PI);
                var y = this.waveHeight + Math.sin((i / 4) - (elapsed * 6)) * this.waveHeight * 0.5 * taper;
                if (i === 0) {
                    this.canvasCtx.moveTo(x, y);
                } else {
                    this.canvasCtx.lineTo(x, y);
                }
                x += stepwidth;
            }

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
        onfinalspeechcapture: function (speechtext) {
            log.debug(speechtext);
        },
        oninterimspeechcapture: function (speechtext) {
            // log.debug(speechtext);
        }

    };//end of returned object
});//total end
