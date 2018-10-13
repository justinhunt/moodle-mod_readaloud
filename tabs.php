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
* Sets up the tabs at the top of the module view page　for teachers.
*
* This file was adapted from the mod/lesson/tabs.php
*
 * @package mod_readaloud
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
*/

defined('MOODLE_INTERNAL') || die();

use \mod_readaloud\constants;

/// This file to be included so we can assume config.php has already been included.
global $DB;
if (empty($moduleinstance)) {
    print_error('cannotcallscript');
}
if (!isset($currenttab)) {
    $currenttab = '';
}
if (!isset($cm)) {
    $cm = get_coursemodule_from_instance(constants::MOD_READALOUD_MODNAME, $moduleinstance->id);
    $context = context_module::instance($cm->id);
}
if (!isset($course)) {
    $course = $DB->get_record('course', array('id' => $moduleinstance->course));
}

$tabs = $row = $inactive = $activated = array();


$row[] = new tabobject('view', "$CFG->wwwroot/mod/readaloud/view.php?id=$cm->id", get_string('view', constants::MOD_READALOUD_LANG), get_string('preview', constants::MOD_READALOUD_LANG, format_string($moduleinstance->name)));
$row[] = new tabobject('grading', "$CFG->wwwroot/mod/readaloud/grading.php?id=$cm->id", get_string('grading', constants::MOD_READALOUD_LANG), get_string('viewgrading', constants::MOD_READALOUD_LANG));
//$row[] = new tabobject('machinegrading', "$CFG->wwwroot/mod/readaloud/grading.php?id=$cm->id&action=machinegrading", get_string('machinegrading', constants::MOD_READALOUD_LANG), get_string('viewmachinegrading', constants::MOD_READALOUD_LANG));
//$row[] = new tabobject('gradesadmin', "$CFG->wwwroot/mod/readaloud/gradesadmin.php?id=$cm->id", get_string('gradesadmin', constants::MOD_READALOUD_LANG), get_string('viewgradesadmin', constants::MOD_READALOUD_LANG));
//$row[] = new tabobject('reports', "$CFG->wwwroot/mod/readaloud/reports.php?id=$cm->id", get_string('reports', constants::MOD_READALOUD_LANG), get_string('viewreports', constants::MOD_READALOUD_LANG));
$tabs[] = $row;

print_tabs($tabs, $currenttab, $inactive, $activated);
