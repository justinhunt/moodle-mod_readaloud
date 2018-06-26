<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_readaloud\report;

use \mod_readaloud\constants;

class attempts extends basereport
{

    protected $report="attempts";
    protected $fields = array('id','username','audiofile','wpm','accuracy_p','grade_p','timecreated','deletenow');
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();


    public function fetch_formatted_field($field,$record,$withlinks)
    {
        global $DB, $CFG, $OUTPUT;
        switch ($field) {
            case 'id':
                $ret = $record->id;
                break;

            case 'username':
                $user = $this->fetch_cache('user', $record->userid);
                $ret = fullname($user);
                break;

            case 'audiofile':
                if ($withlinks) {
                    /*
                    $ret = html_writer::tag('audio','',
                            array('controls'=>'','src'=>$record->audiourl));
                        */
                    $ret = \html_writer::div('<i class="fa fa-play-circle"></i>',
                        constants::MOD_READALOUD_HIDDEN_PLAYER_BUTTON, array('data-audiosource' => $record->audiourl));

                } else {
                    $ret = get_string('submitted', constants::MOD_READALOUD_LANG);
                }
                break;
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
    }

    public function fetch_formatted_heading(){
        $record = $this->headingdata;
        $ret='';
        if(!$record){return $ret;}
        //$ec = $this->fetch_cache(constants::MOD_READALOUD_TABLE,$record->englishcentralid);
        return get_string('attemptsheading',constants::MOD_READALOUD_LANG);

    }

    public function process_raw_data($formdata){
        global $DB;

        //heading data
        $this->headingdata = new \stdClass();

        $emptydata = array();
        $alldata = $DB->get_records(constants::MOD_READALOUD_USERTABLE,array('readaloudid'=>$formdata->readaloudid));

        if($alldata){
            foreach($alldata as $thedata){
                $thedata->audiourl  = \mod_readaloud\utils::make_audio_URL($thedata->filename,$formdata->modulecontextid, constants::MOD_READALOUD_FRANKY,
                    constants::MOD_READALOUD_FILEAREA_SUBMISSIONS, $thedata->id);
                $this->rawdata[] = $thedata;
            }
            $this->rawdata= $alldata;
        }else{
            $this->rawdata= $emptydata;
        }
        return true;
    }

}