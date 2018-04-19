define(['jquery','core/log'], function($,log) {
    "use strict"; // jshint ;_;

    log.debug('Readaloud helper: initialising');

    return {

        classdef: {
            hiddenplayer: 'mod_readaloud_hidden_player',
            hiddenplayerbutton: 'mod_readaloud_hidden_player_button',
            activebutton: 'mod_readaloud_hidden_player_button_active',
            activebuttonpaused: 'mod_readaloud_hidden_player_button_paused',
            activebuttonplaying: 'mod_readaloud_hidden_player_button_playing'
        },

        init: function (opts) {
            this.classdef.hiddenplayer = opts['hiddenplayerclass'];
            this.classdef.hiddenplayerbutton = opts['hiddenplayerbuttonclass'];
            $('.' + this.classdef.hiddenplayerbutton).click(this.dohiddenplayerbuttonclick);
        },


        dohiddenplayerbuttonclick: function () {
            var m = this;//M.mod_readaloud.gradinghelper;
            var audioplayer = $('.' + m.classdef.hiddenplayer);
            var audiosrc = $(this).attr('data-audiosource');
            if (audiosrc == audioplayer.attr('src') && !(audioplayer.prop('paused'))) {
                m.dohiddenstop();
            } else {
                m.dohiddenplay(audiosrc);
            }
        },
        dohiddenplay: function (audiosrc) {
            var m = this;//M.mod_readaloud.gradinghelper;
            var audioplayer = $('.' + m.classdef.hiddenplayer);
            audioplayer.attr('src', audiosrc);
            audioplayer[0].pause();
            audioplayer[0].load();
            audioplayer[0].play();
            m.dobuttonicons();
        },
        dohiddenstop: function () {
            var m = this;// M.mod_readaloud.gradinghelper;
            var audioplayer = $('.' + m.classdef.hiddenplayer);
            audioplayer[0].pause();
            m.dobuttonicons();
        },
        dobuttonicons: function (theaudiosrc) {
            var m = this;//M.mod_readaloud.gradinghelper;
            var audioplayer = $('.' + m.classdef.hiddenplayer);
            if (!theaudiosrc) {
                theaudiosrc = audioplayer.attr('src');
            }
            $('.' + m.classdef.hiddenplayerbutton).each(function (index) {
                var audiosrc = $(this).attr('data-audiosource');
                if (audiosrc == theaudiosrc) {
                    $(this).addClass(m.classdef.activebuttonbutton);
                    if (audioplayer.prop('paused')) {
                        $(this).removeClass(m.classdef.activebuttonplaying);
                        $(this).addClass(m.classdef.activebuttonpaused);
                        //for now we make it look like no button is selected
                        //later we can implement better controls
                        $(this).removeClass(m.classdef.activebuttonbutton);
                    } else {
                        $(this).removeClass(m.classdef.activebuttonpaused);
                        $(this).addClass(m.classdef.activebuttonplaying);
                    }
                } else {
                    $(this).removeClass(m.classdef.activebuttonbutton);
                    $(this).removeClass(m.classdef.activebuttonplaying);
                    $(this).removeClass(m.classdef.activebuttonpaused);
                }
            })

        }
    }//end of return object

});