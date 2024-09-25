define(['jquery', 'core/log','core/ajax'], function ($, log,ajax) {
    "use strict"; // jshint ;_;
    /*
    This file helps you get Polly URLs at runtime
     */

    log.debug('ReadAloud Text Utils: initialising');

    return {
        token:  '',
        region: '',
        owner: '',

        setCloudPoodllToken: function(token){
          this.token=token;
        },

        countWords: function(sentence) {
            // Remove any leading or trailing spaces
            sentence = sentence.trim();

            // Count the number of spaces in the sentence
            var spaceCount = (sentence.match(/ /g) || []).length;

            // Add 1 to get the number of words (assuming at least one space)
            return spaceCount + 1;
        },


        stripTags: function(input) {
            if(typeof input === "undefined" || input == null) return "";
            return input.replace(/<[^>]*>/g, '');
        },

        getSelectedWord: function () {

            var selectedText = "";
            if (window.getSelection) {
                selectedText = window.getSelection().toString();
            } else if (document.selection && document.selection.type !== "Control") {
                selectedText = document.selection.createRange().text;
            }
            if (selectedText.length > 0){
                var words = selectedText.split(/\s+/);
                // Get the first word
                if (words.length > 0) {
                    return words[0];
                }
            }
            return selectedText;
        },

        analyzeText: function (text) {

            //tidy it up
            text=this.stripTags(text);

            // Count the number of words
            const words = text.split(/\s+/);
            const wordCount = words.length;
            const totalChars = text.length;

            // Calculate the average word length
            const totalWordLength = words.reduce((acc, word) => acc + word.length, 0);
            const averageWordLength = (totalWordLength / wordCount).toFixed(1);

            // Count the number of sentences
            const sentences = text.split(/[.!?]+/);
            var sentenceCount = sentences.length - 1; // Ignore the last element
            if(sentenceCount<1 && wordCount>0){sentenceCount=1;}//if no punctuation, then we have one sentence

            // Calculate the average sentence length
            var totalSentenceLength =0;
            for (var i =0; i<sentences.length;i++){
                totalSentenceLength += this.countWords(sentences[i]);
            }
            // const totalSentenceLength = sentences.reduce((acc, sentence) => acc + sentence.length, 0);
            var averageSentenceLength = 0;
            if(sentenceCount>0) {
                averageSentenceLength = (totalSentenceLength / sentenceCount).toFixed(1);
            }

            var stats = {
                charCount: totalChars,
                wordCount: wordCount,
                avWordLength: averageWordLength,
                sentenceCount: sentenceCount,
                avSentenceLength: averageSentenceLength,
            };
            return stats;
        },

       cleanWord: function(inputWord) {
            // Convert to lowercase
            var lowercaseWord = inputWord.toLowerCase();
            // Remove leading and trailing whitespace and punctuation
            var cleanedWord = lowercaseWord.replace(/^[^\w]+|[^\w]+$/g, '');
            return cleanedWord;
        },

        downloadTextContent: function(textcontent, fileName) {

            var blob = new Blob([textcontent], { type: 'text/plain' });
            var link = document.createElement('a');

            link.href = window.URL.createObjectURL(blob);
            link.download = fileName;

            // Append the link to the document and trigger a click event to start the download
            document.body.appendChild(link);
            link.click();

            // Remove the link from the document
            document.body.removeChild(link);
        },

        //FUNCTION rewrite article
        call_ai: function(prompt, language,subject,action, callback) {

            //The REST API we are calling
            var functionname = 'local_cpapi_call_ai';

            //fetch the Posturl. We need this.
            //set up our ajax request
            var xhr = new XMLHttpRequest();
            var that = this;

            //set up our handler for the response
            xhr.onreadystatechange = function (e) {
                if (this.readyState === 4) {
                    if (xhr.status == 200) {

                        //get a yes or forgetit or tryagain
                        var payload = xhr.responseText;
                        var payloadobject = JSON.parse(payload);
                        if (payloadobject) {
                            //returnCode > 0  indicates an error
                            if (payloadobject.returnCode > 0) {
                                console.log(payloadobject.returnMessage);
                                return false;
                                //if all good, then lets do the embed
                            } else if (payloadobject.returnCode === 0){
                                var pollyurl = payloadobject.returnMessage;
                                callback(pollyurl);
                            } else {
                                console.log(' Request failed:');
                                console.log(payloadobject);
                            }
                        } else {
                            console.log(' Request something bad happened');
                        }
                    } else {
                        console.log('Request Not 200 response:' + xhr.status);
                    }
                }
            };

            //make our request
            var xhrparams = "wstoken=" + this.token
                + "&wsfunction=" + functionname
                + "&moodlewsrestformat=" + 'json'
                + "&prompt=" + encodeURIComponent(prompt)
                + "&language=" + language
                + "&subject=" + subject
                + "&action=" + action
                + '&appid=' + 'mod_readaloud'
                + '&owner=poodll'
                + '&region=useast1';

            var serverurl = 'https://cloud.poodll.com' + "/webservice/rest/server.php";
            xhr.open("POST", serverurl, true);
            xhr.setRequestHeader("Cache-Control", "no-cache");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send(xhrparams);
        },


        init: function(token, region, owner){
                this.token =token;
                this.region=region;
                this.owner=owner;
            },

        clean_ssml_chars: function(speaktext){
            //deal with SSML reserved characters
            speaktext =  speaktext.replace(/&/g,'&amp;');
            speaktext = speaktext.replace(/'/g,'&apos;');
            speaktext= speaktext.replace(/"/g,'&quot;');
            speaktext = speaktext.replace(/</g,'&lt;');
            speaktext =  speaktext.replace(/>/g,'&gt;');
            return speaktext;
        },

        can_speak_neural: function(voice,region){
            switch(region){
                case "useast1":
                case "tokyo":
                case "sydney":
                case "dublin":
                case "ottawa":
                case "frankfurt":
                case "london":
                case "singapore":
                case "capetown":
                    //ok
                    break;
                default:
                    return false;
            }

            //check if the voice is supported
            if(def.neural_voices.indexOf(voice) !== -1){
                return true;
            }else{
                return false;
            }

        },

        fetch_polly_url: function(speaktext,voiceoption, voice) {
            var that = this;
            return new Promise(function(resolve,reject){
                //The REST API we are calling
                var functionname = 'local_cpapi_fetch_polly_url';

                //fetch the Posturl. We need this.
                //set up our ajax request
                var xhr = new XMLHttpRequest();

                //set up our handler for the response
                xhr.onreadystatechange = function (e) {
                    if (this.readyState === 4) {
                        if (xhr.status == 200) {

                            //get a yes or forgetit or tryagain
                            var payload = xhr.responseText;
                            var payloadobject = JSON.parse(payload);
                            if (payloadobject) {
                                //returnCode > 0  indicates an error
                                if (payloadobject.returnCode > 0) {
                                    reject(payloadobject.returnMessage);
                                    log.debug(payloadobject.returnMessage);
                                    return false;
                                    //if all good, then lets do the embed
                                } else if (payloadobject.returnCode === 0){
                                    var pollyurl = payloadobject.returnMessage;
                                    resolve(pollyurl);
                                } else {
                                    reject('Polly Signed URL Request failed:');
                                    log.debug('Polly Signed URL Request failed:');
                                    log.debug(payloadobject);
                                }
                            } else {
                                reject('Polly Signed URL Request something bad happened');
                                log.debug('Polly Signed URL Request something bad happened');
                            }
                        } else {
                            reject('Polly Signed URL Request Not 200 response:' + xhr.status);
                            log.debug('Polly Signed URL Request Not 200 response:' + xhr.status);
                        }
                    }
                };
                var texttype='ssml';

                switch(parseInt(voiceoption)){

                    //slow
                    case 1:
                        //fetch slightly slower version of speech
                        //rate = 'slow' or 'x-slow' or 'medium'
                        speaktext =that.clean_ssml_chars(speaktext);
                        speaktext = '<speak><break time="1000ms"></break><prosody rate="slow">' + speaktext + '</prosody></speak>';
                        break;
                    //veryslow
                    case 2:
                        //fetch slightly slower version of speech
                        //rate = 'slow' or 'x-slow' or 'medium'
                        speaktext =that.clean_ssml_chars(speaktext);
                        speaktext = '<speak><break time="1000ms"></break><prosody rate="x-slow">' + speaktext + '</prosody></speak>';
                        break;
                    //ssml
                    case 3:
                        speaktext='<speak>' + speaktext + '</speak>';
                        break;

                    //normal
                    case 0:
                    default:
                        //fetch slightly slower version of speech
                        //rate = 'slow' or 'x-slow' or 'medium'
                        speaktext =that.clean_ssml_chars(speaktext);
                        speaktext = '<speak><break time="1000ms"></break>' + speaktext + '</speak>';
                        break;

                }

                //to use the neural or standard synthesis engine
                var engine = that.can_speak_neural(voice,that.region) ?'neural' : 'standard';

                //log.debug(params);
                var xhrparams = "wstoken=" + that.token
                + "&wsfunction=" + functionname
                + "&moodlewsrestformat=" + 'json'
                + "&text=" + encodeURIComponent(speaktext)
                + '&texttype=' + texttype
                + '&voice=' + voice
                + '&appid=' + def.component
                + '&owner=' + that.owner
                + '&region=' + that.region
                + '&engine=' + engine;

                var serverurl = def.cloudpoodllurl + "/webservice/rest/server.php";
                xhr.open("POST", serverurl, true);
                xhr.setRequestHeader("Cache-Control", "no-cache");
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send(xhrparams);
            });
        }

    };//end of return value
});
