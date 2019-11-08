<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace mod_readaloud\output;

use \mod_readaloud\constants;
use \mod_readaloud\utils;

class gradenow_renderer extends \plugin_renderer_base {

    public function render_attempt_scoresheader($gradenow) {
        $audio = $this->render_audioplayer($gradenow->attemptdetails('audiourl'));
        $wpm = $this->render_wpmdetails();
        $accuracy = $this->render_accuracydetails();
        $sessionscore = $this->render_sessionscoredetails();
        $mistakes = $this->render_mistakedetails();
        $actionheader = \html_writer::div($audio . $mistakes . $wpm . $accuracy . $sessionscore,
                constants::M_GRADING_ACTION_CONTAINER, array('id' => constants::M_GRADING_ACTION_CONTAINER));
        return $actionheader;
    }

    public function render_gradenow($gradenow) {
        $actionheader = $this->render_attempt_scoresheader($gradenow);
        $ret = $this->render_attempt_header($gradenow->attemptdetails('userfullname'));
        $ret .= $actionheader;
        $ret .= $this->render_passage($gradenow->attemptdetails('passage'));
        $ret .= $this->render_passageactions();

        return $ret;
    }

    public function render_userreview($gradenow) {
        $actionheader = $this->render_attempt_scoresheader($gradenow);
        $ret = $actionheader;
        $thepassage = $this->render_passage($gradenow->attemptdetails('passage'));
        $ret .= \html_writer::div($thepassage, constants::M_CLASS . '_postattempt');
        return $ret;
    }

    public function render_machinereview($gradenow, $debug = false) {
        $actionheader = $this->render_attempt_scoresheader($gradenow);
        $ret = $this->render_machinegrade_attempt_header($gradenow->attemptdetails('userfullname'));
        $ret .= $actionheader;
        if ($debug) {
            $passage = $this->render_passage($gradenow->attemptdetails('passage'));
            $ret .= \html_writer::tag('span', $passage, array('class' => constants::M_CLASS . '_debug'));
        } else {
            $ret .= $this->render_passage($gradenow->attemptdetails('passage'));
        }
        return $ret;
    }

    public function render_machinereview_buttons($gradenow) {
        $attemptid = $gradenow->attemptdetails('id');
        $readaloudid = $gradenow->attemptdetails('readaloudid');
        $url = new \moodle_url(constants::M_URL . '/grading.php',
                array('action' => 'gradenow', 'n' => $readaloudid, 'attemptid' => $attemptid));
        $btn = new \single_button($url, get_string('gradethisattempt', constants::M_COMPONENT), 'post');
        $gradenowbutton = $this->output->render($btn);

        $spotcheckbutton = \html_writer::tag('button',
                get_string('spotcheckbutton', constants::M_COMPONENT),
                array('type' => 'button', 'id' => constants::M_CLASS . '_spotcheckbutton',
                        'class' => constants::M_CLASS . '_spotcheckbutton btn btn-success', 'disabled' => true));

        $transcriptcheckbutton = \html_writer::tag('button',
                get_string('transcriptcheckbutton', constants::M_COMPONENT),
                array('type' => 'button', 'id' => constants::M_CLASS . '_transcriptcheckbutton',
                        'class' => constants::M_CLASS . '_transcriptcheckbutton btn btn-warning'));

        $ret = \html_writer::div($gradenowbutton . $spotcheckbutton . $transcriptcheckbutton,
                constants::M_CLASS . '_grading_passageactions');
        return $ret;
    }

    public function render_debuginfo($debugsequences, $transcript, $fulltranscript) {
        $div_fulltranscript = \html_writer::div('<pre>' . print_r(json_decode($fulltranscript), true) . '</pre>',
                constants::M_CLASS . '_grading_debugfulltranscript');
        //sequences
        $debug_sequences = '';
        foreach ($debugsequences as $sequence) {
            $debug_sequences .= \html_writer::tag('span', '<pre>' . print_r($sequence, true) . '</pre>',
                    array('class' => constants::M_CLASS . '_debugsequence'));
        }
        $div_sequences = \html_writer::div($debug_sequences, constants::M_CLASS . '_grading_debugsequences');

        //transcript words
        $t_words = explode(' ', $transcript);
        $t_usewords = [];
        $t_count = 0;
        foreach ($t_words as $t_word) {
            $t_count++;
            $t_usewords[] = \html_writer::tag('span', $t_word,
                    array('class' => constants::M_CLASS . '_debug_transcriptword', 'data-wordnumber' => $t_count));
        }
        $div_transcript = \html_writer::div(implode(' ', $t_usewords), constants::M_CLASS . '_grading_debugtranscript');

        $h_transcript = $this->output->heading('transcript', 5);
        $h_sequences = $this->output->heading('sequences', 5);
        $h_fulltranscript = $this->output->heading('full transcript', 5);
        $ret = \html_writer::div($h_transcript . $div_transcript
                . $h_sequences . $div_sequences
        //. $h_fulltranscript . $div_fulltranscript,constants::M_CLASS . '_grading_debuginfo'
        );
        return $ret;
    }

    public function render_attempt_header($username) {
        $ret = $this->output->heading(get_string('showingattempt', constants::M_COMPONENT, $username), 4);
        return $ret;
    }

    public function render_machinegrade_attempt_header($username) {
        $ret = $this->output->heading(get_string('showingmachinegradedattempt', constants::M_COMPONENT, $username), 3);
        return $ret;
    }

    public function render_passage($passage) {
        // load the HTML document
        $doc = new \DOMDocument;
        // it will assume ISO-8859-1  encoding, so we need to hint it:
        //see: http://stackoverflow.com/questions/8218230/php-domdocument-loadhtml-not-encoding-utf-8-correctly
        @$doc->loadHTML(mb_convert_encoding($passage, 'HTML-ENTITIES', 'UTF-8'));

        // select all the text nodes
        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query('//text()');
        //init the text count
        $wordcount = 0;
        foreach ($nodes as $node) {
            $trimmednode = trim($node->nodeValue);
            if (empty($trimmednode)) {
                continue;
            }

            //explode missed new lines that had been copied and pasted. eg A[newline]B was not split and was one word
            //This resulted in ai selected error words, having different index to their passage text counterpart
            $seperator = ' ';
            //$words = explode($seperator, $node->nodeValue);

            $nodevalue = utils::lines_to_brs($node->nodeValue, $seperator);
            $words = preg_split('/\s+/', $nodevalue);

            foreach ($words as $word) {
                //if its a new line character from lines_to_brs we add it, but not as a word
                if ($word == '<br>') {
                    $newnode = $doc->createElement('br', $word);
                    $node->parentNode->appendChild($newnode);
                    continue;
                }

                $wordcount++;
                $newnode = $doc->createElement('span', $word);
                $spacenode = $doc->createElement('span', $seperator);
                //$newnode->appendChild($spacenode);
                //print_r($newnode);
                $newnode->setAttribute('id', constants::M_CLASS . '_grading_passageword_' . $wordcount);
                $newnode->setAttribute('data-wordnumber', $wordcount);
                $newnode->setAttribute('class', constants::M_CLASS . '_grading_passageword');
                $spacenode->setAttribute('class', constants::M_CLASS . '_grading_passagespace');
                $spacenode->setAttribute('data-wordnumber', $wordcount);
                $spacenode->setAttribute('id', constants::M_CLASS . '_grading_passagespace_' . $wordcount);
                $node->parentNode->appendChild($newnode);
                $node->parentNode->appendChild($spacenode);
                $newnode = $doc->createElement('span', $word);
            }
            $node->nodeValue = "";
        }

        $usepassage = $doc->saveHTML();

        $ret = \html_writer::div($usepassage, constants::M_CLASS . '_grading_passagecont');
        return $ret;
    }

    public function render_passageactions() {

        $gradingbutton = \html_writer::tag('button',
                get_string('gradingbutton', constants::M_COMPONENT),
                array('type' => 'button', 'id' => constants::M_CLASS . '_gradingbutton',
                        'class' => constants::M_CLASS . '_gradingbutton btn btn-primary', 'disabled' => true));

        $spotcheckbutton = \html_writer::tag('button',
                get_string('spotcheckbutton', constants::M_COMPONENT),
                array('type' => 'button', 'id' => constants::M_CLASS . '_spotcheckbutton',
                        'class' => constants::M_CLASS . '_spotcheckbutton btn btn-success'));

        $transcriptcheckbutton = \html_writer::tag('button',
                get_string('transcriptcheckbutton', constants::M_COMPONENT),
                array('type' => 'button', 'id' => constants::M_CLASS . '_transcriptcheckbutton',
                        'class' => constants::M_CLASS . '_transcriptcheckbutton btn btn-warning'));

        $clearbutton = \html_writer::tag('button',
                get_string('doclear', constants::M_COMPONENT),
                array('type' => 'button', 'id' => constants::M_CLASS . '_clearbutton',
                        'class' => constants::M_CLASS . '_clearbutton btn btn-link'));

        $buttons = $gradingbutton . $spotcheckbutton . $transcriptcheckbutton . $clearbutton;

        $container = \html_writer::div($buttons, constants::M_CLASS . '_grading_passageactions');
        return $container;
    }

    public function render_audioplayer($audiourl) {
        $audioplayer = \html_writer::tag('audio', '',
                array('controls' => '', 'src' => $audiourl, 'id' => constants::M_GRADING_PLAYER));
        $ret = \html_writer::div($audioplayer, constants::M_GRADING_PLAYER_CONTAINER,
                array('id' => constants::M_GRADING_PLAYER_CONTAINER));
        return $ret;
    }

    public function render_hiddenaudioplayer() {
        $audioplayer = \html_writer::tag('audio', '',
                array('src' => '', 'id' => constants::M_HIDDEN_PLAYER, 'class' => constants::M_HIDDEN_PLAYER));
        return $audioplayer;
    }

    public function render_wpmdetails() {
        global $CFG;
        $title = \html_writer::div(get_string('wpm', constants::M_COMPONENT), 'panel-heading');
        $score = \html_writer::div('0', constants::M_GRADING_SCORE . ' panel-body', array('id' => constants::M_GRADING_WPM_SCORE));
        $ret = \html_writer::div($title . $score, constants::M_GRADING_WPM_CONTAINER . ' panel panel-primary',
                array('id' => constants::M_GRADING_WPM_CONTAINER));
        return $ret;
    }

    public function render_sessionscoredetails() {
        global $CFG;
        $title = \html_writer::div(get_string('grade_p', constants::M_COMPONENT), 'panel-heading');
        $score = \html_writer::div('0', constants::M_GRADING_SCORE . ' panel-body',
                array('id' => constants::M_GRADING_SESSION_SCORE));
        $ret = \html_writer::div($title . $score, constants::M_GRADING_SESSIONSCORE_CONTAINER . ' panel panel-primary',
                array('id' => constants::M_GRADING_SESSIONSCORE_CONTAINER));
        return $ret;
    }

    public function render_accuracydetails() {
        global $CFG;
        $title = \html_writer::div(get_string('accuracy_p', constants::M_COMPONENT), 'panel-heading');
        $score = \html_writer::div('0', constants::M_GRADING_SCORE . ' panel-body',
                array('id' => constants::M_GRADING_ACCURACY_SCORE));
        $ret = \html_writer::div($title . $score, constants::M_GRADING_ACCURACY_CONTAINER . ' panel panel-primary',
                array('id' => constants::M_GRADING_ACCURACY_CONTAINER));
        return $ret;
    }

    public function render_mistakedetails() {
        global $CFG;
        $title = \html_writer::div(get_string('mistakes', constants::M_COMPONENT), 'panel-heading');
        $score =
                \html_writer::div('0', constants::M_GRADING_SCORE . ' panel-body', array('id' => constants::M_GRADING_ERROR_SCORE));
        $ret = \html_writer::div($title . $score, constants::M_GRADING_ERROR_CONTAINER . ' panel panel-danger',
                array('id' => constants::M_GRADING_ERROR_CONTAINER));
        return $ret;
    }
}