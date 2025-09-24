<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/16
 * Time: 19:31
 */

namespace mod_readaloud;

defined('MOODLE_INTERNAL') || die();

class constants {
    //component name, db tables, things that define app
    const M_COMPONENT = 'mod_readaloud';
    const M_DEFAULT_CLOUDPOODLL = 'cloud.poodll.com';
    const M_FILEAREA_SUBMISSIONS = 'submission';
    const M_TABLE = 'readaloud';
    const M_USERTABLE = 'readaloud_attempt';
    const M_AITABLE = 'readaloud_ai_result';
    const M_QTABLE = 'readaloud_rsquestions';
    const M_AUTHTABLE = 'readaloud_auth';
    const M_MODNAME = 'readaloud';
    const M_URL = '/mod/readaloud';
    const M_PATH = '/mod/readaloud';

    const M_PLUGINSETTINGS = '/admin/settings.php?section=modsettingreadaloud';

    const M_NEURALVOICES = array("Amy","Emma","Brian","Arthur","Olivia","Aria","Ayanda","Ivy","Joanna","Kendra","Kimberly",
        "Salli","Joey","Justin","Kevin","Matthew","Camila","Lupe","Pedro", "Gabrielle", "Vicki", "Seoyeon","Takumi", "Lucia",
        "Lea","Remi","Bianca","Laura","Kajal","Suvi","Liam","Daniel","Hannah","Camila","Ida","Kazuha","Tomoko","Elin","Hala","Zayd","Lisa");

    const M_WHISPERVOICES = array('en-US-Whisper-alloy'=>'Ricky','en-US-Whisper-onyx'=>'Ed',
        'en-US-Whisper-nova'=>'Tiffany','en-US-Whisper-shimmer'=>'Tammy',
        'ms-MY-Whisper-alloy'=>'Afsah','ms-MY-Whisper-shimmer',
        'mi-NZ-Whisper-alloy'=>'Tane','mi-NZ-Whisper-shimmer'=>'Aroha',
        'hr-HR-Whisper-alloy'=>'Marko','hr-HR-Whisper-shimmer'=>'Ivana',
        'sl-SI-Whisper-alloy'=>'Vid','sl-SI-Whisper-shimmer'=>'Pia',
        'mk-MK-Whisper-alloy'=>'Trajko','mk-MK-Whisper-shimmer'=>'Marija');

    const ALL_VOICES_NINGXIA = array(
        constants::M_LANG_ARAE => ['Hala'=>'Hala','Zayd'=>'Zayd'],
        constants::M_LANG_ARSA => ['Zeina'=>'Zeina'],
        constants::M_LANG_ZHCN => ['Zhiyu'=>'Zhiyu'],
        constants::M_LANG_DADK => ['Naja'=>'Naja','Mads'=>'Mads'],
        constants::M_LANG_NLNL => ["Ruben"=>"Ruben","Lotte"=>"Lotte","Laura"=>"Laura"],
        constants::M_LANG_NLBE => ["Lisa"=>"Lisa"],
        constants::M_LANG_ENUS => ['Joey'=>'Joey','Justin'=>'Justin','Matthew'=>'Matthew','Ivy'=>'Ivy',
            'Joanna'=>'Joanna','Kendra'=>'Kendra','Kimberly'=>'Kimberly','Salli'=>'Salli'],
        constants::M_LANG_ENGB => ['Brian'=>'Brian','Amy'=>'Amy', 'Emma'=>'Emma'],
        constants::M_LANG_ENAU => ['Russell'=>'Russell','Nicole'=>'Nicole'],
        constants::M_LANG_ENIN => ['Aditi'=>'Aditi', 'Raveena'=>'Raveena'],
        constants::M_LANG_ENWL => ["Geraint"=>"Geraint"],
        constants::M_LANG_FIFI => ['Suvi'=>'Suvi'],
        constants::M_LANG_FRCA => ['Chantal'=>'Chantal'],
        constants::M_LANG_FRFR => ['Mathieu'=>'Mathieu','Celine'=>'Celine', 'Lea'=>'Lea'],
        constants::M_LANG_DEDE => ['Hans'=>'Hans','Marlene'=>'Marlene', 'Vicki'=>'Vicki'],
        constants::M_LANG_DEAT => ['Hannah'=>'Hannah'],
        constants::M_LANG_HIIN => ["Aditi"=>"Aditi"],
        constants::M_LANG_ISIS => ['Dora' => 'Dora', 'Karl' => 'Karl'],
        constants::M_LANG_ITIT => ['Carla'=>'Carla',  'Bianca'=>'Bianca', 'Giorgio'=>'Giorgio'],
        constants::M_LANG_JAJP => ['Takumi'=>'Takumi','Mizuki'=>'Mizuki'],
        constants::M_LANG_KOKR => ['Seoyeon'=>'Seoyeon'],
        constants::M_LANG_NONO => ['Liv'=>'Liv'],
        constants::M_LANG_PLPL => ['Ewa'=>'Ewa','Maja'=>'Maja','Jacek'=>'Jacek','Jan'=>'Jan'],
        constants::M_LANG_PTBR => ['Ricardo'=>'Ricardo', 'Vitoria'=>'Vitoria','Camila'=>'Camila'],
        constants::M_LANG_PTPT => ["Ines"=>"Ines",'Cristiano'=>'Cristiano'],
        constants::M_LANG_RORO => ['Carmen'=>'Carmen'],
        constants::M_LANG_RURU => ["Tatyana"=>"Tatyana","Maxim"=>"Maxim"],
        constants::M_LANG_ESUS => ['Miguel'=>'Miguel','Penelope'=>'Penelope','Lupe'=>'Lupe','Pedro'=>'Pedro'],
        constants::M_LANG_ESES => [ 'Enrique'=>'Enrique', 'Conchita'=>'Conchita', 'Lucia'=>'Lucia'],
        constants::M_LANG_SVSE => ['Astrid'=>'Astrid'],
        constants::M_LANG_SOSO => ['so-SO-Azure-UbaxNeural'=>'Ubax_a','so-SO-Azure-MuuseNeural'=>'Muuse_a'],
        constants::M_LANG_TRTR => ['Filiz'=>'Filiz'],
    );
    const ALL_VOICES = array(
        constants::M_LANG_ARAE => ['Hala'=>'Hala','Zayd'=>'Zayd'],
        constants::M_LANG_ARSA => ['Zeina'=>'Zeina','ar-XA-Wavenet-B'=>'Amir_g','ar-XA-Wavenet-A'=>'Salma_g','ar-MA-Azure-JamalNeural'=>'Jamal_a','ar-MA-Azure-MounaNeural'=>'Mouna_a'],
        constants::M_LANG_BGBG => ['bg-BG-Standard-A' => 'Mila_g'],//nikolai
        constants::M_LANG_HRHR => ['hr-HR-Whisper-alloy'=>'Marko','hr-HR-Whisper-shimmer'=>'Ivana'],
        constants::M_LANG_ZHCN => ['Zhiyu'=>'Zhiyu'],
        constants::M_LANG_CSCZ => ['cs-CZ-Wavenet-A' => 'Zuzana_g', 'cs-CZ-Standard-A' => 'Karolina_g'],
        constants::M_LANG_DADK => ['Naja'=>'Naja','Mads'=>'Mads'],
        constants::M_LANG_NLNL => ["Ruben"=>"Ruben","Lotte"=>"Lotte","Laura"=>"Laura"],
        constants::M_LANG_NLBE => ["nl-BE-Wavenet-B"=>"Marc_g","nl-BE-Wavenet-A"=>"Marie_g","Lisa"=>"Lisa"],
        //constants::M_LANG_DECH => [],
        constants::M_LANG_ENUS => ['Joey'=>'Joey','Justin'=>'Justin','Kevin'=>'Kevin','Matthew'=>'Matthew','Ivy'=>'Ivy',
            'Joanna'=>'Joanna','Kendra'=>'Kendra','Kimberly'=>'Kimberly','Salli'=>'Salli',
            'en-US-Whisper-alloy'=>'Ricky','en-US-Whisper-onyx'=>'Ed','en-US-Whisper-nova'=>'Tiffany','en-US-Whisper-shimmer'=>'Tammy'],
        constants::M_LANG_ENGB => ['Brian'=>'Brian','Amy'=>'Amy', 'Emma'=>'Emma','Arthur'=>'Arthur'],
        constants::M_LANG_ENAU => ['Russell'=>'Russell','Nicole'=>'Nicole','Olivia'=>'Olivia'],
        constants::M_LANG_ENNZ => ['Aria'=>'Aria'],
        constants::M_LANG_ENZA => ['Ayanda'=>'Ayanda'],
        constants::M_LANG_ENIN => ['Aditi'=>'Aditi', 'Raveena'=>'Raveena', 'Kajal'=>'Kajal'],
        // constants::M_LANG_ENIE => [],
        constants::M_LANG_ENWL => ["Geraint"=>"Geraint"],
        // constants::M_LANG_ENAB => [],

        //constants::M_LANG_FAIR => [],
        constants::M_LANG_FILPH => ['fil-PH-Wavenet-A'=>'Darna_g','fil-PH-Wavenet-B'=>'Reyna_g','fil-PH-Wavenet-C'=>'Bayani_g','fil-PH-Wavenet-D'=>'Ernesto_g'],
        constants::M_LANG_FIFI => ['Suvi'=>'Suvi','fi-FI-Wavenet-A'=>'Kaarina_g'],
        constants::M_LANG_FRCA => ['Chantal'=>'Chantal', 'Gabrielle'=>'Gabrielle','Liam'=>'Liam'],
        constants::M_LANG_FRFR => ['Mathieu'=>'Mathieu','Celine'=>'Celine', 'Lea'=>'Lea', 'Remi'=>'Remi'],
        constants::M_LANG_DEDE => ['Hans'=>'Hans','Marlene'=>'Marlene', 'Vicki'=>'Vicki','Daniel'=>'Daniel'],
        constants::M_LANG_DEAT => ['Hannah'=>'Hannah'],
        constants::M_LANG_ELGR => ['el-GR-Wavenet-A' => 'Sophia_g', 'el-GR-Standard-A' => 'Isabella_g'],
        constants::M_LANG_HIIN => ["Aditi"=>"Aditi"],
        constants::M_LANG_HEIL => ['he-IL-Wavenet-A'=>'Sarah_g','he-IL-Wavenet-B'=>'Noah_g'],
        constants::M_LANG_HUHU => ['hu-HU-Wavenet-A'=>'Eszter_g'],

        constants::M_LANG_IDID => ['id-ID-Wavenet-A'=>'Guntur_g','id-ID-Wavenet-B'=>'Bhoomik_g'],
        constants::M_LANG_ISIS => ['Dora' => 'Dora', 'Karl' => 'Karl'],
        constants::M_LANG_ITIT => ['Carla'=>'Carla',  'Bianca'=>'Bianca', 'Giorgio'=>'Giorgio'],
        constants::M_LANG_JAJP => ['Takumi'=>'Takumi','Mizuki'=>'Mizuki','Kazuha'=>'Kazuha','Tomoko'=>'Tomoko'],
        constants::M_LANG_KOKR => ['Seoyeon'=>'Seoyeon'],
        constants::M_LANG_LVLV => ['lv-LV-Standard-A' => 'Janis_g'],
        constants::M_LANG_LTLT => ['lt-LT-Standard-A' => 'Matas_g'],
        constants::M_LANG_MINZ => ['mi-NZ-Whisper-alloy'=>'Tane','mi-NZ-Whisper-shimmer'=>'Aroha'],
        constants::M_LANG_MKMK => ['mk-MK-Whisper-alloy'=>'Trajko','mk-MK-Whisper-shimmer'=>'Marija'],
        constants::M_LANG_MSMY => ['ms-MY-Whisper-alloy'=>'Afsah','ms-MY-Whisper-shimmer'=>'Siti'],
        constants::M_LANG_NONO => ['Liv'=>'Liv','Ida'=>'Ida','nb-NO-Wavenet-B'=>'Lars_g','nb-NO-Wavenet-A'=>'Hedda_g','nb-NO-Wavenet-D'=>'Anders_g'],
        constants::M_LANG_PSAF => ['ps-AF-Azure-GulNawazNeural'=>'GulNawaz_a','ps-AF-Azure-LatifaNeural'=>'Latifa_a'],
        constants::M_LANG_FAIR => ['fa-IR-Azure-FaridNeural'=>'Farid_a', 'fa-IR-Azure-DilaraNeural'=>'Dilara_a'],
        constants::M_LANG_PLPL => ['Ewa'=>'Ewa','Maja'=>'Maja','Jacek'=>'Jacek','Jan'=>'Jan'],
        constants::M_LANG_PTBR => ['Ricardo'=>'Ricardo', 'Vitoria'=>'Vitoria','Camila'=>'Camila'],
        constants::M_LANG_PTPT => ["Ines"=>"Ines",'Cristiano'=>'Cristiano'],
        constants::M_LANG_RORO => ['Carmen'=>'Carmen','ro-RO-Wavenet-A'=>'Sorina_g'],
        constants::M_LANG_RURU => ["Tatyana"=>"Tatyana","Maxim"=>"Maxim"],
        constants::M_LANG_ESUS => ['Miguel'=>'Miguel','Penelope'=>'Penelope','Lupe'=>'Lupe','Pedro'=>'Pedro'],
        constants::M_LANG_ESES => [ 'Enrique'=>'Enrique', 'Conchita'=>'Conchita', 'Lucia'=>'Lucia'],
        constants::M_LANG_SVSE => ['Astrid'=>'Astrid','Elin'=>'Elin'],
        constants::M_LANG_SKSK => ['sk-SK-Wavenet-A' => 'Laura_g', 'sk-SK-Standard-A' => 'Natalia_g'],
        constants::M_LANG_SLSI => ['sl-SI-Whisper-alloy'=>'Vid','sl-SI-Whisper-shimmer'=>'Pia'],
        constants::M_LANG_SOSO => ['so-SO-Azure-UbaxNeural'=>'Ubax_a','so-SO-Azure-MuuseNeural'=>'Muuse_a'],
        constants::M_LANG_SRRS => ['sr-RS-Standard-A' => 'Milena_g'],
        constants::M_LANG_TAIN => ['ta-IN-Wavenet-A'=>'Dyuthi_g','ta-IN-Wavenet-B'=>'Bhoomik_g'],
        constants::M_LANG_TEIN => ['te-IN-Standard-A'=>'Anandi_g','te-IN-Standard-B'=>'Kai_g'],
        constants::M_LANG_TRTR => ['Filiz'=>'Filiz'],
        constants::M_LANG_UKUA => ['uk-UA-Wavenet-A'=>'Katya_g'],
        constants::M_LANG_VIVN => ['vi-VN-Wavenet-A'=>'Huyen_g','vi-VN-Wavenet-B'=>'Duy_g'],
    );

    //classes for use in CSS
    const M_CLASS = 'mod_readaloud';

    //Guided transcription uses the passage or a combination of passages (corpus)
    const GUIDEDTRANS_PASSAGE = 0;
    const GUIDEDTRANS_CORPUS = 1;
    //corpus (combination of packages) covers the whole site or just the course
    const CORPUSRANGE_SITE = 0;
    const CORPUSRANGE_COURSE = 1;
    //when pushing a setting, apply it activity, course or site wide
    const APPLY_ACTIVITY = 0;
    const APPLY_COURSE = 1;
    const APPLY_SITE = 2;



    //audio recorders
    const REC_READALOUD = 'readaloud';
    const REC_ONCE = 'once';
    const REC_UPLOAD = 'upload';

    //Constants for RS Questions
    const NONE=0;
    const MAXANSWERS=4;
    const TEXTQUESTION = 'itemtext';
    const TEXTANSWER = 'customtext';
    const FILEANSWER = 'customfile';
    const TEXTQUESTION_FILEAREA = 'itemarea';
    const TEXTANSWER_FILEAREA ='answerarea';
    const TEXTPROMPT_FILEAREA = 'textitem';
    const TYPE_TEXTPROMPT_LONG = 4;
    const TYPE_TEXTPROMPT_SHORT = 5;
    const TYPE_TEXTPROMPT_AUDIO = 6;
    const TYPE_INSTRUCTIONS = 7;
    const TEXTCHOICE = 'textchoice';
    const TEXTBOXCHOICE = 'textboxchoice';
    const CORRECTANSWER = 'correctanswer';
    const PASSAGEPICTURE='passagepicture';
    const PASSAGEPICTURE_FILEAREA = 'passagepicture';


    //grading options
    const M_GRADEHIGHEST = 0;
    const M_GRADELOWEST = 1;
    const M_GRADELATEST = 2;
    const M_GRADEAVERAGE = 3;
    const M_GRADENONE = 4;
    //accuracy adjustment method options
    const ACCMETHOD_NONE = 0;
    const ACCMETHOD_AUTO = 1;
    const ACCMETHOD_FIXED = 2;
    const ACCMETHOD_NOERRORS = 3;
    //what to display to user when reviewing activity options
    const POSTATTEMPT_NONE = 0;
    const POSTATTEMPT_EVAL = 1;
    const POSTATTEMPT_EVALERRORS = 2;
    const POSTATTEMPT_EVALERRORSNOGRADE = 3;
    //more review mode options
    const REVIEWMODE_NONE = 0;
    const REVIEWMODE_MACHINE = 1;
    const REVIEWMODE_HUMAN = 2;
    const REVIEWMODE_SCORESONLY = 3;
    //to use or not use machine grades
    const MACHINEGRADE_NONE = 0;
    const MACHINEGRADE_HYBRID = 1;
    const MACHINEGRADE_MACHINEONLY = 2;

    //Session Score
    const SESSIONSCORE_NORMAL = 0; //Normal = WPM / Targetwpm * 100
    const SESSIONSCORE_STRICT = 1; //Strict = (WPM - Errors) / Targetwpm * 100

    //TTS Speed
    const TTSSPEED_MEDIUM = 0;
    const TTSSPEED_SLOW = 1;
    const TTSSPEED_XSLOW = 2;

    //CSS ids/classes
    const M_RECORD_BUTTON = 'mod_readaloud_record_button';
    const M_START_BUTTON = 'mod_readaloud_start_button';
    const M_UPDATE_CONTROL = 'mod_readaloud_update_control';
    const M_DRAFT_CONTROL = 'mod_readaloud_draft_control';
    const M_PROGRESS_CONTAINER = 'mod_readaloud_progress_cont';
    const M_HIDER = 'mod_readaloud_hider';
    const M_STOP_BUTTON = 'mod_readaloud_stop_button';
    const M_WHERETONEXT_CONTAINER = 'mod_readaloud_wheretonext_cont';
    const M_RECORD_BUTTON_CONTAINER = 'mod_readaloud_record_button_cont';
    const M_START_BUTTON_CONTAINER = 'mod_readaloud_start_button_cont';
    const M_STOP_BUTTON_CONTAINER = 'mod_readaloud_stop_button_cont';
    const M_RECORDERID = 'therecorderid';
    const M_RECORDING_CONTAINER = 'mod_readaloud_recording_cont';
    const M_RECORDER_CONTAINER = 'mod_readaloud_recorder_cont';
    const M_DUMMY_RECORDER = 'mod_readaloud_dummy_recorder';
    const M_RECORDER_INSTRUCTIONS_RIGHT = 'mod_readaloud_recorder_instr_right';
    const M_RECORDER_INSTRUCTIONS_LEFT = 'mod_readaloud_recorder_instr_left';
    const M_INSTRUCTIONS_CONTAINER = 'mod_readaloud_instructions_cont';
    const M_INSTRUCTIONS = 'mod_readaloud_instructions';
    const M_MSV_MODE = 'mod_readaloud_msvmode';
    const M_ACTIVITYINSTRUCTIONS_CONTAINER = 'mod_readaloud_activityinstructions_const';
    const M_MENUINSTRUCTIONS_CONTAINER = 'mod_readaloud_menuinstructions_const';
    const M_MENUBUTTONS_CONTAINER = 'mod_readaloud_menubuttons_cont';
    const M_PREVIEWINSTRUCTIONS_CONTAINER = 'mod_readaloud_previewinstructions_cont';
    const M_PREVIEWINSTRUCTIONS = 'mod_readaloud_previewinstructions';
    const M_PRACTICEINSTRUCTIONS_CONTAINER = 'mod_readaloud_practiceinstructions_cont';
    const M_PRACTICEINSTRUCTIONS = 'mod_readaloud_practiceinstructions';
    const M_SMALLREPORT_CONTAINER = 'mod_readaloud_smallreport_cont';
    const M_FULLREPORT_CONTAINER = 'mod_readaloud_fullreport_cont';
    const M_INTRO_CONTAINER = 'mod_intro_box';
    const M_MODE_JOURNEY_CONTAINER = 'mod_readaloud_mode_journey_container';
    const M_FOOTERNAV_CONTAINER = 'mod_readaloud_footernav_cont';


    const M_PASSAGE_CONTAINER = 'mod_readaloud_passage_cont';
    const M_POSTATTEMPT = 'mod_readaloud_postattempt';
    const M_FEEDBACK_CONTAINER = 'mod_readaloud_feedback_cont';
    const M_ERROR_CONTAINER = 'mod_readaloud_error_cont';
    const M_GRADING_ERROR_CONTAINER = 'mod_readaloud_grading_error_cont';
    const M_GRADING_ERROR_IMG = 'mod_readaloud_grading_error_img';
    const M_GRADING_ERROR_SCORE = 'mod_readaloud_grading_error_score';
    const M_GRADING_WPM_CONTAINER = 'mod_readaloud_grading_wpm_cont';
    const M_GRADING_WPM_IMG = 'mod_readaloud_grading_wpm_img';
    const M_GRADING_WPM_SCORE = 'mod_readaloud_grading_wpm_score';
    const M_GRADING_ACCURACY_CONTAINER = 'mod_readaloud_grading_accuracy_cont';
    const M_GRADING_ACCURACY_IMG = 'mod_readaloud_grading_accuracy_img';
    const M_GRADING_ACCURACY_SCORE = 'mod_readaloud_grading_accuracy_score';
    const M_GRADING_SESSION_SCORE = 'mod_readaloud_grading_session_score';
    const M_GRADING_SESSIONSCORE_CONTAINER = 'mod_readaloud_grading_sessionscore_cont';
    const M_GRADING_SCORE = 'mod_readaloud_grading_score';
    const M_GRADING_PLAYER_CONTAINER = 'mod_readaloud_grading_player_cont';
    const M_GRADING_PLAYER = 'mod_readaloud_grading_player';
    const M_GRADING_ACTION_CONTAINER = 'mod_readaloud_grading_action_cont';
    const M_GRADING_FORM_SESSIONTIME = 'mod_readaloud_grading_form_sessiontime';
    const M_GRADING_FORM_SESSIONSCORE = 'mod_readaloud_grading_form_sessionscore';
    const M_GRADING_FORM_WPM = 'mod_readaloud_grading_form_wpm';
    const M_GRADING_FORM_ACCURACY = 'mod_readaloud_grading_form_accuracy';
    const M_GRADING_FORM_SESSIONENDWORD = 'mod_readaloud_grading_form_sessionendword';
    const M_GRADING_FORM_SESSIONERRORS = 'mod_readaloud_grading_form_sessionerrors';
    const M_ADMINTAB_CONTAINER = 'mod_readaloud_admintab_cont';
    const M_HIDDEN_PLAYER = 'mod_readaloud_hidden_player';
    const M_HIDDEN_PLAYER_BUTTON = 'mod_readaloud_hidden_player_button';
    const M_HIDDEN_PLAYER_BUTTON_ACTIVE = 'mod_readaloud_hidden_player_button_active';
    const M_HIDDEN_PLAYER_BUTTON_PAUSED = 'mod_readaloud_hidden_player_button_paused';
    const M_HIDDEN_PLAYER_BUTTON_PLAYING = 'mod_readaloud_hidden_player_button_playing';
    const M_EVALUATED_MESSAGE = 'mod_readaloud_evaluated_message';
    const M_MODELAUDIO_FORM_URLFIELD = 'mod_readaloud_modelaudio_form_urlfield';
    const M_MODELAUDIO_FORM_BREAKSFIELD = 'mod_readaloud_modelaudio_form_breaksfield';
    const M_MODELAUDIO_PLAYER = 'mod_readaloud_modelaudio_player';
    const M_VIEWMODELTRANSCRIPT = 'mod_readaloud_modeltranscript_button';
    const M_MODELTRANSCRIPT = 'mod_readaloud_modeltranscript';
    const M_CLASS_PASSAGEWORD = 'mod_readaloud_grading_passageword';
    const M_CLASS_PASSAGESPACE = 'mod_readaloud_grading_passagespace';
    const M_CLASS_PASSAGEGRADINGCONT = 'mod_readaloud_grading_passagecont';

    //languages
    const M_LANG_ENUS = 'en-US';
    const M_LANG_ENGB = 'en-GB';
    const M_LANG_ENAU = 'en-AU';
    const M_LANG_ENPH = 'en-PH';
    const M_LANG_ENNZ = 'en-NZ';
    const M_LANG_ENZA = 'en-ZA';
    const M_LANG_ENIN = 'en-IN';
    const M_LANG_ESUS = 'es-US';
    const M_LANG_ESES = 'es-ES';
    const M_LANG_FRCA = 'fr-CA';
    const M_LANG_FRFR = 'fr-FR';
    const M_LANG_DEDE = 'de-DE';
    const M_LANG_DEAT ='de-AT';
    const M_LANG_ITIT = 'it-IT';
    const M_LANG_PTBR = 'pt-BR';
    const M_LANG_DADK = 'da-DK';
    const M_LANG_FILPH = 'fil-PH';
    const M_LANG_KOKR = 'ko-KR';
    const M_LANG_HIIN = 'hi-IN';
    const M_LANG_ARAE ='ar-AE';
    const M_LANG_ARSA ='ar-SA';
    const M_LANG_ZHCN ='zh-CN';
    const M_LANG_NLNL ='nl-NL';
    const M_LANG_NLBE ='nl-BE';
    const M_LANG_ENIE ='en-IE';
    const M_LANG_ENWL ='en-WL';
    const M_LANG_ENAB ='en-AB';
    const M_LANG_FAIR ='fa-IR';
    const M_LANG_DECH ='de-CH';
    const M_LANG_HEIL ='he-IL';
    const M_LANG_IDID ='id-ID';
    const M_LANG_JAJP ='ja-JP';
    const M_LANG_MSMY ='ms-MY';
    const M_LANG_PTPT ='pt-PT';
    const M_LANG_RURU ='ru-RU';
    const M_LANG_TAIN ='ta-IN';
    const M_LANG_TEIN ='te-IN';
    const M_LANG_TRTR ='tr-TR';
    const M_LANG_NONO ='no-NO';
    const M_LANG_NBNO ='nb-NO';
    const M_LANG_NNNO ='nn-NO';
    const M_LANG_PSAF = 'ps-AF';
    const M_LANG_PLPL ='pl-PL';
    const M_LANG_RORO ='ro-RO';
    const M_LANG_SVSE ='sv-SE';
    const M_LANG_UKUA ='uk-UA';
    const M_LANG_EUES ='eu-ES';
    const M_LANG_FIFI ='fi-FI';
    const M_LANG_HUHU ='hu-HU';
    const M_LANG_MINZ ='mi-NZ';
    const M_LANG_VIVN ='vi-VN';

    const M_LANG_BGBG = 'bg-BG';
    const M_LANG_CSCZ = 'cs-CZ';
    const M_LANG_ELGR = 'el-GR';
    const M_LANG_HRHR = 'hr-HR';
    const M_LANG_LTLT = 'lt-LT';
    const M_LANG_LVLV = 'lv-LV';
    const M_LANG_SKSK = 'sk-SK';
    const M_LANG_SOSO = 'so-SO';
    const M_LANG_SLSI = 'sl-SI';
    const M_LANG_ISIS = 'is-IS';
    const M_LANG_MKMK = 'mk-MK';
    const M_LANG_SRRS = 'sr-RS';

    const TTS_NONE='ttsnone';

    const TRANSCRIBER_GUIDED = 0;
    const TRANSCRIBER_STRICT = 1;

    //no longer used
    const TRANSCRIBER_NONE = 0; //defunct
    const TRANSCRIBER_AMAZONSTREAMING =4; //defunct

    const M_HOME = 'mod_readaloud_button_home';
    const M_STARTPREVIEW= 'mod_readaloud_button_startpreview';
    const M_STARTLANDR= 'mod_readaloud_button_startlandr';
    const M_STARTREPORT = 'mod_readaloud_button_startreport';
    const M_STARTSHADOW= 'mod_readaloud_button_startshadow';
    const M_STARTNOSHADOW= 'mod_readaloud_button_startnoshadow';
    const M_STARTQUIZ= 'mod_readaloud_button_startquiz';
    const M_READAGAIN = 'mod_readaloud_button_readagain';
    const M_FULLREPORT = 'mod_readaloud_button_fullreport';
    const M_RETURNMENU= 'mod_readaloud_button_returnmenu';
    const M_STOPANDPLAY= 'mod_readaloud_button_stopandplay';
    const M_QUITLISTENING= 'mod_readaloud_button_quitlistening';
    const M_BACKTOTOP= 'mod_readaloud_button_backtotop';
    const M_STOP_BTN = 'mod_readaloud_button_stop';
    const M_PLAY_BTN = 'mod_readaloud_button_play';
    const M_RECORD_BTN = 'mod_readaloud_button_record';

    const M_PUSH_NONE =0;
    const M_PUSH_PASSAGE =1;
    const M_PUSH_ALTERNATIVES =2;
    const M_PUSH_QUESTIONS =3;
    const M_PUSH_TARGETWPM =4;
    const M_PUSH_TTSMODELAUDIO = 5;
    const M_PUSH_TIMELIMIT = 6;
    const M_PUSH_MODES = 7;
    const M_PUSH_GRADESETTINGS = 8;
    const M_PUSH_CANEXITEARLY = 9;

    const M_USE_DATATABLES=true;

    const M_STANDARD_FONTS = ["Arial", "Arial Black", "Verdana", "Tahoma", "Trebuchet MS", "Impact",
        "Times New Roman", "Didot", "Georgia", "American Typewriter", "Andalé Mono", "Courier",
        "Lucida Console", "Monaco", "Bradley Hand", "Brush Script MT", "Luminari", "Comic Sans MS"];

    const M_GOOGLE_FONTS = ["Andika"];

    const M_QUIZ_NONE = 0;
    const M_QUIZ_STANDARD = 1;

    
const TYPE_MULTIAUDIO = 'multiaudio';
const TYPE_MULTICHOICE = 'multichoice';
const TYPE_PAGE = 'page';
const TYPE_DICTATIONCHAT = 'dictationchat';
const TYPE_LGAPFILL = 'listeninggapfill';
const TYPE_TGAPFILL = 'typinggapfill';
const TYPE_SGAPFILL = 'speakinggapfill';
const TYPE_COMPQUIZ = 'comprehensionquiz';
const TYPE_BUTTONQUIZ = 'buttonquiz';
const TYPE_DICTATION = 'dictation';
const TYPE_SPEECHCARDS = 'speechcards';
const TYPE_LISTENREPEAT = 'listenrepeat';
const TYPE_SMARTFRAME = 'smartframe';
const TYPE_SHORTANSWER = 'shortanswer';
const TYPE_SPACEGAME = 'spacegame';
const TYPE_FREEWRITING = 'freewriting';
const TYPE_FREESPEAKING = 'freespeaking';
const TYPE_FLUENCY = 'fluency';
const TYPE_PASSAGEREADING = 'passagereading';
const TYPE_CONVERSATION = 'conversation';

const AUDIOFNAME = 'itemaudiofname';
const AUDIOPROMPT = 'audioitem';
const AUDIOANSWER = 'audioanswer';
const AUDIOMODEL = 'audiomodel';
const AUDIOPROMPT_FILEAREA = 'audioitem';
const TEXTINSTRUCTIONS = 'iteminstructions';
const TEXTQUESTION_FORMAT = 'itemtextformat';
const TTSQUESTION = 'itemtts';
const TTSQUESTIONVOICE = 'itemttsvoice';
const TTSQUESTIONOPTION = 'itemttsoption';
const TTSAUTOPLAY = 'itemttsautoplay';


const MEDIAQUESTION = 'itemmedia';
const QUESTIONTEXTAREA = 'itemtextarea';
const YTVIDEOID = 'itemytid';
const YTVIDEOSTART = 'itemytstart';
const YTVIDEOEND = 'itemytend';
const MEDIAIFRAME = 'customdata5';
const CUSTOMDATA = 'customdata';
const CUSTOMINT = 'customint';
const POLLYVOICE = 'customtext5';
const POLLYOPTION = 'customint4';
const CONFIRMCHOICE = 'customint3';
const AIGRADE_INSTRUCTIONS = 'customtext1';
const AIGRADE_FEEDBACK = 'customtext2';
const AIGRADE_FEEDBACK_LANGUAGE = 'customtext4';
const AIGRADE_MODELANSWER = 'customtext3';
const ALTERNATES = 'customtext2';
const TARGETWORDCOUNT = 'customint3';
const TOTALMARKS = 'customint1';
const TIMELIMIT = 'timelimit';
const GAPFILLALLOWRETRY = 'customint3';
const GAPFILLHIDESTARTPAGE = 'customint5';
const MAXCUSTOMTEXT=5;
const MAXCUSTOMDATA=5;
const MAXCUSTOMINT=5;

const ITEMTEXTAREA_EDOPTIONS =array('trusttext' => 0,'noclean'=>1, 'maxfiles' => 0);
const READSENTENCE = 'customint2';
const IGNOREPUNCTUATION = 'customint2';
const SHOWTEXTPROMPT = 'customint1';
const TEXTPROMPT_WORDS = 1;
const TEXTPROMPT_DOTS = 0;

const LISTENORREAD = 'customint2';
const LISTENORREAD_READ = 0;
const LISTENORREAD_LISTEN = 1;
const LISTENORREAD_LISTENANDREAD = 2;
const LISTENORREAD_IMAGE = 3;

const LAYOUT = 'layout';
const LAYOUT_AUTO = 0;
const LAYOUT_HORIZONTAL = 1;
const LAYOUT_VERTICAL = 2;
const LAYOUT_MAGAZINE = 3;

const TTS_NORMAL = 0;
const TTS_SLOW = 1;
const TTS_VERYSLOW = 2;
const TTS_SSML = 3;

const M_NOITEMS_CONT= 'mod_readaloud_noitems_cont';
const M_ITEMS_CONT= 'mod_readaloud_items_cont';
const M_ITEMS_TABLE= 'mod_readaloud_qpanel';

const RELEVANCE = "customint2";
const RELEVANCETYPE_NONE = 0;
const RELEVANCETYPE_QUESTION = 1;
const RELEVANCETYPE_MODELANSWER = 2;

const STEP_LISTEN = 1;
const STEP_PRACTICE = 2;
const STEP_SHADOW = 4;
const STEP_READ = 8;
const STEP_QUIZ = 16;
const STEP_REPORT = 0;
const STEPS = [
    "step_listen" => constants::STEP_LISTEN,
    "step_practice" => constants::STEP_PRACTICE,
    "step_shadow" => constants::STEP_SHADOW,
    "step_read" => constants::STEP_READ,
    "step_quiz" => constants::STEP_QUIZ,
    "step_report" => constants::STEP_REPORT,
];

const M_HOME_CONTAINER='mod_readaloud_home_cont';
const M_QUIZ_CONTAINER='mod_readaloud_quiz_cont';
const M_QUIZ_CONTAINER_WRAP='mod_readaloud_quiz_cont_wrap';
const M_PRACTICE_CONTAINER_WRAP='mod_readaloud_practice_cont_wrap';
const M_QUIZ_ITEMS_CONTAINER='mod_readaloud_quiz_items_cont';
const M_QUIZ_PLACEHOLDER='mod_readaloud_placeholder';
// const M_QUIZ_SKELETONBOX='mod_readaloud_skeleton_box';
const M_QUIZ_FINISHED = "mod_readaloud_quiz_finished";
const M_QUIZ_REATTEMPT = "mod_readaloud_quiz_reattempt";

// Finish screen options.
const FINISHSCREEN_SIMPLE=1;
const FINISHSCREEN_FULL=0;
const FINISHSCREEN_CUSTOM=2;

const M_SHOWQUIZ_NONE = 0;
const M_SHOWQUIZ_PASSAGE = 1;
const M_SHOWQUIZ_NOPASSAGE = 2;
  
}