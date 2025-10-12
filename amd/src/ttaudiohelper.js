define(['jquery', 'core/log', 'mod_readaloud/ttwavencoder', 'mod_readaloud/ttstreamer'],
    function ($, log, wavencoder, audiostreamer) {
    "use strict"; // jshint ;_;
    /*
    This file is the engine that drives audio rec and canvas drawing. TT Recorder is the just the glory kid
     */

    log.debug('TT Audio Helper initialising');

    return {
        encodingconfig: null,
        streamer: null,
        encoder: null,
        microphone: null,
        isRecording: false,
        audioContext: null,
        processor: null,
        uniqueid: null,
        alreadyhadsound: false, //only start silence detection after we got a sound. Silence detection is end of speech.
        silencecount: 0, //how many intervals of consecutive silence so far
        silenceintervals: 15, //how many consecutive silence intervals (100ms) = silence detected
        silencelevel: 25, //below this volume level = silence
        enablesilencedetection: true,

        // wav config for encoding to wav
        wavconfig: {
            bufferLen: 4096,
            numChannels: 2,
            desiredSampleRate: 48000,
            mimeType: 'audio/wav'
        },
        //streaming config for encoding to pcm and later base64
        // TO DO: wav config might work just as well. test.
        streamingconfig: {
            bufferLen: 4096,
            numChannels: 1,
            desiredSampleRate: 16000,
            mimeType: 'audio/wav'
        },

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },


        init: function(waveHeight, uniqueid, therecorder) {

            this.waveHeight = waveHeight;
            this.uniqueid=uniqueid;
            this.therecorder= therecorder;
            this.region = therecorder.region;
            if(this.therecorder.is_streaming){
                this.encodingconfig = this.streamingconfig;
            } else {
                this.encodingconfig = this.wavconfig;
            }
            this.prepare_html();
            window.AudioContext = window.AudioContext || window.webkitAudioContext;
        },

        onStop: function() {},
        onStream: function() {},
        onSocketReady: function() {},
        onError: function() {},
        onfinalspeechcapture: function (speechtext) {},
        oninterimspeechcapture: function (speechtext) {},


        prepare_html: function(){
            // Just get the canvas reference during init
            // Canvas context will be initialized when recording starts (in start method)
            this.canvas =$('#' + this.uniqueid + "_waveform");
            this.canvasCtx = null;
        },

        start: function() {

            var that =this;

            // Initialize canvas context now that we're sure the element exists
            // (User has clicked the record button, so the template is definitely rendered)
            this.canvas = $('#' + this.uniqueid + "_waveform");
            if (this.canvas.length > 0) {
                this.canvasCtx = this.canvas[0].getContext("2d");
            } else {
                log.debug("TT Audio Helper: Canvas element not found for " + this.uniqueid);
                return;
            }

            // Audio context
            this.audioContext = new AudioContext(
                {
                    sampleRate: this.encodingconfig.desiredSampleRate
                });

            this.processor = this.audioContext.createScriptProcessor(
                this.encodingconfig.bufferLen,
                this.encodingconfig.numChannels,
                this.encodingconfig.numChannels);

            this.processor.connect(this.audioContext.destination);


            var gotStreamMethod= function(stream) {

                that.isRecording = true;
                that.tracks = stream.getTracks();

                //lets check the noise suppression and echo reduction on these
                for(var i=0; i<that.tracks.length; i++){
                    var track = that.tracks[i];
                    if(track.kind == "audio"){
                        var settings = track.getSettings();
                        if(settings.noiseSuppression){
                            log.debug("Noise Suppression is on");
                        }else{
                            log.debug("Noise Suppression is off");
                        }
                        if(settings.echoCancellation){
                            log.debug("Echo Cancellation is on");
                        }else{
                            log.debug("Echo Cancellation is off");
                        }
                    }
                }

                // Create a MediaStreamAudioSourceNode for the microphone
                that.microphone = that.audioContext.createMediaStreamSource(stream);

                // Connect the AudioBufferSourceNode to the gainNode
                that.microphone.connect(that.processor);

                //if we have a streaming transcriber we need to initialize it
                if(that.therecorder.is_streaming){
                    that.streamer = audiostreamer.clone();
                    that.streamer.init(that.therecorder.speechtoken, that);
                    that.enablesilencedetection = false;
                }
                //Alert TT recorder that we are ready to go (it will do visuals and manage state of recorder)
                that.onStream(stream);

                // Init WAV encoder
                that.encoder = wavencoder.clone();
                that.encoder.init(that.audioContext.sampleRate, that.encodingconfig.numChannels);

                // Give the node a function to process audio events
                that.processor.onaudioprocess = function(event) {
                    var thebuffers = that.getBuffers(event);
                    that.encoder.audioprocess(thebuffers);
                    if(that.streamer){
                        that.streamer.audioprocess(thebuffers);
                    }
                };

                that.listener = that.audioContext.createAnalyser();
                that.microphone.connect(that.listener);
                that.listener.fftSize = 2048; // 256

                that.bufferLength = that.listener.frequencyBinCount;
                that.analyserData = new Uint8Array(that.bufferLength);
                that.volumeData = new Uint8Array(that.bufferLength);

                //reset canvas and silence detection
                that.canvasCtx.clearRect(0, 0, that.canvas.width()*2, that.waveHeight*2);
                that.alreadyhadsound= false;
                that.silencecount= 0;

                that.interval = setInterval(function() {
                    that.drawWave();
                    that.detectSilence();
                }, 100);

            };

            //for ios we need to do this to keep playback volume high
            if ("audioSession" in navigator) {
                navigator.audioSession.type = 'play-and-record';
                console.log("AudioSession API is supported");
            }

            // Mic permission
            navigator.mediaDevices.getUserMedia({
                audio: true,
                video: false
            }).then(gotStreamMethod).catch(this.onError);
        },

        stop: function() {
            var that = this;
            clearInterval(this.interval);
            this.canvasCtx.clearRect(0, 0, this.canvas.width()*2, this.waveHeight * 2);
            this.isRecording = false;
            this.silencecount=0;
            this.alreadyhadsound=false;
            this.therecorder.update_audio('isRecording',false);
            //we set a timeout to allow the audiocontext buffer to fill up since we can't flush it
            //if we don't we may miss 1s of audio at the end
            setTimeout(function() {
                //we check audiocontext is not in an odd state before closing
                //superclickers can get it in an odd state
                if (that.audioContext!==null && that.audioContext.state !== "closed") {
                    that.audioContext.close();
                }
                that.processor.disconnect();
                that.tracks.forEach(track => track.stop());
                that.onStop(that.encoder.finish());
                if(that.streamer){
                    that.streamer.finish();
                }
            },1000);

        },

        getBuffers: function(event) {
            var buffers = [];
            for (var ch = 0; ch < this.encodingconfig.numChannels; ++ch) {
                buffers[ch] = event.inputBuffer.getChannelData(ch);
            }
            return buffers;
        },

        detectSilence: function () {

            if(!this.enablesilencedetection){return;}

            this.listener.getByteFrequencyData(this.volumeData);

            let sum = 0;
            for (var vindex =0; vindex <this.volumeData.length;vindex++) {
                sum += this.volumeData[vindex] * this.volumeData[vindex];
            }

            var volume = Math.sqrt(sum / this.volumeData.length);
           // log.debug("volume: " + volume + ', hadsound: ' + this.alreadyhadsound);
            //if we already had a sound, we are looking for end of speech
            if(volume < this.silencelevel && this.alreadyhadsound){
                this.silencecount++;
                if(this.silencecount>=this.silenceintervals){
                    this.therecorder.silence_detected();
                }
            //if we have a sound, reset silence count to zero, and flag that we have started
            }else if(volume > this.silencelevel){
                this.alreadyhadsound = true;
                this.silencecount=0;
            }
        },

        drawWave: function() {

            var width = this.canvas.width() * 2;
            this.listener.getByteTimeDomainData(this.analyserData);

            this.canvasCtx.fillStyle = 'white';
            this.canvasCtx.fillRect(0, 0, width, this.waveHeight*2);

            this.canvasCtx.lineWidth = 5;
            this.canvasCtx.strokeStyle = 'gray';
            this.canvasCtx.beginPath();

            var slicewaveWidth = width / this.bufferLength;
            var x = 0;

            for (var i = 0; i < this.bufferLength; i++) {

                var v = this.analyserData[i] / 128.0;
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

        }
    }; //end of this declaration


});