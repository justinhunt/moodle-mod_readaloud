define(['jquery', 'core/log', 'core/ajax', 'mod_readaloud/definitions', 'mod_readaloud/ttrecorder'],
    function($, log, ajax, def,  ttrecorder) {
  "use strict"; // jshint ;_;

  log.debug('Readaloud Practice: initialising');

  return {

    activated: false,
    audiourl: "",
    currentSentence: "",
    currentPhonetic: "",
    language: "en-US",
    breaks: [],
    currentbreak: 0,
    controls: {},
    results: [],
    phonetics: [],
    cmid: 0,
    ttr: {},

    init: function(props) {

      var self = this;
      self.cmid = props.cmid;

      //Get info from modelaudiokaraoke about breaks and text, for use here
      self.mak = props.modelaudiokaraoke;
      self.audiourl = self.mak.fetch_audio_url();
      self.set_breaks(self.mak.breaks);

      self.language = props.language;
      self.region = props.region;
      self.phonetics = props.phonetics;
      self.stt_guided = props.stt_guided;
      self.shadow = false;//props.shadow;
      self.ttr={};

      //recorder stuff
      var theCallback =function(message) {
          log.debug('Readaloud Practice: ttrecorder callback', message);
          log.debug(message);
          switch (message.type) {
            case 'recordingstarted':
              if (self.controls.shadowplaycheckbox.is(":checked")) {
                self.shadow = true;
                log.debug('shadow is true');
                self.controls.playbutton.trigger('click');
              }else{
                log.debug('shadow is false');
                self.shadow=false;
              }
              //hide the self model player (though it may not be here) because we do not want it playing old stuff into mic
              self.controls.playselfbutton.hide();

              break;

            case 'recordingstopped':
                  if (self.shadow === true){
                    self.controls.hiddenplayer[0].pause();
                  }
                  if(self.stt_guided || self.ttr.usebrowserrec===false){
                    self.controls.playselfbutton.show();
                  }
                  break;

              case 'speech':

                  self.getComparison(
                      self.cmid,
                      self.currentSentence,
                      message.capturedspeech,
                      self.currentPhonetic,
                      function(comparison) {
                          self.gotComparison(comparison, message);
                      }
                  );
                  break;
          }
      };

        //init tt recorder
      var opts = {};
      opts.uniqueid = 'readaloud_ttrecorder';
      opts.stt_guided = self.stt_guided;
      opts.callback = theCallback;
      opts.shadow = false;
      self.ttr = ttrecorder.clone();
      self.ttr.init(opts);


      self.prepare_controls();
      self.register_events();
      // Set the first break as current.
      self.move_break(0);

    },


    prepare_controls: function() {
      var self = this;

        // Control references
      self.controls.container = $('#mod_readaloud_practice_cont_wrap');
      self.controls.mainstage = self.controls.container.find('#mod_readaloud_practice_inner');
      self.controls.targetphrase = self.controls.container.find('#mod_readaloud_practice_target_phrase');
      self.controls.playbutton = $('#mod_readaloud_practice_play');
      self.controls.shadowplaycheckbox = $('#mod_readaloud_practice_shadow');
      self.controls.skipforwardbutton = $('#mod_readaloud_practice_skipforward');
      self.controls.skipbackbutton = $('#mod_readaloud_practice_skipback');
      self.controls.finishedbutton = $("#mod_readaloud_practice_finished");
      self.controls.playselfbutton = $("#mod_readaloud_practice_playself");


      // Results Screen
      self.controls.resultscontainer = self.controls.container.find('.ra_practice_results_container');
      self.controls.results_text = self.controls.resultscontainer.find('.ra_practice_results_text');
      self.controls.results_retrybutton = self.controls.resultscontainer.find('.ra_practice_retry');
      self.controls.results_nextbutton = self.controls.resultscontainer.find('.ra_practice_next');
      self.controls.results_playbutton = self.controls.resultscontainer.find('.ra_practice_results_play');
      self.controls.results_playselfbutton = self.controls.resultscontainer.find('.ra_practice_results_playself');
      self.controls.results_0stars = self.controls.resultscontainer.find('.ra_practice_feedback_0stars');
      self.controls.results_1stars = self.controls.resultscontainer.find('.ra_practice_feedback_1stars');
      self.controls.results_2stars = self.controls.resultscontainer.find('.ra_practice_feedback_2stars');
      self.controls.results_3stars = self.controls.resultscontainer.find('.ra_practice_feedback_3stars');
      self.controls.results_4stars = self.controls.resultscontainer.find('.ra_practice_feedback_4stars');
      self.controls.results_5stars = self.controls.resultscontainer.find('.ra_practice_feedback_5stars');

      // Audio players
      self.controls.hiddenplayer = $('#mod_readaloud_practice_hiddenplayer');
      self.controls.hiddenselfplayer = $('#mod_readaloud_practice_hiddenselfplayer');
      self.controls.hiddenplayer.attr('src', self.audiourl);

    },

    // Set the breaks for the practice activity.
    set_breaks: function(breaks) {
        var self = this;
        self.breaks = breaks;
        self.sort_breaks();
        self.add_info_to_breaks();
    },

    sort_breaks: function() {
        var self = this;
        self.breaks.sort(function(a, b) {
        return a.audiotime - b.audiotime;
      });
    },

    add_info_to_breaks: function(){
      var self=this;
      var lastbreakaudioend = 0;
      var lastbreakwordnumber = 0;
      for (var i = 0; i < self.breaks.length; i++) {
          //Set the break number and audio start time (end time of previous break)
          self.breaks[i].breaknumber=i+1;
          self.breaks[i].audiostarttime= lastbreakaudioend;
          
          //Build the "sentence" property
          var thesentence = "";
          for (var thewordnumber = lastbreakwordnumber + 1; thewordnumber <= self.breaks[i].wordnumber; thewordnumber++) {
              thesentence += $('#' + def.spaceclass + '_' + thewordnumber).text();
              thesentence += $('#' + def.wordclass + '_' + thewordnumber).text();
          }
          self.breaks[i].sentence= thesentence;

          // Set the lastbreak details for the next loop iteration.
          lastbreakaudioend = self.breaks[i].audiotime;
          lastbreakwordnumber = self.breaks[i].wordnumber;
      }
    },

    pause_audio: function() {
      this.controls.hiddenplayer[0].pause();
    },

    play_audio: function() {
      this.controls.hiddenplayer[0].play();
    },

    get_audio_time: function() {
      return this.controls.hiddenplayer[0].currentTime;
    },

    set_audio_time: function(newtime) {
      this.controls.hiddenplayer[0].currentTime=newtime;
    },

    fetch_audio_url: function() {
      return this.controls.hiddenplayer.attr('src');
    },

     move_break:  function(increment) {
       var self = this;
       //quick sanity check
       if( self.currentbreak + increment < 0 || self.currentbreak + increment >= self.breaks.length) {
           return;
       }

       //Increment the break number
       self.currentbreak += increment;
       var thebreak = self.breaks[self.currentbreak];

        //get the previous break
        var prevbreak = (self.currentbreak - 1 > 0) ? self.breaks[self.currentbreak - 1] : {wordnumber: 0, audiotime: 0};

        //Update the sentence
        if (thebreak.sentence.trim() === '') {
          return;
        }
        var thesentence = thebreak.sentence.trim();
        self.currentSentence = thesentence;

        //in some cases ttrecorder wants to know the currentsentence
        if(!self.ttr.usebrowserrec) {
          self.ttr.currentPrompt= thesentence;
        }

      if(self.phonetics.length > thebreak.wordnumber-1){
          var startpos = prevbreak.wordnumber;
          if(startpos<0){startpos=0;}
          var endpos = thebreak.wordnumber;

          /*
          * break=0: wordnumber 0 start = 0, end = 9: jssplit returns 0-8
          * break=1: wordnumber 9 start = 9, end = 18: jssplit returns 9-17
          * break=2: wordnumber 18 start = 18, end = 99: jssplit returns 18-98
           */
          self.currentPhonetic = self.phonetics.slice(startpos,endpos).join(' ');
      }else{
          self.currentPhonetic  = '';
      }


          // UI updates (mainly)
          if (self.currentbreak === self.breaks[self.breaks.length - 1]) {
            self.controls.finishedbutton.show();
            self.controls.skipforwardbutton.hide();
            // Alert server and activity controller that the listen and practice is complete
            self.on_complete();
          } else {
            self.controls.finishedbutton.hide();
            self.controls.skipforwardbutton.show();
          }

         //hide the self model player because when we show page again we dont want it enabled
         self.controls.playselfbutton.hide();


          self.pause_audio();

         self.controls.targetphrase.html(thesentence.split(/ /).map(function(e, i) {
            return '<div class="mod_readaloud_practice_target_word" data-index="' + i + '">' + e + '</div>';
          }));

      },

    // Callback for when the practice activity is complete, overridden by activity controller.
    on_complete: function() {},

    // Register events for the listen and repeat activity.
    register_events: function() {

      var self = this;
      var aplayer = self.controls.hiddenplayer[0];

      self.controls.playbutton.on('click', function(e) {
        if (!aplayer.paused) {
          aplayer.pause();
        }else {
          aplayer.currentTime = self.breaks[self.currentbreak].audiostarttime;
          aplayer.play();
        }
      });

      self.controls.shadowplaycheckbox.on('change', function(e) {
        self.ttr.shadow = $(this).is(':checked');
      });

      self.controls.skipforwardbutton.on('click', function(e) {
            self.move_break(1);
      });

      self.controls.skipbackbutton.on('click', function(e) {
            self.move_break(-1);

      });

      self.controls.finishedbutton.on('click', function() {
       // self.controls.container.modal('hide');
        aplayer.currentTime = 0;
      });


      aplayer.ontimeupdate = function() {
          var currentbreak = self.breaks[self.currentbreak];
          if (aplayer.currentTime >= currentbreak.audiotime) {
              aplayer.pause();
          }
      };

      // Results screen events
        self.controls.results_retrybutton.on('click', function() {
            //hide the results container and show the practice main stage
            self.controls.resultscontainer.hide();
            self.controls.mainstage.show();
            self.controls.container.find('.ra_practice_main_container').show();
        });
        self.controls.results_nextbutton.on('click', function() {
            //hide the results container and show the practice main stage
            self.controls.resultscontainer.hide();
            self.controls.mainstage.show();
            self.controls.skipforwardbutton.trigger('click');
        });
        self.controls.results_playbutton.on('click', function() {
            self.controls.playbutton.trigger('click');
        });
        self.controls.results_playselfbutton.on('click', function() {
            if (!self.controls.hiddenselfplayer[0].paused) {
                self.controls.hiddenselfplayer[0].pause();
            }else {
                self.controls.hiddenselfplayer.attr('src', self.ttr.audio.dataURI);
                self.controls.hiddenselfplayer[0].play();
            }
        });

    },

   // spliton: new RegExp('([,.!?:;" ])', 'g'),
      spliton: new RegExp(/([!"# $%&'()。「」、*+,-.\/:;<=>?@[\]^_`{|}~])/, 'g'),

    gotComparison: function(comparison, typed) {
     if(!comparison){return;}
      var self = this;
      var thisClass;
      var wordsmatched=0;
      $(".mod_readaloud_practice_target_word").removeClass("mod_readaloud_practice_target_word_correct mod_readaloud_practice_target_word_incorrect");

      comparison.forEach(function(word, idx) {

        if( word.matched) {
            thisClass = "mod_readaloud_practice_target_word_correct" ;
            wordsmatched++;
        }else{
            thisClass = "mod_readaloud_practice_target_word_incorrect";
        }
        $(".mod_readaloud_practice_target_word[data-index='" + idx + "']").addClass(thisClass);
      });
        if(comparison.length == wordsmatched){

            self.show_results(5);
            // Previously we auto proceeded to next break if all words matched
            //setTimeout(function(){self.controls.skipforwardbutton.trigger('click');},600);
        } else {
            var stars = Math.round((wordsmatched / comparison.length) * 5);
            self.show_results(stars);
        }
    },
    getComparison: function(cmid, passage, transcript,passagephonetic, callback) {
      var self = this;

      ajax.call([{
        methodname: 'mod_readaloud_compare_passage_to_transcript',
        args: {
          cmid: cmid,
          passage: passage,
          transcript: transcript,
          passagephonetic: passagephonetic,
          language: self.language
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
          log.debug(err);
        }
      }]);

    },

      show_results: function(stars) {
            log.debug('show results', stars);
            var self = this;
            //show the results container and hide the practice main stage
            self.controls.mainstage.hide();
            self.controls.resultscontainer.show();

            //show the marked up text
            self.controls.results_text.html(self.controls.targetphrase.html());

            //hide all the star based feedback
            self.controls.results_0stars.hide();
            self.controls.results_1stars.hide();
            self.controls.results_2stars.hide();
            self.controls.results_3stars.hide();
            self.controls.results_4stars.hide();
            self.controls.results_5stars.hide();

            //based on star count show the right feedback
            switch (stars) {
                case 0:
                    self.controls.results_0stars.show();
                    break;
                case 1:
                    self.controls.results_1stars.show();
                    break;
                case 2:
                    self.controls.results_2stars.show();
                    break;
                case 3:
                    self.controls.results_3stars.show();
                    break;
                case 4:
                    self.controls.results_4stars.show();
                    break;
                case 5:
                    self.controls.results_5stars.show();
                    break;
                default:
                    self.controls.results_0stars.show();
            }
      },

      mobile_user: function() {

          if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
              return true;
          } else {
              return false;
          }
      },

      chrome_user: function(){
          if(/Chrome/i.test(navigator.userAgent)) {
              return true;
          }else{
              return false;
          }
      }
  };
});