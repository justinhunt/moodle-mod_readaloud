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
        },

        // EVERYTHING FROM HERE IS FOR REFERENCE ONLY
        transformrecorder: function(){
            //$('.' + m.recinstructionscontainerleft).hide();
            //$('.' + m.recinstructionscontainerright).hide();
            $('.' + this.recordercontainer).attr('style','width: 1px; height: 1px;');
            $('.' + this.dummyrecorder).css('background-image','url(' + M.cfg.wwwroot + '/mod/readaloud/pix/microphone.png)');
        },
        getpermissionmode: function(){
            this.awaitingpermission=true;
            this.doshowsettings();
            $('.' + this.recinstructionscontainerleft).addClass('mod_readaloud_getpermissionmode');
            $('.' + this.recinstructionscontainerright).addClass('mod_readaloud_getpermissionmode');
        },
        clearpermissionmode: function(){
            this.awaitingpermission=false;
            $('.' + this.recinstructionscontainerleft).removeClass('mod_readaloud_getpermissionmode');
            $('.' + this.recinstructionscontainerright).removeClass('mod_readaloud_getpermissionmode');
        },
        recordbuttonclick: function(){
            var m = M.mod_readaloud.audiohelper;

            $(this).text(M.util.get_string('done','mod_readaloud'));
            if(M.mod_readaloud.audiohelper.awaitingpermission){
                $(this).text(M.util.get_string('recordnameschool','mod_readaloud'));
                M.mod_readaloud.audiohelper.clearpermissionmode();
                return;
            }
            if(m.fetchrecstatus() =='stopped'){
                if(!m.recorderallowed){
                    M.mod_readaloud.audiohelper.getpermissionmode();
                    return;
                }
                m.dorecord();
            }else{
                //reset the text label
                $(this).text(M.util.get_string('recordnameschool','mod_readaloud'));
                m.dostop();
                if(m.gotsound){
                    $('.' + m.recordbutton).hide();
                    $('.' + m.startbutton).prop('disabled',false);
                }else{
                    alert(M.util.get_string('gotnosound','mod_readaloud'));
                }
            }
        },

        poodllcallback: function(args){
            if(args[1] !='timerevent'){
                //console.log ("poodllcallback:" + args[0] + ":" + args[1] + ":" + args[2] + ":" + args[3] + ":" + args[4] + ":" + args[5] + ":" + args[6]);
            }
            switch(args[1]){
                case 'allowed':
                    this.recorderallowed = args[2];
                    if (this.recorderallowed){
                        this.transformrecorder();
                        //if allowed was done after pressing record button
                        //commence recording
                        if(this.awaitingpermission){
                            this.dorecord();
                        }
                        this.clearpermissionmode();
                    }
                    break;

                case 'statuschanged':
                    this.status = args[2];
                    if(this.status=='haverecorded' && this.passagerecorded){
                        this.douploadlayout();
                        this.doexport();
                    }
                    break;
                case 'filesubmitted':
                    this.dofinishedlayout();
                    break;

                case 'uploadstarted':
                    break;
                case 'showerror':
                    //probably should have better error logic than this.
                    this.doerrorlayout();
                    break;
                case 'actionerror':
                    break;
                case 'timeouterror':
                    break;
                case 'nosound':	alert(M.util.get_string('gotnosound','mod_readaloud'));
                    break;
                case 'conversionerror':
                    break;
                case 'beginningconversion':
                    break;
                case 'conversioncomplete':
                    break;
                case 'timerevent':
                    if(args[2]!='0'){
                        //we rather lamely hijack this to run our volume events
                        //console.log(lz.embed[args[0]].getCanvasAttribute('displaytime'));
                        this.dogotsound(lz.embed[args[0]].getCanvasAttribute('currentvolume'));
                    }
                    break;
                case 'volumeevent':
                    if(args[2] > 0){
                        //we no longer use this cos its hard to make a graph when vol don't change
                        //console.log('volume:' + args[2]);
                        //this.dogotsound(args[2]);
                    }
                    break;

                case 'volume':
                    //console.log('volume:' + args[2]);
                    break;

            }
        },
        makecanvas: function(div) {
            var canvas = this.fetchcanvas(div);
            if(!canvas){
                canvas = document.createElement('canvas');
            }
            var thediv = document.getElementById(div);
            canvas.id     = div + '_canvas';
            canvas.className = 'mod_readaloud_voicecanvas';
            thediv.appendChild(canvas);
            return canvas;
        },
        fetchcanvas: function(div) {
            return document.getElementById(div + '_canvas');
        },
        drawvoicechart: function(div){
            var canvas = this.fetchcanvas(div);
            if(!canvas){return;}
            var ctx = canvas.getContext("2d");
            var xsteps = this.sounds.length * 2;
            ctx.clearRect(0,0,canvas.width,canvas.height);
            var cxzero = 0;
            var cyzero = canvas.height / 2;
            var cyratio = cyzero / canvas.height;
            var cxratio = canvas.width / xsteps;

            //get y values by x steps (10 points each side)
            var ys = Array();
            for(var x=0; x< xsteps;x+=2){
                ys[x] = (this.sounds[x] * cyratio) + cyzero;
                ys[x+1] = (this.sounds[x] * cyratio * -1) + cyzero;
            }
            //reverse array to go out to in
            // this got weird. so canned it
            //ys.reverse();

            //draw from right to center
            ctx.beginPath();
            ctx.moveTo(canvas.width,cyzero);
            for(var x=0; x< xsteps;x++){
                ctx.lineTo(canvas.width - (x*cxratio),ys[x]);
            }
            ctx.stroke();
            //draw from left to center
            ctx.beginPath();
            ctx.moveTo(0,cyzero);
            for(var x=0; x< xsteps;x++){
                ctx.lineTo(x*cxratio,ys[x]);
            }
            ctx.stroke();
        },
        dogotsound: function(level){
            if(this.sounds.length > 10){
                this.sounds.shift();
            }
            this.sounds.push(level);
            if(level>0){
                this.gotsound=true;
            }
            if(this.fetchrecstatus()!== 'stopped'){
                //this.drawvoicechart(this.recinstructionscontainerleft);
                this.drawvoicechart(this.dummyrecorder);
            }
        },



        //this function shows how to call the MP3 recorder's API to commence recording
        dorecord: function(){
            $('.' + this.dummyrecorder).removeClass(this.dummyrecorder + '_stopped');
            $('.' + this.dummyrecorder).addClass(this.dummyrecorder + '_recording');
            this.makecanvas(this.dummyrecorder);
        }


    };//end of return value
});