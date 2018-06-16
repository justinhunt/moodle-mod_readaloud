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
 * Grade Now for readaloud plugin
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 namespace mod_readaloud;
defined('MOODLE_INTERNAL') || die();

use \mod_readaloud\constants;


/**
 * Grade Now class for mod_readaloud
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradenow{
	protected $modulecontextid =0;
	protected $attemptid = 0;
	protected $attemptdata = null;
	protected $activitydata = null;
	
	function __construct($attemptid, $modulecontextid=0) {
		global $DB;
       $this->attemptid = $attemptid;
	   $this->modulecontextid = $modulecontextid;
	   $attemptdata = $DB->get_record(constants::MOD_READALOUD_USERTABLE,array('id'=>$attemptid));
	   if($attemptdata){
			$this->attemptdata = $attemptdata;
			$this->activitydata = $DB->get_record(constants::MOD_READALOUD_TABLE,array('id'=>$attemptdata->readaloudid));
		}
   }
   
   public function get_next_ungraded_id(){
		global $DB;
		$where = "id > " .$this->attemptid . " AND sessionscore = 0 AND readaloudid = " . $this->attemptdata->readaloudid;
		$records = $DB->get_records_select(constants::MOD_READALOUD_USERTABLE,$where,array(),' id ASC');
		if($records){
			$rec = array_shift($records);
			return $rec->id;
		}else{
			return false;
		}
   }
   
   public function update($formdata){
		global $DB;
		$updatedattempt = new \stdClass();
		$updatedattempt->id=$this->attemptid;
		$updatedattempt->sessiontime = $formdata->sessiontime;
		$updatedattempt->wpm = $formdata->wpm;
		$updatedattempt->accuracy = $formdata->accuracy;
		$updatedattempt->sessionscore = $formdata->sessionscore;
		$updatedattempt->sessionerrors = $formdata->sessionerrors;
		$updatedattempt->sessionendword = $formdata->sessionendword;
		$DB->update_record(constants::MOD_READALOUD_USERTABLE,$updatedattempt);
   }
   
   public function attemptdetails($property){
		global $DB;
		switch($property){
			case 'userfullname':
				$user = $DB->get_record('user',array('id'=>$this->attemptdata->userid));
				$ret = fullname($user);
				break;
			case 'passage': 
				$ret = $this->activitydata->passage;
				break;
			case 'audiourl':
			    //we need to consider legacy client side URLs and cloud hosted ones
                $ret = utils::make_audio_URL($this->attemptdata->filename,$this->modulecontextid, constants::MOD_READALOUD_FRANKY,
                        constants::MOD_READALOUD_FILEAREA_SUBMISSIONS,
                        $this->attemptdata->id);

				break;
			case 'somedetails': 
				$ret= $this->attemptdata->id . ' ' . $this->activitydata->passage; 
				break;
			default: 
				$ret = $this->attemptdata->{$property};
		}
		return $ret;
   }
   
   public function prepare_javascript($reviewmode=false,$aimode=false){
		global $PAGE;

		//here we set up any info we need to pass into javascript
		$gradingopts =Array();
		$gradingopts['reviewmode'] = $reviewmode;
		$gradingopts['enabletts'] = get_config(constants::MOD_READALOUD_FRANKY,'enabletts');
		$gradingopts['allowearlyexit'] = $this->activitydata->allowearlyexit ? true :false;
		$gradingopts['timelimit'] = $this->activitydata->timelimit;
 		$gradingopts['ttslanguage'] = $this->activitydata->ttslanguage;
		$gradingopts['activityid'] = $this->activitydata->id;
		$gradingopts['targetwpm'] = $this->activitydata->targetwpm;
		$gradingopts['sesskey'] = sesskey();
		$gradingopts['attemptid'] = $this->attemptdata->id;
		$gradingopts['sessiontime'] = $this->attemptdata->sessiontime;
		$gradingopts['sessionerrors'] = $this->attemptdata->sessionerrors;
		$gradingopts['sessionendword'] = $this->attemptdata->sessionendword;
		$gradingopts['wpm'] = $this->attemptdata->wpm;
		$gradingopts['accuracy'] = $this->attemptdata->accuracy;
		$gradingopts['sessionscore'] = $this->attemptdata->sessionscore;
       $gradingopts['opts_id'] = 'mod_readaloud_gradenowopts';


       $jsonstring = json_encode($gradingopts);
       $opts_html = \html_writer::tag('input', '', array('id' => $gradingopts['opts_id'], 'type' => 'hidden', 'value' => $jsonstring));
       $PAGE->requires->js_call_amd("mod_readaloud/gradenowhelper", 'init', array(array('id'=>$gradingopts['opts_id'])));
       //these need to be returned and echo'ed to the page
       return $opts_html;

   }
}
