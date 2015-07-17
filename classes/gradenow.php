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

require_once($CFG->dirroot .'/mod/readaloud/lib.php');


/**
 * Event observer for mod_readaloud
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
	   $attemptdata = $DB->get_record(MOD_READALOUD_USERTABLE,array('id'=>$attemptid));
	   if($attemptdata){
			$this->attemptdata = $attemptdata;
			$this->activitydata = $DB->get_record(MOD_READALOUD_TABLE,array('id'=>$attemptdata->readaloudid));
		}
   }
   
   public function get_next_ungraded_id(){
		global $DB;
		$where = "id > " .$this->attemptid . " AND sessionscore = 0 AND readaloudid = " . $this->attemptdata->readaloudid;
		$records = $DB->get_records_select(MOD_READALOUD_USERTABLE,$where,array(),' id ASC');
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
		$updatedattempt->sessionscore = $formdata->sessionscore;
		$updatedattempt->sessionerrors = $formdata->sessionerrors;
		$updatedattempt->sessionendword = $formdata->sessionendword;
		$DB->update_record(MOD_READALOUD_USERTABLE,$updatedattempt);
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
				$ret = \moodle_url::make_pluginfile_url($this->modulecontextid, MOD_READALOUD_FRANKY, 
					MOD_READALOUD_FILEAREA_SUBMISSIONS, 
					$this->attemptdata->id, '/', 
					$this->attemptdata->filename);
				break;
			case 'somedetails': 
				$ret= $this->attemptdata->id . ' ' . $this->activitydata->passage; 
				break;
			default: 
				$ret = $this->attemptdata->{$property};
		}
		return $ret;
   }
   
   public function prepare_javascript($reviewmode=false){
		global $PAGE;
		
		//get our module javascript all ready to go
		$jsmodule = array(
			'name'     => 'mod_readaloud',
			'fullpath' => '/mod/readaloud/module.js',
			'requires' => array('json')
		);
		//here we set up any info we need to pass into javascript
		$opts =Array();
		//this inits the M.mod_readaloud thingy, after the page has loaded.
		$PAGE->requires->js_init_call('M.mod_readaloud.helper.init', array($opts),false,$jsmodule);


		//here we set up any info we need to pass into javascript
		$gradingopts =Array();
		$gradingopts['reviewmode'] = $reviewmode;
		$gradingopts['enabletts'] = get_config(MOD_READALOUD_FRANKY,'enabletts');
		$gradingopts['allowearlyexit'] = $this->activitydata->allowearlyexit ? true :false;
		$gradingopts['timelimit'] = $this->activitydata->timelimit;
 		$gradingopts['ttslanguage'] = $this->activitydata->ttslanguage;
		$gradingopts['activityid'] = $this->activitydata->id;
		$gradingopts['sesskey'] = sesskey();
		$gradingopts['attemptid'] = $this->attemptdata->id;
		$gradingopts['sessiontime'] = $this->attemptdata->sessiontime;
		$gradingopts['sessionerrors'] = $this->attemptdata->sessionerrors;
		$gradingopts['sessionendword'] = $this->attemptdata->sessionendword;
		$gradingopts['sessionscore'] = $this->attemptdata->sessionscore;

		//this inits the M.mod_readaloud thingy, after the page has loaded.
		$PAGE->requires->js_init_call('M.mod_readaloud.gradinghelper.init', array($gradingopts),false,$jsmodule);
		//$PAGE->requires->strings_for_js(array('gotnosound','recordnameschool','done','beginreading'),MOD_READALOUD_LANG);

   }
}
