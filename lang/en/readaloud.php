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

$string['modulename'] = 'Poodll ReadAloud';
$string['modulenameplural'] = 'Poodll ReadAlouds';
$string['modulename_help'] =
        'ReadAloud gives students practice and feedback reading passages aloud. It can be entirely automatically graded and helps teachers assess and understand their students second language reading ability. The process is as follows:
        
1. Students LISTEN to a passage, set by the teacher.
        
2. Students PRACTICE reading line by line using their microphone.
        
3. Students READ the entire passage aloud.
        
4. Students and teachers can see the FEEDBACK and RESULTS.';
$string['readaloudfieldset'] = 'Custom example fieldset';
$string['readaloudname'] = 'Poodll ReadAloud';
$string['readaloudname_help'] =
        'This is the content of the help tooltip associated with the readaloudname field. Markdown syntax is supported.';
// $string['readaloud'] = 'readaloud';
$string['activitylink'] = 'Link to next activity';
$string['activitylink_help'] = 'To provide a link after the attempt to another activity in the course, select the activity from the dropdown list.';
$string['activitylinkname'] = 'Continue to next activity: {$a}';
$string['pluginadministration'] = 'ReadAloud Administration';
$string['pluginname'] = 'Poodll ReadAloud';
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
$string['readaloud:viewreports'] = 'Can view Read Aloud grades and reports';
$string['readaloud:pushtoclones'] = 'Can push settings to clones';
$string['privacy:metadata'] = 'The Poodll Read Aloud plugin does store personal data.';

$string['id'] = 'ID';
$string['name'] = 'Name';
$string['timecreated'] = 'Time Created';
$string['basicheading'] = 'Basic Report';
$string['attemptsheading'] = 'Attempts Report';
// $string['attemptsbyuserheading'] = 'User Attempts Report';
$string['attemptssummaryheading'] = 'Attempts Summary Report';
$string['gradingheading'] = 'Grading latest attempts for each user.';
$string['machinegradingheading'] = 'Machine evaluated latest attempt for each user.';
$string['gradingbyuserheading'] = 'Grading all attempts for: {$a}';
$string['machinegradingbyuserheading'] = 'Machine evaluated attempts for: {$a}';
$string['totalattempts'] = 'Attempts';
$string['overview'] = 'Overview';
$string['overview_help'] = 'Overview Help';
$string['view'] = 'View';
$string['preview'] = 'Preview';
$string['viewreports'] = 'View Reports';
$string['reports'] = 'Reports';
$string['viewgrading'] = 'View Grading';
$string['grading'] = 'Grading';
$string['gradenow'] = 'Grade Now';
$string['cannotgradenow'] = ' - ';
// $string['gradenowtitle'] = 'Grading: {$a}';
$string['showingattempt'] = 'Showing attempt for: {$a}';
$string['showingmachinegradedattempt'] = 'Machine evaluated attempt for: {$a}';
$string['basicreport'] = 'Basic Report';
$string['returntoreports'] = 'Return to Reports';
$string['returntogradinghome'] = 'Return to Grading Top';
$string['returntomachinegradinghome'] = 'Return to Machine Evaluations Top';
$string['exportexcel'] = 'Export to CSV';
// $string['mingradedetails'] = 'The minimum Read Aloud grade(%) required to "complete" this activity.';
$string['mingrade'] = 'Minimum Grade';
$string['deletealluserdata'] = 'Delete all user data';
$string['maxattempts'] = 'Max. Attempts';
$string['unlimited'] = 'unlimited';
$string['gradeoptions'] = 'Grade Options';
$string['gradeoptions_help'] =
        'When there are multiple attempts by a user on a reading, this setting determines which attempt to use when grading';
$string['gradeoptions_details'] =
        'NB This determines the gradebook entry. The ReadAloud grading page is not affected and will display the latest attempt.';
$string['gradenone'] = 'No grade';
$string['gradelowest'] = 'lowest scoring attempt';
$string['gradehighest'] = 'highest scoring attempt';
$string['gradelatest'] = 'score of latest attempt';
$string['gradeaverage'] = 'average score of all attempts';
// $string['defaultsettings'] = 'Default Settings';
$string['exceededattempts'] = 'You have completed the maximum {$a} attempts.';
$string['exceededallattempts'] = "You have used all of your attempts.";
$string['readaloudtask'] = 'Read Aloud Task';
$string['passagelabel'] = 'Reading Passage';
$string['welcomelabel'] = 'Default instructions';
$string['welcomelabel_details'] = 'The default instructions. Can be edited when creating a new Read Aloud activity.';
$string['feedbacklabel'] = 'Default Feedback';
$string['feedbacklabel_details'] = 'The default text to show in the feedback field when creating a new Read Aloud activity.';
$string['welcomelabel'] = 'Pre-attempt instructions';
$string['feedbacklabel'] = 'Post-attempt instructions';
$string['alternatives'] = 'Alternatives';
$string['alternatives_descr'] =
        'Specify matching options for specific passage words. 1 word set per line. e.g their|there|they\'re See <a href="https://support.poodll.com/support/solutions/articles/19000096937-tuning-your-read-aloud-activity">docs</a> for more details.';
$string['attemptsheading'] = 'Attempts report';
$string['attemptsreport'] = 'Attempts report';
$string['attemptssummaryheading'] = 'Attempts summary report';
$string['attemptssummaryreport'] = 'Attempts summary report';
$string['audiofile'] = 'Audio';
$string['averages'] = 'Average';
$string['basicheading'] = 'Basic report';
$string['basicreport'] = 'Basic report';
$string['beginreading'] = 'Begin reading';
$string['cannotgradenow'] = ' - ';
$string['complete'] = 'Complete';
$string['defaultfeedback'] = 'Thanks for reading.';
$string['defaultwelcome'] = 'Complete this activity by working through the tasks on your screen. You will listen to, practice, and read a passage aloud before reviewing your performance in the report. You may need to enable your microphone.';
$string['deletealluserdata'] = 'Delete all user data';
$string['done'] = 'Done';
$string['enabletts'] = 'Enable TTS(experimental)';
$string['enabletts_details'] = 'TTS is currently not implemented';
$string['errorheader'] = 'Error';
$string['evaluatedmessage'] = 'Your latest attempt has been received and the evaluation is shown below.';
$string['exceededallattempts'] = "You have used all of your attempts.";
$string['exceededattempts'] = 'You have completed the maximum {$a} attempts.';
$string['exportexcel'] = 'Export to CSV';
$string['feedbacklabel_details'] = 'The default text to show in the feedback field when creating a new Read Aloud activity.';
$string['gotnosound'] = 'We could not hear you. Please check the permissions and settings for microphone and try again.';
$string['gradehighest'] = 'highest scoring attempt';
$string['gradelatest'] = 'score of latest attempt';
$string['gradenone'] = 'No grade';
$string['gradenow'] = 'Grade now';
$string['gradeoptions'] = 'Grade options';
$string['gradeoptions_details'] =
        'NB This determines the gradebook entry. The ReadAloud grading page is not affected and will display the latest attempt.';
$string['gradeoptions_help'] =
        'When there are multiple attempts by a user on a reading, this setting determines which attempt to use when grading';
$string['grading'] = 'Grading';
$string['gradingbyuserheading'] = 'Grading all attempts for: {$a}';
$string['gradingheading'] = 'Grading latest attempts for each user.';
$string['hiddenevaluationmessage'] = 'Your attempt has been received. Thank you.';
$string['highest'] = 'Highest';
$string['id'] = 'ID';
$string['instructions'] = 'Instructions';
$string['locked'] = 'Locked';
$string['machinegradingbyuserheading'] = 'Machine evaluated attempts for: {$a}';
$string['machinegradingheading'] = 'Machine evaluated latest attempt for each user.';
$string['maxattempts'] = 'Max. attempts';
$string['mingrade'] = 'Minimum grade';
$string['modulename'] = 'Poodll ReadAloud';
$string['modulename_help'] =
        'ReadAloud gives students practice and feedback reading passages aloud. It can be entirely automatically graded and helps teachers assess and understand their students second language reading ability. The process is as follows:
1. Students LISTEN to a passage, set by the teacher.
2. Students PRACTICE reading line by line using their microphone.
3. Students READ the entire passage aloud.
4. Students CHECK COMPREHENSION with a quiz (optional).
5. Students and teachers can see the FEEDBACK and RESULTS.';
$string['modulenameplural'] = 'Poodll ReadAlouds';
$string['name'] = 'Name';
$string['notaddedtogradebook'] = 'This was a shadow practice, and not added to gradebook.';
$string['notgradedyet'] = 'Your submission has been received, but has not been graded yet. It might take a few minutes.';
$string['notmanuallygradedyet'] = 'Your submission has been received, but has not been graded yet.';
$string['overview_help'] = 'Overview help';
$string['passagelabel'] = 'Reading passage';
$string['pluginadministration'] = 'ReadAloud administration';
$string['pluginname'] = 'Poodll ReadAloud';
$string['preview'] = 'Preview';
$string['privacy:metadata'] = 'The Poodll Read Aloud plugin does store personal data.';
$string['processing'] = 'Processing';
$string['readaloud:addinstance'] = 'Add a new Read Aloud';

$string['readaloud:manage'] = 'Can manage Read Aloud instances';
$string['readaloud:manageattempts'] = 'Can manage Read Aloud attempts';
$string['readaloud:preview'] = 'Can preview Read Aloud activities';
$string['readaloud:pushtoclones'] = 'Can push settings to clones';
$string['readaloud:submit'] = 'Can submit Read Aloud attempts';
$string['readaloud:viewreports'] = 'Can view Read Aloud grades and reports';
$string['readaloudname'] = 'Poodll ReadAloud';
$string['readaloudname_help'] =
        'This is the content of the help tooltip associated with the readaloudname field. Markdown syntax is supported.';

$string['readaloudtask'] = 'Read Aloud task';
$string['reattempt'] = 'Try again';
$string['reports'] = 'Reports';
$string['returntogradinghome'] = 'Return to grading top';
$string['returntomachinegradinghome'] = 'Return to machine evaluations top';
$string['returntoreports'] = 'Return to reports';
$string['saveandnext'] = 'Save .... and next';
$string['showingattempt'] = 'Showing attempt for: {$a}';
$string['showingmachinegradedattempt'] = 'Machine evaluated attempt for: {$a}';
$string['submitted'] = 'submitted';
$string['timelimit'] = 'Time limit';
$string['totalattempts'] = 'Attempts';

$string['unlimited'] = 'unlimited';
$string['uploadconverterror'] =
        'An error occured while posting your file to the server. Your submission has NOT been received. Please refresh the page and try again.';
$string['username'] = 'User';
$string['view'] = 'View';
$string['viewgrading'] = 'View grading';
$string['viewreports'] = 'View report';



$string['welcomelabel_details'] = 'The default instructions. Can be edited when creating a new Read Aloud activity.';




$string['wpm'] = 'WPM';

// We hijacked this setting for both TTS STT .... bad ... but they are always the same aren't they?
$string['ttslanguage'] = 'Passage language';
$string['deleteattemptconfirm'] = "Are you sure that you want to delete this attempt?";
$string['deletenow'] = '';
$string['allowearlyexit'] = 'Can exit early';
$string['allowearlyexit_details'] =
        'If checked students can finish before the time limit, by pressing a finish button. The WPM is calculated using their recording time.';
$string['allowearlyexit_defaultdetails'] =
        'Sets the default setting for allow_early_exit. Can be overriden at the activity level. If true, allow_early_exit means that students can finish before the time limit, by pressing a finish button. The WPM is calculated using their recording time.';
$string['itemsperpage'] = 'Items per page';
$string['accuracy'] = 'Accuracy';
$string['accuracy_p'] = 'Acc(%)';
$string['av_accuracy_p'] = 'Av. acc(%)';
$string['h_accuracy_p'] = 'Max acc(%)';
$string['mistakes'] = 'Mistakes';
$string['grade'] = 'Grade';
$string['grade_p'] = 'Final grade(%)';
$string['readgrade_p'] = 'Read grade(%)';
$string['quizscore_p'] = 'Quiz grade(%)';
$string['av_readgrade_p'] = 'Av. read grade(%)';
$string['h_readgrade_p'] = 'Max read grade(%)';
$string['av_quizscore_p'] = 'Av. quiz score(%)';
$string['h_quizscore_p'] = 'Max quiz score(%)';
$string['av_wpm'] = 'Av. WPM';
$string['h_wpm'] = 'Max WPM';
$string['targetwpm'] = 'Target WPM';
$string['targetwpm_details'] =
        'The default target WPM. A students grade is calculated for the gradebook using this value as the maximum score. If their WPM score is equal to, or greater than the target WPM, they will score 100%. The target WPM can also be set at the activity instance level. ';
$string['targetwpm_help'] =
        'The target WPM score. A students grade is calculated for the gradebook using this value as the maximum score. If their WPM score is equal to, or greater than the target WPM, they will score 100%.';
$string['passage'] = 'Reading passage';
$string['passage_help'] = "The passage that will be shown to the student to read.";
$string['passage_descr'] = "Enter the reading passage above. It should not be longer than 3000 characters if you wish audio to be generated for it.";
$string['timelimit_help'] = "Sets a time limit on the reading. Reading time is used in the WPM calculation. Consider also checking - Allow Early Exit";
$string['ttslanguage_help'] = "This value is used for speech recognition and text to speech.";
$string['ttsvoice_descr'] = "The machine voice used to read the passage aloud. If it is followed by a + symbol it is a better quality voice. It is followed by a ! symbol you will need to manually add speech breaks in the model audio tab.";
$string['ttsvoice_help'] = "The machine voice used to read the passage aloud. You should select a voice that matches the language famly of the passage language. If it is followed by a + symbol it is a better quality voice. It is followed by a ! symbol you will need to manually add speech breaks in the model audio tab. Use the model audio tab to record or upload an alternative model audio, or to manually set speech breaks.";
$string['ttsspeed_help'] = "The machine voice reading speed. Slow or Extra Slow are good for learners, but can distort the audio.";
$string['alternatives_help'] = "Specify matching options for specific passage words. 1 word set per line. e.g their|there|they're See <a href=\"https://support.poodll.com/support/solutions/articles/19000096937-tuning-your-read-aloud-activity\">docs</a> for more details.";

$string['accadjust'] = 'Fixed adjustment.';
$string['accadjust_details'] =
        'This is the number of reading errors to compensate WPM scores for. If WPM adjust is set to "Fixed" then this value will be used to compensate WPM acores. This is a method of mitigating for machine transcription mistakes.';
$string['accadjust_help'] =
        'This rate should correspond as closely as possible to the estimated machine transcription mistake average for a passage.';

$string['accadjustmethod'] = 'WPM adjust(AI)';
$string['accadjustmethod_details'] =
        'Adjust the WPM score by ignoring, or discounting some, reading errors found by AI. The default \'No adjustment\' subtracts all reading errors from final WPM score. ';
$string['accadjustmethod_help'] =
        'For WPM adjustment we can: never adjust, adjust by a fixed amount, or ignore errors when calculating WPM';
$string['accmethod_none'] = 'No adjustment';
$string['accmethod_auto'] = 'Auto audjustment';
$string['accmethod_fixed'] = 'Adjust by fixed amount';
$string['accmethod_noerrors'] = 'Ignore all errors';

$string['apiuser'] = 'Poodll API user ';
$string['apiuser_details'] = 'The Poodll account username that authorises Poodll on this site.';
$string['apisecret'] = 'Poodll API secret ';
$string['enableai'] = 'Enable AI';
$string['enableai_details'] = 'Read Aloud can evaluate results from a student attempt using AI. Check to enable.';

$string['useast1'] = 'US East';
$string['tokyo'] = 'Tokyo, Japan';
$string['sydney'] = 'Sydney, Australia';
$string['dublin'] = 'Dublin, Ireland';
$string['capetown'] = 'Capetown, South Africa';
$string['bahrain'] = 'Bahrain';
$string['ottawa'] = 'Ottawa, Canada';
$string['frankfurt'] = 'Frankfurt, Germany';
$string['london'] = 'London, U.K';
$string['saopaulo'] = 'Sao Paulo, Brazil';
$string['singapore'] = 'Singapore';
$string['mumbai'] = 'Mumbai, India';
$string['ningxia'] = 'Ningxia, China';
$string['forever'] = 'Never expire';

$string['azureapikey'] = 'Azure speech API key';
$string['azureapiregion'] = 'Azure speech region';
$string['otherapikeys'] = 'Other API keys (BYOK)';

$string['en-us'] = 'English (US)';
$string['es-us'] = 'Spanish (US)';
$string['en-au'] = 'English (Aus.)';
$string['en-ph'] = 'English (Phil.)';
$string['en-gb'] = 'English (GB)';
$string['fr-ca'] = 'French (Can.)';
$string['fr-fr'] = 'French (FR)';
$string['it-it'] = 'Italian (IT)';
$string['pt-br'] = 'Portuguese (BR)';
$string['en-in'] = 'English (IN)';
$string['es-es'] = 'Spanish (ES)';
$string['fr-fr'] = 'French (FR)';
$string['fil-ph'] = 'Filipino';
$string['de-de'] = 'German (DE)';
$string['de-ch'] = 'German (CH)';
$string['de-at'] = 'German (AT)';
$string['da-dk'] = 'Danish (DK)';
$string['hi-in'] = 'Hindi';
$string['ko-kr'] = 'Korean';
$string['ar-ae'] = 'Arabic (Gulf)';
$string['ar-sa'] = 'Arabic (Modern Standard)';
$string['zh-cn'] = 'Chinese (Mandarin-Mainland)';
$string['nl-nl'] = 'Dutch (NL)';
$string['nl-be'] = 'Dutch (BE)';
$string['en-ie'] = 'English (Ireland)';
$string['en-wl'] = 'English (Wales)';
$string['en-ab'] = 'English (Scotland)';
$string['en-nz'] = 'English (New Zealand)';
$string['en-za'] = 'English (South Africa)';
$string['fa-ir'] = 'Persian';

$string['he-il'] = 'Hebrew';
$string['id-id'] = 'Indonesian';
$string['ja-jp'] = 'Japanese';
$string['ms-my'] = 'Malay';
$string['mi-nz'] = 'Maori';
$string['pt-pt'] = 'Portuguese (PT)';
$string['ru-ru'] = 'Russian';
$string['ta-in'] = 'Tamil';
$string['te-in'] = 'Telugu';
$string['tr-tr'] = 'Turkish';

$string['uk-ua'] = 'Ukranian';
$string['eu-es'] = 'Basque';
$string['fi-fi'] = 'Finnish';
$string['hu-hu'] = 'Hungarian';

$string['sv-se'] = 'Swedish';
$string['no-no'] = 'Norwegian';
$string['nb-no'] = 'Norwegian (Bokmål)';
$string['nn-no'] = 'Norwegian (Nynorsk)';
$string['pl-pl'] = 'Polish';
$string['ro-ro'] = 'Romanian';

$string['bg-bg'] = 'Bulgarian'; // Bulgarian
$string['cs-cz'] = 'Czech'; // Czech
$string['el-gr'] = 'Greek'; // Greek
$string['hr-hr'] = 'Croatian'; // Croatian
$string['lt-lt'] = 'Lithuanian'; // Lithuanian
$string['lv-lv'] = 'Latvian'; // Latvian
$string['sk-sk'] = 'Slovak'; // Slovak
$string['sl-si'] = 'Slovenian'; // Slovenian
$string['so-so'] = 'Somali'; // Slovenian
$string['ps-af'] = 'Pashto'; // Afghan Pashto
$string['is-is'] = 'Icelandic'; // Icelandic
$string['mk-mk'] = 'Macedonian'; // Macedonian
$string['sr-rs'] = 'Serbian'; // Serbian
$string['vi-vn'] = 'Vietnamese'; // Vietnamese

$string['awsregion'] = 'AWS region';
$string['region'] = 'AWS region';
$string['awsregion_details'] = 'Choose the region closest to you. Your data will stay within that region. Capetown region only supports English and German.';
$string['expiredays'] = 'Days to keep file';
$string['aigradenow'] = 'AI grade';

$string['machinegrading'] = 'Machine evaluations';
$string['viewmachinegrading'] = 'Machine evaluation';
$string['review'] = 'Review';
$string['regrade'] = 'Regrade';

$string['spotcheckbutton'] = "Spot check mode";
$string['gradingbutton'] = "Grading mode";
$string['transcriptcheckbutton'] = "Transcript check mode";
$string['doclear'] = "Clear all markers";

$string['gradethisattempt'] = "Grade this attempt";
$string['rawwpm'] = "WPM";
$string['rawaccuracy_p'] = 'Acc(%)';
$string['rawgrade_p'] = 'Grade(%)';
$string['adjustedwpm'] = "Adj. WPM";
$string['adjustedaccuracy_p'] = 'Adj. acc(%)';
$string['adjustedgrade_p'] = 'Adj. grade(%)';

$string['evaluationview'] = "Evaluation display";
$string['evaluationview_details'] = "What to show students after they have attempted and received an evaluation";
$string['humanpostattempt'] = "Evaluation display (human)";
$string['machinepostattempt'] = "Evaluation display (machine)";
$string['machinepostattempt_details'] = "What to show students after they have attempted and received a machine evaluation";
$string['postattempt_none'] = "Show the passage. Don't show evaluation or errors.";
$string['postattempt_eval'] = "Show the passage, and evaluation(WPM,Acc,Grade)";
$string['postattempt_evalerrorsnograde'] = "Show the passage, evaluation(WPM, Acc) and errors";
$string['postattempt_evalerrors'] = "Show the passage, evaluation(WPM,Acc,Grade) and errors";


$string['attemptsperpage'] = "Attempts to show per page: ";
$string['backtotop'] = "Check for results";
$string['transcript'] = "Transcript";
$string['quickgrade'] = "Quick grade";
$string['ok'] = "OK";
$string['ng'] = "Not OK";
$string['notok'] = "Not OK";
$string['machinegrademethod'] = "Human/Machine grading";
$string['machinegrademethod_help'] = "Use machine evaluations or human evaluations as grades in grade book.";
$string['machinegradenone'] = "Never use machine eval. for grade";
$string['machinegradehybrid'] = "Use human or machine eval. for grade";
$string['machinegrademachineonly'] = "Always use machine eval. grade";
$string['admintab'] = "Administrator";
$string['viewadmintab'] = 'View administrator tab';
$string['machineregradeall'] = 'Save and re-evaluate all attempts';
$string['pushalltogradebook'] = 'Re-push evaluations to gradebook';
$string['currenterrorestimate'] = 'Current error estimate: {$a}';
$string['admintabtitle'] = 'Administrator';
$string['admintabinstructions'] =
        'On this page you can edit the alternatives for the passage while viewing a summary of the mistranscriptions. When you save, all the attempts will be re-evaluated and the adjusted grades to the gradebook.';

$string['noattemptsregrade'] = 'No attempts to regrade';
$string['machineregraded'] = 'Successfully regraded {$a->done} attempts. Skipped {$a->skipped} attempts.';
$string['machinegradespushed'] = 'Successfully pushed grades to gradebook';

$string['notimelimit'] = 'No time limit';
$string['xsecs'] = '{$a} seconds';
$string['onemin'] = '1 minute';
$string['xmins'] = '{$a} minutes';
$string['oneminxsecs'] = '1 minutes {$a} seconds';
$string['xminsecs'] = '{$a->minutes} minutes {$a->seconds} seconds';

$string['postattemptheader'] = 'Post attempt options';
$string['recordingaiheader'] = 'Recording and AI options';

$string['grader'] = 'Graded by';
$string['grader_ai'] = 'AI';
$string['grader_human'] = 'Human';
$string['grader_ungraded'] = 'Ungraded';

$string['displaysubs'] = '{$a->subscriptionname} : expires {$a->expiredate}';
$string['noapiuser'] = "No API user entered. Read Aloud will not work correctly.";
$string['noapisecret'] = "No API secret entered. Read Aloud will not work correctly.";
$string['credentialsinvalid'] = "The API user and secret entered could not be used to get access. Please check them.";
$string['appauthorised'] = "Poodll Read Aloud is authorised for this site.";
$string['appnotauthorised'] = "Poodll Read Aloud is NOT authorised for this site.";
$string['refreshtoken'] = "Refresh license information";
$string['notokenincache'] = "Refresh to see license information. Contact Poodll support if there is a problem.";
// These errors are displayed on activity page.
$string['nocredentials'] = 'API user and secret not entered. Please enter them on <a href="{$a}">the settings page.</a> You can get them from <a href="https://poodll.com/member">Poodll.com.</a>';
$string['novalidcredentials'] = 'API user and secret were rejected and could not gain access. Please check them on <a href="{$a}">the settings page.</a> You can get them from <a href="https://poodll.com/member">Poodll.com.</a>';
$string['nosubscriptions'] = "There is no current subscription for this site/plugin.";

$string['privacy:metadata:attemptid'] = 'The unique identifier of a users Read aloud attempt.';
$string['privacy:metadata:readaloudid'] = 'The unique identifier of a Read Aloud activity instance.';
$string['privacy:metadata:userid'] = 'The user id for the Read Aloud attempt';
$string['privacy:metadata:filename'] = 'File urls of submitted recordings.';
$string['privacy:metadata:wpm'] = 'The Words Per Minute score for the attempt';
$string['privacy:metadata:accuracy'] = 'The accuracy score for the attempt';
$string['privacy:metadata:sessionscore'] = 'The session score for the attempt';
$string['privacy:metadata:sessiontime'] = 'The session time(recording time) for the attempt';
$string['privacy:metadata:sessionerrors']
        = 'The reading errors for the attempt';
$string['privacy:metadata:sessionendword'] = 'The position of last word for the attempt';
$string['privacy:metadata:errorcount'] = 'The reading error count for the attempt';
$string['privacy:metadata:timemodified'] = 'The last time attempt was modified for the attempt';
$string['privacy:metadata:attempttable'] = 'Stores the scores and other user data associated with a read aloud attempt.';
$string['privacy:metadata:aitable'] =
        'Stores the scores and other user data associated with a read aloud attempt as evaluated by machine.';
$string['privacy:metadata:transcriptpurpose'] = 'The recording short transcripts.';
$string['privacy:metadata:fulltranscriptpurpose'] = 'The full transcripts of recordings.';
$string['privacy:metadata:cloudpoodllcom:userid'] =
        'The ReadAloud plugin includes the moodle userid in the urls of recordings and transcripts';
$string['privacy:metadata:cloudpoodllcom'] = 'The ReadAloud plugin stores recordings in AWS S3 buckets via cloud.poodll.com.';

$string['mistranscriptions_summary'] = 'Summary of mistranscriptions.';
$string['nomistranscriptions'] = 'No mistranscriptions.';
$string['passageindex'] = 'Passage index';
$string['passageword'] = 'Passage word';
$string['mistranscriptions'] = 'Mistranscriptions';
$string['mistrans_count'] = 'Count';
$string['total_mistranscriptions'] = 'Total mistranscriptions: {$a}';
$string['startreading'] = 'Read';
$string['readagain'] = 'Read again';
$string['transcriber_guided'] = 'Guided STT (Poodll)';
$string['transcriber_strict'] = 'Open STT (Strict)';

$string['stricttranscribe'] = 'Passage transcriber';
$string['stricttranscribe_details'] = 'The transcriber to use for full passage readings.';

$string['sessionscoremethod'] = 'Grade calculation';
$string['sessionscoremethod_help'] = 'The value(%) for gradebook is calculated as a percentage, either WPM / Target_WPM (normal) or (WPM - Errors)/ Target_WPM (strict)';
$string['sessionscorenormal'] = 'Normal: Total correct words per min / Target_WPM';
$string['sessionscorestrict'] = 'Strict: (Total correct words - errors) per min /Target WPM';
$string['modelaudio'] = 'Model audio';
$string['ttsvoice'] = 'TTS voice';
$string['enablepreview'] = 'Enable listen mode';
$string['enableshadow'] = 'Enable practice mode (Shadowing)';
$string['enablelandr'] = 'Enable practice mode (Listen and Repeat)';
$string['savemodelaudio'] = 'Save recording';
$string['uploadmodelaudio'] = 'Upload audio file';
$string['modelaudioclear'] = 'Clear audio';
$string['modelaudiobreaksgenerate'] = 'Re-Generate model audio markup';
$string['modelaudio_recordinstructions'] = 'Record audio here to be used as the model audio. You can optionally choose to upload audio by pressing the upload audio button. There will be a delay of a few minutes before break point text and audio are automatically synced';
$string['modelaudio_playerinstructions'] = 'The current model audio can be played using the player below.';
$string['modelaudio_breaksinstructions'] = 'Tap words in the passage below to add a break at that point in the audio playback in preview and practice modes. The system will automatically sync the audio and the text. Check <i>manual break timing</i> to set tapped breaks to current location of playing audio.';
$string['modelaudio_recordtitle'] = 'Record model audio';
$string['modelaudio_playertitle'] = 'Play model audio';
$string['modelaudio_breakstitle'] = 'Mark-up model audio';
$string['viewmodeltranscript'] = 'View model transcript';

$string['ttsspeed'] = 'TTS speed';
$string['mediumspeed'] = 'Medium';
$string['slowspeed'] = 'Slow';
$string['extraslowspeed'] = 'Extra slow';


$string['welcomemenu'] = 'Choose from the options below.';
$string['returnmenu'] = 'Return to menu';
$string['attemptno'] = 'Attempt {$a}';
$string['previewhelp'] = "Listen to a speaker read the passage aloud. You do not need to read aloud.";
$string['readhelp'] = "Read the passage aloud. Speak at a speed that is natural for you.";
$string['shadowhelp'] = "Read the passage aloud, along with the teacher. You should wear headphones.";
$string['practicehelp'] = "Listen to the speaker. Repeat after each sentence and check your pronunciation.";
$string['quizhelp'] = "Read the passage silently. Then answer the questions about the passage.";
$string['quizfinishedhelp'] = "Check your results. How well did you understand the passage?";
$string['playbutton'] = "Play";
$string['recordbutton'] = "Record";
$string['stopbutton'] = "Stop";
$string['taptolisten'] = "Tap to listen";

$string['returntomenu'] = "Return to Menu";
$string['fullreport'] = "View Full Report";
$string['fullreportnoeval'] = "View Passage";


$string['secs_till_check'] = 'Checking for results in: ';
$string['checking'] = ' ... checking ... ';

$string['recorder'] = 'Audio recorder type';
$string['recorder_help'] = 'Choose the audio recorder type that best suits your students and situation.';
$string['defaultrecorder'] = 'Default recorder';
$string['defaultrecorder_details'] = 'Choose the default recorder to be shown to students. ';
$string['rec_readaloud'] = 'Mic-test then start';
$string['rec_once'] = 'Just start';
$string['rec_upload'] = 'Upload (for devs/admins)';

$string['close'] = 'Close';
$string['modelaudiowarning'] = "Model audio not marked up.";
$string['modelaudiobreaksclear'] = ' Clear model audio markup';
$string['savemodelaudiomarkup'] = ' Save model audio markup';
$string['enablesetuptab'] = "Enable setup tab";
$string['enablesetuptab_details'] = "Show a tab containing the activity instance settings to admins. Not super useful in most cases.";
$string['setup'] = "Setup";
$string['manualbreaktiming'] = ' Manual break timing';

// rsquestions
$string['numeric'] = 'Must be numeric ';
$string['iteminuse'] = 'This item is part of users attempt history. It cannot be deleted.';

// Questions.
$string['rsquestions'] = 'Questions';
$string['managersquestions'] = 'Manage questions';
$string['correctanswer'] = 'Correct answer';
$string['incorrectanswer'] = 'Incorrect answer';
$string['whatdonow'] = 'Add or edit questions for the post-reading quiz.';
$string['editingitem'] = 'Editing a question';
$string['createaitem'] = 'Create a question';
$string['edit'] = 'Edit';
$string['item'] = 'Item';
$string['itemtitle'] = 'Question title';
$string['itemcontents'] = 'Question text';
$string['answer'] = 'Answer';
$string['saveitem'] = 'Save item';
$string['itemname'] = 'Question name';
$string['itemorder'] = 'Item order';
$string['actions'] = 'Actions';
$string['edititem'] = 'Edit item';
$string['previewitem'] = 'Preview item';
$string['duplicateitem'] = 'Duplicate item';
$string['confirmitemdelete'] = 'Are you sure you want to <i>DELETE</i> item? : {$a}';
$string['confirmitemdeletetitle'] = 'Really delete item?';
$string['noitems'] = 'This quiz contains no questions';
$string['textchoice'] = 'Text area choice';
$string['textboxchoice'] = 'Text box choice';
$string['quiz'] = 'Quiz';
$string['waiting'] = '-- waiting --';
$string['waitingforteacher'] = 'Your teacher will check your reading soon.';
$string['quizcompletedwarning'] = "Quiz completed. Tap to review.";


$string['notmasterinstance'] = 'You can not push settings from this ReadAloud activity unless master instance is checked in activity settings.';
$string['push'] = 'Push';
$string['pushpage'] = 'Push page';
$string['pushalternatives'] = 'Push alternatives';
$string['pushalternatives_done'] = 'Alternatives have been pushed';

$string['pushpassage'] = 'Push passage (and related settings)';
$string['pushpassage_done'] = 'Passage has been pushed';

$string['pushquestions'] = 'Push questions';
$string['pushquestions_done'] = 'Questions have been pushed';

$string['pushtargetwpm'] = 'Target WPM';
$string['pushtargetwpm_done'] = 'Target WPM has been pushed';

$string['pushtimelimit'] = 'Time limit';
$string['pushtimelimit_done'] = 'Time limit has been pushed';

$string['pushcanexitearly'] = 'Can exit early';
$string['pushcanexitearly_done'] = 'Can exit early has been pushed';

$string['pushmodes'] = 'Modes';
$string['pushmodes_done'] = 'Modes have been pushed';

$string['pushgradesettings'] = 'Grade settings';
$string['pushgradesettings_done'] = 'Grade settings have been pushed';

$string['pushttsmodelaudio'] = 'Push TTS and model audio';
$string['pushttsmodelaudio_done'] = 'TTS and model audio have been pushed';

$string['masterinstance'] = 'Master instance';
$string['masterinstance_details'] = 'Master instance allows the author to push the individual settings of one ReadAloud to existing copies of the same activity. They must have exactly the same name.';

$string['pushpage_explanation'] = "Use the buttons on this page to push settings from this ReadAloud instance to clones of it (ie activities with the same name). Be careful there is no going back so be sure of your intention before using.";
$string['pushpage_clonecount'] = 'This activity has {$a} clones. <br><br>';
$string['pushpage_noclones'] = 'This activity IS a master instance, but there are no other activities with the same name (ie clones). So there is nothing to push settings to. Check that this is the right activity. If you are just testing, duplicate this activity and rename the duplicate the same as this one.<br><br>';


$string['disableshadowgrading'] = "Disable shadow mode grading";
$string['disableshadowgrading_details'] = "If checked, attempts made in shadow mode will be evaluated, but no entry passed to the gradebook.";
$string['developer'] = "Developer";

$string['freetrial'] = "Get Cloud Poodll API credentials and a free trial";
$string['freetrial_desc'] = "A dialog should appear that allows you to register for a free trial with Poodll. After registering you should login to the members dashboard to get your API user and secret. And to register your site URL.";
// $string['memberdashboard'] = "Member Dashboard";
// $string['memberdashboard_desc'] = "";
$string['fillcredentials'] = "Set API user and secret with existing credentials";
$string['viewstart'] = "Activity open";
$string['viewend'] = "Activity close";
$string['viewstart_help'] = "If set, prevents a student from entering the activity before the start date/time.";
$string['viewend_help'] = "If set, prevents a student from entering the activity after the closing date/time.";
$string['activitydate:submissionsdue'] = 'Due:';
$string['activitydate:submissionsopen'] = 'Opens:';
$string['activitydate:submissionsopened'] = 'Opened:';
$string['open'] = "Open: ";
$string['until'] = "Until: ";
$string['activityopenscloses'] = "Activity open/close dates";
$string['nottsvoice'] = "No TTS voice";

$string['guidedtranscriptionadmin'] = "Guided transcription admin";
$string['usecorpus'] = "Guided transcription type";
$string['usecorpuschanged'] = "Guided transcription type changed";

$string['applysettingsrange'] = "Apply setting to:";
$string['apply_activity'] = "this activity";
$string['apply_course'] = "this course activities";
$string['apply_site'] = "this site activities";

$string['corpusrange'] = "Corpus range";
$string['corpusrange_course'] = "This course";
$string['corpusrange_site'] = "This site";
$string['guidedtrans_corpus'] = "Use corpus (all ReadAloud passages)";
$string['guidedtrans_passage'] = "Use this activity passage";
$string['guidedtransinstructions'] = "When using guided transcription the transcriber will steer the transcript towards the guide, i.e the words/phrases in this activity's passage, or the words/phrases in the full corpus of ReadAloud passages. Using the full corpus of ReadAloud passages will pick up more reading errors.";
$string['pushcorpus_details'] = "The course/site corpus will be updated automatically, but you can use the button below to update and push the corpus if you need to. This will generate a guide from the corpus range, and it will set all ReadAloud activities(using guided transcription) within the range to use the guide.";
$string['pushcorpus_button'] = "Update and push corpus guide";
$string['corpuspushed'] = "Corpus guide pushed";
$string['passagekey'] = 'Passage Key';
$string['passagekey_details'] =
        'The passage key is just a tag that will be exported to csv with some reports to make post processing those reports in a spreadsheet easier. It is fine to leave it empty.';
$string['passagekey_help'] =
        'The passage key is just a tag that will be exported to csv with some reports to make post processing those reports in a spreadsheet easier.';

$string['courseattemptsreport'] = 'Course attempts report';
$string['courseattemptsheading'] = 'Course attempts report';
$string['studentid'] = "St. no.";
$string['studentname'] = "Student name";
$string['activityname'] = "RA. name.";
$string['errorcount'] = "No. errors";
$string['activitywords'] = "No. words in passage";
$string['readingtime'] = "Read time (secs)";
$string['oralreadingscore'] = "Oral reading score";
$string['oralreadingscore_p'] = 'Oral reading score(%)';
$string['reportsmenutoptext'] = "Review attempts on ReadAloud activities using the reports below.";
$string['courseattempts_explanation'] = "All the attempts on ReadAloud activities within this course";
$string['attemptssummary_explanation'] = "A summary of ReadAloud attempts per user in this activity.";

$string['customfont'] = "Custom font";
$string['customfont_help'] = "A font name that will override site default for this passage when displayed. Must be exact in spelling and case. eg Andika or Comic Sans MS";
$string['advancedheader'] = "Advanced";

$string['missedwords'] = "Missed words";
$string['missedwordsheading'] = "Missed words";
$string['missedwordsreport'] = "Missed words";
$string['missedwords_explanation'] = "The top error words in the most recent attempts";
$string['missed_count'] = "Missed count";
$string['rank'] = "Rank";

$string['unit_percent'] = "%";

$string['totalwords'] = 'Total words';
$string['sentences'] = 'Sentences';
$string['uniquewords'] = 'Unique words';
$string['ideacount'] = 'Concepts';
$string['relevance'] = 'Relevance';
$string['original'] = 'Original';
$string['corrected'] = 'Corrected';

$string['confirm_cancel_recording'] = "Cancel recording and quit this attempt?";
$string['confirm_read_again'] = "Cancel this reading and make a new one?";
$string['aitextutilsshow'] = "Show AI text utils (Beta)";
$string['aitextutilshide'] = "Hide AI text utils (Beta)";
$string['textgenerator_instructions'] = "Enter a short non fiction topic description and press the button to generate a passage. It will often not be factually accurate. Please be careful be using it with students.";
$string['textsimplifier_instructions'] = "Choose the simplification level and press the button to simplify the passage. The passage will be simplified to the approximate level you choose. ";
$string['article-topic-here'] = "e.g Pros and cons of social media";
$string['generate-text'] = "Generate passage";
$string['simplify-text'] = "Simplify passage";
$string['entersomething'] = "Please enter a topic in order to generate a passage";
$string['text-too-long-100'] = "Your topic should be no more than 100 characters. Simply describe the topic, don't write a full sentence, or give additional instructions.";
$string['textoverwriteconfirm'] = "Overwrite Ccnfirmation";
$string['reallyoverwritepassage'] = "Overwrite the current passage?";
$string['overwrite'] = "Overwrite";
$string['cancel'] = "Cancel";
$string['datatables_info'] = "Showing _START_ to _END_ of _TOTAL_ entries";
$string['datatables_infoempty'] = "Showing 0 to 0 of 0 entries";
$string['datatables_lengthmenu'] = "Show _MENU_ entries";
$string['datatables_search'] = "Search:";
$string['datatables_zerorecords'] = "No matching records found";
$string['datatables_paginate_first'] = "First";
$string['datatables_paginate_last'] = "Last";
$string['datatables_paginate_next'] = "Next";
$string['datatables_paginate_previous'] = "Previous";
$string['datatables_emptytable'] = "No data available in table";
$string['datatables_aria_sortascending'] = "activate to sort column ascending";
$string['datatables_aria_sortdescending'] = "activate to sort column descending";
$string['one_simplest'] = "one (simplest)";
$string['two'] = "two";
$string['three'] = "three";
$string['four'] = "four";
$string['five'] = "five";
$string['passagepicture'] = 'Passage picture';
$string['passagepicture_descr'] = 'Add a picture into the activity header.';
$string['stdashboardid'] = 'Student dashboard ID';
$string['eventreadaloudattemptsubmitted'] = 'ReadAloud attempt submitted';
$string['bulkdelete'] = 'Delete selected';
$string['bulkdeletequestion'] = 'Are you sure you want to delete the selected question?';
$string['addquestion'] = 'Add question';
$string['multichoice'] = 'Multi choice';
$string['multiaudio'] = 'MC audio';
$string['dictation'] = 'Dictation';
$string['dictationchat'] = 'Dictation chat';
$string['speechcards'] = 'Speech cards';
$string['listenrepeat'] = 'Listen and speak';
$string['page'] = 'Content page';
$string['smartframe'] = 'SmartFrame';
$string['shortanswer'] = 'Short answer';
$string['lgapfill'] = 'Listening gapfill';
$string['sgapfill'] = 'Speaking gapfill';
$string['tgapfill'] = 'Typing gapfill';
$string['spacegame'] = 'Space game';
$string['freewriting'] = 'Free writing';
$string['freespeaking'] = 'Free speaking';
$string['fluency'] = 'Fluency';
$string['passagereading'] = 'Passage reading';
$string['conversation'] = 'Conversation';
$string['pagelayout'] = 'Page layout';
$string['newitem'] = 'Item: {$a}';

$string['completiondetail:mingrade'] = 'Minimum Grade';
$string['completiondetail:allsteps'] = 'All steps';
$string['completionallsteps'] = 'All steps';
$string['allsteps'] = 'All steps';
$string['completionallsteps_help'] = 'All steps must be completed before the activity is complete';
$string['mingrade_help'] = 'The minimum Read Aloud grade(%) required to "complete" this activity.';
$string['allsteps_help'] = 'All steps must be completed before the activity is complete';

$string['d_question'] = 'Item';
$string['freespeaking_instructions1'] = 'Use the microphone to record your answer to the question.';
$string['freewriting_instructions1'] = 'Type your answer to the question in the text area below.';
$string['lg_instructions1'] = 'Listening gapfill instructions';
$string['sg_instructions1'] = 'Speaking gapfill instructions';
$string['tg_instructions1'] = 'Typing gapfill instructions';
$string['multiaudio_instructions1'] = 'Choose the correct answer. Use the mic to read it aloud.';
$string['multichoice_instructions1'] = 'Choose the correct answer.';
$string['shortanswer_instructions1'] = 'Answer the question by using the mic.';
$string['iteminstructions'] = 'Item instructions';
$string['chooselayout'] = 'Choose layout';
$string['layoutauto'] = 'Auto';
$string['layoutvertical'] = 'Vertical';
$string['layouthorizontal'] = 'Horizontal';
$string['layoutmagazine'] = 'Magazine';
$string['mediaprompts'] = "Media prompts";
// Media toggles.
$string['addmedia'] = 'Image / audio or video';
$string['addttsaudio'] = 'TTS Audio';
$string['addtextarea'] = 'Text block';
$string["reallydeletemediaprompt"] = "Really delete media: ";
$string["deletemediaprompt"] = "Delete media?";
$string["choosemediaprompt"] = "Choose media type ..";
$string["deletefilesfirst"] = "Delete any files you added manually. They will not be deleted automatically.";
$string["cleartextfirst"] = "Clear any content you added manually. It will not be deleted automatically.";

$string['itemmedia'] = 'Image, audio or video to show';
$string['itemttsquestion'] = 'TTS prompt text';
$string['itemttsquestionvoice'] = 'TTS prompt speaker';
$string['itemtextarea'] = 'Text block';

// TTS options.
$string['choosevoiceoption'] = 'TTS prompt options';
$string['autoplay'] = 'Autoplay';
$string["itemsettingsheadings"] = "Item Settings";


$string['enterresponses'] = 'Enter a list of correct responses in the text area below. Place each response on a new line.';
$string['correctresponses'] = 'Correct responses';
$string['choosevoice'] = "Choose the prompt speaker's voice";
$string['choosemultiaudiovoice'] = "Choose the answer reader's voice";
$string['showoptionsastext'] = 'Show answers as text';
$string['showtextprompt'] = 'Show text prompt';
$string['textprompt_words'] = 'Show full text';
$string['textprompt_dots'] = 'Show dots instead of letters';
$string['listenorread'] = "Display options as";
$string['listenorread_read'] = 'plain text';
$string['listenorread_listen'] = 'audio players + dots';
$string['listenorread_listenandread'] = 'audio players + plain text';
$string['listenorread_image'] = 'images + plain text';
$string['confirmchoice_formlabel'] = "Must attempt (no skip)";
$string['continue'] = "Continue <i class='fa fa-arrow-right'></i>";
$string['confirmchoice'] = "Check";
$string['listeninggapfill'] = 'Listening gapfill';
$string['speakinggapfill'] = 'Speaking gapfill';
$string['typinggapfill'] = 'Typing gapfill';
$string['gapfillitemsdesc'] = 'Enter the list of items in the text area below. Each item should be on a new line. The letter gaps should be enclosed in square brackets: [ ].The format is:<br>Text prompt | hint<br>.e.g  This is my d[og]| a common pet';
$string['listeninggapfillitemsdesc'] = 'Enter the list of items in the text area below. Each item should be on a new line. The letter gaps should be enclosed in square brackets: [ ]. The format is:<br>Text prompt<br>.e.g  This is my d[og]';
$string['readsentences'] = 'Read sentences (TTS)';
$string['readsentences_desc'] = 'If checked each sentence will be read aloud. It will be a form of dictation';
$string['allowretry'] = 'Allow retry';
$string['allowretry_desc'] = 'If checked allows students to submit new attempts, if their previous response was not correct.';
$string['hidestartpage'] = 'Hide start page';
$string['hidestartpage_desc'] = 'If checked the activity item begins as soon as it has loaded.';
$string['sentenceprompts'] = 'Sentences (prompts)';
$string['relevancetype'] = 'Relevance type';
$string['relevancetype_none'] = 'Relevance not considered';
$string['relevancetype_question'] = 'Relevance to the question (item text)';
$string['relevancetype_modelanswer'] = 'Relevance to a model answer';
$string['freewritingdesc'] = 'Set target word count and grading and feedback guidelines for the AI evaluation. Students should type their answer to the topic, and they will receive an AI powered grade and feedback.';
$string['freespeakingdesc'] = '<b>Free Speaking is a BETA item type.</b> Different browsers and mobile devices may behave differently.<br/><br/> Set target word count and grading and feedback guidelines for the AI evaluation. Students should record themselves speaking on the topic, and they will receive an AI powered grade and feedback.';
$string['freespeaking_default_aigrade'] = 'Deduct 1 point for each grammar mistake but do not penalize for spelling or punctuation errors.';
$string['freespeaking_default_aigradefeedback'] = 'Explain each grammar mistake simply.';
$string['freewriting_default_aigrade'] = 'Deduct 1 point for each grammar, spelling or punctuation error.';
$string['freewriting_default_aigradefeedback'] = 'Explain each mistake simply.';
$string['writehere'] = 'Write here ..';
$string['submit'] = 'Submit';
$string['fs_totalmarks_instructions'] = 'The total marks this free speaking item contributes to the quiz score.';
$string['fw_totalmarks_instructions'] = 'The total marks this free writing item contributes to the quiz score.';
$string['targetwordcount_title'] = 'Target word count';
$string['totalmarks'] = 'Total marks';
$string['aigrade_instructions'] = 'Grading instructions for AI';
$string['aigrade_feedback'] = 'Feedback instructions for AI';
$string['aigrade_feedback_language'] = 'AI feedback language';
$string["aigrade_feedback_title"] = "Feedback";

$string['action'] = 'Action';
$string['order'] = 'Order';
$string['deletebuttonlabel'] = 'DELETE';
$string['totalscore'] = 'Score';
$string['reattempttitle'] = "Reattempt quiz";
$string['reattemptbody'] = "Do you want to reattempt this quiz?";
$string['questiontext'] = "Question";
$string['check'] = "Check";
$string['skip'] = "Skip";
$string['start'] = "Start";
$string['score'] = "Score";
$string['currentwordcount'] = "Word count";
$string['showcorrections'] = "Show inline corrections";
$string['hidecorrections'] = "Hide inline corrections";
$string['reallyreattempt'] = 'Your previous attempt will be overwritten. Are you sure you want to try again?';
$string['answerdetails'] = 'Answer details';

$string["allowmicaccess"] = "Please allow access to your microphone.";
$string["nomicdetected"] = "No microphone detected.";
$string["speechnotrecognized"] = "We could not recognize your speech.";
$string['gapfill_results'] = 'Results';
$string['loading'] = 'Loading...';
$string['dc_results'] = 'Results';

$string["quizsettingsheader"] = "Quiz settings";
$string["quizscore"] = "Quiz score";
$string["showqtitles"] = "Show question titles";
$string["showqtitles_help"] = "Show question titles";
$string["showqreview"] = "Show quiz review";
$string["showqreview_help"] = "Show quiz review";
$string["qfinishscreen"] = "Quiz finish screen";
$string["qfinishscreen_details"] = "When you finish the quiz, you can see a simple screen, a full screen or a custom screen. The custom screen is a page you can design yourself.";
$string["qfinishscreen_help"] = "When you finish the quiz, you can see a simple screen, a full screen or a custom screen. The custom screen is a page you can design yourself.";
$string["qfinishscreen_simple"] = "Simple - score only";
$string["qfinishscreen_full"] = "Full - score and question details";
$string["qfinishscreen_custom"] = "Custom";
$string["qfinishscreencustom"] = "Custom finish screen";
$string["qfinishscreencustom_help"] = "The custom screen is an advanced feature, that allows you to build a custom finish screen using mustache notation and variables. Some of the variables are: {total} {courseurl} {coursename} {yellowstars} {graystars} {reattempturl} and an array of {results} each with {title}, {grade}, {yellowstars} and {graystars} variables.";

// Modes.
$string['home'] = 'Home';
$string['mode_listen'] = 'Listen';
$string['mode_practice'] = 'Practice';
$string['mode_quiz'] = 'Quiz';
$string['mode_read'] = 'Read';
$string['mode_shadow'] = 'Shadow';
$string['mode_report'] = 'Report';

$string['next'] = 'Next';
$string['prev'] = 'Prev';
$string['taptospeak'] = 'Tap to speak';

$string['enablenativelanguage'] = "Enable native language";
$string['enablenativelanguage_details'] = 'If set, the student can choose their native language, this will override the default feedback language that AI returns with the quiz free writing and free speaking results. The language must currently be <a href="https://support.poodll.com/en/support/solutions/articles/19000163890-definitions-in-user-s-native-language">set in Poodll WordCards</a>.';
$string['letsadditems'] = 'Lets add some questions!';
$string['additems'] = 'Add quiz questions';
$string['numberonly'] = 'Numbers only';
$string['aigrade_modelanswer'] = 'Model answer';
$string['enableread'] = 'Enable read';
$string['enablequiz'] = 'Enable quiz';
$string['activitysteps'] = 'Activity steps';
$string['activitystepsdetails'] = 'Set the learning steps in this ReadAloud activity.';
$string['alternatestreaming'] = 'Enable alternate streaming';
$string['alternatestreaming_details'] = 'Streams recorded audio for open transcription. Slightly slower then the default browser transcription and only works in English. On by default in mobile app.';
$string['cloudpoodllserver'] = 'Cloud Poodll server';
$string['cloudpoodllserver_details'] = 'The server to use for Cloud Poodll. Only change this if Poodll has provided a different one.';


$string['almost'] = 'Almost...';
$string['almost_desc'] = 'You mispronounced some words. Would you like to try again or continue?';
$string['continue'] = 'Continue';
$string['dontshowtilltheend'] = "Don't show this till the end";
$string['imready'] = "I'm ready";
$string['incorrect'] = 'Incorrect';
$string['incorrect_desc'] = "You did not say that correctly. Would you like to try again or continue?";
$string['keeplistening'] = 'Keep listening';
$string['keeppracticing'] = 'Keep practicing';
$string['listen'] = 'Listen';
$string['listenorpractice'] = 'You can continue listening or start practicing.';
$string['nextsentence'] = 'Next sentence';
$string['noquestions'] = 'There are no questions to show.';
$string['practice'] = 'Practice';
$string['practicecomplete'] = 'Superb you completed the practice session!';
$string['practicecomplete_desc'] = 'It looks like you are ready to read the full passage.';
$string['question'] = 'Question?';
$string['questions'] = 'Questions';
$string['quizresults'] = 'Quiz results';
$string['quiztime'] = 'Quiz time';
$string['quiztimehelp'] = 'Take the quiz to test your reading skill further.';
$string['readaloudresults'] = 'Read aloud results';
$string['readingpassage'] = 'Reading passage';
$string['readreporthelp'] = "Check your results. How well did you understand the passage?";
$string['readreportdummyhelp'] = "Your results are on their way ... please wait ... ";
$string['nowevaluatingreading'] = "We are evaluating your reading .. wait a moment ...";

$string['takethequiz'] = 'Take the quiz';
$string['timetopractice'] = 'Finished listening?';
$string['tryagain'] = 'Try again';
$string['viewfinalreport'] = 'View final report';
$string['viewfinalreportintro'] = 'Your complete results and progress summary.';
$string['finalreporthelp'] = 'Your complete results and progress summary.';
$string['welldone'] = 'Well done!';
$string['welldone_desc'] = 'You pronounced all of the words correctly!';
$string['quitlistening'] = 'Finish listening';
$string['improveyourscore'] = 'Want to try to improve your score?';
$string['reallyreattemptquiz'] = 'Reattempting the quiz will overwrite your previous attempt. Are you sure you want to try again?';
$string['quizreattempt'] = 'Can reattempt the quiz';
$string['quizreattempt_help'] = 'Allow student to reattempt the quiz within the current attempt.';
$string['readreattempt'] = 'Can reattempt reading';
$string['readreattempt_help'] = 'Allow student to reattempt the reading within the current attempt.';

$string['azureapikey_details'] = 'This is the API key for using Azure speech services with ReadAloud. It is optional.  This is primarily for use by our users in Mainland China. See <a href= "https://learn.microsoft.com/en-us/azure/cognitive-services/speech-service/overview">here</a> for more details.';
$string['azureapiregion_details'] = 'This is the region for your Azure speech services API key. If you do not have one you can get one from the Azure portal.';
$string['machinegrademethod_details'] = "Use machine evaluations or human evaluations as grades in grade book.";
$string['sessionscoremethod_details'] = 'How the value(%) for gradebook is calculated.';
$string['ttslanguage_details'] = 'This value is used for speech recognition and text to speech.';
$string['itemsperpage_details'] = 'This sets the number of rows to be shown on reports or lists of attempts.';
$string['stdashboardid_details'] = 'If the student dashboard block is installed, put the id of the block here.';

// Duplicate strings.
$string['readaloud:view'] = 'Preview Read Aloud';
$string['readaloud:view'] = 'View Read Aloud';
$string['readaloud:itemedit'] = 'Edit questions';
$string['readaloud:itemedit'] = 'Edit items';
$string['readaloud:itemview'] = 'View questions';
$string['readaloud:itemview'] = 'View items';
$string['timecreated'] = 'Time created';
$string['timecreated'] = 'Time created';
$string['welcomelabel'] = 'Default instructions';
$string['welcomelabel'] = 'Pre-attempt instructions';
$string['feedbacklabel'] = 'Post-attempt instructions';
$string['feedbacklabel'] = 'Default feedback';
$string['nodataavailable'] = 'No data available';
$string['nodataavailable'] = 'No data available yet';
$string['transcriber'] = 'Line transcriber';
$string['transcriber'] = 'Transcriber';
$string['transcriber_details'] = 'The transcription engine to use';
$string['transcriber_details'] = 'The transcription engine to use for line by line reading.';
$string['correct'] = 'Correct';
$string['correct'] = 'Correct';
$string['itemtype'] = 'Item type';
$string['itemtype'] = 'Item type';
$string['deleteitem'] = 'Delete item';
$string['deleteitem'] = 'Delete item';
$string['guidedtrans_corpus'] = "Use corpus texts";
$string['guidedtrans_corpus'] = "Use corpus (all ReadAloud passages)";
$string['reattemptquiz'] = 'Reattempt quiz';
$string['reattemptquiz'] = 'Reattempt the quiz?';
$string['addtextarea_instructions'] = 'Enter the text you want to show in the lesson item.';
$string['addttsaudio_instructions'] = 'Enter the text you want to be spoken by the TTS engine.';
$string['addmedia_instructions'] = 'Choose the media type you want to show in the lesson item.';

// $string['waitforpassage'] = "There is no reading passage set yet for this activity. You will not be able to do the activity until your teacher adds one";
// $string['ttsveryslow'] = 'Very Slow';
// $string['ttsssml'] = 'SSML';
// $string['ttsnormal'] = 'Normal';
// $string['ttsslow'] = 'Slow';
// $string['transcriber_warning'] = 'You have selected instant transcription. Note that this will <strong>only work if passage language and region are correct</strong>.';
// $string['transcriber_none'] = 'No transcription';
// $string['transcriber_auto'] = 'Open STT (Strict)';
// $string['transcriber_poodll'] = 'Guided STT (Poodll)';
// $string['thatsnotright'] = 'Something is wrong';
// $string['taptorecord'] = "Tap to record";
// $string['summaryexplainer'] = 'The table below shows your average and your highest scores for this activity.';
// $string['submitnow'] = 'Submit';
// $string['startshadowreading'] = 'Shadow practice';
// $string['shuffleanswers'] = 'Shuffle answers';
// $string['shufflequestions'] = 'Shuffle questions';
// $string['showquestionscores'] = "Show question scores";
// $string['seeanswerdetails'] = 'see details';
// $string['returntomenu'] = "Return to menu";
// $string['readaloudfieldset'] = 'Custom example fieldset';
// $string['inprogress'] = 'In progress';
// $string['readaloud:tts'] = 'Can use Text To Speech(tts)';
// $string['attemptsbyuserheading'] = 'User Attempts Report';
// $string['overview'] = 'Overview';
// $string['gradelowest'] = 'lowest scoring attempt';
// $string['gradeaverage'] = 'average score of all attempts';
// $string['myattemptssummary'] = 'Attempts summary ({$a} attempts)';
// $string['av_grade_p'] = 'Av. grade(%)';
// $string['h_grade_p'] = 'Max grade(%)';
// $string['apisecret_details'] =
// 'The Poodll API secret. See <a href= "https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret">here</a> for more details';
// $string['humanpostattempt_details'] = "What to show students after they have attempted and received a human evaluation";
// $string['previewreading'] = 'Listen';
// $string['practicereading'] = 'Practice';
// $string['enablepreview_details'] = 'Listen mode shows the reading and model audio to student before the activity commences.';
// $string['enableshadow_details'] = 'Enables shadowing mode. This plays the model audio as students are read the entire passage aloud. Students will need headphones for this.';
// $string['enablelandr_details'] = 'Enables listen and repeat mode. Line by line, the student listens and reads alternately.';
// $string['progresschart'] = 'Progress chart';
// $string['chartexplainer'] = 'The chart below shows your progress over time in reading this passage.';
// $string['fullreportnoeval'] = "View passage";

// $string['nocourseid'] = 'You must specify a course_module ID or an instance ID. Probably your session expired.';
// $string['pushgradesettings_desc'] = 'Push some of grade settings (completion cond. min grade, grade calculation, human/machine grading, highest/latest attempt) from this instance to clone instances. This wont update the max grade or other settings that affect the gradebook setup nor will it force a regrade of existing attempts. It is best to only use this on not yet attempted clones.';
// $string['pushmodes_desc'] = 'Push the optional activity mode settings (preview, listen and repeat and shadow) from this instance to clone instances.';
// $string['pushcanexitearly_desc'] = 'Push the \'Can exit early\' setting to all clone instances. This setting allows users to exit the activity before the time limit is reached.';
// $string['pushtimelimit_desc'] = 'Push the time limit setting to all clone instances.';
// $string['pushtargetwpm_desc'] = 'Push the Target WPM setting to all clone instances.';
// $string['pushquestions_desc'] = 'You could push comprehension questions from here if there were any. They will be implemented soon.';
// $string['pushpassage_desc'] = 'Push passage and phonetics and segments and other elements that are unique to the passage, to clones. ';
// $string['pushalternatives_desc'] = 'Push alternatives field to all clone instances.';
// $string['audioresponse'] = 'Audio response';
// $string['correcttranslationtitle'] = 'Correct translation';
// $string['avgcorrect'] = 'Av. correct';
// $string['avgtotaltime'] = 'Av. duration';
// $string['itemdetails'] = 'Item details: {$a}';
// $string['itemsummary'] = 'Item summary: {$a}';
// $string['iscorrectlabel'] = 'Correct/Incorrect';
// $string['audioitemfile'] = 'Item audio (MP3)';
// $string['addtextpromptshortitem'] = 'Add item';
// $string['addnewitem'] = 'Add a new question';
// $string['addingitem'] = 'Adding a new question';
// $string['moveitemup'] = 'Up';
// $string['moveitemdown'] = 'Down';
// $string['nopassage'] = "No reading passage";
// $string['addpassage'] = "Setup activity";
// $string['letsaddpassage'] = "There is no reading passage set yet for this activity. Lets add one.";
// $string['relevancetype_desc'] = 'AI will penalize answers of low relevance. Choose the type of relevance to use.';

// $string['addiframe'] = 'iFrame / custom HTML';
// $string['addiframe_instructions'] = 'Paste the embed code for the iframe you want to show in the lesson item.';
// $string['addmultichoiceitem'] = 'Multi choice';
// $string['addmultiaudioitem'] = 'MC audio';
// $string['addpageitem'] = 'Content page';
// $string['addshortansweritem'] = 'Short answer';
// $string['addlisteninggapfillitem'] = 'Listening gapfill';
// $string['addspeakinggapfillitem'] = 'Speaking gapfill';
// $string['addtypinggapfillitem'] = 'Typing gapfill';
// $string['addfreewritingitem'] = 'Free writing';
// $string['addfreespeakingitem'] = 'Free speaking';
// $string['datatables_infofiltered'] = "(filtered from _MAX_ total entries)";
// $string['datatables_infothousands'] = ",";
// $string['passagekey_details'] =
// 'The passage key is just a tag that will be exported to csv with some reports to make post processing those reports in a spreadsheet easier. It is fine to leave it empty.';
// $string['pushttsmodelaudio_desc'] = 'Push TTS and model audio related settings, this will not push any uploaded/recorded audio. It will push TTS audio and meta data including audio breaks.';
// $string['practiceiconalt'] = 'Practice';
// $string['mode_listenandrepeat'] = 'Listen and repeat';
// $string['mode_tooltip_notcomplete'] = 'Next: {{a}}'; // Adds the next mode name.
// $string['mode_tooltip_end'] = 'End';
// $string['qfinishscreencustom_details'] = "If the quiz finish screen options are set to 'custom' this will be the default mustache template that generates the finish screen. It can be overridden at the quiz level.";
// $string['notsubmit'] = 'Not submitted';
// $string['notsubmitted'] = 'You have not submitted your answer. Submit now?';
// $string['questionscore'] = "Score";
// $string['backtomenu'] = "Back to top menu";
// $string['deleteitem_message'] = 'Really delete item:&nbsp;';
