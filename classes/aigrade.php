<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/11
 * Time: 22:28
 */

namespace mod_readaloud;



class aigrade
{
    function __construct($attemptid, $modulecontextid=0) {
        global $DB;
        $this->attemptid = $attemptid;
        $this->modulecontextid = $modulecontextid;
        $attemptdata = $DB->get_record(MOD_READALOUD_USERTABLE,array('id'=>$attemptid));
        if($attemptdata) {
            $this->attemptdata = $attemptdata;
            $this->activitydata = $DB->get_record(MOD_READALOUD_TABLE, array('id' => $attemptdata->readaloudid));
            $record = $DB->get_record(MOD_READALOUD_AITABLE, array('attemptid' => $attemptid));
            if ($record) {
                $this->recordid = $record->id;
                $this->aidata = $record;
            } else {
                $this->recordid = $this->create_record($attemptdata);
                if ($this->recordid) {
                    $record = $DB->get_record(MOD_READALOUD_AITABLE, array('attemptid' => $attemptid));
                    $this->aidata = $record;
                }
            }
            if(!property_exists($record,'transcript') || empty($record->transcript)){
                if( $this->activitydata->region=='useast1') {
                    $this->update_transcripts();
                }
            }
            if(!$this->aidata->sessionendword){
                $this->do_diff();
            }
        }
    }
    
    public function aidetails($property){
        global $DB;
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

     function does_exist($attemptid){
        global $DB;
        $exists = $DB->record_exists(MOD_READALOUD_AITABLE,array('attemptid'=>$attemptid));
        return $exists;
    }

    function create_record($attemptdata){
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
        $recordid = $DB->insert_record(MOD_READALOUD_AITABLE,$data);
        $this->recordid=$recordid;
        return $recordid;
    }


    function update_transcripts(){
        global $DB;
        if($this->attemptdata->filename && strpos($this->attemptdata->filename,'https')===0){
            $transcript = $this->curl_fetch($this->attemptdata->filename . '.txt');
            //$full_transcript = $this->curl_fetch($this->attemptdata->filename . '.json');
        }
        if($transcript ) {
            $record = new \stdClass();
            $record->id = $this->recordid;
            $record->transcript = $transcript;
            $record->full_transcript = '';
            $DB->update_record(MOD_READALOUD_AITABLE, $record);

            $this->aidata->transcript = $transcript;
            $this->aidata->fulltranscript = '';
        }
    }

    function curl_fetch($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }

    function do_diff(){
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

        //run diff engine
        $diffs = diff::compare($line_passage,$line_transcript);
        $errors = new \stdClass();
        $currentword=0;
        $lastunmodified=0;
        foreach($diffs as $diff){

            switch($diff[1]){
                case Diff::DELETED:
                    $currentword++;
                    $error = new \stdClass();
                    $error->word=$diff[0];
                    $error->wordnumber=$currentword;
                    $errors->{$currentword}=$error;
                    break;
                case Diff::UNMODIFIED:
                    //we need to track which word in the passage is the error
                    //currentword increments on deleted or good, so we keep on sync with passage
                    //but must not add inserted (that would take us out of sync
                    $currentword++;
                    $lastunmodified = $currentword;
                    break;
                case Diff::INSERTED:
                    //do nothing

            }
        }
        $sessionendword = $lastunmodified;

        //discard errors after session end word.
        $errorcount = 0;
        $finalerrors = new \stdClass();
        foreach($errors as $key=>$error) {
            if ($key < $sessionendword) {
                $finalerrors->{$key} = $error;
                $errorcount++;
            }
        }
        $sessionerrors = json_encode($finalerrors);

        ////wpm score
        $sessiontime = $this->attemptdata->sessiontime;
        if(!$sessiontime){$sessiontime=60;}
        $wpmscore = round(($sessionendword - $errorcount) * 60 / $sessiontime);

        //accuracy score
        $accuracyscore = round(($sessionendword - $errorcount)/$sessionendword * 100);


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
        $DB->update_record(MOD_READALOUD_AITABLE, $record);
    }


    public function prepare_javascript(){
        global $PAGE;

        //here we set up any info we need to pass into javascript
        $gradingopts =Array();
        $gradingopts['reviewmode'] = false;
        $gradingopts['enabletts'] = get_config(MOD_READALOUD_FRANKY,'enabletts');
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
                $ret = \mod_readaloud\utils::make_audio_URL($this->attemptdata->filename,$this->modulecontextid, MOD_READALOUD_FRANKY,
                    MOD_READALOUD_FILEAREA_SUBMISSIONS,
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
        $records = $DB->get_records_select(MOD_READALOUD_USERTABLE,$where,array(),' id ASC');
        if($records){
            $rec = array_shift($records);
            return $rec->id;
        }else{
            return false;
        }
    }

}