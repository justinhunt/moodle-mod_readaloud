define(['jquery', 'core/log', 'core/ajax', 'mod_readaloud/definitions', 'mod_readaloud/cloudpoodllloader'], function($, log, ajax, def, cloudpoodll) {
  "use strict"; // jshint ;_;

  log.debug('Readaloud listen and repeat: initialising');

  return {

    currentSentence:"",
    mak: null,
    container: null,
    audiourl: false,
    results:[],
    init: function(props) {
      var self = this;
      cloudpoodll.init('readaloud_pushrecorder', function(message) {

        switch (message.type) {
          case 'recording':
            break;

          case 'speech':
            self.getComparison(
              self.currentSentence,
              message.capturedspeech,
              function(comparison) {
                self.gotComparison(comparison, message);
              }
            );
            break;

        }

      });
      self.mak = props.modelaudiokaraoke;
      self.results = props.results;
      self.audiourl = self.mak.fetch_audio_url();
      self.container = $('#' + def.landrcontainer);
      self.register_events();
      self.register_mak();
    },

    register_mak: function() {
      var self = this;
      
      self.mak.on_reach_audio_break = function(sentence, oldbreak, newbreak) {

        // sentence contains the target text

        //empty strings are none of our concern
        if (sentence.trim() === '') {
          return;
        }

        self.currentSentence = sentence;
        
        log.debug(sentence);
        log.debug(oldbreak);
        log.debug(newbreak);

        //pause audio while we do our thing
        self.mak.pause_audio();
        self.container.modal('show');
        $("#mod_readaloud_modal_target_phrase").html(sentence.replace(/[a-zA-Z0-9]/g,'').split(/ /).map(function(e){return '<span></span>';}));

      }

    },

    register_events: function() {

      var self = this;

      self.container.on('hidden.bs.modal', function(e) {
        self.mak.play_audio();
      });

    },
    spliton: new RegExp('([,.!?:;" ])', 'g'),

    gotComparison: function(comparison, typed) {

      var self = this;
      console.log("comparison:"+JSON.stringify(comparison));
      console.log("typed:"+JSON.stringify(typed));
      //self.results[i]=100

    },
    getComparison: function(passage, transcript, callback) {
      var self = this;

      ajax.call([{
        methodname: 'mod_readaloud_compare_passage_to_transcript',
        args: {
          passage: passage,
          transcript: transcript,
          alternatives: '',
          language: 'en-US'
        },
        done: function(ajaxresult) {
          var payloadobject = JSON.parse(ajaxresult);
          if (payloadobject) {
            callback(payloadobject);
          } else {
            callback(false);
          }
        },
        fail: function(err) {
          console.log(err);
        }
      }]);

    }

  };
});