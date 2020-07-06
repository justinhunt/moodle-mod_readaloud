define(['jquery', 'core/log', 'core/ajax', 'mod_readaloud/definitions', 'mod_readaloud/cloudpoodllloader'], function($, log, ajax, def, cloudpoodll) {
  "use strict"; // jshint ;_;

  log.debug('Readaloud listen and repeat: initialising');

  return {

    activated: false,
    currentSentence:"",
    currentAudioStart: 0,
    currentAudioStop: 0,
    mak: null,
    controls: {},
    results:[],
    init: function(props) {
      var self = this;
      var recid ='readaloud_pushrecorder';

      cloudpoodll.init(recid, function(message) {
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

      self.prepare_controls();
      self.register_events();
      self.register_mak();
    },

    activate: function(){
        this.results = [];
        this.activated=true;
    },
    deactivate: function(){
        if(this.mak.controls.audioplayer[0].playing){
            this.mak.controls.audioplayer[0].pause();
        }
        this.activated=false;
    },

    prepare_controls: function(){
        var self = this;
        self.controls.container =$('#' + def.landrcontainer);
        self.controls.hiddenplayer = $('#mod_readaloud_landr_hiddenplayer');
        self.controls.playbutton = $('#mod_readaloud_landr_modalplay');
        self.controls.skipbutton = $('#mod_readaloud_landr_modalskip');
        self.controls.stopbutton = $('#mod_readaloud_landr_modalstop');
        self.audiourl = self.mak.fetch_audio_url();
        self.controls.hiddenplayer.attr('src',self.audiourl);

    },

    register_mak: function() {
      var self = this;
      
      self.mak.on_reach_audio_break = function(sentence, oldbreak, newbreak) {
          //do not get involved if we are not active
          //model audio karaoke is used elsewhere (shadow and preview) as well
          if(!self.activated){return;}

        // sentence contains the target text

        //empty strings are none of our concern
        if (sentence.trim() === '') {
          return;
        }

        self.currentSentence = sentence;
        self.currentAudioStart = oldbreak.audiotime;
        self.currentAudioEnd = newbreak.audiotime;
        
        log.debug(sentence);
        log.debug(oldbreak);
        log.debug(newbreak);

        //pause audio while we do our thing
        self.mak.pause_audio();
        self.controls.container.modal('show');
        $("#mod_readaloud_modal_target_phrase").html(sentence.replace(/[a-zA-Z0-9]/g,'').split(/ /).map(function(e){return '<span></span>';}));

      }

    },

    register_events: function() {

      var self = this;

      self.controls.playbutton.on('click',function(e){
         self.controls.hiddenplayer[0].currentTime=self.currentAudioStart;
          self.controls.hiddenplayer[0].play();
      });

        self.controls.skipbutton.on('click',function(e){
            self.controls.container.modal('hide');
            if(self.controls.hiddenplayer[0].playing){
                self.controls.hiddenplayer[0].pause();
            }
            self.mak.play_audio();
        });

        self.controls.stopbutton.on('click',function(e){
            self.controls.container.modal('hide');
            if(self.controls.hiddenplayer[0].playing){
                self.controls.hiddenplayer[0].pause();
            }
        });

      self.controls.hiddenplayer[0].ontimeupdate =function(){
        if(self.controls.hiddenplayer[0].currentTime >= self.currentAudioEnd){
            self.controls.hiddenplayer[0].pause();
        }
      };

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