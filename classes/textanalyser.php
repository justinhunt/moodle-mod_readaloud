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
 * text analyser for poodll plugins
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 namespace mod_readaloud;

defined('MOODLE_INTERNAL') || die();

use mod_readaloud\constants;


/**
 * Functions used for producing a textanalysis
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class textanalyser {

    const CLOUDPOODLL = 'https://cloud.poodll.com';
    // const CLOUDPOODLL = 'https://vbox.poodll.com/cphost';



    /** @var string $token The cloudpoodll token. */
    protected $token;

    /** @var string $region The aws region. */
    protected $region;

    /** @var string $passage The aws region. */
    protected $passage;

    /** @var string $language The target language. */
    protected $language;

    /** @var string $userlanguage The users L1 language. */
    protected $userlanguage;

    /** @var string $targetembedding The vector for the 'correct'/model answer. */
    protected $targetembedding;

    /** @var string $targettopic The topic. */
    protected $targettopic;

        /**
         * The class constructor.
         *
         */
    public function __construct($token, $passage, $region, $language, $targetembedding=false, $userlanguage = false, $targettopic = false) {
        $this->token = $token;
        $this->region = $region;
        $this->passage = $passage;
        $this->language = $language;
        $this->targetembedding = $targetembedding;
        $this->userlanguage = $userlanguage;
        $this->targettopic = $targettopic;
    }

    // fetch lang server url, services incl. 'transcribe' , 'lm', 'lt', 'spellcheck'
    public function fetch_lang_server_url($service ='transcribe') {
        switch($this->region) {
            case 'useast1':
                $ret = 'https://useast.ls.poodll.com/';
                break;
            default:
                $ret = 'https://' . $this->region . '.ls.poodll.com/';
        }
        return $ret . $service;
    }


    public function fetch_sentence_stats($passage='') {

        if (empty($passage)) {
            $passage = $this->passage;
        }

        // count sentences
        $items = $this->split_into_sentences();
        $items = array_filter($items);
        $sentencecount = count($items);

        // longest sentence length
        // average sentence length
        $longestsentence = 1;
        $averagesentence = 1;
        $totallengths = 0;
        foreach ($items as $sentence) {
            $length = $this->mb_count_words($sentence, 0);
            if ($length > $longestsentence) {
                $longestsentence = $length;
            }
            $totallengths += $length;
        }
        if($totallengths > 0 && $sentencecount > 0){
            $averagesentence = round($totallengths / $sentencecount);
        }

        // return values
        return ['sentences' => $sentencecount, 'sentenceavg' => $averagesentence, 'sentencelongest' => $longestsentence];
    }

    public function is_english() {
        $ret = strpos($this->language, 'en') === 0;
        return $ret;
    }

    public function fetch_word_stats($passage='') {

        if(empty($passage)){
            $passage = $this->passage;
        }

        // prepare data
        $isenglish = $this->is_english();
        $lowerpassage = \core_text::strtolower($passage);
        $items = $this->mb_count_words($lowerpassage, 1); // returns array for format 1
        $totalwords = count($items);
        $items = array_unique($items);

        // unique words
        $uniquewords = count($items);

        // long words
        $longwords = 0;
        foreach ($items as $item) {
            if($isenglish) {
                if (self::count_syllables($item) > 2) {
                    $longwords++;
                }
            }else{
                if (\core_text::strlen($item) > 5) {
                    $longwords++;
                }
            }
        }

        // return results
        return ['words' => $totalwords, 'wordsunique' => $uniquewords, 'wordslong' => $longwords];
    }

    /*
     * count words,
     * return number of words for format 0
     * return words array for format 1
     */
    public function mb_count_words($string,  $format=0) {

        // wordcount will be different for different languages
        switch($this->language){
            // arabic
            case constants::M_LANG_ARAE:
            case constants::M_LANG_ARSA:
                // remove double spaces and count spaces remaining to estimate words
                $string = preg_replace('!\s+!', ' ', $string);
                switch($format){

                    case 1:
                        $ret = explode(' ', $string);
                        break;
                    case 0:
                    default:
                        $ret = substr_count($string, ' ') + 1;
                }

                break;

            // Chinese / Japanese / Korean - we do not do words, just characters
            case constants::M_LANG_ZHCN:
            case constants::M_LANG_JAJP:
            case constants::M_LANG_KOKR:
                preg_match_all('/./u', $string, $characters);
                $characterarray = $characters[0];
                switch($format){
                    case 1:
                        $ret = $characterarray;
                        break;
                    case 0:
                    default:
                        $ret = count($characterarray);
                }

                break;

            // others
            default:
                $words = diff::fetchWordArray($string);
                $wordcount = count($words);
                // $wordcount = str_word_count($string,$format);
                switch($format){

                    case 1:
                        $ret = $words;
                        break;
                    case 0:
                    default:
                        $ret = $wordcount;
                }

        }

        return $ret;
    }

    /**
     * count_syllables
     *
     * based on: https://github.com/e-rasvet/sassessment/blob/master/lib.php
     */
    public function count_syllables($word) {
        // https://github.com/vanderlee/phpSyllable (multilang)
        // https://github.com/DaveChild/Text-Statistics (English only)
        // https://pear.php.net/manual/en/package.text.text-statistics.intro.php
        // https://pear.php.net/package/Text_Statistics/docs/latest/__filesource/fsource_Text_Statistics__Text_Statistics-1.0.1TextWord.php.html
        $str = strtoupper($word);
        $oldlen = strlen($str);
        if ($oldlen < 2) {
            $count = 1;
        } else {
            $count = 0;

            // detect syllables for double-vowels
            $vowels = ['AA', 'AE', 'AI', 'AO', 'AU',
                    'EA', 'EE', 'EI', 'EO', 'EU',
                    'IA', 'IE', 'II', 'IO', 'IU',
                    'OA', 'OE', 'OI', 'OO', 'OU',
                    'UA', 'UE', 'UI', 'UO', 'UU'];
            $str = str_replace($vowels, '', $str);
            $newlen = strlen($str);
            $count += (($oldlen - $newlen) / 2);

            // detect syllables for single-vowels
            $vowels = ['A', 'E', 'I', 'O', 'U'];
            $str = str_replace($vowels, '', $str);
            $oldlen = $newlen;
            $newlen = strlen($str);
            $count += ($oldlen - $newlen);

            // adjust count for special last char
            switch (substr($str, -1)) {
                case 'E': $count--;
break;
                case 'Y': $count++;
break;
            };
        }
        return $count;
    }

    public function process_all_stats($targetwords=[]) {

            $stats = $this->calculate_stats($this->passage, $targetwords);
        if ($stats) {
            $stats['ideacount'] = $this->process_idea_count();
            $stats['cefrlevel'] = $this->process_cefr_level();
            $stats['relevance'] = $this->process_relevance();
            // something went wrong, but it might be used for grading. Lets give them 100, though it sucks
            if ( $stats['relevance'] == 0 || $stats['relevance'] == false) {
                $stats['relevance'] = 100;
            }
            $stats = array_merge($stats, $this->fetch_sentence_stats());
            $stats = array_merge($stats, $this->fetch_word_stats());
            $stats = array_merge($stats, $this->calc_grammarspell_stats($stats['words']));
            $stats = (object)$stats;
        }
            return $stats;
    }

    public function process_some_stats($targetwords=[]) {

        $stats = $this->calculate_stats($this->passage, $targetwords);
        if ($stats) {
            $stats['ideacount'] = $this->process_idea_count();
          //  $stats['cefrlevel'] = $this->process_cefr_level();
            $stats['relevance'] = $this->process_relevance();
            // something went wrong, but it might be used for grading. Lets give them 100, though it sucks
            if ( $stats['relevance'] == 0 || $stats['relevance'] == false) {
                $stats['relevance'] = 100;
            }
            $stats = array_merge($stats, $this->fetch_sentence_stats());
            $stats = array_merge($stats, $this->fetch_word_stats());
          //  $stats = array_merge($stats, $this->calc_grammarspell_stats($stats['words']));
            $stats = (object)$stats;
        }
        return $stats;
}

    public function process_grammar_correction($passage) {

        $ret = ['gcorrections' => false, 'gcerrors' => false, 'gcmatches' => false, 'gcerrorcount' => false];
        // If this is English then lets see if we can get a grammar correction
        // if(!empty($attempt->selftranscript) && self::is_english($moduleinstance)){
        if(!empty($passage)){
                $grammarcorrection = self::fetch_grammar_correction($passage);
            if ($grammarcorrection) {
                $ret['gcorrections'] = $grammarcorrection;

                // fetch and set GC Diffs
                list($gcerrors, $gcmatches, $gcinsertioncount) = $this->fetch_grammar_correction_diff($passage, $grammarcorrection);
                if(self::is_json($gcerrors)&& self::is_json($gcmatches)) {
                    $ret['gcerrors'] = $gcerrors;
                    $ret['gcmatches'] = $gcmatches;
                    $gcerrorobject = json_decode($gcerrors);
                    $ret['gcerrorcount'] = count(get_object_vars($gcerrorobject)) + $gcinsertioncount;
                }
            }

        }
        return $ret;
    }

    public function process_relevance($passage='', $targetembedding = false, $targettopic = false) {

        if (empty($passage)) {
            $passage = $this->passage;
        }
        if (!$targetembedding) {
            $targetembedding = $this->targetembedding;
        }
        if (!$targettopic) {
            $targettopic = $this->targettopic;
        }

        $relevance = false;
        if (!empty($passage)) {
            if ($targettopic !== false) {
                $relevance = $this->fetch_relevance_topic($targettopic, $passage);
            } else if ($targetembedding !== false) {
                $relevance = $this->fetch_relevance_semantic($targetembedding, $passage);
            }
        }
        if ($relevance !== false) {
            return $relevance;
        } else {
            return 0;
        }
    }

    //fetch the relevance by topic
    public function fetch_relevance_topic($topic, $passage='') {
        global $USER;

        // Default to 100% relevant if no TTS model.
        if ($topic === false || empty($topic)) {
            return 100;
        }

        // Use local passage if not set.
        if (empty($passage)) {
            $passage = $this->passage;
        }

        // The REST API we are calling.
        $functionname = 'local_cpapi_call_ai';

        $params = array();
        $params['wstoken'] = $this->token;
        $params['wsfunction'] = $functionname;
        $params['moodlewsrestformat'] = 'json';
        $params['action'] = 'get_topic_relevance';
        $params['appid'] = 'mod_readaloud';
        $params['prompt'] = $passage;
        $params['subject'] = $topic;
        $params['language'] = $this->language;
        $params['region'] = $this->region;
        $params['owner'] = hash('md5', $USER->username);


        $serverurl = self::CLOUDPOODLL . '/webservice/rest/server.php';
        $response = self::curl_fetch($serverurl, $params, 'post');
        if (!self::is_json($response)) {
            return false;
        }
        $payloadobject = json_decode($response);

        // ReturnCode > 0  indicates an error.
        if (!isset($payloadobject->returnCode) || $payloadobject->returnCode > 0) {
            return false;
            //if all good, then return the value
        } else if ($payloadobject->returnCode === 0) {
            $relevance = $payloadobject->returnMessage;
            if (is_numeric($relevance)) {
                $relevance = (int)round($relevance * 100, 0);
            } else {
                $relevance = false;
            }
            return $relevance;
        } else {
            return false;
        }
    }

    //fetch the relevance by semantic similarity
    public function fetch_relevance_semantic($model_or_modelembedding, $passage='') {
        global $USER;

        // Default to 100% relevant if no TTS model.
        if ($model_or_modelembedding === false || empty($model_or_modelembedding)) {
            return 100;
        }

        // Use local passage if not set.
        if (empty($passage)) {
            $passage = $this->passage;
        }

        //The REST API we are calling
        $functionname = 'local_cpapi_call_ai';

        $params = array();
        $params['wstoken'] = $this->token;
        $params['wsfunction'] = $functionname;
        $params['moodlewsrestformat'] = 'json';
        $params['action'] = 'get_semantic_sim';
        $params['appid'] = 'mod_readaloud';
        $params['prompt'] = $passage;
        $params['subject'] = $model_or_modelembedding;
        $params['language'] = $this->language;
        $params['region'] = $this->region;
        $params['owner'] = hash('md5', $USER->username);

        $serverurl = self::CLOUDPOODLL . '/webservice/rest/server.php';
        $response = self::curl_fetch($serverurl, $params,'post');
        if (!self::is_json($response)) {
            return false;
        }
        $payloadobject = json_decode($response);

        // ReturnCode > 0  indicates an error.
        if (!isset($payloadobject->returnCode) || $payloadobject->returnCode > 0) {
            return false;
            //if all good, then return the value
        } else if ($payloadobject->returnCode === 0) {
            $relevance = $payloadobject->returnMessage;
            if (is_numeric($relevance)) {
                $relevance = (int)round($relevance * 100, 0);
            } else {
                $relevance = false;
            }
            return $relevance;
        } else {
            return false;
        }
    }

    public function process_cefr_level($passage='') {

        if (empty($passage)) {
            $passage = $this->passage;
        }

        $cefrlevel = false;
        if (!empty($passage)) {
            $cefrlevel = $this->fetch_cefr_level($passage);
        }
        if ($cefrlevel !== false) {
            return $cefrlevel;
        } else {
            return "";
        }
    }

    public function process_idea_count($passage='') {

        if (empty($passage)) {
            $passage = $this->passage;
        }

        $ideacount = false;
        if (!empty($passage)) {
            $ideacount = $this->fetch_idea_count($passage);
        }
        if ($ideacount !== false) {
            return $ideacount;
        } else {
            return 0;
        }

    }


    // we leave it up to the grading logic how/if it adds the ai grades to gradebook
    public function calc_grammarspell_stats($wordcount, $passage='') {
        // init stats with defaults
        $stats = new \stdClass();
        $stats->autospell = "";
        $stats->autogrammar = "";
        $stats->autospellscore = 100;
        $stats->autogrammarscore = 100;
        $stats->autospellerrors = 0;
        $stats->autogrammarerrors = 0;

        if($passage == ''){
            $passage = $this->passage;
        }

        // if we have no words for whatever reason the calc will not work
        if(!$wordcount || $wordcount < 1) {
            // update spelling and grammar stats in DB
            return get_object_vars($stats);
        }

        // if this is not supported by lang tool (for now) lets just return
        // in future we want to use some AI features to support those languages, and weakly supported langtool langs
        if(!self::can_lang_tool($this->language)){
            return get_object_vars($stats);
        }

        // get lanserver lang string
        switch($this->language){
            case constants::M_LANG_ARSA:
            case constants::M_LANG_ARAE:
                $targetlanguage = 'ar';
                break;
            default:
                $targetlanguage = $this->language;
        }

        // fetch grammar stats
        $lturl = self::fetch_lang_server_url('lt');
        $postdata = ['text' => $passage, 'language' => $targetlanguage];
        $autogrammar = self::curl_fetch($lturl, $postdata, 'post');
        // default grammar score
        $autogrammarscore = 100;

        // fetch spell stats
        $spellcheckurl = self::fetch_lang_server_url('spellcheck');
        $spelltranscript = self::spellSafeCleanText($passage);
        $postdata = ['passage' => $spelltranscript, 'lang' => $targetlanguage];
        $autospell = self::curl_fetch($spellcheckurl, $postdata, 'post');
        // default spell score
        $autospellscore = 100;

        // calc grammar score
        if(self::is_json($autogrammar)) {
            // work out grammar
            $grammarobj = json_decode($autogrammar);
            $incorrect = count($grammarobj->matches);
            $stats->autogrammarerrors = $incorrect;
            $raw = $wordcount - ($incorrect * 3);
            if ($raw < 1) {
                $autogrammarscore = 0;
            } else {
                $autogrammarscore = round($raw / $wordcount, 2) * 100;
            }

            $stats->autogrammar = $autogrammar;
            $stats->autogrammarscore = $autogrammarscore;
        }

        // calculate spell score
        if(self::is_json($autospell)) {

            // work out spelling
            $spellobj = json_decode($autospell);
            $correct = 0;
            if($spellobj->status) {
                $spellarray = $spellobj->data->results;
                foreach ($spellarray as $val) {
                    if ($val) {
                        $correct++;
                    }else{
                        $stats->autospellerrors++;
                    }
                }

                if ($correct > 0) {
                    $autospellscore = round($correct / $wordcount, 2) * 100;
                } else {
                    $autospellscore = 0;
                }
            }
        }

        // update spelling and grammar stats in data object and return
        $stats->autospell = $autospell;
        $stats->autogrammar = $autogrammar;
        $stats->autospellscore = $autospellscore;
        $stats->autogrammarscore = $autogrammarscore;
        return get_object_vars($stats);
    }



    // calculate stats of transcript
    public function calculate_stats($passage='', $targetwords=[]) {

        if($passage == ''){
            $passage = $this->passage;
        }

        $stats = new \stdClass();
        $stats->turns = 0;
        $stats->words = 0;
        $stats->avturn = 0;
        $stats->longestturn = 0;
        $stats->targetwords = 0;
        $stats->totaltargetwords = 0;
        $stats->aiaccuracy = -1;

        if(!$passage || empty($passage)){
            return get_object_vars($stats);
        }

        $items = $this->split_into_sentences();
        $transcriptarray = array_filter($items);
        $totalturnlengths = 0;
        $jsontranscript = '';

        foreach($transcriptarray as $sentence){
            // wordcount will be different for different languages
            // for chinese / japanese / korean -  we dont even try, we just count characters.
            $wordcount = $this->mb_count_words($sentence, 0);

            if($wordcount === 0){continue;
            }
            $jsontranscript .= $sentence . ' ';
            $stats->turns++;
            $stats->words += $wordcount;
            $totalturnlengths += $wordcount;
            if($stats->longestturn < $wordcount){$stats->longestturn = $wordcount;
            }
        }
        if(!$stats->turns){
            return false;
        }
        $stats->avturn = round($totalturnlengths / $stats->turns);
        $stats->totaltargetwords = count($targetwords);

        $searchpassage = \core_text::strtolower($jsontranscript);
        foreach($targetwords as $theword){
            $searchword = self::cleanText($theword);
            if(empty($searchword) || empty($searchpassage)){
                $usecount = 0;
            }else {
                $usecount = substr_count($searchpassage, $searchword);
            }
            if($usecount){$stats->targetwords++;
            }
        }
        return get_object_vars($stats);
    }

    public static function can_lang_tool($language) {
        // https://dev.languagetool.org/languages
        switch($language){
            case constants::M_LANG_DEDE:
            case constants::M_LANG_DECH:
            case constants::M_LANG_ENUS:
            case constants::M_LANG_ENGB:
            case constants::M_LANG_ENAU:
            case constants::M_LANG_ENIN:
            case constants::M_LANG_ENIE:
            case constants::M_LANG_ENWL:
            case constants::M_LANG_ENAB:
            case constants::M_LANG_ESUS:
            case constants::M_LANG_ESES:
            case constants::M_LANG_FRCA:
            case constants::M_LANG_FRFR:
            case constants::M_LANG_HEIL:
            case constants::M_LANG_ITIT:
            case constants::M_LANG_NONO:
            case constants::M_LANG_NLNL:
            case constants::M_LANG_PTBR:
            case constants::M_LANG_PTPT:
            case constants::M_LANG_RURU:
            case constants::M_LANG_TAIN:
            case constants::M_LANG_PLPL:
            case constants::M_LANG_UKUA:
                return true;

            default:
                return false;
        }
    }

    public function split_into_sentences() {
        $items = [];
        switch($this->language){
            // Arabic
            case constants::M_LANG_ARAE:
            case constants::M_LANG_ARSA:
                $items = preg_split('/[!?.،؟]+(?![0-9])/', $this->passage);
                break;

            // Spanish
            case constants::M_LANG_ESES:
            case constants::M_LANG_ESUS:
                $items = preg_split('/[!?.¡¿]+(?![0-9])/', $this->passage);
                break;

            // hebrew
            case constants::M_LANG_HEIL:
                $items = preg_split('/[!?.׃׀]+(?![0-9])/', $this->passage);
                break;

             // Japanese
            case constants::M_LANG_JAJP:
                $items = preg_split('/[。！？]/u', $this->passage);
                break;

            // Chinese
            case constants::M_LANG_ZHCN:
                $items = preg_split('/[。！？]/u', $this->passage);
                break;

            // Korean
            case constants::M_LANG_KOKR:
                $items = preg_split('/[!?.。！？]+(?![0-9])/u', $this->passage);
                break;

            // Farsi
            case constants::M_LANG_FAIR:
                $items = preg_split('/[!?.،؟؛]+(?![0-9])/', $this->passage);
                break;

                // Tamil
            case constants::M_LANG_TAIN:
                $items = preg_split('/[புள்ளிவினைச்சொல்]+(?![0-9])/', $this->passage);
                break;

                // Telegu
            case constants::M_LANG_TEIN:
                $items = preg_split('/[పూర్ణవిరామమువిరామము]+(?![0-9])/', $this->passage);
                break;

                // Turkish
            case constants::M_LANG_TRTR:
                $items = preg_split('/[!.?…]+(?![0-9])/', $this->passage);
                break;

            // English and English Like languages
            case constants::M_LANG_ENUS:
            case constants::M_LANG_ENGB:
            case constants::M_LANG_ENAU:
            case constants::M_LANG_ENIN:
            case constants::M_LANG_ENIE:
            case constants::M_LANG_ENWL:
            case constants::M_LANG_ENAB:
            case constants::M_LANG_RURU:
            case constants::M_LANG_NONO:
            case constants::M_LANG_NBNO:
            case constants::M_LANG_DECH:
            case constants::M_LANG_DEDE:
            case constants::M_LANG_FRFR:
            case constants::M_LANG_FRCA:
            default:
                $items = preg_split('/[!?.]+(?![0-9])/', $this->passage);
                break;
        }
        return $items;
    }

    // fetch the grammar correction suggestions
    public function fetch_grammar_correction($passage='') {
        global $USER;

        // use local passage if not set
        if(empty($passage)){
            $passage = $this->passage;
        }

        // The REST API we are calling
        $functionname = 'local_cpapi_call_ai';

        $params = [];
        $params['wstoken'] = $this->token;
        $params['wsfunction'] = $functionname;
        $params['moodlewsrestformat'] = 'json';
        $params['action'] = 'request_grammar_correction';
        $params['appid'] = 'mod_readaloud';
        $params['prompt'] = $passage;
        $params['language'] = $this->language;
        $params['subject'] = 'none';
        $params['region'] = $this->region;
        $params['owner'] = hash('md5', $USER->username);

        // log.debug(params);

        $serverurl = self::CLOUDPOODLL . '/webservice/rest/server.php';
        $response = self::curl_fetch($serverurl, $params);
        if (!self::is_json($response)) {
            return false;
        }
        $payloadobject = json_decode($response);

        // returnCode > 0  indicates an error
        if (!isset($payloadobject->returnCode) || $payloadobject->returnCode > 0) {
            return false;
            // if all good, then lets do the embed
        } else if ($payloadobject->returnCode === 0) {
            $correction = $payloadobject->returnMessage;
            // clean up the correction a little
            if(\core_text::strlen($correction) > 0){
                $correction = \core_text::trim_utf8_bom($correction);
                $charone = substr($correction, 0, 1);
                if(preg_match('/^[.,:!?;-]/', $charone)){
                    $correction = substr($correction, 1);
                }
            }

            return $correction;
        } else {
            return false;
        }
    }

    // fetch the CEFR Level
    public function fetch_cefr_level($passage='') {
        global $USER;

        if(empty($passage)){
            $passage = $this->passage;
        }

        // The REST API we are calling
        $functionname = 'local_cpapi_call_ai';

        $params = [];
        $params['wstoken'] = $this->token;
        $params['wsfunction'] = $functionname;
        $params['moodlewsrestformat'] = 'json';
        $params['action'] = 'predict_cefr';
        $params['appid'] = 'mod_readaloud';
        $params['prompt'] = $passage;// urlencode($passage);
        $params['language'] = $this->language;
        $params['subject'] = 'none';
        $params['region'] = $this->region;
        $params['owner'] = hash('md5', $USER->username);

        // log.debug(params);

        $serverurl = self::CLOUDPOODLL . '/webservice/rest/server.php';
        $response = self::curl_fetch($serverurl, $params);
        if (!self::is_json($response)) {
            return false;
        }
        $payloadobject = json_decode($response);

        // returnCode > 0  indicates an error
        if (!isset($payloadobject->returnCode) || $payloadobject->returnCode > 0) {
            return false;
            // if all good, then return the value
        } else if ($payloadobject->returnCode === 0) {
            $cefr = $payloadobject->returnMessage;
            // make pretty sure its a CEFR level
            if(\core_text::strlen($cefr) !== 2){
                $cefr = false;
            }

            return $cefr;
        } else {
            return false;
        }
    }

    // fetch embedding
    public function fetch_embedding($passage='') {
        global $USER;

        if(empty($passage)){
            $passage = $this->passage;
        }

        // The REST API we are calling
        $functionname = 'local_cpapi_call_ai';

        $params = [];
        $params['wstoken'] = $this->token;
        $params['wsfunction'] = $functionname;
        $params['moodlewsrestformat'] = 'json';
        $params['action'] = 'get_embedding';
        $params['appid'] = 'mod_readaloud';
        $params['prompt'] = $passage;// urlencode($passage);
        $params['language'] = $this->language;
        $params['subject'] = 'none';
        $params['region'] = $this->region;
        $params['owner'] = hash('md5', $USER->username);

        // log.debug(params);

        $serverurl = self::CLOUDPOODLL . '/webservice/rest/server.php';
        $response = self::curl_fetch($serverurl, $params);
        if (!self::is_json($response)) {
            return false;
        }
        $payloadobject = json_decode($response);

        // returnCode > 0  indicates an error
        if (!isset($payloadobject->returnCode) || $payloadobject->returnCode > 0) {
            return false;
            // if all good, then process  it
        } else if ($payloadobject->returnCode === 0) {
            $returndata = $payloadobject->returnMessage;
            // clean up the correction a little
            if(!self::is_json($returndata)){
                $embedding = false;
            }else{
                $dataobject = json_decode($returndata);
                if(is_array($dataobject)&&$dataobject[0]->object == 'embedding') {
                    $embedding = json_encode($dataobject[0]->embedding);
                }else{
                    $embedding = false;
                }
            }
            return $embedding;
        } else {
            return false;
        }
    }

    // fetch the Idea Count
    public function fetch_idea_count($passage='') {
        global $USER;

        if(empty($passage)){
            $passage = $this->passage;
        }

        // The REST API we are calling
        $functionname = 'local_cpapi_call_ai';

        $params = [];
        $params['wstoken'] = $this->token;
        $params['wsfunction'] = $functionname;
        $params['moodlewsrestformat'] = 'json';
        $params['action'] = 'count_unique_ideas';
        $params['appid'] = 'mod_readaloud';
        $params['prompt'] = $passage;// urlencode($passage);
        $params['language'] = $this->language;
        $params['subject'] = 'none';
        $params['region'] = $this->region;
        $params['owner'] = hash('md5', $USER->username);

        // log.debug(params);

        $serverurl = self::CLOUDPOODLL . '/webservice/rest/server.php';
        $response = self::curl_fetch($serverurl, $params);
        if (!self::is_json($response)) {
            return false;
        }
        $payloadobject = json_decode($response);

        // returnCode > 0  indicates an error
        if (!isset($payloadobject->returnCode) || $payloadobject->returnCode > 0) {
            return false;
            // if all good, then lets do the embed
        } else if ($payloadobject->returnCode === 0) {
            $ideacount = $payloadobject->returnMessage;
            // clean up the correction a little
            if(!is_number($ideacount)){
                $ideacount = false;
            }

            return $ideacount;
        } else {
            return false;
        }
    }

    public function process_modelanswer_stats($passage='') {
        $ret = ['embedding' => false, 'ideacount' => false];

        if(empty($passage)){
            $passage  = $this->passage;
        }

        if(empty($passage)) {
            return $ret;
        }

        $embedding = self::fetch_embedding($passage);
        $ideacount = self::fetch_idea_count($passage);
        if($embedding){
            $ret['embedding'] = $embedding;
        }
        if($ideacount){
            $ret['ideacount'] = $ideacount;
        }
        return $ret;
    }

    /*
    * Clean word of things that might prevent a match
    * i) lowercase it
    * ii) remove html characters
    * iii) replace any line ends with spaces (so we can "split" later)
    * iv) remove punctuation
    *
    */
    public static function cleantext($thetext) {
        // lowercaseify
        $thetext = \core_text::strtolower($thetext);

        // remove any html
        $thetext = strip_tags($thetext);

        // replace all line ends with empty strings
        $thetext = preg_replace('#\R+#', '', $thetext);

        // remove punctuation
        // see https://stackoverflow.com/questions/5233734/how-to-strip-punctuation-in-php
        // $thetext = preg_replace("#[[:punct:]]#", "", $thetext);
        // https://stackoverflow.com/questions/5689918/php-strip-punctuation
        $thetext = preg_replace("/[[:punct:]]+/", "", $thetext);

        // remove bad chars
        $bopen = "“";
        $bclose = "”";
        $bsopen = '‘';
        $bsclose = '’';
        $bads = [$bopen, $bclose, $bsopen, $bsclose];
        foreach($bads as $bad){
            $thetext = str_replace($bad, '', $thetext);
        }

        // remove double spaces
        // split on spaces into words
        $textbits = explode(' ', $thetext);
        // remove any empty elements
        $textbits = array_filter($textbits, function($value) { return $value !== '';
        });
        $thetext = implode(' ', $textbits);
        return $thetext;
    }

    /*
    * Clean word of things that might prevent a match
    * i) remove html characters
    * ii) replace any line ends with spaces (so we can "split" later)
    *
    */
    public static function spellsafecleantext($thetext) {

        // remove any html
        $thetext = strip_tags($thetext);

        // replace all line ends with empty strings
        $thetext = preg_replace('#\R+#', '', $thetext);

        // remove bad chars
        $bopen = "“";
        $bclose = "”";
        $bsopen = '‘';
        $bsclose = '’';
        $bads = [$bopen, $bclose, $bsopen, $bsclose];
        foreach($bads as $bad){
            $thetext = str_replace($bad, '', $thetext);
        }

        // remove double spaces
        // split on spaces into words
        $textbits = explode(' ', $thetext);
        // remove any empty elements
        $textbits = array_filter($textbits, function($value) { return $value !== '';
        });
        $thetext = implode(' ', $textbits);
        return $thetext;
    }




    // we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
    // this is our helper
    // we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
    // this is our helper
    public static function curl_fetch($url, $postdata=false, $method='get') {
        global $CFG;

        require_once($CFG->libdir.'/filelib.php');
        $curl = new \curl();

        if($method == 'post') {
            $result = $curl->post($url, $postdata);
        }else{
            $result = $curl->get($url, $postdata);
        }
        return $result;
    }


    public static function fetch_spellingerrors($stats, $transcript) {
        $spellingerrors = [];
        $usetranscript = self::cleanText($transcript);
        // sanity check
        if(empty($usetranscript) ||!self::is_json($stats->autospell)){
            return $spellingerrors;
        }

        // return errors
        $spellobj = json_decode($stats->autospell);
        if($spellobj->status) {
            $spellarray = $spellobj->data->results;
            $wordarray = explode(' ', $usetranscript);
            for($index = 0; $index < count($spellarray); $index++) {
                if (!$spellarray[$index]) {
                    $spellingerrors[] = $wordarray[$index];
                }
            }
        }
        return $spellingerrors;

    }

    public static function fetch_grammarerrors($stats, $transcript) {
        $usetranscript = self::cleanText($transcript);
        // sanity check
        if(empty($usetranscript) ||!self::is_json($stats->autogrammar)){
            return [];
        }

        // return errors
        $grammarobj = json_decode($stats->autogrammar);
        return $grammarobj->matches;

    }

    public static function fetch_grammar_correction_diff($selftranscript, $correction) {

        // turn the passage and transcript into an array of words
        $alternatives = diff::fetchAlternativesArray('');
        $wildcards = diff::fetchWildcardsArray($alternatives);
        $passagebits = diff::fetchWordArray($selftranscript);
        $transcriptbits = diff::fetchWordArray($correction);

        // fetch sequences of transcript/passage matched words
        // then prepare an array of "differences"
        $passagecount = count($passagebits);
        $transcriptcount = count($transcriptbits);
        // rough estimate of insertions
        $insertioncount = $transcriptcount - $passagecount;
        if($insertioncount < 0){$insertioncount = 0;
        }

        $language = constants::M_LANG_ENUS;
        $sequences = diff::fetchSequences($passagebits, $transcriptbits, $alternatives, $language);

        // fetch diffs
        $diffs = diff::fetchDiffs($sequences, $passagecount, $transcriptcount);
        $diffs = diff::applyWildcards($diffs, $passagebits, $wildcards);

        // from the array of differences build error data, match data, markers, scores and metrics
        $errors = new \stdClass();
        $matches = new \stdClass();
        $currentword = 0;
        $lastunmodified = 0;
        // loop through diffs
        foreach($diffs as $diff){
            $currentword++;
            switch($diff[0]){
                case Diff::UNMATCHED:
                    // we collect error info so we can count and display them on passage
                    $error = new \stdClass();
                    $error->word = $passagebits[$currentword - 1];
                    $error->wordnumber = $currentword;
                    $errors->{$currentword} = $error;
                    break;

                case Diff::MATCHED:
                    // we collect match info so we can play audio from selected word
                    $match = new \stdClass();
                    $match->word = $passagebits[$currentword - 1];
                    $match->pposition = $currentword;
                    $match->tposition = $diff[1];
                    $match->audiostart = 0;// not meaningful when processing corrections
                    $match->audioend = 0;// not meaningful when processing corrections
                    $match->altmatch = $diff[2];// not meaningful when processing corrections
                    $matches->{$currentword} = $match;
                    $lastunmodified = $currentword;
                    break;

                default:
                    // do nothing
                    // should never get here

            }
        }
        $sessionendword = $lastunmodified;

        // discard errors that happen after session end word.
        $errorcount = 0;
        $finalerrors = new \stdClass();
        foreach($errors as $key => $error) {
            if ($key < $sessionendword) {
                $finalerrors->{$key} = $error;
                $errorcount++;
            }
        }
        // finalise and serialise session errors
        $sessionerrors = json_encode($finalerrors);
        $sessionmatches = json_encode($matches);

        return [$sessionerrors, $sessionmatches, $insertioncount];

    }

    public static function fetch_duration_from_transcript($jsontranscript) {
        $transcript = json_decode($jsontranscript);
        $titems = $transcript->results->items;
        $twords = [];
        foreach($titems as $titem){
            if($titem->type == 'pronunciation'){
                $twords[] = $titem;
            }
        }
        $lastindex = count($twords);
        if($lastindex > 0){
            return $twords[$lastindex - 1]->end_time;
        }else{
            return 0;
        }
    }

    // see if this is truly json or some error
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


}
