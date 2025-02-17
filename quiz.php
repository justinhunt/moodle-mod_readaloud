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
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
use mod_readaloud\constants;
use mod_readaloud\utils;
use mod_readaloud\mobile_auth;


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$retake = optional_param('retake', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // readaloud instance ID - it should be named as the first character of the module
$embed = optional_param('embed', 0, PARAM_INT); // course_module ID, or

// Allow login through an authentication token.
$userid = optional_param('user_id', null, PARAM_ALPHANUMEXT);
$secret  = optional_param('secret', null, PARAM_RAW);
// Formerly had !isloggedin() check, but we want tologin afresh on each embedded access.
if (!empty($userid) && !empty($secret) ) {
    if (mobile_auth::has_valid_token($userid, $secret)) {
        $user = get_complete_user_data('id', $userid);
        complete_user_login($user);
        $embed = 2;
    }
}

if ($id) {
    $cm         = get_coursemodule_from_id('readaloud', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record('readaloud', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $moduleinstance  = $DB->get_record('readaloud', ['id' => $n], '*', MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('readaloud', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

$PAGE->set_url('/mod/readaloud/quiz.php', ['id' => $cm->id, 'retake' => $retake, 'embed' => $embed]);
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

// Trigger module viewed event.
$event = \mod_readaloud\event\course_module_viewed::create([
   'objectid' => $moduleinstance->id,
   'context' => $modulecontext,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('readaloud', $moduleinstance);
$event->trigger();


// If we got this far, we can consider the activity "viewed".
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// log usage to CloudPoodll
// utils::stage_remote_process_job($moduleinstance->ttslanguage, $cm->id);

// Are we a teacher or a student?
$mode = "view";

// Set up the page header.
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Get an admin settings.
$config = get_config(constants::M_COMPONENT);

// We want readaloud to embed nicely, or display according to layout settings.
if ($moduleinstance->foriframe == 1  || $moduleinstance->pagelayout == 'embedded' || $embed == 1) {
    $PAGE->set_pagelayout('embedded');
} else if ($config->enablesetuptab || $moduleinstance->pagelayout == 'popup' || $embed == 2) {
    $PAGE->set_pagelayout('popup');
    $PAGE->add_body_class('poodll-readaloud-embed');
} else {
    if (has_capability('mod/' . constants::M_MODNAME . ':' . 'manage', $modulecontext)) {
        $PAGE->set_pagelayout('incourse');
    } else {
        $PAGE->set_pagelayout($moduleinstance->pagelayout);
    }
}

// Get our renderers.
$renderer = $PAGE->get_renderer('mod_readaloud');
$rsquestionrenderer = $PAGE->get_renderer(constants::M_COMPONENT, 'rsquestion');

// Get attempts.
$attempts = $DB->get_records(constants::M_USERTABLE, ['readaloudid' => $moduleinstance->id, 'userid' => $USER->id], 'timecreated DESC');


// Can make a new attempt?
$canattempt = true;
$canpreview = has_capability('mod/readaloud:preview', $modulecontext);
if (!$canpreview && $moduleinstance->maxattempts > 0) {
    if ($attempts && count($attempts) >= $moduleinstance->maxattempts) {
        $canattempt = false;
    }
}

// Create a new attempt or just fall through to no-items or finished modes.
if (!$attempts || ($canattempt && $retake == 1)) {
    $latestattempt = reset($attempts);
     // $latestattempt = utils::create_new_attempt($moduleinstance->course, $moduleinstance->id);
} else {
    $latestattempt = reset($attempts);
}

// This library is licensed with the hippocratic license (https://github.com/EthicalSource/hippocratic-license/)
// which is not GPL3 compat. so cant be distributed with plugin. Hence we load it from CDN
//if($config->animations == constants::M_ANIM_FANCY) {
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css'));
//}

// If we need a non standard font we can do that from here.
if (!empty($moduleinstance->lessonfont)) {
    if (!in_array($moduleinstance->lessonfont, constants::M_STANDARD_FONTS)) {
        $PAGE->requires->css(new moodle_url('https://fonts.googleapis.com/css?family=' . $moduleinstance->lessonfont));
    }
}

// From here we actually display the page.
// If we are teacher we see tabs. If student we just see the quiz.
// In mobile no tabs are shown.
echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('view', constants::M_COMPONENT));

$quizhelper = new \mod_readaloud\quizhelper($cm);
$itemcount = $quizhelper->fetch_item_count();

// Show open close dates.
// TODO: Need to understand if this is specific to quiz or if our existing version of this (now in template) is same.
// $hasopenclosedates = $moduleinstance->viewend > 0 || $moduleinstance->viewstart > 0;
// if ($hasopenclosedates) {
//     echo $renderer->box($renderer->show_open_close_dates($moduleinstance), 'generalbox');

//     $currenttime = time();
//     $closed = false;
//     if ( $currenttime > $moduleinstance->viewend && $moduleinstance->viewend > 0) {
//         echo get_string('activityisclosed', constants::M_COMPONENT);
//         $closed = true;
//     } else if ($currenttime < $moduleinstance->viewstart) {
//         echo get_string('activityisnotopenyet', constants::M_COMPONENT);
//         $closed = true;
//     }
//     // If we are not a teacher and the activity is closed/not-open leave at this point.
//     if (!has_capability('mod/readaloud:preview', $modulecontext) && $closed) {
//         echo $renderer->footer();
//         exit;
//     }
// }

// Instructions / intro if less then Moodle 4.0 show.
if ($CFG->version < 2022041900) {
    $introcontent = $renderer->show_intro($moduleinstance, $cm);
    echo $introcontent;
} else {
    $introcontent = '';
}

// Capture the quiz output.
ob_start(); // Start output buffering.
if ($latestattempt->status == constants::M_STATE_QUIZCOMPLETE && !$retake == 1) {
    echo $rsquestionrenderer->show_finished_results($quizhelper, $latestattempt, $cm, $canattempt, $embed);
} else if ($itemcount > 0) {
    echo $rsquestionrenderer->show_quiz($quizhelper, $moduleinstance);
    $previewid = 0;
    echo $rsquestionrenderer->fetch_quiz_amd($cm, $moduleinstance, $previewid, $canattempt, $embed);
} else {
    $showadditemlinks = has_capability('mod/readaloud:manage', $modulecontext);
    echo $rsquestionrenderer->show_no_items($cm, $showadditemlinks);
}
$quizhtml = ob_get_clean();

// Finish the page.
echo $renderer->footer();
