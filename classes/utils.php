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

require_once($CFG->dirroot .'/mod/readaloud/lib.php');


/**
 * Event observer for mod_readaloud
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils{
    function shift_draft_file($cmid, $draftitemid,$filename,$submissionid) {
        global $USER;

        $usercontext = \context_user::instance($USER->id);
        $usercontextid = \context_user::instance($USER->id)->id;
        $modulecontextid= \context_module::instance($cmid)->id;

        //Don't do anything in the case that the filename is empty
        //possibly the user is just updating something else on the page(eg an online text submission)
        //if we overwrite here, we might trash their existing poodll submission file
        if($filename==''){return false;}

        //fetch the file info object for our original file
        $browser = get_file_browser();
        $draft_fileinfo = $browser->get_file_info($usercontext, 'user','draft', $draftitemid, '/', $filename);

        //perform the copy
        if($draft_fileinfo){
            //create the file record for our new file
            $file_record = array(
                'userid' => $USER->id,
                'contextid'=>$modulecontextid,
                'component'=>MOD_READALOUD_FRANKY,
                'filearea'=>MOD_READALOUD_FILEAREA_SUBMISSIONS ,
                'itemid'=>$submissionid,
                'filepath'=>'/',
                'filename'=>$filename,
                'author'=>'moodle user',
                'license'=>'allrighttsreserved',
                'timecreated'=>time(),
                'timemodified'=>time()
            );
            $draft_fileinfo->copy_to_storage($file_record);

        }//end of if $draft_fileinfo
        return $filename;
    }//end of shift_draft_file

   public static function save_to_moodle($filename,$cmid){
		global $USER,$DB;

		$cm         = get_coursemodule_from_id('readaloud', $cmid, 0, false, MUST_EXIST);
		$readaloud  = $DB->get_record('readaloud', array('id' => $cm->instance), '*', MUST_EXIST);

		//first create an attempt and then we will come back and save the file
		$newattempt = new \stdClass();
		$newattempt->courseid=$readaloud->course;
		$newattempt->readaloudid=$readaloud->id;
		$newattempt->userid=$USER->id;
		$newattempt->status=0;
		$newattempt->filename=$filename;
		$newattempt->sessionscore=0;
		$newattempt->wpm=0;
		$newattempt->timecreated=time();
		$newattempt->timemodified=time();
		$attemptid = $DB->insert_record(MOD_READALOUD_USERTABLE,$newattempt);
		if(!$attemptid){
			return false;
		}

        //Save our audio file
         self::shift_draft_file($cmid,$filename,$attemptid);

        return true;
}



   public static function get_lang_options(){
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
   }
}
