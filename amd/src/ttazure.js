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
        ready: false,
        finaltext: '',
        region: 'westeurope',
        apidomain: 'microsoft.com',
        lang: 'en-US',

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
            var url = `wss://${this.region}.stt.speech.${this.apidomain}/speech/recognition/conversation/cognitiveservices/v1?language=${this.lang}`;
            url += `&format=simple`;
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
                                let msg = res.DisplayText;
                                that.finaltext += ' ' + msg;
                                that.audiohelper.oninterimspeechcapture(that.finaltext);
                                console.debug('Azure final: ' + msg);
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
                that.audiohelper.onfinalspeechcapture(that.finaltext);
                that.cleanup();
            }, 1000);
        },

        cancel: function () {
            this.ready = false;
            this.earlyaudio = [];
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
