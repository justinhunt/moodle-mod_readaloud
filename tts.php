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
 * Prints a particular instance of englishcentral
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
global $CFG;

$n = required_param('n',PARAM_INT); // the readaloud activity
$txt = optional_param('txt','hello', PARAM_TEXT); // the word we are seeking
$lang  = optional_param('lang', 'en', PARAM_TEXT);  // the language id of the word we are seeking(not moodle lang, but same code?)
$filepath = $CFG->dataroot . '/ttscache/' . $lang . '/' . urlencode($txt);
$writefile=false;

$moduleinstance  = $DB->get_record('readaloud', array('id' => $n), '*', MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
$cm         = get_coursemodule_from_instance('readaloud', $moduleinstance->id, $course->id, false, MUST_EXIST);
$modulecontext = context_module::instance($cm->id);

//lets require students have access to this activity before we let them use TTS
require_capability('mod/readaloud:tts',$modulecontext);

if(!file_exists($filepath)){
	//$qs = http_build_query(array("ie" => "utf-8","tl" => $lang, "q" => $txt));
	$qs = "ie=utf-8&tl=$lang&q=" . urlencode($txt);
	$ctx = stream_context_create(array("http"=>array("method"=>"GET","header"=>"Referer: \r\n")));
	$soundfile = file_get_contents("http://translate.google.com/translate_tts?".$qs, false, $ctx);
	$writefile=true;
}else{
	$soundfile = file_get_contents($filepath);
}
 
header("Content-type: audio/mpeg");
header("Content-Transfer-Encoding: binary");
header('Pragma: no-cache');
header('Expires: 0');
 
echo($soundfile);
if($writefile){
	if(!file_exists($CFG->dataroot . '/ttscache')){
		mkdir($CFG->dataroot . '/ttscache');
	}
	if(!file_exists($CFG->dataroot . '/ttscache/' . $lang)){
		mkdir($CFG->dataroot . '/ttscache/' . $lang);
	}
	file_put_contents($filepath,$soundfile);
}