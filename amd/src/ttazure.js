define(['jquery', 'core/log'], function ($, log) {
    "use strict"; // jshint ;_;
    /*
    This file is the streamer to azure
     */

    log.debug('TT Azure Streamer initialising');

    return {

        speechtoken: null,
        socket: null,
        audiohelper: null,
        earlyaudio: [],
        partials: [],
        finals: [],
        finalwords: [],
        ready: false,
        finaltext: '',
        region: 'westeurope',
        apidomain: 'microsoft.com',
        lang: 'en-US',
        //samplerate of the pcm we send. ttaudiohelper builds the AudioContext at this rate.
        samplerate: 16000,
        //azure reports offsets in 100 nanosecond ticks, so this many ticks to the second.
        tickspersecond: 10000000,
        //total audio sent since recording started, in seconds. our clock, and it survives a reconnect.
        audioseconds: 0,
        //the audio clock reading when the current socket generation opened. word offsets arrive
        //relative to the session, so we add this to get times relative to the whole recording.
        sessionoffset: 0,

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        init: function (speechtoken, speechregion, theaudiohelper) {
            this.speechtoken = speechtoken;
            this.region = speechregion;
            this.audiohelper = theaudiohelper;
            this.lang = theaudiohelper.therecorder.lang;
            this.sentHeader = false; // Track if WAV header was sent
            this.finals = [];
            this.finalwords = [];
            this.finaltext = '';
            this.audioseconds = 0;
            this.sessionoffset = 0;
            // If region starts with "china" set aipdomain to azure.cn
            if(this.region.startsWith('china')) {
                this.apidomain = 'azure.cn';
            } else if (this.region.startsWith('usgov')) {
                this.apidomain = 'azure.us';
            } else {
                this.apidomain = 'microsoft.com';
            }
            this.preparesocket();
        },

        preparesocket: async function () {
            var that = this;

            //a socket generation is one azure session. a token refresh closes the socket and opens a new one,
            //and the new session restarts its offsets at zero. park the audio clock so word timings from the
            //incoming session get shifted back onto the recording's timeline. the ms speech sdk does the same
            //thing internally (DetailedSpeechPhrase.updateOffsets applies a baseOffset per connection).
            this.sessionoffset = this.audioseconds;

            var url = `wss://${this.region}.stt.speech.${this.apidomain}/speech/recognition/conversation/cognitiveservices/v1?language=${this.lang}`;
            //detailed format puts results in NBest, and wordLevelTimestamps adds the per word Offset/Duration
            //that readaloud needs for wpm and for spot check playback. ConnectionFactoryBase in the ms speech
            //sdk maps SpeechServiceResponse_RequestWordLevelTimestamps onto this same query parameter.
            url += `&format=detailed`;
            url += `&wordLevelTimestamps=true`;
            // Using the token as a query param is the only easy way without headers
            url += `&Authorization=Bearer ${this.speechtoken}`;

            this.socket = new WebSocket(url);

            log.debug('TT Azure Streamer socket prepared');

            this.socket.binaryType = 'arraybuffer'; // Important for receiving binary if needed, though we get text mostly

            // Generate a request ID for this session if not already set
            if (!this.requestId) {
                this.requestId = this.getUuid();
            }

            this.socket.onmessage = function (message) {
                if (typeof message.data === 'string') {
                    try {
                        // 1. Find the start of the JSON body (after the headers)
                        const bodyStartIndex = message.data.indexOf('{');
                        if (bodyStartIndex === -1) return; // Not a JSON message (e.g., turn.start)

                        // 2. Extract headers to check the Path
                        const headerSection = message.data.substring(0, bodyStartIndex);
                        const bodySection = message.data.substring(bodyStartIndex);

                        // 3. Parse the JSON body
                        const res = JSON.parse(bodySection);

                        // 4. Determine the Path from the header section
                        if (headerSection.includes('Path:speech.hypothesis')) {
                            let msg = res.Text;
                            that.audiohelper.oninterimspeechcapture(that.finaltext + ' ' + msg);
                        }
                        else if (headerSection.includes('Path:speech.phrase')) {
                            if (res.RecognitionStatus === 'Success') {
                                //detailed format puts the text in NBest[0].Display. fall back to DisplayText
                                //so we still work if the service ignores the detailed request.
                                var best = (res.NBest && res.NBest.length) ? res.NBest[0] : null;
                                let msg = (best && best.Display) ? best.Display : res.DisplayText;
                                if (msg) {
                                    that.finaltext += ' ' + msg;
                                    that.finalwords.push(that.extractwords(best));
                                    that.audiohelper.oninterimspeechcapture(that.finaltext);
                                    log.debug('Azure final: ' + msg);
                                }
                            }
                        }
                    } catch (e) {
                        console.error("Error parsing Azure message:", e);
                    }
                }
            };

            this.socket.onopen = (event) => {
                log.debug('TT Azure Streamer socket opened');
                that.ready = true;
                that.sentHeader = false; // Reset on new connection
                that.audiohelper.onSocketReady('fromsocketopen');
            };

            this.socket.onerror = (event) => {
                log.debug(event);
                that.socket.close();
            };

            this.socket.onclose = (event) => {
                log.debug(event);
                that.socket = null;
                that.requestId = null; // Clear request ID on close so a new one is generated for next session
            };
        },

        /*
        * Pull word timings out of an NBest entry. Azure reports Offset and Duration in 100 nanosecond ticks
        * relative to the start of the session, so convert to seconds and add the generation offset to land on
        * the recording's timeline, which is what the audio file and the grading ui are indexed against.
         */
        extractwords: function (best) {
            var that = this;
            var words = [];
            if (!best || !best.Words || !best.Words.length) {
                return words;
            }
            for (var i = 0; i < best.Words.length; i++) {
                var w = best.Words[i];
                var text = (w.Word || '').trim();
                if (text === '') {
                    continue;
                }
                var start = (w.Offset || 0) / that.tickspersecond;
                var duration = (w.Duration || 0) / that.tickspersecond;
                words.push({
                    content: text,
                    start_time: that.sessionoffset + start,
                    end_time: that.sessionoffset + start + duration,
                    confidence: typeof w.Confidence === 'number' ? w.Confidence : 1
                });
            }
            return words;
        },

        /*
        * The flat, ordered word list for the whole recording, in the same shape ttstreamer produces.
         */
        buildwords: function () {
            var all = [];
            for (var i = 0; i < this.finalwords.length; i++) {
                var phrasewords = this.finalwords[i];
                if (phrasewords && phrasewords.length) {
                    all = all.concat(phrasewords);
                }
            }
            return all;
        },

        updatetoken: function (newtoken) {
            var that = this;
            if (that.socket) {
                that.socket.close();
            }
            that.speechtoken = newtoken;
            that.preparesocket();
        },

        audioprocess: function (stereodata) {
            var that = this;
            const base64data = this.binarytobase64(stereodata[0]);

            //advance the audio clock. we count every buffer we are handed, including the ones held as
            //earlyaudio, so the clock tracks the recording rather than the socket.
            this.audioseconds += stereodata[0].length / this.samplerate;

            if (this.ready === undefined || !this.ready) {
                this.earlyaudio.push(base64data);
            } else {
                // If we have early audio, send it first
                if (this.earlyaudio.length > 0) {
                    // Send WAV header with first chunk if not sent
                    if (!this.sentHeader) {
                        this.sendWavHeader();
                    }
                    for (var i = 0; i < this.earlyaudio.length; i++) {
                        this.sendaudio(this.earlyaudio[i]);
                    }
                    this.earlyaudio = [];
                }

                // Send current chunk
                if (!this.sentHeader) {
                    this.sendWavHeader();
                }
                this.sendaudio(base64data);
            }
        },

        binarytobase64: function (monoaudiodata) {
            var tempbuffer = []
            for (let i = 0; i < monoaudiodata.length; i++) {
                const sample = Math.max(-1, Math.min(1, monoaudiodata[i]))
                const intSample = sample < 0 ? sample * 0x8000 : sample * 0x7fff
                tempbuffer.push(intSample & 0xff)
                tempbuffer.push((intSample >> 8) & 0xff)
            }
            // Return Unit8Array
            return new Uint8Array(tempbuffer);
        },

        sendWavHeader: function () {
            // Send a valid WAV header for 16kHz, 16bit, Mono
            // We use a large size or max size for data chunk
            var buffer = new ArrayBuffer(44);
            var view = new DataView(buffer);
            var sampleRate = 16000;
            var numChannels = 1;

            /* RIFF identifier */
            this.writeString(view, 0, 'RIFF');
            /* file length */
            view.setUint32(4, 2147483647, true); // Use max int? usually file size - 8. 
            /* RIFF type */
            this.writeString(view, 8, 'WAVE');
            /* format chunk identifier */
            this.writeString(view, 12, 'fmt ');
            /* format chunk length */
            view.setUint32(16, 16, true);
            /* sample format (raw) */
            view.setUint16(20, 1, true);
            /* channel count */
            view.setUint16(22, numChannels, true);
            /* sample rate */
            view.setUint32(24, sampleRate, true);
            /* byte rate (sample rate * block align) */
            view.setUint32(28, sampleRate * 2, true);
            /* block align (channel count * bytes per sample) */
            view.setUint16(32, 2, true);
            /* bits per sample */
            view.setUint16(34, 16, true);
            /* data chunk identifier */
            this.writeString(view, 36, 'data');
            /* data chunk length */
            view.setUint32(40, 2147483647, true);

            var headerBytes = new Uint8Array(buffer);
            this.sendaudio(headerBytes);
            this.sentHeader = true;
        },

        writeString: function (view, offset, string) {
            for (var i = 0; i < string.length; i++) {
                view.setUint8(offset + i, string.charCodeAt(i));
            }
        },

        getUuid: function () {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },

        createBinaryMessage: function (data) {
            // Use the session request ID
            var requestId = this.requestId ? this.requestId : this.getUuid();
            var headers = [
                "Path: audio",
                "X-RequestId: " + requestId,
                "X-Timestamp: " + new Date().toISOString(),
                "Content-Type: audio/x-wav"
            ].join("\r\n"); // Headers end with \r\n

            var headerBytes = new TextEncoder().encode(headers);
            var headerLen = headerBytes.length;

            var msg = new Uint8Array(2 + headerLen + data.length);
            // Big Endian 16-bit length
            msg[0] = (headerLen >> 8) & 0xFF;
            msg[1] = headerLen & 0xFF;
            msg.set(headerBytes, 2);
            msg.set(data, 2 + headerLen);
            return msg;
        },

        sendaudio: function (data) {
            var that = this;
            if (that.socket && that.socket.readyState === WebSocket.OPEN) {
                var binaryMsg = this.createBinaryMessage(data);
                that.socket.send(binaryMsg);
            }
        },

        finish: function (mimeType) {
            // Azure auto-detects silence usually, but we can close.
            if (this.socket) {
                // Maybe send end of stream?
            }
            var that = this;
            setTimeout(function () {
                var finalwords = that.buildwords();
                log.debug('TT Azure Streamer final capture with ' + finalwords.length + ' timed words');
                that.audiohelper.onfinalspeechcapture(that.finaltext, finalwords);
                that.cleanup();
            }, 1000);
        },

        cancel: function () {
            this.ready = false;
            this.earlyaudio = [];
            this.finals = [];
            this.finalwords = [];
            this.finaltext = '';
            if (this.socket) {
                this.socket.close();
            }
        },

        cleanup: function () {
            this.cancel();
        }

    };

});
