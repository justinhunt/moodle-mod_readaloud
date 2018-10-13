/* jshint ignore:start */
define(['jquery','jqueryui', 'core/log','mod_readaloud/audiohelper'], function($, jqui, log, audiohelper) {

    "use strict"; // jshint ;_;

    log.debug('Activity controller: initialising');

    return {

        cmid: null,
        activitydata: null,
        holderid: null,
        recorderid: null,
        playerid: null,
        sorryboxid: null,
        controls: null,
        ra_recorder: null,
        rec_time_start: 0,

        //for making multiple instances
        clone: function(){
            return $.extend(true,{},this);
        },

        //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
        init: function(props){
            var dd = this.clone();

            //pick up opts from html
            var theid='#amdopts_' + props.widgetid;
            var configcontrol = $(theid).get(0);
            if(configcontrol){
                dd.activitydata = JSON.parse(configcontrol.value);
                $(theid).remove();
            }else{
                //if there is no config we might as well give up
                log.debug('Read Aloud Test Controller: No config found on page. Giving up.');
                return;
            }

            dd.cmid = props.cmid;
            dd.holderid = props.widgetid + '_holder';
            dd.recorderid = props.widgetid + '_recorder';
            dd.playerid = props.widgetid + '_player';
            dd.sorryboxid = props.widgetid + '_sorrybox';

            //if the browser doesn't support html5 recording.
            //then warn and do not go any further
            if(!dd.is_browser_ok()){
                $('#' + dd.sorryboxid).show();
                return;
            }

            dd.setup_recorder();
            dd.process_html(dd.activitydata);
            dd.register_events();
        },



        process_html: function(opts){

            var controls ={

                hider: $('.' + opts['hider']),
                introbox: $('.' + 'mod_intro_box'),
                progresscontainer: $('.' +  opts['progresscontainer']),
                feedbackcontainer: $('.' +  opts['feedbackcontainer']),
                errorcontainer: $('.' +  opts['errorcontainer']),
                passagecontainer: $('.' +  opts['passagecontainer']),
                recordingcontainer: $('.' +  opts['recordingcontainer']),
                dummyrecorder: $('.' +  opts['dummyrecorder']),
                recordercontainer: $('.' +  opts['recordercontainer']),
                instructionscontainer: $('.' +  opts['instructionscontainer']),
                recinstructionscontainerright: $('.' +  opts['recinstructionscontainerright']),
                recinstructionscontainerleft: $('.' +  opts['recinstructionscontainerleft']),
                allowearlyexit: $('.' +  opts['allowearlyexit']),
                wheretonextcontainer: $('.' +  opts['wheretonextcontainer'])
            };
            this.controls = controls;
        },

        beginall: function(){
            var m = this;
           // m.dorecord();
            m.passagerecorded = true;
        },

        is_browser_ok: function(){
            return (navigator && navigator.mediaDevices
                && navigator.mediaDevices.getUserMedia);
        },

        setup_recorder: function(){
            var dd = this;

            //Set up the callback functions for the audio recorder

            //originates from the recording:started event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_start= function(eventdata){

                dd.rec_time_start = new Date().getTime();
                dd.dopassagelayout();
                dd.controls.passagecontainer.show(1000,dd.beginall);
            };

            //originates from the recording:ended event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_end= function(eventdata){
                //its a bit hacky but the rec end event can arrive immed. somehow probably when the mic test ends
                var now = new Date().getTime();
                if((now - dd.rec_time_start) < 3000){
                    return;
                }
                dd.douploadlayout();
            };

            //data sent here originates from the awaiting_processing event
            //See https://api.poodll.com
           var on_audio_processing= function(eventdata){
                //at this point we know the submission has been uploaded and we know the fileURL
               //so we send the submission
               dd.send_submission(eventdata.mediaurl);
              //and let the user know that they are all done
               dd.dofinishedlayout();
            };

            //init the recorder
            audiohelper.init(dd.activitydata,
                on_recording_start,
                on_recording_end,
                on_audio_processing);
        },

        register_events: function() {
            var dd = this;

			//events for other controls on the page
            //ie not recorders
            //dd.controls.passagecontainer.click(function(){log.debug('clicked');})
        },



        send_submission: function(filename){

            //set up our ajax request
            var xhr = new XMLHttpRequest();
            var that = this;
            
            //set up our handler for the response
            xhr.onreadystatechange = function(e){
                if(this.readyState===4){
                    if(xhr.status==200){
                        log.debug('ok we got an attempt submission response');
                        //get a yes or forgetit or tryagain
                        var payload = xhr.responseText;
                        var payloadobject = JSON.parse(payload);
                        if(payloadobject){
                            switch(payloadobject.success) {
                                case true:
                                    log.debug('attempted submission accepted');

                                    break;

                                case false:
                                default:
                                    log.debug('attempted item evaluation failure');
                                    if (payloadobject.message) {
                                        log.debug('message: ' + payloadobject.message);
                                    }
                            }
                        }
                     }else{
                        log.debug('Not 200 response:' + xhr.status);
                    }
                }
            };

            var params = "cmid=" + that.cmid + "&filename=" + filename;
            xhr.open("POST",M.cfg.wwwroot + '/mod/readaloud/ajaxhelper.php', true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.setRequestHeader("Cache-Control", "no-cache");
            xhr.send(params);
        },


        dopassagelayout: function(){
            var m = this;
            m.controls.introbox.hide();
            //m.controls.instructionscontainer.hide();
            if(m.controls.allowearlyexit){
              //  m.controls.stopbutton.hide();
            }
        },
        douploadlayout: function(){
            var m = this;
            m.controls.passagecontainer.addClass('mod_readaloud_passage_finished');
            m.controls.hider.fadeIn('fast');
            m.controls.progresscontainer.fadeIn('fast');
        },

        dofinishedlayout: function(){
            var m = this;
            m.controls.hider.fadeOut('fast');
            m.controls.progresscontainer.fadeOut('fast');
            m.controls.instructionscontainer.hide();
            m.controls.passagecontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.feedbackcontainer.show();
            m.controls.wheretonextcontainer.show();
        },
        doerrorlayout: function(){
            var m = this;
            m.controls.hider.fadeOut('fast');
            m.controls.progresscontainer.fadeOut('fast');
            m.controls.passagecontainer.hide();
            m.controls.recordingcontainer.hide();
            m.controls.errorcontainer.show();
            m.controls.wheretonextcontainer.show();
        }
    };//end of returned object
});//total end
