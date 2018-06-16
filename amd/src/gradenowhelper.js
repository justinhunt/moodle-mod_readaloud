define(['jquery','core/log'], function($,log) {
    "use strict"; // jshint ;_;

    log.debug('Readaloud Gradenow helper: initialising');

    return{
        //controls
        controls: {},

        //class definitions
        cd: {

            audioplayerclass: 'mod_readaloud_grading_player',
            wordplayerclass: 'mod_readaloud_hidden_player',
            wordclass: 'mod_readaloud_grading_passageword',
            spaceclass: 'mod_readaloud_grading_passagespace',
            badwordclass: 'mod_readaloud_grading_badword',
            endspaceclass: 'mod_readaloud_grading_endspace',
            unreadwordclass:  'mod_readaloud_grading_unreadword',
            wpmscoreid: 'mod_readaloud_grading_wpm_score',
            accuracyscoreid: 'mod_readaloud_grading_accuracy_score',
            sessionscoreid: 'mod_readaloud_grading_session_score',
            errorscoreid: 'mod_readaloud_grading_error_score',
            formelementwpmscore: 'mod_readaloud_grading_form_wpm',
            formelementaccuracy: 'mod_readaloud_grading_form_accuracy',
            formelementsessionscore: 'mod_readaloud_grading_form_sessionscore',
            formelementendword: 'mod_readaloud_grading_form_sessionendword',
            formelementtime: 'mod_readaloud_grading_form_sessiontime',
            formelementerrors: 'mod_readaloud_grading_form_sessionerrors'
        },

        options: {
            enabletts: false,
            targetwpm: 100,
            ttslanguage: 'en',
            totalseconds: 60,
            allowearlyexit: false,
            timelimit: 60,
            enforcemarker: true,
            totalwordcount: 0,
            wpm: 0,
            accuracy: 0,
            sessionscore: 0,
            endwordnumber: 0,
            errorwords: {},
            activityid: null,
            attemptid: null,
            sesskey: null,
            passagecontainer: 'mod_readaloud_grading_passagecont'
        },


        init: function(config){

            //pick up opts from html
            var theid='#' + config['id'];
            var configcontrol = $(theid).get(0);
            if(configcontrol){
                var opts = JSON.parse(configcontrol.value);
                $(theid).remove();
            }else{
                //if there is no config we might as well give up
                log.debug('Gradenow helper js: No config found on page. Giving up.');
                return;
            }

            //register the controls
            this.register_controls();

            //stash important info
            this.options.activityid = opts['activityid'];
            this.options.attemptid = opts['attemptid'];
            this.options.sesskey = opts['sesskey'];
            this.options.enabletts = opts['enabletts'];
            this.options.ttslanguage = opts['ttslanguage'];
            this.options.targetwpm = opts['targetwpm'];
            this.options.allowearlyexit = opts['allowearlyexit'];
            this.options.timelimit = opts['timelimit'];
            this.options.reviewmode = opts['reviewmode'];
            this.options.totalwordcount = $('.' + this.cd.wordclass).length ;

            if(opts['sessiontime']>0){
                if(opts['sessionerrors']!=='') {
                    this.options.errorwords = JSON.parse(opts['sessionerrors']);
                }else{
                    this.options.errorwords = {};
                }
                this.options.totalseconds=opts['sessiontime'];
                this.options.endwordnumber=opts['sessionendword'];
                this.options.sessionscore=opts['sessionscore'];
                this.options.accuracy=opts['accuracy'];
                this.options.wpm=opts['wpm'];
                //if this has been graded, draw the gradestate
                this.redrawgradestate();
            }else{
                //set up our end passage marker
                this.options.endwordnumber = this.options.totalwordcount;
            }



            //add the endword marker
            this.controls.endwordmarker.addClass(this.cd.endspaceclass);

            //register events
           this.register_events();

            //initialise our audio duration. We need this to calc. wpm
            //but if allowearlyexit is false, actually we can skip waiting for audio.
            //After audio loaded(if nec.) we call processscores to init score boxe
            //TODO: really should get audio duration at recording time.
            var m = this;
            var processloadedaudio= function(){
                if(m.options.allowearlyexit){
                    m.options.totalseconds = Math.round($('#' + m.cd.audioplayerclass).prop('duration'));
                }else{
                    m.options.totalseconds = m.options.timelimit;
                }
                //update form field
                m.controls.formelementtime.val(m.options.totalseconds);
                m.processscores();
            }


            var audioplayer = $('#' + this.cd.audioplayerclass);
            if(audioplayer.prop('readyState')<1 && this.options.allowearlyexit){
                audioplayer.on('loadedmetadata',processloadedaudio);
            }else{
                processloadedaudio();
            }
        },

        register_controls: function(){

            this.controls.wordplayer = $('#' + this.cd.wordplayerclass);
            this.controls.audioplayer = $('#' + this.cd.audioplayerclass);
            this.controls.eachword = $('.' + this.cd.wordclass);
            this.controls.eachspace = $('.' + this.cd.spaceclass);
            this.controls.endwordmarker =  $('#' + this.cd.spaceclass + '_' + this.options.endwordnumber);

            this.controls.wpmscorebox = $('#' + this.cd.wpmscoreid);
            this.controls.accuracyscorebox = $('#' + this.cd.accuracyscoreid);
            this.controls.sessionscorebox = $('#' + this.cd.sessionscoreid);
            this.controls.errorscorebox = $('#' + this.cd.errorscoreid);

            this.controls.formelementwpmscore = $("#" + this.cd.formelementwpmscore);
            this.controls.formelementsessionscore = $("#" + this.cd.formelementsessionscore);
            this.controls.formelementaccuracy = $("#" + this.cd.formelementaccuracy);
            this.controls.formelementendword = $("#" + this.cd.formelementendword);
            this.controls.formelementerrors = $("#" + this.cd.formelementerrors);
            this.controls.formelementtime = $("#" + this.cd.formelementtime);

        },

        register_events: function(){
            var that = this;
            //set up event handlers
            //in review mode, do nuffink though ... thats for the student
            if(this.options.reviewmode){
                if(this.enabletts && this.options.ttslanguage != 'none'){
                    this.controls.eachword.click(this.playword);
                }
            }else{

                //process word clicks
                this.controls.eachword.click(
                    function() {
                        var wordnumber = $(this).attr('data-wordnumber');
                        var theword = $(this).text();
                        //this will disallow badwords after the endmarker
                        if(that.options.enforcemarker && Number(wordnumber)>Number(that.options.endwordnumber)){
                            return;
                        }

                        if(wordnumber in that.options.errorwords){
                            delete that.options.errorwords[wordnumber];
                            $(this).removeClass(that.cd.badwordclass);
                        }else{
                            that.adderrorword(wordnumber,theword);
                            $(this).addClass(that.cd.badwordclass);
                        }
                        that.processscores();
                    }
                ); //end of each word click

                //process space clicks
                this.controls.eachspace.click(
                    function() {
                        //this event is entered by  click on space
                        //it relies on attr data-wordnumber being set correctly
                        var wordnumber = $(this).attr('data-wordnumber');
                        var thespace = $('#' + that.cd.spaceclass + '_' + wordnumber);

                        if(wordnumber == that.options.endwordnumber){
                            that.options.endwordnumber = that.options.totalwordcount;
                            thespace.removeClass(that.cd.endspaceclass);
                            $('#' + that.cd.spaceclass + '_' + that.options.totalwordcount).addClass(that.cd.endspaceclass);
                        }else{
                            $('#' + that.cd.spaceclass + '_' + that.options.endwordnumber).removeClass(that.cd.endspaceclass);
                            that.options.endwordnumber = wordnumber;
                            thespace.addClass(that.cd.endspaceclass);
                        }
                        that.processunread();
                        that.processscores();
                    }
                );//end of each space click
            }//end of if/else reviewmode

        },

        playword: function(){
            var m = this;//M.mod_readaloud.gradenowhelper;
            m.controls.wordplayer.attr('src',M.cfg.wwwroot + '/mod/readaloud/tts.php?txt=' + encodeURIComponent($(this).text())
                + '&lang=' + m.options.ttslanguage + '&n=' + m.options.activityid);
            m.controls.wordplayer[0].pause();
            m.controls.wordplayer[0].load();
            m.controls.wordplayer[0].play();
        },
        redrawgradestate: function(){
            var m = this;
            this.processunread();
            $.each(m.options.errorwords,function(index){
                    $('#' + m.cd.wordclass + '_' + m.options.errorwords[index].wordnumber).addClass(m.cd.badwordclass);
                }
            );

        },
        adderrorword: function(wordnumber,word) {
            this.options.errorwords[wordnumber] = {word: word, wordnumber: wordnumber};
            //console.log(this.errorwords);
            return;
        },
        processword: function() {
            var m = this;// M.mod_readaloud.gradenowhelper;
            var wordnumber = $(this).attr('data-wordnumber');
            var theword = $(this).text();
            //this will disallow badwords after the endmarker
            if(m.options.enforcemarker && Number(wordnumber)>Number(m.options.endwordnumber)){
                return;
            }

            if(wordnumber in m.options.errorwords){
                delete m.options.errorwords[wordnumber];
                $(this).removeClass(m.cd.badwordclass);
            }else{
                m.adderrorword(wordnumber,theword);
                $(this).addClass(m.cd.badwordclass);
            }
            m.processscores();
        },
        processspace: function() {
            //this event is entered by  click on space
            //it relies on attr data-wordnumber being set correctly
            var m = this;// M.mod_readaloud.gradenowhelper;
            var wordnumber = $(this).attr('data-wordnumber');
            var thespace = $('#' + m.cd.spaceclass + '_' + wordnumber);

            if(wordnumber == m.options.endwordnumber){
                m.options.endwordnumber = m.options.totalwordcount;
                thespace.removeClass(m.cd.endspaceclass);
                $('#' + m.cd.spaceclass + '_' + m.options.totalwordcount).addClass(m.cd.endspaceclass);
            }else{
                $('#' + m.cd.spaceclass + '_' + m.options.endwordnumber).removeClass(m.cd.endspaceclass);
                m.options.endwordnumber = wordnumber;
                thespace.addClass(m.cd.endspaceclass);
            }
            m.processunread();
            m.processscores();
        },
        processunread: function(){
            var m = this;// M.mod_readaloud.gradenowhelper;
            m.controls.eachword.each(function(index){
                var wordnumber = $(this).attr('data-wordnumber');
                if(Number(wordnumber)>Number(m.options.endwordnumber)){
                    $(this).addClass(m.cd.unreadwordclass);
                    //this will clear badwords after the endmarker
                    if(m.options.enforcemarker && wordnumber in m.options.errorwords){
                        delete m.options.errorwords[wordnumber];
                        $(this).removeClass(m.cd.badwordclass);
                    }
                }else{
                    $(this).removeClass(m.cd.unreadwordclass);
                }
            });
        },
        processscores: function(){
            var m = this;//M.mod_readaloud.gradenowhelper;
            var errorscore = Object.keys(m.options.errorwords).length;
            m.controls.errorscorebox.text(errorscore);

            //wpm score
            var wpmscore = Math.round((m.options.endwordnumber - errorscore) * 60 / m.options.totalseconds);
            m.options.wpm = wpmscore;
            m.controls.wpmscorebox.text(wpmscore);

            //accuracy score
            var accuracyscore = Math.round((m.options.endwordnumber - errorscore)/m.options.endwordnumber * 100);
            m.options.accuracy = accuracyscore;
            m.controls.accuracyscorebox.text(accuracyscore);

            //sessionscore
            var usewpmscore = wpmscore;
            if(usewpmscore > m.options.targetwpm){
                usewpmscore = m.options.targetwpm;
            }
            var sessionscore = Math.round(usewpmscore/m.options.targetwpm * 100);
            m.controls.sessionscorebox.text(sessionscore);

            //update form field
            m.controls.formelementwpmscore.val(wpmscore);
            m.controls.formelementsessionscore.val(sessionscore);
            m.controls.formelementaccuracy.val(accuracyscore);
            m.controls.formelementendword.val(m.options.endwordnumber);
            m.controls.formelementerrors.val(JSON.stringify(m.options.errorwords));

        }

    };
});