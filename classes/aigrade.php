<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/11
 * Time: 22:28
 */

namespace mod_readaloud;

use \mod_readaloud\constants;

class aigrade
{
    function __construct($attemptid, $modulecontextid=0) {
        global $DB;
        $this->attemptid = $attemptid;
        $this->modulecontextid = $modulecontextid;
        $this->attemptdata = $DB->get_record(constants::MOD_READALOUD_USERTABLE,array('id'=>$attemptid));
        if($this->attemptdata) {
            $this->activitydata = $DB->get_record(constants::MOD_READALOUD_TABLE, array('id' => $this->attemptdata->readaloudid));
            $record = $DB->get_record(constants::MOD_READALOUD_AITABLE, array('attemptid' => $attemptid));
            if ($record) {
                $this->recordid = $record->id;
                $this->aidata = $record;
            } else {
                $this->recordid = self::create_record($this->attemptdata,$this->activitydata->timelimit);
                if ($this->recordid) {
                    $record = $DB->get_record(constants::MOD_READALOUD_AITABLE, array('attemptid' => $attemptid));
                    $this->aidata = $record;
                }
            }
            if(!$this->has_transcripts()){
                //if we do not have transcripts we try to fetch them
                    $success = $this->fetch_transcripts();
                    //if we got transcripts, right on man.
                    //we process them and update gradebook
                    if($success){
                        $this->do_diff();
                        $this->send_to_gradebook();
                    }
            }
        }else{
            //if there is no attempt we should not even be here
        }
    }

    //just a simple interface to manage returning read only property data
    public function aidetails($property){
        switch($property) {
            case 'sessionscore':
                $ret = $this->aidata->sessionscore;
                break;
            case 'sessionendword':
                $ret = $this->aidata->sessionendword;
                break;

            case 'sessionerrors':
                $ret = $this->aidata->sessionerrors;
                break;
            case 'wpm':
                $ret = $this->aidata->wpm;
                break;

            case 'sessiontime':
            $ret = $this->aidata->sessiontime;
            break;

            case 'sessionmatches':
                $ret = $this->aidata->sessionmatches;
                break;
        }
        return $ret;
    }

    //has attempt data. If not we really can not do much. Perhaps the attempt was deleted?
    public function has_attempt(){
        global $CFG;
        require_once($CFG->dirroot.'/mod/readaloud/lib.php');
        return $this->attemptdata ? true : false;
    }

    //we leave it up to the grading logic how/if it adds the ai grades to gradebook
    public function send_to_gradebook(){
        readaloud_update_grades($this->activitydata, $this->attemptdata->userid);
    }

    //do we have the AI transcripts
   public function has_transcripts(){
        return property_exists($this->aidata,'transcript') && !empty($this->aidata->transcript) && !empty($this->aidata->fulltranscript);
    }

    //do we have the AI at all
    public static function is_ai_enabled($moduleinstance){
       return utils::can_transcribe($moduleinstance);
    }

    //add an entry for the AI data for this attempt in the database
    //we will fill it up with data shortly
   public static function create_record($attemptdata,$timelimit){
        global $DB;
        $data = new \stdClass();
        $data->attemptid=$attemptdata->id;
        $data->courseid=$attemptdata->courseid;
        $data->readaloudid=$attemptdata->readaloudid;
        $data->sessiontime=$attemptdata->sessiontime ? $attemptdata->sessiontime:$timelimit;
        $data->transcript='';
        $data->sessionerrors='';
        $data->errorcount=0;
        $data->fulltranscript='';
        $data->timecreated=time();
        $data->timemodified=time();
        $recordid = $DB->insert_record(constants::MOD_READALOUD_AITABLE,$data);
        return $recordid;
    }


    //transcripts become ready in their own time, if they're ready update data and DB,
    // if not just report that back
   public function fetch_transcripts(){
        global $DB;
        $success = false;
        $transcript= false;
        if($this->attemptdata->filename && strpos($this->attemptdata->filename,'https')===0){
            $transcript = utils::curl_fetch($this->attemptdata->filename . '.txt');
            if(strpos($transcript,"<Error><Code>AccessDenied</Code>")>0){
                return false;
            }
            $fulltranscript = utils::curl_fetch($this->attemptdata->filename . '.json');
        }
        if($transcript ) {
            $record = new \stdClass();
            $record->id = $this->recordid;
            $record->transcript = diff::cleanText($transcript);
            $record->fulltranscript = $fulltranscript;
            $success = $DB->update_record(constants::MOD_READALOUD_AITABLE, $record);

            $this->aidata->transcript = $transcript;
            $this->aidata->fulltranscript =  $fulltranscript;
        }
        return $success;
    }

    //this is the serious stuff, this is the high level function that manages the comparison of transcript and passage
   public function do_diff(){
        global $DB;

        //turn the passage and transcript into an array of words
       $passagebits = diff::fetchWordArray($this->activitydata->passage);
       $alternatives = diff::fetchAlternativesArray($this->activitydata->alternatives);
       $transcriptbits = diff::fetchWordArray($this->aidata->transcript);

       //fetch sequences of transcript/pattern matched words
       // then prepare an array of "differences"
       $passagecount = count($passagebits);
       $sequences = diff::fetchSequences($passagebits,$transcriptbits,$alternatives);
       $diffs = diff::fetchDiffs($sequences,$passagecount);

       //from the array of differences build error data, match data, markers, scores and metrics
        $errors = new \stdClass();
        $matches = new \stdClass();
        $currentword=0;
        $lastunmodified=0;
        //loop through diffs
       // (could do a for loop here .. since diff count = passage words count for now index is $currentword
        foreach($diffs as $diff){
            $currentword++;
            switch($diff[0]){
                case Diff::UNMATCHED:
                    //we collect error info so we can count and display them on passage
                    $error = new \stdClass();
                    $error->word=$passagebits[$currentword-1];
                    $error->wordnumber=$currentword;
                    $errors->{$currentword}=$error;
                    break;

                case Diff::MATCHED:
                    //we collect match info so we can play audio from selected word
                    $match = new \stdClass();
                    $match->word=$passagebits[$currentword-1];
                    $match->pposition=$currentword;
                    $match->tposition = $diff[1];
                    $match->audiostart=0;//we will assess this from full transcript shortly
                    $match->audioend=0;//we will assess this from full transcript shortly
                    $matches->{$currentword}=$match;
                    $lastunmodified = $currentword;
                    break;

                default:
                    //do nothing
                    //should never get here

            }
        }
        $sessionendword = $lastunmodified;

        //discard errors that happen after session end word.
        $errorcount = 0;
        $finalerrors = new \stdClass();
        foreach($errors as $key=>$error) {
            if ($key < $sessionendword) {
                $finalerrors->{$key} = $error;
                $errorcount++;
            }
        }
        //finalise and serialise session errors
        $sessionerrors = json_encode($finalerrors);

        //also  capture match information for debugging and audio point matching
       //we can only map transcript to audio from match data
       $matches = utils::fetch_audio_points($this->aidata->fulltranscript, $matches,$alternatives);
       $sessionmatches = json_encode($matches);

       //session time
        $sessiontime = $this->attemptdata->sessiontime;
        if(!$sessiontime){
            if($this->activitydata->timelimit > 0){
                $sessiontime=$this->activitydata->timelimit;
            }else {
                //this is a guess, but really we need the audio duration. We just don't know it.
                //well .. we COULD get it from the end_time attribute of the final recognised word in the fulltranscript
                $sessiontime = 60;
            }
        }

        $scores = utils::processscores($sessiontime,$sessionendword,$errorcount,$this->activitydata->targetwpm);

        //save the diff and attempt analysis in the DB
        $record = new \stdClass();
        $record->id = $this->recordid;
        $record->sessionerrors = $sessionerrors;
        $record->errorcount = $errorcount;
        $record->sessionmatches = $sessionmatches;
        $record->sessionendword = $sessionendword;
        $record->accuracy = $scores->accuracyscore;
        $record->sessionscore = $scores->sessionscore;
        $record->wpm = $scores->wpmscore;
        $DB->update_record(constants::MOD_READALOUD_AITABLE, $record);

        //also uodate our internal data to prevent another db call to refresh data
       $this->aidata->sessionerrors = $sessionerrors;
       $this->aidata->errorcount = $errorcount;
       $this->aidata->sessionmatches = $sessionmatches;
       $this->aidata->sessionendword = $sessionendword;
       $this->aidata->accuracy = $scores->accuracyscore;
       $this->aidata->sessionscore = $scores->sessionscore;
       $this->aidata->wpm = $scores->wpmscore;
    }

}