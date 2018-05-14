define(['jquery','core/log'], function($,log) {
    "use strict"; // jshint ;_;

    log.debug('Readaloud grading helper: initialising');

    return {

        controls: {},
        hiddenplayer: 'mod_readaloud_hidden_player',
        hiddenplayerbutton: 'mod_readaloud_hidden_player_button',
        activebutton: 'mod_readaloud_hidden_player_button_active',
        activebuttonpaused: 'mod_readaloud_hidden_player_button_paused',
        activebuttonplaying: 'mod_readaloud_hidden_player_button_playing',

        init: function (opts) {
            this.hiddenplayer = opts['hiddenplayerclass'];
            this.hiddenplayerbutton = opts['hiddenplayerbuttonclass'];
            this.register_controls();
            this.register_events();
        },

        register_controls: function(){
            this.controls.hiddenplayer = $('.' + this.hiddenplayer);
            this.controls.hiddenplayerbutton = $('.' + this.hiddenplayerbutton);
        },

        register_events: function(){
            var that = this;
            var audioplayer = this.controls.hiddenplayer;
            //handle the button click
            this.controls.hiddenplayerbutton.click(function(e){
                var audiosrc = $(this).attr('data-audiosource');
                if (audiosrc == audioplayer.attr('src') && !(audioplayer.prop('paused'))) {
                    that.dohiddenstop();
                } else {
                    that.dohiddenplay(audiosrc);
                }
            });

        },


        dohiddenplay: function (audiosrc) {
            var m = this;//M.mod_readaloud.gradinghelper;
            var audioplayer = m.controls.hiddenplayer;
            audioplayer.attr('src', audiosrc);
            audioplayer[0].pause();
            audioplayer[0].load();
            var pp = audioplayer[0].play();
            if (pp !== undefined) {
                pp.then(function() {
                    // Yay we are playing
                }).catch(function(error) {
                    // somethings up ... but we can ignore it
                });
            }
            m.dobuttonicons();
        },
        dohiddenstop: function () {
            var m = this;// M.mod_readaloud.gradinghelper;
            var audioplayer =  m.controls.hiddenplayer;
            audioplayer[0].pause();
            m.dobuttonicons();
        },

        dobuttonicons: function (theaudiosrc) {
            var m = this;//M.mod_readaloud.gradinghelper;
            var audioplayer = m.controls.hiddenplayer;
            if (!theaudiosrc) {
                theaudiosrc = audioplayer.attr('src');
            }
            m.controls.hiddenplayerbutton.each(function (index) {
                var audiosrc = $(this).attr('data-audiosource');
                if (audiosrc == theaudiosrc) {
                    $(this).addClass(m.activebutton);
                    if (audioplayer.prop('paused')) {
                        $(this).removeClass(m.activebuttonplaying);
                        $(this).addClass(m.activebuttonpaused);
                        //for now we make it look like no button is selected
                        //later we can implement better controls
                        $(this).removeClass(m.activebutton);
                    } else {
                        $(this).removeClass(m.activebuttonpaused);
                        $(this).addClass(m.activebuttonplaying);
                    }
                } else {
                    $(this).removeClass(m.activebutton);
                    $(this).removeClass(m.activebuttonplaying);
                    $(this).removeClass(m.activebuttonpaused);
                }
            });
        }
    };//end of return object

});