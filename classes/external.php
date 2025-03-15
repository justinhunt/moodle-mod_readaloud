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
 * External class.
 *
 * @package mod_readaloud
 * @author  Justin Hunt - Poodll.com
 */

global $CFG;

// This is for pre M4.0 and post M4.0 to work on same code base
require_once($CFG->libdir . '/externallib.php');

/*
 * This is for M4.0 and later
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
*/

use mod_readaloud\utils;
use mod_readaloud\diff;
use mod_readaloud\alphabetconverter;
use mod_readaloud\constants;


class mod_readaloud_external extends external_api {

    public static function check_for_results_parameters() {
        return new external_function_parameters([
                'attemptid' => new external_value(PARAM_INT),
        ]);
    }

    public static function check_for_results($attemptid) {
        global $DB, $USER;
        // defaults
        $ret = ['ready' => false, 'rating' => 0, 'src' => ''];
        $havehumaneval = false;
        $haveaieval = false;
        $aigrade = false;

        $params = self::validate_parameters(self::check_for_results_parameters(),
                ['attemptid' => $attemptid]);

        // fetch attempt information
        $attempt = $DB->get_record(constants::M_USERTABLE, ['userid' => $USER->id, 'id' => $attemptid]);
        if ($attempt) {
            $readaloud = $DB->get_record('readaloud', ['id' => $attempt->readaloudid], '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('readaloud', $readaloud->id, $readaloud->course, false, MUST_EXIST);

            if (\mod_readaloud\utils::can_transcribe($readaloud)) {
                $aigrade = new \mod_readaloud\aigrade($attempt->id, $cm->id);
            } else {
                $aigrade = false;
            }

            $havehumaneval = $attempt->sessiontime != null;
            $haveaieval = $aigrade && $aigrade->has_transcripts();
        }

        // If no results, thats that. return.
        if (!$haveaieval && !$havehumaneval) {
            // Just return defaults.
            // If we got results return ratings.
        } else {
            $ret['ready'] = true;
            $ret['src'] = $attempt->filename;
            // stars
            $ret['rating'] = utils::fetch_rating($attempt, $aigrade);
            // stats
            $stats = utils::fetch_small_reportdata($attempt, $aigrade);
            $ret['wpm'] = $stats->wpm;
            $ret['acc'] = $stats->accuracy;
            $ret['totalwords'] = $stats->sessionendword;
        }
        return json_encode($ret);
    }

    public static function check_for_results_returns() {
        return new external_value(PARAM_RAW);
    }

    public static function submit_regular_attempt_parameters() {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT),
                'filename' => new external_value(PARAM_TEXT),
                'rectime' => new external_value(PARAM_INT),
                 'shadowing' => new external_value(PARAM_INT),
        ]);
    }

    public static function submit_regular_attempt($cmid, $filename, $rectime, $shadowing) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::submit_regular_attempt_parameters(),
                ['cmid' => $cmid, 'filename' => $filename, 'rectime' => $rectime, 'shadowing' => $shadowing]);

        $cm = get_coursemodule_from_id('readaloud', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $readaloud = $DB->get_record('readaloud', ['id' => $cm->instance], '*', MUST_EXIST);
        $modulecontext = context_module::instance($cm->id);

        // make database items and adhoc tasks
        $success = false;
        $message = '';

        // by default we are gradeable unless its shadowing and they turned off shadow grading
        $gradeable = true;
        if ($shadowing) {
            $config = get_config(constants::M_COMPONENT);
            if ($config->disableshadowgrading) {
                $gradeable = false;
            }
        }
        $newattempt = utils::create_attempt($filename, $rectime, $readaloud, $gradeable);
        if ($newattempt && $newattempt->id) {
            // trigger attempt submitted event
            \mod_readaloud\event\attempt_submitted::create_from_attempt($newattempt, $modulecontext)->trigger();

            // register adhoc task to fetch transcriptions
            if (utils::can_transcribe($readaloud)) {
                $success = utils::register_aws_task($readaloud->id, $newattempt->id, $modulecontext->id);
                if (!$success) {
                    $message = "Unable to create adhoc task to fetch transcriptions";
                }
            } else {
                $success = true;
            }
        } else {
            $message = "Unable to add update database with submission";
        }

        // handle return to Moodle
        $ret = new stdClass();
        if ($success) {
            $ret->success = true;
        } else {
            $ret->success = false;
            $ret->message = $message;
        }

        return json_encode($ret);
    }

    public static function submit_regular_attempt_returns() {
        return new external_value(PARAM_RAW);
    }


    // ---------------------------------------
    public static function compare_passage_to_transcript_parameters() {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT),
                'language' => new external_value(PARAM_TEXT),
                'passage' => new external_value(PARAM_TEXT),
                'transcript' => new external_value(PARAM_TEXT),
                'passagephonetic' => new external_value(PARAM_TEXT),
        ]);
    }

    public static function compare_passage_to_transcript($cmid, $language, $passage, $transcript, $passagephonetic) {
        global $DB, $CFG;

        if($cmid > 0){
            $cm = get_coursemodule_from_id('readaloud', $cmid, 0, false, MUST_EXIST);
            $readaloud = $DB->get_record('readaloud', ['id' => $cm->instance], '*', MUST_EXIST);
            $alternatives = $readaloud->alternatives;
            $region = $readaloud->region;
        }else {
            $alternatives = '';
            $region = 'tokyo';
        }
        // get a short language code, eg en-US => en
        $shortlang = utils::fetch_short_lang($language);

        // we also want to fetch the alternatives for the number_words in passage (though we expect number_digits there)
        $alternatives .= PHP_EOL . alphabetconverter::fetch_numerical_alternates($shortlang);  // "four|for|4";
        $alternativesarray = diff::fetchAlternativesArray($alternatives);

        // Fetch phonetics and segments
        list($transcriptphonetic, $transcript) = utils::fetch_phones_and_segments($transcript, $language, $region);
        ;

        // conv. number words to digits (if that is what they originally were)
        switch ($shortlang){
            case 'ja':
                // find digits in original passage, and convert number words to digits in the target passage (transcript)
                // this works but segmented digits are a bit messed up, not sure its worthwhile. more testing needed
                // from here and aigrade
                $transcript = alphabetconverter::words_to_suji_convert($passage, $transcript);
                break;
            case 'en':
            default:
                // find digits in original passage, and convert number words to digits in the target passage (transcript)
                // eg passage "more than 4 million people" transcript "more than four million people" => "more than 4 million people"
                // eg passage "more than 50,000 people" transcript "more than fifty thousand people" => "more than 50 thousand people"
                $transcript = alphabetconverter::words_to_numbers_convert($passage, $transcript, $shortlang);

                break;
        }

        // for german we need to deal with eszetts
        if($shortlang == 'de'){
                // find eszetts in original passage, and convert ss words to eszetts in the target passage
                $transcript = alphabetconverter::ss_to_eszett_convert($passage, $transcript );
        }

        // if its japanese turn zenkaku numbers into hankaku ones
        if($language == constants::M_LANG_JAJP) {
            $sentence = mb_convert_kana($passage, "n");
        }

        // turn the passage and transcript into an array of words
        $passagebits = diff::fetchWordArray($passage);
        $transcriptbits = diff::fetchWordArray($transcript);
        $wildcards = diff::fetchWildcardsArray($alternativesarray);
        $transcriptphoneticbits = diff::fetchWordArray($transcriptphonetic);
        $passagephoneticbits = diff::fetchWordArray($passagephonetic);

        // fetch sequences of transcript/passage matched words
        // then prepare an array of "differences"
        $passagecount = count($passagebits);
        $transcriptcount = count($transcriptbits);
        $sequences = diff::fetchSequences($passagebits, $transcriptbits, $alternativesarray, $language, $transcriptphoneticbits, $passagephoneticbits);
        // fetch diffs
        $debug = false;
        $diffs = diff::fetchDiffs($sequences, $passagecount, $transcriptcount, $debug);
        $diffs = diff::applyWildcards($diffs, $passagebits, $wildcards);

        // from the array of differences build error data, match data, markers, scores and metrics
        $errors = new \stdClass();
        $currentword = 0;

        // loop through diffs
        $results = [];
        foreach ($diffs as $diff) {
            $currentword++;
            $result = new \stdClass();
            $result->word = $passagebits[$currentword - 1];
            $result->wordnumber = $currentword;
            switch ($diff[0]) {
                case Diff::UNMATCHED:
                    // we collect error info so we can count and display them on passage

                    $result->matched = false;
                    break;

                case Diff::MATCHED:
                    $result->matched = true;
                    break;

                default:
                    // do nothing
                    // should never get here
            }
            $results[] = $result;
        }

        // finalise and serialise session errors
        $sessionresults = json_encode($results);

        return $sessionresults;

    }
    public static function compare_passage_to_transcript_returns() {
        return new external_value(PARAM_RAW);
    }
    // ---------------------------------------

    public static function submit_streaming_attempt_parameters() {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT),
                'filename' => new external_value(PARAM_TEXT),
                'rectime' => new external_value(PARAM_INT),
                'awsresults' => new external_value(PARAM_RAW),
        ]);
    }

    public static function submit_streaming_attempt($cmid, $filename, $rectime, $awsresults) {
        global $DB;

        $params = self::validate_parameters(self::submit_streaming_attempt_parameters(),
                ['cmid' => $cmid, 'filename' => $filename, 'rectime' => $rectime, 'awsresults' => $awsresults]);
        extract($params);

        $cm = get_coursemodule_from_id('readaloud', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $readaloud = $DB->get_record('readaloud', ['id' => $cm->instance], '*', MUST_EXIST);
        $modulecontext = context_module::instance($cm->id);

        // make database items and adhoc tasks
        $success = false;
        $message = '';
        $gradeable = true;
        $newattempt = utils::create_attempt($filename, $rectime, $readaloud, $gradeable);
        if (!$newattempt || !$newattempt->id) {
            $message = "Unable to add update database with submission";
        } else {
            $success = true;
        }

        if ($success) {
            $processedawsresults = utils::parse_streaming_results($awsresults);
            $aigrade = new \mod_readaloud\aigrade($newattempt->id, $modulecontext->id, $processedawsresults);
            if ($aigrade) {
                if (!$aigrade->has_attempt()) {
                    $message = 'No attempt could be found when processing transcript';
                    $success = false;
                }

                if (!$aigrade->has_transcripts()) {
                    $message = 'Processing of transcript failed';
                    $success = false;
                } else {
                    $success = true;
                }

            } else {
                $message = 'Unable to create AI grade for some reason';
                $success = false;
            }
        }

        // handle return to Moodle
        $ret = new stdClass();
        if ($success) {
            $ret->success = true;
        } else {
            $ret->success = false;
            $ret->message = $message;
        }

        return json_encode($ret);
    }

    public static function submit_streaming_attempt_returns() {
        return new external_value(PARAM_RAW);
    }

    // ---------------------------------------

    public static function fetch_streaming_diffs_parameters() {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT),
                'awsresults' => new external_value(PARAM_RAW),
        ]);
    }

    public static function fetch_streaming_diffs($cmid, $awsresults) {
        global $DB;

        $params = self::validate_parameters(self::fetch_streaming_diffs_parameters(), ['cmid' => $cmid, 'awsresults' => $awsresults]);
        extract($params);

        return true;
    }

    public static function fetch_streaming_diffs_returns() {
        return new external_value(PARAM_RAW);
    }
}
