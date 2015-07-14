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
 *  Report Classes.
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Classes for Reports 
 *
 *	The important functions are:
*  process_raw_data : turns log data for one thig (question attempt) into one row
 * fetch_formatted_fields: uses data prepared in process_raw_data to make each field in fields full of formatted data
 * The allusers report is the simplest example 
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mod_readaloud_base_report {

    protected $report="";
    protected $head=array();
	protected $rawdata=null;
    protected $fields = array();
	protected $dbcache=array();
	
	
	abstract function process_raw_data($formdata);
	abstract function fetch_formatted_heading();
	
	public function fetch_fields(){
		return $this->fields;
	}
	public function fetch_head(){
		$head=array();
		foreach($this->fields as $field){
			$head[]=get_string($field,MOD_READALOUD_LANG);
		}
		return $head;
	}
	public function fetch_name(){
		return $this->report;
	}

	public function truncate($string, $maxlength){
		if(strlen($string)>$maxlength){
			$string=substr($string,0,$maxlength - 2) . '..';
		}
		return $string;
	}

	public function fetch_cache($table,$rowid){
		global $DB;
		if(!array_key_exists($table,$this->dbcache)){
			$this->dbcache[$table]=array();
		}
		if(!array_key_exists($rowid,$this->dbcache[$table])){
			$this->dbcache[$table][$rowid]=$DB->get_record($table,array('id'=>$rowid));
		}
		return $this->dbcache[$table][$rowid];
	}

	public function fetch_formatted_time($seconds){
			
			//return empty string if the timestamps are not both present.
			if(!$seconds){return '';}
			$time=time();
			return $this->fetch_time_difference($time, $time + $seconds);
	}
	
	public function fetch_time_difference($starttimestamp,$endtimestamp){
			
			//return empty string if the timestamps are not both present.
			if(!$starttimestamp || !$endtimestamp){return '';}
			
			$s = $date = new DateTime();
			$s->setTimestamp($starttimestamp);
						
			$e =$date = new DateTime();
			$e->setTimestamp($endtimestamp);
						
			$diff = $e->diff($s);
			$ret = $diff->format("%H:%I:%S");
			return $ret;
	}
	
	public function fetch_formatted_rows($withlinks=true){
		$records = $this->rawdata;
		$fields = $this->fields;
		$returndata = array();
		foreach($records as $record){
			$data = new stdClass();
			foreach($fields as $field){
				$data->{$field}=$this->fetch_formatted_field($field,$record,$withlinks);
			}//end of for each field
			$returndata[]=$data;
		}//end of for each record
		return $returndata;
	}
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'timecreated':
					$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
				case 'userid':
					$u = $this->fetch_cache('user',$record->userid);
					$ret =fullname($u);
					break;
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
}


/*
* Grading Report
*
*
*/
class mod_readaloud_grading_report extends  mod_readaloud_base_report {
	
	protected $report="grading";
	protected $fields = array('id','username','audiofile','totalattempts','wpm','gradenow','timecreated','deletenow');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB,$CFG,$OUTPUT;
			switch($field){
				case 'id':
						$ret = $record->id;
						break;
				
				case 'username':
						$user = $this->fetch_cache('user',$record->userid);
						$ret = fullname($user);
						if($withlinks){
							$link = new moodle_url(MOD_READALOUD_URL . '/grading.php',
								array('action'=>'gradingbyuser','n'=>$record->readaloudid, 'userid'=>$record->userid));
							$ret = html_writer::link($link,$ret);
						}
					break;
				
				case 'totalattempts':
						$ret = $record->totalattempts;
						if($withlinks){
							$link = new moodle_url(MOD_READALOUD_URL . '/grading.php',
								array('action'=>'gradingbyuser','n'=>$record->readaloudid, 'userid'=>$record->userid));
							$ret = html_writer::link($link,$ret);
						}
					break;
				
				case 'audiofile':
						if($withlinks){
							
							$ret = html_writer::tag('audio','',
									array('controls'=>'','src'=>$record->audiourl));
						}else{
							$ret = get_string('submitted',MOD_READALOUD_LANG);
						}
					break;
				
				case 'wpm':
						$ret = $record->sessionscore;
					break;
					
				case 'gradenow':
						if($withlinks){
							$link = new moodle_url(MOD_READALOUD_URL . '/grading.php',array('action'=>'gradenow','n'=>$record->readaloudid, 'attemptid'=>$record->id));
							$ret =  html_writer::link($link, get_string('gradenow',MOD_READALOUD_LANG));
						}else{
							$ret = get_string('cannotgradenow',MOD_READALOUD_LANG);
						}
					break;
				
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
				
				case 'deletenow':
						$url = new moodle_url(MOD_READALOUD_URL . '/manageattempts.php',
							array('action'=>'delete','n'=>$record->readaloudid, 'attemptid'=>$record->id, 'source'=>$this->report));
						$btn = new single_button($url, get_string('delete'), 'post');
						$btn->add_confirm_action(get_string('deleteattemptconfirm',MOD_READALOUD_LANG));
						$ret =$OUTPUT->render($btn);
						break;
					
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field}; 
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
	public function fetch_formatted_heading(){
		$record = $this->headingdata;
		$ret='';
		if(!$record){return $ret;}
		//$ec = $this->fetch_cache(MOD_READALOUD_TABLE,$record->englishcentralid);
		return get_string('gradingheading',MOD_READALOUD_LANG);
		
	}
	
	public function process_raw_data($formdata){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$user_attempt_totals= array();
		$alldata = $DB->get_records(MOD_READALOUD_USERTABLE,array('readaloudid'=>$formdata->readaloudid),'id DESC, userid');
		
		if($alldata){
			
			foreach($alldata as $thedata){
			
				//we ony take the most recent attempt
				if(array_key_exists($thedata->userid,$user_attempt_totals)){
					$user_attempt_totals[$thedata->userid] = $user_attempt_totals[$thedata->userid] + 1;
					continue;
				}
				$user_attempt_totals[$thedata->userid]=1;
				
				$thedata->audiourl = moodle_url::make_pluginfile_url($formdata->modulecontextid, MOD_READALOUD_FRANKY, 			MOD_READALOUD_FILEAREA_SUBMISSIONS, $formdata->readaloudid, '/' . $thedata->userid . '/', $thedata->filename);
				$this->rawdata[] = $thedata;
			}
			foreach($this->rawdata as $thedata){
				$thedata->totalattempts = $user_attempt_totals[$thedata->userid];
			}
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}

/*
* Grading Report
*
*
*/
class mod_readaloud_grading_byuser_report extends  mod_readaloud_grading_report {
	protected $report="gradingbyuser";
	protected $fields = array('id','username','audiofile','wpm','gradenow','timecreated','deletenow');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	
	
	public function fetch_formatted_heading(){
		$record = $this->headingdata;
		$ret='';
		if(!$record){return $ret;}
		$user = $this->fetch_cache('user',$record->userid);
		return get_string('gradingbyuserheading',MOD_READALOUD_LANG,fullname($user));
		
	}
	
	public function process_raw_data($formdata){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		$this->headingdata->userid = $formdata->userid;
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_READALOUD_USERTABLE,array('readaloudid'=>$formdata->readaloudid,'userid'=>$formdata->userid),'id DESC');
		
		if($alldata){
			
			foreach($alldata as $thedata){
				$thedata->audiourl = moodle_url::make_pluginfile_url($formdata->modulecontextid, MOD_READALOUD_FRANKY, 			MOD_READALOUD_FILEAREA_SUBMISSIONS, $formdata->readaloudid, '/' . $thedata->userid . '/', $thedata->filename);
				$this->rawdata[] = $thedata;
			}
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
	
}



/*
* Attempts Report
*
*
*/
class mod_readaloud_attempts_report extends  mod_readaloud_base_report {
	
	protected $report="attempts";
	protected $fields = array('id','username','audiofile','wpm','timecreated','deletenow');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB,$CFG,$OUTPUT;
			switch($field){
				case 'id':
						$ret = $record->id;
						break;
				
				case 'username':
						$user = $this->fetch_cache('user',$record->userid);
						$ret = fullname($user);
					break;
				
				case 'audiofile':
						if($withlinks){
							
							$ret = html_writer::tag('audio','',
									array('controls'=>'','src'=>$record->audiourl));
						}else{
							$ret = get_string('submitted',MOD_READALOUD_LANG);
						}
					break;
				
				case 'wpm':
						$ret = $record->sessionscore;
					break;
				
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
					
				case 'deletenow':
					$url = new moodle_url(MOD_READALOUD_URL . '/manageattempts.php',
						array('action'=>'delete','n'=>$record->readaloudid, 'attemptid'=>$record->id, 'source'=>$this->report));
					$btn = new single_button($url, get_string('delete'), 'post');
					$btn->add_confirm_action(get_string('deleteattemptconfirm',MOD_READALOUD_LANG));
					$ret =$OUTPUT->render($btn);
					break;
					
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
	public function fetch_formatted_heading(){
		$record = $this->headingdata;
		$ret='';
		if(!$record){return $ret;}
		//$ec = $this->fetch_cache(MOD_READALOUD_TABLE,$record->englishcentralid);
		return get_string('attemptsheading',MOD_READALOUD_LANG);
		
	}
	
	public function process_raw_data($formdata){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_READALOUD_USERTABLE,array('readaloudid'=>$formdata->readaloudid));
		
		if($alldata){
			foreach($alldata as $thedata){
				$thedata->audiourl = moodle_url::make_pluginfile_url($formdata->modulecontextid, MOD_READALOUD_FRANKY, 			MOD_READALOUD_FILEAREA_SUBMISSIONS, $formdata->readaloudid, '/' . $thedata->userid . '/', $thedata->filename);
				$this->rawdata[] = $thedata;
			}
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}


/*
* Basic Report
*
*
*/
class mod_readaloud_basic_report extends  mod_readaloud_base_report {
	
	protected $report="basic";
	protected $fields = array('id','name','timecreated');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->id;
						break;
				
				case 'name':
						$ret = $record->name;
					break;
				
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
					
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
	public function fetch_formatted_heading(){
		$record = $this->headingdata;
		$ret='';
		if(!$record){return $ret;}
		//$ec = $this->fetch_cache(MOD_READALOUD_TABLE,$record->englishcentralid);
		return get_string('basicheading',MOD_READALOUD_LANG);
		
	}
	
	public function process_raw_data($formdata){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_READALOUD_TABLE,array());
		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}
