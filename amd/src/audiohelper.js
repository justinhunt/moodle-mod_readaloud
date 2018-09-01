define(['jquery','core/log','mod_readaloud/cloudpoodllloader'], function($,log, cloudpoodll) {
    "use strict"; // jshint ;_;
/*
This file is largely to handle recorder specific tasks, configuring it , loading it, its appearance
It should not be concerned with anything non recorder'ish like elements on the page around
Relationships between the recorder and the surrounding elements should be managed via event handlers in activity controller
 */

    log.debug('Readaloud helper: initialising');

    return{

        status: 'stopped',

        init: function(opts,on_recording_start,
            on_recording_end,
            on_audio_processing){

            var that = this;
            cloudpoodll.init(opts['recorderid'],

                function(message){
                    console.log(message);
                    switch(message.type){
                        case 'recording':
                            if(message.action==='started'){
                                that.startbuttonclick();
                                on_recording_start(message);

                            }else if(message.action==='stopped'){
                                that.stopbuttonclick();
                                on_recording_end(message);
                            }
                            break;
                        case 'awaitingprocessing':
                            //awaitingprocessing fires often, but we only want to post once
                            if(that.status!='posted') {
                                on_audio_processing(message);
                            }
                            that.status='posted';
                            break;
                    }
                }
            );
        },
        stopbuttonclick: function(){
            var m = this;
            this.status='stopped';
            //do something
        },
        startbuttonclick: function(){
            var m = this;
            this.status='started';
           //do something
        }

    };//end of return value
});