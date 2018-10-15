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

use \mod_readaloud\constants;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // readaloud instance ID 
$format = optional_param('format', 'html', PARAM_TEXT); //export format csv or html
$action = optional_param('action', 'grading', PARAM_TEXT); // report type
$userid = optional_param('userid', 0, PARAM_INT); // user id
$attemptid = optional_param('attemptid', 0, PARAM_INT); // attemptid
$saveandnext = optional_param('submitbutton2', 'false', PARAM_TEXT); //Is this a savebutton2


//paging details
$paging = new stdClass();
$paging->perpage = optional_param('perpage',-1, PARAM_INT);
$paging->pageno = optional_param('pageno',0, PARAM_INT);
$paging->sort  = optional_param('sort','user', PARAM_TEXT);


if ($id) {
    $cm         = get_coursemodule_from_id(constants::MOD_READALOUD_MODNAME, $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record(constants::MOD_READALOUD_TABLE, array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $moduleinstance  = $DB->get_record(constants::MOD_READALOUD_TABLE, array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance(constants::MOD_READALOUD_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

require_capability('mod/readaloud:manage', $modulecontext);

//Get an admin settings 
$config = get_config(constants::MOD_READALOUD_FRANKY);

//set per page according to admin setting
if($paging->perpage==-1){
	$paging->perpage = $config->itemsperpage;
}

// Trigger module viewed event.
$event = \mod_readaloud\event\course_module_viewed::create(array(
   'objectid' => $moduleinstance->id,
   'context' => $modulecontext
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot(constants::MOD_READALOUD_MODNAME, $moduleinstance);
$event->trigger();


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
			if($saveandnext != ('false')){
				$attemptid = $gradenow->get_next_ungraded_id();
				if($attemptid){
					$action='gradenow';
					//redirect to clear out form data so we can gradenow on next attempt
                    $url =  new \moodle_url(constants::MOD_READALOUD_URL . '/grading.php',
                            array('id' => $cm->id,'format'=>$format,
                                'action'=>$action,'userid'=>$userid,
                                'attemptid'=>$attemptid));
                    redirect($url);
				}else{
					$action='grading';
				}
			}else{
				$action='grading';
			}
		}
		break;
}


$PAGE->set_url(constants::MOD_READALOUD_URL . '/grading.php',
    array('id' => $cm->id,'format'=>$format,'action'=>$action,'userid'=>$userid,'attemptid'=>$attemptid));

/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->requires->jquery();

//This puts all our display logic into the renderer.php files in this plugin
$renderer = $PAGE->get_renderer(constants::MOD_READALOUD_FRANKY);
$reportrenderer = $PAGE->get_renderer(constants::MOD_READALOUD_FRANKY,'report');
$gradenowrenderer = $PAGE->get_renderer(constants::MOD_READALOUD_FRANKY,'gradenow');

//From here we actually display the page.
$mode = "grading";
$extraheader="";
switch ($action){

    //load individual attempt page with most recent(human or machine) eval and action buttons
	case 'gradenow':

		$gradenow = new \mod_readaloud\gradenow($attemptid,$modulecontext->id);
		$force_aidata=false;//ai data could still be used if not human grading. we just do not force it
        $reviewmode=$reviewmode=constants::REVIEWMODE_NONE;
        $nextid = $gradenow->get_next_ungraded_id();
		$setdata=array(
			'action'=>'gradenowsubmit',
			'attemptid'=>$attemptid,
			'n'=>$moduleinstance->id,
			'shownext'=>$nextid,
			'sessiontime'=>$gradenow->formdetails('sessiontime',$force_aidata),
			'sessionscore'=>$gradenow->formdetails('sessionscore',$force_aidata),
			'sessionendword'=>$gradenow->formdetails('sessionendword',$force_aidata),
			'sessionerrors'=>$gradenow->formdetails('sessionerrors',$force_aidata));

		$gradenowform = new \mod_readaloud\gradenowform(null,array('shownext'=>$nextid !== false));
		$gradenowform->set_data($setdata);
		echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('grading', constants::MOD_READALOUD_LANG));
        echo $gradenow->prepare_javascript($reviewmode,$force_aidata);
		echo $gradenowrenderer->render_gradenow($gradenow);
		$gradenowform->display();
        echo $reportrenderer->show_grading_footer($moduleinstance,$cm,$mode);
		echo $renderer->footer();
		return;


    //load individual attempt page with machine eval and action buttons   (BUT rerun the AI auto grade code on it first)
    case 'regradenow':

        $mode = "machinegrading";

        //this forces the regrade using any changes in the diff algorythm, or alternatives
        //must be done before instant. $gradenow which also  aigrade object internally
        $aigrade = new \mod_readaloud\aigrade($attemptid,$modulecontext->id);
        $aigrade->do_diff();

        //fetch attempt and ai data
        $gradenow = new \mod_readaloud\gradenow($attemptid,$modulecontext->id);
        $force_aidata=true;//in this case we are just interested in ai data
        $reviewmode = $reviewmode=constants::REVIEWMODE_MACHINE;

        echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('grading', constants::MOD_READALOUD_LANG));
        echo $gradenow->prepare_javascript($reviewmode,$force_aidata);
        echo $gradenowrenderer->render_machinereview($gradenow);
        //if we can grade and manage attempts show the gradenow button
        if(has_capability('mod/readaloud:manageattempts',$modulecontext )) {
            echo $gradenowrenderer->render_machinereview_buttons($gradenow);
        }
        echo $reportrenderer->show_grading_footer($moduleinstance,$cm,$mode);
        echo $renderer->footer();
        return;

    //load individual attempt page with machine eval (NO action buttons )
    case 'machinereview':

        $mode = "machinegrading";
        $gradenow = new \mod_readaloud\gradenow($attemptid,$modulecontext->id);
        $force_aidata=true;//in this case we are just interested in ai data
        $reviewmode=constants::REVIEWMODE_MACHINE;

        echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('grading', constants::MOD_READALOUD_LANG));

        echo $gradenow->prepare_javascript($reviewmode,$force_aidata);
        echo $gradenowrenderer->render_machinereview($gradenow);
        //if we can grade and manage attempts show the gradenow button
        if(has_capability('mod/readaloud:manageattempts',$modulecontext )) {
            echo $gradenowrenderer->render_machinereview_buttons($gradenow);
        }
        echo $reportrenderer->show_grading_footer($moduleinstance,$cm,$mode);
        echo $renderer->footer();
        return;

     //load individual attempt page with machine eval and action buttons
    case 'aigradenow':

        $mode = "machinegrading";
        $gradenow = new \mod_readaloud\gradenow($attemptid,$modulecontext->id);
        $force_aidata=true;//in this case we are just interested in ai data
        $reviewmode=$reviewmode=constants::REVIEWMODE_NONE;

        //$aigrade = new \mod_readaloud\aigrade($attemptid,$modulecontext->id);

        $setdata=array(
            'action'=>'gradenowsubmit',
            'attemptid'=>$attemptid,
            'n'=>$moduleinstance->id,
            'sessiontime'=>$gradenow->formdetails('sessiontime',$force_aidata),
            'sessionscore'=>$gradenow->formdetails('sessionscore',$force_aidata),
            'sessionendword'=>$gradenow->formdetails('sessionendword',$force_aidata),
            'sessionerrors'=>$gradenow->formdetails('sessionerrors',$force_aidata));
        $nextid = $gradenow->get_next_ungraded_id();
        $gradenowform = new \mod_readaloud\gradenowform(null,array('shownext'=>$nextid !== false));
        $gradenowform->set_data($setdata);
        echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('grading', constants::MOD_READALOUD_LANG));
        echo $gradenow->prepare_javascript($reviewmode,$force_aidata);
        echo $gradenowrenderer->render_gradenow($gradenow);
        $gradenowform->display();
        echo $reportrenderer->show_grading_footer($moduleinstance,$cm,$mode);
        echo $renderer->footer();
        return;

    //list view of attempts and grades and action links
	case 'grading':
		$report = new \mod_readaloud\report\grading();
		//formdata should only have simple values, not objects
		//later it gets turned into urls for the export buttons
		$formdata = new stdClass();
		$formdata->readaloudid = $moduleinstance->id;
		$formdata->modulecontextid = $modulecontext->id;
		break;

    //list view of attempts and grades and action links for a particular user
	case 'gradingbyuser':
		$report = new \mod_readaloud\report\gradingbyuser();
		//formdata should only have simple values, not objects
		//later it gets turned into urls for the export buttons
		$formdata = new stdClass();
		$formdata->readaloudid = $moduleinstance->id;
		$formdata->userid = $userid;
		$formdata->modulecontextid = $modulecontext->id;
		break;

    //list view of attempts and machine grades and action links
    case 'machinegrading':
        $mode="machinegrading";
        $report = new \mod_readaloud\report\machinegrading();
        //formdata should only have simple values, not objects
        //later it gets turned into urls for the export buttons
        $formdata = new stdClass();
        $formdata->readaloudid = $moduleinstance->id;
        $formdata->modulecontextid = $modulecontext->id;
        switch($moduleinstance->accadjustmethod){
            case constants::ACCMETHOD_NONE:
                $accadjust=0;
                break;
            case constants::ACCMETHOD_AUTO:
                $accadjust = \mod_readaloud\utils::estimate_errors($moduleinstance->id);
                break;
            case constants::ACCMETHOD_FIXED:
                $accadjust = $moduleinstance->accadjust;
        }
        $formdata->accadjust=$accadjust;
        $formdata->targetwpm=$moduleinstance->targetwpm;
        break;

    //list view of machine  attempts and grades and action links for a particular user
    case 'machinegradingbyuser':
        $mode = "machinegrading";
        $report = new \mod_readaloud\report\machinegradingbyuser();
        //formdata should only have simple values, not objects
        //later it gets turned into urls for the export buttons
        $formdata = new stdClass();
        $formdata->readaloudid = $moduleinstance->id;
        $formdata->userid = $userid;
        $formdata->modulecontextid = $modulecontext->id;
        break;

	default:
		echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('grading', constants::MOD_READALOUD_LANG));
		echo "unknown action.";
		echo $renderer->footer();
		return;
}

//if we got to here we are loading the report on screen
//so we need our audio player loaded
//here we set up any info we need to pass into javascript
$aph_opts =Array();
$aph_opts['hiddenplayerclass'] = constants::MOD_READALOUD_HIDDEN_PLAYER;
$aph_opts['hiddenplayerbuttonclass'] = constants::MOD_READALOUD_HIDDEN_PLAYER_BUTTON;
$aph_opts['hiddenplayerbuttonactiveclass'] =constants::MOD_READALOUD_HIDDEN_PLAYER_BUTTON_ACTIVE;
$aph_opts['hiddenplayerbuttonplayingclass'] =constants::MOD_READALOUD_HIDDEN_PLAYER_BUTTON_PLAYING;
$aph_opts['hiddenplayerbuttonpausedclass'] =constants::MOD_READALOUD_HIDDEN_PLAYER_BUTTON_PAUSED;

//this inits the js for the audio players on the list of submissions
$PAGE->requires->js_call_amd("mod_readaloud/gradinghelper", 'init', array($aph_opts));


/*
1) load the class
2) call report->process_raw_data
3) call $rows=report->fetch_formatted_records($withlinks=true(html) false(print/excel))
5) call $reportrenderer->render_section_html($sectiontitle, $report->name, $report->get_head, $rows, $report->fields);
*/

$report->process_raw_data($formdata, $moduleinstance);
$reportheading = $report->fetch_formatted_heading();

switch($format){
    case 'csv':
        $reportrows = $report->fetch_formatted_rows(false);
        $reportrenderer->render_section_csv($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows, $report->fetch_fields());
        exit;
	case 'html':
	default:
        $reportrows = $report->fetch_formatted_rows(true,$paging);
        $allrowscount = $report->fetch_all_rows_count();
	    $pagingbar = $reportrenderer->show_paging_bar($allrowscount, $paging,$PAGE->url);
        $perpage_selector = $reportrenderer->show_perpage_selector($PAGE->url,$paging);


		echo $renderer->header($moduleinstance, $cm, $mode, null, get_string('grading', constants::MOD_READALOUD_LANG));
		echo $gradenowrenderer->render_hiddenaudioplayer();
		echo $extraheader;
		echo $pagingbar;
		echo $perpage_selector;
		echo $reportrenderer->render_section_html($reportheading, $report->fetch_name(), $report->fetch_head(), $reportrows, $report->fetch_fields());
		echo $pagingbar;
		echo $reportrenderer->show_grading_footer($moduleinstance,$cm,$mode);
        echo $reportrenderer->show_export_buttons($cm,$formdata,$action);
		echo $renderer->footer();
}