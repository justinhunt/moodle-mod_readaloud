define(['jquery', 'core/log','mod_readaloud/definitions','core/str','core/ajax','core/templates','core/notification'],
    function ($, log, def, str, Ajax,templates, notification) {
    "use strict"; // jshint ;_;
    /*
    This file does small report
     */

    log.debug('Click to hear: initialising');

    return {
        //controls
        controls: {},
        ready: false,
        remotetranscribe: false,
        showstats: true,
        showgrades: true,
        notingradebook: false,
        attemptid: 0,
        checking: '... checking ...',
        secstillcheck: 'Checking again in: ',
        notgradedyet: 'Your reading has not been evaluated yet.',
        evaluated: 'Your reading has been evaluated.',
        notaddedtogradebook: 'This was a shadow practice, and not added to gradebook.',

        //init the module
        init: function(opts){
            this.attemptid=opts['attemptid'];
            this.ready=opts['ready'];
            this.remotetranscribe=opts['remotetranscribe'];
            this.showstats =opts['showstats'];
            this.showgrades =opts['showgrades'];
            this.filename=opts['filename'];
            this.notingradebook=opts['notingradebook'];
            this.init_strings();
            this.register_controls();
            this.register_events();


            if(!this.ready && this.attemptid){
                if(this.remotetranscribe) {
                    //check for ai results
                    this.check_for_results(this, 15);
                }
                //check for audio audio
                this.check_for_audio(0);
            }

        },

        init_strings: function(){
          var that =this;
          str.get_string('checking','mod_readaloud').done(function(s){that.checking=s;});
          str.get_string('secs_till_check','mod_readaloud').done(function(s){that.secstillcheck=s;});
          str.get_string('notgradedyet','mod_readaloud').done(function(s){that.notgradedyet=s;});
          str.get_string('evaluatedmessage','mod_readaloud').done(function(s){that.evaluated=s;});
          str.get_string('notaddedtogradebook','mod_readaloud').done(function(s){that.notaddedtogradebook=s;});
        },

        //load all the controls so we do not have to do it later
        register_controls: function(){
            this.controls.heading = $('.' + def.smallreportheading);
            this.controls.player = $('.' + def.smallreportplayer);
            this.controls.dummyplayer = $('.' + def.smallreportdummyplayer);
            this.controls.stars = $('.' + def.smallreportstars);
            this.controls.cards = $('.' + def.smallreportcards);
            this.controls.status = $('.' + def.smallreportstatus);
            this.controls.fullreportbutton = $('.' + def.fullreportbutton);
        },

        //attach the various event handlers we need
        register_events: function() {
            var that = this;
        },//end of register events

        check_for_audio: function(waitms){
            //we commence a series of ping and retries until the recorded file is available
            var that = this;
            $.ajax({
                url: that.filename,
                method: 'HEAD',
                cache: false,
                error: function () {
                    //We get here if its a 404 or 403. So settimout here and wait for file to arrive
                    //we increment the timeout period each time to prevent bottlenecks
                    log.debug('403 errors are normal here, till the audio file arrives back from conversion');
                    setTimeout(function () {
                        that.check_for_audio( waitms + 500);
                    }, waitms);
                },
                success: function (data, textStatus, xhr) {
                    switch (xhr.status) {
                        case 200:

                            //audioplayer
                            var tdata=[];
                            tdata.src=that.filename;
                            tdata.UNIQID= that.generate_random_string(8);
                            templates.render('mod_readaloud/audioplayer',tdata).then(
                                function(html,js){
                                    //that.controls.player.html(html);
                                    templates.appendNodeContents('.' + def.smallreportplayer, html, js);
                                    that.controls.dummyplayer.hide();
                                }
                            );
                            break;
                        default:
                            setTimeout(function () {
                                that.check_for_audio( waitms + 500);
                            }, waitms);
                    }

                }
            });
        },

       generate_random_string: function(length) {
            var result = '';
            var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var charactersLength = characters.length;

            for (var i = 0; i < length; i++) {
                result += characters.charAt(Math.floor(Math.random() * charactersLength));
            }

            return result;
        },

        check_for_results: function (that, seconds) {

            //decrement 1 s. At 15 seconds do the check
            seconds=seconds-1;
            if(seconds>0){
                setTimeout(that.check_for_results,1000,that,seconds);
                that.controls.status.text(that.secstillcheck + seconds);
                return;
            }

            //do the check
            that.controls.status.text(that.checking);
            Ajax.call([{
                methodname: 'mod_readaloud_check_for_results',
                args: {
                    attemptid: that.attemptid
                },
                done: function (ajaxresult) {
                    var payloadobject = JSON.parse(ajaxresult);
                    if (payloadobject) {
                        switch (payloadobject.ready) {
                            case true:
                                log.debug('result fetched');
                                var heading = that.evaluated;
                                if(that.notingradebook){
                                    heading = heading + ' ' + that.notaddedtogradebook;
                                }
                                that.controls.heading.text(heading);
                                var tdata=[];
                                tdata.ready=payloadobject.ready;

                                //stars

                                //if we are not showing grades, everyone gets 5 stars
                                if(!that.showgrades){payloadobject.rating=5;}

                                var emptystar='fa-star-o';
                                var solidstar='fa-star';
                                var stars=[];
                                for(var star=0;star<5;star++){
                                    stars[star] = payloadobject.rating > star ? solidstar : emptystar;
                                }
                                tdata.stars=stars;
                                templates.render('mod_readaloud/smallreportstars',tdata).then(
                                    function(html,js){
                                        that.controls.stars.html(html);
                                    }
                                );

                                //stats
                                //if we are not showing stats, we really should not be here
                                if(!that.showstats){
                                    that.controls.status.hide();
                                    that.controls.fullreportbutton.show();
                                    break;
                                }

                                tdata.wpm=payloadobject.wpm;
                                tdata.acc=payloadobject.acc;
                                tdata.totalwords=payloadobject.totalwords;

                                templates.render('mod_readaloud/smallreportcards',tdata).then(
                                    function(html,js){
                                        that.controls.cards.html(html);
                                    }
                                );


                                that.controls.status.hide();
                                that.controls.fullreportbutton.show();
                                break;

                            case false:
                            default:
                                log.debug('result not fetched');
                                setTimeout(that.check_for_results,1000,that,10);
                                that.controls.status.text(that.secstillcheck + seconds);
                        }
                    }
                },
                fail: notification.exception
            }]);
        },

    };//end of return value
});