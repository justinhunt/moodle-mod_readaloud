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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

use \mod_readaloud\constants;
use \mod_readaloud\utils;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$retake = optional_param('retake', 0, PARAM_INT); // course_module ID, or
$n = optional_param('n', 0, PARAM_INT);  // readaloud instance ID - it should be named as the first character of the module
$debug = optional_param('debug', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('readaloud', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('readaloud', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $moduleinstance = $DB->get_record('readaloud', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('readaloud', $moduleinstance->id, $course->id, false, MUST_EXIST);
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
$mode = "view";

/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

//Get an admin settings
$config = get_config(constants::M_COMPONENT);

//Get our renderers
$renderer = $PAGE->get_renderer('mod_readaloud');
$gradenowrenderer = $PAGE->get_renderer(constants::M_COMPONENT, 'gradenow');
$modelaudiorenderer = $PAGE->get_renderer(constants::M_COMPONENT, 'modelaudio');

//if we are in review mode, lets review
$attempts = $DB->get_records(constants::M_USERTABLE, array('userid' => $USER->id, 'readaloudid' => $moduleinstance->id), 'id DESC');
$ai_evals = \mod_readaloud\utils::get_aieval_byuser($moduleinstance->id, $USER->id);

//can attempt ?
$canattempt = true;
$canpreview = has_capability('mod/readaloud:preview', $modulecontext);
if (!$canpreview && $moduleinstance->maxattempts > 0) {
    $attempts = $DB->get_records(constants::M_USERTABLE,
            array('userid' => $USER->id, constants::M_MODNAME . 'id' => $moduleinstance->id), 'timecreated DESC');
    if ($attempts && count($attempts) >= $moduleinstance->maxattempts) {
        $canattempt = false;
    }
}

//debug mode is for teachers only
if (!$canpreview) {
    $debug = false;
}

//reset our retake flag if we cant reattempt
if (!$canattempt) {
    $retake = 0;
}

//init our token variable
$token=false;

//display the most recent previous attempt if we have one
if ($attempts && $retake == 0) {
    //if we are teacher we see tabs. If student we just see the quiz
    if (has_capability('mod/readaloud:preview', $modulecontext)) {
        echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('view', constants::M_COMPONENT));
    } else {
        echo $renderer->notabsheader();
    }

    //show activity title
    echo $renderer->show_title($moduleinstance->name);


    //show an attempt summary if we have more than one attempt
    if(count($attempts)>1) {
        $attemptsummary = utils::fetch_attempt_summary($moduleinstance);
        echo $renderer->show_attempt_summary($attemptsummary);
        $chartdata = utils::fetch_attempt_chartdata($moduleinstance);
        echo $renderer->show_progress_chart($chartdata);
    }

    //show feedback summary
    echo $renderer->show_feedback_postattempt($moduleinstance);

    //we show the recorder if we can, but if the token or API creds are invalid we report that
    if(empty($config->apiuser) || empty($config->apisecret)){
        $message = get_string('nocredentials',constants::M_COMPONENT,
                $CFG->wwwroot . constants::M_PLUGINSETTINGS);
        echo $renderer->show_problembox($message);
    }else {
        //fetch token
        $token = utils::fetch_token($config->apiuser, $config->apisecret);

        //check token authenticated and no errors in it
        $errormessage = utils::fetch_token_error($token);
        if(!empty($errormessage)){
            echo $renderer->show_problembox($errormessage);
        }
    }


    $latestattempt = array_shift($attempts);

    if (\mod_readaloud\utils::can_transcribe($moduleinstance)) {
        $latest_aigrade = new \mod_readaloud\aigrade($latestattempt->id, $modulecontext->id);
    } else {
        $latest_aigrade = false;
    }

    $readonly = true;
    $have_humaneval = $latestattempt->sessiontime != null;
    $have_aieval = $latest_aigrade && $latest_aigrade->has_transcripts();

    //Are we showing spaces between characters/words. JP/Chinese etc may not
    $collapsespaces=true;

    if ($have_humaneval || $have_aieval) {
        //we useed to distingush between humanpostattempt and machinepostattempt but we simplified it,
        // /and just use the human value for all
        switch ($moduleinstance->humanpostattempt) {
            case constants::POSTATTEMPT_NONE:
                echo $renderer->show_passage_postattempt($moduleinstance,$collapsespaces);
                echo $renderer->fetch_clicktohear_amd($moduleinstance,$token);
                echo $renderer->render_hiddenaudioplayer();
                break;
            case constants::POSTATTEMPT_EVAL:
                if ($have_humaneval) {
                    echo $renderer->show_humanevaluated_message();
                    $force_aidata = false;
                } else {
                    echo $renderer->show_machineevaluated_message();
                    $force_aidata = true;
                }
                $gradenow = new \mod_readaloud\gradenow($latestattempt->id, $modulecontext->id);
                $reviewmode = constants::REVIEWMODE_SCORESONLY;

                echo $gradenow->prepare_javascript($reviewmode, $force_aidata, $readonly);
                echo $renderer->fetch_clicktohear_amd($moduleinstance,$token);
                echo $renderer->render_hiddenaudioplayer();
                echo $gradenowrenderer->render_userreview($gradenow,$collapsespaces);

                break;

            case constants::POSTATTEMPT_EVALERRORS:
                if ($have_humaneval) {
                    echo $renderer->show_humanevaluated_message();
                    $reviewmode = constants::REVIEWMODE_HUMAN;
                    $force_aidata = false;
                } else {
                    echo $renderer->show_machineevaluated_message();
                    $reviewmode = constants::REVIEWMODE_MACHINE;
                    $force_aidata = true;
                }
                $gradenow = new \mod_readaloud\gradenow($latestattempt->id, $modulecontext->id);
                echo $gradenow->prepare_javascript($reviewmode, $force_aidata, $readonly);
                echo $renderer->fetch_clicktohear_amd($moduleinstance,$token);
                echo $renderer->render_hiddenaudioplayer();
                echo $gradenowrenderer->render_userreview($gradenow,$collapsespaces);
                break;
        }
    } else {
        echo $renderer->show_ungradedyet();
        echo $renderer->fetch_clicktohear_amd($moduleinstance,$token);
        echo $renderer->render_hiddenaudioplayer();
        echo $renderer->show_passage_postattempt($moduleinstance,$collapsespaces);
    }

    //show  button or a label depending on of can retake
    if ($canattempt) {
        echo $renderer->reattemptbutton($moduleinstance);
    } else {
        echo $renderer->exceededattempts($moduleinstance);
    }
    echo $renderer->footer();
    return;
}

//From here we actually display the page.
//this is core renderer stuff

//if we are teacher we see tabs. If student we just see the quiz
if (has_capability('mod/readaloud:preview', $modulecontext)) {
    echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('view', constants::M_COMPONENT));
} else {
    echo $renderer->notabsheader();
}

//show all the main parts. Many will be hidden and displayed by JS
echo $renderer->show_title($moduleinstance->name);
echo $renderer->show_welcome_menu(get_string('welcomemenu',constants::M_COMPONENT));

//get our token
if(!$token){
    //we show the recorder if we can, but if the token or API creds are invalid we report that
    if(empty($config->apiuser) || empty($config->apisecret)){
        $message = get_string('nocredentials',constants::M_COMPONENT,
                $CFG->wwwroot . constants::M_PLUGINSETTINGS);
        echo $renderer->show_problembox($message);
        // Finish the page
        echo $renderer->footer();
        return;
    }else {
        //fetch token
        $token = utils::fetch_token($config->apiuser, $config->apisecret);

        //check token authenticated and no errors in it
        $errormessage = utils::fetch_token_error($token);
        if(!empty($errormessage)){
            echo $renderer->show_problembox($errormessage);
            // Finish the page
            echo $renderer->footer();
            return;
        }
    }
}



echo $renderer->show_welcome_activity($moduleinstance->welcome);

echo $renderer->show_feedback($moduleinstance);
echo $renderer->show_error($moduleinstance, $cm);

//show menu buttons
echo $renderer->show_menubuttons($moduleinstance);

//Show model audio player
$visible=false;
echo $modelaudiorenderer->render_modelaudio_player($moduleinstance, $token, $visible);

//for Japanese (and later other languages we collapse spaces)
$collapsespaces=$moduleinstance->ttslanguage==constants::M_LANG_JAJP;

//changing to marked up passage, so we can handle events better
//echo $renderer->show_passage($moduleinstance, $cm);

echo $gradenowrenderer->render_passage($moduleinstance->passage,constants::M_PASSAGE_CONTAINER, $collapsespaces);

//lets fetch recorder
echo $renderer->show_recorder($moduleinstance, $token, $debug);

echo $renderer->show_progress($moduleinstance, $cm);
echo $renderer->show_wheretonext($moduleinstance);

//the module AMD code
//get aws info
/*
$cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
$tokenobject = $cache->get('recentpoodlltoken');
$accessid = $tokenobject->awsaccessid;
$accesssecret= $tokenobject->awsaccesssecret;
*/
echo $renderer->fetch_activity_amd($cm, $moduleinstance,$token);

//return to menu button
echo "<hr/>";
echo $renderer->show_returntomenu_button();

// Finish the page
echo $renderer->footer();
