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
 * User: justin
 * Date: 17/08/29
 * Time: 16:12
 */

namespace mod_readaloud;


class quizhelper {

    protected $cm;
    protected $context;
    protected $mod;
    protected $items;
    protected $course;

    public function __construct($cm) {
        global $DB;
        $this->cm = $cm;
        $this->mod = $DB->get_record(constants::M_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);
        $this->context = \context_module::instance($cm->id);
        $this->course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    }

    public function fetch_item_count() {
        global $DB;
        if (!$this->items) {
            $this->items = $DB->get_records(constants::M_QTABLE, ['readaloudid' => $this->mod->id], 'itemorder ASC');
        }
        if ($this->items) {
            return count($this->items);
        } else {
            return 0;
        }
    }

    public function fetch_items() {
        global $DB;
        if (!$this->items) {
            $this->items = $DB->get_records(constants::M_QTABLE, ['readaloudid' => $this->mod->id], 'itemorder ASC');
        }
        if ($this->items) {
            return $this->items;
        } else {
            return [];
        }
    }

    public function fetch_latest_attempt($userid) {
        global $DB;

        $attempts = $DB->get_records(constants::M_USERTABLE, ['readaloudid' => $this->mod->id, 'userid' => $userid], 'id DESC');
        if($attempts){
            $attempt = array_shift($attempts);
            return $attempt;
        }else{
            return false;
        }
    }

    /* return the test items suitable for js to use */
    public function fetch_quiz_items_for_js($renderer=false) {
        global $CFG, $USER, $OUTPUT;

        $items = $this->fetch_items();

        // first confirm we are authorised before we try to get the token
        $config = get_config(constants::M_COMPONENT);
        if (empty($config->apiuser) || empty($config->apisecret)){
            $errormessage = get_string('nocredentials', constants::M_COMPONENT,
                    $CFG->wwwroot . constants::M_PLUGINSETTINGS);
            // return error?
            $token = false;
        } else {
            // fetch token
            $token = utils::fetch_token($config->apiuser, $config->apisecret);

            // check token authenticated and no errors in it
            $errormessage = utils::fetch_token_error($token);
            if (!empty($errormessage)) {
                // return error?
                // return $this->show_problembox($errormessage);
            }
        }

        // prepare data array for test
        $testitems = [];
        $currentitem = 0;
        foreach ($items as $item) {
            $currentitem++;
            $titem = utils::fetch_item_from_itemrecord($item, $this->mod, $this->context);
            $titem->set_token($token);
            $titem->set_currentnumber($currentitem);
            // add our item to test
            if(!$renderer){$renderer = $OUTPUT;
            }
            $testitems[] = $titem->export_for_template($renderer);
        }//end of loop
        return $testitems;
    }

    /* called from ajaxhelper to grade test */
    public function grade_test($answers) {

        $items = $this->fetch_items();
        $currentitem = 0;
        $score = 0;
        foreach ($items as $item) {
            $currentitem++;
            if (isset($answers->{'' . $currentitem})) {
                if ($item->correctanswer == $answers->{'' . $currentitem}) {
                    $score++;
                }
            }
        }
        if($score == 0 || count($items) == 0){
            return 0;
        }else{
            return floor(100 * $score / count($items));
        }
    }

    public static function render_passage($passage, $markuptype='passage') {
        // load the HTML document
        $doc = new \DOMDocument;
        // it will assume ISO-8859-1  encoding, so we need to hint it:
        // see: http://stackoverflow.com/questions/8218230/php-domdocument-loadhtml-not-encoding-utf-8-correctly

        //The old way ... throws errors on PHP 8.2+
        //$safepassage = mb_convert_encoding($passage, 'HTML-ENTITIES', 'UTF-8');
        //@$doc->loadHTML($safepassage, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

        //This could work, but on some occasions the doc has a meta header already .. hmm
        //$safepassage = mb_convert_encoding($passage, 'HTML-ENTITIES', 'UTF-8');
        //@$doc->loadHTML('<?xml encoding="utf-8" ? >' . $safepassage, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

        //The new way .. incomprehensible but works
        $safepassage = htmlspecialchars($passage, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        @$doc->loadHTML(mb_encode_numericentity($safepassage, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));

        // select all the text nodes
        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query('//text()');

        // Base CSS class.
        // We will add _mu_passage_word and _mu_passage_space. Can be customized though
        $cssword = constants::M_CLASS . '_mu_' .$markuptype . '_word';
        $cssspace = constants::M_CLASS . '_mu_' .$markuptype . '_space';

        // original CSS classes
        // The original classes are to show the original passage word before or after the corrections word
        // because of the layout, "rewritten/added words" [corrections] will show in green, after the original words [red]
        // but "removed(omitted) words" [corrections] will show as a green space  after the original words [red]
        // so the span layout for each word in the corrections is:
        // [original_preword][correctionsword][original_postword][correctionsspace]
        // suggested word: (original)He eat apples => (corrected)He eats apples =>
        // [original_preword: "eat->"][correctionsword: "eats"][original_postword][correctionsspace]
        // removed(omitted) word: (original)He eat devours the apples=> (corrected)He devours the apples =>
        // [original_preword: ][correctionsword: "He"][original_postword: "eat->" ][correctionsspace: " "]

        $cssoriginalpreword = constants::M_CLASS . '_mu_original_preword';
        $cssoriginalpostword = constants::M_CLASS . '_mu_original_postword';

        // Init the text count.
        $wordcount = 0;
        foreach ($nodes as $node) {
            $trimmednode = utils::super_trim($node->nodeValue);
            if (empty($trimmednode)) {
                continue;
            }

            // Explode missed new lines that had been copied and pasted. eg A[newline]B was not split and was one word.
            // This resulted in ai selected error words, having different index to their passage text counterpart.
            $seperator = ' ';
            // $words = explode($seperator, $node->nodeValue);

            $nodevalue = self::lines_to_brs($node->nodeValue, $seperator);
            $words = preg_split('/\s+/', $nodevalue);

            foreach ($words as $word) {
                // If its a new line character from lines_to_brs we add it, but not as a word.
                if ($word == '<br>') {
                    $newnode = $doc->createElement('br', $word);
                    $node->parentNode->appendChild($newnode);
                    continue;
                }

                $wordcount++;
                $newnode = $doc->createElement('span', $word);
                $spacenode = $doc->createElement('span', $seperator);
                // $newnode->appendChild($spacenode);
                // print_r($newnode);
                $newnode->setAttribute('id', $cssword . '_' . $wordcount);
                $newnode->setAttribute('data-wordnumber', $wordcount);
                $newnode->setAttribute('class', $cssword);
                $spacenode->setAttribute('id', $cssspace . '_' . $wordcount);
                $spacenode->setAttribute('data-wordnumber', $wordcount);
                $spacenode->setAttribute('class', $cssspace);
                // Original pre node.
                if ($markuptype !== 'passage') {
                    $originalprenode = $doc->createElement('span', '');
                    $originalprenode->setAttribute('id', $cssoriginalpreword . '_' . $wordcount);
                    $originalprenode->setAttribute('data-wordnumber', $wordcount);
                    $originalprenode->setAttribute('class', $cssoriginalpreword);

                }
                // Original post node.
                if ($markuptype !== 'passage') {
                    $originalpostnode = $doc->createElement('span', '');
                    $originalpostnode->setAttribute('id', $cssoriginalpostword . '_' . $wordcount);
                    $originalpostnode->setAttribute('data-wordnumber', $wordcount);
                    $originalpostnode->setAttribute('class', $cssoriginalpostword);

                }
                // add nodes to doc
                if ($markuptype == 'passage') {
                    $node->parentNode->appendChild($newnode);
                    $node->parentNode->appendChild($spacenode);
                } else {
                    $node->parentNode->appendChild($originalprenode);
                    $node->parentNode->appendChild($newnode);
                    $node->parentNode->appendChild($originalpostnode);
                    $node->parentNode->appendChild($spacenode);
                }
                // $newnode = $doc->createElement('span', $word);
            }
            $node->nodeValue = "";
        }

        $usepassage = $doc->saveHTML();
        // Remove container 'p' tags, they mess up formatting in solo.
        $usepassage = str_replace('<p>', '', $usepassage);
        $usepassage = str_replace('</p>', '', $usepassage);

        if($markuptype == 'passage') {
            $ret = \html_writer::div($usepassage, constants::M_CLASS . '_original ' . constants::M_CLASS . '_summarytranscriptplaceholder');
        }else{
            $ret = \html_writer::div($usepassage, constants::M_CLASS . '_corrections ');
        }
        return $ret;
    }
 /*
    * Turn a passage with text "lines" into html "brs"
    *
    * @param String The passage of text to convert
    * @param String An optional pad on each replacement (needed for processing when marking up words as spans in passage)
    * @return String The converted passage of text
    */
    public static function lines_to_brs($passage, $seperator='') {
        // See https://stackoverflow.com/questions/5946114/how-to-replace-newline-or-r-n-with-br .
        return str_replace("\r\n", $seperator . '<br>' . $seperator, $passage);
        // This is better but we can not pad the replacement and we need that.
        /* return nl2br($passage); */
    }


}//end of class
