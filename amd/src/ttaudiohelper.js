define(['jquery', 'core/log', 'mod_readaloud/ttwavencoder'], function ($, log, wavencoder) {
    "use strict"; // jshint ;_;
    /*
    This file is the engine that drives audio rec and canvas drawing. TT Recorder is the just the glory kid
     */

    log.debug('TT Audio Helper initialising');

    return {
        encoder: null,
        microphone: null,
        isRecording: false,
        audioContext: null,
        processor: null,
        uniqueid: null,

        config: {
            bufferLen: 4096,
            numChannels: 2,
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
            this.prepare_html();


            window.AudioContext = window.AudioContext || window.webkitAudioContext;

        },

        onStop: function() {},
        onStream: function() {},
        onError: function() {},


        prepare_html: function(){
            this.canvas =$('#' + this.uniqueid + "_waveform");
            this.canvasCtx = this.canvas[0].getContext("2d");
        },

        start: function(shadow) {

            var that =this;

            // Audio context
            this.audioContext = new AudioContext();
            if (this.audioContext.createJavaScriptNode) {
                this.processor = this.audioContext.createJavaScriptNode(this.config.bufferLen, this.config.numChannels, this.config.numChannels);
            } else if (this.audioContext.createScriptProcessor) {
                this.processor = this.audioContext.createScriptProcessor(this.config.bufferLen, this.config.numChannels, this.config.numChannels);
            } else {
                log.debug('WebAudio API has no support on this browser.');
            }
            this.processor.connect(this.audioContext.destination);


            var gotStreamMethod= function(stream) {
                that.onStream(stream);
                that.isRecording = true;
                that.therecorder.update_audio('isRecording',true);
                that.tracks = stream.getTracks();

                //lets check the noise suppression and echo reduction on thise
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
                that.encoder = wavencoder.clone();
                that.encoder.init(that.audioContext.sampleRate, 2);

                // Give the node a function to process audio events
                that.processor.onaudioprocess = function(event) {
                    that.encoder.encode(that.getBuffers(event));
                };

                that.listener = that.audioContext.createAnalyser();
                that.microphone.connect(that.listener);
                that.listener.fftSize = 2048; // 256

                that.bufferLength = that.listener.frequencyBinCount;
                that.analyserData = new Uint8Array(that.bufferLength);

                that.canvasCtx.clearRect(0, 0, that.canvas.width()*2, that.waveHeight*2);

                that.interval = setInterval(function() {
                    that.drawWave();
                }, 100);

            };



            // Mic permission
            var audioconstraints = true;
            log.debug("Shadow is " + shadow);
            if(shadow===true){
                audioconstraints =  {
                    echoCancellation: false,
                    noiseSuppression: false
                }
            }

            //for ios we need to do this to keep playback volume high
            if ("audioSession" in navigator) {
                navigator.audioSession.type = 'play-and-record';
                console.log("AudioSession API is supported");
            }

            //get media stream
            navigator.mediaDevices.getUserMedia({
                audio:  audioconstraints,
                video: false,

            }).then(gotStreamMethod).catch(this.onError);
        },

        stop: function() {
            clearInterval(this.interval);
            this.canvasCtx.clearRect(0, 0, this.canvas.width()*2, this.waveHeight * 2);
            this.isRecording = false;
            this.therecorder.update_audio('isRecording',false);
            //we check audiocontext is not in an odd state before closing
            //superclickers can get it in an odd state
            if (this.audioContext!==null && this.audioContext.state !== "closed") {
                this.audioContext.close();
            }
            this.processor.disconnect();
            this.tracks.forEach(function(track){track.stop();});
            this.onStop(this.encoder.finish());
        },

        getBuffers: function(event) {
            var buffers = [];
            for (var ch = 0; ch < 2; ++ch) {
                buffers[ch] = event.inputBuffer.getChannelData(ch);
            }
            return buffers;
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