<?php

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
        'ReadAloud is an activity designed to assist teachers in evaluating their students reading fluency. Students read a passage, set by the teacher, into a microphone. Later the teacher can mark words as incorrect and get the student WCPM(Words Correct Per Minute) scores.';
$string['readaloudfieldset'] = 'Custom example fieldset';
$string['readaloudname'] = 'Poodll ReadAloud';
$string['readaloudname_help'] =
        'This is the content of the help tooltip associated with the readaloudname field. Markdown syntax is supported.';


$string['activitylink'] = 'Link to next activity';
$string['activitylink_help'] = 'To provide a link after the attempt to another activity in the course, select the activity from the dropdown list.';
$string['activitylinkname'] = 'Continue to next activity: {$a}';
$string['complete'] = 'Complete';
$string['inprogress'] = 'In progress';
$string['locked'] = 'Locked';
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
//$string['attemptsbyuserheading'] = 'User Attempts Report';
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
$string['viewreports'] = 'View Report';
$string['reports'] = 'Reports';
$string['viewgrading'] = 'View Grading';
$string['grading'] = 'Grading';
$string['gradenow'] = 'Grade Now';
$string['cannotgradenow'] = ' - ';
//$string['gradenowtitle'] = 'Grading: {$a}';
$string['showingattempt'] = 'Showing attempt for: {$a}';
$string['showingmachinegradedattempt'] = 'Machine evaluated attempt for: {$a}';
$string['basicreport'] = 'Basic Report';
$string['returntoreports'] = 'Return to Reports';
$string['returntogradinghome'] = 'Return to Grading Top';
$string['returntomachinegradinghome'] = 'Return to Machine Evaluations Top';
$string['exportexcel'] = 'Export to CSV';
//$string['mingradedetails'] = 'The minimum Read Aloud grade(%) required to "complete" this activity.';
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
//$string['defaultsettings'] = 'Default Settings';
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

$string['defaultwelcome'] =
        'In this activity you should read a passage out loud. You may be required to test your microphone first. You should see the audio recorder below. After you have started recording the reading passage will appear. Read the passage aloud as clearly as you can.';
$string['defaultfeedback'] = 'Thanks for reading.';
$string['timelimit'] = 'Time Limit';
$string['gotnosound'] = 'We could not hear you. Please check the permissions and settings for microphone and try again.';
$string['done'] = 'Done';
$string['processing'] = 'Processing';
//$string['feedbackheader'] = 'Finished';
$string['beginreading'] = 'Begin Reading';
$string['errorheader'] = 'Error';
$string['uploadconverterror'] =
        'An error occured while posting your file to the server. Your submission has NOT been received. Please refresh the page and try again.';
$string['attemptsreport'] = 'Attempts Report';
$string['attemptssummaryreport'] = 'Attempts Summary Report';
$string['myattemptssummary'] = 'Attempts Summary ({$a} attempts)';
$string['summaryexplainer'] = 'The table below shows your average and your highest scores for this activity.';
$string['averages'] = 'Average';
$string['highest'] = 'Highest';
$string['submitted'] = 'submitted';
$string['id'] = 'ID';
$string['username'] = 'User';
$string['audiofile'] = 'Audio';
$string['wpm'] = 'WPM';
$string['timecreated'] = 'Time Created';
$string['nodataavailable'] = 'No Data Available Yet';
$string['saveandnext'] = 'Save .... and next';
$string['reattempt'] = 'Try Again';
$string['notgradedyet'] = 'Your submission has been received, but has not been graded yet. It might take a few minutes.';
$string['notmanuallygradedyet'] = 'Your submission has been received, but has not been graded yet.';
$string['evaluatedmessage'] = 'Your latest attempt has been received and the evaluation is shown below.';
$string['hiddenevaluationmessage'] = 'Your attempt has been received. Thank you.';
$string['notaddedtogradebook'] = 'This was a shadow practice, and not added to gradebook.';
$string['enabletts'] = 'Enable TTS(experimental)';
$string['enabletts_details'] = 'TTS is currently not implemented';
//we hijacked this setting for both TTS STT .... bad ... but they are always the same aren't they?
$string['ttslanguage'] = 'Passage Language';
$string['ttslanguage_details'] = 'This value is used for speech recognition and text to speech.';
$string['deleteattemptconfirm'] = "Are you sure that you want to delete this attempt?";
$string['deletenow'] = '';
$string['allowearlyexit'] = 'Can exit early';
$string['allowearlyexit_details'] =
        'If checked students can finish before the time limit, by pressing a finish button. The WPM is calculated using their recording time.';
$string['allowearlyexit_defaultdetails'] =
        'Sets the default setting for allow_early_exit. Can be overriden at the activity level. If true, allow_early_exit means that students can finish before the time limit, by pressing a finish button. The WPM is calculated using their recording time.';
$string['itemsperpage'] = 'Items per page';
$string['itemsperpage_details'] = 'This sets the number of rows to be shown on reports or lists of attempts.';
$string['accuracy'] = 'Accuracy';
$string['accuracy_p'] = 'Acc(%)';
$string['av_accuracy_p'] = 'Av. Acc(%)';
$string['h_accuracy_p'] = 'Max Acc(%)';
$string['mistakes'] = 'Mistakes';
$string['grade'] = 'Grade';
$string['grade_p'] = 'Grade(%)';
$string['av_grade_p'] = 'Av. Grade(%)';
$string['h_grade_p'] = 'Max Grade(%)';
$string['av_wpm'] = 'Av. WPM';
$string['h_wpm'] = 'Max WPM';
$string['targetwpm'] = 'Target WPM';
$string['targetwpm_details'] =
        'The default target WPM. A students grade is calculated for the gradebook using this value as the maximum score. If their WPM score is equal to, or greater than the target WPM, they will score 100%. The target WPM can also be set at the activity instance level. ';
$string['targetwpm_help'] =
        'The target WPM score. A students grade is calculated for the gradebook using this value as the maximum score. If their WPM score is equal to, or greater than the target WPM, they will score 100%.';
$string['passage'] = 'Reading Passage';
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

$string['accadjustmethod'] = 'WPM Adjust(AI)';
$string['accadjustmethod_details'] =
        'Adjust the WPM score by ignoring, or discounting some, reading errors found by AI. The default \'No adjustment\' subtracts all reading errors from final WPM score. ';
$string['accadjustmethod_help'] =
        'For WPM adjustment we can: never adjust, adjust by a fixed amount, or ignore errors when calculating WPM';
$string['accmethod_none'] = 'No adjustment';
$string['accmethod_auto'] = 'Auto audjustment';
$string['accmethod_fixed'] = 'Adjust by fixed amount';
$string['accmethod_noerrors'] = 'Ignore all errors';

$string['apiuser'] = 'Poodll API User ';
$string['apiuser_details'] = 'The Poodll account username that authorises Poodll on this site.';
$string['apisecret'] = 'Poodll API Secret ';
$string['apisecret_details'] =
        'The Poodll API secret. See <a href= "https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret">here</a> for more details';
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

$string['awsregion'] = 'AWS Region';
$string['region'] = 'AWS Region';
$string['awsregion_details']='Choose the region closest to you. Your data will stay within that region. Capetown region only supports English and German.';
$string['expiredays'] = 'Days to keep file';
$string['aigradenow'] = 'AI Grade';

$string['machinegrading'] = 'Machine Evaluations';
$string['viewmachinegrading'] = 'Machine Evaluation';
$string['review'] = 'Review';
$string['regrade'] = 'Regrade';

//$string['dospotcheck'] = "Spot Check";
$string['spotcheckbutton'] = "Spot Check Mode";
$string['gradingbutton'] = "Grading Mode";
$string['transcriptcheckbutton'] = "Transcript Check Mode";
//$string['doaigrade'] = "AI Grade";
$string['doclear'] = "Clear all markers";

$string['gradethisattempt'] = "Grade this attempt";
$string['rawwpm'] = "WPM";
$string['rawaccuracy_p'] = 'Acc(%)';
$string['rawgrade_p'] = 'Grade(%)';
$string['adjustedwpm'] = "Adj. WPM";
$string['adjustedaccuracy_p'] = 'Adj. Acc(%)';
$string['adjustedgrade_p'] = 'Adj. Grade(%)';

$string['evaluationview'] = "Evaluation display";
$string['evaluationview_details'] = "What to show students after they have attempted and received an evaluation";
$string['humanpostattempt'] = "Evaluation display (human)";
$string['humanpostattempt_details'] = "What to show students after they have attempted and received a human evaluation";
$string['machinepostattempt'] = "Evaluation display (machine)";
$string['machinepostattempt_details'] = "What to show students after they have attempted and received a machine evaluation";
$string['postattempt_none'] = "Show the passage. Don't show evaluation or errors.";
$string['postattempt_eval'] = "Show the passage, and evaluation(WPM,Acc,Grade)";
$string['postattempt_evalerrorsnograde'] = "Show the passage, evaluation(WPM, Acc) and errors";
$string['postattempt_evalerrors'] = "Show the passage, evaluation(WPM,Acc,Grade) and errors";


$string['attemptsperpage'] = "Attempts to show per page: ";
$string['backtotop'] = "Check for Results";
$string['transcript'] = "Transcript";
$string['quickgrade'] = "Quick Grade";
$string['ok'] = "OK";
$string['ng'] = "Not OK";
$string['notok'] = "Not OK";
$string['machinegrademethod'] = "Human/Machine Grading";
$string['machinegrademethod_details'] = "Use machine evaluations or human evaluations as grades in grade book.";
$string['machinegrademethod_help'] = "Use machine evaluations or human evaluations as grades in grade book.";
$string['machinegradenone'] = "Never use machine eval. for grade";
$string['machinegradehybrid'] = "Use human or machine eval. for grade";
$string['machinegrademachineonly'] = "Always use machine eval. grade";
$string['admintab'] = "Administrator";
$string['viewadmintab'] = 'View Administrator Tab';
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
//these errors are displayed on activity page
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
$string['passageindex'] = 'Passage Index';
$string['passageword'] = 'Passage Word';
$string['mistranscriptions'] = 'Mistranscriptions';
$string['mistrans_count'] = 'Count';
$string['total_mistranscriptions'] = 'Total mistranscriptions: {$a}';

$string['previewreading'] = 'Listen';
$string['startreading'] = 'Read';
$string['readagain'] = 'Read Again';
$string['startshadowreading'] = 'Shadow Practice';
$string['practicereading'] = 'Practice';


$string['transcriber'] = 'Line Transcriber';
$string['transcriber_details'] = 'The transcription engine to use for line by line reading.';
$string['transcriber_none'] = 'No transcription';
$string['transcriber_guided'] = 'Guided STT (Poodll)';
$string['transcriber_strict'] = 'Open STT (Strict)';

$string['stricttranscribe'] = 'Passage Transcriber';
$string['stricttranscribe_details'] = 'The transcriber to use for full passage readings.';

//$string['submitrawaudio'] = 'Submit uncompressed audio';
//$string['submitrawaudio_details'] = 'Submitting uncompressed audio may increase transcription accuracy, but at the expense of upload speed and reliability.';

$string['sessionscoremethod'] = 'Grade Calculation';
$string['sessionscoremethod_details'] = 'How the value(%) for gradebook is calculated.';
$string['sessionscoremethod_help'] = 'The value(%) for gradebook is calculated as a percentage, either WPM / Target_WPM (normal) or (WPM - Errors)/ Target_WPM (strict)';
$string['sessionscorenormal'] = 'Normal: Total correct words per min / Target_WPM';
$string['sessionscorestrict'] = 'Strict: (Total correct words - errors) per min /Target WPM';
$string['modelaudio'] = 'Model Audio';
$string['ttsvoice'] = 'TTS Voice';
$string['enablepreview'] = 'Enable Listen mode';
$string['enablepreview_details'] = 'Listen mode shows the reading and model audio to student before the activity commences.';
$string['enableshadow'] = 'Enable Practice mode (Shadowing)';
$string['enableshadow_details'] = 'Enables shadowing mode. This plays the model audio as students are read the entire passage aloud. Students will need headphones for this.';
$string['enablelandr'] = 'Enable Practice mode (Listen and Repeat)';
$string['enablelandr_details'] = 'Enables listen and repeat mode. Line by line, the student listens and reads alternately.';
$string['savemodelaudio'] = 'Save Recording';
$string['uploadmodelaudio'] = 'Upload Audio File';
$string['modelaudioclear'] = 'Clear Audio';
$string['modelaudiobreaksgenerate'] = 'Re-Generate Model Audio Markup';
$string['modelaudio_recordinstructions'] = 'Record audio here to be used as the model audio. You can optionally choose to upload audio by pressing the upload audio button. There will be a delay of a few minutes before break point text and audio are automatically synced';
$string['modelaudio_playerinstructions'] = 'The current model audio can be played using the player below.';
$string['modelaudio_breaksinstructions'] = 'Tap words in the passage below to add a break at that point in the audio playback in preview and practice modes. The system will automatically sync the audio and the text. Check <i>manual break timing</i> to set tapped breaks to current location of playing audio.';
$string['modelaudio_recordtitle'] = 'Record Model Audio';
$string['modelaudio_playertitle'] = 'Play Model Audio';
$string['modelaudio_breakstitle'] = 'Mark-up Model Audio';
$string['viewmodeltranscript'] = 'View Model Transcript';

$string['ttsspeed'] = 'TTS Speed';
$string['mediumspeed'] = 'Medium';
$string['slowspeed'] = 'Slow';
$string['extraslowspeed'] = 'Extra Slow';


$string['welcomemenu'] = 'Choose from the options below.';
$string['returnmenu'] = 'Return to Menu';
$string['attemptno'] = 'Attempt {$a}';
$string['progresschart'] = 'Progress Chart';
$string['chartexplainer'] = 'The chart below shows your progress over time in reading this passage.';

$string['previewhelp'] = "Listen to a speaker read the passage aloud. You do not need to read aloud.";
$string['normalhelp'] = "Read the passage aloud. Speak at a speed that is natural for you.";
$string['shadowhelp'] = "Read the passage aloud, along with the teacher. You should wear headphones.";
$string['landrhelp'] = "Listen to the speaker. Repeat after each sentence and check your pronunciation.";
$string['quizhelp'] = "Read the passage silently. Then answer the questions about the passafe.";
$string['playbutton'] = "Play";
$string['recordbutton'] = "Record";
$string['stopbutton'] = "Stop";
$string['taptolisten'] = "Tap to listen";
$string['taptorecord'] = "Tap to record";

$string['returntomenu']="Return to Menu";
$string['fullreport'] = "View Full Report";
$string['fullreportnoeval'] = "View Passage";

$string['nocourseid'] = 'You must specify a course_module ID or an instance ID. Probably your session expired.';

$string['secs_till_check']='Checking for results in: ';
$string['checking']=' ... checking ... ';

$string['recorder']='Audio recorder type';
$string['recorder_help']='Choose the audio recorder type that best suits your students and situation.';
$string['defaultrecorder']='Default recorder';
$string['defaultrecorder_details']='Choose the default recorder to be shown to students. ';
$string['rec_readaloud']='Mic-test then start';
$string['rec_once']='Just start';
$string['rec_upload']='Upload (for devs/admins)';

$string['transcriber_warning']='You have selected instant transcription. Note that this will <strong>only work if passage language and region are correct</strong>.';

$string['close']='Close';
$string['modelaudiowarning']="Model audio not marked up.";
$string['modelaudiobreaksclear']=' Clear model audio markup';
$string['savemodelaudiomarkup']=' Save model audio markup';
$string['enablesetuptab']="Enable setup tab";
$string['enablesetuptab_details']="Show a tab containing the activity instance settings to admins. Not super useful in most cases.";
$string['setup']="Setup";
//$string['failedttsmarkup']='Unable to mark up speech..';
$string['manualbreaktiming']=' Manual break timing';

$string['nopassage']="No Reading Passage";
$string['addpassage']="Setup Activity";
$string['waitforpassage']="There is no reading passage set yet for this activity. You will not be able to do the activity until your teacher adds one";
$string['letsaddpassage']="There is no reading passage set yet for this activity. Lets add one.";

$string['readaloud:itemview'] = 'View questions';
$string['readaloud:itemedit'] = 'Edit questions';

//rsquestions
//$string['durationgradesettings'] = 'Grade Settings ';
//$string['durationboundary']='{$a}: Completion time less than (seconds)';
//$string['boundarygrade']='{$a}: points ';
$string['numeric']='Must be numeric ';
$string['iteminuse']= 'This item is part of users attempt history. It cannot be deleted.';
$string['moveitemup']='Up';
$string['moveitemdown']='Down';

//questions
$string['rsquestions'] ='Questions';
$string['managersquestions'] ='Manage Questions';
$string['correctanswer'] ='Correct answer';
$string['incorrectanswer'] ='Incorrect answer';
$string['whatdonow'] = 'What would you like to do?';
$string['addnewitem'] = 'Add a New question';
$string['addingitem'] = 'Adding a New question';
$string['editingitem'] = 'Editing a question';
$string['addtextpromptshortitem']='Add item';
$string['createaitem'] = 'Create a question';
$string['edit'] = 'Edit';
$string['item'] = 'Item';
$string['itemtitle'] = 'Question Title';
$string['itemcontents'] = 'Question Description';
$string['answer'] = 'Answer';
$string['saveitem'] = 'Save item';
$string['audioitemfile'] = 'item Audio(MP3)';
$string['itemname'] = 'Question Name';
$string['itemorder'] = 'Item Order';
$string['correct'] = 'Correct';
$string['itemtype'] = 'Item Type';
$string['actions'] = 'Actions';
$string['edititem'] = 'Edit item';
$string['previewitem'] = 'Preview item';
$string['deleteitem'] = 'Delete item';
$string['duplicateitem'] = 'Duplicate item';
$string['confirmitemdelete'] = 'Are you sure you want to <i>DELETE</i> item? : {$a}';
$string['confirmitemdeletetitle'] = 'Really Delete item?';
$string['noitems'] = 'This quiz contains no questions';
$string['itemdetails'] = 'item Details: {$a}';
$string['itemsummary'] = 'item Summary: {$a}';
$string['iscorrectlabel'] = 'Correct/Incorrect';
$string['textchoice'] = 'Text Area Choice';
$string['textboxchoice'] = 'Text Box Choice';
$string['audioresponse'] = 'Audio response';
$string['correcttranslationtitle'] = 'Correct Translation';
$string['shuffleanswers'] = 'Shuffle Answers';
$string['shufflequestions'] = 'Shuffle Questions';
$string['correct'] = 'Correct';
$string['avgcorrect'] = 'Av. Correct';
$string['avgtotaltime'] = 'Av. Duration';
$string['nodataavailable'] = 'No data available';
$string['quiz'] = 'Quiz';
$string['waiting']='-- waiting --';
$string['waitingforteacher'] = 'Your teacher will check your reading soon.';
$string['quizcompletedwarning'] = "Quiz completed. Tap to review.";


$string['notmasterinstance']='You can not push settings from this ReadAloud activity unless master instance is checked in activity settings.';
$string['push']='Push';
$string['pushpage']='Push Page';
$string['pushalternatives']='Push Alternatives';
$string['pushalternatives_desc']='Push alternatives field to all clone instances.';
$string['pushalternatives_done']='Alternatives have been pushed';

$string['pushpassage']='Push Passage (and related settings)';
$string['pushpassage_desc']='Push passage and phonetics and segments and other elements that are unique to the passage, to clones. ';
$string['pushpassage_done']='Passage has been pushed';

$string['pushquestions']='Push Questions';
$string['pushquestions_desc']='You could push comprehension questions from here if there were any. They will be implemented soon.';
$string['pushquestions_done']='Questions have been pushed';

$string['pushtargetwpm']='Target WPM';
$string['pushtargetwpm_desc']='Push the Target WPM setting to all clone instances.';
$string['pushtargetwpm_done']='Target WPM has been pushed';

$string['pushtimelimit']='Time Limit';
$string['pushtimelimit_desc']='Push the Time Limit setting to all clone instances.';
$string['pushtimelimit_done']='Time limit has been pushed';

$string['pushcanexitearly']='Can Exit Early';
$string['pushcanexitearly_desc']='Push the \'Can Exit Early\' setting to all clone instances. This setting allows users to exit the activity before the time limit is reached.';
$string['pushcanexitearly_done']='Can Exit Early has been pushed';

$string['pushmodes']='Modes';
$string['pushmodes_desc']='Push the optional activity mode settings (preview, listen and repeat and shadow) from this instance to clone instances.';
$string['pushmodes_done']='Modes have been pushed';

$string['pushgradesettings']='Grade Settings';
$string['pushgradesettings_desc']='Push some of grade settings (completion cond. min grade, grade calculation, human/machine grading, highest/latest attempt) from this instance to clone instances. This wont update the max grade or other settings that affect the gradebook setup nor will it force a regrade of existing attempts. It is best to only use this on not yet attempted clones.';
$string['pushgradesettings_done']='Grade Settings have been pushed';

$string['pushttsmodelaudio']='Push TTS and Model Audio';
$string['pushttsmodelaudio_desc']='Push TTS and Model Audio related settings, this will not push any uploaded/recorded audio. It will push TTS audio and meta data including audio breaks.';
$string['pushttsmodelaudio_done']='TTS and Model Audio have been pushed';

$string['masterinstance']='Master Instance';
$string['masterinstance_details']='Master instance allows the author to push the individual settings of one ReadAloud to existing copies of the same activity. They must have exactly the same name.';

$string['pushpage_explanation']= "Use the buttons on this page to push settings from this ReadAloud instance to clones of it (ie activities with the same name). Be careful there is no going back so be sure of your intention before using.";
$string['pushpage_clonecount']= 'This activity has {$a} clones. <br><br>';
$string['pushpage_noclones']= 'This activity IS a master instance, but there are no other activities with the same name (ie clones). So there is nothing to push settings to. Check that this is the right activity. If you are just testing, duplicate this activity and rename the duplicate the same as this one.<br><br>';


$string['disableshadowgrading'] = "Disable Shadow Mode Grading";
$string['disableshadowgrading_details'] = "If checked, attempts made in shadow mode will be evaluated, but no entry passed to the gradebook.";
//$string['gradeable'] = "Gradeable";
$string['developer'] = "Developer";

$string['freetrial'] = "Get Cloud Poodll API Credentials and a Free Trial";
$string['freetrial_desc'] = "A dialog should appear that allows you to register for a free trial with Poodll. After registering you should login to the members dashboard to get your API user and secret. And to register your site URL.";
//$string['memberdashboard'] = "Member Dashboard";
//$string['memberdashboard_desc'] = "";
$string['fillcredentials']="Set API user and secret with existing credentials";
$string['viewstart']="Activity open";
$string['viewend']="Activity close";
$string['viewstart_help']="If set, prevents a student from entering the activity before the start date/time.";
$string['viewend_help']="If set, prevents a student from entering the activity after the closing date/time.";
$string['activitydate:submissionsdue'] = 'Due:';
$string['activitydate:submissionsopen'] = 'Opens:';
$string['activitydate:submissionsopened'] = 'Opened:';
$string['activityisnotopenyet']="This activity is not open yet.";
$string['activityisclosed']="This activity is closed.";
$string['open']="Open: ";
$string['until']="Until: ";
$string['activityopenscloses']="Activity open/close dates";
$string['nottsvoice']="No TTS Voice";

$string['guidedtranscriptionadmin']= "Guided Transcription Admin";
//$string['show_guidedtranscriptionadmin']= "Guided Transcription Admin";
$string['guidedtrans_corpus']="Use corpus texts";
$string['usecorpus']="Guided Transcription Type";
$string['usecorpuschanged']="Guided Transcription Type Changed";

$string['applysettingsrange']="Apply setting to:";
$string['apply_activity']="this activity";
$string['apply_course']="this course activities";
$string['apply_site']="this site activities";

$string['corpusrange']="Corpus range";
$string['corpusrange_course']="This course";
$string['corpusrange_site']="This site";
$string['guidedtrans_corpus']="Use corpus (all ReadAloud passages)";
$string['guidedtrans_passage']="Use this activity passage";
$string['guidedtransinstructions']="When using guided transcription the transcriber will steer the transcript towards the guide, i.e the words/phrases in this activity's passage, or the words/phrases in the full corpus of ReadAloud passages. Using the full corpus of ReadAloud passages will pick up more reading errors.";
$string['pushcorpus_details']="The course/site corpus will be updated automatically, but you can use the button below to update and push the corpus if you need to. This will generate a guide from the corpus range, and it will set all ReadAloud activities(using guided transcription) within the range to use the guide.";
$string['pushcorpus_button']="Update and push corpus guide";
$string['corpuspushed']="Corpus guide pushed";
$string['passagekey'] = 'Passage Key';
$string['passagekey_details'] =
    'The passage key is just a tag that will be exported to csv with some reports to make post processing those reports in a spreadsheet easier. It is fine to leave it empty.';
$string['passagekey_help'] =
    'The passage key is just a tag that will be exported to csv with some reports to make post processing those reports in a spreadsheet easier.';

$string['courseattemptsreport'] = 'Course Attempts Report';
$string['courseattemptsheading'] = 'Course Attempts Report';
$string['studentid']="St. No.";
$string['studentname']="Student Name";
$string['activityname']="RA. Name.";
$string['errorcount']="No. errors";
$string['activitywords']="No. Words in Passage";
$string['readingtime']="Read Time (secs)";
$string['oralreadingscore']="Oral Reading Score";
$string['oralreadingscore_p'] = 'Oral Reading Score(%)';
$string['reportsmenutoptext']="Review attempts on ReadAloud activities using the reports below.";
$string['courseattempts_explanation']="All the attempts on ReadAloud activities within this course";
$string['attemptssummary_explanation']="A summary of ReadAloud attempts per user in this activity.";

$string['customfont']="Custom font";
$string['customfont_help']="A font name that will override site default for this passage when displayed. Must be exact in spelling and case. eg Andika or Comic Sans MS";
$string['advancedheader']="Advanced";

$string['missedwords']="Missed Words";
$string['missedwordsheading']="Missed Words";
$string['missedwordsreport']="Missed Words";
$string['missedwords_explanation']="The top error words in the most recent attempts";
$string['missed_count'] = "Missed Count";
$string['rank'] = "Rank";

$string['unit_wpm']="words/min";
$string['unit_percent']="percent";
$string['unit_words']="words";

$string['totalwords'] = 'Total Words';
$string['sentences'] = 'Sentences';
$string['uniquewords'] = 'Unique Words';
$string['ideacount'] = 'Concepts';
$string['relevance'] = 'Relevance';
$string['original'] = 'Original';
$string['corrected'] = 'Corrected';

$string['confirm_cancel_recording']="Cancel recording and quit this attempt?";
$string['confirm_read_again']="Cancel this reading and make a new one?";
$string['aitextutilsshow']="Show AI Text Utils (Beta)";
$string['aitextutilshide']="Hide AI Text Utils (Beta)";
$string['textgenerator_instructions']="Enter a short non fiction topic description and press the button to generate a passage. It will often not be factually accurate. Please be careful be using it with students.";
$string['textsimplifier_instructions']="Choose the simplification level and press the button to simplify the passage. The passage will be simplified to the approximate level you choose. ";
$string['article-topic-here']="e.g Pros and cons of social media";
$string['generate-text']="Generate Passage";
$string['simplify-text']="Simplify Passage";
$string['entersomething']="Please enter a topic in order to generate a passage";
$string['text-too-long-100']="Your topic should be no more than 100 characters. Simply describe the topic, don't write a full sentence, or give additional instructions.";
$string['textoverwriteconfirm']="Overwrite Confirmation";
$string['reallyoverwritepassage']="Overwrite the current passage?";
$string['overwrite']="Overwrite";
$string['cancel']="Cancel";
$string['datatables_info']="Showing _START_ to _END_ of _TOTAL_ entries";
$string['datatables_infoempty']="Showing 0 to 0 of 0 entries";
$string['datatables_infofiltered']="(filtered from _MAX_ total entries)";
$string['datatables_infothousands']=",";
$string['datatables_lengthmenu']="Show _MENU_ entries";
$string['datatables_search']="Search:";
$string['datatables_zerorecords']="No matching records found";
$string['datatables_paginate_first']="First";
$string['datatables_paginate_last']="Last";
$string['datatables_paginate_next']="Next";
$string['datatables_paginate_previous']="Previous";
$string['datatables_emptytable']="No data available in table";
$string['datatables_aria_sortascending']="activate to sort column ascending";
$string['datatables_aria_sortdescending']="activate to sort column descending";
$string['one_simplest']="one (simplest)";
$string['two']="two";
$string['three']="three";
$string['four']="four";
$string['five']="five";
$string['passagepicture']='Passage picture';
$string['passagepicture_descr']='*The passage picture is not used yet. It is part of an upcoming feature*';
$string['stdashboardid']='Student Dashboard ID';
$string['stdashboardid_details']='If the student dashboard block is installed, put the id of the block here.';
$string['eventreadaloudattemptsubmitted'] = 'ReadAloud attempt submitted';
$string['bulkdelete'] = 'Delete selected';
$string['bulkdeletequestion'] = 'Are you sure you want to delete the selected question?';
$string['addmultichoiceitem']='Multi Choice';
$string['addmultiaudioitem']='MC Audio';
$string['addpageitem']='Content Page';
$string['addshortansweritem']='Short Answer';
$string['addlisteninggapfillitem']='Listening Gapfill';
$string['addspeakinggapfillitem']='Speaking Gapfill';
$string['addtypinggapfillitem']='Typing Gapfill';
$string['addfreewritingitem']='Free Writing';
$string['addfreespeakingitem']='Free Speaking';
$string['multichoice'] = 'Multi Choice';
$string['multiaudio'] = 'MC Audio';
$string['dictation']='Dictation';
$string['dictationchat']='Dictation Chat';
$string['speechcards']='Speech Cards';
$string['listenrepeat']='Listen and Speak';
$string['page']='Content Page';
$string['smartframe']='SmartFrame';
$string['shortanswer']='Short Answer';
$string['lgapfill']='Listening Gapfill';
$string['sgapfill']='Speaking Gapfill';
$string['tgapfill']='Typing Gapfill';
$string['spacegame']='Space Game';
$string['freewriting']='Free Writing';
$string['freespeaking']='Free Speaking';
$string['fluency']='Fluency';
$string['passagereading']='Passage Reading';
$string['conversation']='Conversation';
$string['transcriber'] = 'Transcriber';
$string['transcriber_details'] = 'The transcription engine to use';
$string['transcriber_auto'] = 'Open STT (Strict)';
$string['transcriber_poodll'] = 'Guided STT (Poodll)';
$string['pagelayout'] = 'Page layout';
$string['thatsnotright'] = 'Something is Wrong';
$string['newitem'] = 'Item: {$a}';

$string['d_question'] = 'Item';
$string['freespeaking_instructions1'] = 'Use the microphone to record your answer to the question.';
$string['freewriting_instructions1'] = 'Type your answer to the question in the text area below.';
$string['lg_instructions1'] = 'Listening GapFill instructions';
$string['sg_instructions1'] = 'Speaking GapFill instructions';
$string['tg_instructions1'] = 'Typing GapFill instructions';
$string['multiaudio_instructions1'] = 'Choose the correct answer. Use the mic to read it aloud.';
$string['multichoice_instructions1'] = 'Choose the correct answer.';
$string['shortanswer_instructions1'] = 'Answer the question by using the mic.';
$string['iteminstructions'] = 'Item instructions';
$string['chooselayout']='Choose layout';
$string['layoutauto']='Auto';
$string['layoutvertical']='Vertical';
$string['layouthorizontal']='Horizontal';
$string['layoutmagazine']='Magazine';
$string['mediaprompts']="Media Prompts";
//media toggles
$string['addmedia'] = 'Image / audio or video';
$string['addmedia_instructions'] = 'Choose the media type you want to show in the lesson item.';
$string['addiframe'] = 'iFrame / custom HTML';
$string['addiframe_instructions'] = 'Paste the embed code for the iframe you want to show in the lesson item.';
$string['addttsaudio'] = 'TTS Audio';
$string['addttsaudio_instructions'] = 'Enter the text you want to be spoken by the TTS engine.';
$string['addtextarea'] = 'Text Block';
$string['addtextarea_instructions'] = 'Enter the text you want to show in the lesson item.';$string["reallydeletemediaprompt"]="Really delete media: ";
$string["deletemediaprompt"]="Delete media?";
$string["choosemediaprompt"]="Choose media type ..";
$string["deletefilesfirst"]="Delete any files you added manually. They will not be deleted automatically.";
$string["cleartextfirst"]="Clear any content you added manually. It will not be deleted automatically.";

$string['itemmedia'] ='Image, audio or video to show';
$string['itemttsquestion'] ='TTS prompt text';
$string['itemttsquestionvoice'] ='TTS prompt speaker';
$string['itemtextarea'] = 'Text Block';

//TTS options
$string['ttsnormal']='Normal';
$string['ttsslow']='Slow';
$string['ttsveryslow']='Very Slow';
$string['ttsssml']='SSML';
$string['choosevoiceoption']='TTS prompt options';
$string['autoplay']='Autoplay';
$string["itemsettingsheadings"]="Item Settings";


$string['enterresponses'] ='Enter a list of correct responses in the text area below. Place each response on a new line.';
$string['correctresponses'] ='Correct responses';
$string['choosevoice'] = "Choose the prompt speaker's voice";
$string['choosemultiaudiovoice'] = "Choose the answer reader's voice";
$string['showoptionsastext'] = 'Show answers as text';
$string['showtextprompt'] = 'Show text prompt';
$string['textprompt_words'] = 'Show full text';
$string['textprompt_dots'] = 'Show dots instead of letters';
$string['listenorread'] = "Display options as";
$string['listenorread_read'] = 'plain text';
$string['listenorread_listen']= 'audio players + dots';
$string['listenorread_listenandread']= 'audio players + plain text';
$string['listenorread_image']= 'images + plain text';
$string['confirmchoice_formlabel']="Must attempt (no skip)";
$string['continue']="Continue <i class='fa fa-arrow-right'></i>";
$string['confirmchoice']="Check";
$string['listeninggapfill'] = 'Listening GapFill';
$string['speakinggapfill'] = 'Speaking GapFill';
$string['typinggapfill'] = 'Typing GapFill';
$string['gapfillitemsdesc'] ='Enter the list of items in the text area below. Each item should be on a new line. The letter gaps should be enclosed in square brackets: [ ].The format is:<br>Text prompt | hint<br>.e.g  This is my d[og]| a common pet';
$string['listeninggapfillitemsdesc'] ='Enter the list of items in the text area below. Each item should be on a new line. The letter gaps should be enclosed in square brackets: [ ]. The format is:<br>Text prompt<br>.e.g  This is my d[og]';
$string['readsentences'] = 'Read Sentences (TTS)';
$string['readsentences_desc'] = 'If checked each sentence will be read aloud. It will be a form of dictation';
$string['allowretry'] = 'Allow retry';
$string['allowretry_desc'] = 'If checked allows students to submit new attempts, if their previous response was not correct.';
$string['hidestartpage'] = 'Hide start page';
$string['hidestartpage_desc'] = 'If checked the activity item begins as soon as it has loaded.';
$string['sentenceprompts'] ='Sentences (prompts)';
$string['relevancetype'] = 'Relevance type';
$string['relevancetype_desc'] = 'AI will penalize answers of low relevance. Choose the type of relevance to use.';
$string['relevancetype_none'] = 'Relevance not considered';
$string['relevancetype_question'] = 'Relevance to the Question (Item Text)';
$string['relevancetype_modelanswer'] = 'Relevance to a Model Answer';
$string['freewritingdesc'] ='Set target word count and grading and feedback guidelines for the AI evaluation. Students should type their answer to the topic, and they will receive an AI powered grade and feedback.';
$string['freespeakingdesc'] ='<b>Free Speaking is a BETA item type.</b> Different browsers and mobile devices may behave differently.<br/><br/> Set target word count and grading and feedback guidelines for the AI evaluation. Students should record themselves speaking on the topic, and they will receive an AI powered grade and feedback.';
$string['freespeaking_default_aigrade']  = 'Deduct 1 point for each grammar mistake but do not penalize for spelling or punctuation errors.';
$string['freespeaking_default_aigradefeedback']  = 'Explain each grammar mistake simply.';
$string['freewriting_default_aigrade'] = 'Deduct 1 point for each grammar, spelling or punctuation error.';
$string['freewriting_default_aigradefeedback']  = 'Explain each mistake simply.';
$string['writehere']  = 'Write here ..';
$string['submit']='Submit';
$string['fs_totalmarks_instructions'] = 'The total marks this free speaking item contributes to the quiz score.';
$string['fw_totalmarks_instructions'] = 'The total marks this free writing item contributes to the quiz score.';
$string['targetwordcount_title'] = 'Target Word Count';
$string['totalmarks'] = 'Total Marks';
$string['aigrade_instructions'] = 'Grading Instructions for AI';
$string['aigrade_feedback'] = 'Feedback Instructions for AI';
$string['aigrade_feedback_language'] = 'AI Feedback Language';
$string["aigrade_feedback_title"]="Feedback";
$string['itemtype']= 'Item Type';
$string['action']= 'Action';
$string['order']= 'Order';
$string['deleteitem'] = 'Delete Item';
$string['deleteitem_message'] = 'Really delete item:&nbsp;';
$string['deletebuttonlabel'] = 'DELETE';
$string['totalscore'] = 'Score';
$string['backtomenu'] = "Back to Top Menu";
$string['reattempttitle'] = "Reattempt Quiz";
$string['reattemptbody'] = "Do you want to reattempt this quiz?";
$string['showquestionscores'] = "Show Question Scores";
$string['questiontext'] = "Question";
$string['questionscore'] = "Score";
$string['check'] = "Check";
$string['skip'] = "Skip";
$string['start'] = "Start";
$string['score'] = "Score";
$string['currentwordcount'] = "Word Count";
$string['showcorrections'] = "Show inline corrections";
$string['hidecorrections'] = "Hide inline corrections";
$string['reallyreattempt'] = 'Your previous attempt will be overwritten. Are you sure you want to try again?';
$string['answerdetails'] = 'Answer Details';
$string['seeanswerdetails'] = 'see details';
$string['notsubmit'] = 'Not Submitted';
$string['notsubmitted'] = 'You have not submitted your answer. Submit now?';
$string['submitnow'] = 'Submit';
$string["allowmicaccess"]="Please allow access to your microphone.";
$string["nomicdetected"]="No microphone detected.";
$string["speechnotrecognized"]="We could not recognize your speech.";
$string['gapfill_results'] = 'Results';
$string['loading'] = 'Loading...';
$string['dc_results'] = 'Results';

$string["quizsettingsheader"]="Quiz settings";
$string["showqtitles"]="Show question titles";
$string["showqtitles_help"]="Show question titles";
$string["showqreview"]="Show quiz review";
$string["showqreview_help"]="Show quiz review";
$string["showquiz"]="Show Quiz";
$string["showquiz_help"]="Show Quiz";
$string["showquiz_none"]="No quiz";
$string['showquiz_passage'] = "Show quiz with passage";
$string['showquiz_nopassage'] = "Show quiz without passage";
$string["qfinishscreen"]="Quiz finish Screen";
$string["qfinishscreen_details"]="When you finish the quiz, you can see a simple screen, a full screen or a custom screen. The custom screen is a page you can design yourself.";
$string["qfinishscreen_help"]="When you finish the quiz, you can see a simple screen, a full screen or a custom screen. The custom screen is a page you can design yourself.";
$string["qfinishscreen_simple"]="Simple";
$string["qfinishscreen_full"]="Full";
$string["qfinishscreen_custom"]="Custom";
$string["qfinishscreencustom"]="Custom finish screen";
$string["qfinishscreencustom_help"]="The custom screen is an advanced feature, that allows you to build a custom finish screen using mustache notation and variables. Some of the variables are: {total} {courseurl} {coursename} {yellowstars} {graystars} {reattempturl} and an array of {results} each with {title}, {grade}, {yellowstars} and {graystars} variables.";
$string['qfinishscreencustom_details'] = "If the quiz finish screen options are set to 'custom' this will be the default mustache template that generates the finish screen. It can be overridden at the quiz level.";

// Modes.
$string['home'] = 'Home';
$string['mode_listen'] = 'Listen';
$string['mode_practice'] = 'Practice';
$string['mode_quiz'] = 'Quiz';
$string['mode_read'] = 'Read';
$string['mode_shadow'] = 'Shadow';
$string['mode_report'] = 'Report';
$string['mode_listenandrepeat'] = 'Listen and Repeat';
$string['mode_tooltip_notcomplete'] = 'Next: {{a}}'; // Adds the next mode name.
$string['mode_tooltip_end'] = 'End';

$string['next'] = 'Next';
$string['practiceiconalt'] = 'Practice';
$string['prev'] = 'Prev';
$string['taptospeak'] = 'Tap to speak';

$string['enablenativelanguage'] = "Enable Native Language";
$string['enablenativelanguage_details'] = 'If set, the student can choose their native language, this will override the default feedback language that AI returns with the quiz free writing and free speaking results. The language must currently be <a href="https://support.poodll.com/en/support/solutions/articles/19000163890-definitions-in-user-s-native-language">set in Poodll WordCards</a>.';
$string['letsadditems'] ='Lets add some questions!';
$string['additems'] ='Add quiz questions';
$string['numberonly'] = 'Numbers only';
$string['aigrade_modelanswer'] = 'Model answer';
$string['reattemptquiz'] = 'Reattempt Quiz';
$string['enableread'] = 'Enable Read';
$string['enablequiz'] = 'Enable Quiz';
$string['activitysteps'] = 'Activity Steps';
$string['activitystepsdetails'] = 'Set the learning steps in this ReadAloud activity.';
$string['alternatestreaming'] = 'Enable alternate streaming';
$string['alternatestreaming_details'] = 'Streams recorded audio for open transcription. Slightly slower then the default browser transcription and only works in English. On by default in mobile app.';
$string['cloudpoodllserver'] = 'Cloud Poodll Server';
$string['cloudpoodllserver_details'] = 'The server to use for Cloud Poodll. Only change this if Poodll has provided a different one.';


$string['almost'] = 'Almost...';
$string['almost_desc'] = 'You mispronounced some words. Would you like to try again or continue?';
$string['continue'] = 'Continue';
$string['imready'] = "I'm Ready";
$string['incorrect'] = 'Incorrect';
$string['incorrect_desc'] = "You did not say that correctly. Would you like to try again or continue?";
$string['keeppracticing'] = 'Keep Practicing';
$string['nextsentence'] = 'Next Sentence';
$string['noquestions'] = 'There are no questions to show.';
$string['practicecomplete'] = 'Superb you completed the practice session!';
$string['practicecomplete_desc'] = 'It looks like you are ready to read the full passage.';
$string['question'] = 'Question?';
$string['questions'] = 'Questions';
$string['quizresults'] = 'Quiz Results';
$string['readingpassage'] = 'Reading Passage';
$string['tryagain'] = 'Try Again';
$string['viewfinalreport'] = 'View Final Report';
$string['viewfinalreportintro'] = 'Your complete results and progress summary.';
$string['welldone'] = 'Well Done!';
$string['welldone_desc'] = 'You pronounced all of the words correctly!';

