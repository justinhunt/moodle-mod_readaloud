<?php
/**
* Functions to use with PoodLL Audio Recording SDK
* 
* @author Justin Hunt (@link http://www.poodll.com)
* @copyright 2013 onwards Justin Hunt http://www.poodll.com
* @license JustinsPlainEnglishLicense ( http://www.poodll.com/justinsplainenglishlicense.txt )
*
*/
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
define('FANCYBUTTON',true);

	
class audiohelper {



	//For receiving an uploaded a file direct from an HTML5 or SWF widget
	// $p2=cmid | $p1=sesskey
	function uploadfile($filedata,  $fileextension, $actionid,$sesskey, $cmid, $p3,$p4){
		global $CFG,$USER;

		//setup our return object
		$return=$this->fetchReturnArray(true);
		
		//make sure nobody passed in a bogey file extension
		//we are not really expecting JPG / PNG etc, but they could be handled
		//if necessary
		switch($fileextension){
			case "mp3": 
			case "flv":
			case "jpg":
			case "png":
			case "xml":
			case "mov":
			case "wav":
			case "mp4":
			case "3gpp":
				break;
			default: $fileextension="";
		}
	  
		//make filename and set it
		$filenamebase = "upfile_" . rand(100,32767) . rand(100,32767) . "." ;
		$filename = $filenamebase . $fileextension;

			
		//check there is no metadata prefixed to the base 64. From the SWF recorder there is none, from HTML5/JS there is.
		//if so it will look  a bit like this: data:image/png;base64,iVBORw0K
		//we remove it, there must be a better way of course ...
		//$metapos = strPos($filedata,";base64,");
		$metapos = strPos($filedata,",");
		if($metapos >10 && $metapos <30){
			$filedata = substr($filedata,$metapos+1);
		
		}
		//actually make the file and save it
		$xfiledata = base64_decode($filedata);
		$ret = $this->save_to_moodle($xfiledata, $filename,$cmid);
		
		//if successful, return filename
		if($ret){
			//return our recorded filename to the browser
			array_push($return['messages'],$filename );
		
		}else{
			$return['success']=false;
			array_push($return['messages'],"unable to save file with filename:" . $filename );
		}
			
		//we process the result for return to browser
		$xml_output= $this->prepareXMLReturn($return, $actionid);	
		
		//we return to widget/client the result of our file operation
		return $xml_output;
		
	}
	
	
	function save_to_moodle($filedata,$filename,$cmid){
		global $USER,$DB,$CFG;
		$cm         = get_coursemodule_from_id('readaloud', $cmid, 0, false, MUST_EXIST);
		$readaloud  = $DB->get_record('readaloud', array('id' => $cm->instance), '*', MUST_EXIST);
		$context = context_module::instance($cm->id);
		
		//first create an attempt and then we will come back and save the file
		$newattempt = new stdClass();
		$newattempt->courseid=$readaloud->course;
		$newattempt->readaloudid=$readaloud->id;
		$newattempt->userid=$USER->id;
		$newattempt->status=0;
		$newattempt->filename=$filename;
		$newattempt->sessionscore=0;
		$newattempt->wpm=0;
		$newattempt->timecreated=time();
		$newattempt->timemodified=time();
		$attemptid = $DB->insert_record(MOD_READALOUD_USERTABLE,$newattempt);
		if(!$attemptid){
			return false;
		}
		
		//init our fs object
		$fs = get_file_storage();
		//add userid to filepath
		$filepath='/';
		$component = MOD_READALOUD_FRANKY;
		$filearea = MOD_READALOUD_FILEAREA_SUBMISSIONS;
		$itemid = $attemptid;

			
		
		//make our filerecord
		 $record = new stdClass();
		 $record->filename = $filename;
		 $record->filearea = $filearea;
		$record->component = $component;
		$record->filepath = $filepath;
		$record->itemid   = $itemid;
		$record->license  = $CFG->sitedefaultlicense;
		$record->author   = 'Moodle User';
		$record->contextid = $context->id;
		$record->userid    = $USER->id;
		$record->source    = '';
		$stored_file = $fs->create_file_from_string($record, $filedata);

		
		
		//if unsuccessful return error
		if(!$stored_file){
			return false;
		}
		
		return true;
	}

	function fetchRecorder($updatecontrol="",$callbackjs="",$p1="",$p2="",$p3="",$p4="",$recorderid="", $autosubmit="true", $skin="noskin"){
		//set up our browser object
		$browser = new Browser(); //get our browser object for detecting Os and browser types
		

		
		//ismobile accepts 'always' (always HTML5) never (never HTML5) mobile (if mobile HTML5) or webkit (if mobile or webkit browser HTML5)
		if($this->isMobile('mobile',$browser)){
		
			if(!$this->canDoUpload($browser)){
				$ret ="<div class='os_version_warning'>
				<p>Your OS Version is too low</p>
				<p>Android requires version 4 or greater.</p>
				<p>iOS requires version 6 or greater.</p>
				</div>";
			}else{		
				$mediatype= $this->canSpecAudio($browser); 
				switch ($mediatype){
					case 'yes': $mediatype = 'audio';
								break;
					case 'no': $mediatype = 'video';
								break;
					default: $mediatype = ' ';
				}
				$fancystyle = $this->doFancyStyle($browser);
				$ret = $this->fetchHTML5Recorder($updatecontrol,$callbackjs, $p1,$p2,$p3,$p4,$recorderid,$autosubmit,$mediatype, $fancystyle);
			}
		}else{
			$ret = $this->fetchMP3Recorder($updatecontrol,$callbackjs, $p1,$p2,$p3,$p4,$recorderid,$autosubmit,$skin);
		}

		return $ret; 
	}
	
	function fetchRecorderJSON($updatecontrol="",$callbackjs="",$p1="",$p2="",$p3="",$p4="",$recorderid="", $autosubmit="true", $skin="noskin",$timelimit=0){
		$ret = $this->fetchMP3Recorder($updatecontrol,$callbackjs, $p1,$p2,$p3,$p4,$recorderid,$autosubmit,$skin,true,$timelimit);
		return $ret; 
	}


	//Fetch the MP3 Recorder to be included on the page
	function fetchMP3Recorder($updatecontrol="",$callbackjs="",$p1="",$p2="",$p3="",$p4="",$recorderid='',$autosubmit="true", $skinmode="noskin",$json=false,$timelimit=0){

	global $CFG;
	
	//Set the microphone config params
	$micrate = "44";
	$micgain = "50";
	$micsilence = "0";
	$micecho ="yes";
	$micloopback = "no";
	$micdevice = "";//if have a pref device, set it here

	//Set the recorder size (MP3)
	$size="normal";  // "normal" or "small"

	//set time limit in seconds
	$timelimit=$timelimit; //you could try "30" / "60" / "90"


	//determine the size of the widget on the skinmode and player size(if not skinning)
	if($skinmode=="noskin"){
		switch ($size){
			case "small":
				$width="240"; 
				$height="170";
				break;
			case "normal":
			default:
				$width="350"; 
				$height="200";
		}
	//if skinning set the size to minimal
	}else{
			$width="240"; 
			$height="170";
		/*
		$width="215"; 
		$height="130";
		*/
	}

	$posturl= $CFG->wwwroot . "/mod/readaloud/audio/poodllfilelib.php";


	//To send directly ('ajax') or via an html form('post') 
	//if you use post, then you will need to set the audio data control below
	$sendmethod="ajax";
	//the control on the page to store base 64 encoded mp3 data.
	//this should be used when sendmethod is set to post
	$audiodatacontrol = '';

	//The update control is the id of the control
	//The save control is auto added to page if the user has no specified a control
	if ($updatecontrol == ""){
		//$updatecontrol= "poodll_file_" . rand(100,32767) . rand(100,32767);
		$updatecontrol= "poodll_recorded_file";
		$savecontrol = "<input name='" . $updatecontrol ."' type='hidden' value='' id='" . $updatecontrol . "' />";
	}else{
		$savecontrol = "";
	}

	$params = array();
			//quality settings, passed straight through to flash
			$params['rate'] = $micrate;
			$params['gain'] = $micgain;
			$params['prefdevice'] = $micdevice;
			$params['loopback'] = $micloopback;
			$params['echosuppression'] = $micecho;
			$params['silencelevel'] = $micsilence;
			
			//settings for updating the page after recording
			$params['updatecontrol'] = $updatecontrol;
			$params['callbackjs'] = $callbackjs;
			
			//Skin setting
			$params['skinmode'] = $skinmode;
			$params['backcolor'] = "0xffffff";
			
			//whether to submit on "Stop" or to wait for a "save button" click
			$params['autosubmit'] = $autosubmit;
			//Size setting small or normal
			$params['size'] = $size;
			//time limit setting. Only applicable for SWF recorder.
			$params['timelimit'] = $timelimit;
			//send method
			$params['sendmethod'] = $sendmethod;
			//audiodatacontrol
			$params['audiodatacontrol'] = $audiodatacontrol;
			//The recording format, either "mp3" or "wav". If mp3 the rate will be set to 44.
			$params['recformat'] = "mp3";
			//show the settings buttons
			$params['showsettingsbutton'] = "true";
			
			//settings to control where the upload gets posted to, and what info it sends with it
			//e.g p1 p2 could contain a user id and an assignment id .
			$params['posturl'] = $posturl;
			$params['p1'] = $p1;
			$params['p2'] = $p2;
			$params['p3'] = $p3;
			$params['p4'] = $p4;
			
			//set the recorder id
			//generate a (most likely) unique id for the recorder, if one was not passed in
			if($recorderid==''){
				$recorderid = 'lzapp_' . rand(100000, 999999);
			}
			$params['recorderid'] = $recorderid;
			
			if($json){
				$returnString=$this->fetchSWFWidgetJSONParams('PoodllMP3Record.lzx.swf10.swf',
									$params,$width,$height,'#CFCFCF',$recorderid);
			}else{
				//we fetch a configured recorder script suitable for outputting on a page 
				$returnString=  $this->fetchSWFWidgetCode('PoodllMP3Record.lzx.swf10.swf',
									$params,$width,$height,'#CFCFCF',$recorderid);
				
				//if the user did not declare a control to store the filename on the page, we just create such a field					
				$returnString .= $savecontrol;
			}
			
			return $returnString ;

	}

	//fetch the HTML5 "recorder" to be included on the page
	function fetchHTML5Recorder($updatecontrol="",$callbackjs="",$p1="",$p2="",$p3="",$p4="", $recorderid="",$autosubmit="true",$mediatype="audio", $fancystyle=true){
		global $CFG;

		//get our upload file path
		$posturl= $CFG->wwwroot . "/mod/readaloud/audio/poodllfilelib.php";
		
		//get a seed to make sure each fileupload field set has unique ids
		//also create a recorder id if we did not have one
		if($recorderid==""){
			$recorderid = rand(100000, 999999);
		}
		$seed=$recorderid;
		
		//create the ids for our elements we need to output on the page
		//we use the fileselectid as the "id" for our recorder in some parts of the javascript
		$fileselectid = "p_fileselect_" . $seed;
		$progressid = "p_progress_" . $seed;
		$messagesid = "p_messages_" . $seed;

		//depending on our media type, tell the mobile device what kind of file we want
		//currently iOS 6  only does video (even though we only want audio)
		//hopefully later, or for android blackberry etc, we have more options here
		switch($mediatype){
			case "audio":	$mediatype="accept=\"audio/*\"";break;
			case "video":   $mediatype="accept=\"video/*\"";break;
			default: $mediatype="";
		}
		
		//create and output our HTML. 
		if($fancystyle){
			$returnString="
			<div class=\"p_btn_wrapper\">			
				<input class=\"file\" type=\"file\" id=\"$fileselectid\" name=\"poodllfileselect[]\" $mediatype />
				<button type=\"button\" class=\"p_btn\">Record or Choose a File</button>
			</div>";
		}else{
			$returnString="		
				<input class=\"file\" type=\"file\" id=\"$fileselectid\" name=\"poodllfileselect[]\" $mediatype />"
				;
		}
		
		//complete html string with feedback and progress elements.
		$returnString .= "<div id=\"$progressid\" class=\"p_progress\"><p></p></div>
			<div id=\"$messagesid\" class=\"p_messages\"></div>
			";
		
		//create an array of options to passed to javascript
		$opts = array('posturl'=>$posturl,
						'fileselectid'=>$fileselectid, 
						'updatecontrolid'=>$updatecontrol, 
						'progressid'=>$progressid,					
						'messagesid'=>$messagesid,
						'callbackjs'=>$callbackjs,
						'recorderid'=>$recorderid,
						'autosubmit'=>$autosubmit=="true",
						'p1'=>$p1, 
						'p2'=>$p2, 
						'p3'=>$p3,
						'p4'=>$p4);
			
		//set up our javascript call which will do the ajax uploading
		$returnString .= "<script type=\"text/javascript\">loadmobileupload(" . json_encode($opts) . ");</script>";
		
		//return the html to be output
		return $returnString;
	}

	
	//This is use for assembling the html elements + javascript that will be swapped out and replaced with the MP3 recorder
	function fetchSWFWidgetJSONParams($widget,$paramsArray,$width,$height, $bgcolor="#FFFFFF", $recorderid=''){
		global $CFG;
		
		//build the parameter string out of the passed in array
		$params="?";
		foreach ($paramsArray as $key => $value) {
			$params .= '&' . $key . '=' . $value;
		}
		
		//add in any common params
		$params .= '&debug=false&lzproxied=false'; 
		
		//generate a (most likely) unique id for the recorder, if one was not passed in
		if($recorderid==''){
			$recorderid = 'lzapp_' . rand(100000, 999999);
		}
		$paramobj = new stdClass();
		$paramobj->url =$CFG->wwwroot . '/mod/readaloud/audio/' . $widget . $params;
		$paramobj->bgcolor = $bgcolor;
		$paramobj->cancelmousewheel = true;
		$paramobj->allowfullscreen = true;
		$paramobj->width = $width;//might need to make this a string
		$paramobj->height = $height; //might need to make this a string
		$paramobj->id = $recorderid;
		$paramobj->accessible = true;
		$paramobj->appenddivid = MOD_READALOUD_RECORDER_CONTAINER;
		
		$retjson = json_encode($paramobj);
		/*
		$retjson = '{"url": \'' . POODLLURL . '/' . $widget . $params . 
			 '\', "bgcolor": \'' . $bgcolor . '\', "cancelmousewheel": true, "allowfullscreen": true, "width": \'' .$width . '\', "height": \'' . $height . '\', "id": \'' . $recorderid . '\', "accessible": true}';
		*/
			
		return $retjson;

	}
	 

	//This is use for assembling the html elements + javascript that will be swapped out and replaced with the MP3 recorder
	function fetchSWFWidgetCode($widget,$paramsArray,$width,$height, $bgcolor="#FFFFFF", $recorderid=''){
		global $CFG;
		
		//build the parameter string out of the passed in array
		$params="?";
		foreach ($paramsArray as $key => $value) {
			$params .= '&' . $key . '=' . $value;
		}
		
		//add in any common params
		$params .= '&debug=false&lzproxied=false'; 
		
		//generate a (most likely) unique id for the recorder, if one was not passed in
		if($recorderid==''){
			$recorderid = 'lzapp_' . rand(100000, 999999);
		}

		
		$retcode = "
			<table><tr><td>
			<script type=\'text/javascript\'>
				lzOptions = { ServerRoot: \'\'};
			</script> 
			<script type=\"text/javascript\">
	" . '	lz.embed.swf({url: \'' . $CFG->wwwroot . '/mod/readaloud/audio/' . $widget . $params . 
			 '\', bgcolor: \'' . $bgcolor . '\', cancelmousewheel: true, allowfullscreen: true, width: \'' .$width . '\', height: \'' . $height . '\', id: \'' . $recorderid . '\', accessible: true});	
			
	' . "
			</script>
			<noscript>
				Please enable JavaScript in order to use this application.
			</noscript>
			</td></tr>
			</table>";
			
			return $retcode;

	}

	//We check if the OS version is too old here,
	//Android 4+ iOS6+
	//(2013/09/26)
	function canDoUpload($browser){
		switch($browser->getPlatform()){
		
			case Browser::PLATFORM_ANDROID: 
				$ver = $browser->getAndroidMajorVersion() ;
				//if parsing failed, just assume they can upload
					if(!$ver) {
						return true;
					}elseif($ver>3){
						return true;
					}else{
						return false;
					}
					break;
			
			case Browser::PLATFORM_IPHONE:
			case Browser::PLATFORM_IPOD:
			case Browser::PLATFORM_IPAD:
				$ver = $browser->getIOSMajorVersion() ;
				//if parsing failed, just assume they can upload
					if(!$ver) {
						return true;
					}elseif($ver>5){
						return true;
					}else{
						return false;
					}
					break;
			default:
				return true;
			}//end of switch
			
					
		}//end of function

	//Here we try to detect if this supports uploading audio files spec
	//iOS doesn't but android can record from mic. Apple and Windows can just filter by audio when browsing
	//(2013/03/05)Firefox on android, doesn't use sound recorder currently. 
	//(2013/03/05)Chrome on android gives wrong useragent(ipad/safari!)
	function canSpecAudio($browser){

		switch($browser->getPlatform()){

				case Browser::PLATFORM_APPLE:
				case Browser::PLATFORM_WINDOWS:
					return 'yes';
					break;
					
				case Browser::PLATFORM_IPAD:
					return 'no';
					break;
			
				case Browser::PLATFORM_IPOD:
				case Browser::PLATFORM_IPHONE:
					return 'no';
					break;
				
				case Browser::PLATFORM_ANDROID:
					if($browser->getBrowser() == Browser::BROWSER_FIREFOX){
						return 'maybe';
					}else if($browser->isNexus7()){
						return 'no';
					}else{
						return 'yes';
					}
					break;
					
				default:
					return 'maybe';
		}//end of switch
	}

	//If we wish to show a styled upload button, here we return true
	//on Firefox on Android doesn't support it currently, so we hard code that to false 
	//(2013/03/05)
	function doFancyStyle($browser){

		if($browser->getPlatform() == Browser::PLATFORM_ANDROID &&
			$browser->getBrowser() == Browser::BROWSER_FIREFOX){
					return false;
		}else if($browser->getPlatform() == Browser::PLATFORM_MICROSOFT_SURFACE){
			return false;
		}else{
					return FANCYBUTTON;
		}
	}

	//Here we try to detect if this is a mobile device or not
	//this is used to determine whther to return a JS or SWF widget
	function isMobile($profile,$browser){
		global $CFG;
		
		if ($profile=='never'){return false;}
		if ($profile=='always'){return true;}
		
		
		//check by browser
		 switch($browser->getBrowser()){
			case Browser::BROWSER_IPAD:
			case Browser::BROWSER_IPOD:
			case Browser::BROWSER_IPHONE:
			case Browser::BROWSER_ANDROID:
			return true;
		}

		//check by platform
		switch($browser->getPlatform()){

				case Browser::PLATFORM_IPHONE:
				case Browser::PLATFORM_IPOD:
				case Browser::PLATFORM_IPAD:
				case Browser::PLATFORM_BLACKBERRY:
				case Browser::PLATFORM_NOKIA:
				case Browser::PLATFORM_ANDROID:
				case Browser::PLATFORM_WINDOWS_CE:
				//if MS Surface, consider to be desktop
				//case Browser::PLATFORM_MICROSOFT_SURFACE:
				return true;
		}//end of switch

		
		//if we are still not mobile, but webkit browwsers count, check that too
		if ($profile=='webkit'){
			 switch($browser->getBrowser()){
				case Browser::BROWSER_SAFARI:
				case Browser::BROWSER_ICAB:
				case Browser::BROWSER_OMNIWEB:
				case Browser::BROWSER_NOKIA_S60:
				case Browser::BROWSER_CHROME:
				return true;
			}		
		}
		return false;
	}


	//this turns our results array into an xml string for returning to browser
	function prepareXMLReturn($resultArray, $requestid){
		//set up xml to return	
		$xml_output = "<result requestid='" . $requestid . "'>";

			if($resultArray['success']){
				$xml_output .= 'success';
				foreach ($resultArray['messages'] as $message) {
					$xml_output .= '<error>' . $message . '</error>';
				}
			}else{
				$xml_output .= 'failure';
				foreach ($resultArray['messages'] as $message) {
					$xml_output .= '<error>' . $message . '</error>';
				}
			}	
		
		//close off xml to return	
		$xml_output .= "</result>";	
		return $xml_output;
	}



	//this initialises and returns a results array
	function fetchReturnArray($initsuccess=false){
		//new filearray
		$return = array();
		$return['messages'] = array();
		$return['success'] = $initsuccess;
		return $return;
	}

}//end of class