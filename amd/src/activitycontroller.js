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
 * Activity controller for mod_readaloud module.
 *
 * Handles initialisation, mode rendering, navigation, and student progression
 * for the ReadAloud activity learning journey.
 *
 * @module     mod_readaloud/activitycontroller
 * @copyright  2025 Justin Hunt (Poodll)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* jshint ignore:start */
define(['jquery', 'core/log', "core/str", 'mod_readaloud/definitions',
    'mod_readaloud/recorderhelper', 'mod_readaloud/modelaudiokaraoke',
    'core/ajax', 'core/notification', 'mod_readaloud/smallreporthelper',
    'mod_readaloud/practice', 'mod_readaloud/quizhelper', 'mod_readaloud/clicktohear',  'core/templates',
],
    function ($, log, str, def, recorderhelper, modelaudiokaraoke,
        Ajax, notification, smallreporthelper, practice, quizhelper, clicktohear, Templates) {

        "use strict"; // jshint ;_;

        log.debug('Activity controller: initialising');

        // Disable click event.
        function disableClick(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }

        return {

            cmid: null,
            activitydata: null,
            holderid: null,
            recorderid: null,
            playerid: null,
            sorryboxid: null,
            modeviewid: null,
            controls: null,
            ra_recorder: null,
            rec_time_start: 0,
            steps_enabled: {},
            steps_open: {},
            steps_complete: {},
            letsshadow: false,
            strings: {},


            // CSS in this file.
            passagefinished: def.passagefinished,

            // For making multiple instances.
            clone: function () {
                return $.extend(true, {}, this);
            },

            // Pass in config, the jquery video/audio object, and a function to be called when conversion has finished.
            init: function (props) {
                var dd = this.clone();

                log.debug('Steps enabled:', props.stepsenabled);
                log.debug('Steps open:', props.stepsopen);
                log.debug('Complete:', props.stepscomplete);
                log.debug('Steps enabled:', props.stepsenabled);
                log.debug('props:');
                log.debug(props);
                log.debug(props, 'props:');


                // Pick up opts from html.
                var theid = '#amdopts_' + props.widgetid;
                var configcontrol = $(theid).get(0);
                if (configcontrol) {
                    dd.activitydata = JSON.parse(configcontrol.value);
                    $(theid).remove();
                } else {
                    // If there is no config we might as well give up.
                    log.debug('Read Aloud Test Controller: No config found on page. Giving up.');
                    return;
                }

                dd.cmid = props.cmid;
                dd.holderid = props.widgetid + '_holder';
                dd.recorderid = props.widgetid + '_recorder';
                dd.playerid = props.widgetid + '_player';
                dd.sorryboxid = props.widgetid + '_sorrybox';

                // If the browser doesn't support html5 recording,
                // then warn and do not go any further.
                if (!dd.is_browser_ok()) {
                    $('#' + dd.sorryboxid).show();
                    return;
                }

                // Set up steps - enabled, open and complete (passed from PHP)
                dd.steps_enabled = dd.activitydata.stepsenabled;
                dd.steps_open = dd.activitydata.stepsopen;
                dd.steps_complete = dd.activitydata.stepscomplete;

                // Set up model audio.
                dd.setupmodelaudio();

                // Set up listen and repeat.
                dd.setuppractice();

                // Init recorder and html and events.
                // NOTE: setup_recorder() is now called dynamically when read/shadow template is loaded.
                // dd.setup_recorder();
                dd.process_html(dd.activitydata);

                dd.register_events();
                dd.setup_strings();

                // Set up quiz.
                dd.setupquiz();

                // Set up click to hear.
                dd.setupclicktohear();

                // Set up small report helper.
                smallreporthelper.init(dd.activitydata);

                // Set initial mode.
                var initialMode = dd.getModeFromUrl();
                if (initialMode) {
                    dd.replaceModeInUrl(initialMode);
                    dd.renderMode(initialMode, null, false); // Validate URL based navigation.
                } else {
                    dd.domenulayout(); // Default to home/menu.
                }

                // Enable browser navigation from home to view.
                window.addEventListener('popstate', dd.onPopState.bind(dd));

            },

            setup_strings: function () {
                var dd = this;
                // Set up strings.
                str.get_strings([
                    { "key": "confirm_cancel_recording", "component": def.component },
                    { "key": "confirm_read_again", "component": def.component },
                    // More strings here.
                ]).done(function (s) {
                    var i = 0;
                    dd.strings.confirm_cancel_recording = s[i++];
                    dd.strings.confirm_read_again = s[i++];
                    // More strings here.
                });
            },

            setupmodelaudio: function () {
                var dd = this;
                var karaoke_opts = { breaks: this.activitydata.breaks, audioplayerclass: this.activitydata.audioplayerclass };
                modelaudiokaraoke.init(karaoke_opts);
                modelaudiokaraoke.on_complete = function () {
                    // Complete the current step (update server and ui).
                    dd.update_activity_step(dd.activitydata.steps.step_listen);
                    dd.domenulayout();
                }
            },

            setuppractice: function () {
                var dd = this;
                // Store practice options for later use when template is rendered
                // Don't call practice.init() here - it will be called in renderMode() when template exists.
                dd.practice_opts = {
                    modelaudiokaraoke: modelaudiokaraoke, cmid: this.cmid, language: this.activitydata.language,
                    region: this.activitydata.region, phonetics: this.activitydata.phonetics, stt_guided: this.activitydata.stt_guided
                };
                // Set the callback function to complete the activity.
                practice.on_complete = function () {
                    // Complete the current step (update server and ui).
                    dd.update_activity_step(dd.activitydata.steps.step_practice);
                    dd.domenulayout();
                }
            },

            setupclicktohear: function () {
                var dd = this;
                var clicktohear_opts = {
                    token: dd.activitydata.token, ttsvoice: dd.activitydata.ttsvoice,
                    region: dd.activitydata.region, owner: dd.activitydata.owner
                };
                clicktohear.init(clicktohear_opts);
            },

            setupquiz: function () {
                var dd = this;
                // Hack TO DO - get the real attempt id.
                dd.attemptid = 1;

                quizhelper.init(dd.activitydata,
                    dd.cmid,
                    dd.attemptid);

                // Set the callback function to complete the quiz.
                quizhelper.on_complete = function () {
                    // Complete the current step (update server and ui).
                    dd.update_activity_step(dd.activitydata.steps.step_quiz);
                }
            },

            process_html: function (opts) {
                // These css classes/ids are all passed in from php in
                // renderer.php::fetch_activity_amd should maybe just simplify and declare them in definitions.js.
                var controls = {
                    hider: $('.' + opts['hider']),
                    introbox: $('.' + 'mod_intro_box'),
                    feedbackcontainer: $('.' + opts['feedbackcontainer']),
                    errorcontainer: $('.' + opts['errorcontainer']),
                    passagecontainer: $('.mod_readaloud_readingcontainer ' + '.' + opts['passagecontainer']),
                    reviewpassagecontainer: $('.mod_readaloud_studentreportpassage ' + '.' + opts['passagecontainer']),
                    recordingcontainer: $('.' + opts['recordingcontainer']),
                    dummyrecorder: $('.' + opts['dummyrecorder']),
                    recordercontainer: $('.' + opts['recordercontainer']),
                    menubuttonscontainer: $('.' + opts['menubuttonscontainer']),
                    menuinstructionscontainer: $('.' + opts['menuinstructionscontainer']),
                    previewinstructionscontainer: $('.' + opts['previewinstructionscontainer']),
                    practiceinstructionscontainer: $('.' + opts['practiceinstructionscontainer']),
                    practicecontainerwrap: $('.' + opts['practicecontainerwrap']),
                    activityinstructionscontainer: $('.' + opts['activityinstructionscontainer']),
                    recinstructionscontainerright: $('.' + opts['recinstructionscontainerright']),
                    recinstructionscontainerleft: $('.' + opts['recinstructionscontainerleft']),
                    allowearlyexit: $('.' + opts['allowearlyexit']),
                    modelaudioplayer: $('#' + opts['modelaudioplayer']),
                    homebutton: $('#' + opts['homebutton']),
                    startlistenbutton: $('#' + opts['menubuttonscontainer'] + ' .mode-chooser[data-step="' + opts.steps.step_listen + '"]'),
                    startpracticebutton: $('#' + opts['menubuttonscontainer'] + ' .mode-chooser[data-step="' + opts.steps.step_practice + '"]'),
                    startreadbutton: $('#' + opts['menubuttonscontainer'] + ' .mode-chooser[data-step="' + opts.steps.step_read + '"]'),
                    startshadowbutton: $('#' + opts['menubuttonscontainer'] + ' .mode-chooser[data-step="' + opts.steps.step_shadow + '"]'),
                    startquizbutton: $('#' + opts['menubuttonscontainer'] + ' .mode-chooser[data-step="' + opts.steps.step_quiz + '"]'),
                    readagainbutton: $('#' + opts['readagainbutton']),
                    startreportbutton: $('#' + opts['startreportbutton'] + ' .mode-chooser[data-step="' + opts.steps.step_report + '"]'),
                    returnmenubutton: $('#' + opts['returnmenubutton']),
                    stopandplay: $('#' + opts['stopandplay']),
                    quitlisteningbutton: $('#' + opts['quitlisteningbutton']),
                    smallreportcontainer: $('#' + opts['smallreportcontainer']),
                    fullreportcontainer: $('#' + opts['fullreportcontainer']),
                    readingcontainer: $('#' + def.readingcontainer),
                    modeimagecontainer: $('#' + opts['modeimagecontainer']),
                    modejourneycontainer: $('#' + opts['modejourneycontainer']),
                    quizcontainer: $('.' + opts['quizcontainer']),
                    quizcontainerwrap: $('.' + opts['quizcontainerwrap']),
                    quizplaceholder: $('.' + opts['quizplaceholder']),
                    quizresultscontainer: $("." + opts['quizresultscontainer']),
                    homecontainer: $('.' + opts['homecontainer']),
                    modeview: $('#' + opts['modeview']),
                    activityheader: $('.mod_readaloud-activity-header'),
                    takequizbutton: $('.mod_readaloud_takequiz_button'),
                    quizviewreportbutton: $('.mod_readaloud_quizviewreport_button'),
                };
                this.controls = controls;
            },

            is_browser_ok: function () {
                return (navigator && navigator.mediaDevices
                    && navigator.mediaDevices.getUserMedia);
            },

            reset_recorder: function () {
                recorderhelper.reset();
                this.setup_recorder();
            },

            setup_recorder: function () {
                var dd = this;

                // After the recorder reports that it has (really) started, this function is called.
                var beginall = function () {
                    dd.passagerecorded = true;
                    if (dd.stepshadow_enabled && dd.letsshadow) {
                        dd.controls.modelaudioplayer[0].play();
                    }
                };

                var on_speech = function (eventdata) {
                    var speech = eventdata.capturedspeech;
                    var speechresults = eventdata.speechresults;
                };

                // Originates from the recording:started event.
                // Contains no meaningful data.
                // See https://api.poodll.com.
                var on_recording_start = function (eventdata) {
                    dd.rec_time_start = new Date().getTime();
                    dd.dopassagelayout();

                    // dd.controls.passagecontainer.show(1000, beginall);
                    dd.controls.passagecontainer.show(500, beginall);
                    dd.controls.passagecontainer[0].scrollIntoView({ behaviour: "smooth", block: "start", inline: "nearest" });

                    /*
                    var scrollparent = $("#page");
                    var newtop = scrollparent.scrollTop() + dd.controls.passagecontainer.offset().top - scrollparent.offset().top;
                    if(newtop<0) {newtop=0;}
                    scrollparent.animate({scrollTop: newtop}, 500,beginall);
                    */

                };

                // Originates from the recording:ended event.
                // Contains no meaningful data.
                // See https://api.poodll.com.
                var on_recording_end = function (eventdata) {
                    // Its a bit hacky but the rec end event can arrive immed. somehow probably when the mic test ends.
                    var now = new Date().getTime();
                    if ((now - dd.rec_time_start) < 3000) {
                        return;
                    }
                    dd.douploadlayout();
                    // If we are shadowing we should stop the audio player.
                    if (dd.stepshadow_enabled && dd.letsshadow) {
                        dd.controls.modelaudioplayer[0].currentTime = 0;
                        dd.controls.modelaudioplayer[0].pause();
                    }
                };

                // Data sent here originates from the awaiting_processing event.
                // See https://api.poodll.com.
                var on_audio_processing = function (eventdata) {
                    // At this point we know the submission has been uploaded and we know the fileURL.
                    // So we send the submission.
                    var now = new Date().getTime();
                    var rectime = now - dd.rec_time_start;
                    if (rectime > 0) {
                        rectime = Math.ceil(rectime / 1000);
                    }

                    dd.send_submission(eventdata.mediaurl, rectime);

                    // Complete the current step (update server and ui).
                    dd.update_activity_step(dd.activitydata.steps.step_read);

                    // Send user to the finished report immediately.
                    smallreporthelper.update_filename(eventdata.mediaurl);
                    smallreporthelper.start_check_for_results();
                    dd.doreadreportlayout();
                };

                // Init the recorder.
                recorderhelper.init(dd.activitydata,
                    on_recording_start,
                    on_recording_end,
                    on_audio_processing,
                    on_speech,
                );
            },

            register_events: function () {
                var dd = this;

                $('.mode-chooser.no-click').each(function () {
                    this.addEventListener('click', disableClick, true);
                    this.addEventListener('keypress', disableClick, true);
                });

                // Intercept navigation that would cause page reload - use SPA navigation instead
                $(document).on('click', '.secondary-navigation [data-key="modulepage"] a, .backarrow[data-action="back-to-home"], [data-action="readagain"], [data-action="takequiz"], [data-action="quizviewreport"]', function(e) {
                    e.preventDefault();
                    var action = $(this).data('action');
                    if (action === 'readagain') {
                        var result = confirm(dd.strings.confirm_read_again);
                        if (!result) {
                            return;
                        }
                        dd.reset_recorder();
                        dd.letsshadow = false;
                        dd.doreadinglayout();
                        return;
                    } else if (action === 'takequiz') {
                        dd.doquizlayout();
                        return;
                    } else if (action === 'quizviewreport') {
                        dd.doreportlayout();
                        return;
                    }
                    dd.domenulayout(); // Only call domenulayout for navigation actions.
                });

                dd.controls.startlistenbutton.click(function (e) {
                    dd.dopreviewlayout();
                    // TO DO: where to set this properly?
                    // Complete the current step (update server and ui).
                    dd.update_activity_step(dd.activitydata.steps.step_listen);
                });
                dd.controls.startlistenbutton.keypress(function (e) {
                    if (e.which == 32 || e.which == 13) {
                        dd.dopreviewlayout();
                        e.preventDefault();
                        // TO DO: where to set this properly?
                        // Complete the current step (update server and ui).
                        dd.update_activity_step(dd.activitydata.steps.step_listen);
                    }
                });
                dd.controls.startpracticebutton.click(function (e) {
                    dd.dopracticelayout();
                });
                dd.controls.startpracticebutton.keypress(function (e) {
                    if (e.which == 32 || e.which == 13) {
                        dd.dopracticelayout();
                        e.preventDefault();
                    }
                });
                dd.controls.startreadbutton.click(function (e) {
                    if (dd.steps_complete.step_read) {
                        dd.doreadreportlayout();
                    } else {
                        dd.letsshadow = false;
                        dd.doreadinglayout();
                    }
                });
                dd.controls.startreadbutton.keypress(function (e) {
                    if (e.which == 32 || e.which == 13) {
                        if (dd.steps_complete.step_read) {
                            dd.doreadreportlayout();
                        } else {
                            dd.letsshadow = false;
                            dd.doreadinglayout();
                        }
                        e.preventDefault();
                    }
                });

                dd.controls.readagainbutton.click(function (e) {
                    var result = confirm(dd.strings.confirm_read_again);
                    // Exit if they dont want to.
                    if (!result) {
                        return;
                    }
                    // Reset the recorder and start again.
                    dd.reset_recorder();
                    dd.letsshadow = false;
                    dd.doreadinglayout();
                });

                dd.controls.readagainbutton.keypress(function (e) {
                    if (e.which == 32 || e.which == 13) {
                        dd.letsshadow = false;
                        dd.doreadinglayout();
                        e.preventDefault();
                    }
                });

                dd.controls.startshadowbutton.click(function (e) {
                    // Practice shadowing.
                    // dd.dopracticelayout();
                    // practice.shadow=true.

                    dd.letsshadow = true;
                    dd.doreadinglayout();
                });
                dd.controls.startshadowbutton.keypress(function (e) {
                    if (e.which == 32 || e.which == 13) {
                        // dd.dopracticelayout();
                        // practice.shadow=true.

                        dd.letsshadow = true;
                        dd.doreadinglayout();
                        e.preventDefault();
                    }
                });
                dd.controls.returnmenubutton.click(function (e) {
                    // In most cases ajax hide show is ok, but L&R stuffs up android for normal readaloud so we reload.
                    if (dd.isandroid() && dd.controls.practiceinstructionscontainer.is(":visible")) {
                        location.reload();
                    } else if (dd.controls.readingcontainer.is(":visible")
                        && dd.controls.passagecontainer.hasClass('readmode')
                        && dd.controls.passagecontainer.is(":visible")) {
                        // Display a confirmation dialog.
                        var result = confirm(dd.strings.confirm_cancel_recording);
                        // There is no way to stop the recorder early, so just reload the page, brutal.
                        if (result) {
                            location.reload();
                        }
                    } else {
                        dd.controls.modelaudioplayer[0].currentTime = 0;
                        dd.controls.modelaudioplayer[0].pause();
                        dd.domenulayout();
                    }
                });

                dd.controls.startreportbutton.click(function (e) {
                    dd.doreportlayout();
                });
                dd.controls.startreportbutton.keypress(function (e) {
                    if (e.which == 32 || e.which == 13) {
                        dd.doreportlayout();
                    }
                });

                dd.controls.startquizbutton.click(function (e) {
                    dd.doquizlayout();
                });
                dd.controls.startquizbutton.keypress(function (e) {
                    if (e.which == 32 || e.which == 13) {
                        dd.doquizlayout();
                        e.preventDefault();
                    }
                });
                dd.controls.homebutton.click(function (e) {
                    dd.domenulayout();
                });
            },

            // When a step is completed, we update the activity completion on the server
            // and open the next step
            update_activity_step: function (step) {
                var that = this;
                Ajax.call([{
                    methodname: 'mod_readaloud_report_activitystep_completion',
                    args: {
                        cmid: that.cmid,
                        step: step
                    },
                    done: function (ajaxresult) {
                        var success = JSON.parse(ajaxresult);
                        switch (success) {
                            case true:
                                var adata = that.activitydata;
                                for (var key in adata.steps) {
                                    var thestep = adata.steps[key];
                                    if (thestep === step) {
                                        that.activitydata.stepscomplete[key] = true;
                                    } else {
                                        continue;
                                    }
                                }
                                // Open next step first (this updates stepsopen in activitydata).
                                that.open_next_step(step);
                                // Then update the UI based on the new stepsopen state
                                that.updateBigButtonMenuModeStatus();

                                break;
                            case false:
                            default:
                                log.debug('step ' + step + ' update failed');
                        }

                    },
                    fail: notification.exception
                }]);
            },

            open_next_step: function (oldstep) {
                var that = this;
                var adata = this.activitydata;
                // Loop through adata.steps array.
                // This looks like['step_listen': 1, 'step_practice': 2, 'step_shadow': 4, 'step_read': 8, 'step_quiz': 16].
                for (var key in adata.steps) {
                    var thestep = adata.steps[key];
                    // If the looped step is less than or equal to the old step, skip.
                    if (thestep <= oldstep) {
                        continue;
                    } else {
                        // If the looped step is enabled (present on page), open it.
                        var step_chooser = $('#' + adata['menubuttonscontainer'] + ' .mode-chooser[data-step="' + thestep + '"]');
                        log.debug(step_chooser);
                        if (step_chooser.length) {
                            step_chooser.removeClass('no-click');
                            step_chooser[0].removeEventListener('click', disableClick, true);

                            // (Hacky) show report if read step was done.
                            if (oldstep == adata.steps.step_read) {
                                that.controls.startreportbutton.removeClass('no-click');
                            }

                            // Record the newly opened step as 'open' for client side use.
                            that.activitydata.stepsopen[key] = thestep;
                            break;
                        }
                    }
                }
            },

            send_submission: function (filename, rectime) {
                var that = this;
                var shadowing = (that.stepshadow_enabled && that.letsshadow) ? 1 : 0;
                Ajax.call([{
                    methodname: 'mod_readaloud_submit_regular_attempt',
                    args: {
                        cmid: that.cmid,
                        filename: filename,// encodeURIComponent(filename),
                        rectime: rectime,
                        shadowing: shadowing
                    },
                    done: function (ajaxresult) {
                        var payloadobject = JSON.parse(ajaxresult);
                        if (payloadobject) {
                            switch (payloadobject.success) {
                                case true:
                                    log.debug('attempted submission accepted');
                                    break;
                                case false:
                                default:
                                    log.debug('attempted item evaluation failure');
                                    if (payloadobject.message) {
                                        log.debug('message: ' + payloadobject.message);
                                    }
                            }
                        }
                    },
                    fail: notification.exception
                }]);
            },

            dopassagelayout: function () {
                var m = this;

                // Hide.
                m.controls.introbox.hide();

                m.controls.readingcontainer.addClass(def.containerfillscreen);
            },

            douploadlayout: function () {
                var m = this;

                m.controls.passagecontainer.addClass(m.passagefinished);
                m.controls.hider.fadeIn('fast');
            },

            // TODO: These appear to be unused. Let's trial removal.
            // dofinishedlayout: function () {
            //     var m = this;

            //     // Hide.
            //     m.controls.activityinstructionscontainer.hide();
            //     m.controls.passagecontainer.hide();
            //     m.controls.quizcontainerwrap.hide();
            //     m.controls.recordingcontainer.hide();
            //     m.controls.returnmenubutton.hide();
            //     m.controls.smallreportcontainer.hide();

            //     // Show.
            //     m.controls.menubuttonscontainer.show();
            //     m.controls.feedbackcontainer.show();

            //     m.controls.readingcontainer.removeClass(def.containerfillscreen);

            //     m.controls.hider.fadeOut('fast');

            // },
            // doerrorlayout: function () {
            //     var m = this;

            //     // Hide.
            //     m.controls.passagecontainer.hide();
            //     m.controls.quizcontainerwrap.hide();
            //     m.controls.recordingcontainer.hide();

            //     // Show.
            //     m.controls.menubuttonscontainer.show();
            //     m.controls.errorcontainer.show();

            //     m.controls.readingcontainer.removeClass(def.containerfillscreen);

            //     m.controls.hider.fadeOut('fast');
            // },

            // Home (menu).
            domenulayout: function () {
                var m = this;
                m.showHome();
                if (typeof m.updateBigButtonMenuModeStatus === 'function') m.updateBigButtonMenuModeStatus();
                modelaudiokaraoke.modeling = true;
            },

            // Listen mode (preview).
            dopreviewlayout: function () {
                var m = this;
                modelaudiokaraoke.modeling = false;
                m.renderMode('listen', null, true);
            },

            // Practice mode.
            dopracticelayout: function () {
                var m = this;
                modelaudiokaraoke.modeling = false;
                m.renderMode('practice', null, true);
            },

            // Read mode (read / shadow).
            doreadinglayout: function () {
                var m = this;
                modelaudiokaraoke.modeling = true;
                var mode = m.letsshadow ? 'shadow' : 'read';
                m.renderMode(mode, { letsshadow: m.letsshadow }, true);
            },

            // Quiz mode.
            doquizlayout: function () {
                this.renderMode('quiz', null, true);
            },

            // Report mode.
            doreportlayout: function () {
                this.renderMode('report', null, true);
            },

            // Read report.
            doreadreportlayout: function () {
                this.renderMode('readreport', null, true);
            },

            getModeFromUrl: function () {
                var params = new URLSearchParams(window.location.search);
                var mode = params.get('mode');
                var allowed = ['listen', 'practice', 'read', 'shadow', 'quiz', 'report', 'readreport', 'quizreport'];
                return allowed.indexOf(mode) >= 0 ? mode : null;
            },

            canAccessMode: function(mode) {
                var dd = this;

                // Map modes to their required step.
                var modeStepMap = {
                    'listen': 'step_listen',
                    'practice': 'step_practice',
                    'read': 'step_read',
                    'shadow': 'step_shadow',
                    'quiz': 'step_quiz',
                    'report': 'step_report',
                    'readreport': 'step_read', // Requires read completion.
                    'quizreport': 'step_quiz', // Requires quiz completion.
                }

                var requiredStep = modeStepMap[mode];
                if (!requiredStep) {
                    return true; // Unknown mode, allow access.
                }

                // Check if the step is open (user has access).
                return dd.activitydata.stepsopen[requiredStep] !== undefined;
            },

            pushModeToUrl: function (mode) {
                var url = new URL(window.location.href);
                if (mode) {
                    url.searchParams.set('mode', mode);
                } else {
                    url.searchParams.delete('mode');
                }
                window.history.pushState({mode: mode}, '', url.toString());
            },

            replaceModeInUrl: function (mode) {
                var url = new URL(window.location.href);
                if (mode) {
                    url.searchParams.set('mode', mode);
                } else {
                    url.searchParams.delete('mode');
                }
                window.history.replaceState({
                    mode: mode
                }, '', url.toString());
            },

            // Map the modes to their corresponding mustache templates.
            getTemplateForMode: function (mode) {
                switch (mode) {
                    case 'listen':   return 'mod_readaloud/listen';
                    case 'practice': return 'mod_readaloud/practice';
                    case 'read':     return 'mod_readaloud/read';
                    case 'shadow':   return 'mod_readaloud/listen'; // TEMP: reuse listen for shadow
                    case 'quiz':     return 'mod_readaloud/quizcontainer';
                    case 'report':   return 'mod_readaloud/finalreport';
                    case 'readreport': return 'mod_readaloud/readreport';
                    default:         return null;
                }
            },

            // Hide home, render mode template into modeview.
            renderMode: function (mode, extraContext, isTrustedNavigation) {
                var dd = this;

                // Validate access if this is not trusted nvigation (i.e. from url).
                // Trusted navigation means the user has clicked a button we provided.
                if(!isTrustedNavigation && !dd.canAccessMode(mode)) {
                    log.debug('Access denied to mode: ' + mode);
                    dd.domenulayout(); // Redirect to home.
                    return;
                }

                var template = dd.getTemplateForMode(mode);
                if (!template) {
                    dd.domenulayout();
                    return;
                }

                // Use the template context from PHP which includes all template variables (playbutton, passagehtml, etc.)
                var templatecontext = $.extend(true, {}, dd.activitydata.templatecontext || {}, { mode: mode }, extraContext || {});
                var $home = dd.controls.homecontainer;
                var $view = dd.controls.modeview;

                Templates.renderForPromise(template, templatecontext).then(function (out) {
                    $view.html(out.html);
                    Templates.runTemplateJS(out.js);

                    // Show view, then hide home - prevents footer jump.
                    $view.removeClass('d-none').attr('hidden', false);
                    $home.addClass('d-none').attr('hidden', true);
                    dd.controls.activityheader.addClass('d-none').attr('hidden', true);
                    // Re-grab dynamic selectors inside the freshly rendered view.
                    dd.process_html(dd.activitydata);

                    // Re-initialise components that depend on DOM elements after template render.
                    modelaudiokaraoke.register_controls();
                    modelaudiokaraoke.register_events();

                    setTimeout(function() {
                        if (typeof dd.updateBigButtonMenuModeStatus === 'function') dd.updateBigButtonMenuModeStatus();
                    }, 0);

                    if (mode === 'practice') {
                        // Now call practice.init() with the template DOM elements available.
                        practice.init(dd.practice_opts);
                    }
                    if (mode === 'read' || mode === 'shadow') {
                        // Initialise the Cloud Poodll recorder after the template is rendered.
                        // The recorder div is now in the DOM, so we can initialise it.
                        dd.setup_recorder();
                    }
                    if (mode === 'quiz') {
                        /* quizhelper already init'd in setupquiz; it draws into quizcontainer */
                        // Maybe not .. when activitycontroller init calls setupquiz the quiz html is not there yet.
                        // So lets try here.
                        dd.setupquiz();
                    }

                    // Scroll/focus like a full page.
                    $view.attr('tabindex', '-1')[0].focus({preventScroll: true});
                    window.scrollTo({top: 0, behavior: 'instant'});

                    // Update URL.
                    dd.pushModeToUrl(mode);
                }).catch(notification.exception);
            },

            showHome: function () {
                var dd = this;
                var $home = dd.controls.homecontainer;
                var $view = dd.controls.modeview;

                $view.addClass('d-none').attr('hidden', true).empty();
                $home.removeClass('d-none').attr('hidden', false);
                dd.controls.activityheader.removeClass('d-none').attr('hidden', false);

                dd.pushModeToUrl(null);
            },

            // Gets the mode from the URL and renders the appropriate view.
            onPopState: function () {
                var dd = this;
                var mode = dd.getModeFromUrl();
                if (mode) {
                    dd.renderMode(mode, null, false); // Validate browser back/forward. navigation.
                } else {
                    dd.showHome();
                }
            },

            // Helper for bigbuttonmenu.
            // Updates each step element based on its enabled/completed state.
            // Assumes "stepsOrder" is fixed and that both templates output numeric data-step values.
            // Marks report as complete if either of step_read or step_quiz is completed.
            updateStepsStatus: function ($container, itemSelector, renderCallback) {
                var dd = this;
                var stepsOrder = ['step_listen', 'step_practice', 'step_shadow', 'step_read', 'step_quiz', 'step_report'];
                var stepsComplete = dd.activitydata.stepscomplete || {};
                var stepsEnabled = dd.activitydata.stepsenabled || {};
                var stepsMapping = dd.activitydata.steps || {}; // Maps canonical keys to numeric values.

                if (!$container || !$container.length) {
                    console.error('Container not found.');
                    return;
                }

                // Build an array of enabled step keys (in fixed order).
                var enabledSteps = stepsOrder.filter(function (step) {
                    return !!stepsEnabled[step];
                });

                // Determine the first enabled but incomplete step (skip step_report).
                var firstIncomplete = null;
                for (var i = 0; i < enabledSteps.length; i++) {
                    var step = enabledSteps[i];
                    if (step === 'step_report') { continue; }
                    if (!(stepsComplete[step] === true || stepsComplete[step] === 'true')) {
                        firstIncomplete = step;
                        break;
                    }
                }

                // Process each element in the container.
                $container.find(itemSelector).each(function () {
                    var $elem = $(this);
                    // Here data-step returns the numeric value.
                    var stepNumber = $elem.data("step");
                    if (stepNumber === undefined || stepNumber === null) {
                        console.error("Missing data-step on element, skipping.");
                        return true; // Continue.
                    }
                    // Reverse lookup: find the canonical step key that matches the numeric value.
                    var stepKey = null;
                    $.each(stepsMapping, function (key, value) {
                        if (parseInt(value, 10) === parseInt(stepNumber, 10)) {
                            stepKey = key;
                            return false; // Break out of the loop.
                        }
                    });
                    if (!stepKey) {
                        console.error("No canonical step key found for data-step value:", stepNumber);
                        return true;
                    }
                    // Only process if enabled.
                    if (!stepsEnabled[stepKey]) {
                        return true;
                    }
                    var status;
                    // Mark step_report as completed if step_read or step_quiz is completed.
                    if (stepKey === 'step_report' &&
                        ((stepsComplete['step_read'] === true || stepsComplete['step_read'] === 'true') ||
                            (stepsComplete['step_quiz'] === true || stepsComplete['step_quiz'] === 'true'))) {
                        status = 'completed';
                    } else {
                        var isComplete = (stepsComplete[stepKey] === true || stepsComplete[stepKey] === 'true');
                        if (isComplete) {
                            status = 'completed';
                        } else if (stepKey === firstIncomplete) {
                            status = 'in-progress';
                        } else {
                            status = 'upcoming';
                        }
                    }
                    renderCallback($elem, status, stepKey);
                });
            },

            d_show: function (els) {
                // If el is a jquery object get the first element.
                if (!els instanceof jQuery) {
                    els = [els];
                }

                for (var i = 0; i < els.length; i++) {
                    var el = els[i];

                    if (el.classList.contains('d-none')) {
                        el.classList.remove('d-none');
                        el.classList.add('d-flex');

                    } else if (el.classList.contains('hidden')) {
                        el.classList.remove('hidden');
                        el.classList.add('visible');

                    } else {
                        $(el).show();
                    }
                }
            },

            d_hide: function (els) {
                // If el is a jquery object get the first element.
                if (!els instanceof jQuery) {
                    els = [els];
                }

                for (var i = 0; i < els.length; i++) {
                    var el = els[i];

                    if (el.classList.contains('d-flex')) {
                        el.classList.remove('d-flex');
                        el.classList.add('d-none');

                    } else if (el.classList.contains('visible')) {
                        el.classList.remove('visible');
                        el.classList.add('hidden');

                    } else {
                        $(el).hide();
                    }
                }
            },

            updateBigButtonMenuModeStatus: function () {
                var dd = this;
                var stepsOpen = dd.activitydata.stepsopen || {};
                dd.updateStepsStatus(dd.controls.menubuttonscontainer, 'li.mode-chooser', function ($elem, status, stepKey) {
                    var $iconImg = $elem.find('.nav-status-icon');
                    if ($iconImg.length) {
                        var iconName, altText;
                        var isOpen = stepsOpen[stepKey] !== undefined && stepsOpen[stepKey] !== false;

                        if (status === 'completed') {
                            iconName = 'checked';
                            altText = 'Complete';
                        } else if (isOpen) {
                            // If step is open but not complete, show current icon.
                            iconName = 'current';
                            altText = 'In progress';
                        } else {
                            // Step is not open yet, show locked.
                            iconName = 'locked';
                            altText = 'Locked';
                        }
                        var iconUrl = M.util.image_url(iconName, 'mod_readaloud');
                        $iconImg.attr('src', iconUrl);
                        $iconImg.attr('alt', altText);

                        // Update CSS classes on the li element to match the icon state.
                        if (status === 'completed') {
                            $elem.removeClass('locked current').addClass('completed');
                        } else if (isOpen) {
                            $elem.removeClass('locked completed').addClass('current');
                        } else {
                            $elem.removeClass('current completed').addClass('locked');
                        }
                    }
                });
            },

            isandroid: function () {
                if (/Android/i.test(navigator.userAgent)) {
                    return true;
                } else {
                    return false;
                }
            }
        };// End of returned object.
    });// Total end.
