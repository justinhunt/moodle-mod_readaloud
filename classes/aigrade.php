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
                $this->evaldata = $record;
            } else {
                $this->recordid = $this->create_record($attemptdata);
                if ($this->recordid) {
                    $record = $DB->get_record(MOD_READALOUD_AITABLE, array('attemptid' => $attemptid));
                    $this->evaldata = $record;
                }
            }
            if(!property_exists($record,'transcript') || empty($record->transcript)){
                if( $this->activitydata->region=='useast1') {
                    $this->update_transcripts();
                }
            }
        }
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
            $full_transcript = $this->curl_fetch($this->attemptdata->filename . '.json');
        }
        if($transcript && $full_transcript) {
            $record = new \stdClass();
            $record->id = $this->recordid;
            $record->transcript = $transcript;
            $record->full_transcript = $full_transcript;
            $DB->update_record(MOD_READALOUD_AITABLE, $record);

            $this->evaldata->transcript = $transcript;
            $this->evaldata->fulltranscript = $full_transcript;
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

}