define(['jquery', 'core/log', 'core/ajax', 'mod_readaloud/definitions',  'mod_readaloud/cloudpoodllloader'], function($, log, ajax, def, cloudpoodll) {
    "use strict"; // jshint ;_;

    log.debug('Readaloud listen and repeat: initialising');

    return {

        mak: null,
        container: null,
        audiourl: false,

        init: function(props) {
            var self = this;
            cloudpoodll.init('readaloud_pushrecorder', function(message) {

                switch (message.type) {
                    case 'recording':
                        break;

                    case 'speech':
                        console.log(message);
                        self.getComparison(
                            self.items[self.game.pointer].target,
                            message.capturedspeech,
                            function(comparison) {
                                self.gotComparison(comparison, message);
                            }
                        );
                        break;

                }

            });
            self.mak = props.modelaudiokaraoke;
            self.audiourl = self.mak.fetch_audio_url();
            self.container=$('#' + def.landrcontainer);
            self.register_events();
            self.register_mak();
            self.appReady();
        },

        register_mak: function(){
            var self =this;
            self.mak.on_reach_audio_break= function(sentence, oldbreak,newbreak){
                //empty strings are none of our concern
                if(sentence.trim()===''){return;}


                log.debug(sentence);
                log.debug(oldbreak);
                log.debug(newbreak);

                //pause audio while we do our thing
                self.mak.pause_audio();
                self.container.modal('show');

            }

        },

        register_events: function() {

            var self = this;

            self.container.on('hidden.bs.modal', function (e) {
                self.mak.play_audio();
            });


            $("#mod_readaloud_landrplayer_container .landr_start_btn").on("click", function() {
                self.start();
            });

            $("#mod_readaloud_landrplayer_container .landr_listen_btn").on("click", function() {
                self.items[self.game.pointer].audio.load();
                self.items[self.game.pointer].audio.play();
            });

            $("#mod_readaloud_landrplayer_container .landr_skip_btn").on("click", function() {
                $("#mod_readaloud_landrplayer_container .landr_ctrl-btn").prop("disabled", true);
                $("#mod_readaloud_landrplayer_container .landr_speech.landr_teacher_left").text("PUT THE SENTENCE TEXT HERE PAUL");
                setTimeout(function() {
                    if (self.game.pointer < self.items.length - 1) {
                        self.items[self.game.pointer].answered = true;
                        self.items[self.game.pointer].correct = false;
                        self.game.pointer++;
                        self.nextPrompt();
                    } else {
                        self.end();
                    }
                }, 3000);
            });
        },
        spliton: new RegExp('([,.!?:;" ])', 'g'),


        appReady: function() {
            var self = this;
            $("#mod_readaloud_landrplayer_container .landr_not_loaded").hide();
            $("#mod_readaloud_landrplayer_container .landr_loaded").show();
            $("#mod_readaloud_landrplayer_container .landr_start_btn").prop("disabled", false);
        },
        gotComparison: function(comparison, typed) {

            console.log(comparison, typed);

            var self = this;

            $("#mod_readaloud_landrplayer_container .landr_targetWord").addClass("landr_correct").removeClass("landr_incorrect");

            if (!Object.keys(comparison).length) {
                $("#mod_readaloud_landrplayer_container .landr_speech.landr_teacher_left").text(self.items[self.game.pointer].target + "");

                self.items[self.game.pointer].answered = true;
                self.items[self.game.pointer].correct = true;
                self.items[self.game.pointer].typed = typed;

                $("#mod_readaloud_landrplayer_container .landr_ctrl-btn").prop("disabled", true);
                if (self.game.pointer < self.items.length - 1) {
                    setTimeout(function() {
                        self.game.pointer++;
                        self.nextPrompt();
                    }, 3000);
                } else {
                    setTimeout(function() {
                        self.end();
                    }, 3000);
                }

            } else {

                Object.keys(comparison).forEach(function(idx) {
                    $("#mod_readaloud_landrplayer_container .landr_targetWord[data-idx='" + idx + "']").removeClass("landr_correct").addClass("landr_incorrect");
                });

                $("#mod_readaloud_landrplayer_container .landr_reply_" + self.game.pointer).effect("shake", function() {

                    $("#mod_readaloud_landrplayer_container .landr_ctrl-btn").prop("disabled", false);

                });

            }

            $("#mod_readaloud_landrplayer_container .landr_targetWord.landr_correct").each(function() {
                var realidx = $(this).data("realidx");
                var landr_targetWord = self.items[self.game.pointer].landr_targetWords[realidx];
                $(this).val(landr_targetWord).prop("disabled", true);
            });

        },
        getWords: function(thetext) {
            var self = this;
            var checkcase = false;
            if (checkcase == 'false') {
                thetext = thetext.toLowerCase();
            }
            var chunks = thetext.split(self.spliton).filter(function(e) {
                return e !== "";
            });
            var words = [];
            for (var i = 0; i < chunks.length; i++) {
                if (!chunks[i].match(self.spliton)) {
                    words.push(chunks[i]);
                }
            }
            return words;
        },
        getSimpleComparison(passage, transcript, callback) {
            var self = this;
            var pwords = self.getWords(passage);
            var twords = self.getWords(transcript);
            var ret = {};
            for (var pi = 0; pi < pwords.length && pi < twords.length; pi++) {
                if (pwords[pi] != twords[pi]) {
                    ret[pi + 1] = {
                        "word": pwords[pi],
                        "number": pi + 1
                    };
                }
            }
            callback(ret);
        },
        getComparison: function(passage, transcript, callback) {
            var self = this;
            /*
            var comparison = "simple";
            if (comparison == 'simple') {
              self.getSimpleComparison(passage, transcript, callback);
              return;
            }
            */

            console.log(passage,transcript);

            $(".landr_ctrl-btn").prop("disabled", true);

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

        },
        end: function() {
            var self = this;

            var numCorrect = self.items.filter(function(e) {
                return e.correct;
            }).length;

            var totalNum = self.items.length;

            $("#mod_readaloud_landrplayer_container .landr_results").html("TOTAL<br/>" + numCorrect + "/" + totalNum).show();

            setTimeout(function() {
                $("#mod_readaloud_landrplayer_container .landr_results").fadeOut(function() {
                    $("#mod_readaloud_landrplayer_container .landr_start_btn").show();
                });
            }, 2000);

            $("#mod_readaloud_landrplayer_container .landr_game").hide();
            $("#mod_readaloud_landrplayer_container .landr_mainmenu").show();
            $("#mod_readaloud_landrplayer_container .landr_controls").hide();
            $("#mod_readaloud_landrplayer_container .landr_title").html("Listen and Repeat");
            $("#mod_readaloud_landrplayer_container .landr_speakbtncontainer").hide();

        },
        start: function() {
            var self = this;

            $("#mod_readaloud_landrplayer_container .landr_ctrl-btn").prop("disabled", true);
            $("#mod_readaloud_landrplayer_container .landr_speakbtncontainer").show();

            self.items.forEach(function(item) {
                item.spoken = "";
                item.answered = false;
                item.correct = false;
            });

            self.game.pointer = 0;

            $("#mod_readaloud_landrplayer_container .landr_game").show();
            $("#mod_readaloud_landrplayer_container .landr_start_btn").hide();
            $("#mod_readaloud_landrplayer_container .landr_mainmenu").hide();
            $("#mod_readaloud_landrplayer_container .landr_controls").show();

            self.nextPrompt();

        },
        nextPrompt: function() {

            var showText = true;
            var self = this;

            var target = self.items[self.game.pointer].target;
            var code = "<div class='landr_prompt landr_prompt_" + self.game.pointer + "' style='display:none;'>";

            code += "<i class='fa fa-graduation-cap landr_speech-icon-left'></i>";
            code += "<div style='margin-left:90px;' class='landr_speech landr_teacher_left'>";
            if(!showText){
                code += target.replace(/[^a-zA-Z0-9 ]/g, '').replace(/[a-zA-Z0-9]/g, '•');
            } else{
                code += target;
            }
            code += "</div>";
            code += "</div>";

            $("#mod_readaloud_landrplayer_container .landr_game").html(code);
            $(".landr_ctrl-btn").prop("disabled", false);

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

            $("#mod_readaloud_landrplayer_container .landr_title").html(progress);
            $(".landr_prompt_" + self.game.pointer).toggle("slide", {
                direction: 'left'
            });

            self.nextReply();

        },
        nextReply: function() {
            var self = this;
            var target = self.items[self.game.pointer].target;
            var code = "<div class='landr_reply landr_reply_" + self.game.pointer + "' style='display:none;'>";
            code += "<i class='fa fa-user landr_speech-icon-right'></i>";
            var landr_targetWordsCode = "";
            var idx = 1;
            self.items[self.game.pointer].landr_targetWords.forEach(function(word, realidx) {
                if (!word.match(self.spliton)) {
                    landr_targetWordsCode += "<input disabled type='text' maxlength='" + word.length + "' size='" + (word.length + 1) + "' class='landr_targetWord' data-realidx='" + realidx + "' data-idx='" + idx + "'>";
                    idx++;

                } else {
                    landr_targetWordsCode += word;
                }
            });
            code += "<div style='margin-right:90px;' class='landr_speech landr_right'>" + landr_targetWordsCode + "</div>";
            code += "</div>";
            $("#mod_readaloud_landrplayer_container .landr_game").append(code);
            $(".landr_reply_" + self.game.pointer).toggle("slide", {
                direction: 'right'
            });
            $("#mod_readaloud_landrplayer_container .landr_ctrl-btn").prop("disabled", false);
            if(!self.quizhelper.mobile_user()){
                setTimeout(function(){
                    $("#mod_readaloud_landrplayer_container .landr_listen_btn").trigger('click');
                },1000);
            }
        }

    };
});