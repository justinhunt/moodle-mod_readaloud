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
 * ReadAloud main page
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

use mod_readaloud\constants;
use mod_readaloud\mobile_auth;

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$n = optional_param('n', 0, PARAM_INT);  // Readaloud instance ID - it should be named as the first character of the module.
$reviewattempts = optional_param('reviewattempts', 0, PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);
$embed = optional_param('embed', 0, PARAM_INT);

// Allow login through an authentication token.
$userid = optional_param('user_id', null, PARAM_ALPHANUMEXT);
$secret  = optional_param('secret', null, PARAM_RAW);

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
}

$PAGE->set_url('/mod/readaloud/view.php', [
    'id' => $cm->id,
    'reviewattempts' => $reviewattempts,
    'embed' => $embed,
]);
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

// Page setup.
$PAGE->set_context($modulecontext);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));

// This library is licensed with the hippocratic license (https://github.com/EthicalSource/hippocratic-license/)
// which is not GPL3 compat. so cant be distributed with plugin. Hence we load it from CDN.
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css'));

// If we need a non standard font we can do that from here.
if (!empty($moduleinstance->customfont)) {
    if (!in_array($moduleinstance->customfont, constants::M_STANDARD_FONTS)) {
        $PAGE->requires->css(new moodle_url('https://fonts.googleapis.com/css?family=' . $moduleinstance->customfont));
    }
}

// We need to load jquery for some old themes (Essential mainly).
$PAGE->requires->jquery();

// Get plugin settings.
$config = get_config(constants::M_COMPONENT);

// Determine the layout.
if ($moduleinstance->foriframe == 1 || $embed == 1) {
    $PAGE->set_pagelayout('embedded');
} else if ($config->enablesetuptab || $embed == 2) {
    $PAGE->set_pagelayout('popup');
    $PAGE->add_body_class('poodll-readaloud-embed');
} else {
    if (has_capability('mod/' . constants::M_MODNAME . ':manage', $modulecontext)) {
        $PAGE->set_pagelayout('incourse');
    }
}

$renderer = $PAGE->get_renderer('mod_readaloud');

// Render the page.
echo $renderer->header(
    $moduleinstance,
    $cm,
    'view',
    null,
    get_string('view', constants::M_COMPONENT)
);

echo $OUTPUT->render(
    new \mod_readaloud\output\view(
        $cm,
        $config,
        $debug,
        $embed,
        $modulecontext,
        $moduleinstance,
        $reviewattempts
    )
);

echo $renderer->footer();
