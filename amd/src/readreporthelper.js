define(['jquery', 'core/log','mod_readaloud/definitions','mod_readaloud/passagemarkuphelper',
    'core/str','core/ajax','core/templates','core/notification'],
    function ($, log, def, passagemarkuphelper, str, Ajax,templates, notification) {
    "use strict"; // jshint ;_;
    /*
    This file does read report
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
        resultsnotready: 'Your reading could not be evaluated.',
        evaluated: 'Your reading has been evaluated.',
        notaddedtogradebook: 'This was a shadow practice, and not added to gradebook.',

        //class definitions
        cd: {
            wordclass: def.wordclass,
            spaceclass: def.spaceclass,
            badwordclass: def.badwordclass,
            endspaceclass: def.endspaceclass,
            unreadwordclass: def.unreadwordclass,
            unreadspaceclass: def.unreadspaceclass,
            aiunmatched: def.aiunmatched,
            passagecontainer: def.passagecontainer,
            fullreportcontainer: def.fullreportcontainer,
        },

        //init the module
        init: function(opts){
            log.debug("read report opts");
            log.debug(opts);
            this.cmid=opts['cmid'];
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

            //Init the full report passage
            passagemarkuphelper.init($('.' + this.cd.passagecontainer));//fullreportcontainer
            log.debug($('.' + this.cd.passagecontainer));
            if(opts['sessionmatches']){
                passagemarkuphelper.markup_passage(opts['sessionmatches'],opts['sessionerrors'],opts['sessionendword']);

            }

            //if we are ready, we can start checking for results
            if(!this.ready && this.attemptid){
                log.debug('not ready yet, will start checking for results');
                this.start_check_for_results();
            }

        },

        start_check_for_results: function(initialseconds){
            //reset the results display to the pre-data state
            this.reset_display();
            this.resultchecks = 0;

            //How long to wait before the first check. The upload path needs time for the audio to reach the
            //cloud and be transcribed, but a streaming attempt was graded before we got here, so the caller
            //passes a short wait and the student sees their result straight away.
            var firstwait = (typeof initialseconds === 'number') ? initialseconds : 15;

            //if we are doing remote transcribe, we need to check for results
            if(this.remotetranscribe) {
                //check for ai results
                log.debug('doing remote transcribe, so begin check for results in ' + firstwait + 's');
                this.check_for_results(this, firstwait);
            } else {
                log.debug('not doing remote transcribe, so no need to check for results');
            }
            //check for audio audio
            this.check_for_audio(0);
        },

        init_strings: function(){
          var that =this;
          str.get_string('checking','mod_readaloud').done(function(s){that.checking=s;});
          str.get_string('secs_till_check','mod_readaloud').done(function(s){that.secstillcheck=s;});
          str.get_string('notgradedyet','mod_readaloud').done(function(s){that.notgradedyet=s;});
          str.get_string('resultsnotready','mod_readaloud').done(function(s){that.resultsnotready=s;});
          str.get_string('evaluatedmessage','mod_readaloud').done(function(s){that.evaluated=s;});
          str.get_string('notaddedtogradebook','mod_readaloud').done(function(s){that.notaddedtogradebook=s;});
        },

        //load all the controls so we do not have to do it later
        register_controls: function(){
            this.controls.heading = $('.' + def.readreportheading);
            this.controls.fullreportcontainer = $('.' + def.fullreportcontainer);
            this.controls.player = $('.' + def.readreportplayer);
            this.controls.dummyplayer = $('.' + def.readreportdummyplayer);
            this.controls.stars = $('.' + def.readreportstars);
            this.controls.cards = $('.' + def.readreportcards);
            this.controls.status = $('.' + def.readreportstatus);
        },

        //attach the various event handlers we need
        update_filename: function(filename) {
            var that = this;
            that.filename = filename;
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
                                    $('.' + def.readreportplayer).html('');
                                    templates.appendNodeContents('.' + def.readreportplayer, html, js);
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

        /*
        * Play the reading back from the local blob while the cloud copy is still uploading and transcoding.
        * check_for_audio() swaps in the real url once it returns a 200, so this is only ever a stand in.
        * The read report template may not be on the page yet when this is called, so retry for a short while.
         */
        show_local_audio: function(bloburl, attempts){
            var that = this;
            if(!bloburl){
                return;
            }
            var tries = (typeof attempts === 'number') ? attempts : 0;
            if($('.' + def.readreportplayer).length === 0){
                //give up after about 10 seconds, check_for_audio will fill the player in soon enough anyway
                if(tries > 20){
                    log.debug('gave up waiting for the read report player to appear');
                    return;
                }
                setTimeout(function(){
                    that.show_local_audio(bloburl, tries + 1);
                }, 500);
                return;
            }
            var tdata = [];
            tdata.src = bloburl;
            tdata.UNIQID = that.generate_random_string(8);
            templates.render('mod_readaloud/audioplayer', tdata).then(
                function(html, js){
                    $('.' + def.readreportplayer).html('');
                    templates.appendNodeContents('.' + def.readreportplayer, html, js);
                    that.controls.dummyplayer.hide();
                }
            );
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

        reset_display: function(){
            //reset the read report to the default state
            this.controls.heading.text(this.notgradedyet);
            this.controls.stars.html('');
            this.controls.cards.html('');
            this.controls.status.text(this.notgradedyet);
            this.controls.status.show();
            this.controls.fullreportcontainer.hide();
            this.controls.player.html('');
            this.controls.dummyplayer.show();
        },

        //call back function
        on_results_fetched: function() {},

        //How many times we will ask the server for a result before giving up. Each round trip is preceded by
        //a 10 second wait, so this is roughly 10 minutes. Nothing should ever take that long, but without a
        //cap a result that never arrives leaves the student watching a countdown for the rest of the lesson.
        maxresultchecks: 60,
        resultchecks: 0,

        check_for_results: function (that, seconds) {

            //decrement 1 s. At 15 seconds do the check
            seconds = seconds - 1;
            if(seconds>0){
                setTimeout(that.check_for_results,1000,that,seconds);
                that.controls.status.text(that.secstillcheck + seconds);
                log.debug('seconds till next check: ' + seconds);
                return;
            }

            //do the check
            that.controls.status.text(that.checking);
            Ajax.call([{
                methodname: 'mod_readaloud_check_for_results',
                args: {
                    cmid: that.cmid
                },
                done: function (ajaxresult) {
                    var payloadobject = JSON.parse(ajaxresult);
                    if (payloadobject) {
                        switch (payloadobject.ready) {
                            case true:
                                log.debug('result fetched');
                                // Alert any listeners
                                that.on_results_fetched();
                                break;

                            case false:
                            default:
                                log.debug('result not fetched');
                                that.resultchecks = that.resultchecks + 1;
                                if (that.resultchecks >= that.maxresultchecks) {
                                    log.debug('giving up waiting for a result');
                                    that.controls.status.text(that.resultsnotready);
                                    return;
                                }
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