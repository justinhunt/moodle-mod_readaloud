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
 * Reports for readaloud
 *
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

use mod_readaloud\constants;
use mod_readaloud\utils;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n = optional_param('n', 0, PARAM_INT);  // readaloud instance ID
$action = optional_param('action', 'menu', PARAM_TEXT);

if ($id) {
    $cm = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance(constants::M_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(0, 'You must specify a course_module ID or an instance ID');
}

$PAGE->set_url(constants::M_URL . '/admintab.php',
        ['id' => $cm->id]);
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

require_capability('mod/readaloud:manage', $modulecontext);

// Get any admin settings.
$config = get_config(constants::M_COMPONENT);

// if the alternatives form was submitted
$alternativesform = new \mod_readaloud\form\alternatives();
$adata = $alternativesform->get_data();
if ($adata) {
    $DB->update_record(constants::M_TABLE, ['id' => $adata->n, 'alternatives' => $adata->alternatives]);
    $action = 'machineregradeall';
}

// if the guidedtranscription form was submitted
$gtform = new \mod_readaloud\form\guidedtranscriptionform();
$gtdata = $gtform->get_data();
if ($gtdata) {
    if ($gtdata->usecorpus == constants::GUIDEDTRANS_CORPUS &&
        (empty($moduleinstance->corpushash) || $gtdata->corpusrange != $moduleinstance->corpusrange)) {
        $gtdata->corpushash = utils::fetch_current_corpushash($moduleinstance, $gtdata->corpusrange);
    }

    $updatefields = ['usecorpus', 'corpusrange'];
    if (isset($gtdata->corpushash)) {
        $updatefields[] = 'corpushash';
    }
    switch($gtdata->applysettingsrange){
        case constants::APPLY_ACTIVITY:
            foreach ($updatefields as $thefield) {
                $DB->set_field(constants::M_TABLE, $thefield, $gtdata->{$thefield}, ['id' => $moduleinstance->id, 'ttslanguage' => $moduleinstance->ttslanguage]);
            }
            break;
        case constants::APPLY_COURSE:
            foreach($updatefields as $thefield) {
                $DB->set_field(constants::M_TABLE, $thefield, $gtdata->{$thefield}, ['course' => $moduleinstance->course, 'ttslanguage' => $moduleinstance->ttslanguage]);
            }
            break;
        case constants::APPLY_SITE:
            foreach($updatefields as $thefield) {
                $DB->set_field(constants::M_TABLE, $thefield, $gtdata->{$thefield}, ['ttslanguage' => $moduleinstance->ttslanguage]);
            }
            break;
    }

    $url = new \moodle_url(constants::M_URL . '/admintab.php',
        ['id' => $cm->id,
            'action' => 'menu']);
    redirect($url, get_string('usecorpuschanged', constants::M_COMPONENT), 5);
}

switch ($action) {

    case 'machineregradeall':
        $url = new \moodle_url(constants::M_URL . '/admintab.php',
                ['id' => $cm->id,
                        'action' => 'menu']);
        $aievals = $DB->get_records(constants::M_AITABLE, ['readaloudid' => $moduleinstance->id]);
        if (!$aievals) {
            redirect($url, get_string('noattemptsregrade', constants::M_COMPONENT));
        } else {
            $skipped = 0;
            foreach ($aievals as $eval) {
                $aigrade = new \mod_readaloud\aigrade($eval->attemptid, $modulecontext->id);
                if ($aigrade->has_transcripts()) {
                    $aigrade->do_diff();
                } else {
                    $skipped++;
                }
            }
            $results = new stdClass();
            $results->done = count($aievals) - $skipped;
            $results->skipped = $skipped;
            redirect($url, get_string('machineregraded', constants::M_COMPONENT, $results), 5);
        }
        break;
    case 'pushalltogradebook':
        $url = new \moodle_url(constants::M_URL . '/admintab.php',
                ['id' => $cm->id,
                        'action' => 'menu']);
        if (($moduleinstance->machgrademethod == constants::MACHINEGRADE_HYBRID ||
                        $moduleinstance->machgrademethod == constants::MACHINEGRADE_MACHINEONLY)
            && utils::can_transcribe($moduleinstance)) {
            readaloud_update_grades($moduleinstance);
        }
        redirect($url, get_string('machinegradespushed', constants::M_COMPONENT), 5);
        break;

    case 'pushcorpus':
        $url = new \moodle_url(constants::M_URL . '/admintab.php',
            ['id' => $cm->id,
                'action' => 'menu']);
        utils::push_corpus($moduleinstance, $moduleinstance->corpusrange);
        redirect($url, get_string('corpuspushed', constants::M_COMPONENT), 5);
        break;

    case 'menu':
    default:

}

/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

if($config->enablesetuptab){
    $PAGE->set_pagelayout('popup');
}else{
    $PAGE->set_pagelayout('incourse');
}

$mode = "admintab";

// This puts all our display logic into the renderer.php files in this plugin
$renderer = $PAGE->get_renderer(constants::M_COMPONENT);

// fetch mistranscriptions and html table for them (need to do this before head is printed because of AMD/CSS in table)
$mistranscriptions = utils::fetch_all_mistranscriptions($moduleinstance->id);
$tableofmistranscriptions = $renderer->show_all_mistranscriptions($mistranscriptions);

echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('admintab', constants::M_COMPONENT));

echo $renderer->show_admintab_heading(get_string('admintabtitle', constants::M_COMPONENT),
        get_string('admintabinstructions', constants::M_COMPONENT));

// This is the estimate of errors based on comparing human grades to machine grades
// echo $renderer->show_currenterrorestimate(utils::estimate_errors($moduleinstance->id));

$mform = new \mod_readaloud\form\alternatives();
// id is cmid on this page, so we use n as the id of the instance, so to make sure we arrive back here ok, we add n
$moduleinstance->n = $moduleinstance->id;
$mform->set_data($moduleinstance);
$mform->display();

// echo $renderer->show_machineregradeallbutton($moduleinstance);

echo $tableofmistranscriptions;

// guided transcription
$gttitle = get_string("guidedtranscriptionadmin", constants::M_COMPONENT);
echo $renderer->heading($gttitle, 4);

echo html_writer::div(get_string("guidedtransinstructions", constants::M_COMPONENT), 'mod_readaloud_gtinstructions');

$gform = new \mod_readaloud\form\guidedtranscriptionform(null, []);
$gtdata = new \stdClass();
// id is cmid on this page, so we use n as the id of the instance, so to make sure we arrive back here ok, we add n
$moduleinstance->n = $moduleinstance->id;
$gform->set_data($moduleinstance);
$gform->display();

echo $renderer->show_pushcorpusdetails($moduleinstance);

// This shows button that pushes all updated machine grades to gradebook
echo $renderer->show_pushalltogradebook($moduleinstance);

echo $renderer->footer();
return;
