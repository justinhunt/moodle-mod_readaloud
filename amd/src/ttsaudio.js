define(['jquery','core/log'], function($,log) {
    "use strict"; // jshint ;_;


    log.debug('ReadAloud TTS Audio: initialising');

    return{

        init: function(AUDIOID){

            //DECLARATIONS and INITs ...........................
            var stoporpause='stop';

            //audio player declarations
            var aplayer = $('#' + AUDIOID + '_ttsaudioplayer');
            var fa = $('#' + AUDIOID + '_ttsaudio .fa');

            //FUNCTION: play a single sentence and mark it active for display purposes
            var doplayaudio = function(){
                aplayer[0].play();
            };


            //AUDIO PLAYER events
            aplayer[0].addEventListener('ended', function(){
                    $(fa).removeClass('fa-stop');
                    $(fa).addClass('fa-volume-up');
            });

            aplayer[0].addEventListener('play', function(){
                $(fa).removeClass('fa-volume-up');
                $(fa).addClass('fa-stop');
            });

            //handle audio player button clicks
            $('#' + AUDIOID).click(function(){
                if(!aplayer[0].paused && !aplayer[0].ended){
                    aplayer[0].pause();
                    if(stoporpause=='stop'){
                        aplayer[0].load();
                    }
                    $(fa).removeClass('fa-stop');
                    $(fa).addClass('fa-volume-up');

                    //if paused and in limbo no src state
                }else if(aplayer[0].paused && aplayer.attr('src')){
                    aplayer[0].play();
                    $(fa).removeClass('fa-volume-up');
                    $(fa).addClass('fa-stop');
                    //play
                }else{
                    doplayaudio();
                    $(fa).removeClass('fa-volume-up');
                    $(fa).addClass('fa-stop');
                }//end of if paused ended
            });
            //end of instance wrapper
        }

    };//end of return value
});