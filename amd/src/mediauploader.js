/* jshint ignore:start */
define(['jquery', 'core/log'], function ($, log) {

    "use strict"; // jshint ;_;

    log.debug('Media Uploader: initialising');

    return {

        config: null,

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        init: function (config) {
            this.config = config;
            this.config.sourcemimetype = 'audio/wav';
            this.registerEvents();
            this.fetchNewUploadDetails();
        },

        registerEvents: function () {
            var that = this;
            // Events here.
        },

        fetchNewUploadDetails: function () {

            //The REST API we are calling
            var functionname = 'local_cpapi_fetch_upload_details';

            //fetch the Posturl. We need this.
            //set up our ajax request
            var xhr = new XMLHttpRequest();
            var that = this;

            //set up our handler for the response
            xhr.onreadystatechange = function (e) {
                if (this.readyState === 4) {
                    if (xhr.status === 200) {

                        // Get a yes or forget-it or try-again.
                        var payload = xhr.responseText;
                        var payloadobject = JSON.parse(payload);
                        if (payloadobject) {
                            // returnCode > 0  indicates an error
                            if (payloadobject.returnCode > 0) {
                                //We alert the iframe host that something did not go right
                                var messageObject = {};
                                messageObject.id = that.config.id;
                                messageObject.type = "error";
                                messageObject.code = payloadobject.returnCode;
                                messageObject.message = payloadobject.returnMessage;
                                that.message(messageObject);
                                return;
                                // If all good, then lets do the embed.
                            } else {
                                that.config.allowedURL = payloadobject.allowedURL;
                                that.config.posturl = payloadobject.postURL;
                                that.config.filename = payloadobject.filename;
                                that.config.s3filename = payloadobject.s3filename;
                                that.config.s3root = payloadobject.s3root;
                                that.config.cloudfilename = payloadobject.shortfilename;
                                that.config.cloudroot = payloadobject.shortroot;
                            }
                        } else {
                            log.debug('error:' + payloadobject.message);
                        }
                    } else {
                        log.debug('Not 200 response:' + xhr.status);
                    }
                }
            };

            //log.debug(params);
            var xhrparams = "wstoken=" + this.config.wstoken
                + "&wsfunction=" + functionname
                + "&moodlewsrestformat=" + 'json'
                + "&mediatype=" + this.config.mediatype
                + '&parent=' + this.config.parent
                + '&appid=' + this.config.appid
                + '&owner=' + this.config.owner
                + '&region=' + this.config.region
                + '&expiredays=' + this.config.expiredays
                + '&transcode=' + this.config.transcode
                + '&transcoder=' + this.config.transcoder
                + '&transcribe=' + this.config.transcribe
                + '&subtitle=' + this.config.subtitle
                + '&transcribelanguage=' + this.config.language
                + '&transcribevocab=' + this.config.transcribevocab
                + '&notificationurl=' + this.config.notificationurl
                + '&sourcemimetype=' + this.config.sourcemimetype;

            var serverurl = this.config.cloudpoodllurl + "/webservice/rest/server.php";
            xhr.open("POST", serverurl, true);
            xhr.setRequestHeader("Cache-Control", "no-cache");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send(xhrparams);
        },

        uploadBlob: function (blob, sourcemimetype) {
            this.uploadFile(blob, sourcemimetype);
        },

        //extract filename from the text returned as response to upload
        extractFilename: function (returntext) {
            var searchkey = "success<filename>";
            var start = returntext.indexOf(searchkey);
            if (start < 1) {
                return false;
            }
            var end = returntext.indexOf("</filename>");
            var filename = returntext.substring(start + (searchkey.length), end);
            return filename;
        },

        //fetch file extension from the filetype
        fetchFileExtension: function (filetype) {
            var ext = "";
            //in the case of a string like this:
            // "audio/webm;codecs=opus" we do not the codecs
            if(filetype.indexOf(';')>0){
                filetype = filetype.split(';')[0];
            }
            switch (filetype) {
                case "image/jpeg":
                    ext = "jpg";
                    break;
                case "image/png":
                    ext = "png";
                    break;
                case "audio/wav":
                    ext = "wav";
                    break;
                case "audio/ogg":
                    ext = "ogg";
                    break;
                case "audio/mpeg3":
                    ext = "mp3";
                    break;
                case "audio/mp3":
                    ext = "mp3";
                    break;
                case "audio/webm":
                    ext = "webm";
                    break;
                case "audio/wma":
                    ext = "wma";
                    break;
                case "audio/x-mpeg-3":
                    ext = "mp3";
                    break;
                case "audio/mp4":
                case "audio/m4a":
                case "audio/x-m4a":
                    ext = "m4a";
                    break;
                case "audio/3gpp":
                    ext = "3gpp";
                    break;
                case "video/mpeg3":
                    ext = "3gpp";
                    break;
                case "video/m4v":
                    ext = "m4v";
                    break;
                case "video/mp4":
                    ext = "mp4";
                    break;
                case "video/mov":
                case "video/quicktime":
                    ext = "mov";
                    break;
                case "video/x-matroska":
                case "video/webm":
                    ext = "webm";
                    break;
                case "video/wmv":
                    ext = "wmv";
                    break;
                case "video/ogg":
                    ext = "ogg";
                    break;
            }
            //if we get here we have an unknown mime type, just guess based on the mediatype
            if(ext===""){
                if(filetype.indexOf('video')>-1){
                    ext = "mp4";
                }else{
                    ext = "mp3";
                }
            }
            return ext;
        },

        doUploadCompleteCallback: function (uploader, filename) {

            filename = uploader.config.s3root + uploader.config.s3filename;

            //For callbackjs and for postmessage we need an array of stuff
            var callbackObject = [];
            callbackObject[0] = uploader.config.widgetid;
            callbackObject[1] = "filesubmitted";
            callbackObject[2] = filename;
            callbackObject[3] = uploader.config.updatecontrol;
            callbackObject[4] = uploader.config.s3filename;

            //invoke callbackjs if we have one
            if (uploader.config.callbackjs && uploader.config.callbackjs !== '') {
                if (typeof(uploader.config.callbackjs) === 'function') {
                    uploader.config.callbackjs(callbackObject);
                }
            }

        },

        //after an upload handle the filename poke and callback call
        postProcessUpload: function (e, uploader) {
            var xhr = e.currentTarget;
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    var filename = uploader.config.filename;
                    if (!filename) {
                        filename = uploader.extractFilename(xhr.responseText);
                    }
                    if (!filename) {
                        log.debug('upload failed #1');
                        log.debug(xhr);
                        return;
                    }
                    //Alert any listeners about the upload complete
                    this.doUploadCompleteCallback(uploader, filename);

                    //Fetch new upload details for next time
                    this.fetchNewUploadDetails(); // Prepare for next upload.

                } else {
                    log.debug('upload failed #3');
                    log.debug(xhr);
                } //end of if status 200
            }//end of if ready state 4
        },

        // upload Media file to wherever
        uploadFile: function (filedata, sourcemimetype) {

            var xhr = new XMLHttpRequest();
            var config = this.config;
            var uploader = this;

            //get the file extension from the filetype
            var sourceext = this.fetchFileExtension(sourcemimetype);


            //are we using s3
            var using_s3 = config.using_s3;


            //init sourcemimetype and sourcefilename
            uploader.config.sourcemimetype = sourcemimetype;
            uploader.config.sourcefilename = uploader.config.s3filename;

            xhr.onreadystatechange = function (e) {
                if (using_s3 && this.readyState === 4) {
                   uploader.update_filenames(uploader, sourceext);
                }
                uploader.postProcessUpload(e, uploader);

            };

            xhr.open("put", config.posturl, true);
            xhr.setRequestHeader("Content-Type", 'application/octet-stream');
            xhr.send(filedata);

        },

        update_filenames: function (uploader, sourceext) {
            var config = uploader.config;

            //now its a bit hacky, but
            // only now do we know the true final file extension (ext) and mimetype of unconv. media
            // so we want to save that and if the user is NOT transcoding,
            //we want to change the s3filename from the default mp4/mp3 to whatever the mimetype inidicates, ie sourceext

            switch (config.mediatype) {
                case 'audio':
                    //source info
                    uploader.config.sourcefilename = config.s3filename.replace('.mp3', '.' + sourceext);
                    if (!config.transcode) {
                        uploader.config.s3filename = uploader.config.sourcefilename;
                        //do we need this, I think its old and noone uses it.
                        uploader.config.cloudfilename = uploader.config.s3filename;
                    }
                    break;
                case 'video':
                    uploader.config.sourcefilename = config.s3filename.replace('.mp4', '.' + sourceext);
                    if (!config.transcode) {
                        uploader.config.s3filename = uploader.config.sourcefilename;
                    }
                    break;
            }
        },


        dataURItoBlob: function (dataURI, mimetype) {
            var byteString = atob(dataURI.split(',')[1]);
            var ab = new ArrayBuffer(byteString.length);
            var ia = new Uint8Array(ab);
            for (var i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }
            return new Blob([ab], {type: mimetype});
        },//end of dataURItoBlob

        message : function(messageobject){
            log.debug('Media Uploader Message: ' + messageobject.code + ' : ' + messageobject.message);
        }

    };//end of returned object
});//total end
