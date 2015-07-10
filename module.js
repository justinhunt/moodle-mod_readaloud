// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JavaScript library for the readaloud module.
 *
 * @package    mod
 * @subpackage readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_readaloud = M.mod_readaloud || {};

M.mod_readaloud.helper = {
	gY: null,
	 /**
     * @param Y the YUI object
     * @param opts an array of options
     */
    init: function(Y,opts) {
    	M.mod_readaloud.helper.gY = Y;
    }
};

M.mod_readaloud.gradinghelper = {
	audioplayerclass: 'mod_readaloud_grading_player',
	wordclass: 'mod_readaloud_grading_passageword',
	spaceclass: 'mod_readaloud_grading_passagespace',
	badwordclass: 'mod_readaloud_grading_badword',
	endspaceclass: 'mod_readaloud_grading_endspace',
	unreadwordclass:  'mod_readaloud_grading_unreadword',
	wpmscoreid: 'mod_readaloud_grading_wpm_score',
	errorscoreid: 'mod_readaloud_grading_error_score',
	formelementscore: 'mod_readaloud_grading_form_sessionscore',
	formelementendword: 'mod_readaloud_grading_form_sessionendword',
	formelementtime: 'mod_readaloud_grading_form_sessiontime',
	formelementerrors: 'mod_readaloud_grading_form_sessionerrors',
	totalseconds: 60,
	enforcemarker: true,
	totalwordcount: 0,
	wpm: 0,
	endwordnumber: 0,
	errorwords: {},
	activityid: null,
	attemptid: null,
	sesskey: null,
	passagecontainer: 'mod_readaloud_grading_passagecont',

	init: function(Y,opts){
		//stash important info
		this.activityid = opts['activityid'];
		this.attemptid = opts['attemptid'];
		this.sesskey = opts['sesskey'];
		this.totalwordcount = $('.' + this.wordclass).length ;
		
		if(opts['sessiontime']>0){
		debugger;
			this.errorwords=JSON.parse(opts['sessionerrors']);
			this.totalseconds=opts['sessiontime'];
			this.endwordnumber=opts['sessionendword'];
			this.wpm=opts['sessionscore'];
			//if this has been graded, draw the gradestate
			this.redrawgradestate();
		}else{
			//set up our end passage marker
			this.endwordnumber = this.totalwordcount;	
		}
		
		//add the endword marker
		$('#' + this.spaceclass + '_' + this.endwordnumber).addClass(this.endspaceclass);
		
		//set up event handlers
		//in review mode, do nuffink though ... thats for the student
		if(opts['reviewmode']){
			$('.' + this.wordclass).click(this.playword);
		}else{
			$('.' + this.wordclass).click(this.processword);
			$('.' + this.spaceclass).click(this.processspace);
		}
		//initialise our audio duration. 
		//After that we call processscores to init score boxe
		//TODO: really should get audio duration at recording time.
		var audioplayer = $('#' + this.audioplayerclass);
		if(audioplayer.prop('readyState')<1){
			audioplayer.on('loadedmetadata',this.processloadedaudio);
		}else{
			this.processloadedaudio();
		}
	},
	playword: function(){
		//for now do nothing;
		
	},
	redrawgradestate: function(){
		var m = M.mod_readaloud.gradinghelper;
		this.processunread();
		debugger;
		$.each(m.errorwords,function(index){
				$('#' + m.wordclass + '_' + this.wordnumber).addClass(m.badwordclass);
			}
		);
		
	},
	adderrorword: function(wordnumber,word) {
		this.errorwords[wordnumber] = {word: word, wordnumber: wordnumber};
		//console.log(this.errorwords);
		return;
	},
	processword: function() {
		var m = M.mod_readaloud.gradinghelper;
		var wordnumber = $(this).attr('data-wordnumber');
		var theword = $(this).text();
		//this will disallow badwords after the endmarker
		if(m.enforcemarker && Number(wordnumber)>Number(m.endwordnumber)){
			return;
		}
				
		if(wordnumber in m.errorwords){
			delete m.errorwords[wordnumber];
			$(this).removeClass(m.badwordclass);
		}else{
			m.adderrorword(wordnumber,theword);
			$(this).addClass(m.badwordclass);
		}
		m.processscores();
	},
	processspace: function() {
		//this event is entered by  click on space
		//it relies on attr data-wordnumber being set correctly
		var m = M.mod_readaloud.gradinghelper;
		var wordnumber = $(this).attr('data-wordnumber');
		var thespace = $('#' + m.spaceclass + '_' + wordnumber);
		
		if(wordnumber == m.endwordnumber){
			m.endwordnumber = m.totalwordcount;
			thespace.removeClass(m.endspaceclass);
			$('#' + m.spaceclass + '_' + m.totalwordcount).addClass(m.endspaceclass);
		}else{
			$('#' + m.spaceclass + '_' + m.endwordnumber).removeClass(m.endspaceclass);
			m.endwordnumber = wordnumber;
			thespace.addClass(m.endspaceclass);
		}
		m.processunread();
		m.processscores();
	},
	processunread: function(){
		var m = M.mod_readaloud.gradinghelper;
		$('.' + m.wordclass).each(function(index){
			var wordnumber = $(this).attr('data-wordnumber');
			if(Number(wordnumber)>Number(m.endwordnumber)){
				$(this).addClass(m.unreadwordclass);
				//this will clear badwords after the endmarker
				if(m.enforcemarker && wordnumber in m.errorwords){
					delete m.errorwords[wordnumber];
					$(this).removeClass(m.badwordclass);
				}
			}else{
				$(this).removeClass(m.unreadwordclass);
			}
		})
	},
	processscores: function(){
		var m = M.mod_readaloud.gradinghelper;
		var wpmscorebox = $('#' + m.wpmscoreid);
		var errorscorebox = $('#' + m.errorscoreid);
		var errorscore = Object.keys(m.errorwords).length;
		errorscorebox.text(errorscore);
		var wpmscore = Math.round((m.endwordnumber - errorscore) * 60 / m.totalseconds);
		m.wpm = wpmscore;
		wpmscorebox.text(wpmscore);
		//update form field
		debugger;
		$("#" + m.formelementscore).val(wpmscore);
		$("#" + m.formelementendword).val(m.endwordnumber);
		$("#" + m.formelementerrors).val(JSON.stringify(m.errorwords));
		
	},
	processloadedaudio: function(){
		var m = M.mod_readaloud.gradinghelper;
		m.totalseconds = Math.round($('#' + m.audioplayerclass).prop('duration'));
		//update form field
		$("#" + m.formelementtime).val(m.totalseconds);
		m.processscores();
	}
};

M.mod_readaloud.audiohelper = {	
	recorderid: null,
	recordbutton: null,
	startbutton: null,
	stopbutton: null,
	gotsound: false,
	hider: null,
	passagerecorded: false,
	sounds: Array(),
	awaitingpermission: false,
	recorderallowed: false,
	passagecontainer: null,
	feedbackcontainer: null,
	errorcontainer: null,
	recordingcontainer: null,
	recordercontainer: null,
	dummyrecorder: null,
	instructionscontainer: null,
	progresscontainer: null,
	recinstructionscontainerright: null,
	recinstructionscontainerleft: null,
	status: 'stopped',

	init: function(Y,opts){
		lzOptions = {ServerRoot: '\\'};
		lz.embed.swf(JSON.parse(opts['recorderjson']));
		this.recorderid = opts['recorderid'];
		this.recordbutton = opts['recordbutton'];
		this.startbutton = opts['startbutton'];
		this.stopbutton = opts['stopbutton'];
		this.hider = opts['hider'];
		this.progresscontainer = opts['progresscontainer'];
		this.feedbackcontainer = opts['feedbackcontainer'];
		this.errorcontainer= opts['errorcontainer'];
		this.passagecontainer = opts['passagecontainer'];
		this.recordingcontainer= opts['recordingcontainer'];
		this.dummyrecorder= opts['dummyrecorder'];
		this.recordercontainer= opts['recordercontainer'];
		this.instructionscontainer= opts['instructionscontainer'];
		this.recinstructionscontainerright= opts['recinstructionscontainerright'];
		this.recinstructionscontainerleft= opts['recinstructionscontainerleft'];
		$('.' + this.recordbutton).click(this.recordbuttonclick);
		$('.' + this.startbutton).click(this.startbuttonclick);
		$('.' + this.stopbutton).click(this.stopbuttonclick);
	},
	beginall: function(){
		var m = M.mod_readaloud.audiohelper;
		m.dorecord();
		m.passagerecorded = true;
	},
	stopbuttonclick: function(){
		var m = M.mod_readaloud.audiohelper;
		if(m.fetchrecstatus() !='stopped'){
			m.dostop();
		}
	},
	startbuttonclick: function(){
		var m = M.mod_readaloud.audiohelper;
		if(m.fetchrecstatus() !='stopped'){
			m.dostop();
		}
		m.dopassagelayout();
		$('.' + m.passagecontainer).show(1000,m.beginall);
		
	},
	dopassagelayout: function(){
		var m = M.mod_readaloud.audiohelper;
		$('.mod_intro_box').hide();
		$('.' + m.recordbutton).hide();
		$('.' + m.startbutton).hide();
		$('.' + m.instructionscontainer).hide();
	},
	douploadlayout: function(){
		var m = M.mod_readaloud.audiohelper;
		$('.' + m.passagecontainer).addClass('mod_readaloud_passage_finished');
		$('.' + m.stopbutton).prop('disabled',true);
		$('.' + m.hider).fadeIn('slow');
		$('.' + m.progresscontainer).fadeIn('slow');
	},
	dofinishedlayout: function(){
		var m = M.mod_readaloud.audiohelper;
		$('.' + m.hider).fadeOut('fast');
		$('.' + m.progresscontainer).fadeOut('fast');
		$('.' + m.passagecontainer).hide();
		$('.' + m.recordingcontainer).hide();
		$('.' + m.dummyrecorder).hide();
		$('.' + m.feedbackcontainer).show();
	},
	doerrorlayout: function(){
		var m = M.mod_readaloud.audiohelper;
		$('.' + m.hider).fadeOut('fast');
		$('.' + m.progresscontainer).fadeOut('fast');
		$('.' + m.passagecontainer).hide();
		$('.' + m.recordingcontainer).hide();
		$('.' + m.dummyrecorder).hide();
		$('.' + m.errorcontainer).show();
	},
	transformrecorder: function(){
		//$('.' + m.recinstructionscontainerleft).hide();
		//$('.' + m.recinstructionscontainerright).hide();
		$('.' + this.recordercontainer).attr('style','width: 1px; height: 1px;');
		$('.' + this.dummyrecorder).removeClass(this.dummyrecorder + '_hidden');
		$('.' + this.dummyrecorder).addClass(this.dummyrecorder + '_stopped');
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
	fetchrecstatus: function(){
		return this.fetchrecproperty('recorderstatus');
	},
	fetchrecproperty: function(propertyname){
		return lz.embed[this.recorderid].getCanvasAttribute(propertyname);
	},
	poodllcallback: function(args){
		if(args[1] !='timerevent'){
			console.log ("poodllcallback:" + args[0] + ":" + args[1] + ":" + args[2] + ":" + args[3] + ":" + args[4] + ":" + args[5] + ":" + args[6]);
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
				console.log('volume:' + args[2]);
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
	//handles calls into the recorder
	dorecorderapi: function(callingfunction){
		if(lz.embed[this.recorderid] != null){
			var apicall = '';
			switch(callingfunction){
				case 'dorecord': apicall = 'poodllapi.mp3_record()';break;
				case 'dostop': apicall = 'poodllapi.mp3_stop()';break;
				case 'dopause': apicall = 'poodllapi.mp3_pause()';break;
				case 'doshowsettings': apicall = 'poodllapi.mp3_show_settings()';break;
				case 'doplay': apicall = 'poodllapi.mp3_play()';break;
				case 'dodisable': apicall = 'poodllapi.mp3_disable()';break;
				case 'doenable': apicall = 'poodllapi.mp3_enable()';break;
			}
			lz.embed[this.recorderid].callMethod(apicall);
		}
	
	},
	//this function shows how to call the MP3 recorder's API to export the recording to the server
	doexport: function(){
		if(lz.embed[this.recorderid] != null){
			lz.embed[this.recorderid].callMethod('poodllapi.mp3_export()');
		}else{
			deferredexport(this.recorderid);
		}
	},
	
	//this function shows how to call the MP3 recorder's API to commence recording
	doshowsettings: function(){
		this.dorecorderapi('doshowsettings');
	},

	//this function shows how to call the MP3 recorder's API to commence recording
	dorecord: function(){
		this.dorecorderapi('dorecord');
		$('.' + this.dummyrecorder).removeClass(this.dummyrecorder + '_stopped');
		$('.' + this.dummyrecorder).addClass(this.dummyrecorder + '_recording');
		//this.makecanvas(this.recinstructionscontainerleft);
		this.makecanvas(this.dummyrecorder);
	},

	//this function shows how to call the MP3 recorder's API to playback the recording
	doplay: function(){
		this.dorecorderapi('doplay');
	},
	
	//this function shows how to call the MP3 recorder's API to playback the recording
	dopause: function(){
		this.dorecorderapi('dopause');
	},
	
	//this function shows how to call the MP3 recorder's API to stop the recording or playback
	dostop: function(){
		this.dorecorderapi('dostop');
		$('.' + this.dummyrecorder).removeClass(this.dummyrecorder + '_recording');
		$('.' + this.dummyrecorder).addClass(this.dummyrecorder + '_stopped');
	},
	
	//this function shows how to call the MP3 recorder's API to stop the recording or playback
	dodisable: function(){
		this.dorecorderapi('dodisable');
	}

};
