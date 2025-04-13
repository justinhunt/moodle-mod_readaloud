define(['jquery',
    'core/log',
    'core/ajax',
    'mod_readaloud/definitions',
    'mod_readaloud/pollyhelper',
    'mod_readaloud/ttrecorder',
    'mod_readaloud/animatecss',
    'mod_readaloud/progresstimer',
    'core/templates'
], function($, log, ajax, def, polly,  ttrecorder, anim, progresstimer, templates) {
    "use strict"; // jshint ;_;

    log.debug('Readaloud speaking gap fill: initialising');

    return {

        //a handle on the tt recorder
        ttrec: null,

        // For making multiple instances
        clone: function() {
            return $.extend(true, {}, this);
        },

        init: function(index, itemdata, quizhelper) {
            var self = this;
            var theCallback = function(message) {

                switch (message.type) {
                    case 'recording':
                        break;

                    case 'speech':
                        log.debug("Speech at speaking gap fill -");
                        var words = self.items[self.game.pointer].words;
                        var maskedwords = [];

                        Object.keys(words).forEach(function(key) {
                            maskedwords.push(words[key]);
                        });

                        self.getComparison(
                            maskedwords.join(" "),
                            message.capturedspeech,
                            self.items[self.game.pointer].phonetic,
                            function(comparison) {
                                self.gotComparison(comparison, message);
                            }
                        );
                        break;

                }

            };

            // Set up the ttrecorder
            var opts = {};
            opts.uniqueid = itemdata.uniqueid;
            opts.callback = theCallback;
            opts.stt_guided = quizhelper.is_stt_guided();
            opts.wwwroot = quizhelper.is_stt_guided();
            self.ttrec = ttrecorder.clone();
            self.ttrec.init(opts);

            self.itemdata = itemdata;
            log.debug("itemdata");
            log.debug(itemdata);
            self.quizhelper = quizhelper;
            self.index = index;

            // Anim
            var animopts = {};
            animopts.useanimatecss = quizhelper.useanimatecss;
            anim.init(animopts);

            self.register_events();
            self.setvoice();
            self.getItems();
        },

        next_question: function(percent) {
            var self = this;
            var stepdata = {};
            stepdata.index = self.index;
            stepdata.hasgrade = true;
            stepdata.totalitems = self.items.length;
            stepdata.correctitems = self.items.filter(function(e) {
                return e.correct;
            }).length;
            stepdata.grade = Math.round((stepdata.correctitems / stepdata.totalitems) * 100);
            self.quizhelper.do_next(stepdata);
        },

        show_item_review:function(){
            var self=this;
            var review_data = {};
            review_data.items = self.items;
            review_data.totalitems=self.items.length;
            review_data.correctitems=self.items.filter(function(e) {return e.correct;}).length;

            //Get controls
            var listencont = $("#" + self.itemdata.uniqueid + "_container .sgapfill_listen_cont");
            var qbox = $("#" + self.itemdata.uniqueid + "_container .question");
            var recorderbox = $("#" + self.itemdata.uniqueid + "_container .sgapfill_speakbtncontainer");
            var gamebox = $("#" + self.itemdata.uniqueid + "_container .sgapfill_game");
            var controlsbox = $("#" + self.itemdata.uniqueid + "_container .sgapfill_controls");
            var resultsbox = $("#" + self.itemdata.uniqueid + "_container .sgapfill_resultscontainer");

            //display results
            templates.render('mod_readaloud/listitemresults',review_data).then(
              function(html,js){
                  resultsbox.html(html);
                  //show and hide
                  resultsbox.show();
                  gamebox.hide();
                  controlsbox.hide();
                  listencont.hide();
                  qbox.hide();
                  recorderbox.hide();
                  // Run js for audio player events
                  templates.runTemplateJS(js);
              }
            );// End of templates
        },

        register_events: function() {

            var self = this;
            // On next button click
            $("#" + self.itemdata.uniqueid + "_container .readaloud_nextbutton").on('click', function(e) {
                self.next_question();
            });
            // On start button click
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_start_btn").on("click", function() {
                self.start();
            });

            //AUDIO PLAYER Button events
            var audioplayerbtn=$("#" + self.itemdata.uniqueid + "_container .sgapfill_listen_btn");
            // On listen button click
            if(self.itemdata.readsentence) {
                audioplayerbtn.on("click", function () {
                    var theaudio = self.items[self.game.pointer].audio;

                    //if we are already playing stop playing
                    if(!theaudio.paused){
                        theaudio.pause();
                        theaudio.currentTime=0;
                        $(audioplayerbtn).children('.fa').removeClass('fa-stop');
                        $(audioplayerbtn).children('.fa').addClass('fa-volume-up');
                        return;
                    }

                    //change icon to indicate playing state
                    theaudio.addEventListener('ended', function () {
                        $(audioplayerbtn).children('.fa').removeClass('fa-stop');
                        $(audioplayerbtn).children('.fa').addClass('fa-volume-up');
                    });

                    theaudio.addEventListener('play', function () {
                        $(audioplayerbtn).children('.fa').removeClass('fa-volume-up');
                        $(audioplayerbtn).children('.fa').addClass('fa-stop');
                    });
                    theaudio.load();
                    theaudio.play();
                });
            }

            // On skip button click
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_skip_btn").on("click", function() {
                // Disable the buttons
                $("#" + self.itemdata.uniqueid + "_container .sgapfill_ctrl-btn").prop("disabled", true);
                // Reveal the prompt
                $("#" + self.itemdata.uniqueid + "_container .sgapfill_speech.sgapfill_teacher_left").text(self.items[self.game.pointer].prompt + "");
                // Reveal the answer
                $("#" + self.itemdata.uniqueid + "_container .sgapfill_targetWord").each(function() {
                    var realidx = $(this).data("realidx");
                    var sgapfill_targetWord = self.items[self.game.pointer].sgapfill_targetWords[realidx];
                    $(this).val(sgapfill_targetWord);
                });

                self.stopTimer(self.items[self.game.pointer].timer);

                //mark as answered and incorrect
                self.items[self.game.pointer].answered = true;
                self.items[self.game.pointer].correct = false;

                if (self.game.pointer < self.items.length - 1) {
                    // Move on after short time to next prompt
                    setTimeout(function() {
                        $("#" + self.itemdata.uniqueid + "_container .sgapfill_reply_" + self.game.pointer).hide();
                        self.game.pointer++;
                        self.nextPrompt();
                    }, 2000);
                    // End question
                } else {
                    self.end();
                }

            });

        },

        game: {
            pointer: 0
        },

        usevoice: '',

        setvoice: function() {
            var self = this;
            self.usevoice = self.itemdata.usevoice;
            self.voiceoption = self.itemdata.voiceoption;
            return;
        },

        getItems: function() {
            var self = this;
            var text_items = self.itemdata.sentences;

            self.items = text_items.map(function(target) {
                return {
                    target: target.sentence,
                    prompt: target.prompt,
                    parsedstring: target.parsedstring,
                    displayprompt: target.displayprompt,
                    definition: target.definition,
                    phonetic: target.phonetic,
                    words: target.words,
                    typed: "",
                    timer: [],
                    answered: false,
                    correct: false,
                    audio: null
                };
            }).filter(function(e) {
                return e.target !== "";
            });

            if(self.itemdata.readsentence) {
                $.each(self.items, function (index, item) {
                    polly.fetch_polly_url(item.prompt, self.voiceoption, self.usevoice).then(function (audiourl) {
                        item.audio = new Audio();
                        item.audio.src = audiourl;
                        if (self.items.filter(function (e) {
                            return e.audio === null;
                        }).length === 0) {
                            self.appReady();
                        }
                    });
                });
            }else{
                self.appReady();
            }


        },

        appReady: function() {
            var self = this;
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_not_loaded").hide();
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_loaded").show();
            if(self.itemdata.hidestartpage){
                self.start();
            }else{
                $("#" + self.itemdata.uniqueid + "_container .sgapfill_start_btn").prop("disabled", false);
            }
        },

        gotComparison: function(comparison, typed) {
            log.debug("sgapfill comparison");
            var self = this;
            var countdownStarted = false;
            var feedback = $("#" + self.itemdata.uniqueid + "_container .sgapfill_reply_" + self.game.pointer + " .dictate_feedback[data-idx='" + self.game.pointer + "']");

            $("#" + self.itemdata.uniqueid + "_container .sgapfill_targetWord").removeClass("sgapfill_correct sgapfill_incorrect");
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_feedback").removeClass("fa fa-check fa-times");

            var allCorrect = comparison.filter(function(e) {
                return !e.matched;
            }).length == 0;
            log.debug('allcorrect=' + allCorrect);

            if (allCorrect && comparison && comparison.length > 0) {

                self.items[self.game.pointer].parsedstring.forEach(function(data, index) {
                    var characterinput = $("#" + self.itemdata.uniqueid + "_container .sgapfill_reply_" + self.game.pointer + ' input.single-character[data-index="' + index + '"]');
                    if (data.type === 'input') {
                        characterinput.val(data.character);
                    }
                });

                feedback.removeClass("fa fa-times");
                feedback.addClass("fa fa-check");
                //make the input boxes green and move forward
                log.debug('applying correct class to input boxes');
                $("#" + self.itemdata.uniqueid + "_container .sgapfill_reply_" + self.game.pointer + " input").addClass("ra_gapfill_char_correct");

                self.items[self.game.pointer].answered = true;
                self.items[self.game.pointer].correct = true;
                self.items[self.game.pointer].typed = typed;

                $("#" + self.itemdata.uniqueid + "_container .sgapfill_ctrl-btn").prop("disabled", true);

                self.stopTimer(self.items[self.game.pointer].timer);

                if ((self.game.pointer < self.items.length - 1) && !countdownStarted) {
                    countdownStarted = true;
                    log.debug('moving to next prompt B');
                    setTimeout(function() {
                        $("#" + self.itemdata.uniqueid + "_container .sgapfill_reply_" + self.game.pointer).hide();
                        self.game.pointer++;
                        self.nextPrompt();
                    }, 2000);
                } else {
                    self.updateProgressDots();
                    self.end();
                }
            } else {
                feedback.removeClass("fa fa-check");
                feedback.addClass("fa fa-times");
                // Mark up the words as correct or not
                comparison.forEach(function(obj) {
                    var words = self.items[self.game.pointer].words;

                    Object.keys(words).forEach(function(key) {
                        if (words[key] == obj.word) {
                            if (!obj.matched) {
                                self.items[self.game.pointer].parsedstring.forEach(function(data, index) {
                                    var characterinput = $("#" + self.itemdata.uniqueid + "_container .sgapfill_reply_" + self.game.pointer + ' input.single-character[data-index="' + index + '"]');
                                    if (data.index == key && data.type === 'input') {
                                        characterinput.val('');
                                    }
                                });
                            } else {
                                self.items[self.game.pointer].parsedstring.forEach(function(data, index) {
                                    var characterinput = $("#" + self.itemdata.uniqueid + "_container .sgapfill_reply_" + self.game.pointer + ' input.single-character[data-index="' + index + '"]');
                                    if (data.index == key && data.type === 'input') {
                                        characterinput.val(data.character);
                                    }
                                });
                            }
                        }
                    });
                });
                var thereply = $("#" + self.itemdata.uniqueid + "_container .sgapfill_reply_" + self.game.pointer);
                anim.do_animate(thereply, 'shakeX animate__faster').then(
                    function() {
                        $("#" + self.itemdata.uniqueid + "_container .sgapfill_ctrl-btn").prop("disabled", false);
                    }
                );

                // Show all the correct words
                $("#" + self.itemdata.uniqueid + "_container .sgapfill_targetWord.sgapfill_correct").each(function() {
                    var realidx = $(this).data("realidx");
                    var sgapfill_targetWord = self.items[self.game.pointer].sgapfill_targetWords[realidx];
                    $(this).val(sgapfill_targetWord);
                });

                //if they cant retry OR the time limit is up, move on
                var timelimit_progressbar = $("#" + self.itemdata.uniqueid + "_container .progress-container .progress-bar");
                if(!self.itemdata.allowretry || timelimit_progressbar.hasClass('progress-bar-complete')){
                    self.items[self.game.pointer].answered = true;
                    self.items[self.game.pointer].correct = false;
                    self.items[self.game.pointer].typed = typed;

                    $("#" + self.itemdata.uniqueid + "_container .sgapfill_ctrl-btn").prop("disabled", true);

                    self.stopTimer(self.items[self.game.pointer].timer);

                    if(!countdownStarted) {
                        if (self.game.pointer < self.items.length - 1) {
                            log.debug('moving to next prompt A');
                            countdownStarted = true;
                            setTimeout(function () {
                                $("#" + self.itemdata.uniqueid + "_container .sgapfill_reply_" + self.game.pointer).hide();
                                self.game.pointer++;
                                self.nextPrompt();
                            }, 2000);
                        } else {
                            self.updateProgressDots();
                            self.end();
                        }
                    }//end of if countdown not started
                } //end of if can't retry or time limit up
            }//end of if -all -correct or not
        },

        getComparison: function(passage, transcript, phonetic, callback) {
            var self = this;

            $(".sgapfill_ctrl-btn").prop("disabled", true);
            self.quizhelper.comparePassageToTranscript(passage, transcript, phonetic, self.itemdata.language).then(function(ajaxresult) {
                var payloadobject = JSON.parse(ajaxresult);
                if (payloadobject) {
                    callback(payloadobject);
                } else {
                    callback(false);
                }
            });
        },

        end: function() {
            var self = this;
            $(".readaloud_nextbutton").prop("disabled", true);

            //progress dots are updated on next_item. The last item has no next item, so we update from here
            self.updateProgressDots();

            setTimeout(function() {
                $(".readaloud_nextbutton").prop("disabled",false);
                if(self.quizhelper.showqreview){
                    self.show_item_review();
                }else{
                    self.next_question();
                }
            }, 2000);

        },

        start: function() {
            var self = this;

            $("#" + self.itemdata.uniqueid + "_container .sgapfill_ctrl-btn").prop("disabled", true);
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_speakbtncontainer").show();

            self.items.forEach(function(item) {
                item.spoken = "";
                item.answered = false;
                item.correct = false;
            });

            self.game.pointer = 0;

            $("#" + self.itemdata.uniqueid + "_container .question").show();
            if(self.itemdata.readsentence) {
                $("#" + self.itemdata.uniqueid + "_container .sgapfill_listen_cont").show();
            }
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_start_btn").hide();
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_mainmenu").hide();
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_controls").show();

            self.nextPrompt();
        },

        updateProgressDots: function(){
            var self = this;
            var color;
            var progress = self.items.map(function(item, idx) {
                color = "gray";
                if (self.items[idx].answered && self.items[idx].correct) {
                    color = "green";
                } else if (self.items[idx].answered && !self.items[idx].correct) {
                    color = "red";
                }
                return "<i style='color:" + color + "' class='fa fa-circle'></i>";
            }).join(" ");
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_title").html(progress);
        },

        nextPrompt: function() {
            var self = this;
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_ctrl-btn").prop("disabled", false);
            self.updateProgressDots();
            var newprompt = $("#" + self.itemdata.uniqueid + "_container .sgapfill_prompt_" + self.game.pointer);
            anim.do_animate(newprompt, 'zoomIn animate__faster', 'in').then(
                function() {
                }
            );
            self.nextReply();
        },
        

        nextReply: function() {
            var self = this;

            var code = "<div class='sgapfill_reply sgapfill_reply_" + self.game.pointer + " text-center' style='display:none;'>";

            code += "<div class='form-container'>";
            self.items[self.game.pointer].parsedstring.forEach(function(data, index) {
                if (data.type === 'input') {
                    code += "<input class='single-character' autocomplete='off' type='text' name='filltext" + index + "' maxlength='1' data-index='" + index + "' readonly>";
                } else if (data.type === 'mtext') {
                    code += "<input class='single-character-mtext' type='text' name='readonly" + index + "' maxlength='1' value='" + data.character + "' readonly>";
                } else {
                    code += data.character;
                }
            });
            //correct or not
            code += " <i data-idx='" + self.game.pointer + "' class='dictate_feedback'></i></div>";

            //definition
            code += "<div class='definition-container'>";
            code += "<div class='definition'>" + self.items[self.game.pointer].definition + "</div>";
            code += "</div>";


            $("#" + self.itemdata.uniqueid + "_container .question").append(code);
            var newreply = $("#" + self.itemdata.uniqueid + "_container .sgapfill_reply_" + self.game.pointer);
            anim.do_animate(newreply, 'zoomIn animate__faster', 'in').then(
                function() {
                }
            );
            $("#" + self.itemdata.uniqueid + "_container .sgapfill_ctrl-btn").prop("disabled", false);

            if (self.itemdata.timelimit > 0) {
                $("#" + self.itemdata.uniqueid + "_container .progress-container").show();
                $("#" + self.itemdata.uniqueid + "_container .progress-container i").show();
                var progresbar = $("#" + self.itemdata.uniqueid + "_container .progress-container #progresstimer").progressTimer({
                    height: '5px',
                    timeLimit: self.itemdata.timelimit,
                    onFinish: function() {
                        $("#" + self.itemdata.uniqueid + "_container .sgapfill_skip_btn").trigger('click');
                    }
                });

                progresbar.each(function() {
                    self.items[self.game.pointer].timer.push($(this).attr('timer'));
                });
            }

            if (!self.quizhelper.mobile_user()) {
                setTimeout(function() {
                    $("#" + self.itemdata.uniqueid + "_container .sgapfill_listen_btn").trigger('click');
                }, 1000);
            }

            //target is the speech we expect
            var target = self.items[self.game.pointer].target;
            //in some cases ttrecorder wants to know the target
            if(self.quizhelper.use_ttrecorder()) {
                self.ttrec.currentPrompt=target;
            }
        },

        stopTimer: function(timers) {
            if (timers.length) {
                timers.forEach(function(timer) {
                    clearInterval(timer);
                });
            }
        },
    };
});