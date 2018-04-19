define(['jquery','core/log'], function($,log) {
    "use strict"; // jshint ;_;

    log.debug('Readaloud Gradenow helper: initialising');

    return{
        classdef: {

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


        init: function(opts){
            //stash important info
            this.options.activityid = opts['activityid'];
            this.options.attemptid = opts['attemptid'];
            this.options.sesskey = opts['sesskey'];
            this.options.enabletts = opts['enabletts'];
            this.options.ttslanguage = opts['ttslanguage'];
            this.options.targetwpm = opts['targetwpm'];
            this.options.allowearlyexit = opts['allowearlyexit'];
            this.options.timelimit = opts['timelimit'];
            this.options.totalwordcount = $('.' + this.classdef.wordclass).length ;

            if(opts['sessiontime']>0){
                this.options.errorwords=JSON.parse(opts['sessionerrors']);
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
            $('#' + this.classdef.spaceclass + '_' + this.options.endwordnumber).addClass(this.classdef.endspaceclass);

            //set up event handlers
            //in review mode, do nuffink though ... thats for the student
            if(opts['reviewmode']){
                if(this.enabletts && this.options.ttslanguage != 'none'){
                    $('.' + this.classdef.wordclass).click(this.playword);
                }
            }else{
                $('.' + this.classdef.wordclass).click(this.processword);
                $('.' + this.classdef.spaceclass).click(this.processspace);
            }
            //initialise our audio duration. We need this to calc. wpm
            //but if allowearlyexit is false, actually we can skip waiting for audio.
            //After audio loaded(if nec.) we call processscores to init score boxe
            //TODO: really should get audio duration at recording time.
            var audioplayer = $('#' + this.classdef.audioplayerclass);
            if(audioplayer.prop('readyState')<1 && this.options.allowearlyexit){
                audioplayer.on('loadedmetadata',this.processloadedaudio);
            }else{
                this.processloadedaudio();
            }
        },

        playword: function(){
            var m = this;// M.mod_readaloud.gradenowhelper;
            var audioplayer = $('.' + m.classdef.wordplayerclass);
            audioplayer.attr('src',M.cfg.wwwroot + '/mod/readaloud/tts.php?txt=' + encodeURIComponent($(this).text())
                + '&lang=' + m.options.ttslanguage + '&n=' + m.options.activityid);
            audioplayer[0].pause();
            audioplayer[0].load();
            audioplayer[0].play();
        },
        redrawgradestate: function(){
            var m = this;//M.mod_readaloud.gradenowhelper;
            this.processunread();
            $.each(m.options.errorwords,function(index){
                    $('#' + m.classdef.wordclass + '_' + m.options.wordnumber).addClass(m.classdef.badwordclass);
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
                $(this).removeClass(m.classdef.badwordclass);
            }else{
                m.adderrorword(wordnumber,theword);
                $(this).addClass(m.classdef.badwordclass);
            }
            m.processscores();
        },
        processspace: function() {
            //this event is entered by  click on space
            //it relies on attr data-wordnumber being set correctly
            var m = this;// M.mod_readaloud.gradenowhelper;
            var wordnumber = $(this).attr('data-wordnumber');
            var thespace = $('#' + m.classdef.spaceclass + '_' + wordnumber);

            if(wordnumber == m.options.endwordnumber){
                m.options.endwordnumber = m.options.totalwordcount;
                thespace.removeClass(m.classdef.endspaceclass);
                $('#' + m.classdef.spaceclass + '_' + m.options.totalwordcount).addClass(m.classdef.endspaceclass);
            }else{
                $('#' + m.classdef.spaceclass + '_' + m.options.endwordnumber).removeClass(m.classdef.endspaceclass);
                m.options.endwordnumber = wordnumber;
                thespace.addClass(m.classdef.endspaceclass);
            }
            m.processunread();
            m.processscores();
        },
        processunread: function(){
            var m = this;// M.mod_readaloud.gradenowhelper;
            $('.' + m.classdef.wordclass).each(function(index){
                var wordnumber = $(this).attr('data-wordnumber');
                if(Number(wordnumber)>Number(m.options.endwordnumber)){
                    $(this).addClass(m.classdef.unreadwordclass);
                    //this will clear badwords after the endmarker
                    if(m.options.enforcemarker && wordnumber in m.options.errorwords){
                        delete m.options.errorwords[wordnumber];
                        $(this).removeClass(m.classdef.badwordclass);
                    }
                }else{
                    $(this).removeClass(m.classdef.unreadwordclass);
                }
            })
        },
        processscores: function(){
            var m = this;//M.mod_readaloud.gradenowhelper;
            var wpmscorebox = $('#' + m.classdef.wpmscoreid);
            var accuracyscorebox = $('#' + m.classdef.accuracyscoreid);
            var sessionscorebox = $('#' + m.classdef.sessionscoreid);
            var errorscorebox = $('#' + m.classdef.errorscoreid);
            var errorscore = Object.keys(m.options.errorwords).length;
            errorscorebox.text(errorscore);

            //wpm score
            var wpmscore = Math.round((m.options.endwordnumber - errorscore) * 60 / m.options.totalseconds);
            m.options.wpm = wpmscore;
            wpmscorebox.text(wpmscore);

            //accuracy score
            var accuracyscore = Math.round((m.options.endwordnumber - errorscore)/m.options.endwordnumber * 100);
            m.options.accuracy = accuracyscore;
            accuracyscorebox.text(accuracyscore);

            //sessionscore
            var usewpmscore = wpmscore;
            if(usewpmscore > m.options.targetwpm){
                usewpmscore = m.options.targetwpm;
            }
            var sessionscore = Math.round(usewpmscore/m.options.targetwpm * 100);
            sessionscorebox.text(sessionscore);

            //update form field
            $("#" + m.classdef.formelementwpmscore).val(wpmscore);
            $("#" + m.classdef.formelementsessionscore).val(sessionscore);
            $("#" + m.classdef.formelementaccuracy).val(accuracyscore);
            $("#" + m.classdef.formelementendword).val(m.options.endwordnumber);
            $("#" + m.classdef.formelementerrors).val(JSON.stringify(m.options.errorwords));

        },
        processloadedaudio: function(){
            var m = this;//M.mod_readaloud.gradenowhelper;
            if(m.options.allowearlyexit){
                m.options.totalseconds = Math.round($('#' + m.classdef.audioplayerclass).prop('duration'));
            }else{
                m.options.totalseconds = m.options.timelimit;
            }
            //update form field
            $("#" + m.classdef.formelementtime).val(m.options.totalseconds);
            m.processscores();
        }
    }
});