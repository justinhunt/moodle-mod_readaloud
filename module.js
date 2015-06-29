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

M.mod_readaloud.audiohelper = {	
	recorderid: null,
	recordbutton: null,
	startbutton: null,
	stopbutton: null,
	awaitingpermission: false,
	recorderallowed: false,
	passagecontainer: null,
	recordingcontainer: null,
	recordercontainer: null,
	dummyrecorder: null,
	instructionscontainer: null,
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
	},
	stopbuttonclick: function(){
		var m = M.mod_readaloud.audiohelper;
		if(m.fetchrecstatus() !='stopped'){
			m.dostop();
			alert('All done');
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
	transformrecorder: function(){
		//$('.' + m.recinstructionscontainerleft).hide();
		//$('.' + m.recinstructionscontainerright).hide();
		$('.' + this.recordercontainer).attr('style','width: 1px; height: 1px;');
		$('.' + this.dummyrecorder).removeClass(this.dummyrecorder + '_hidden');
		$('.' + this.dummyrecorder).addClass(this.dummyrecorder + '_stopped');
	},
	getpermissionmode: function(){
	console.log('getmpermissionde');
		this.awaitingpermission=true;
		this.doshowsettings();
		$('.' + this.recinstructionscontainerleft).addClass('mod_readaloud_getpermissionmode');
		$('.' + this.recinstructionscontainerright).addClass('mod_readaloud_getpermissionmode');
	},
	clearpermissionmode: function(){
		console.log('clearrecoderallowed1');
		this.awaitingpermission=false;
		$('.' + this.recinstructionscontainerleft).removeClass('mod_readaloud_getpermissionmode');
		$('.' + this.recinstructionscontainerright).removeClass('mod_readaloud_getpermissionmode');
	},
	recordbuttonclick: function(){
		var m = M.mod_readaloud.audiohelper;
		$(this).text("Stopo");	
		if(M.mod_readaloud.audiohelper.awaitingpermission){
			M.mod_readaloud.audiohelper.clearpermissionmode();
			return;
		}
		if(m.fetchrecstatus() =='stopped'){
			if(!m.recorderallowed){
				$(this).text("Stopppo");
				M.mod_readaloud.audiohelper.getpermissionmode();
				return;
			}
			m.dorecord();
		}else{
			//reset the text label
			$(this).text("Recordo");
			m.dostop();
			$('.' + m.recordbutton).hide();
			$('.' + m.startbutton).prop('disabled',false);
		}
	},
	fetchrecstatus: function(){
		return this.fetchrecproperty('recorderstatus');
	},
	fetchrecproperty: function(propertyname){
		return lz.embed[this.recorderid].getCanvasAttribute(propertyname);
	},
	poodllcallback: function(args){
		console.log ("poodllcallback:" + args[0] + ":" + args[1] + ":" + args[2] + ":" + args[3] + ":" + args[4] + ":" + args[5] + ":" + args[6]);
		
		switch(args[1]){
			case 'allowed':
					this.recorderallowed = args[2]; 
					console.log('recoderallowed1:' + this.recorderallowed );
					if (this.recorderallowed){
						console.log('recoderallowed2');
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
					break;
			case 'filesubmitted':
					//audio filename
					var audlabel=document.createTextNode("filename: " + args[2]);
					
					//audio element
					var aud=document.createElement('audio');
					aud.controls="controls";
					
					//audio source
					var dasrc = document.createElement('source');
					dasrc.type= 'audio/mpeg';
					dasrc.src="out/" + args[2];
					dasrc.setAttribute("preload","auto");
					
					//set audio src
					aud.appendChild(dasrc);
					aud.load();	

					//put it all on the page
					var players = document.getElementById('players');
					players.appendChild(audlabel);
					players.appendChild(document.createElement('br'));
					players.appendChild(aud);
					players.appendChild(document.createElement('br'));
					
					//to disablerecorder after exporting
					if(lz.embed[args[0]] != null){
						lz.embed[args[0]].callMethod('poodllapi.mp3_disable()');
					}
					
					
					break;
			case 'uploadstarted':
								break;
			case 'actionerror':
								break;
			case 'timeouterror':
								break;
			case 'nosound':
								break;
			case 'conversionerror':
								break;
			case 'beginningconversion':
								break;
			case 'conversioncomplete':
								break;
			case 'timerevent':
				if(args[2]!='0'){
					document.getElementById('displaytime').innerHTML= lz.embed[args[0]].getCanvasAttribute('displaytime');
				}
								break;
		
		
		
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
