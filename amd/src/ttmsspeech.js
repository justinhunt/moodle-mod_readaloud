define(['jquery', 
    'core/log'], 
    function ($, log) {
    
   "use strict"; // jshint ;_;
    /*
    This file streams to msspeech and collects the response. 
     */

    log.debug('MS Speech initialising');

    return {

        thetoken: null,
        theregion: null,
        thelanguage: null,
        thereferencetext: null,
        speechsdk: null,

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        init: function(mstoken, msregion, mslanguage, referencetext) {
            var that = this;
            this.thetoken = mstoken;
            this.theregion = msregion;
            this.thelanguage = mslanguage;
            this.thereferencetext = referencetext;
            log.debug('MS Speech init');
            if(!window.hasOwnProperty('SpeechSDK')){ 
                log.debug('MS Speech loading');
                $.getScript('https://aka.ms/csspeech/jsbrowserpackageraw', function(){
                    log.debug('MS Speech loaded');
                    that.speechsdk = window.SpeechSDK;
                    log.debug(that.speechsdk);
                });
            }
        },

        recognize: function(blob, callback) {
            var that = this;
            
          //MS Speech SDK requires the audio to be in wav format and to have a name field
          blob.name = 'audio.wav';
          let audioConfig = that.speechsdk.AudioConfig.fromWavFileInput(blob,blob.name);

          var speechConfig = that.speechsdk.SpeechConfig.fromAuthorizationToken(that.thetoken, that.theregion);
          speechConfig.speechRecognitionLanguage = that.thelanguage;

          //need to pass this in, better
          var referencetext = that.thereferencetext;

          // create pronunciation assessment config, set grading system, granularity and if enable miscue based on your requirement.
          const pronunciationAssessmentConfig = new that.speechsdk.PronunciationAssessmentConfig(
              referencetext,
              that.speechsdk.PronunciationAssessmentGradingSystem.HundredMark,
              that.speechsdk.PronunciationAssessmentGranularity.Phoneme,
              true
          );
          pronunciationAssessmentConfig.enableProsodyAssessment = true;

          // create the speech recognizer.
          var reco = new that.speechsdk.SpeechRecognizer(speechConfig, audioConfig);
          // (Optional) get the session ID
          reco.sessionStarted = (_s, e) => {
              console.log(`SESSION ID: ${e.sessionId}`);
          };
          pronunciationAssessmentConfig.applyTo(reco);

          reco.recognizeOnceAsync(
              function (speechRecognitionResult) {
                    // The pronunciation assessment result as a Speech SDK object
                    var pronunciationAssessmentResult = that.speechsdk.PronunciationAssessmentResult.fromResult(speechRecognitionResult);
                    // The pronunciation assessment result as a JSON string
                    //var pronunciationAssessmentResultJson = speechRecognitionResult.properties.getProperty(SpeechSDK.PropertyId.SpeechServiceResponse_JsonResult);
                  callback(pronunciationAssessmentResult);
              },
              function (err) {
                  console.log("ERROR: " + err);
                  exit();
              }
          );
        },

        on_recognition: function(){

        },

     }

});