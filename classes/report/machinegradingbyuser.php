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

class machinegradingbyuser extends basereport {
    protected $report = "machinegradingbyuser";
    protected $fields = ['id', 'audiofile', 'wpm', 'accuracy_p', 'grade_p', 'timecreated', 'review'];
    protected $headingdata = null;
    protected $qcache = [];
    protected $ucache = [];

    public function fetch_formatted_heading() {
        $record = $this->headingdata;
        $ret = '';
        if (!$record) {
            return $ret;
        }
        $user = $this->fetch_cache('user', $record->userid);
        return get_string('machinegradingbyuserheading', constants::M_COMPONENT, fullname($user));
    }

    public function fetch_formatted_field($field, $record, $withlinks) {
        global $DB, $CFG, $OUTPUT;
        switch ($field) {
            case 'id':
                $ret = $record->id;
                break;

            case 'audiofile':
                if ($withlinks) {
                    $ret = \html_writer::div(
                        '<i class="fa fa-play-circle"></i>',
                        constants::M_HIDDEN_PLAYER_BUTTON,
                        ['data-audiosource' => $record->audiourl]
                    );
                } else {
                    $ret = get_string('submitted', constants::M_COMPONENT);
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

            case 'review':
                // FOR NOW WE REFGRADE ... just temp. while fixing bogeys
                if ($withlinks) {
                    $link = new \moodle_url(
                        constants::M_URL . '/grading.php',
                        ['action' => 'machinereview', 'n' => $record->readaloudid, 'attemptid' => $record->attemptid]
                    );
                    $ret = \html_writer::link($link, get_string('review', constants::M_COMPONENT));
                } else {
                    $ret = get_string('cannotgradenow', constants::M_COMPONENT);
                }
                break;

            case 'timecreated':
                $ret = date("Y-m-d H:i:s", $record->timecreated);
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

    public function process_raw_data($formdata) {
        global $DB;

        // heading data
        $this->headingdata = new \stdClass();
        $this->rawdata = [];

        // heading data
        $this->headingdata->userid = $formdata->userid;

        $emptydata = [];
        $user_attempt_totals = [];
        $sql =
                "SELECT tai.id,tu.userid, tai.wpm, tai.accuracy,tu.timecreated,tai.attemptid, tai.sessionscore,tai.sessiontime,tai.sessionendword, tu.filename, tai.readaloudid  FROM {" .
                constants::M_AITABLE . "} tai INNER JOIN  {" . constants::M_USERTABLE . "}" .
                " tu ON tu.id =tai.attemptid AND tu.readaloudid=tai.readaloudid WHERE tu.readaloudid=? AND tu.userid=? ORDER BY 'tai.id DESC'";
        $alldata = $DB->get_records_sql($sql, [$formdata->readaloudid, $formdata->userid]);

        if ($alldata) {
            foreach ($alldata as $thedata) {
                $thedata->audiourl =
                        \mod_readaloud\utils::make_audio_URL(
                            $thedata->filename,
                            $formdata->modulecontextid,
                            constants::M_COMPONENT,
                            constants::M_FILEAREA_SUBMISSIONS,
                            $thedata->id
                        );
                $this->rawdata[] = $thedata;
            }
        } else {
            $this->rawdata = $emptydata;
        }
        return true;
    }//end of function
}
