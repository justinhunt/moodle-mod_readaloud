define(['jquery',
      'core/log',
      'mod_readaloud/definitions',
      'mod_readaloud/pollyhelper',
      'mod_readaloud/cloudpoodllloader',
      'mod_readaloud/ttrecorder',
      'mod_readaloud/animatecss'],
    function($, log, def, polly,cloudpoodll, ttrecorder, anim) {
  "use strict"; // jshint ;_;

  /*
  This file is to manage the quiz stage
   */

  log.debug('Readaloud ShortAnswer: initialising');

  return {

    //a handle on the tt recorder
    ttrec: null,

    passmark: 90,//lower this if it often doesnt match (was 85)

    //for making multiple instances
      clone: function () {
          return $.extend(true, {}, this);
     },

    init: function(index, itemdata, quizhelper) {

      //anim
      var animopts = {};
      animopts.useanimatecss=quizhelper.useanimatecss;
      anim.init(animopts);

      this.register_events(index, itemdata, quizhelper);
      this.init_components(index, itemdata, quizhelper);
    },
    next_question: function(percent) {
      var self = this;
      var stepdata = {};
      stepdata.index = self.index;
      stepdata.hasgrade = true;
      stepdata.totalitems = 1;
      stepdata.correctitems = percent>0?1:0;
      stepdata.grade = percent;
      self.quizhelper.do_next(stepdata);
    },

    /* NOT NEEDED */
    prepare_audio: function(itemdata) {
      // debugger;
      $.each(itemdata.sentences, function(index, sentence) {
        polly.fetch_polly_url(sentence.sentence, itemdata.voiceoption, itemdata.usevoice).then(function(audiourl) {
          $("#" + itemdata.uniqueid + "_option" + (index+1)).attr("data-src", audiourl);
        });
      });
    },
    
    register_events: function(index, itemdata, quizhelper) {
      
      var self = this;
      self.index = index;
      self.quizhelper = quizhelper;
      
      $("#" + itemdata.uniqueid + "_container .readaloud_nextbutton").on('click', function(e) {
        self.next_question(0);
      });
      
      $("#" + itemdata.uniqueid + "_container ." + itemdata.uniqueid + "_option").on('click', function(e) {
        
        $("." + itemdata.uniqueid + "_option").prop("disabled", true);
        $("." + itemdata.uniqueid + "_fb").html("<i style='color:red;' class='fa fa-times'></i>");
        $("." + itemdata.uniqueid + "_option" + itemdata.correctanswer + "_fb").html("<i style='color:green;' class='fa fa-check'></i>");
        
        var checked = $('input[name='+itemdata.uniqueid+'_options]:checked').data('index');
        var percent = checked == itemdata.correctanswer ? 100 : 0;
        
        $(".readaloud_nextbutton").prop("disabled", true);
        setTimeout(function() {
          $(".readaloud_nextbutton").prop("disabled", false);
          self.next_question(percent);
        }, 2000);
        
      });
      
    },

    init_components: function(index, itemdata, quizhelper) {
      var app= this;
      var sentences = itemdata.sentences;//sentence & phonetic

      log.debug('initcomponents_shortanswer');
      log.debug(sentences);

      //clean the text of any junk
      for(var i=0;i<sentences.length;i++){
          sentences[i].originalsentence= sentences[i].sentence;
          sentences[i].sentence=quizhelper.cleanText(sentences[i].sentence);
      }

      var theCallback = async function(message) {

        switch (message.type) {
          case 'recording':

            break;

          case 'speech':
            log.debug("speech at shortanswer");
            var speechtext = message.capturedspeech;
            var cleanspeechtext = quizhelper.cleanText(speechtext);
            var spoken = cleanspeechtext;

            log.debug('speechtext:',speechtext);
            log.debug('cleanspeechtext:',spoken);
            var matched=false;
            var percent=0;

            //Similarity check by direct-match/acceptable-mistranscriptio
            for(var x=0;x<sentences.length;x++){
              //if this is the correct answer index, just move on
              if(sentences[x].sentence===''){continue;}
              var similar = quizhelper.similarity(spoken, sentences[x].sentence);
              log.debug('JS similarity: ' + spoken + ':' + sentences[x].sentence + ':' + similar);
              if (similar >= app.passmark ||
                  app.spokenIsCorrect(quizhelper, cleanspeechtext, sentences[x].sentence)) {
                  percent = app.process_accepted_response(itemdata, x);
                  matched=true;
                  break;
              }//end of if similarity
            }//end of for x

            

            //we do not do a passage match check , but this is how we would ..
              if(!matched ) {
                for (x = 0; x < sentences.length; x++) {
                  var ajaxresult = await quizhelper.comparePassageToTranscript(sentences[x].sentence, spoken, sentences[x].phonetic, itemdata.language);
                  var result = JSON.parse(ajaxresult);
                  var haserror=false;
                  for (var i=0;i<result.length;i++){
                    if(result[i].matched===false){haserror=true;break;}
                  }
                  if(!haserror){
                    percent = app.process_accepted_response(itemdata, x);
                    matched=true;
                    break;
                  }
                }
              }

              //if we got a match then process it
            if(matched){
              //proceed to next question
              $(".readaloud_nextbutton").prop("disabled", true);
              setTimeout(function () {
                $(".readaloud_nextbutton").prop("disabled", false);
                app.next_question(percent);
              }, 2000);
              return;
            }else{
              //shake the screen
              var theanswer = $("#" + itemdata.uniqueid + "_correctanswer");
              anim.do_animate(theanswer,'rubberBand animate__faster').then(
                  function() {}
              );
              //$("#" + itemdata.uniqueid + "_correctanswer").effect("shake");
            }
        } //end of switch message type
      }; //end of callback declaration

      //init TT recorder
      var opts = {};
      opts.uniqueid = itemdata.uniqueid;
      log.debug('sa uniqueid:' + itemdata.uniqueid);
      opts.callback = theCallback;
      opts.stt_guided=quizhelper.is_stt_guided();
      app.ttrec = ttrecorder.clone();
      app.ttrec.init(opts);

      //set the prompt for TT Rec
      var allsentences="";
      for(var i=0;i<sentences.length;i++){
        allsentences += sentences[i].sentence + ' ';
        sentences[i].originalsentence= sentences[i].sentence;
        sentences[i].sentence=quizhelper.cleanText(sentences[i].sentence);
      }
      app.ttrec.currentPrompt=allsentences;


    } ,//end of init components

    spokenIsCorrect: function(quizhelper, phraseheard, currentphrase) {
      //lets lower case everything
      phraseheard = quizhelper.cleanText(phraseheard);
      currentphrase = quizhelper.cleanText(currentphrase);
      if (phraseheard === currentphrase) {
        return true;
      }
      return false;
    },

    process_accepted_response: function(itemdata, sentenceindex){
      var percent = sentenceindex >= 0 ? 100 : 0;
      //TO DO .. disable TT recorder here
      //disable TT recorder

      if(percent > 0) {
        //turn dots into text (if they were dots)
        if (parseInt(itemdata.show_text) === 0) {
          for (var i = 0; i < itemdata.sentences.length; i++) {
            var theline = $("#" + itemdata.uniqueid + "_option" + (i + 1));
            $("#" + itemdata.uniqueid + "_option" + (i + 1) + ' .readaloud_sentence').text(itemdata.sentences[i].sentence);
          }
        }

        //hightlight successgit cm
        var  answerdisplay =  $("#" + itemdata.uniqueid + "_correctanswer");
        answerdisplay.text(itemdata.sentences[sentenceindex].originalsentence);
        answerdisplay.addClass("readaloud_success");
      }

      return percent;

    },

  };
});