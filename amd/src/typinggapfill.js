define(['jquery',
    'core/log',
    'core/ajax',
    'mod_readaloud/definitions',
    'mod_readaloud/animatecss',
    'mod_readaloud/progresstimer',
    'core/templates'
], function($, log, ajax, def, anim, progresstimer, templates) {
    "use strict"; // jshint ;_;

    log.debug('Readaloud typing gap fill: initialising');

    return {

        // For making multiple instances
        clone: function() {
            return $.extend(true, {}, this);
        },

        init: function(index, itemdata, quizhelper) {
            var self = this;
            self.itemdata = itemdata;
            self.quizhelper = quizhelper;
            self.index = index;

            var animopts = {};
            animopts.useanimatecss = quizhelper.useanimatecss;
            anim.init(animopts);

            self.register_events();
            self.getItems();
            self.appReady();
        },

        next_question: function() {
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
            var listencont = $("#" + self.itemdata.uniqueid + "_container .tgapfill_listen_cont");
            var qbox = $("#" + self.itemdata.uniqueid + "_container .question");
            //var recorderbox = $("#" + self.itemdata.uniqueid + "_container .tgapfill_speakbtncontainer");
            var gamebox = $("#" + self.itemdata.uniqueid + "_container .tgapfill_game");
            var controlsbox = $("#" + self.itemdata.uniqueid + "_container .tgapfill_controls");
            var resultsbox = $("#" + self.itemdata.uniqueid + "_container .tgapfill_resultscontainer");

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
                  //recorderbox.hide();
                  // Run js for audio player events
                  templates.runTemplateJS(js);
              }
            );// End of templates
        },

        register_events: function() {

            var self = this;

            // Next page button.
            $("#" + self.itemdata.uniqueid + "_container .readaloud_nextbutton").on('click', function(e) {
                self.next_question();
            });

            // Start button.
            $("#" + self.itemdata.uniqueid + "_container .tgapfill_start_btn").on("click", function() {
                self.start();
            });

            // Skip.
            $("#" + self.itemdata.uniqueid + "_container .tgapfill_skip_btn").on("click", function() {
                $(this).prop("disabled", true);
                $("#" + self.itemdata.uniqueid + "_container .tgapfill_check_btn").prop("disabled", true);
                self.stopTimer(self.items[self.game.pointer].timer);

                //mark as answered and incorrect
                self.items[self.game.pointer].answered = true;
                self.items[self.game.pointer].correct = false;

                // Move on after short time, to next prompt, or next question.
                if (self.game.pointer < self.items.length - 1) {
                    setTimeout(function() {
                        $(".tgapfill_reply_" + self.game.pointer).hide();
                        self.game.pointer++;
                        self.nextPrompt();
                    }, 2000);
                } else {
                    self.end();
                }
            });

            // Check.
            $("#" + self.itemdata.uniqueid + "_container .tgapfill_check_btn").on("click", function() {
                self.check_answer();
            });

            // Listen for enter key on input boxes
            $("#" + self.itemdata.uniqueid + "_container").on("keydown", ".single-character", function(e) {
                if (e.which == 13) {
                    self.check_answer();
                }
            });
        },

        game: {
            pointer: 0
        },

        check_answer: function() {
            var self = this;
            var passage = self.items[self.game.pointer].parsedstring;
            var characterunputs = $("#" + self.itemdata.uniqueid + "_container .tgapfill_reply_" + self.game.pointer + ' input.single-character');
            var transcript = [];

            characterunputs.each(function() {
                var index = $(this).data('index');
                var value = $(this).val();
                transcript.push = ({
                    index: index,
                    value: value
                });
            });

            self.getComparison(passage, transcript, function(comparison) {
                self.gotComparison(comparison);
            });
        },

        getItems: function() {
            var self = this;
            var text_items = self.itemdata.sentences;

            self.items = text_items.map(function(target) {
                return {
                    target: target.sentence,
                    prompt: target.prompt,
                    parsedstring: target.parsedstring,
                    definition: target.definition,
                    timer: [],
                    typed: "",
                    answered: false,
                    correct: false,
                    audio: null
                };
            }).filter(function(e) {
                return e.target !== "";
            });
        },

        appReady: function() {
            var self = this;
            $("#" + self.itemdata.uniqueid + "_container .tgapfill_not_loaded").hide();
            $("#" + self.itemdata.uniqueid + "_container .tgapfill_loaded").show();
            if(self.itemdata.hidestartpage){
                self.start();
            }else{
                $("#" + self.itemdata.uniqueid + "_container .tgapfill_start_btn").prop("disabled", false);
            }
        },

        gotComparison: function(comparison) {
            var self = this;
            var timelimit_progressbar = $("#" + self.itemdata.uniqueid + "_container .progress-container .progress-bar");
            if (comparison) {
                $("#" + self.itemdata.uniqueid + "_container .tgapfill_reply_" + self.game.pointer + " .tgapfill_feedback[data-idx='" + self.game.pointer + "']").addClass("fa fa-check");
                self.items[self.game.pointer].answered = true;
                self.items[self.game.pointer].correct = true;
                self.items[self.game.pointer].typed = false;
                //make the input boxes green and move forward
                $("#" + self.itemdata.uniqueid + "_container .tgapfill_reply_" + self.game.pointer + " input").addClass("ra_gapfill_char_correct");

            //if they cant retry OR the time limit is up, move on
            } else if(!self.itemdata.allowretry || timelimit_progressbar.hasClass('progress-bar-complete')) {
                $("#" + self.itemdata.uniqueid + "_container .tgapfill_reply_" + self.game.pointer + " .tgapfill_feedback[data-idx='" + self.game.pointer + "']").addClass("fa fa-times");
                self.items[self.game.pointer].answered = true;
                self.items[self.game.pointer].correct = false;
                self.items[self.game.pointer].typed = false;
            } else {
                //it was wrong but they can retry
                var thereply = $("#" + self.itemdata.uniqueid + "_container .tgapfill_reply_" + self.game.pointer);
                anim.do_animate(thereply, 'shakeX animate__faster').then(
                    function() {
                        $("#" + self.itemdata.uniqueid + "_container .tgapfill_ctrl-btn").prop("disabled", false);
                    }
                );
                return;
            }

            self.stopTimer(self.items[self.game.pointer].timer);

            if (self.game.pointer < self.items.length - 1) {
                setTimeout(function() {
                    $(".tgapfill_reply_" + self.game.pointer).hide();
                    self.game.pointer++;
                    self.nextPrompt();
                }, 2000);
            } else {
                self.end();
            }
        },

        getComparison: function(passage, transcript, callback) {
            var self = this;

            $(".tgapfill_ctrl-btn").prop("disabled", true);

            var correctanswer = true;

            passage.forEach(function(data, index) {
                var char = '';

                if (data.type === 'input') {
                    if (correctanswer === true) {
                        char = $("#" + self.itemdata.uniqueid + "_container .tgapfill_reply_" + self.game.pointer + ' input.single-character[data-index="' + index + '"]').val();
                        if (char == '') {
                            correctanswer = false;
                        } else if (char != data.character) {
                            correctanswer = false;
                        }
                    }
                }
            });

            callback(correctanswer);
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

            $("#" + self.itemdata.uniqueid + "_container .tgapfill_ctrl-btn").prop("disabled", true);

            self.items.forEach(function(item) {
                item.spoken = "";
                item.answered = false;
                item.correct = false;
            });

            self.game.pointer = 0;

            $("#" + self.itemdata.uniqueid + "_container .question").show();
            $("#" + self.itemdata.uniqueid + "_container .tgapfill_start_btn").hide();
            $("#" + self.itemdata.uniqueid + "_container .tgapfill_mainmenu").hide();
            $("#" + self.itemdata.uniqueid + "_container .tgapfill_controls").show();

            self.nextPrompt();

        },

        nextPrompt: function() {

            var self = this;

            $(".tgapfill_ctrl-btn").prop("disabled", false);

            self.updateProgressDots();

            self.nextReply();
        },

        updateProgressDots: function() {
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
            $("#" + self.itemdata.uniqueid + "_container .tgapfill_title").html(progress);
        },

        nextReply: function() {
            var self = this;
            var code = "<div class='tgapfill_reply tgapfill_reply_" + self.game.pointer + " text-center' style='display:none;'>";

            code += "<div class='form-container'>";
            self.items[self.game.pointer].parsedstring.forEach(function(data, index) {
                if (data.type === 'input') {
                    code += "<input class='single-character' autocomplete='off' type='text' name='filltext" + index + "' maxlength='1' data-index='" + index + "'>";
                } else if (data.type === 'mtext') {
                    code += "<input class='single-character-mtext' type='text' name='readonly" + index + "' maxlength='1' value='" + data.character + "' readonly>";
                } else {
                    code += data.character;
                }
            });
            code += " <i data-idx='" + self.game.pointer + "' class='tgapfill_feedback'></i></div>";

            code += "<div class='definition-container'>";
            code += "<div class='definition'>" + self.items[self.game.pointer].definition + "</div>";
            code += "</div>";

            code += "</div>";
            $("#" + self.itemdata.uniqueid + "_container .question").append(code);

            var newreply = $(".tgapfill_reply_" + self.game.pointer);

            anim.do_animate(newreply, 'zoomIn animate__faster', 'in').then(
                function() {
                }
            );

            $("#" + self.itemdata.uniqueid + "_container .tgapfill_ctrl-btn").prop("disabled", false);

            var inputElements = [...document.querySelectorAll("#" + self.itemdata.uniqueid + "_container .tgapfill_reply_" + self.game.pointer + ' input.single-character')];
            self.formReady(inputElements);

            $("#" + self.itemdata.uniqueid + "_container .tgapfill_reply_" + self.game.pointer + ' input.single-character:first').focus();

            if (self.itemdata.timelimit > 0) {
                $("#" + self.itemdata.uniqueid + "_container .progress-container").show();
                $("#" + self.itemdata.uniqueid + "_container .progress-container i").show();
                var progresbar = $("#" + self.itemdata.uniqueid + "_container .progress-container #progresstimer").progressTimer({
                    height: '5px',
                    timeLimit: self.itemdata.timelimit,
                    onFinish: function() {
                        log.debug('timer finished');
                        var btn = $("#" + self.itemdata.uniqueid + "_container .tgapfill_check_btn");
                        log.debug(btn);

                        $("#" + self.itemdata.uniqueid + "_container .tgapfill_check_btn").trigger('click');
                    }
                });

                progresbar.each(function() {
                    self.items[self.game.pointer].timer.push($(this).attr('timer'));
                });
            }
        },

        stopTimer: function(timers) {
            if (timers.length) {
                timers.forEach(function(timer) {
                    clearInterval(timer);
                });
            }
        },

        formReady: function(inputElements) {
            inputElements.forEach(function(ele, index) {
                ele.addEventListener("keydown", function(e) {
                    // If the keycode is backspace & the current field is empty
                    // focus the input before the current. Then the event happens
                    // which will clear the "before" input box.
                    if (e.keyCode === 8 && e.target.value === "") {
                     inputElements[Math.max(0, index - 1)].focus();
                    }
                });
                ele.addEventListener("input", function(e) {
                    // Take the first character of the input
                    // this actually breaks if you input an emoji like 👨‍👩‍👧‍👦....
                    // but I'm willing to overlook insane security code practices.
                    const [first, ...rest] = e.target.value;
                    e.target.value = first ?? ""; // First will be undefined when backspace was entered, so set the input to ""
                    const lastInputBox = index === inputElements.length - 1;
                    const didInsertContent = first !== undefined;
                    if (didInsertContent && !lastInputBox) {
                        // Continue to input the rest of the string
                        inputElements[index + 1].focus();
                        inputElements[index + 1].value = rest.join("");
                        inputElements[index + 1].dispatchEvent(new Event("input"));
                    }
                });
            });
        },
    };
});