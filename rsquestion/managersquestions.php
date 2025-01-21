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
 * Action for adding/editing a rsquestion.
 *
 * @package mod_readaloud
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

use mod_readaloud\constants;
use mod_readaloud\utils;
use mod_readaloud\local\itemtype\item;

require_once("../../../config.php");
require_once($CFG->dirroot.'/mod/readaloud/lib.php');


global $USER, $DB;

// first get the nfo passed in to set up the page
$itemid = optional_param('itemid', 0 , PARAM_INT);
$id     = required_param('id', PARAM_INT);         // Course Module ID
$type  = optional_param('type', constants::NONE, PARAM_TEXT);
$action = optional_param('action', 'edit', PARAM_TEXT);

// get the objects we need
$cm = get_coursemodule_from_id(constants::M_MODNAME, $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);

// make sure we are logged in and can see this form
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/readaloud:itemedit', $context);

// set up the page object
$PAGE->set_url('/mod/readaloud/rsquestion/managersquestions.php', ['itemid' => $itemid, 'id' => $id, 'type' => $type]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

// are we in new or edit mode?
if ($itemid) {
    $item = $DB->get_record(constants::M_QTABLE, ['id' => $itemid, constants::M_MODNAME . 'id' => $cm->instance], '*', MUST_EXIST);
    if(!$item){
        print_error('could not find item of id:' . $itemid);
    }
    $type = $item->type;
    $edit = true;
} else {
    $edit = false;
}

// we always head back to the readaloud items page
$redirecturl = new moodle_url('/mod/readaloud/rsquestion/rsquestions.php', ['id' => $cm->id]);

    // handle delete actions
if($action == 'confirmdelete'){
    // TODO more intelligent detection of question usage
    $usecount = $DB->count_records(constants::M_USERTABLE, [constants::M_MODNAME .'id' => $cm->instance]);
    if($usecount > 0){
         redirect($redirecturl, get_string('iteminuse', constants::M_COMPONENT), 10);
    }

        $renderer = $PAGE->get_renderer(constants::M_COMPONENT);
        $rsquestionrenderer = $PAGE->get_renderer(constants::M_COMPONENT, 'rsquestion');
        echo $renderer->header($moduleinstance, $cm, 'rsquestions', null, get_string('confirmitemdeletetitle', constants::M_COMPONENT));
        echo $rsquestionrenderer->confirm(get_string("confirmitemdelete", constants::M_COMPONENT, $item->name),
        new moodle_url('/mod/readaloud/rsquestion/managersquestions.php', ['action' => 'delete', 'id' => $cm->id, 'itemid' => $itemid]),
        $redirecturl);
    echo $renderer->footer();
    return;

    /////// Delete item NOW////////
}else if ($action == 'delete'){
    require_sesskey();
    $success = helper::delete_item($moduleinstance, $itemid, $context);
    redirect($redirecturl);
}else if($action == "moveup" || $action == "movedown"){
    helper::move_item($moduleinstance, $itemid, $action);
    redirect($redirecturl);
}



// Get filechooser and html editor options.
$editoroptions = \mod_readaloud\local\itemtype\item::fetch_editor_options($course, $context);
$filemanageroptions = \mod_readaloud\local\itemtype\item::fetch_filemanager_options($course, 3);


// get the mform for our item
$itemformclass  = utils::fetch_itemform_classname($type);
if(!$itemformclass){
    throw new \moodle_exception('No item type specified');
    return 0;
}
$mform = new $itemformclass(null,
    ['editoroptions' => $editoroptions,
        'filemanageroptions' => $filemanageroptions,
        'moduleinstance' => $moduleinstance]
);

// if the cancel button was pressed, we are out of here
if ($mform->is_cancelled()) {
    redirect($redirecturl);
    exit;
}

// if we have data, then our job here is to save it and return to the quiz edit page
if ($data = $mform->get_data()) {
    require_sesskey();
    $data->type = $type;

  



    if($edit){
        $theitem = utils::fetch_item_from_itemrecord($data, $moduleinstance);
        $olditem = $item;
    }else{
        $theitem = utils::fetch_item_from_itemrecord($data, $moduleinstance);
        $olditem = false;
        // echo json_encode($data,JSON_PRETTY_PRINT);
        // die;

    }

    // remove bad accents and things that mess up transcription (kind of like clear but permanent)
    $theitem->deaccent();

    $result = $theitem->update_insert_item();
    if($result->error == true){
        print_error($result->message);
        redirect($redirecturl);

    }else{
        $theitem = $result->item;
    }

    // go back to edit quiz page
    redirect($redirecturl);
}

// if  we got here, there was no cancel, and no form data, so we are showing the form
// if edit mode load up the item into a data object
if ($edit) {
    $data = $item;
    $data->itemid = $item->id;

    // make sure the media upload fields are in the correct state
    $fs = get_file_storage();
    $files = $fs->get_area_files( $context->id,  constants::M_COMPONENT, constants::MEDIAQUESTION, $data->itemid);
    if ($files) {
        $data->addmedia = 1;
    } else {
        $data->addmedia = 0;
    }
    if (!empty($data->{constants::TTSQUESTION})) {
        $data->addttsaudio = 1;
    } else {
        $data->addttsaudio = 0;
    }

    if (!empty($data->{constants::QUESTIONTEXTAREA})) {
        $edoptions = constants::ITEMTEXTAREA_EDOPTIONS;
        $edoptions['context'] = $context;
        $data->{constants::QUESTIONTEXTAREA. 'format'} = FORMAT_HTML;
        $data = file_prepare_standard_editor($data, constants::QUESTIONTEXTAREA, $edoptions, $context, constants::M_COMPONENT,
        constants::TEXTQUESTION_FILEAREA, $data->itemid);
        $data->addtextarea = 1;
    } else {
        $data->addtextarea = 0;
    }

    // init our itemmedia upload file field
    $draftitemid = file_get_submitted_draft_itemid(constants::MEDIAQUESTION);
    file_prepare_draft_area($draftitemid, $context->id, constants::M_COMPONENT,
    constants::MEDIAQUESTION, $data->itemid,
    $filemanageroptions);
    $data->{constants::MEDIAQUESTION} = $draftitemid;

    // show the fields by default if they have some content
    $visibility = ['addmedia' => $data->addmedia,
    'addttsaudio' => $data->addttsaudio,
    'addtextarea' => $data->addtextarea];
    $PAGE->requires->js_call_amd(constants::M_COMPONENT . '/mediaprompts', 'init', [$visibility]);


} else {
    $data = new stdClass;
    $data->itemid = null;
    $data->visible = 1;
    $data->type = $type;


    // init media prompts - all hidden initiall
    $visibility = ['addmedia' => 0,
    'addttsaudio' => 0,
    'addtextarea' => 0, ];
    $PAGE->requires->js_call_amd(constants::M_COMPONENT . '/mediaprompts', 'init', [$visibility]);
}

    // init our item, we move the id fields around a little
    $data->id = $cm->id;

    $mform->set_data($data);
    $PAGE->navbar->add(get_string('edit'), new moodle_url('/mod/readaloud/rsquestion/rsquestions.php', ['id' => $id]));
    $PAGE->navbar->add(get_string('editingitem', constants::M_COMPONENT, get_string($mform->type, constants::M_COMPONENT)));
    $renderer = $PAGE->get_renderer(constants::M_COMPONENT);
    $mode = 'rsquestions';
    echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('edit', constants::M_COMPONENT));
    $mform->display();
    echo $renderer->footer();
