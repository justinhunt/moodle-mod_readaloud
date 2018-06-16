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
 * A mod_readaloud adhoc task
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_readaloud\task;

defined('MOODLE_INTERNAL') || die();

use \mod_readaloud\constants;


/**
 * A mod_readaloud adhoc task to fetch back transcriptions from Amazon S3
 *
 * @package    mod_readaloud
 * @since      Moodle 2.7
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class readaloud_s3_adhoc extends \core\task\adhoc_task {
                                                                     
   	 /**
     *  Run the tasks
     */
	 public function execute(){
	     global $DB;
		$trace = new \text_progress_trace();

		//CD should contain activityid / attemptid and modulecontextid
		$cd =  $this->get_custom_data();
		//$trace->output($cd->somedata)

         $activity = $DB->get_record(constants::MOD_READALOUD_TABLE,array('id'=>$cd->activityid));
         if(!\mod_readaloud\utils::can_transcribe($activity)){
             $this->do_forever_fail('This activity does not support transcription',$trace);
             return;
         }

         $aigrade = new \mod_readaloud\aigrade($cd->attemptid,$cd->modulecontextid);
         if($aigrade){
             if(!$aigrade->has_attempt()){
                 $this->do_forever_fail('No attempt could be found',$trace);
                 return;
             }

             if(!$aigrade->has_transcripts()){
                 $this->do_retry_fail('Transcript appears to not be ready yet',$trace);
                 return;
             }else{
                 //if we got here, we have transcripts and we do not need to come back
                 $trace->output("Transcripts are fetched for " . $cd->attemptid . " ...all ok");
                 return;
             }

         }else{
             $this->do_forever_fail('Unable to create AI grade for some reason',$trace);
             return;
         }
	}

	protected function do_retry_fail($reason,$trace){
        $trace->output($reason . ": will retry ");
        throw new \file_exception('retrievefileproblem', 'could not fetch transcripts.');
	 }

    protected function do_forever_fail($reason,$trace){
        $trace->output($reason . ": will not retry ");
	}
		
}

