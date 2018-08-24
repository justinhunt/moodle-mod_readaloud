<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_readaloud\report;

use \mod_readaloud\constants;

class machinegrading extends basereport
{

    protected $report = "machinegrading";
    protected $fields = array('id', 'username', 'audiofile', 'totalattempts', 'wpm', 'accuracy_p', 'grade_p', 'review', 'timecreated','gradenow');
    protected $headingdata = null;
    protected $qcache = array();
    protected $ucache = array();


    public function fetch_formatted_field($field, $record, $withlinks)
    {
        global $DB, $CFG, $OUTPUT;
        switch ($field) {
            case 'id':
                $ret = $record->id;
                break;

            case 'username':
                $user = $this->fetch_cache('user', $record->userid);
                $ret = fullname($user);
                if ($withlinks) {
                    $link = new \moodle_url(constants::MOD_READALOUD_URL . '/grading.php',
                        array('action' => 'gradingbyuser', 'n' => $record->readaloudid, 'userid' => $record->userid));
                    $ret = \html_writer::link($link, $ret);
                }
                break;

            case 'totalattempts':
                $ret = $record->totalattempts;
                if ($withlinks) {
                    $link = new \moodle_url(constants::MOD_READALOUD_URL . '/grading.php',
                        array('action' => 'gradingbyuser', 'n' => $record->readaloudid, 'userid' => $record->userid));
                    $ret = \html_writer::link($link, $ret);
                }
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

            case 'wpm':
                $ret = $record->wpm;
                break;

            case 'accuracy_p':
                $ret = $record->accuracy;
                break;

            case 'grade_p':
                $ret = $record->sessionscore;
                break;

            case 'gradenow':
                if ($withlinks) {
                    $url = new \moodle_url(constants::MOD_READALOUD_URL . '/grading.php', array('action' => 'gradenow', 'n' => $record->readaloudid, 'attemptid' => $record->attemptid));
                    $btn = new \single_button($url, get_string('gradenow', constants::MOD_READALOUD_LANG), 'post');
                    $ret = $OUTPUT->render($btn);
                } else {
                    $ret = get_string('cannotgradenow', constants::MOD_READALOUD_LANG);
                }
                break;

            case 'regrade':

                //FOR  REGRADE ... when fixing bogeys (replace review link with this one)
                if ($withlinks) {
                    $link = new \moodle_url(constants::MOD_READALOUD_URL . '/grading.php', array('action' => 'regradenow', 'n' => $record->readaloudid, 'attemptid' => $record->attemptid));
                    $ret = \html_writer::link($link, 'REGRADE');
                } else {
                    $ret = get_string('cannotgradenow', constants::MOD_READALOUD_LANG);
                }
                break;

            case 'review':

                //FOR NOW WE REFGRADE ... just temp. while fixing bogeys
                if ($withlinks) {
                    $link = new \moodle_url(constants::MOD_READALOUD_URL . '/grading.php', array('action' => 'machinereview', 'n' => $record->readaloudid, 'attemptid' => $record->attemptid));
                    $ret = \html_writer::link($link, get_string('review', constants::MOD_READALOUD_LANG));
                } else {
                    $ret = get_string('cannotgradenow', constants::MOD_READALOUD_LANG);
                }
                break;

            case 'timecreated':
                $ret = date("Y-m-d H:i:s", $record->timecreated);
                break;

                //do we need this..? hid it for now
            case 'deletenow':
                $url = new \moodle_url(constants::MOD_READALOUD_URL . '/manageattempts.php',
                    array('action' => 'delete', 'n' => $record->readaloudid, 'attemptid' => $record->id, 'source' => $this->report));
                $btn = new \single_button($url, get_string('delete'), 'post');
                $btn->add_confirm_action(get_string('deleteattemptconfirm', constants::MOD_READALOUD_LANG));
                $ret = $OUTPUT->render($btn);
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


    public function fetch_formatted_heading()
    {
        $record = $this->headingdata;
        $ret = '';
        if (!$record) {
            return $ret;
        }
        return get_string('gradingheading', constants::MOD_READALOUD_LANG);

    }//end of function

    public function process_raw_data($formdata)
    {
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();
        $this->rawdata = [];

        $emptydata = array();
        $maxfield = 'id';
        $user_attempt_totals = array();
        $sql = "SELECT tai.id,tu.userid, tai.wpm, tai.accuracy,tu.timecreated,tai.attemptid, tai.sessionscore,tai.sessiontime,tai.sessionendword, tu.filename, tai.readaloudid  FROM {" . constants::MOD_READALOUD_AITABLE . "} tai INNER JOIN  {" . constants::MOD_READALOUD_USERTABLE . "}" .
            " tu ON tu.id =tai.attemptid AND tu.readaloudid=tai.readaloudid WHERE tu.readaloudid=? ORDER BY 'tu.userid, tai." . $maxfield . " DESC'";
        $alldata = $DB->get_records_sql($sql, array($formdata->readaloudid));

        if ($alldata) {

            foreach ($alldata as $thedata) {

                //we ony take the max (attempt, accuracy, wpm ..)
                if (array_key_exists($thedata->userid, $user_attempt_totals)) {
                    $user_attempt_totals[$thedata->userid] = $user_attempt_totals[$thedata->userid] + 1;
                }else{
                    $user_attempt_totals[$thedata->userid] = 1;
                }
                if(array_key_exists($thedata->userid,$this->rawdata)){
                    if($this->rawdata[$thedata->userid]->{$maxfield} >= $thedata->{$maxfield}){
                        continue;
                    }
                }
                $thedata->audiourl = \mod_readaloud\utils::make_audio_URL($thedata->filename, $formdata->modulecontextid, constants::MOD_READALOUD_FRANKY,
                    constants::MOD_READALOUD_FILEAREA_SUBMISSIONS, $thedata->id);
                $this->rawdata[$thedata->userid] = $thedata;
            }
            foreach ($this->rawdata as $thedata) {
                $thedata->totalattempts = $user_attempt_totals[$thedata->userid];
            }
        } else {
            $this->rawdata = $emptydata;
        }
        return true;
    }//end of function
}//end of class