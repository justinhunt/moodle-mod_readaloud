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
 * Ajax helper for Read Aloud
 *
 *
 * @package    mod_ReadAloud
 * @copyright  Justin Hunt (justin@poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$cmid = required_param('cmid',  PARAM_INT); // course_module ID, or
//$sessionid = required_param('sessionid',  PARAM_INT); // course_module ID, or
$filename= required_param('filename',  PARAM_TEXT); // data baby yeah
$ret =new stdClass();

if ($cmid) {
    $cm         = get_coursemodule_from_id('readaloud', $cmid, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $readaloud  = $DB->get_record('readaloud', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $ret->success=false;
    $ret->message="You must specify a course_module ID or an instance ID";
    return json_encode($ret);
}

require_login($course, false, $cm);

$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);

$success = save_to_moodle($filename, $readaloud);
$ret =new stdClass();
if($success){
    $ret->success=true;
}else{
    $ret->success=false;
    $ret->message="Unable to add update database with submission";
}
echo json_encode($ret);
return;

//save the data to Moodle.
function save_to_moodle($filename,$readaloud){
    global $USER,$DB;


    //Add a blank attempt with just the filename  and essential details
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
    return true;
}