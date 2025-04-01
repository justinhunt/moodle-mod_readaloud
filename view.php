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
 * readaloud main page
 *
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

use mod_readaloud\constants;
use mod_readaloud\utils;
use mod_readaloud\mobile_auth;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$reviewattempts = optional_param('reviewattempts', 0, PARAM_INT); // course_module ID, or
$n = optional_param('n', 0, PARAM_INT);  // readaloud instance ID - it should be named as the first character of the module.
$debug = optional_param('debug', 0, PARAM_INT);
$embed = optional_param('embed', 0, PARAM_INT);

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
    $cm = get_coursemodule_from_id('readaloud', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('readaloud', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $moduleinstance = $DB->get_record('readaloud', ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('readaloud', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    // Soft eject if they get here.
    $redirecturl = new moodle_url('/', []);
    redirect($redirecturl, get_string('invalidcoursemodule', 'error'));
    // print_error('invalidcourseid');
}

$PAGE->set_url('/mod/readaloud/view.php', ['id' => $cm->id, 'reviewattempts' => $reviewattempts, 'embed' => $embed]);
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

// Are we a teacher or a student?
$mode = "view";

// In the case that passage segments have not been set (usually from an upgrade from an earlier version) set those now.
if ($moduleinstance->passagesegments === null) {
    $olditem = false;
    list($thephonetic, $thepassagesegments) = utils::update_create_phonetic_segments($moduleinstance, $olditem);
    if (!empty($thephonetic)) {
        $DB->update_record(constants::M_TABLE, ['id' => $moduleinstance->id, 'phonetic' => $thephonetic, 'passagesegments' => $thepassagesegments]);
        $moduleinstance->phonetic = $thephonetic;
        $moduleinstance->passagesegments = $thepassagesegments;
    }
}

// Get admin settings.
$config = get_config(constants::M_COMPONENT);

// Set up the page header.
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
// This library is licensed with the hippocratic license (https://github.com/EthicalSource/hippocratic-license/)
// which is not GPL3 compat. so cant be distributed with plugin. Hence we load it from CDN.
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css'));


// We want readaloud to embed nicely.
if ($moduleinstance->foriframe == 1 || $embed == 1) {
    $PAGE->set_pagelayout('embedded');
} else if ($config->enablesetuptab || $embed == 2) {
    $PAGE->set_pagelayout('popup');
    $PAGE->add_body_class('poodll-readaloud-embed');
} else {
    // Default layout for users with the 'manage' capability.
    if (has_capability('mod/' . constants::M_MODNAME . ':manage', $modulecontext)) {
        $PAGE->set_pagelayout('incourse');
    }
}

// We need to load jquery for some old themes (Essential mainly).
$PAGE->requires->jquery();

// Get our renderers.
$renderer = $PAGE->get_renderer('mod_readaloud');
$passagerenderer = $PAGE->get_renderer(constants::M_COMPONENT, 'passage');
$modelaudiorenderer = $PAGE->get_renderer(constants::M_COMPONENT, 'modelaudio');

// Do we have attempts and ai data.
$attempts = utils::fetch_user_attempts($moduleinstance);
$aievals = \mod_readaloud\utils::get_aieval_byuser($moduleinstance->id, $USER->id);

// Can attempt ?
$canattempt = true;
$canpreview = has_capability('mod/readaloud:preview', $modulecontext);
if (!$canpreview && $moduleinstance->maxattempts > 0) {
    $gradeableattempts = 0;
    if ($attempts) {
        foreach ($attempts as $candidate) {
            if ($candidate->dontgrade == 0) {
                $gradeableattempts++;
            }
        }
    }
    if ($attempts && $gradeableattempts >= $moduleinstance->maxattempts) {
        $canattempt = false;
    }
}

// Debug mode is for teachers only.
if (!$canpreview) {
    $debug = false;
}

 // For Japanese (and later other languages we collapse spaces).
 $collapsespaces = false;
if ($moduleinstance->ttslanguage == constants::M_LANG_JAJP) {
    $collapsespaces = true;
}

// Fetch a token and report a failure to a display item: $problembox.
$problembox = '';
$token = "";
if (empty($config->apiuser) || empty($config->apisecret)) {
    $message = get_string('nocredentials', constants::M_COMPONENT,
            $CFG->wwwroot . constants::M_PLUGINSETTINGS);
    $problembox = $renderer->show_problembox($message);
} else {
    // Fetch token.
    $token = utils::fetch_token($config->apiuser, $config->apisecret);

    // Check token authenticated and no errors in it.
    $errormessage = utils::fetch_token_error($token);
    if (!empty($errormessage)) {
        $problembox = $renderer->show_problembox($errormessage);
    }
}

// Fetch attempt information.
if ($attempts) {
    $latestattempt = current($attempts);

    if (\mod_readaloud\utils::can_transcribe($moduleinstance)) {
        $latestaigrade = new \mod_readaloud\aigrade($latestattempt->id, $modulecontext->id);
    } else {
        $latestaigrade = false;
    }

    $havehumaneval = $latestattempt->sessiontime != null;
    $haveaieval = $latestaigrade && $latestaigrade->has_transcripts();
} else {
    $latestattempt = false;
    $havehumaneval = false;
    $haveaieval = false;
    $latestaigrade = false;
}

// If we need a non standard font we can do that from here.
if (!empty($moduleinstance->customfont)) {
    if (!in_array($moduleinstance->customfont, constants::M_STANDARD_FONTS)) {
        $PAGE->requires->css(new moodle_url('https://fonts.googleapis.com/css?family=' . $moduleinstance->customfont));
    }
}

// From here we actually display the page.
// If we are teacher we see tabs. If student we just see the activity.
echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('view', constants::M_COMPONENT));

// If we have no content, and its setup tab, we send to setup tab.
if ($config->enablesetuptab && empty($moduleinstance->passage)) {
    if (has_capability('mod/readaloud:manage', $modulecontext)) {
        echo $renderer->show_no_content($cm, true);
    } else {
        echo $renderer->show_no_content($cm, false);
    }
    echo $renderer->footer();
    return;
}

// If we are reviewing attempts we do that here and return.
// If we are going to the dashboard we output that below.
if ($attempts && $reviewattempts) {
    $attemptreviewhtml = $renderer->show_attempt_for_review($moduleinstance, $attempts,
            $havehumaneval, $haveaieval, $collapsespaces, $latestattempt, $token, $modulecontext, $passagerenderer, $embed);
    echo $attemptreviewhtml;

    return;
}

// Show all the main parts. Many will be hidden and displayed by JS
// so here we just put them on the page in the correct sequence.

// FIXME: Everything below here should be in the templatecontext data.

// Show small report.
if ($attempts) {
    if (!$latestattempt) {
        $latestattempt = current($attempts);
    }
}

// If we have a problem (usually with auth/token) we display and return.
if (!empty($problembox)) {
    echo $problembox;
    // Finish the page.
    echo $renderer->footer();
    return;
}

// Show model audio player.
$visible = false;
echo $modelaudiorenderer->render_modelaudio_player($moduleinstance, $token, $visible);

// Render from template.
$templatecontext = $renderer->get_view_page_data(
    $moduleinstance,
    $cm,
    $modulecontext,
    $canattempt,
    $attempts,
    $config,
    $embed,
    $token,
    $latestattempt,
    $latestaigrade,
    $debug
);


echo $OUTPUT->render_from_template('mod_readaloud/view', $templatecontext);

// Finish the page.
echo $renderer->footer();
