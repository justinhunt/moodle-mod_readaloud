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
 * Provides the interface for overall managing of items
 *
 * @package mod_readaloud
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

use mod_readaloud\constants;

require_once('../../../config.php');
require_once($CFG->dirroot.'/mod/readaloud/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);

$cm = get_coursemodule_from_id('readaloud', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);

$quizhelper = new \mod_readaloud\quizhelper($cm);
$items = $quizhelper->fetch_items();

// mode is necessary for tabs
$mode = 'rsquestions';
// Set page url before require login, so post login will return here
$PAGE->set_url('/mod/readaloud/rsquestion/rsquestions.php', ['id' => $cm->id, 'mode' => $mode]);

// require login for this page
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$renderer = $PAGE->get_renderer(constants::M_COMPONENT);
$rsquestionrenderer = $PAGE->get_renderer(constants::M_COMPONENT, 'rsquestion');

if ($action === 'bulkdelete') {
    confirm_sesskey();
    $questionids = optional_param_array('deletequestionid', [], PARAM_INT);
    foreach ($questionids as $questionid) {
        \mod_readaloud\local\itemtype\item::delete_item($questionid, $context);
    }
    if (!empty($questionids)) {
        \mod_readaloud\utils::reset_item_order($moduleinstance->id);
        redirect($PAGE->url);
    }
}

// If we have items, Data tables will make them pretty.
// Prepare datatable(before header printed).
$tableid = constants::M_ITEMS_TABLE;
$rsquestionrenderer->setup_datatables($tableid);

$PAGE->navbar->add(get_string('rsquestions', constants::M_MODNAME));
echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('rsquestions', constants::M_COMPONENT));


// We need view permission to be here
require_capability('mod/readaloud:itemview', $context);

    // if have edit permission, show edit buttons
if (has_capability('mod/readaloud:itemview', $context)){
    echo $rsquestionrenderer->add_edit_page_links($context, $tableid);
}

// if we have items, show em
$itemsvisible = $items && count($items);
echo $rsquestionrenderer->show_items_list($items, $moduleinstance, $cm, $itemsvisible);
echo $rsquestionrenderer->show_noitems_message($items, $moduleinstance, $cm, $itemsvisible);
/*
if ($items){
    echo $rsquestionrenderer->show_items_list($items, $moduleinstance, $cm);
}
    */
echo $renderer->footer();
