define(['jquery', 'core/log', 'mod_readaloud/definitions', 'mod_readaloud/correctionsmarkup', 'core/templates'],
    function($, log, def,  correctionsmarkup, templates) {
  "use strict"; // jshint ;_;

  /*
  This file is to manage the free writing item type
   */

  log.debug('Readaloud FreeWriting: initialising');

      return {

        transcript_evaluation: null,
        rawscore: 0,
        percentscore: 0,

        //for making multiple instances
        clone: function () {
          return $.extend(true, {}, this);
        },

        init: function(index, itemdata, quizhelper) {
          this.itemdata = itemdata;
          this.quizhelper = quizhelper;
          this.init_components(quizhelper,itemdata);
          this.register_events(index, itemdata, quizhelper);
        },

        next_question: function() {
          var self = this;
          var stepdata = {};
          stepdata.index = self.index;
          stepdata.hasgrade = true;
          stepdata.totalitems = self.itemdata.totalmarks;
          stepdata.correctitems = self.rawscore > 0 ? self.rawscore : 0;
          stepdata.grade = self.percentscore;
          stepdata.resultsdata = self.transcript_evaluation;
          self.quizhelper.do_next(stepdata);
        },

        calculate_score: function(transcript_evaluation) {
          var self = this;

          if(transcript_evaluation === null){
            return 0;
          }

          //words ratio
          var wordsratio = 1;
          if(self.itemdata.countwords) {
            wordsratio = transcript_evaluation.stats.words / self.itemdata.targetwordcount;
            if(wordsratio > 1){wordsratio = 1;}
          }

          //relevance
          var relevanceratio = 1;
          if(self.itemdata.relevance > 0){
            relevanceratio = (transcript_evaluation.stats.relevance + 10) / 100;
            if(relevanceratio > 1){relevanceratio = 1;}
          }
          //calculate score based on AI grade * relevance * wordcount
          var score = Math.round(transcript_evaluation.marks * relevanceratio * wordsratio);
          return score;
        },

        register_events: function(index, itemdata, quizhelper) {
          var self = this;
          self.index = index;
          self.quizhelper = quizhelper;

          self.nextbutton.on('click', function(e) {
            self.next_question();
          });

          self.thetextarea.on('input', function(e) {
            e.preventDefault();
            var wordcount = self.quizhelper.count_words(self.thetextarea.val());
            self.wordcount.text(wordcount);
          });

          self.submitbutton.on('click', function(e) {
            e.preventDefault();
            var transcript = self.thetextarea.val();
            //update the wordcount
            var wordcount = self.quizhelper.count_words(transcript);
            self.wordcount.text(wordcount);

            self.do_evaluation(transcript);
          });

        },

        init_components: function(quizhelper,itemdata){
          var self=this;
          self.allwords = $("#" + self.itemdata.uniqueid + "_container.mod_readaloud_mu_passage_word");
          self.submitbutton = $("#" + itemdata.uniqueid + "_container .ml_freewriting_submitbutton");
          self.nextbutton = $("#" + itemdata.uniqueid + "_container .readaloud_nextbutton");
          self.thetextarea = $("#" + self.itemdata.uniqueid + "_container .ml_freewriting_textarea");
          self.wordcount = $("#" + self.itemdata.uniqueid + "_container span.ml_wordcount");
          self.actionbox = $("#" + self.itemdata.uniqueid + "_container div.ml_freewriting_actionbox");
          self.pendingbox = $("#" + self.itemdata.uniqueid + "_container div.ml_freewriting_pendingbox");
          self.resultsbox = $("#" + self.itemdata.uniqueid + "_container div.ml_freewriting_resultsbox");
          self.timerdisplay = $("#" + self.itemdata.uniqueid + "_container div.ml_freewriting_timerdisplay");
        }, //end of init components

        do_corrections_markup: function(grammarerrors,grammarmatches,insertioncount) {
          var self = this;
          //corrected text container is created at runtime, so it wont exist at init_components time
          //thats we find it here
          var correctionscontainer = self.resultsbox.find('.mlfsr_correctedtext');

          correctionsmarkup.init({ "correctionscontainer": correctionscontainer,
            "grammarerrors": grammarerrors,
            "grammarmatches": grammarmatches,
            "insertioncount": insertioncount});
        },

        do_evaluation: function(speechtext) {
          var self = this;

          //show a spinner while we do the AI stuff
          self.resultsbox.hide();
          self.actionbox.hide();
          self.pendingbox.show();

          //do evaluation
          this.quizhelper.evaluateTranscript(speechtext,this.itemdata.itemid).then(function(ajaxresult) {
            var transcript_evaluation = JSON.parse(ajaxresult);
            if (transcript_evaluation) {
              //calculate raw score and percent score
              transcript_evaluation.rawscore = self.calculate_score(transcript_evaluation);
              self.rawscore = self.calculate_score(transcript_evaluation);
              if(self.itemdata.totalmarks > 0){
                self.percentscore = Math.round((self.rawscore / self.itemdata.totalmarks) * 100);
              }
              //add raw and percent score to trancript_evaluation for mustache
              transcript_evaluation.rawscore = self.rawscore;
              transcript_evaluation.percentscore = self.percentscore;
              transcript_evaluation.rawspeech = speechtext;
              transcript_evaluation.maxscore = self.itemdata.totalmarks;
              self.transcript_evaluation = transcript_evaluation;

              log.debug(transcript_evaluation);
              //display results
              templates.render('mod_readaloud/freewritingresults',transcript_evaluation).then(
                  function(html,js){
                    self.resultsbox.html(html);
                    //do corrections markup
                    if(transcript_evaluation.hasOwnProperty('grammarerrors')){
                      self.do_corrections_markup(transcript_evaluation.grammarerrors,
                          transcript_evaluation.grammarmatches,
                          transcript_evaluation.insertioncount
                      );
                    }
                    //show and hide
                    self.resultsbox.show();
                    self.pendingbox.hide();
                    self.actionbox.hide();
                    templates.runTemplateJS(js);
                    //reset timer and wordcount on this page, in case reattempt
                    self.wordcount.text('0');
                    self.ttrec.timer.reset();
                    var displaytime = self.ttrec.timer.fetch_display_time();
                    self.timerdisplay.html(displaytime);
                  }
              );// End of templates
            } else {
              log.debug('transcript_evaluation: oh no it failed');
              self.resultsbox.hide();
              self.pendingbox.hide();
              self.actionbox.show();
            }
          });
        },
      };
});