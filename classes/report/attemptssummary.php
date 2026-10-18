<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 20:52
 */

namespace mod_readaloud\report;

use mod_readaloud\constants;
use mod_readaloud\utils;

class attemptssummary extends basereport {
    protected $report = "attemptssummary";
    protected $fields = ['id', 'username', 'totalattempts', 'av_wpm', 'av_accuracy_p', 'av_readgrade_p',
            'av_quizscore_p', 'h_wpm', 'h_accuracy_p', 'h_readgrade_p', 'h_quizscore_p'];
    protected $headingdata = null;
    protected $qcache = [];
    protected $ucache = [];
    protected $qenabled = true;

    public function __construct($moduleinstance, $cm) {
        parent::__construct($moduleinstance, $cm);
        // If there is no quiz, remove quiz fields
        if (!$this->quizhelper->quiz_enabled()) {
            $this->qenabled = false;
            $this->fields = array_filter($this->fields, function ($key) {
                return !in_array($this->fields[$key], ['av_quizscore_p', 'h_quizscore_p']);
            }, ARRAY_FILTER_USE_KEY);
        }
    }

    public function fetch_formatted_field($field, $record, $withlinks) {
        global $DB, $CFG, $OUTPUT;

        switch ($field) {
            case 'id':
                $ret = $record->userid;
                break;

            case 'username':
                $user = $this->fetch_cache('user', $record->userid);
                $ret = fullname($user);
                if ($withlinks) {
                    $link = new \moodle_url(
                        constants::M_URL . '/grading.php',
                        ['action' => 'gradingbyuser', 'n' => $record->readaloudid, 'userid' => $record->userid]
                    );
                    $ret = \html_writer::link($link, $ret);
                }
                break;

            case 'totalattempts':
                $ret = $record->totalattempts;
                if ($withlinks) {
                    $link = new \moodle_url(
                        constants::M_URL . '/grading.php',
                        ['action' => 'gradingbyuser', 'n' => $record->readaloudid, 'userid' => $record->userid]
                    );
                    $ret = \html_writer::link($link, $ret);
                }
                break;

            // WPM.
            case 'av_wpm':
                $ret = $record->av_wpm;
                break;

            // Accuracy.
            case 'av_accuracy_p':
                $ret = $record->av_accuracy;
                break;

            // Read grade.
            case 'av_readgrade_p':
                $ret = $record->av_sessionscore;
                break;

            // Quiz grade.
            case 'av_quizscore_p':
                $ret = $record->av_quizscore;
                break;

            // Highest WPM.
            case 'h_wpm':
                $ret = $record->h_wpm;
                break;

            // Highest accuracy
            case 'h_accuracy_p':
                $ret = $record->h_accuracy;
                break;

            // Highest grade
            case 'h_readgrade_p':
                $ret = $record->h_sessionscore;
                break;

             // Highest grade
            case 'h_quizscore_p':
                $ret = $record->h_quizscore;
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

    public function fetch_formatted_heading() {
        $record = $this->headingdata;
        $ret = '';
        if (!$record) {
            return $ret;
        }
        // $ec = $this->fetch_cache(constants::M_TABLE,$record->englishcentralid);
        return get_string('attemptssummaryheading', constants::M_COMPONENT);
    }//end of function

    public function fetch_formatted_description() {

        return get_string('attemptssummary_explanation', constants::M_COMPONENT);
    }

    public function process_raw_data($formdata) {
        global $DB, $USER;

        // heading data
        $this->headingdata = new \stdClass();

        $emptydata = [];
        $user_totals = [];

        // Groups stuff
        $moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $formdata->readaloudid]);
        $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance(constants::M_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
        $groupsmode = groups_get_activity_groupmode($cm, $course);
        $context = empty($cm) ? \context_course::instance($course->id) : \context_module::instance($cm->id);
        $supergrouper = has_capability('moodle/site:accessallgroups', $context, $USER->id);

        // if we need to show  groups
        if ($formdata->groupid > 0) {
            [$groupswhere, $sqlparams] = $DB->get_in_or_equal($formdata->groupid);

            // if we are not machine grading the SQL is simpler
            $human_sql = "SELECT tu.*, false as fulltranscript  FROM {" . constants::M_USERTABLE .
                    "} tu INNER JOIN {user} u ON tu.userid=u.id " .
                    " INNER JOIN {groups_members} gm ON tu.userid=gm.userid " .
                    " WHERE gm.groupid $groupswhere AND tu.readaloudid=?  AND tu.dontgrade = 0 " .
                    " ORDER BY u.lastnamephonetic,u.firstnamephonetic,u.lastname,u.firstname,u.middlename,u.alternatename,tu.id DESC";

            // if we are machine grading we need to fetch human and machine so we can get WPM etc from either
            $hybrid_sql =
                    "SELECT tu.*,tai.accuracy as aiaccuracy,tai.wpm as aiwpm, tai.sessionscore as aisessionscore,tai.fulltranscript as fulltranscript FROM {" .
                    constants::M_USERTABLE . "} tu INNER JOIN {user} u ON tu.userid=u.id " .
                    " INNER JOIN {" . constants::M_AITABLE . "} tai ON tai.attemptid=tu.id " .
                    " INNER JOIN {groups_members} gm ON tu.userid=gm.userid " .
                    " WHERE gm.groupid $groupswhere AND tu.readaloudid=?  AND tu.dontgrade = 0 " .
                    " ORDER BY u.lastnamephonetic,u.firstnamephonetic,u.lastname,u.firstname,u.middlename,u.alternatename,tu.id DESC";
        } else {
            $sqlparams = [];
            // if we are not machine grading the SQL is simpler
            $human_sql = "SELECT tu.*, false as fulltranscript  FROM {" . constants::M_USERTABLE .
                    "} tu INNER JOIN {user} u ON tu.userid=u.id WHERE tu.readaloudid=?  AND tu.dontgrade = 0 " .
                    " ORDER BY u.lastnamephonetic,u.firstnamephonetic,u.lastname,u.firstname,u.middlename,u.alternatename,tu.id DESC";

            // if we are machine grading we need to fetch human and machine so we can get WPM etc from either
            $hybrid_sql =
                    "SELECT tu.*,tai.accuracy as aiaccuracy,tai.wpm as aiwpm, tai.sessionscore as aisessionscore,tai.fulltranscript as fulltranscript FROM {" .
                    constants::M_USERTABLE . "} tu INNER JOIN {user} u ON tu.userid=u.id " .
                    "INNER JOIN {" . constants::M_AITABLE . "} tai ON tai.attemptid=tu.id " .
                    "WHERE tu.readaloudid=?  AND tu.dontgrade = 0 " .
                    " ORDER BY u.lastnamephonetic,u.firstnamephonetic,u.lastname,u.firstname,u.middlename,u.alternatename,tu.id DESC";
        }

        // we need a module instance to know which scoring method we are using.
        $sqlparams[] = $formdata->readaloudid;
        $moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $formdata->readaloudid]);
        $cantranscribe = utils::can_transcribe($moduleinstance);

        // run the sql and match up WPM accuracy and sessionscore if we need to
        if (
            ($moduleinstance->machgrademethod == constants::MACHINEGRADE_HYBRID ||
                        $moduleinstance->machgrademethod == constants::MACHINEGRADE_MACHINEONLY) &&
                $cantranscribe
        ) {
            $alldata = $DB->get_records_sql($hybrid_sql, $sqlparams);
            if ($alldata) {
                // sessiontime is our indicator that a human grade has been saved.
                foreach ($alldata as $result) {
                    if (!$result->sessiontime || $moduleinstance->machgrademethod == constants::MACHINEGRADE_MACHINEONLY) {
                        $result->wpm = $result->aiwpm;
                        $result->accuracy = $result->aiaccuracy;
                        $result->sessionscore = $result->aisessionscore;
                    }
                }
            }
        } else {
            $alldata = $DB->get_records_sql($human_sql, $sqlparams);
        }

        // loop through data
        if ($alldata) {
            foreach ($alldata as $thedata) {
                // if no previously counted attempts
                // its just the single attempts data
                if (!array_key_exists($thedata->userid, $user_totals)) {
                    $totals = new \stdClass();
                    $totals->userid = $thedata->userid;
                    $totals->readaloudid = $thedata->readaloudid;
                    $totals->total_wpm = $thedata->wpm;
                    $totals->h_wpm = $thedata->wpm;
                    $totals->total_accuracy = $thedata->accuracy;
                    $totals->h_accuracy = $thedata->accuracy;
                    $totals->total_sessionscore = $thedata->sessionscore;
                    $totals->h_sessionscore = $thedata->sessionscore;
                    $totals->total_quizscore = $thedata->qscore;
                    $totals->h_quizscore = $thedata->qscore;
                    $totals->totalattempts = 1;
                    $user_totals[$thedata->userid] = $totals;
                    continue;
                    // otherwise increment totals, and figure out 'highest'
                } else {
                    $totals = $user_totals[$thedata->userid];
                    $totals->total_wpm += $thedata->wpm;
                    $totals->h_wpm = max($totals->h_wpm, $thedata->wpm);
                    $totals->total_accuracy += $thedata->accuracy;
                    $totals->h_accuracy = max($totals->h_accuracy, $thedata->accuracy);
                    $totals->total_sessionscore += $thedata->sessionscore;
                    $totals->h_sessionscore = max($totals->h_sessionscore, $thedata->sessionscore);
                    $totals->total_quizscore += $thedata->qscore;
                    $totals->h_quizscore = max($totals->h_quizscore, $thedata->qscore);
                    $totals->totalattempts++;
                    continue;
                }
            }
            // calc averages and set to raw data
            foreach ($user_totals as $oneuser) {
                $oneuser->av_wpm = round($oneuser->total_wpm / $oneuser->totalattempts, 1);
                $oneuser->av_accuracy = round($oneuser->total_accuracy / $oneuser->totalattempts, 1);
                $oneuser->av_sessionscore = round($oneuser->total_sessionscore / $oneuser->totalattempts, 1);
                $oneuser->av_quizscore = round($oneuser->total_quizscore / $oneuser->totalattempts, 1);
                $this->rawdata[] = $oneuser;
            }
        } else {
            $this->rawdata = $emptydata;
        }
        return true;
    }//end of function
}//end of class
