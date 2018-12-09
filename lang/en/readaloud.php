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

$string['modulename'] = 'Read Aloud';
$string['modulenameplural'] = 'Read Alouds';
$string['modulename_help'] = 'ReadAloud is an activity designed to assist teachers in evaluating their students reading fluency. Students read a passage, set by the teacher, into a microphone. Later the teacher can mark words as incorrect and get the student WCPM(Words Correct Per Minute) scores.';
$string['readaloudfieldset'] = 'Custom example fieldset';
$string['readaloudname'] = 'Read Aloud';
$string['readaloudname_help'] = 'This is the content of the help tooltip associated with the readaloudname field. Markdown syntax is supported.';
$string['readaloud'] = 'readaloud';
$string['activitylink'] = 'Link to next activity';
$string['activitylink_help'] = 'To provide a link after the attempt to another activity in the course, select the activity from the dropdown list.';
$string['activitylinkname'] = 'Continue to next activity: {$a}';
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
$string['machinegradingheading']='Machine evaluated latest attempt for each user.';
$string['gradingbyuserheading']='Grading all attempts for: {$a}';
$string['machinegradingbyuserheading']='Machine evaluated attempts for: {$a}';
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
$string['showingmachinegradedattempt']='Machine evaluated attempt for: {$a}';
$string['basicreport']='Basic Report';
$string['returntoreports']='Return to Reports';
$string['returntogradinghome']='Return to Grading Top';
$string['returntomachinegradinghome']='Return to Machine Evaluations Top';
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
$string['alternatives']='Alternatives';
$string['defaultwelcome'] = 'To begin the activity first test your microphone. When we can hear sound from your microphone a start button will appear. After you press the start button, a reading passage will appear. Read the passage aloud as clearly as you can.';
$string['defaultfeedback'] = 'Thanks for reading. Please be patient until your attempt has been evaluated.';
$string['timelimit'] = 'Time Limit';
$string['gotnosound'] = 'We could not hear you. Please check the permissions and settings for microphone and try again.';
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
$string['notgradedyet'] = 'Your submission has been received, but has not been graded yet';
$string['enabletts'] = 'Enable TTS(experimental)';
$string['enabletts_details'] = 'TTS is currently not implemented';
//we hijacked this setting for both TTS STT .... bad ... but they are always the same aren't they?
$string['ttslanguage'] = 'Passage Language';
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

$string['accadjust']='Fixed adjustment.';
$string['accadjust_details']='This is the number of reading errors to compensate WPM scores for. If WPM adjust is set to "Fixed" then this value will be used to compensate WPM acores. This is a method of mitigating for machine transcription mistakes.';
$string['accadjust_help']='This rate should correspond as closely as possible to the estimated machine transcription mistake average for a passage.';

$string['accadjustmethod']='WPM Adjust(AI)';
$string['accadjustmethod_details']='Adjust the WPM score by ignoring, or discounting some, reading errors found by AI. The default \'No adjustment\' subtracts all reading errors from final WPM score. ';
$string['accadjustmethod_help']='For WPM adjustment we can: never adjust, adjust by a fixed amount, or ignore errors when calculating WPM';
$string['accmethod_none']='No adjustment';
$string['accmethod_auto']='Auto audjustment';
$string['accmethod_fixed']='Adjust by fixed amount';
$string['accmethod_noerrors']='Ignore all errors';

$string['apiuser']='Poodll API User ';
$string['apiuser_details']='The Poodll account username that authorises Poodll on this site.';
$string['apisecret']='Poodll API Secret ';
$string['apisecret_details']='The Poodll API secret. See <a href= "https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret">here</a> for more details';
$string['enableai']='Enable AI';
$string['enableai_details']='Read Aloud can evaluate results from a student attempt using AI. Check to enable.';


$string['useast1']='US East';
$string['tokyo']='Tokyo, Japan (no AI)';
$string['sydney']='Sydney, Australia';
$string['dublin']='Dublin, Ireland';
$string['ottawa']='Ottawa, Canada (slow)';
$string['frankfurt']='Frankfurt, Germany (no AI)';
$string['london']='London, U.K (no AI)';
$string['saopaulo']='Sao Paulo, Brazil (no AI)';
$string['forever']='Never expire';
$string['en-us']='English (US)';
$string['es-us']='Spanish (US)';
$string['en-au']='English (Aus.)';
$string['en-uk']='English (UK)';
$string['fr-ca']='French (Can.)';
$string['awsregion']='AWS Region';
$string['region']='AWS Region';
$string['expiredays']='Days to keep file';
$string['aigradenow']='AI Grade';

$string['machinegrading']='Machine Evaluations';
$string['viewmachinegrading']='Machine Evaluation';
$string['review']='Review';
$string['regrade']='Regrade';


$string['humanevaluatedmessage']='Your latest attempt has been graded by your teacher and results are displayed below.';
$string['machineevaluatedmessage']='Your latest attempt has been graded <i>automatically</i> and results are displayed below.';

$string['dospotcheck']="Spot Check";
$string['spotcheckbutton']="Spot Check Mode";
$string['gradingbutton']="Grading Mode";
$string['transcriptcheckbutton']="Transcript Check Mode";
$string['doaigrade']="AI Grade";
$string['doclear']="Clear all markers";

$string['gradethisattempt']="Grade this attempt";
$string['rawwpm']= "WPM";
$string['rawaccuracy_p']='Acc(%)';
$string['rawgrade_p']='Grade(%)';
$string['adjustedwpm']= "Adj. WPM";
$string['adjustedaccuracy_p']='Adj. Acc(%)';
$string['adjustedgrade_p']='Adj. Grade(%)';

$string['evaluationview']="Evaluation display";
$string['evaluationview_details']="What to show students after they have attempted and received an evaluation";
$string['humanpostattempt']="Evaluation display (human)";
$string['humanpostattempt_details']="What to show students after they have attempted and received a human evaluation";
$string['machinepostattempt']="Evaluation display (machine)";
$string['machinepostattempt_details']="What to show students after they have attempted and received a machine evaluation";
$string['postattempt_none']="Show the passage. Don't show evaluation or errors.";
$string['postattempt_eval']="Show the passage, and evaluation(scores)";
$string['postattempt_evalerrors']="Show the passage, evaluation(scores) and errors";
$string['attemptsperpage']="Attempts to show per page: ";
$string['backtotop']="Back to Start";
$string['transcript']="Transcript";
$string['quickgrade']="Quick Grade";
$string['ok']="OK";
$string['ng']="Not OK";
$string['notok']="Not OK";
$string['machinegrademethod']="Human/Machine Grading";
$string['machinegrademethod_help']="Use machine evaluations or human evaluations as grades in grade book.";
$string['machinegradenone']="Never use machine eval. for grade";
$string['machinegrademachine']="Use human or machine eval. for grade";
$string['gradesadmin']="Machine Grades Admin";
$string['viewgradesadmin']='Grades Admin';
$string['machineregradeall']='Re machine evaluate all attempts';
$string['pushmachinegrades']='Push machine evaluations to gradebook';
$string['currenterrorestimate']='Current error estimate: {$a}';
$string['gradesadmintitle']='Machine Grades Administration';
$string['gradesadmininstructions']='On this page you can re-evaluate all the machine evaluations, which is a good thing to do if you have altered the alternatives for this passage. If you have enabled machine grading you can also push the adjusted grades to the gradebook.';

$string['noattemptsregrade']='No attempts to regrade';
$string['machineregraded']='Successfully regraded {$a->done} attempts. Skipped {$a->skipped} attempts.';
$string['machinegradespushed']='Successfully pushed grades to gradebook';

$string['notimelimit']='No time limit';
$string['xsecs']='{$a} seconds';
$string['onemin']='1 minute';
$string['xmins']='{$a} minutes';
$string['oneminxsecs']='1 minutes {$a} seconds';
$string['xminsecs']='{$a->minutes} minutes {$a->seconds} seconds';

$string['postattemptheader']='Post attempt options';
$string['recordingaiheader']='Recording and AI options';

$string['grader']='Graded by';
$string['grader_ai']='AI';
$string['grader_human']='Human';
$string['grader_ungraded']='Ungraded';

$string['displaysubs'] = '{$a->subscriptionname} : expires {$a->expiredate}';
$string['noapiuser'] = "No API user entered. Read Aloud will not work correctly.";
$string['noapisecret'] = "No API secret entered. Read Aloud will not work correctly.";
$string['credentialsinvalid'] = "The API user and secret entered could not be used to get access. Please check them.";
$string['appauthorised']= "Poodll Read Aloud is authorised for this site.";
$string['appnotauthorised']= "Poodll Read Aloud is NOT authorised for this site.";
$string['refreshtoken']= "Refresh license information";
$string['notokenincache']= "Refresh to see license information. Contact Poodll support if there is a problem.";

$string['privacy:metadata:attemptid']='The unique identifier of a users Read aloud attempt.';
$string['privacy:metadata:readaloudid']='The unique identifier of a Read Aloud activity instance.';
$string['privacy:metadata:userid']='The user id for the Read Aloud attempt';
$string['privacy:metadata:filename']='File urls of submitted recordings.';
$string['privacy:metadata:wpm']='The Words Per Minute score for the attempt';
$string['privacy:metadata:accuracy']='The accuracy score for the attempt';
$string['privacy:metadata:sessionscore']='The session score for the attempt';
$string['privacy:metadata:sessiontime']='The session time(recording time) for the attempt';
$string['privacy:metadata:sessionerrors']='The reading errors for the attempt';
$string['privacy:metadata:sessionendword']='The position of last word for the attempt';
$string['privacy:metadata:errorcount']='The reading error count for the attempt';
$string['privacy:metadata:timemodified']='The last time attempt was modified for the attempt';
$string['privacy:metadata:attempttable']='Stores the scores and other user data associated with a read aloud attempt.';
$string['privacy:metadata:aitable']='Stores the scores and other user data associated with a read aloud attempt as evaluated by machine.';
$string['privacy:metadata:transcriptpurpose']='The recording short transcripts.';
$string['privacy:metadata:fulltranscriptpurpose']='The full transcripts of recordings.';
$string['privacy:metadata:cloudpoodllcom:userid']='The ReadAloud plugin includes the moodle userid in the urls of recordings and transcripts';
$string['privacy:metadata:cloudpoodllcom']='The ReadAloud plugin stores recordings in AWS S3 buckets via cloud.poodll.com.';