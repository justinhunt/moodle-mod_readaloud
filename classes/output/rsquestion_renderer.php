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

namespace mod_readaloud\output;

use mod_readaloud\constants;
use mod_readaloud\utils;
use mod_readaloud\quizhelper;

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_readaloud
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rsquestion_renderer extends \plugin_renderer_base
{

    /**
     * Return HTML to display add first page links
     * @param \context $context
     * @param int $tableid
     * @return string
     */
    public function add_item_links($context, $tableid)
    {
        global $CFG;
        $itemid = 0;
        $config = get_config(constants::M_COMPONENT);

        $output = $this->output->heading(get_string("whatdonow", "readaloud"), 3);
        $links = [];

        $qtypes = [constants::TYPE_PAGE, constants::TYPE_MULTICHOICE];
        $qtypes[] = constants::TYPE_MULTIAUDIO;
        $qtypes[] = constants::TYPE_SHORTANSWER;
        $qtypes[] = constants::TYPE_LGAPFILL;
        $qtypes[] = constants::TYPE_TGAPFILL;
        $qtypes[] = constants::TYPE_SGAPFILL;
        $qtypes[] = constants::TYPE_FREESPEAKING;
        $qtypes[] = constants::TYPE_FREEWRITING;

        // If modaleditform is true adding and editing item types is done in a popup modal. Thats good ...
        // but when there is a lot to be edited , a standalone page is better. The modaleditform flag is acted on on additemlink template and rsquestionmanager js
        $modaleditform = false; // $config->modaleditform == "1";
        foreach ($qtypes as $qtype) {
            $data = [
                'wwwroot' => $CFG->wwwroot,
                'type' => $qtype,
                'itemid' => $itemid,
                'cmid' => $this->page->cm->id,
                'label' => get_string('add' . $qtype . 'item', constants::M_COMPONENT),
                'modaleditform' => $modaleditform
            ];
            $links[] = $this->render_from_template('mod_readaloud/additemlink', $data);
        }

        $props = ['contextid' => $context->id, 'tableid' => $tableid, 'modaleditform' => $modaleditform, 'wwwroot' => $CFG->wwwroot, 'cmid' => $this->page->cm->id];
        $this->page->requires->js_call_amd(constants::M_COMPONENT . '/rsquestionmanager', 'init', [$props]);

        return $this->output->box($output . implode("", $links), 'generalbox firstpageoptions mod_readaloud_link_box_container');
    }

    public function add_multichoice_item_link($context, $tableid)
    {
        global $CFG;
        $itemid = 0;
        $config = get_config(constants::M_COMPONENT);

        // If modaleditform is true adding and editing item types is done in a popup modal. Thats good ...
        // but when there is a lot to be edited , a standalone page is better. The modaleditform flag is acted on on additemlink template and rsquestionmanager js
        $modaleditform = false; // $config->modaleditform == "1";

        $data = [
            'wwwroot' => $CFG->wwwroot,
            'type' => constants::TYPE_MULTICHOICE,
            'itemid' => $itemid,
            'cmid' => $this->page->cm->id,
            'isbutton' => true,
            'label' => get_string('addquestion', constants::M_COMPONENT),
            'modaleditform' => $modaleditform
        ];

        $output = $this->output->heading(get_string("whatdonow", "readaloud"), 3);
        $links = [$this->render_from_template('mod_readaloud/additemlink', $data)];
        $props = ['contextid' => $context->id, 'tableid' => $tableid, 'modaleditform' => $modaleditform, 'wwwroot' => $CFG->wwwroot, 'cmid' => $this->page->cm->id];
        $this->page->requires->js_call_amd(constants::M_COMPONENT . '/rsquestionmanager', 'init', [$props]);
        return $this->output->box($output . implode("", $links), 'generalbox firstpageoptions mod_readaloud_link_box_container');
    }


    /**
     * Setup datatables with the given table ID.
     *
     * @param int $tableid The ID of the table to setup.
     */
    public function setup_datatables($tableid)
    {
        global $USER;

        $tableprops = [];
        $columns = [];
        // for cols .. .'itemname', 'itemtype', 'itemtags','timemodified', 'action'
        $columns[0] = ['orderable' => false];
        $columns[1] = ['orderable' => false];
        $columns[2] = ['orderable' => false];
        $columns[3] = ['orderable' => false];
        $columns[4] = ['orderable' => false];
        $columns[5] = ['orderable' => false];
        $tableprops['columns'] = $columns;
        $tableprops['dom'] = 'lBfrtip';

        // Default ordering.
        $order = [];
        $order[0] = [1, "asc"];
        $tableprops['order'] = $order;

        // Here we set up any info we need to pass into javascript.
        $opts = [];
        $opts['tableid'] = $tableid;
        $opts['tableprops'] = $tableprops;
        $this->page->requires->js_call_amd(constants::M_COMPONENT . "/datatables", 'init', [$opts]);
        $this->page->requires->css(new \moodle_url('https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css'));
        $this->page->requires->css(new \moodle_url('https://cdn.datatables.net/buttons/3.2.0/css/buttons.dataTables.min.css'));
        $this->page->requires->strings_for_js(['bulkdelete', 'bulkdeletequestion'], constants::M_COMPONENT);
    }

    public function show_no_items($cm, $showadditemlinks)
    {
        $displaytext = $this->output->box_start();
        $displaytext .= $this->output->heading(get_string('noitems', constants::M_COMPONENT), 3, 'main');
        if ($showadditemlinks) {
            $displaytext .= \html_writer::div(get_string('letsadditems', constants::M_COMPONENT), '', []);
            $displaytext .= $this->output->single_button(new \moodle_url(
                constants::M_URL . '/rsquestion/rsquestions.php',
                ['id' => $cm->id]
            ), get_string('additems', constants::M_COMPONENT));
        }
        $displaytext .= $this->output->box_end();
        $ret = \html_writer::div($displaytext, constants::M_NOITEMS_CONT, ['id' => constants::M_NOITEMS_CONT]);
        return $ret;
    }
    function show_noitems_message($itemsvisible)
    {
        $message = $this->output->heading(get_string('noitems', constants::M_COMPONENT), 3, 'main');
        $displayvalue = $itemsvisible ? 'none' : 'block';
        $ret = \html_writer::div($message, constants::M_NOITEMS_CONT, ['id' => constants::M_NOITEMS_CONT, 'style' => 'display: ' . $displayvalue]);
        return $ret;
    }

    /**
     * Return the html table of items
     * @param array homework objects
     * @param integer $courseid
     * @return string html of table
     */
    public function show_items_list($items, $readaloud, $cm, $visible)
    {

        // new code
        $data = [];
        $data['tableid'] = constants::M_ITEMS_TABLE;
        $data['display'] = $visible ? 'block' : 'none';
        $itemsarray = [];
        foreach (array_values($items) as $i => $item) {
            $arrayitem = (Array) $item;
            $arrayitem['index'] = ($i + 1);
            // due to odd  data in the field from legacy times we need to check for empty or oddstrings
            $arrayitem['typelabel'] = empty($arrayitem['type']) || strlen($arrayitem['type']) < 4 ? 'unknown' : get_string($arrayitem['type'], constants::M_COMPONENT);
            $itemsarray[] = $arrayitem;
        }
        $data['items'] = $itemsarray;

        $uppix = new \pix_icon('t/up', get_string('up'));
        $downpix = new \pix_icon('t/down', get_string('down'));
        $data['up'] = $uppix->export_for_pix();
        $data['down'] = $downpix->export_for_pix();

        return $this->render_from_template('mod_readaloud/itemlist', $data);

    }

    public function fetch_quizfinished_data($quizhelper, $moduleinstance, $latestattempt, $cm)
    {
        // Finished quiz results div.
        $quizisfinished = utils::quiz_is_finished($moduleinstance, $latestattempt, $cm);
        if ($quizisfinished) {
            $finisheddata = utils::fetch_quiz_results($quizhelper, $latestattempt, $cm);
            $finisheddata->isfinished = true;
            $finisheddata->canreattemptquiz = $moduleinstance->quizreattempt ? true : false;
            $modulecontext = \context_module::instance($cm->id);
            $finisheddata->activityname = format_string($moduleinstance->name);
            $finisheddata->backurl = (new \moodle_url('/mod/readaloud/view.php', ['id' => $cm->id]))->out(false);
            $finisheddata->passagepictureurl = utils::get_passage_picture($moduleinstance, $modulecontext);
        } else {
            $finisheddata = new \stdClass();
            $finisheddata->isfinished = false;
        }
        return $finisheddata;
    }

    /**
     *  Show quiz container
     */
    public function show_quiz($quizhelper, $moduleinstance, $latestattempt, $cm)
    {

        // Quiz items data div.
        $quizdata = $quizhelper->fetch_quiz_items_for_js();
        $itemshtml = [];
        foreach ($quizdata as $item) {
            $itemshtml[] = $this->render_from_template(constants::M_COMPONENT . '/qi_' . $item->type, $item);
        }

        $quizattributes = ['id' => constants::M_QUIZ_ITEMS_CONTAINER];
        // Div style if we have a custom font use it. If quiz has results, items are by default hidden.
        $style = '';
        if (!empty($moduleinstance->lessonfont)) {
            $style .= "font-family: '$moduleinstance->lessonfont', serif;";
        }
        $quizattributes['style'] = $style;

        // Quiz items div.
        $quizitemsclass = constants::M_QUIZ_ITEMS_CONTAINER;
        $quizitemsdiv = \html_writer::div(implode('', $itemshtml), $quizitemsclass, $quizattributes);

        $ret = $quizitemsdiv;
        return $ret;
    }

    public function show_quiz_preview($quizhelper, $qid)
    {

        // quiz data
        $quizdata = $quizhelper->fetch_quiz_items_for_js();
        $itemshtml = [];
        foreach ($quizdata as $item) {
            if ($item->id == $qid) {
                $itemshtml[] = $this->render_from_template(constants::M_COMPONENT . '/qi_' . $item->type, $item);
            }
        }

        // Quiz items div.
        $quizitemsdiv = \html_writer::div(implode('', $itemshtml),
            constants::M_QUIZ_ITEMS_CONTAINER,
            ['id' => constants::M_QUIZ_ITEMS_CONTAINER]);


        // Quiz div
        $quizdiv = \html_writer::div(
            $quizitemsdiv,
            constants::M_QUIZ_CONTAINER_WRAP,
            ['id' => constants::M_QUIZ_CONTAINER_WRAP]
        );

        return $quizdiv;
    }

    /**
     * Return HTML to display message about problem
     */
    public function show_problembox($msg)
    {
        $output = '';
        $output .= $this->output->box_start(constants::M_COMPONENT . '_problembox');
        $output .= $this->notification($msg, 'warning');
        $output .= $this->output->box_end();
        return $output;
    }
}
