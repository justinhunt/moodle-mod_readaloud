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
                $this->recordid = $this->create_record($this->attemptdata);
                if ($this->recordid) {
                    $record = $DB->get_record(constants::MOD_READALOUD_AITABLE, array('attemptid' => $attemptid));
                    $this->aidata = $record;
                }
            }
            if(!$this->has_transcripts()){
                if( $this->activitydata->region=='useast1') {
                    $success = $this->fetch_transcripts();
                    if($success){
                        $this->do_diff();
                    }
                }
            }
        }else{
            //if there is no attempt we should not even be here
        }
    }

    
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
        }
        return $ret;
    }

    //has attempt data. If not we really can not do much. Perhaps the attempt was deleted?
    public function has_attempt(){
        return $this->attemptdata ? true : false;
    }

    //do we have the AI transcripts
   public function has_transcripts(){
        return property_exists($this->aidata,'transcript') && !empty($this->aidata->transcript);
    }

    //do we have the AI at all
    public static function is_ai_enabled($moduleinstance){
       return utils::can_transcribe($moduleinstance);
    }

   protected function create_record($attemptdata){
        global $DB;
        $data = new \stdClass();
        $data->attemptid=$attemptdata->id;
        $data->courseid=$attemptdata->courseid;
        $data->readaloudid=$attemptdata->readaloudid;
        $data->sessiontime=$attemptdata->sessiontime ? $attemptdata->sessiontime:$this->activitydata->timelimit;
        $data->transcript='';
        $data->sessionerrors='';
        $data->fulltranscript='';
        $data->timecreated=time();
        $data->timemodified=time();
        $recordid = $DB->insert_record(constants::MOD_READALOUD_AITABLE,$data);
        $this->recordid=$recordid;
        return $recordid;
    }


   public function fetch_transcripts(){
        global $DB;
        $success = false;
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
            $record->transcript = $transcript;
            $record->fulltranscript = $fulltranscript;
            $success = $DB->update_record(constants::MOD_READALOUD_AITABLE, $record);

            $this->aidata->transcript = $transcript;
            $this->aidata->fulltranscript = '';
        }
        return $success;
    }

   public function do_diff(){
        global $DB;

        //lowercase both
        $passage =strtolower($this->activitydata->passage);
        $transcript=strtolower($this->aidata->transcript);

        //turn passage into html only
        $passage = strip_tags($passage);

        //replace all line ends with spaces
        $passage = preg_replace('#\R+#', ' ', $passage);
        $transcript = preg_replace('#\R+#', ' ', $transcript);

        //remove punctuation
        //see https://stackoverflow.com/questions/5233734/how-to-strip-punctuation-in-php
        $passage = preg_replace("#[[:punct:]]#", "", $passage);
        $transcript=preg_replace("#[[:punct:]]#", "", $transcript);

        //split $passage and $transcript
        $passagebits = explode(' ',$passage);
        $transcriptbits = explode(' ',$transcript);

        //remove any empty elements
        $passagebits = array_filter($passagebits, function($value) { return $value !== ''; });
        $transcriptbits= array_filter($transcriptbits, function($value) { return $value !== ''; });

        //turn them into lines
        $line_passage = implode(' ',$passagebits);
        $line_transcript = implode(' ',$transcriptbits);

       //use this to debug and stop on a certain word and see what is happening
       $passagecount = count(explode(' ', $line_passage));
       $sequences = diff::fetchSequences($line_passage,$line_transcript);
       $diffs = diff::processSequences($sequences,$passagecount);

       //run diff engine
       // $diffs = diff::compare($line_passage,$line_transcript);



        $errors = new \stdClass();
        $currentword=0;
        $lastunmodified=0;
        foreach($diffs as $diff){

          //  switch($diff[1]){
            switch($diff){
                case Diff::DELETED:
                    $currentword++;
                    $error = new \stdClass();
                    $error->word="";//$diff[0];
                    $error->wordnumber=$currentword;
                    $errors->{$currentword}=$error;
                    break;
                case Diff::UNMODIFIED:
                    //we need to track which word in the passage is the error
                    //currentword increments on deleted or good, so we keep on sync with passage
                    //but must not add "diff:inserted" (that would take us out of sync)
                    $currentword++;
                    $lastunmodified = $currentword;
                    break;
                case Diff::INSERTED:
                    //do nothing

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

       ////wpm score
       if($sessiontime > 0) {
           $wpmscore = round(($sessionendword - $errorcount) * 60 / $sessiontime);
       }else{
           $wpmscore =0;
       }

        //accuracy score
       if($sessionendword > 0) {
           $accuracyscore = round(($sessionendword - $errorcount) / $sessionendword * 100);
       }else{
           $accuracyscore=0;
       }

        //sessionscore
        $usewpmscore = $wpmscore;
        if($usewpmscore > $this->activitydata->targetwpm){
            $usewpmscore = $this->activitydata->targetwpm;
        }
        $sessionscore = round($usewpmscore/$this->activitydata->targetwpm * 100);

        $record = new \stdClass();
        $record->id = $this->recordid;
        $record->sessionerrors = $sessionerrors;
        $record->sessionendword = $sessionendword;
        $record->accuracy = $accuracyscore;
        $record->sessionscore = $sessionscore;
        $record->wpm = $wpmscore;
        $DB->update_record(constants::MOD_READALOUD_AITABLE, $record);
    }


    public function prepare_javascript(){
        global $PAGE;

        //here we set up any info we need to pass into javascript
        $gradingopts =Array();
        $gradingopts['reviewmode'] = false;
        $gradingopts['enabletts'] = get_config(constants::MOD_READALOUD_FRANKY,'enabletts');
        $gradingopts['allowearlyexit'] = $this->activitydata->allowearlyexit ? true :false;
        $gradingopts['timelimit'] = $this->activitydata->timelimit;
        $gradingopts['ttslanguage'] = $this->activitydata->ttslanguage;
        $gradingopts['activityid'] = $this->activitydata->id;
        $gradingopts['targetwpm'] = $this->activitydata->targetwpm;
        $gradingopts['sesskey'] = sesskey();
        $gradingopts['attemptid'] = $this->attemptdata->id;
        $gradingopts['sessiontime'] = $this->aidata->sessiontime;
        $gradingopts['sessionerrors'] = $this->aidata->sessionerrors;
        $gradingopts['sessionendword'] = $this->aidata->sessionendword;
        $gradingopts['wpm'] = $this->aidata->wpm;
        $gradingopts['accuracy'] = $this->aidata->accuracy;
        $gradingopts['sessionscore'] = $this->aidata->sessionscore;
        $gradingopts['opts_id'] = 'mod_readaloud_gradenowopts';


        $jsonstring = json_encode($gradingopts);
        $opts_html = \html_writer::tag('input', '', array('id' => $gradingopts['opts_id'], 'type' => 'hidden', 'value' => $jsonstring));
        $PAGE->requires->js_call_amd("mod_readaloud/gradenowhelper", 'init', array(array('id'=>$gradingopts['opts_id'])));
        //these need to be returned and echo'ed to the page
        return $opts_html;

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
                $ret = \mod_readaloud\utils::make_audio_URL($this->attemptdata->filename,$this->modulecontextid, constants::MOD_READALOUD_FRANKY,
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

}