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
 * English strings for readaloud
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Read Aloud';
$string['modulenameplural'] = 'Read Alouds';
$string['modulename_help'] = 'ReadAloud is an activity designed to assist teachers in evaluating their students reading fluency. Students read a passage, set by the teacher, into a microphone. Later the teacher can mark words as incorrect and get the student WCPM(Words Correct Per Minute) scores.';
$string['readaloudfieldset'] = 'Custom example fieldset';
$string['readaloudname'] = 'Read Aloud';
$string['readaloudname_help'] = 'This is the content of the help tooltip associated with the readaloudname field. Markdown syntax is supported.';
$string['readaloud'] = 'readaloud';
$string['pluginadministration'] = 'Read Aloud Administration';
$string['pluginname'] = 'Read Aloud Activity';
$string['someadminsetting'] = 'Some Admin Setting';
$string['someadminsetting_details'] = 'More info about Some Admin Setting';
$string['someinstancesetting'] = 'Some Instance Setting';
$string['someinstancesetting_details'] = 'More infor about Some Instance Setting';
$string['readaloudsettings'] = 'readaloud settings';
$string['readaloud:addinstance'] = 'Add a new Read Aloud';
$string['readaloud:view'] = 'View Read Aloud';
$string['readaloud:view'] = 'Preview Read Aloud';
$string['readaloud:itemview'] = 'View items';
$string['readaloud:itemedit'] = 'Edit items';
$string['readaloud:tts'] = 'Can use Text To Speech(tts)';
$string['readaloud:manageattempts'] = 'Can manage Read Aloud attempts';
$string['readaloud:manage'] = 'Can manage Read Aloud instances';
$string['readaloud:preview'] = 'Can preview Read Aloud activities';
$string['readaloud:submit'] = 'Can submit Read Aloud attempts';
$string['privacy:metadata'] = 'The Poodll Read Aloud plugin does store personal data.';


$string['id']='ID';
$string['name']='Name';
$string['timecreated']='Time Created';
$string['basicheading']='Basic Report';
$string['attemptsheading']='Attempts Report';
$string['attemptsbyuserheading']='User Attempts Report';
$string['gradingheading']='Grading latest attempts for each user.';
$string['gradingbyuserheading']='Grading all attempts for: {$a}';
$string['totalattempts']='Attempts';
$string['overview']='Overview';
$string['overview_help']='Overview Help';
$string['view']='View';
$string['preview']='Preview';
$string['viewreports']='View Reports';
$string['reports']='Reports';
$string['viewgrading']='View Grading';
$string['grading']='Grading';
$string['gradenow']='Grade Now';
$string['cannotgradenow']=' - ';
$string['gradenowtitle']='Grading: {$a}';
$string['showingattempt']='Showing attempt for: {$a}';
$string['basicreport']='Basic Report';
$string['returntoreports']='Return to Reports';
$string['returntogradinghome']='Return to Grading Top';
$string['exportexcel']='Export to CSV';
$string['mingradedetails'] = 'The minimum grade required to "complete" this activity.';
$string['mingrade'] = 'Minimum Grade';
$string['deletealluserdata'] = 'Delete all user data';
$string['maxattempts'] ='Max. Attempts';
$string['unlimited'] ='unlimited';
$string['gradeoptions'] ='Grade Options';
$string['gradenone'] ='No grade';
$string['gradelowest'] ='lowest scoring attempt';
$string['gradehighest'] ='highest scoring attempt';
$string['gradelatest'] ='score of latest attempt';
$string['gradeaverage'] ='average score of all attempts';
$string['defaultsettings'] ='Default Settings';
$string['exceededattempts'] ='You have completed the maximum {$a} attempts.';
$string['readaloudtask'] ='Read Aloud Task';
$string['passagelabel'] ='Reading Passage';
$string['welcomelabel'] ='Default Welcome';
$string['welcomelabel_details'] ='The default text to show in the welcome field when creating a new Read Aloud activity.';
$string['feedbacklabel'] ='Default Feedback';
$string['feedbacklabel_details'] ='The default text to show in the feedback field when creating a new Read Aloud activity.';
$string['welcomelabel'] = 'Welcome Message';
$string['feedbacklabel'] = 'Feedback Message';
$string['defaultwelcome'] = 'Please read the following passage aloud.';
$string['defaultfeedback'] = 'Thanks for your time.';
$string['timelimit'] = 'Time Limit';
$string['gotnosound'] = 'We could not hear you. Please check the permissions and settings for microphone and try again.';
$string['recordnameschool'] = 'Say your name and school';
$string['done'] = 'Done';
$string['processing'] = 'Processing';
$string['feedbackheader'] = 'Finished';
$string['beginreading'] = 'Begin Reading';
$string['errorheader'] = 'Error';
$string['uploadconverterror'] = 'An error occured while posting your file to the server. Your submission has NOT been received. Please refresh the page and try again.';
$string['attemptsreport'] = 'Attempts Report';
$string['submitted'] = 'submitted';
$string['id'] = 'ID';
$string['username'] = 'User';
$string['audiofile'] = 'Audio';
$string['wpm'] = 'WPM';
$string['timecreated'] = 'Time Created';
$string['nodataavailable'] = 'No Data Available Yet';
$string['saveandnext'] = 'Save .... and next';
$string['reattempt'] = 'Try Again';
$string['notgradedyet'] = 'Your last submission has not been graded yet';
$string['enabletts'] = 'Enable TTS(experimental)';
$string['enabletts_details'] = '<b>TTS is currently not implemented</b>. When implemented words marked as errors, if clicked will play back the correct pronunciation via a TTS service.';
$string['ttslanguage'] = 'TTS Language';
$string['deleteattemptconfirm'] = "Are you sure that you want to delete this attempt?";
$string['deletenow']='';
$string['allowearlyexit']='Can exit early';
$string['allowearlyexit_details']='If checked students can finish before the time limit, by pressing a finish button. The WPM is calculated using their recording time.';
$string['allowearlyexit_defaultdetails']='Sets the default setting for allow_early_exit. Can be overriden at the activity level. If true, allow_early_exit means that students can finish before the time limit, by pressing a finish button. The WPM is calculated using their recording time.';
$string['itemsperpage']='Items per page';
$string['itemsperpage_details']='This sets the number of rows to be shown on reports or lists of attempts.';
$string['accuracy']='Accuracy';
$string['accuracy_p']='Acc(%)';
$string['mistakes']='Mistakes';
$string['grade']='Grade';
$string['grade_p']='Grade(%)';
$string['targetwpm']='Target WPM';
$string['targetwpm_details']='The default target WPM. A students grade is calculated for the gradebook using this value as the maximum score. If their WPM score is equal to, or greater than the target WPM, they will score 100%. The target WPM can also be set at the activity instance level. ';
$string['targetwpm_help']='The target WPM score. A students grade is calculated for the gradebook using this value as the maximum score. If their WPM score is equal to, or greater than the target WPM, they will score 100%.';

$string['loadbootstrap']='Load Bootstrap';
$string['loadbootstrap_details']='Bootstrap is a set of CSS and javascript often used to make big colorful buttons and user interfaces that work well on PC and mobile devices. ReadAloud will load it independently if this is checked. If your theme already loads it, and it causes things to look weird then uncheck this.';
$string['loadfontawesome']='Load FontAwesome';
$string['loadfontawesome_details']='FontAwesome provides bland icons that represent arrows and circles and various common symbols you see on toolbars and buttons. ReadAloud loads this for you if this is checked. Should this cause problems, uncheck it.';

$string['apiuser']='Poodll API User ';
$string['apiuser_details']='The Poodll account username that authorises Poodll on this site.';
$string['apisecret']='Poodll API Secret ';
$string['apisecret_details']='The Poodll API secret. See <a href= "https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret">here</a> for more details';
$string['enableai']='Enable AI';
$string['enableai_details']='Using AI Read Aloud can estimate the results from a student attempt';


$string['useast1']='US East';
$string['tokyo']='Tokyo, Japan';
$string['sydney']='Sydney, Australia';
$string['dublin']='Dublin, Ireland';
$string['forever']='Never expire';
$string['en-us']='English (US)';
$string['es-us']='Spanish (US)';
$string['awsregion']='AWS Region';
$string['region']='AWS Region';
$string['expiredays']='Days to keep file';
$string['aigradenow']='AI Grade';