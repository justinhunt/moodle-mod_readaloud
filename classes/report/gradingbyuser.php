<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_readaloud\report;

use \mod_readaloud\constants;
use \mod_readaloud\utils;

class gradingbyuser extends basereport
{

    protected $report="gradingbyuser";
    protected $fields = array('id','audiofile','wpm','accuracy_p','grade_p','grader','timecreated','gradenow','deletenow');
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

    public function fetch_formatted_field($field, $record, $withlinks)
    {
        global $DB, $CFG, $OUTPUT;
        switch ($field) {
            case 'id':
                $ret = $record->id;
                break;


            case 'audiofile':
                if ($withlinks) {
                    /*
                    $ret = html_writer::tag('audio','',
                            array('controls'=>'','src'=>$record->audiourl));
                        */
                    $ret = \html_writer::div('<i class="fa fa-play-circle"></i>', constants::MOD_READALOUD_HIDDEN_PLAYER_BUTTON, array('data-audiosource' => $record->audiourl));

                } else {
                    $ret = get_string('submitted', constants::MOD_READALOUD_LANG);
                }
                break;

            //WPM could hold either human or AI data
            case 'wpm':
                //if not human or ai graded
                if($record->sessiontime ==0 && !$record->wpm){
                    $ret = '';
                }else {
                    $ret = $record->wpm;
                }
                break;

            //accuracy could hold either human or ai data
            case 'accuracy_p':
                //if not human or ai graded
                if($record->sessiontime ==0 && !$record->wpm){
                    $ret = '';
                }else {
                    $ret = $record->accuracy;
                }
                break;

            //grade could hold either human or ai data
            case 'grade_p':
                //if not human or ai graded
                if($record->sessiontime ==0 && !$record->wpm){
                    $ret = '';
                }else {
                    $ret = $record->sessionscore;
                }
                break;

            case 'grader':
                if($record->sessiontime ==0 && $record->wpm){
                    $ret = get_string('grader_ai',constants::MOD_READALOUD_LANG);
                }else if($record->sessiontime){
                    $ret = get_string('grader_human',constants::MOD_READALOUD_LANG);
                }else{
                    $ret =get_string('grader_ungraded',constants::MOD_READALOUD_LANG);
                }
                break;

            case 'gradenow':
                if ($withlinks) {
                    $link = new \moodle_url(constants::MOD_READALOUD_URL . '/grading.php', array('action' => 'gradenow', 'n' => $record->readaloudid, 'attemptid' => $record->id));
                    $ret = \html_writer::link($link, get_string('gradenow', constants::MOD_READALOUD_LANG));
                } else {
                    $ret = get_string('cannotgradenow', constants::MOD_READALOUD_LANG);
                }
                break;


            case 'timecreated':
                $ret = date("Y-m-d H:i:s", $record->timecreated);
                break;

            case 'deletenow':
                if ($withlinks) {
                    $url = new \moodle_url(constants::MOD_READALOUD_URL . '/manageattempts.php',
                        array('action' => 'delete', 'n' => $record->readaloudid, 'attemptid' => $record->id, 'source' => $this->report));
                    $btn = new \single_button($url, get_string('delete'), 'post');
                    $btn->add_confirm_action(get_string('deleteattemptconfirm', constants::MOD_READALOUD_LANG));
                    $ret = $OUTPUT->render($btn);
                } else {
                    $ret = '';
                }
                break;

            default:
                if (property_exists($record, $field)) {
                    $ret = $record->{$field};
                } else {
                    $ret = '';
                }
        }
        return $ret;

    } //end of function


    public function process_raw_data($formdata)
    {
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();
        $this->rawdata = [];

        //heading data
        $this->headingdata->userid = $formdata->userid;

        $emptydata = array();
        $user_attempt_totals = array();
        $alldata = $DB->get_records(constants::MOD_READALOUD_USERTABLE, array('readaloudid' => $formdata->readaloudid, 'userid' => $formdata->userid), 'id DESC');

        //if we are not machine grading the SQL is simpler
        $human_sql = "SELECT tu.*  FROM {" . constants::MOD_READALOUD_USERTABLE . "} tu " .
            "WHERE tu.readaloudid=? " .
            "ORDER BY tu.id DESC";

        //if we are machine grading we need to fetch human and machine so we can get WPM etc from either
        $hybrid_sql="SELECT tu.*,tai.accuracy as aiaccuracy,tai.wpm as aiwpm, tai.sessionscore as aisessionscore  FROM {" . constants::MOD_READALOUD_USERTABLE . "} tu " .
            "INNER JOIN {". constants::MOD_READALOUD_AITABLE ."} tai ON tai.attemptid=tu.id " .
            "WHERE tu.readaloudid=? " .
            "ORDER BY tu.id DESC";

        //we need a module instance to know which scoring method we are using.
        $moduleinstance = $DB->get_record(constants::MOD_READALOUD_TABLE,array('id'=>$formdata->readaloudid));
        $cantranscribe = utils::can_transcribe($moduleinstance);

        //run the sql and match up WPM/ accuracy and sessionscore if we need to
        if($moduleinstance->machgrademethod==constants::MACHINEGRADE_MACHINE && $cantranscribe) {
            $alldata = $DB->get_records_sql($hybrid_sql, array($formdata->readaloudid));
            if($alldata) {
                //sessiontime is our indicator that a human grade has been saved.
                foreach ($alldata as $result) {
                    if (!$result->sessiontime) {
                        $result->wpm = $result->aiwpm;
                        $result->accuracy = $result->aiaccuracy;
                        $result->sessionscore = $result->aisessionscore;
                    }
                }
            }
        }else{
            $alldata =$DB->get_records_sql($human_sql, array($formdata->readaloudid));
        }

        if ($alldata) {
            foreach ($alldata as $thedata) {
                $thedata->audiourl = \mod_readaloud\utils::make_audio_URL($thedata->filename, $formdata->modulecontextid, constants::MOD_READALOUD_FRANKY,
                    constants::MOD_READALOUD_FILEAREA_SUBMISSIONS, $thedata->id);
                $this->rawdata[] = $thedata;
            }

        } else {
            $this->rawdata = $emptydata;
        }
        return true;
    }//end of function

}