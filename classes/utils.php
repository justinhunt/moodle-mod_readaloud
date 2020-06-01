<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Grade Now for readaloud plugin
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_readaloud;
defined('MOODLE_INTERNAL') || die();

use \mod_readaloud\constants;

/**
 * Functions used generally across this mod
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    //we need to consider legacy client side URLs and cloud hosted ones
    public static function make_audio_URL($filename, $contextid, $component, $filearea, $itemid) {
        //we need to consider legacy client side URLs and cloud hosted ones
        if (strpos($filename, 'http') === 0) {
            $ret = $filename;
        } else {
            $ret = \moodle_url::make_pluginfile_url($contextid, $component,
                    $filearea,
                    $itemid, '/',
                    $filename);
        }
        return $ret;
    }

    public static function can_streaming_transcribe($instance){

        $ret = false;

        //The instance languages
        switch($instance->ttslanguage){
            case constants::M_LANG_ENAU:
            case constants::M_LANG_ENGB:
            case constants::M_LANG_ENUS:
            case constants::M_LANG_ESUS:
            case constants::M_LANG_FRFR:
            case constants::M_LANG_FRCA:
                $ret =true;
                break;
            default:
                $ret = false;
        }

        //The supported regions
        if($ret) {
            switch ($instance->region) {
                case "useast1":
                case "useast2":
                case "uswest2":
                case "sydney":
                case "dublin":
                case "ottawa":
                    $ret =true;
                    break;
                default:
                    $ret = false;
            }
        }

        return $ret;
    }

    //are we willing and able to transcribe submissions?
    public static function can_transcribe($instance) {

        //we default to true
        //but it only takes one no ....
        $ret = true;

        //The regions that can transcribe
        switch($instance->region){
            default:
                $ret = true;
        }

        //if user disables ai, we do not transcribe
        if (!$instance->enableai) {
            $ret = false;
        }

        return $ret;
    }

    //see if this is truly json or some error
    public static function is_json($string) {
        if (!$string) {
            return false;
        }
        if (empty($string)) {
            return false;
        }
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    //Insert spaces in between segments in order to create "words"
    public static function segment_japanese($passage){
        $segments = \mod_readaloud\jp\Analyzer::segment($passage);
        return implode(" ",$segments);
    }

    //we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
    //this is our helper
    //we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
    //this is our helper
    public static function curl_fetch($url, $postdata = false) {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
       // $curl->setopt(array('CURLOPT_ENCODING' => ""));
        $result = $curl->get($url, $postdata);
        return $result;
    }

    //fetch slightly slower version of speech
    public static function fetch_speech_ssml($text, $ttsspeed){

        switch($ttsspeed){
            case constants::TTSSPEED_SLOW:
                $speed='slow';
                break;
            case constants::TTSSPEED_XSLOW:
                $speed='x-slow';
                break;
            case constants::TTSSPEED_MEDIUM:
            default:
            $speed='medium';
        }

        $slowtemplate='<speak><break time="1000ms"></break><prosody rate="@@speed@@">@@text@@</prosody></speak>';
        $slowtemplate = str_replace('@@text@@',$text,$slowtemplate);
        $slowtemplate = str_replace('@@speed@@',$speed,$slowtemplate);
        return $slowtemplate;
    }

    //fetch the MP3 URL of the text we want transcribed
    public static function fetch_polly_url($token,$region,$speaktext,$texttype, $voice) {
        global $USER;

        //The REST API we are calling
        $functionname = 'local_cpapi_fetch_polly_url';

        //log.debug(params);
        $params = array();
        $params['wstoken'] = $token;
        $params['wsfunction'] = $functionname;
        $params['moodlewsrestformat'] = 'json';
        $params['text'] = urlencode($speaktext);
        $params['texttype'] = $texttype;
        $params['voice'] = $voice;
        $params['appid'] = 'mod_readaloud';
        $params['owner'] = hash('md5',$USER->username);
        $params['region'] = $region;
        $serverurl = 'https://cloud.poodll.com/webservice/rest/server.php';
        $response = self::curl_fetch($serverurl, $params);
        if (!self::is_json($response)) {
            return false;
        }
        $payloadobject = json_decode($response);

        //returnCode > 0  indicates an error
        if ($payloadobject->returnCode > 0) {
            return false;
            //if all good, then lets do the embed
        } else if ($payloadobject->returnCode === 0) {
            $pollyurl = $payloadobject->returnMessage;
            return $pollyurl;
        } else {
            return false;
        }
    }




    //This is called from the settings page and we do not want to make calls out to cloud.poodll.com on settings
    //page load, for performance and stability issues. So if the cache is empty and/or no token, we just show a
    //"refresh token" links
    public static function fetch_token_for_display($apiuser, $apisecret) {
        global $CFG;

        //First check that we have an API id and secret
        //refresh token
        $refresh = \html_writer::link($CFG->wwwroot . constants::M_URL . '/refreshtoken.php',
                        get_string('refreshtoken', constants::M_COMPONENT)) . '<br>';

        $message = '';
        $apiuser = trim($apiuser);
        $apisecret = trim($apisecret);
        if (empty($apiuser)) {
            $message .= get_string('noapiuser', constants::M_COMPONENT) . '<br>';
        }
        if (empty($apisecret)) {
            $message .= get_string('noapisecret', constants::M_COMPONENT);
        }

        if (!empty($message)) {
            return $refresh . $message;
        }

        //Fetch from cache and process the results and display
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');

        //if we have no token object the creds were wrong ... or something
        if (!($tokenobject)) {
            $message = get_string('notokenincache', constants::M_COMPONENT);
            //if we have an object but its no good, creds werer wrong ..or something
        } else if (!property_exists($tokenobject, 'token') || empty($tokenobject->token)) {
            $message = get_string('credentialsinvalid', constants::M_COMPONENT);
            //if we do not have subs, then we are on a very old token or something is wrong, just get out of here.
        } else if (!property_exists($tokenobject, 'subs')) {
            $message = 'No subscriptions found at all';
        }
        if (!empty($message)) {
            return $refresh . $message;
        }

        //we have enough info to display a report. Lets go.
        foreach ($tokenobject->subs as $sub) {
            $sub->expiredate = date('d/m/Y', $sub->expiredate);
            $message .= get_string('displaysubs', constants::M_COMPONENT, $sub) . '<br>';
        }
        //Is app authorised
        if (in_array(constants::M_COMPONENT, $tokenobject->apps)) {
            $message .= get_string('appauthorised', constants::M_COMPONENT) . '<br>';
        } else {
            $message .= get_string('appnotauthorised', constants::M_COMPONENT) . '<br>';
        }

        return $refresh . $message;

    }

    //We need a Poodll token to make all this recording and transcripts happen
    public static function fetch_token($apiuser, $apisecret, $force = false) {

        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');
        $tokenuser = $cache->get('recentpoodlluser');
        $apiuser = trim($apiuser);
        $apisecret = trim($apisecret);

        //if we got a token and its less than expiry time
        // use the cached one
        if ($tokenobject && $tokenuser && $tokenuser == $apiuser && !$force) {
            if ($tokenobject->validuntil == 0 || $tokenobject->validuntil > time()) {
                return $tokenobject->token;
            }
        }

        // Send the request & save response to $resp
        $token_url = "https://cloud.poodll.com/local/cpapi/poodlltoken.php";
        $postdata = array(
                'username' => $apiuser,
                'password' => $apisecret,
                'service' => 'cloud_poodll'
        );
        $token_response = self::curl_fetch($token_url, $postdata);
        if ($token_response) {
            $resp_object = json_decode($token_response);
            if ($resp_object && property_exists($resp_object, 'token')) {
                $token = $resp_object->token;
                //store the expiry timestamp and adjust it for diffs between our server times
                if ($resp_object->validuntil) {
                    $validuntil = $resp_object->validuntil - ($resp_object->poodlltime - time());
                    //we refresh one hour out, to prevent any overlap
                    $validuntil = $validuntil - (1 * HOURSECS);
                } else {
                    $validuntil = 0;
                }

                //cache the token
                $tokenobject = new \stdClass();
                $tokenobject->token = $token;
                $tokenobject->validuntil = $validuntil;
                $tokenobject->subs = false;
                $tokenobject->apps = false;
                $tokenobject->sites = false;
                if (property_exists($resp_object, 'subs')) {
                    $tokenobject->subs = $resp_object->subs;
                }
                if (property_exists($resp_object, 'apps')) {
                    $tokenobject->apps = $resp_object->apps;
                }
                if (property_exists($resp_object, 'sites')) {
                    $tokenobject->sites = $resp_object->sites;
                }
                if(property_exists($resp_object,'awsaccesssecret')){
                    $tokenobject->awsaccesssecret = $resp_object->awsaccesssecret;
                }
                if(property_exists($resp_object,'awsaccessid')){
                    $tokenobject->awsaccessid = $resp_object->awsaccessid;
                }

                $cache->set('recentpoodlltoken', $tokenobject);
                $cache->set('recentpoodlluser', $apiuser);

            } else {
                $token = '';
                if ($resp_object && property_exists($resp_object, 'error')) {
                    //ERROR = $resp_object->error
                }
            }
        } else {
            $token = '';
        }
        return $token;
    }

    //check token and tokenobject(from cache)
    //return error message or blank if its all ok
    public static function fetch_token_error($token){
        global $CFG;

        //check token authenticated
        if(empty($token)) {
            $message = get_string('novalidcredentials', constants::M_COMPONENT,
                    $CFG->wwwroot . constants::M_PLUGINSETTINGS);
            return $message;
        }

        // Fetch from cache and process the results and display.
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, constants::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');

        //we should not get here if there is no token, but lets gracefully die, [v unlikely]
        if (!($tokenobject)) {
            $message = get_string('notokenincache', constants::M_COMPONENT);
            return $message;
        }

        //We have an object but its no good, creds were wrong ..or something. [v unlikely]
        if (!property_exists($tokenobject, 'token') || empty($tokenobject->token)) {
            $message = get_string('credentialsinvalid', constants::M_COMPONENT);
            return $message;
        }
        // if we do not have subs.
        if (!property_exists($tokenobject, 'subs')) {
            $message = get_string('nosubscriptions', constants::M_COMPONENT);
            return $message;
        }
        // Is app authorised?
        if (!property_exists($tokenobject, 'apps') || !in_array(constants::M_COMPONENT, $tokenobject->apps)) {
            $message = get_string('appnotauthorised', constants::M_COMPONENT);
            return $message;
        }

        //just return empty if there is no error.
        return '';
    }

    /*
    * Turn a passage with text "lines" into html "brs"
    *
    * @param String The passage of text to convert
    * @param String An optional pad on each replacement (needed for processing when marking up words as spans in passage)
    * @return String The converted passage of text
    */
    public static function lines_to_brs($passage, $seperator = '') {
        //see https://stackoverflow.com/questions/5946114/how-to-replace-newline-or-r-n-with-br
        return str_replace("\r\n", $seperator . '<br>' . $seperator, $passage);
        //this is better but we can not pad the replacement and we need that
        //return nl2br($passage);
    }

    public static function fetch_duration_from_transcript($fulltranscript) {
        //if we do not have the full transcript return 0
        if (!$fulltranscript || empty($fulltranscript)) {
            return 0;
        }

        $transcript = json_decode($fulltranscript);
        if (isset($transcript->results)) {
            $duration = self::fetch_duration_from_transcript_json($fulltranscript);
        } else {
            $duration = self::fetch_duration_from_transcript_gjson($fulltranscript);
        }
        return $duration;

    }

    public static function fetch_duration_from_transcript_json($fulltranscript) {
        //if we do not have the full transcript return 0
        if (!$fulltranscript || empty($fulltranscript)) {
            return 0;
        }

        $transcript = json_decode($fulltranscript);
        $titems = $transcript->results->items;
        $twords = array();
        foreach ($titems as $titem) {
            if ($titem->type == 'pronunciation') {
                $twords[] = $titem;
            }
        }
        $lastindex = count($twords);
        if ($lastindex > 0) {
            return round($twords[$lastindex - 1]->end_time, 0);
        } else {
            return 0;
        }
    }

    public static function fetch_duration_from_transcript_gjson($fulltranscript) {
        //if we do not have the full transcript return 0
        if (!$fulltranscript || empty($fulltranscript)) {
            return 0;
        }

        $transcript = json_decode($fulltranscript);
        $twords = [];
        //create a big array of 'words' from gjson sentences
        foreach ($transcript as $sentence) {
            $twords = array_merge($twords, $sentence->words);

        }//end of sentence
        $twordcount = count($twords);
        if ($twordcount > 0) {
            $tword = $twords[$twordcount - 1];
            $ms = round(floatval($tword->endTime->nanos * .000000001), 2);
            return round($tword->endTime->seconds + $ms, 0);
        } else {
            return 0;
        }
    }

    public static function fetch_audio_points($fulltranscript, $matches, $alternatives) {

        //first check if we have a fulltranscript (we might only have a transcript in some cases)
        //if not we just return dummy audio points. Que sera sera
        if (!self::is_json($fulltranscript)) {
            foreach ($matches as $matchitem) {
                $matchitem->audiostart = 0;
                $matchitem->audioend = 0;
            }
            return $matches;
        }
        $transcript = json_decode($fulltranscript);
        if (isset($transcript->results)) {
            $matches = self::fetch_audio_points_json($transcript, $matches, $alternatives);
        } else {
            $matches = self::fetch_audio_points_gjson($transcript, $matches, $alternatives);
        }
        return $matches;
    }

    //fetch start-time and end-time points for each word
    public static function fetch_audio_points_json($transcript, $matches, $alternatives) {

        //get type 'pronunciation' items from full transcript. The other type is 'punctuation'.
        $titems = $transcript->results->items;
        $twords = array();
        foreach ($titems as $titem) {
            if ($titem->type == 'pronunciation') {
                $twords[] = $titem;
            }
        }
        $twordcount = count($twords);

        //loop through matches and fetch audio start from word item
        foreach ($matches as $matchitem) {
            if ($matchitem->tposition <= $twordcount) {
                //pull the word data object from the full transcript, at the index of the match
                $tword = $twords[$matchitem->tposition - 1];

                //trust or be sure by matching ...
                $trust = false;
                if ($trust) {
                    $matchitem->audiostart = $tword->start_time;
                    $matchitem->audioend = $tword->end_time;
                } else {
                    //format the text of the word to lower case no punc, to match the word in the matchitem
                    $tword_text = strtolower($tword->alternatives[0]->content);
                    $tword_text = preg_replace("#[[:punct:]]#", "", $tword_text);
                    //if we got it, fetch the audio position from the word data object
                    if ($matchitem->word == $tword_text) {
                        $matchitem->audiostart = $tword->start_time;
                        $matchitem->audioend = $tword->end_time;

                        //do alternatives search for match
                    } else if (diff::check_alternatives_for_match($matchitem->word,
                            $tword_text,
                            $alternatives)) {
                        $matchitem->audiostart = $tword->start_time;
                        $matchitem->audioend = $tword->end_time;
                    }
                }
            }
        }
        return $matches;
    }

    //fetch start-time and end-time points for each word
    public static function fetch_audio_points_gjson($transcript, $matches, $alternatives) {
        $twords = [];
        //create a big array of 'words' from gjson sentences
        foreach ($transcript as $sentence) {
            $twords = array_merge($twords, $sentence->words);

        }//end of sentence
        $twordcount = count($twords);

        //loop through matches and fetch audio start from word item
        foreach ($matches as $matchitem) {
            if ($matchitem->tposition <= $twordcount) {
                //pull the word data object from the full transcript, at the index of the match
                $tword = $twords[$matchitem->tposition - 1];
                //make startTime and endTime match the regular format
                $start_time = $tword->startTime->seconds + round(floatval($tword->startTime->nanos * .000000001), 2);
                $end_time = $tword->endTime->seconds + round(floatval($tword->endTime->nanos * .000000001), 2);

                //trust or be sure by matching ...
                $trust = false;
                if ($trust) {
                    $matchitem->audiostart = $start_time;
                    $matchitem->audioend = $end_time;
                } else {
                    //format the text of the word to lower case no punc, to match the word in the matchitem
                    $tword_text = strtolower($tword->word);
                    $tword_text = preg_replace("#[[:punct:]]#", "", $tword_text);
                    //if we got it, fetch the audio position from the word data object
                    if ($matchitem->word == $tword_text) {
                        $matchitem->audiostart = $start_time;
                        $matchitem->audioend = $end_time;

                        //do alternatives search for match
                    } else if (diff::check_alternatives_for_match($matchitem->word,
                            $tword_text,
                            $alternatives)) {
                        $matchitem->audiostart = $start_time;
                        $matchitem->audioend = $end_time;
                    }
                }
            }
        }//end of words

        return $matches;
    }

    //this is a server side implementation of the same name function in gradenowhelper.js
    //we need this when calculating adjusted grades(reports/machinegrading.php) and on making machine grades(aigrade.php)
    //the WPM adjustment based on accadjust only applies to machine grades, so it is NOT in gradenowhelper
    public static function processscores($sessiontime, $sessionendword, $errorcount, $activitydata) {

        ////wpm score
        $wpmerrors = $errorcount;
        switch ($activitydata->accadjustmethod) {

            case constants::ACCMETHOD_FIXED:
                $wpmerrors = $wpmerrors - $activitydata->accadjust;
                if ($wpmerrors < 0) {
                    $wpmerrors = 0;
                }
                break;

            case constants::ACCMETHOD_NOERRORS:
                $wpmerrors = 0;
                break;

            case constants::ACCMETHOD_AUTO:
                $adjust = \mod_readaloud\utils::estimate_errors($activitydata->id);
                $wpmerrors = $wpmerrors - $adjust;
                if ($wpmerrors < 0) {
                    $wpmerrors = 0;
                }
                break;

            case constants::ACCMETHOD_NONE:
            default:
                $wpmerrors = $errorcount;
                break;
        }
        if ($sessiontime > 0) {
            //regular WPM
            $totalwords = $sessionendword - $wpmerrors;
            $wpmscore = round(($totalwords * 60) / $sessiontime);

            //strict WPM
            $totalwords = $totalwords - $wpmerrors;
            if($totalwords < 0){$totalwords =0;}
            $strictwpmscore = round(($totalwords * 60) / $sessiontime);

        } else {
            $wpmscore = 0;
            $strictwpmscore = 0;
        }

        //accuracy score
        if ($sessionendword > 0) {
            $accuracyscore = round(($sessionendword - $errorcount) / $sessionendword * 100);
        } else {
            $accuracyscore = 0;
        }

        //sessionscore
        $targetwpm = $activitydata->targetwpm;
        if($activitydata->sessionscoremethod == constants::SESSIONSCORE_STRICT){
            $usewpmscore = $strictwpmscore;
        }else{
            $usewpmscore = $wpmscore;
        }

        if ($usewpmscore > $targetwpm) {
            $usewpmscore = $targetwpm;
        }
        $sessionscore = round($usewpmscore / $targetwpm * 100);

        $scores = new \stdClass();
        $scores->wpmscore = $wpmscore;
        $scores->accuracyscore = $accuracyscore;
        $scores->sessionscore = $sessionscore;
        return $scores;

    }

    //take a json string of session errors, anmd count how many there are.
    public static function count_sessionerrors($sessionerrors) {
        $errors = json_decode($sessionerrors);
        if ($errors) {
            $errorcount = count(get_object_vars($errors));
        } else {
            $errorcount = 0;
        }
        return $errorcount;
    }

    //get all the aievaluations for a user
    public static function get_aieval_byuser($readaloudid, $userid) {
        global $DB;
        $sql = "SELECT tai.*  FROM {" . constants::M_AITABLE . "} tai INNER JOIN  {" . constants::M_USERTABLE . "}" .
                " tu ON tu.id =tai.attemptid AND tu.readaloudid=tai.readaloudid WHERE tu.readaloudid=? AND tu.userid=?";
        $result = $DB->get_records_sql($sql, array($readaloudid, $userid));
        return $result;
    }

    //get average difference between human graded attempt error count and AI error count
    //we only fetch if A) have machine grade and B) sessiontime> 0(has been manually graded)
    public static function estimate_errors($readaloudid) {
        global $DB;
        $errorestimate = 0;
        $sql = "SELECT AVG(tai.errorcount - tu.errorcount) as errorestimate  FROM {" . constants::M_AITABLE .
                "} tai INNER JOIN  {" . constants::M_USERTABLE . "}" .
                " tu ON tu.id =tai.attemptid AND tu.readaloudid=tai.readaloudid WHERE tu.sessiontime > 0 AND tu.readaloudid=?";
        $result = $DB->get_field_sql($sql, array($readaloudid));
        if ($result !== false) {
            $errorestimate = round($result);
        }
        return $errorestimate;
    }

    /*
  * Per passageword, an object with mistranscriptions and their frequency will be returned
    * To be consistent with how data is stored in matches/errors, we return a 1 based array of mistranscriptions
     * @return array an array of stdClass (1 item per passage word) with the passage index(1 based), passage word and array of mistranscription=>count
   */
    public static function fetch_all_mistranscriptions($readaloudid) {
        global $DB;
        $attempts = $DB->get_records(constants::M_AITABLE, array('readaloudid' => $readaloudid));
        $activity = $DB->get_record(constants::M_TABLE, array('id' => $readaloudid));
        $passagewords = diff::fetchWordArray($activity->passage);
        $passagecount = count($passagewords);
        //$alternatives = diff::fetchAlternativesArray($activity->alternatives);

        $results = array();
        $mistranscriptions = array();
        foreach ($attempts as $attempt) {
            $transcriptwords = diff::fetchWordArray($attempt->transcript);
            $matches = json_decode($attempt->sessionmatches);
            $mistranscriptions[] = self::fetch_attempt_mistranscriptions($passagewords, $transcriptwords, $matches);
        }
        //aggregate results
        for ($wordnumber = 1; $wordnumber <= $passagecount; $wordnumber++) {
            $aggregate_set = array();
            foreach ($mistranscriptions as $mistranscript) {
                if (!$mistranscript[$wordnumber]) {
                    continue;
                }
                if (array_key_exists($mistranscript[$wordnumber], $aggregate_set)) {
                    $aggregate_set[$mistranscript[$wordnumber]]++;
                } else {
                    $aggregate_set[$mistranscript[$wordnumber]] = 1;
                }
            }
            $result = new \stdClass();
            $result->mistranscriptions = $aggregate_set;
            $result->passageindex = $wordnumber;
            $result->passageword = $passagewords[$wordnumber - 1];
            $results[] = $result;
        }//end of for loop
        return $results;
    }

    /*
   * This will return an array of mistranscript strings for a single attemot. 1 entry per passageword.
     * To be consistent with how data is stored in matches/errors, we return a 1 based array of mistranscriptions
     * @return array a 1 based array of mistranscriptions(string) or false. i item for each passage word
    */
    public static function fetch_attempt_mistranscriptions($passagewords, $transcriptwords, $matches) {
        $passagecount = count($passagewords);
        if (!$passagecount) {
            return false;
        }
        $mistranscriptions = array();
        for ($wordnumber = 1; $wordnumber <= $passagecount; $wordnumber++) {
            $mistranscription = self::fetch_one_mistranscription($wordnumber, $transcriptwords, $matches);
            if ($mistranscription) {
                $mistranscriptions[$wordnumber] = $mistranscription;
            } else {
                $mistranscriptions[$wordnumber] = false;
            }
        }//end of for loop
        return $mistranscriptions;
    }

    /*
   * This will take a wordindex and find the previous and next transcript indexes that were matched and
   * return all the transcript words in between those.
     *
     * @return a string which is the transcript match of a passage word, or false if the transcript=passage
    */
    public static function fetch_one_mistranscription($passageindex, $transcriptwords, $matches) {

        //if we have a problem with matches (bad data?) just return
        if (!$matches) {
            return false;
        }

        //count transcript words
        $transcriptlength = count($transcriptwords);
        if ($transcriptlength == 0) {
            return false;
        }

        //build a quick to search array of matched words
        $passagematches = array();
        foreach ($matches as $match) {
            $passagematches[$match->pposition] = $match->word;
        }

        //find startindex
        $startindex = -1;
        for ($wordnumber = $passageindex; $wordnumber > 0; $wordnumber--) {

            $ismatched = array_key_exists($wordnumber, $passagematches);
            if ($ismatched) {
                $startindex = $matches->{$wordnumber}->tposition + 1;
                break;
            }
        }//end of for loop

        //find endindex
        $endindex = -1;
        for ($wordnumber = $passageindex; $wordnumber <= $transcriptlength; $wordnumber++) {

            $ismatched = array_key_exists($wordnumber, $passagematches);
            //if we matched then the previous transcript word is the last unmatched one in the checkindex sequence
            if ($ismatched) {
                $endindex = $matches->{$wordnumber}->tposition - 1;
                break;
            }
        }//end of for loop --

        //if there was no previous matched word, we set start to 1
        if ($startindex == -1) {
            $startindex = 1;
        }
        //if there was no subsequent matched word we flag the end as the -1
        if ($endindex == $transcriptlength) {
            $endindex = -1;
            //an edge case is where the first word is not in transcript and first match is the second or later passage
            //word. It might not be possible for endindex to be lower than start index, but we don't want it anyway
        } else if ($endindex == 0 || $endindex < $startindex) {
            return false;
        }

        //up until this point the indexes have started from 1, since the passage word numbers start from 1
        //but the transcript array is 0 based so we adjust. array_slice function does not include item and endindex
        ///so it needs to be one more then start index. hence we do not adjust that
        $startindex--;

        //finally we return the section of transcript
        if ($endindex > 0) {
            $chunklength = $endindex - $startindex;
            $retarray = array_slice($transcriptwords, $startindex, $chunklength);
        } else {
            $retarray = array_slice($transcriptwords, $startindex);
        }

        $ret = implode(" ", $retarray);
        if (trim($ret) == '') {
            return false;
        } else {
            return $ret;
        }
    }

    /**
     * Returns the link for the related activity
     *
     * @return string
     */
    public static function fetch_next_activity($activitylink) {
        global $DB;
        $ret = new \stdClass();
        $ret->url = false;
        $ret->label = false;
        if (!$activitylink) {
            return $ret;
        }

        $module = $DB->get_record('course_modules', array('id' => $activitylink));
        if ($module) {
            $modname = $DB->get_field('modules', 'name', array('id' => $module->module));
            if ($modname) {
                $instancename = $DB->get_field($modname, 'name', array('id' => $module->instance));
                if ($instancename) {
                    $ret->url = new \moodle_url('/mod/' . $modname . '/view.php', array('id' => $activitylink));
                    $ret->label = get_string('activitylinkname', constants::M_COMPONENT, $instancename);
                }
            }
        }
        return $ret;
    }

    public static function fetch_attempt_chartdata($moduleinstance,$userid=0){
        global $DB, $USER;

        //use current user if not passed in
        if($userid==0){$userid = $USER->id;}
        //init return value
        $chartdata = false;

        $sql =
                "SELECT tu.*,tai.accuracy as aiaccuracy,tai.wpm as aiwpm, tai.sessionscore as aisessionscore,tai.fulltranscript as fulltranscript FROM {" .
                constants::M_USERTABLE . "} tu INNER JOIN {user} u ON tu.userid=u.id " .
                "INNER JOIN {" . constants::M_AITABLE . "} tai ON tai.attemptid=tu.id " .
                "WHERE tu.readaloudid=? AND u.id=?" .
                " ORDER BY tu.id ASC";

        $alldata = $DB->get_records_sql($sql, array($moduleinstance->id, $userid));

        //if we have data, yay
        if ($alldata) {

            //init our data set
            $chartdata = new \stdClass();
            $wpmdata=[];
            $accuracydata=[];
            $sessionscoredata=[];
            $labelsdata=[];
            $attemptno=0;

            //loop through each attempt
            foreach ($alldata as $thedata) {


                //sessiontime is our indicator that a human grade has been saved.
                //use aidata if no human grade or machinegrades only
                if (!$thedata->sessiontime || $moduleinstance->machgrademethod == constants::MACHINEGRADE_MACHINEONLY) {
                    $wpmdata[]= $thedata->aiwpm;
                    $accuracydata[] = $thedata->aiaccuracy;
                    $sessionscoredata[] = $thedata->aisessionscore;
                }else{
                    $wpmdata[]= $thedata->wpm;
                    $accuracydata[] = $thedata->accuracy;
                    $sessionscoredata[] = $thedata->sessionscore;

                }
                $attemptno++;
                $labelsdata[] =get_string('attemptno', constants::M_COMPONENT, $attemptno);
            }
            $chartdata->accuracyseries = new \core\chart_series(get_string('accuracy_p', constants::M_COMPONENT),$accuracydata);
            $chartdata->wpmseries = new \core\chart_series(get_string('wpm', constants::M_COMPONENT),$wpmdata);
            $chartdata->sessionscoreseries = new \core\chart_series(get_string('grade_p', constants::M_COMPONENT),$sessionscoredata);
            $chartdata->labelsdata=$labelsdata;

        }
        return $chartdata;
    }

    public static function fetch_attempt_summary($moduleinstance,$userid=0){
        global $DB, $USER;

        //use current user if not passed in
        if($userid==0){$userid = $USER->id;}
        //init return value
        $attemptsummary = false;

        $sql =
                "SELECT tu.*,tai.accuracy as aiaccuracy,tai.wpm as aiwpm, tai.sessionscore as aisessionscore,tai.fulltranscript as fulltranscript FROM {" .
                constants::M_USERTABLE . "} tu INNER JOIN {user} u ON tu.userid=u.id " .
                "INNER JOIN {" . constants::M_AITABLE . "} tai ON tai.attemptid=tu.id " .
                "WHERE tu.readaloudid=? AND u.id=?" .
                " ORDER BY u.lastnamephonetic,u.firstnamephonetic,u.lastname,u.firstname,u.middlename,u.alternatename,tu.id DESC";

        $alldata = $DB->get_records_sql($sql, array($moduleinstance->id, $userid));

        //if we have data, yay
        if ($alldata) {

            //initialise our return object
            $attemptsummary = new \stdClass();
            $attemptsummary->totalattempts = count($alldata);
            $attemptsummary->total_wpm = 0;
            $attemptsummary->h_wpm = 0;
            $attemptsummary->total_accuracy = 0;
            $attemptsummary->h_accuracy = 0;
            $attemptsummary->total_sessionscore = 0;
            $attemptsummary->h_sessionscore = 0;


           //loop through each attempt
            foreach ($alldata as $thedata) {

                //sessiontime is our indicator that a human grade has been saved.
                //use aidata if no human grade or machinegrades only
                if (!$thedata->sessiontime || $moduleinstance->machgrademethod == constants::MACHINEGRADE_MACHINEONLY) {
                    $thedata->wpm = $thedata->aiwpm;
                    $thedata->accuracy = $thedata->aiaccuracy;
                    $thedata->sessionscore = $thedata->aisessionscore;
                }
                //calc totals and highest
                $attemptsummary->total_wpm += $thedata->wpm;
                $attemptsummary->h_wpm = max($attemptsummary->h_wpm, $thedata->wpm);
                $attemptsummary->total_accuracy += $thedata->accuracy;
                $attemptsummary->h_accuracy = max($attemptsummary->h_accuracy, $thedata->accuracy);
                $attemptsummary->total_sessionscore += $thedata->sessionscore;
                $attemptsummary->h_sessionscore = max($attemptsummary->h_sessionscore, $thedata->sessionscore);

            }
            //finally calc averages
            $attemptsummary->av_wpm = round($attemptsummary->total_wpm / $attemptsummary->totalattempts,1);
            $attemptsummary->av_accuracy = round($attemptsummary->total_accuracy / $attemptsummary->totalattempts,1);
            $attemptsummary->av_sessionscore = round($attemptsummary->total_sessionscore / $attemptsummary->totalattempts,1);

        }
        return $attemptsummary;
    }

    //save the data to Moodle.
    public static function create_attempt($filename, $rectime, $readaloud) {
        global $USER, $DB;

        //correct filename which has probably been massaged to get through mod_security
        $filename = str_replace('https___', 'https://', $filename);

        //Add a blank attempt with just the filename  and essential details
        $newattempt = new \stdClass();
        $newattempt->courseid = $readaloud->course;
        $newattempt->readaloudid = $readaloud->id;
        $newattempt->userid = $USER->id;
        $newattempt->status = 0;
        $newattempt->filename = $filename;
        $newattempt->sessionscore = 0;
        //$newattempt->sessiontime=$rectime;  //.. this would work. But sessiontime is used as flag of human has graded ...so needs more thought
        $newattempt->sessionerrors = '';
        $newattempt->errorcount = 0;
        $newattempt->wpm = 0;
        $newattempt->timecreated = time();
        $newattempt->timemodified = time();
        $attemptid = $DB->insert_record(constants::M_USERTABLE, $newattempt);
        if (!$attemptid) {
            return false;
        }
        $newattempt->id = $attemptid;

        //if we are machine grading we need an entry to AI table too
        //But ... there is the chance a user will CHANGE the machgrademethod value after submissions have begun,
        //If they do, INNER JOIN SQL in grade related logic will mess up gradebook if aigrade record is not available.
        //So for prudence sake we ALWAYS create an aigrade record
        if (true ||
                $readaloud->machgrademethod == constants::MACHINEGRADE_HYBRID ||
                $readaloud->machgrademethod == constants::MACHINEGRADE_MACHINEONLY) {
            aigrade::create_record($newattempt, $readaloud->timelimit);
        }

        //return the attempt id
        return $attemptid;
    }

    //streaming results are not the same format as non streaming, we massage the streaming to look like a non streaming
    //to our code that will go on to process it.
    public static function parse_streaming_results($streaming_results){
        $results = json_decode($streaming_results);
        $alltranscript = '';
        $allitems=[];
        foreach($results as $result){
            foreach($result as $completion) {
                foreach ($completion->Alternatives as $alternative) {
                    $alltranscript .= $alternative->Transcript . ' ';
                    foreach ($alternative->Items as $item) {
                        $processeditem = new \stdClass();
                        $processeditem->alternatives = [['content' => $item->Content, 'confidence' => "1.0000"]];
                        $processeditem->end_time = "" . round($item->EndTime,3);
                        $processeditem->start_time = "" . round($item->StartTime,3);
                        $processeditem->type = $item->Type;
                        $allitems[] = $processeditem;
                    }
                }
            }
        }
        $ret = new \stdClass();
        $ret->jobName="streaming";
        $ret->accountId="streaming";
        $ret->results =[];
        $ret->status='COMPLETED';
        $ret->results['transcripts']=[['transcript'=>$alltranscript]];
        $ret->results['items']=$allitems;

        return json_encode($ret);
    }


    //register an adhoc task to pick up transcripts
    public static function register_aws_task($activityid, $attemptid, $modulecontextid) {
        $s3_task = new \mod_readaloud\task\readaloud_s3_adhoc();
        $s3_task->set_component('mod_readaloud');

        $customdata = new \stdClass();
        $customdata->activityid = $activityid;
        $customdata->attemptid = $attemptid;
        $customdata->modulecontextid = $modulecontextid;
        $customdata->taskcreationtime = time();

        $s3_task->set_custom_data($customdata);
        // queue it
        \core\task\manager::queue_adhoc_task($s3_task);
        return true;
    }

    //What to show students after an attempt
    public static function get_postattempt_options() {
        return array(
                constants::POSTATTEMPT_NONE => get_string("postattempt_none", constants::M_COMPONENT),
                constants::POSTATTEMPT_EVAL => get_string("postattempt_eval", constants::M_COMPONENT),
                constants::POSTATTEMPT_EVALERRORS => get_string("postattempt_evalerrors", constants::M_COMPONENT),
                constants::POSTATTEMPT_EVALERRORSNOGRADE => get_string("postattempt_evalerrorsnograde", constants::M_COMPONENT)
        );
    }

    //What multi-attempt grading approach
    public static function get_grade_options() {
        return array(
                constants::M_GRADELATEST => get_string("gradelatest", constants::M_COMPONENT),
                constants::M_GRADEHIGHEST => get_string("gradehighest", constants::M_COMPONENT)
        );
    }

    //for error estimate and accuracy adjustment, we can auto estimate errors, never estimate errors, or use a fixed error estimate, or ignore errors
    public static function get_accadjust_options() {
        return array(
                constants::ACCMETHOD_NONE => get_string("accmethod_none", constants::M_COMPONENT),
            //constants::ACCMETHOD_AUTO  => get_string("accmethod_auto",constants::M_COMPONENT),
                constants::ACCMETHOD_FIXED => get_string("accmethod_fixed", constants::M_COMPONENT),
                constants::ACCMETHOD_NOERRORS => get_string("accmethod_noerrors", constants::M_COMPONENT),
        );
    }

    public static function get_region_options() {
        return array(
                "useast1" => get_string("useast1", constants::M_COMPONENT),
                "tokyo" => get_string("tokyo", constants::M_COMPONENT),
                "sydney" => get_string("sydney", constants::M_COMPONENT),
                "dublin" => get_string("dublin", constants::M_COMPONENT),
                "ottawa" => get_string("ottawa", constants::M_COMPONENT),
                "frankfurt" => get_string("frankfurt", constants::M_COMPONENT),
                "london" => get_string("london", constants::M_COMPONENT),
                "saopaulo" => get_string("saopaulo", constants::M_COMPONENT),
                "singapore" => get_string("singapore",constants::M_COMPONENT),
                "mumbai" => get_string("mumbai",constants::M_COMPONENT)
        );
    }

    public static function fetch_rating($attempt,$aigrade){
        $have_humaneval = $attempt->sessiontime != null;
        $have_aieval = $aigrade && $aigrade->has_transcripts();
        if(!$have_humaneval && !$have_aieval){
            return -1;
        }elseif($have_humaneval){
            if($attempt->sessionscore==0){return 0;}
            if($attempt->sessionscore==100){return 5;}
            return floor($attempt->sessionscore / 20) + 1;

        }else{
            if($aigrade->aidata->sessionscore==0){return 0;}
            if($aigrade->aidata->sessionscore==100){return 5;}
            return floor($aigrade->aidata->sessionscore / 20) + 1;
        }
    }

    public static function fetch_options_recorders(){
        $rec_options = array( constants::REC_READALOUD => get_string("rec_readaloud", constants::M_COMPONENT),
                constants::REC_ONCE => get_string("rec_once", constants::M_COMPONENT),
                constants::REC_UPLOAD => get_string("rec_upload", constants::M_COMPONENT));
        return $rec_options;
    }

    public static function get_machinegrade_options() {
        return array(
                constants::MACHINEGRADE_NONE => get_string("machinegradenone", constants::M_COMPONENT),
                constants::MACHINEGRADE_HYBRID => get_string("machinegradehybrid", constants::M_COMPONENT),
                constants::MACHINEGRADE_MACHINEONLY => get_string("machinegrademachineonly", constants::M_COMPONENT)
        );
    }

    public static function get_sessionscore_options() {
        return array(
                constants::SESSIONSCORE_NORMAL => get_string("sessionscorenormal", constants::M_COMPONENT),
                constants::SESSIONSCORE_STRICT => get_string("sessionscorestrict", constants::M_COMPONENT)
        );
    }

    public static function get_ttsspeed_options() {
        return array(
                constants::TTSSPEED_MEDIUM => get_string("mediumspeed", constants::M_COMPONENT),
                constants::TTSSPEED_SLOW => get_string("slowspeed", constants::M_COMPONENT),
                constants::TTSSPEED_XSLOW => get_string("extraslowspeed", constants::M_COMPONENT)
        );
    }

    public static function get_timelimit_options() {
        return array(
                0 => get_string("notimelimit", constants::M_COMPONENT),
                15 => get_string("xsecs", constants::M_COMPONENT, '15'),
                30 => get_string("xsecs", constants::M_COMPONENT, '30'),
                45 => get_string("xsecs", constants::M_COMPONENT, '45'),
                60 => get_string("onemin", constants::M_COMPONENT),
                90 => get_string("oneminxsecs", constants::M_COMPONENT, '30'),
                120 => get_string("xmins", constants::M_COMPONENT, '2'),
                150 => get_string("xminsecs", constants::M_COMPONENT, array('minutes' => 2, 'seconds' => 30)),
                180 => get_string("xmins", constants::M_COMPONENT, '3')
        );
    }

    public static function get_expiredays_options() {
        return array(
                "1" => "1",
                "3" => "3",
                "7" => "7",
                "30" => "30",
                "90" => "90",
                "180" => "180",
                "365" => "365",
                "730" => "730",
                "9999" => get_string('forever', constants::M_COMPONENT)
        );
    }

    public static function fetch_options_transcribers() {
        $options =
                array(constants::TRANSCRIBER_AMAZONTRANSCRIBE => get_string("transcriber_amazontranscribe", constants::M_COMPONENT),
                        constants::TRANSCRIBER_AMAZONSTREAMING => get_string("transcriber_amazonstreaming", constants::M_COMPONENT),
                        constants::TRANSCRIBER_GOOGLECLOUDSPEECH => get_string("transcriber_googlecloud", constants::M_COMPONENT));
        return $options;
    }
    
    public static function fetch_ttsvoice_options($langcode=''){
        $alllang= array(
                constants::M_LANG_ARAE => ['Zeina'],
                //constants::M_LANG_ARSA => [],
                constants::M_LANG_DADK => ["Naja"=>"Naja","Mads"=>"Mads"],
                constants::M_LANG_DEDE => ['Hans'=>'Hans','Marlene'=>'Marlene', 'Vicki'=>'Vicki'],
                //constants::M_LANG_DECH => [],
                constants::M_LANG_ENUS => ['Joey'=>'Joey','Justin'=>'Justin','Matthew'=>'Matthew','Ivy'=>'Ivy',
                'Joanna'=>'Joanna','Kendra'=>'Kendra','Kimberly'=>'Kimberly','Salli'=>'Salli'],
                constants::M_LANG_ENGB => ['Brian'=>'Brian','Amy'=>'Amy', 'Emma'=>'Emma'],
                constants::M_LANG_ENAU => ['Russell'=>'Russell','Nicole'=>'Nicole'],
                constants::M_LANG_ENIN => ['Aditi'=>'Aditi', 'Raveena'=>'Raveena'],
               // constants::M_LANG_ENIE => [],
                constants::M_LANG_ENWL => ["Geraint"=>"Geraint"],
               // constants::M_LANG_ENAB => [],
                constants::M_LANG_ESUS => ['Miguel'=>'Miguel','Penelope'=>'Penelope'],
                constants::M_LANG_ESES => [ 'Enrique'=>'Enrique', 'Conchita'=>'Conchita', 'Lucia'=>'Lucia'],
                //constants::M_LANG_FAIR => [],
                constants::M_LANG_FRCA => ['Chantal'=>'Chantal'],
                constants::M_LANG_FRFR => ['Mathieu'=>'Mathieu','Celine'=>'Celine', 'Léa'=>'Léa'],
                constants::M_LANG_HIIN => ["Aditi"=>"Aditi"],
                //constants::M_LANG_HEIL => [],
                //constants::M_LANG_IDID => [],
                constants::M_LANG_ITIT => ['Carla'=>'Carla',  'Bianca'=>'Bianca', 'Giorgio'=>'Giorgio'],
                constants::M_LANG_JAJP => ['Takumi'=>'Takumi','Mizuki'=>'Mizuki'],
                constants::M_LANG_KOKR => ['Seoyan'=>'Seoyan'],
                //constants::M_LANG_MSMY => [],
                constants::M_LANG_NLNL => ["Ruben"=>"Ruben","Lotte"=>"Lotte"],
                constants::M_LANG_PTBR => ['Ricardo'=>'Ricardo', 'Vitoria'=>'Vitoria'],
                constants::M_LANG_PTPT => ["Ines"=>"Ines",'Cristiano'=>'Cristiano'],
                constants::M_LANG_RURU => ["Tatyana"=>"Tatyana","Maxim"=>"Maxim"],
                //constants::M_LANG_TAIN => [],
                //constants::M_LANG_TEIN => [],
                constants::M_LANG_TRTR => ['Filiz'=>'Filiz'],
                constants::M_LANG_ZHCN => ['Zhiyu']
        );


        $lang_options = self::get_lang_options();
        $ret=[];
        foreach($alllang as $lang=>$voices){
            foreach($voices as $voice){
             $ret[$voice]=$voice . ' - (' . $lang_options[$lang] . ')';
            }
        }
        return $ret;
    }

    public static function get_lang_options() {
        return array(
                constants::M_LANG_ARAE => get_string('ar-ae', constants::M_COMPONENT),
                constants::M_LANG_ARSA => get_string('ar-sa', constants::M_COMPONENT),
                constants::M_LANG_DADK => get_string('da-dk', constants::M_COMPONENT),
                constants::M_LANG_DEDE => get_string('de-de', constants::M_COMPONENT),
                constants::M_LANG_DECH => get_string('de-ch', constants::M_COMPONENT),
                constants::M_LANG_ENUS => get_string('en-us', constants::M_COMPONENT),
                constants::M_LANG_ENGB => get_string('en-gb', constants::M_COMPONENT),
                constants::M_LANG_ENAU => get_string('en-au', constants::M_COMPONENT),
                constants::M_LANG_ENIN => get_string('en-in', constants::M_COMPONENT),
                constants::M_LANG_ENIE => get_string('en-ie', constants::M_COMPONENT),
                constants::M_LANG_ENWL => get_string('en-wl', constants::M_COMPONENT),
                constants::M_LANG_ENAB => get_string('en-ab', constants::M_COMPONENT),
                constants::M_LANG_ESUS => get_string('es-us', constants::M_COMPONENT),
                constants::M_LANG_ESES => get_string('es-es', constants::M_COMPONENT),
                constants::M_LANG_FAIR => get_string('fa-ir', constants::M_COMPONENT),
                constants::M_LANG_FRCA => get_string('fr-ca', constants::M_COMPONENT),
                constants::M_LANG_FRFR => get_string('fr-fr', constants::M_COMPONENT),
                constants::M_LANG_HIIN => get_string('hi-in', constants::M_COMPONENT),
                constants::M_LANG_HEIL => get_string('he-il', constants::M_COMPONENT),
                constants::M_LANG_IDID => get_string('id-id', constants::M_COMPONENT),
                constants::M_LANG_ITIT => get_string('it-it', constants::M_COMPONENT),
                constants::M_LANG_JAJP => get_string('ja-jp', constants::M_COMPONENT),
                constants::M_LANG_KOKR => get_string('ko-kr', constants::M_COMPONENT),
                constants::M_LANG_MSMY => get_string('ms-my', constants::M_COMPONENT),
                constants::M_LANG_NLNL => get_string('nl-nl', constants::M_COMPONENT),
                constants::M_LANG_PTBR => get_string('pt-br', constants::M_COMPONENT),
                constants::M_LANG_PTPT => get_string('pt-pt', constants::M_COMPONENT),
                constants::M_LANG_RURU => get_string('ru-ru', constants::M_COMPONENT),
                constants::M_LANG_TAIN => get_string('ta-in', constants::M_COMPONENT),
                constants::M_LANG_TEIN => get_string('te-in', constants::M_COMPONENT),
                constants::M_LANG_TRTR => get_string('tr-tr', constants::M_COMPONENT),
                constants::M_LANG_ZHCN => get_string('zh-cn', constants::M_COMPONENT)
        );
        /*
          return array(
                "none"=>"No TTS",
                "af"=>"Afrikaans",
                "sq"=>"Albanian",
                "am"=>"Amharic",
                "ar"=>"Arabic",
                "hy"=>"Armenian",
                "az"=>"Azerbaijani",
                "eu"=>"Basque",
                "be"=>"Belarusian",
                "bn"=>"Bengali",
                "bh"=>"Bihari",
                "bs"=>"Bosnian",
                "br"=>"Breton",
                "bg"=>"Bulgarian",
                "km"=>"Cambodian",
                "ca"=>"Catalan",
                "zh-CN"=>"Chinese (Simplified)",
                "zh-TW"=>"Chinese (Traditional)",
                "co"=>"Corsican",
                "hr"=>"Croatian",
                "cs"=>"Czech",
                "da"=>"Danish",
                "nl"=>"Dutch",
                "en"=>"English",
                "eo"=>"Esperanto",
                "et"=>"Estonian",
                "fo"=>"Faroese",
                "tl"=>"Filipino",
                "fi"=>"Finnish",
                "fr"=>"French",
                "fy"=>"Frisian",
                "gl"=>"Galician",
                "ka"=>"Georgian",
                "de"=>"German",
                "el"=>"Greek",
                "gn"=>"Guarani",
                "gu"=>"Gujarati",
                "xx-hacker"=>"Hacker",
                "ha"=>"Hausa",
                "iw"=>"Hebrew",
                "hi"=>"Hindi",
                "hu"=>"Hungarian",
                "is"=>"Icelandic",
                "id"=>"Indonesian",
                "ia"=>"Interlingua",
                "ga"=>"Irish",
                "it"=>"Italian",
                "ja"=>"Japanese",
                "jw"=>"Javanese",
                "kn"=>"Kannada",
                "kk"=>"Kazakh",
                "rw"=>"Kinyarwanda",
                "rn"=>"Kirundi",
                "xx-klingon"=>"Klingon",
                "ko"=>"Korean",
                "ku"=>"Kurdish",
                "ky"=>"Kyrgyz",
                "lo"=>"Laothian",
                "la"=>"Latin",
                "lv"=>"Latvian",
                "ln"=>"Lingala",
                "lt"=>"Lithuanian",
                "mk"=>"Macedonian",
                "mg"=>"Malagasy",
                "ms"=>"Malay",
                "ml"=>"Malayalam",
                "mt"=>"Maltese",
                "mi"=>"Maori",
                "mr"=>"Marathi",
                "mo"=>"Moldavian",
                "mn"=>"Mongolian",
                "sr-ME"=>"Montenegrin",
                "ne"=>"Nepali",
                "no"=>"Norwegian",
                "nn"=>"Norwegian(Nynorsk)",
                "oc"=>"Occitan",
                "or"=>"Oriya",
                "om"=>"Oromo",
                "ps"=>"Pashto",
                "fa"=>"Persian",
                "xx-pirate"=>"Pirate",
                "pl"=>"Polish",
                "pt-BR"=>"Portuguese(Brazil)",
                "pt-PT"=>"Portuguese(Portugal)",
                "pa"=>"Punjabi",
                "qu"=>"Quechua",
                "ro"=>"Romanian",
                "rm"=>"Romansh",
                "ru"=>"Russian",
                "gd"=>"Scots Gaelic",
                "sr"=>"Serbian",
                "sh"=>"Serbo-Croatian",
                "st"=>"Sesotho",
                "sn"=>"Shona",
                "sd"=>"Sindhi",
                "si"=>"Sinhalese",
                "sk"=>"Slovak",
                "sl"=>"Slovenian",
                "so"=>"Somali",
                "es"=>"Spanish",
                "su"=>"Sundanese",
                "sw"=>"Swahili",
                "sv"=>"Swedish",
                "tg"=>"Tajik",
                "ta"=>"Tamil",
                "tt"=>"Tatar",
                "te"=>"Telugu",
                "th"=>"Thai",
                "ti"=>"Tigrinya",
                "to"=>"Tonga",
                "tr"=>"Turkish",
                "tk"=>"Turkmen",
                "tw"=>"Twi",
                "ug"=>"Uighur",
                "uk"=>"Ukrainian",
                "ur"=>"Urdu",
                "uz"=>"Uzbek",
                "vi"=>"Vietnamese",
                "cy"=>"Welsh",
                "xh"=>"Xhosa",
                "yi"=>"Yiddish",
                "yo"=>"Yoruba",
                "zu"=>"Zulu"
            );
        */
    }
}
