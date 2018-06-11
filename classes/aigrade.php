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
        $passage = preg_replace('#\R+#', '</p><p>', $passage);
        $transcript = preg_replace('#\R+#', '</p><p>', $transcript);

        //remove punctuation
        //see https://stackoverflow.com/questions/5233734/how-to-strip-punctuation-in-php
        $passage = preg_replace("/(?![.=$'€%-])\p{P}/u", "", $passage);
        $transcript=preg_replace("/(?![.=$'€%-])\p{P}/u", "", $transcript);

        //split $passage and $transcript
        $passagebits = explode(' ',$passage);
        $transcriptbits = explode(' ',$transcript);
        if(count($transcriptbits)<count($passagebits)){
            $sessionendword = count($transcriptbits);
        }else{
            $sessionendword = count($passagebits);
        }

        //turn them into lines
        $line_passage = implode(' ',$passagebits);
        $line_transcript = implode(' ',$transcriptbits);

        //run diff engine
        $diffs = diff::compare($line_passage,$line_transcript);
        $errors = new \stdClass();
        $currentword=0;
        $errorcount = 0;
        foreach($diffs as $diff){
            $currentword++;
            if($diff[1] == Diff::DELETED){
                $errorcount++;
                $error = new \stdClass();
                $error->word=$diff[0];
                $error->wordnumber=$currentword;
                $errors->{$currentword}=$error;
            }
        }
        $sessionerrors = json_encode($errors);


        ////wpm score
        $wpmscore = round(($sessionendword - $errorcount) * 60 / $this->attemptdata->sessiontime);

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
        $gradingopts['sessiontime'] = $this->attemptdata->sessiontime;
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

}