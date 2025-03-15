<?php

namespace mod_readaloud\local\itemform;

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
 * Internal library of functions for module readaloud
 *
 * All the readaloud specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_readaloud
 * @copyright  COPYRIGHTNOTICE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_readaloud\constants;
use \mod_readaloud\utils;

class helper
{

    public static function move_item($readaloud, $moveitemid, $direction)
    {
        global $DB;

        switch ($direction) {
            case 'up':
                $sort = 'itemorder ASC';
                break;
            case 'down':
                $sort = 'itemorder DESC';
                break;
            default:
                //inconceivable that we should ever arrive here.
                return;
        }

        if (!$items = $DB->get_records(constants::M_QTABLE, array('readaloudid' => $readaloud->id), $sort)) {
            print_error("Could not fetch items for ordering. readaloudid:" . $readaloud->id);
            return;
        }

        $prioritem = null;
        foreach ($items as $item) {
            if ($item->id == $moveitemid && $prioritem != null) {
                $currentitemorder = $item->itemorder;
                $item->itemorder = $prioritem->itemorder;
                $prioritem->itemorder = $currentitemorder;

                //Set the new sort order
                $DB->set_field(constants::M_QTABLE, 'itemorder', $item->itemorder, array('id' => $item->id));
                $DB->set_field(constants::M_QTABLE, 'itemorder', $prioritem->itemorder, array('id' => $prioritem->id));
                break;
            }//end of if
            $prioritem = $item;
        }//end of for each
    }//end of move item function
    
    public static function get_new_itemorder($cm){
        //get itemorder
        $quizhelper = new \mod_readaloud\quizhelper($cm);
        $currentitems = $quizhelper->fetch_items();
        if (count($currentitems) > 0) {
            $lastitem = array_pop($currentitems);
            $itemorder = $lastitem->itemorder + 1;
        } else {
            $itemorder = 1;
        }
        return $itemorder;
    }

    public static function duplicate_item($readaloud,$context, $itemid)
    {
        global $CFG, $USER, $DB;
        

        if (!$item = $DB->get_record(constants::M_QTABLE, array('readaloudid' => $readaloud->id, 'id'=>$itemid))) {
            print_error("Could not fetch item for duplication");
            return;
        }

        //reset the item order and clear the ID before we insert
        $cm = get_coursemodule_from_id('', $context->instanceid, 0, false, MUST_EXIST);
        $newitemorder = self::get_new_itemorder($cm);
        $item->itemorder = $newitemorder;
        $item->name = $item->name . '(1)';
        $olditemid = $item->id;
        unset($item->id);
        
        //insert new record
        if (!$newitemid = $DB->insert_record(constants::M_QTABLE, $item)) {
            print_error("Could not duplicate item");
            return;
        }

        //copy files
        $fs = get_file_storage();
        $fileareas = array(constants::TEXTPROMPT_FILEAREA,
            constants::TEXTPROMPT_FILEAREA . '1',
            constants::TEXTPROMPT_FILEAREA . '2',
            constants::TEXTPROMPT_FILEAREA . '3',
            constants::TEXTPROMPT_FILEAREA . '4',
            constants::MEDIAQUESTION);
        
        //file record
        $newfilerecord = new \stdClass();
        $newfilerecord->userid = $USER->id;
        $newfilerecord->contextid = $context->id;
        $newfilerecord->component = constants::M_COMPONENT;
        $newfilerecord->itemid = $newitemid;
        $newfilerecord->filepath = '/';
        $newfilerecord->license = $CFG->sitedefaultlicense;
        $newfilerecord->author = 'Moodle User';
        $newfilerecord->source = '';
        $newfilerecord->timecreated = time();
        $newfilerecord->timemodified = time();

        foreach ($fileareas as $filearea) {
            $newfilerecord->filearea = $filearea;
            $files = $fs->get_area_files($context->id, constants::M_COMPONENT, $filearea, $olditemid);
            if($files){
                foreach ($files as $file){
                    if($file->get_filename()!=='.') {
                        $newfilerecord->filename = $file->get_filename();
                        $fs->create_file_from_storedfile($newfilerecord, $file);
                    }
                }
            }
        }
        $typelabel = get_string($item->type,constants::M_COMPONENT);
       return [$newitemid,$item->name,$item->type,$typelabel];
    }//end of move item function


    /*
     *  If we change AWS region we will need a new lang model for all the items
     *
     *
     */
    public static function update_all_langmodels($moduleinstance){
      global $DB;
        $updates=0;
        $itemrecords = $DB->get_records(constants:: M_QTABLE,array('readaloudid'=>$moduleinstance->id));
        foreach($itemrecords as $itemrecord) {
            $theitem =  utils::fetch_item_from_itemrecord($itemrecord,$moduleinstance);
            $olditemrecord=false;
            $updated = $theitem->update_create_langmodel($olditemrecord);
            if($updated) {
                $theitem->update_insert_item();
            }
        }
    }

    /*
     *  We want to upgrade all the phonetic models on occasion
     *
     */
    public static function update_all_phonetic($moduleinstance){
        global $DB;
        $updates=0;
        $itemrecords = $DB->get_records(constants:: M_QTABLE,array('readaloudid'=>$moduleinstance->id));
        foreach($itemrecords as $itemrecord) {
            $item =  utils::fetch_item_from_itemrecord($itemrecord,$moduleinstance);
            $olditem = false;
            $phonetic = $item->update_create_phonetic($olditem);
            if(!empty($phonetic)){
                $item->update_insert_item();
                $updates++;
            }
        }
    }


}
