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


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/reportclasses.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // readaloud instance ID 
$format = optional_param('format', 'html', PARAM_TEXT); //export format csv or html
$action = optional_param('action', 'grading', PARAM_TEXT); // report type
$userid = optional_param('userid', 0, PARAM_INT); // user id
$attemptid = optional_param('attemptid', 0, PARAM_INT); // attemptid

//paging details
$paging = new stdClass();
$paging->perpage = optional_param('perpage',-1, PARAM_INT);
$paging->pageno = optional_param('pageno',0, PARAM_INT);
$paging->sort  = optional_param('sort','iddsc', PARAM_TEXT);


if ($id) {
    $cm         = get_coursemodule_from_id(MOD_READALOUD_MODNAME, $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record(MOD_READALOUD_TABLE, array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $moduleinstance  = $DB->get_record(MOD_READALOUD_TABLE, array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance(MOD_READALOUD_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

$PAGE->set_url(MOD_READALOUD_URL . '/grading.php', 
	array('id' => $cm->id,'format'=>$format,'action'=>$action,'userid'=>$userid,'attemptid'=>$attemptid));
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);


//Get an admin settings 
$config = get_config(MOD_READALOUD_FRANKY);

//set per page according to admin setting
if($paging->perpage==-1){
	$paging->perpage = $config->itemsperpage;
}

//Diverge logging logic at Moodle 2.7
if($CFG->version<2014051200){
	add_to_log($course->id, MOD_READALOUD_MODNAME, 'reports', "reports.php?id={$cm->id}", $moduleinstance->name, $cm->id);
}else{
	// Trigger module viewed event.
	$event = \mod_readaloud\event\course_module_viewed::create(array(
	   'objectid' => $moduleinstance->id,
	   'context' => $modulecontext
	));
	$event->add_record_snapshot('course_modules', $cm);
	$event->add_record_snapshot('course', $course);
	$event->add_record_snapshot(MOD_READALOUD_MODNAME, $moduleinstance);
	$event->trigger();
} 

//process form submission
switch($action){
	case 'gradenowsubmit':
		$mform = new \mod_readaloud\gradenowform();
		if($mform->is_cancelled()) {
			$action='grading';
			break;
		}else{
			$data = $mform->get_data();
			$gradenow = new \mod_readaloud\gradenow($attemptid,$modulecontext->id);
			$gradenow->update($data);
			
			//update gradebook
			readaloud_update_grades($moduleinstance, $gradenow->attemptdetails('userid'));
			
			//move on or return to grading
			if(property_exists($data,'submit2')){
				$attemptid = $gradenow->get_next_ungraded_id();
				if($attemptid){
					$action='gradenow';
				}else{
					$action='grading';
				}
			}else{
				$action='grading';
			}
		}
		break;
}



/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->requires->jquery();

//require bootstrap and fontawesome ... maybe
if($config->loadfontawesome){
	$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/readaloud/font-awesome/css/font-awesome.min.css'));
}
if($config->loadbootstrap){
	$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/readaloud/bootstrap-3.3.4-dist/css/bootstrap.min.css'));
	$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/readaloud/bootstrap-3.3.4-dist/js/bootstrap.min.js'));
}


//This puts all our display logic into the renderer.php files in this plugin
$renderer = $PAGE->get_renderer(MOD_READALOUD_FRANKY);
$reportrenderer = $PAGE->get_renderer(MOD_READALOUD_FRANKY,'report');
$gradenowrenderer = $PAGE->get_renderer(MOD_READALOUD_FRANKY,'gradenow');

//From here we actually display the page.
$mode = "grading";
$extraheader="";
switch ($action){

	case 'gradenow':
		$gradenow = new \mod_readaloud\gradenow($attemptid,$modulecontext->id);
		$data=array(
			'action'=>'gradenowsubmit',
			'attemptid'=>$attemptid,
			'n'=>$moduleinstance->id,
			'sessiontime'=>$gradenow->attemptdetails('sessiontime'),
			'sessionscore'=>$gradenow->attemptdetails('sessionscore'),
			'sessionendword'=>$gradenow->attemptdetails('sessionendword'),
			'sessionerrors'=>$gradenow->attemptdetails('sessionerrors'));
		$nextid = $gradenow->get_next_ungraded_id();
		$gradenowform = new \mod_readaloud\gradenowform(null,array('shownext'=>$nextid !== false));
		$gradenowform->set_data($data);
		$gradenow->prepare_javascript();
		echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('grading', MOD_READALOUD_LANG));
		echo $gradenowrenderer->render_gradenow($gradenow);
		$gradenowform->display();
		echo $renderer->footer();
		return;
	case 'grading':
		$report = new mod_readaloud_grading_report();
		//formdata should only have simple values, not objects
		//later it gets turned into urls for the export buttons
		$formdata = new stdClass();
		$formdata->readaloudid = $moduleinstance->id;
		$formdata->modulecontextid = $modulecontext->id;
		break;

	case 'gradingbyuser':
		$report = new mod_readaloud_grading_byuser_report();
		//formdata should only have simple values, not objects
		//later it gets turned into urls for the export buttons
		$formdata = new stdClass();
		$formdata->readaloudid = $moduleinstance->id;
		$formdata->userid = $userid;
		$formdata->modulecontextid = $modulecontext->id;
		break;
		
	default:
		echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('grading', MOD_READALOUD_LANG));
		echo "unknown action.";
		echo $renderer->footer();
		return;
}

//if we got to here we are loading the report on screen
//so we need our audio player loaded
//here we set up any info we need to pass into javascript
$jsmodule = array(
			'name'     => 'mod_readaloud',
			'fullpath' => '/mod/readaloud/module.js',
			'requires' => array('json')
		);
$aph_opts =Array();
$aph_opts['hiddenplayerclass'] = MOD_READALOUD_HIDDEN_PLAYER;
$aph_opts['hiddenplayerbuttonclass'] = MOD_READALOUD_HIDDEN_PLAYER_BUTTON;
$aph_opts['hiddenplayerbuttonactiveclass'] =MOD_READALOUD_HIDDEN_PLAYER_BUTTON_ACTIVE;
$aph_opts['hiddenplayerbuttonplayingclass'] =MOD_READALOUD_HIDDEN_PLAYER_BUTTON_PLAYING;
$aph_opts['hiddenplayerbuttonpausedclass'] =MOD_READALOUD_HIDDEN_PLAYER_BUTTON_PAUSED;

//this inits the M.mod_readaloud thingy, after the page has loaded.
$PAGE->requires->js_init_call('M.mod_readaloud.gradinghelper.init', array($aph_opts),false,$jsmodule);


/*
1) load the class
2) call report->process_raw_data
3) call $rows=report->fetch_formatted_records($withlinks=true(html) false(print/excel))
5) call $reportrenderer->render_section_html($sectiontitle, $report->name, $report->get_head, $rows, $report->fields);
*/

$report->process_raw_data($formdata, $moduleinstance);
$reportheading = $report->fetch_formatted_heading();

switch($format){
	case 'html':
	default:
		
		$reportrows = $report->fetch_formatted_rows(true,$paging);
		$allrowscount = $report->fetch_all_rows_count();
		$pagingbar = $reportrenderer->show_paging_bar($allrowscount, $paging,$PAGE->url);
		echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('grading', MOD_READALOUD_LANG));
		echo $gradenowrenderer->render_hiddenaudioplayer();
		echo $extraheader;
		echo $pagingbar;
		echo $reportrenderer->render_section_html($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows, $report->fetch_fields());
		echo $pagingbar;
		echo $reportrenderer->show_grading_footer($moduleinstance,$cm,$formdata);
		echo $renderer->footer();
}