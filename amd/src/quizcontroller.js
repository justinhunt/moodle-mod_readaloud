/* jshint ignore:start */
define(['jquery', 'core/log','mod_readaloud/definitions','mod_readaloud/quizhelper','mod_readaloud/pollyhelper'], function($,  log, def,  quizhelper,polly) {

    "use strict"; // jshint ;_;

    log.debug('ReadAloud Quiz Controller: initialising');

    return {

        cmid: null,
        quizdata: null,
        sorryboxid: null,
        controls: null,

        //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
        init: function(props){
            var dd = this;

            //pick up opts from html
            var theid='#amdopts_' + props.widgetid;
            var configcontrol = $(theid).get(0);
            if(configcontrol){
                dd.quizdata = JSON.parse(configcontrol.value);
                $(theid).remove();
            }else{
                //if there is no config we might as well give up
                log.debug('Readaloud quiz Controller: No config found on page. Giving up.');
                return;
            }

            dd.cmid = props.cmid;
            dd.attemptid = 1;
            dd.sorryboxid = props.widgetid + '_sorrybox';

            polly.init(dd.quizdata.token,dd.quizdata.region,dd.quizdata.owner);

            dd.register_events();
            dd.process_html();
            // OR to show quiz and no recorder do dd.doquizlayout
            dd.doquizlayout();
        },

        process_html: function(){
            var opts = this.quizdata;
            // These css classes/ids are all passed in from php in
            // renderer.php::fetch_activity_amd
            var controls = {
                errorcontainer: $('.' +  opts['errorcontainer']),
                feedbackcontainer: $('.' +  opts['feedbackcontainer']),
                instructionscontainer: $('.' +  opts['instructionscontainer']),
                introbox: $('.' + 'mod_intro_box'),
                modeimagecontainer: $('#' + opts['modeimagecontainer']),
                placeholder: $('.mod_readaloud_placeholder'),
                quizcontainer: $('.' +  opts['quizcontainer']),
                smallreportcontainer: $('#' + opts['smallreportcontainer']),
                startquizbutton: $('#' + opts['startquizbutton']),
                wheretonextcontainer: $('.' +  opts['wheretonextcontainer']),
            };
            this.controls = controls;
        },

        register_events: function() {
            var dd = this;

            dd.controls.startquizbutton.click(function(e){
                dd.doquizlayout();
            });
        },

        doquizlayout: function(){
            var m = this;

            m.controls.instructionscontainer.hide();
            m.controls.smallreportcontainer.hide();
            m.controls.modeimagecontainer.removeClass('preview landr readaloud readaloudshadow report');
            m.controls.modeimagecontainer.addClass('quiz');

            // Set up the quiz.
           // quizhelper.onSubmit = function(returndata){m.dofinishedreadinglayout(returndata);};
            quizhelper.init(m.controls.quizcontainer,
                this.quizdata,
                this.cmid,
                this.attemptid,
                polly);

            // Show the quiz.
            m.controls.placeholder.hide();
            m.controls.quizcontainer.show();
        },

        doerrorlayout: function(){
            this.controls.errorcontainer.show();
            this.controls.wheretonextcontainer.show();
        }
    };// End of returned object.
});// Total end.
