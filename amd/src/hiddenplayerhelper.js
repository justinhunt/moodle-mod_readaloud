define(['jquery', 'core/log', 'mod_readaloud/definitions'], function ($, log, def) {
    "use strict"; // jshint ;_;

    log.debug('Readaloud hidden player helper: initialising');

    return {

        controls: {},
        hiddenplayer: def.hiddenplayer,
        hiddenplayerbutton: def.hiddenplayerbutton,
        hiddenplayerbuttonactive: def.hiddenplayerbuttonactive,
        hiddenplayerbuttonpaused: def.hiddenplayerbuttonpaused,
        hiddenplayerbuttonplaying: def.hiddenplayerbuttonpaused,

        init: function (opts) {
            this.register_controls();
            this.register_events();
        },

        register_controls: function () {
            this.controls.hiddenplayer = $('.' + this.hiddenplayer);
            //we stopped using this because of a race condition where buttons arrived on page after this was called
            //this.controls.hiddenplayerbutton = $('.' + this.hiddenplayerbutton);
        },

        register_events: function () {
            var that = this;
            var audioplayer = this.controls.hiddenplayer;

            // Use event delegation to handle the button click
            $(document).on('click', '.' + this.hiddenplayerbutton, function (e) {
                var audiosrc = $(this).attr('data-audiosource');
                if (audiosrc === audioplayer.attr('src') && !(audioplayer.prop('paused'))) {
                    that.dohiddenstop();
                } else {
                    that.dohiddenplay(audiosrc);
                }
            });
        },

        dohiddenplay: function (audiosrc) {
            var m = this;
            var audioplayer = m.controls.hiddenplayer;
            audioplayer.attr('src', audiosrc);
            audioplayer[0].pause();
            audioplayer[0].load();
            var pp = audioplayer[0].play();
            if (pp !== undefined) {
                pp.then(function () {
                    // Yay we are playing
                }).catch(function (error) {
                    // somethings up ... but we can ignore it
                });
            }
            m.dobuttonicons();
        },
        dohiddenstop: function () {
            var m = this;
            var audioplayer = m.controls.hiddenplayer;
            audioplayer[0].pause();
            m.dobuttonicons();
        },

        dobuttonicons: function (theaudiosrc) {
            var m = this;
            var audioplayer = m.controls.hiddenplayer;
            var thebuttons = $('.' + this.hiddenplayerbutton);
            if (!theaudiosrc) {
                theaudiosrc = audioplayer.attr('src');
            }
            thebuttons.each(function (index) {
                var audiosrc = $(this).attr('data-audiosource');
                if (audiosrc === theaudiosrc) {
                    $(this).addClass(m.hiddenplayerbuttonactive);
                    if ($(audioplayer).prop('paused')) {
                        $(this).removeClass(m.hiddenplayerbuttonplaying);
                        $(this).addClass(m.hiddenplayerbuttonpaused);
                        //for now we make it look like no button is selected
                        //later we can implement better controls
                        $(this).removeClass(m.hiddenplayerbuttonactive);
                    } else {
                        $(this).removeClass(m.hiddenplayerbuttonpaused);
                        $(this).addClass(m.hiddenplayerbuttonplaying);
                    }
                } else {
                    $(this).removeClass(m.hiddenplayerbuttonactive);
                    $(this).removeClass(m.hiddenplayerbuttonplaying);
                    $(this).removeClass(m.hiddenplayerbuttonpaused);
                }
            });
        }
    };//end of return object

});