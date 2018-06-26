<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_readaloud\report;

use \mod_readaloud\constants;

class gradingbyuser extends basereport
{

    protected $report="gradingbyuser";
    protected $fields = array('id','username','audiofile','wpm','accuracy_p','grade_p','gradenow','timecreated','deletenow');
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();



    public function fetch_formatted_heading(){
        $record = $this->headingdata;
        $ret='';
        if(!$record){return $ret;}
        $user = $this->fetch_cache('user',$record->userid);
        return get_string('gradingbyuserheading',constants::MOD_READALOUD_LANG,fullname($user));

    }

    public function process_raw_data($formdata){
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();
        $this->headingdata->userid = $formdata->userid;

        $emptydata = array();
        $alldata = $DB->get_records(constants::MOD_READALOUD_USERTABLE,array('readaloudid'=>$formdata->readaloudid,'userid'=>$formdata->userid),'id DESC');

        if($alldata){

            foreach($alldata as $thedata){
                $thedata->audiourl =  \mod_readaloud\utils::make_audio_URL($thedata->filename,$formdata->modulecontextid, constants::MOD_READALOUD_FRANKY,
                    constants::MOD_READALOUD_FILEAREA_SUBMISSIONS, $thedata->id);
                $this->rawdata[] = $thedata;
            }
        }else{
            $this->rawdata= $emptydata;
        }
        return true;
    }

}