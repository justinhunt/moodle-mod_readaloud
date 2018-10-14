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
use \mod_readaloud\constants;




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

// Trigger module viewed event.
$event = \mod_readaloud\event\course_module_viewed::create(array(
   'objectid' => $moduleinstance->id,
   'context' => $modulecontext
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('readaloud', $moduleinstance);
$event->trigger();


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



//Get an admin settings 
$config = get_config(constants::MOD_READALOUD_FRANKY);

//Get our renderers
$renderer = $PAGE->get_renderer('mod_readaloud');
$gradenowrenderer = $PAGE->get_renderer(constants::MOD_READALOUD_FRANKY,'gradenow');

//if we are in review mode, lets review
$attempts = $DB->get_records(constants::MOD_READALOUD_USERTABLE,array('userid'=>$USER->id,'readaloudid'=>$moduleinstance->id),'id DESC');
$ai_evals = \mod_readaloud\utils::get_aieval_byuser($moduleinstance->id,$USER->id);

//can attempt ?
$canattempt = true;
$canpreview = has_capability('mod/readaloud:preview',$modulecontext);
if(!$canpreview && $moduleinstance->maxattempts > 0){
	$attempts =  $DB->get_records(constants::MOD_READALOUD_USERTABLE,array('userid'=>$USER->id, constants::MOD_READALOUD_MODNAME.'id'=>$moduleinstance->id));
	if($attempts && count($attempts)>=$moduleinstance->maxattempts){
		$canattempt=false;
	}
}

//reset our retake flag if we cant reatempt
if(!$canattempt){$retake=0;}

//display the most recent previous attempt if we have one
if($attempts && $retake==0){
    //if we are teacher we see tabs. If student we just see the quiz
    if(has_capability('mod/readaloud:preview',$modulecontext)){
        echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('view', constants::MOD_READALOUD_LANG));
    }else{
        echo $renderer->notabsheader();
    }
    $latestattempt = array_shift($attempts);

    //========================================
    if(\mod_readaloud\utils::can_transcribe($moduleinstance)) {
        $latest_aigrade = new \mod_readaloud\aigrade($latestattempt->id, $modulecontext->id);
    }else{
        $latest_aigrade =false;
    }

    $have_humaneval = $latestattempt->sessiontime!=null;
    $have_aieval = $latest_aigrade && $latest_aigrade->has_transcripts();

    if( $have_humaneval || $have_aieval){
        //we useed to distingush between humanpostattempt and machinepostattempt but we simplified it,
        // /and just use the human value for all
        switch($moduleinstance->humanpostattempt){
            case constants::POSTATTEMPT_NONE:
                echo $renderer->show_feedback_postattempt($moduleinstance,$moduleinstance->name);
                echo $renderer->show_passage_postattempt($moduleinstance);
                break;
            case constants::POSTATTEMPT_EVAL:
                echo $renderer->show_feedback_postattempt($moduleinstance,$moduleinstance->name);
                if( $have_humaneval) {
                    echo $renderer->show_humanevaluated_message();
                    $force_aidata=false;
                }else{
                    echo $renderer->show_machineevaluated_message();
                    $force_aidata=true;
                }
                $gradenow = new \mod_readaloud\gradenow($latestattempt->id,$modulecontext->id);
                $reviewmode =constants::REVIEWMODE_SCORESONLY;
                echo $gradenow->prepare_javascript($reviewmode,$force_aidata);
                echo $gradenowrenderer->render_attempt_scoresheader($gradenow);
                echo $renderer->show_passage_postattempt($moduleinstance);

                break;

            case constants::POSTATTEMPT_EVALERRORS:
                echo $renderer->show_feedback_postattempt($moduleinstance,$moduleinstance->name);
                if( $have_humaneval) {
                    echo $renderer->show_humanevaluated_message();
                    $reviewmode = constants::REVIEWMODE_HUMAN;
                    $force_aidata=false;
                }else{
                    echo $renderer->show_machineevaluated_message();
                    $reviewmode =constants::REVIEWMODE_MACHINE;
                    $force_aidata=true;
                }
                $gradenow = new \mod_readaloud\gradenow($latestattempt->id,$modulecontext->id);
                echo $gradenow->prepare_javascript($reviewmode,$force_aidata);
                echo $gradenowrenderer->render_hiddenaudioplayer();
                echo $gradenowrenderer->render_userreview($gradenow);
                break;
        }
    }else{
        echo $renderer->show_feedback_postattempt($moduleinstance,$moduleinstance->name);
        echo $renderer->show_ungradedyet();
        echo $renderer->show_passage_postattempt($moduleinstance);
    }

    //show  button or a label depending on of can retake
    if($canattempt){
        echo $renderer->reattemptbutton($moduleinstance);
    }else{
        echo $renderer->exceededattempts($moduleinstance);
    }
    echo $renderer->footer();
    return;
}


//From here we actually display the page.
//this is core renderer stuff

//if we are teacher we see tabs. If student we just see the quiz
if(has_capability('mod/readaloud:preview',$modulecontext)){
	echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('view', constants::MOD_READALOUD_LANG));
}else{
	echo $renderer->notabsheader();
}

//fetch token
$token = \mod_readaloud\utils::fetch_token($config->apiuser,$config->apisecret);


//show all the main parts. Many will be hidden and displayed by JS
echo $renderer->show_welcome($moduleinstance->welcome,$moduleinstance->name);
echo $renderer->show_feedback($moduleinstance,$moduleinstance->name);
echo $renderer->show_error($moduleinstance,$cm);
echo $renderer->show_passage($moduleinstance,$cm);
echo $renderer->show_recorder($moduleinstance,$token);
echo $renderer->show_progress($moduleinstance,$cm);
echo $renderer->show_wheretonext($moduleinstance);

//the module AMD code
echo $renderer->fetch_activity_amd($cm, $moduleinstance);

// Finish the page
echo $renderer->footer();
