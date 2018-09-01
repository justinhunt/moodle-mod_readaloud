<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_readaloud\report;

use \mod_readaloud\constants;

class grading extends basereport
{

    protected $report = "grading";
    protected $fields = array('id', 'username', 'audiofile', 'totalattempts', 'wpm', 'accuracy_p', 'grade_p', 'gradenow', 'timecreated', 'deletenow');
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
                if($record->sessiontime ==0){
                    $ret = '';
                }else {
                    $ret = $record->wpm;
                }
                break;

            case 'accuracy_p':
                if($record->sessiontime ==0){
                    $ret = '';
                }else {
                    $ret = $record->accuracy;
                }
                break;

            case 'grade_p':
                if($record->sessiontime ==0){
                    $ret = '';
                }else {
                    $ret = $record->sessionscore;
                }
                break;

            case 'gradenow':
                if ($withlinks) {

                    if($record->sessiontime ==0) {
                        $buttonclasses= 'btn btn-default';
                        $buttonlabel = get_string('gradenow', constants::MOD_READALOUD_LANG);
                    }else{
                        $buttonclasses= '';
                        $buttonlabel= get_string('regrade', constants::MOD_READALOUD_LANG);
                    }
                    $link = new \moodle_url(constants::MOD_READALOUD_URL . '/grading.php', array('action' => 'gradenow', 'n' => $record->readaloudid, 'attemptid' => $record->id));
                    $ret = \html_writer::link($link, $buttonlabel,array('class'=>$buttonclasses));
                } else {
                    $ret = get_string('cannotgradenow', constants::MOD_READALOUD_LANG);
                }
                break;

                //this will load AI data and start from there, currently hidden from menu to keep it simple.
            case 'aigradenow':
                if ($withlinks) {
                    $link = new \moodle_url(constants::MOD_READALOUD_URL . '/grading.php', array('action' => 'aigradenow', 'n' => $record->readaloudid, 'attemptid' => $record->id));
                    $ret = \html_writer::link($link, get_string('aigradenow', constants::MOD_READALOUD_LANG));
                } else {
                    $ret = get_string('cannotgradenow', constants::MOD_READALOUD_LANG);
                }
                break;


            case 'timecreated':
                $ret = date("Y-m-d H:i:s", $record->timecreated);
                break;

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
        //$ec = $this->fetch_cache(constants::MOD_READALOUD_TABLE,$record->englishcentralid);
        return get_string('gradingheading', constants::MOD_READALOUD_LANG);

    }//end of function

    public function process_raw_data($formdata)
    {
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();

        $emptydata = array();
        $user_attempt_totals = array();

        $sql = "SELECT tu.*  FROM {" . constants::MOD_READALOUD_USERTABLE . "} tu INNER JOIN {user} u ON tu.userid=u.id WHERE tu.readaloudid=?" .
            " ORDER BY u.lastnamephonetic,u.firstnamephonetic,u.lastname,u.firstname,u.middlename,u.alternatename,tu.id DESC";
        $alldata = $DB->get_records_sql($sql, array($formdata->readaloudid));


        if ($alldata) {

            foreach ($alldata as $thedata) {

                //we ony take the most recent attempt
                if (array_key_exists($thedata->userid, $user_attempt_totals)) {
                    $user_attempt_totals[$thedata->userid] = $user_attempt_totals[$thedata->userid] + 1;
                    continue;
                }
                $user_attempt_totals[$thedata->userid] = 1;

                $thedata->audiourl = \mod_readaloud\utils::make_audio_URL($thedata->filename, $formdata->modulecontextid, constants::MOD_READALOUD_FRANKY,
                    constants::MOD_READALOUD_FILEAREA_SUBMISSIONS, $thedata->id);
                $this->rawdata[] = $thedata;
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