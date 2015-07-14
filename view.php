<?php

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
 * Prints a particular instance of readaloud
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/audio/audiohelper.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$retake = optional_param('retake', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // readaloud instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('readaloud', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record('readaloud', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $moduleinstance  = $DB->get_record('readaloud', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('readaloud', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

$PAGE->set_url('/mod/readaloud/view.php', array('id' => $cm->id));
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

//Diverge logging logic at Moodle 2.7
if($CFG->version<2014051200){
	add_to_log($course->id, 'readaloud', 'view', "view.php?id={$cm->id}", $moduleinstance->name, $cm->id);
}else{
	// Trigger module viewed event.
	$event = \mod_readaloud\event\course_module_viewed::create(array(
	   'objectid' => $moduleinstance->id,
	   'context' => $modulecontext
	));
	$event->add_record_snapshot('course_modules', $cm);
	$event->add_record_snapshot('course', $course);
	$event->add_record_snapshot('readaloud', $moduleinstance);
	$event->trigger();
} 

//if we got this far, we can consider the activity "viewed"
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

//are we a teacher or a student?
$mode= "view";

/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
//require jquery
$PAGE->requires->jquery();
//require bootstrap
//can skip this ... if bootstrap theme??
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/readaloud/bootstrap-3.3.4-dist/css/bootstrap.min.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/readaloud/font-awesome/css/font-awesome.min.css'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/readaloud/bootstrap-3.3.4-dist/js/bootstrap.min.js'));


//load swf loader
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/readaloud/audio/embed-compressed.js'));

//Get an admin settings 
$config = get_config(MOD_READALOUD_FRANKY);

//Get our renderers
$renderer = $PAGE->get_renderer('mod_readaloud');
$gradenowrenderer = $PAGE->get_renderer(MOD_READALOUD_FRANKY,'gradenow');

//if we are in review mode, lets review
$attempts = $DB->get_records(MOD_READALOUD_USERTABLE,array('userid'=>$USER->id,'readaloudid'=>$moduleinstance->id),'id DESC');

//can attempt ?
$canattempt = has_capability('mod/readaloud:preview',$modulecontext);
if(!$canattempt && $moduleinstance->maxattempts > 0){
	$canattempt=true;
	$attempts =  $DB->get_records(MOD_READALOUD_USERTABLE,array('userid'=>$USER->id, MOD_READALOUD_MODNAME.'id'=>$moduleinstance->id));
	if($attempts && count($attempts)<$moduleinstance->maxattempts){
		$canattempt=false;
	}
}

//reset our retake flag if we cant reatempt
if(!$canattempt){$retake=0;}

//display previous attempts if we have them
if($attempts && $retake==0){
		//if we are teacher we see tabs. If student we just see the quiz
		if(has_capability('mod/readaloud:preview',$modulecontext)){
			echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('view', MOD_READALOUD_LANG));
		}else{
			echo $renderer->notabsheader();
		}


		$latestattempt = array_shift($attempts);
		
		// show results if graded
		if($latestattempt->sessiontime==null){
			echo $renderer->show_welcome($moduleinstance->welcome,$moduleinstance->name);
			echo $renderer->show_ungradedyet();
		}else{	
			$gradenow = new \mod_readaloud\gradenow($latestattempt->id,$modulecontext->id);
			$reviewmode =true;
			$gradenow->prepare_javascript($reviewmode);
			echo $gradenowrenderer->render_hiddenaudioplayer();
			echo $gradenowrenderer->render_gradenow($gradenow);
		}
		
		//show  button or a label depending on of can retake
		if($canattempt){
			echo $renderer->reattemptbutton($moduleinstance);
		}else{
			echo get_string("exceededattempts",MOD_READALOUD_LANG,$moduleinstance->maxattempts);
		}
		echo $renderer->footer();
		return;
}


//if not in review mode, lets start up the test mode
//get our module javascript all ready to go
$jsmodule = array(
	'name'     => 'mod_readaloud',
	'fullpath' => '/mod/readaloud/module.js',
	'requires' => array()
);
//here we set up any info we need to pass into javascript
$opts =Array();
//this inits the M.mod_readaloud thingy, after the page has loaded.
$PAGE->requires->js_init_call('M.mod_readaloud.helper.init', array($opts),false,$jsmodule);


//here we set up any info we need to pass into javascript
$ah = new audiohelper();
$recopts =Array();
$recopts['recorderid'] = MOD_READALOUD_RECORDERID;
$recopts['startbutton'] = MOD_READALOUD_START_BUTTON;
$recopts['stopbutton'] = MOD_READALOUD_STOP_BUTTON;
$recopts['recordbutton'] = MOD_READALOUD_RECORD_BUTTON;
$recopts['hider']=MOD_READALOUD_HIDER;
$recopts['passagecontainer'] = MOD_READALOUD_PASSAGE_CONTAINER;
$recopts['recordingcontainer'] = MOD_READALOUD_RECORDING_CONTAINER;
$recopts['recordercontainer'] = MOD_READALOUD_RECORDER_CONTAINER;
$recopts['dummyrecorder'] = MOD_READALOUD_DUMMY_RECORDER;
$recopts['instructionscontainer'] = MOD_READALOUD_INSTRUCTIONS_CONTAINER;
$recopts['recinstructionscontainerright'] = MOD_READALOUD_RECORDER_INSTRUCTIONS_RIGHT;
$recopts['recinstructionscontainerleft'] = MOD_READALOUD_RECORDER_INSTRUCTIONS_LEFT;
$recopts['recordbuttoncontainer'] =MOD_READALOUD_RECORD_BUTTON_CONTAINER;
$recopts['startbuttoncontainer'] =MOD_READALOUD_START_BUTTON_CONTAINER;
$recopts['progresscontainer'] = MOD_READALOUD_PROGRESS_CONTAINER;
$recopts['feedbackcontainer'] = MOD_READALOUD_FEEDBACK_CONTAINER;
$recopts['errorcontainer'] = MOD_READALOUD_ERROR_CONTAINER;
$p1 = sesskey();
$p2 = $cm->id;
$recopts['recorderjson'] = $ah->fetchRecorderJSON("","M.mod_readaloud.audiohelper.poodllcallback",
						$p1,$p2,"p3","p4",MOD_READALOUD_RECORDERID,"false", "volume",$moduleinstance->timelimit);


//this inits the M.mod_readaloud thingy, after the page has loaded.
$PAGE->requires->js_init_call('M.mod_readaloud.audiohelper.init', array($recopts),false,$jsmodule);
$PAGE->requires->strings_for_js(array('gotnosound','recordnameschool','done','beginreading'),MOD_READALOUD_LANG);

//From here we actually display the page.
//this is core renderer stuff


//if we are teacher we see tabs. If student we just see the quiz
if(has_capability('mod/readaloud:preview',$modulecontext)){
	echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('view', MOD_READALOUD_LANG));
}else{
	echo $renderer->notabsheader();
}

//show all the main parts. Many will be hidden and displayed by JS
echo $renderer->show_welcome($moduleinstance->welcome,$moduleinstance->name);
echo $renderer->show_button_recorder($moduleinstance,$cm);
echo $renderer->show_passage($moduleinstance,$cm);
echo $renderer->show_progress($moduleinstance,$cm);
echo $renderer->show_feedback($moduleinstance,$cm,$moduleinstance->name);
echo $renderer->show_error($moduleinstance,$cm);

// Finish the page
echo $renderer->footer();
