define(['jquery', 'core/log', 'mod_readaloud/definitions', 'core/templates', 'core/ajax','mod_readaloud/pollyhelper',
    'mod_readaloud/multichoice', 'mod_readaloud/multiaudio',
        'mod_readaloud/page', 'mod_readaloud/shortanswer',
        'mod_readaloud/listeninggapfill', 'mod_readaloud/typinggapfill', 'mod_readaloud/speakinggapfill',
        'mod_readaloud/freespeaking', 'mod_readaloud/freewriting'],
  function($, log, def, templates, Ajax, polly, multichoice, multiaudio,
           page, shortanswer, listeninggapfill, typinggapfill, speakinggapfill, freespeaking, freewriting) {
    "use strict"; // jshint ;_;

    /*
    This file is to manage the quiz stage
     */

    log.debug('Readaloud Quiz helper: initialising');

    return {

      //original spliton_regexp: new RegExp(/([,.!?:;" ])/, 'g'),
        // V2 spliton_regexp new RegExp(/([!"# ¡¿$%&'()。「」、*+,-.\/:;<=>?@[\]^_`{|}~])/, 'g'),
        //v3 we removed the apostrophe because it was not counting words correcting in listen and speak
      spliton_regexp: new RegExp(/([!"# ¡¿$%&()。「」、*+,-.\/:;<=>?@[\]^_`{|}~])/, 'g'),
      //nopunc is diff to split on because it does not match on spaces
      nopunc_regexp: new RegExp(/[!"#¡¿$%&'()。「」、*+,-.\/:;<=>?@[\]^_`{|}~]/,'g'),
      nonspaces_regexp: new RegExp(/[^ ]/,'g'),
      autoplaydelay: 800,

      controls: {},
      submitbuttonclass: 'mod_readaloud_quizsubmitbutton',
      stepresults: [],

      init: function( activitydata, cmid, attemptid) {
        log.debug(activitydata);
        this.quizdata = activitydata.quizdata;
        this.region = activitydata.region;
        this.ttslanguage = activitydata.ttslanguage;
        this.controls.quizcontainer = $("." + activitydata.quizcontainer);
        this.controls.quizitemscontainer = $("." + activitydata.quizitemscontainer);
        this.controls.quizresultscontainer = $("." + activitydata.quizresultscontainer);
        this.attemptid = attemptid;
        this.courseurl = activitydata.courseurl;
        this.cmid = cmid;
        this.reattempturl = decodeURIComponent(activitydata.reattempturl).replace(/&amp;/g, "&");
        this.activityurl = decodeURIComponent(activitydata.activityurl).replace(/&amp;/g, "&");
        this.backtocourse = activitydata.backtocourse;
        this.stt_guided = activitydata.stt_guided;
        this.wwwroot = activitydata.wwwroot;
        this.useanimatecss  = activitydata.useanimatecss;
        this.showqreview  = activitydata.showqreview;
        
  

        polly.init(this.quizdata.token,this.quizdata.region,this.quizdata.owner);
        this.prepare_html();
        this.init_questions(this.quizdata);
        this.register_events();
        this.start_quiz();
      },

      // Callback for when the quiz is complete, overridden by activity controller.
      on_complete: function() {},

      prepare_html: function() {

        // this.controls.quizcontainer.append(submitbutton);
        this.controls.quizfinished=$("#mod_readaloud_quiz_finished");

      },

      init_questions: function(quizdata) {
        var dd = this;
        $.each(quizdata, function(index, item) {
          switch (item.type) {
            case def.qtype_multichoice:
              multichoice.clone().init(index, item, dd);
              break;
            case def.qtype_multiaudio:
                multiaudio.clone().init(index, item, dd);
                break;

             case def.qtype_page:
                  page.clone().init(index, item, dd);
                  break;

              case def.qtype_shortanswer:
                  shortanswer.clone().init(index, item, dd);
                  break;

              case def.qtype_listeninggapfill:
                  listeninggapfill.clone().init(index, item, dd);
                  break;

              case def.qtype_typinggapfill:
                  typinggapfill.clone().init(index, item, dd);
                  break;

              case def.qtype_speakinggapfill:
                  speakinggapfill.clone().init(index, item, dd);
                  break;

              case def.qtype_freespeaking:
                freespeaking.clone().init(index, item, dd);
                break;
                
              case def.qtype_freewriting:
                freewriting.clone().init(index, item, dd);
                break;
                
          }

        });

        //TTS in question headers
          $("audio.mod_readaloud_itemttsaudio").each(function(){
              var that=this;
              polly.fetch_polly_url($(this).data('text'), $(this).data('ttsoption'), $(this).data('voice')).then(function(audiourl) {
                  $(that).attr("src", audiourl);
              });
          });

      },

      register_events: function() {
        $('.' + this.submitbuttonclass).on('click', function() {
          //do something
        });
      },
      render_quiz_progress:function(current,total){
        var array = [];
        for(var i=0;i<total;i++){
          array.push(i);
        }

        if(total<6) {
            var slice = array.slice(0, 5);
            var linestyles = "width: " + (100 - 100 / slice.length) + "%; margin-left: auto; margin-right: auto";
            var html = "<div class='readaloud_quiz_progress_line' style='" + linestyles + "'></div>";

            slice.forEach(function (i) {
                html += "<div class='readaloud_quiz_progress_item " + (i === current ? 'readaloud_quiz_progress_item_current' : '') + " " + (i < current ? 'readaloud_quiz_progress_item_completed' : '') + "'>" + (i + 1) + "</div>";
            });
        }else {
             if(current > total-6){
                 var slice = array.slice(total-5, total-1);
             }else{
                 var slice = array.slice(current, current + 4);
             }

              //if first item is visible then no line trailing left of item 1
              if(current==0){
                  var linestyles = "width: 80%; margin-left: auto; margin-right: auto";
              }else {
                  var linestyles = "width: " + (100 - 100 / (2 *slice.length)) + "%; margin-left: 0";
              }
            var html = "<div class='readaloud_quiz_progress_line' style='" + linestyles + "'></div>";
              slice.forEach(function (i) {
                  html += "<div class='readaloud_quiz_progress_item " + (i === current ? 'readaloud_quiz_progress_item_current' : '') + " " + (i < current ? 'readaloud_quiz_progress_item_completed' : '') + "'>" + (i + 1) + "</div>";
              });
              //end marker
            html += "<div class='readaloud_quiz_progress_finalitem'>" + (total) + "</div>";
          }

        html+="";
        $(".readaloud_quiz_progress").html(html);

      },

      do_next: function(stepdata){
        var dd = this;
        //get current question
        var currentquizdataindex =   stepdata.index;
        var currentitem = this.quizdata[currentquizdataindex];

        //in preview mode do no do_next
        if(currentitem.preview===true){return;}

        //post grade
         // log.debug("reporting step grade");
        dd.report_step_grade(stepdata);
         // log.debug("reported step grade");
        
        var theoldquestion = $("#" + currentitem.uniqueid + "_container");
        
        //show next question or End Screen
        if (dd.quizdata.length > currentquizdataindex+1) {
          var nextindex = currentquizdataindex+ 1;
          var nextitem = this.quizdata[nextindex];
          //hide current question
          theoldquestion.hide();
            //show the question
            $("#" + nextitem.uniqueid + "_container").show();
          //any per question type init that needs to occur can go here
          switch (nextitem.type) {
              case def.qtype_speechcards:
                  //speechcards.init(nextindex, nextitem, dd);
                  break;
              case def.qtype_dictation:
              case def.qtype_dictationchat:
              case def.qtype_multichoice:
              case def.qtype_multiaudio:
              case def.qtype_listenrepeat:
              case def.qtype_smartframe:
              case def.qtype_shortanswer:
              case def.qtype_spacegame:
              case def.qtype_fluency:
              case def.qtype_freespeaking:
              case def.qtype_freewriting:
              case def.qtype_passagereading:
              case def.qtype_buttonquiz:
              case def.qtype_conversation:
              case def.qtype_compquiz:
              default:
          }//end of nextitem switch

            //autoplay audio if we need to
            var ttsquestionplayer = $("#" + nextitem.uniqueid + "_container audio.mod_readaloud_itemttsaudio");
            if(ttsquestionplayer.data('autoplay')=="1"){
                var that=this;
                setTimeout(function() {ttsquestionplayer[0].play();}, that.autoplaydelay);
            }

        } else {
            // Alert server and activity controller that the quiz is complete
            dd.on_complete();

            //just reload and re-fetch all the data to display
              $(".readaloud_nextbutton").prop("disabled", true);
              //fetch the results and display them
              var resultpromise = Ajax.call([{
                methodname: 'mod_readaloud_fetch_quiz_results',
                args: {
                  cmid: dd.cmid
                },
                async: false
              }])[0];
            //load the results into the quiz finished container
            resultpromise.then(function(jsonresults){
                var results = JSON.parse(jsonresults);
                log.debug(results);
                templates.render('mod_readaloud/quizfinished', results).then(
                  function(html,js){
                      dd.controls.quizresultscontainer.html(html);
                      dd.controls.quizresultscontainer.show();
                      dd.controls.quizitemscontainer.hide();
                      templates.runTemplateJS(js);
                });
            });
            return;
        
        }//end of if has more questions

        dd.render_quiz_progress(stepdata.index+1,this.quizdata.length);
        //we want to destroy the old question in the DOM also because iframe/media content might be playing
        theoldquestion.remove();
        
      },

      report_step_grade: function(stepdata) {
        var dd = this;

        //store results locally
        this.stepresults.push(stepdata);

        //push results to server
        var ret = Ajax.call([{
          methodname: 'mod_readaloud_report_quizstep_grade',
          args: {
            cmid: dd.cmid,
            step: JSON.stringify(stepdata),
          },
          async: false
        }])[0];
        log.debug("report_step_grade success: " + ret);

      },



      start_quiz: function() {
        if (this.quizdata == null || this.quizdata.length == 0) { return; }
        $("#" + this.quizdata[0].uniqueid + "_container").show();
          //autoplay audio if we need to
          var ttsquestionplayer = $("#" + this.quizdata[0].uniqueid + "_container audio.mod_readaloud_itemttsaudio");
          if(ttsquestionplayer.data('autoplay')=="1"){
              var that=this;
              setTimeout(function() {ttsquestionplayer[0].play();}, that.autoplaydelay);
          }
        this.render_quiz_progress(0,this.quizdata.length);
      },

      //this function is overridden by the calling class
      onSubmit: function() {
        alert('quiz submitted. Override this');
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
        },

        //this will always be true these days
        use_ttrecorder: function(){
            return true;
        },
        is_stt_guided: function(){
          return this.stt_guided;
        },

        //count words
        count_words: function(transcript) {
          return transcript.trim().split(/\s+/).filter(function(word) {
              return word.length > 0;
          }).length;
        },

        //text comparison functions follow===============

        similarity: function(s1, s2) {
            //we remove spaces because JP transcript and passage might be different. And who cares about spaces anyway?
            s1 = s1.replace(/\s+/g, '');
            s2 = s2.replace(/\s+/g, '');

            var longer = s1;
            var shorter = s2;
            if (s1.length < s2.length) {
                longer = s2;
                shorter = s1;
            }
            var longerLength = longer.length;
            if (longerLength === 0) {
                return 100;
            }
            return 100 * ((longerLength - this.editDistance(longer, shorter)) / parseFloat(longerLength));
        },
        editDistance: function(s1, s2) {
            s1 = s1.toLowerCase();
            s2 = s2.toLowerCase();

            var costs = [];
            for (var i = 0; i <= s1.length; i++) {
                var lastValue = i;
                for (var j = 0; j <= s2.length; j++) {
                    if (i === 0) {
                        costs[j] = j;
                    }else {
                        if (j > 0) {
                            var newValue = costs[j - 1];
                            if (s1.charAt(i - 1) !== s2.charAt(j - 1)) {
                                newValue = Math.min(Math.min(newValue, lastValue),
                                    costs[j]) + 1;
                            }
                            costs[j - 1] = lastValue;
                            lastValue = newValue;
                        }
                    }
                }
                if (i > 0) {
                    costs[s2.length] = lastValue;
                }
            }
            return costs[s2.length];
        },

        cleanText: function(text) {
            var lowertext = text.toLowerCase();
            var punctuationless = lowertext.replace(this.nopunc_regexp,"");
            var ret = punctuationless.replace(/\s+/g, " ").trim();
            return ret;
        },

        //this will return the promise, the result of which is an integer 100 being perfect match, 0 being no match
        checkByPhonetic: function(passage, transcript, passagephonetic, language) {
            return Ajax.call([{
                methodname: 'mod_readaloud_check_by_phonetic',
                args: {
                    'spoken': transcript,
                    'correct': passage,
                    'language': language,
                    'phonetic': passagephonetic,
                    'region': this.region,
                    'cmid': this.cmid
                },
                async: false
            }])[0];

        },

       comparePassageToTranscript: function (passage,transcript,passagephonetic,language,alternatives=""){
          return Ajax.call([{
               methodname: 'mod_readaloud_compare_passage_to_transcript',
               args: {
                   passage: passage,
                   transcript: transcript,
                   //alternatives: alternatives,
                   passagephonetic: '',//passagephonetic,
                   language: language,
                   //region: this.region,
                   cmid: this.cmid
               },
              async: false
           }])[0];
       },

      //this will return the promise, the result of which is an integer 100 being perfect match, 0 being no match
      evaluateTranscript: function(transcript, itemid) {
        return Ajax.call([{
            methodname: 'mod_readaloud_evaluate_transcript',
            args: {
                'transcript': transcript,
                'itemid': itemid,
                'cmid': this.cmid
            },
            async: false
        }])[0];
      },

    }; //end of return value
  });
