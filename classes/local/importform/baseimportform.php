<?php

namespace mod_readaloud\local\importform;

/**
 * Helper.
 *
 * @package mod_readaloud
 * @author  Justin Hunt
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use mod_readaloud\constants;

/**
 * Helper class.
 *
 * @package mod_readaloud
 * @author  Justin Hunt
 */
class baseimportform extends \moodleform {

   public function definition() {
       global $CFG;

       $m35 = $CFG->version >= 2018051700;
       $mform = $this->_form;

       //JSON or CSV import ... everybody choose JSON please..
       $importformats=['json'=>'JSON','csv'=>'CSV'];
       $mform->addElement('select', 'importformat', get_string('importformat', constants::M_COMPONENT), $importformats);

       //a bug prevents hideif working for static elements, so put them in a group, and hideif that
       //Example JSON file link
       $json_example_url = new \moodle_url('example.json');
       $json_example_link = \html_writer::link( $json_example_url , 'example.json');
       $json_example_group= [];
       $json_example_group[] = &$mform->createElement('static', 'examplejson', get_string('examplejson', constants::M_COMPONENT), $json_example_link);
       $mform->addGroup($json_example_group, 'examplejsongroup', get_string('examplejson', constants::M_COMPONENT), array(' '), false);

        //a bug prevents hideif working for static elements, so put them in a group, and hideif that
       //Example CSV file link
       $csv_example_url = new \moodle_url('example.csv');
       $csv_example_link = \html_writer::link( $csv_example_url , 'example.csv');
       $csv_example_group=[] ;
       $csv_example_group[] = &$mform->createElement('static', 'examplecsv', get_string('examplecsv', constants::M_COMPONENT), $csv_example_link);
       $mform->addGroup($csv_example_group, 'examplecsvgroup', get_string('examplecsv', constants::M_COMPONENT), array(' '), false);

       //CSV delimiter
       $choices = \csv_import_reader::get_delimiter_list();
       $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', constants::M_COMPONENT), $choices);
       if (array_key_exists('cfg', $choices)) {
           $mform->setDefault('delimiter_name', 'cfg');
       } else if (get_string('listsep', 'langconfig') == ';') {
           $mform->setDefault('delimiter_name', 'semicolon');
       } else {
           $mform->setDefault('delimiter_name', 'comma');
       }

       //CSV encoding
       $choices = \core_text::get_encodings();
       $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
       $mform->setDefault('encoding', 'UTF-8');


       //hide CSV stuff, if its JSON (and vice versa)
       $m35 = $CFG->version >= 2018051700;
       if($m35) {
           $mform->hideIf('examplejsongroup', 'importformat', 'neq', 'json');
           $mform->hideIf('examplecsvgroup', 'importformat', 'neq', 'csv');
           $mform->hideIf('delimiter_name', 'importformat', 'neq', 'csv');
           $mform->hideIf('encoding', 'importformat', 'neq', 'csv');
       }else{
           $mform->disabledIf('examplejsongroup', 'importformat', 'neq', 'json');
           $mform->disabledIf('examplecsvgroup', 'importformat', 'neq', 'csv');
           $mform->disabledIf('delimiter_name', 'importformat', 'neq', 'csv');
           $mform->disabledIf('encoding', 'importformat', 'neq', 'csv');
       }

       //The file upload area
       $file_options = array();
       $file_options['accepted_types'] = array('.csv', '.txt','.json');
       $mform->addElement('filepicker', 'importfile', get_string('file'), 'size="40"', $file_options);
       $mform->addRule('importfile', null, 'required');

        $this->add_action_buttons(false);
    }

}
