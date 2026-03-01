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
define([
	"jquery",
	"core/log",
	"core/str",
	"core/fragment",
	"mod_readaloud/definitions",
	"mod_readaloud/modelaudiokaraoke",
	"core/ajax",
	"core/notification",
	"mod_readaloud/readreporthelper",
	"mod_readaloud/practice",
	"mod_readaloud/read",
	"mod_readaloud/quizhelper",
	"mod_readaloud/clicktohear",
	"core/templates",
], function (
	$,
	log,
	str,
	Fragment,
	def,
	modelaudiokaraoke,
	Ajax,
	notification,
	readreporthelper,
	practice,
	read,
	quizhelper,
	clicktohear,
	Templates,
) {
	"use strict"; // jshint ;_;

	log.debug("Activity controller: initialising");

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

			log.debug("Steps enabled:", props.stepsenabled);
			log.debug("Steps open:", props.stepsopen);
			log.debug("Complete:", props.stepscomplete);
			log.debug("Steps enabled:", props.stepsenabled);
			log.debug("props:");
			log.debug(props);
			log.debug(props, "props:");

			// Pick up opts from html.
			var theid = "#amdopts_" + props.widgetid;
			var configcontrol = $(theid).get(0);
			if (configcontrol) {
				dd.activitydata = JSON.parse(configcontrol.value);
				$(theid).remove();
			} else {
				// If there is no config we might as well give up.
				log.debug(
					"Read Aloud Test Controller: No config found on page. Giving up.",
				);
				return;
			}

			dd.cmid = props.cmid;
			dd.holderid = props.widgetid + "_holder";
			dd.recorderid = props.widgetid + "_recorder";
			dd.playerid = props.widgetid + "_player";
			dd.sorryboxid = props.widgetid + "_sorrybox";

			// If the browser doesn't support html5 recording,
			// then warn and do not go any further.
			if (!dd.is_browser_ok()) {
				$("#" + dd.sorryboxid).show();
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

			// Set up read
			dd.setupread();

			// Init recorder and html and events.
			// NOTE: setup_recorder() is now called dynamically when read/shadow template is loaded.
			// dd.setup_recorder();
			dd.process_html(dd.activitydata);

			dd.register_events();
			dd.setup_strings();

			// Set up quiz.
			// Don't call quizhelper.init() here - it will be called in renderMode() when template exists.
			// the html for the questions is not on the page yet.
			//dd.setupquiz();

			// Set up click to hear.
			dd.setupclicktohear();

			// Set up read report helper.
			readreporthelper.init(dd.activitydata);

			// Set initial mode.
			var initialMode = dd.getModeFromUrl();
			if (initialMode) {
				dd.replaceModeInUrl(initialMode);
				dd.renderMode(initialMode, null, false); // Validate URL based navigation.
			} else {
				dd.domenulayout(); // Default to home/menu.
			}

			// Enable browser navigation from home to view.
			window.addEventListener("popstate", dd.onPopState.bind(dd));
		},

		get_activity_data: function () {
			return this.activitydata;
		},

		setup_strings: function () {
			var dd = this;
			// Set up strings.
			str
				.get_strings([
					{ key: "confirm_cancel_recording", component: def.component },
					{ key: "confirm_read_again", component: def.component },
					// More strings here.
				])
				.done(function (s) {
					var i = 0;
					dd.strings.confirm_cancel_recording = s[i++];
					dd.strings.confirm_read_again = s[i++];
					// More strings here.
				});
		},

		setupmodelaudio: function () {
			var dd = this;
			var karaoke_opts = {
				breaks: this.activitydata.breaks,
				audioplayerclass: this.activitydata.audioplayerclass,
			};
			modelaudiokaraoke.init(karaoke_opts);
			modelaudiokaraoke.on_complete = function () {
				// Complete the current step (update server and ui).
				dd.update_activity_step(dd.activitydata.steps.step_listen);
				dd.domenulayout();
			};
		},

		setuppractice: function () {
			var dd = this;
			// Store practice options for later use when template is rendered
			// Don't call practice.init() here - it will be called in renderMode() when template exists.
			dd.practice_opts = {
				modelaudiokaraoke: modelaudiokaraoke,
				cmid: this.cmid,
				language: this.activitydata.language,
				region: this.activitydata.region,
				phonetics: this.activitydata.phonetics,
				stt_guided: this.activitydata.stt_guided,
			};
			// Set the callback function to complete the activity.
			practice.on_complete = function () {
				// Complete the current step (update server and ui).
				dd.update_activity_step(dd.activitydata.steps.step_practice);
				dd.domenulayout();
			};
		},

		setupclicktohear: function () {
			var dd = this;
			var clicktohear_opts = {
				token: dd.activitydata.token,
				ttsvoice: dd.activitydata.ttsvoice,
				region: dd.activitydata.region,
				owner: dd.activitydata.owner,
			};
			clicktohear.init(clicktohear_opts);
		},

		setupquiz: function () {
			var dd = this;
			// Hack TO DO - get the real attempt id.
			dd.attemptid = 1;

			quizhelper.init(dd.activitydata, dd.cmid, dd.attemptid);

			// Set the callback function to complete the quiz.
			quizhelper.on_complete = function () {
				// Complete the current step (update server and ui).
				dd.update_activity_step(dd.activitydata.steps.step_quiz);
				// Flag the quiz as finished
				dd.activitydata.templatecontext.quizfinished = true;
				// Re-fetch the results data
				dd.refresh_activity_data(
					"fullreportdata,readreport,quizfinisheddata",
					function () {
						// First make sure the user is still in quiz location
						if (dd.getModeFromUrl() === "quiz") {
							dd.doquizlayout();
						} else {
							log.debug(
								"Callback to display quizreport skipped because the user is no longer on that page/view",
							);
						}
					}.bind(dd),
				);
			};
		},

		setupread: function () {
			var dd = this;

			var readprops = dd.getJsPropsForMode("read");
			read.init(readprops);

			// Set the callback function to complete the quiz.
			read.on_complete = function (eventdata) {
				// Complete the current step (update server and ui).
				dd.update_activity_step(dd.activitydata.steps.step_read);
				// Init the read report helper to check for results
				var readreportprops = dd.getJsPropsForMode("readreport");
				readreporthelper.init(readreportprops);
				readreporthelper.on_results_fetched = function () {
					dd.refresh_activity_data(
						"fullreportdata,readreport,passagehtml",
						function () {
							// First make sure the user is still in readreportdummy location
							if (dd.getModeFromUrl() === "readreportdummy") {
								dd.doreadreportlayout();
							} else {
								log.debug(
									"Callback to display readreport skipped because the user is no longer on that page/view",
								);
							}
						}.bind(dd),
					);
				};

				readreporthelper.update_filename(eventdata.mediaurl);
				// Commence a loop checking for results
				readreporthelper.start_check_for_results();
				// Send user to the  read report immediately though it will be a dummy
				dd.dodummyreadreportlayout();
				// Set flag in read report data so if user comes in off menu,
				// It will not show read report of old data, but show readreportdummy
				dd.activitydata.templatecontext.readreport.ready = false;
			};
		},

		process_html: function (opts) {
			// These css classes/ids are all passed in from php in renderer.php::fetch_activity_amd
			//  should maybe just simplify and declare them in definitions.js.
			var controls = {
				hider: $("." + opts["hider"]),
				introbox: $("." + "mod_intro_box"),
				feedbackcontainer: $("." + opts["feedbackcontainer"]),
				errorcontainer: $("." + opts["errorcontainer"]),
				passagecontainer: $(
					".mod_readaloud_readingcontainer " + "." + opts["passagecontainer"],
				),
				reviewpassagecontainer: $(
					".mod_readaloud_studentreportpassage " +
						"." +
						opts["passagecontainer"],
				),
				recordingcontainer: $("." + opts["recordingcontainer"]),
				dummyrecorder: $("." + opts["dummyrecorder"]),
				recordercontainer: $("." + opts["recordercontainer"]),
				menubuttonscontainer: $("." + opts["menubuttonscontainer"]),
				menuinstructionscontainer: $("." + opts["menuinstructionscontainer"]),
				previewinstructionscontainer: $(
					"." + opts["previewinstructionscontainer"],
				),
				practiceinstructionscontainer: $(
					"." + opts["practiceinstructionscontainer"],
				),
				practicecontainerwrap: $("." + opts["practicecontainerwrap"]),
				activityinstructionscontainer: $(
					"." + opts["activityinstructionscontainer"],
				),
				recinstructionscontainerright: $(
					"." + opts["recinstructionscontainerright"],
				),
				recinstructionscontainerleft: $(
					"." + opts["recinstructionscontainerleft"],
				),
				allowearlyexit: $("." + opts["allowearlyexit"]),
				modelaudioplayer: $("#" + opts["modelaudioplayer"]),
				homebutton: $("#" + opts["homebutton"]),
				startlistenbutton: $(
					"#" +
						opts["menubuttonscontainer"] +
						' .mode-chooser[data-step="' +
						opts.steps.step_listen +
						'"]',
				),
				startpracticebutton: $(
					"#" +
						opts["menubuttonscontainer"] +
						' .mode-chooser[data-step="' +
						opts.steps.step_practice +
						'"]',
				),
				startreadbutton: $(
					"#" +
						opts["menubuttonscontainer"] +
						' .mode-chooser[data-step="' +
						opts.steps.step_read +
						'"]',
				),
				startshadowbutton: $(
					"#" +
						opts["menubuttonscontainer"] +
						' .mode-chooser[data-step="' +
						opts.steps.step_shadow +
						'"]',
				),
				startquizbutton: $(
					"#" +
						opts["menubuttonscontainer"] +
						' .mode-chooser[data-step="' +
						opts.steps.step_quiz +
						'"]',
				),
				readagainbutton: $("#" + opts["readagainbutton"]),
				startreportbutton: $(
					"#" +
						opts["menubuttonscontainer"] +
						' .mode-chooser[data-step="' +
						opts.steps.step_report +
						'"]',
				),
				returnmenubutton: $("#" + opts["returnmenubutton"]),
				stopandplay: $("#" + opts["stopandplay"]),
				quitlisteningbutton: $("#" + opts["quitlisteningbutton"]),
				readreportcontainer: $("#" + opts["readreportcontainer"]),
				fullreportcontainer: $("#" + opts["fullreportcontainer"]),
				readingcontainer: $("#" + def.readingcontainer),
				modeimagecontainer: $("#" + opts["modeimagecontainer"]),
				modejourneycontainer: $("#" + opts["modejourneycontainer"]),
				quizcontainer: $("." + opts["quizcontainer"]),
				quizcontainerwrap: $("." + opts["quizcontainerwrap"]),
				quizplaceholder: $("." + opts["quizplaceholder"]),
				quizresultscontainer: $("." + opts["quizresultscontainer"]),
				homecontainer: $("." + opts["homecontainer"]),
				modeview: $("#" + opts["modeview"]),
				activityheader: $(".mod_readaloud-activity-header"),
				takequizbutton: $(".mod_readaloud_takequiz_button"),
				viewfinalreportbutton: $(".mod_readaloud_quizviewreport_button"),
			};
			this.controls = controls;
		},

		is_browser_ok: function () {
			return (
				navigator &&
				navigator.mediaDevices &&
				navigator.mediaDevices.getUserMedia
			);
		},

		register_events: function () {
			var dd = this;

			$(".mode-chooser.no-click").each(function () {
				this.addEventListener("click", disableClick, true);
				this.addEventListener("keypress", disableClick, true);
			});

			// Intercept navigation that would cause page reload - use SPA navigation instead
			$(document).on(
				"click",
				'.secondary-navigation [data-key="modulepage"] a, .backarrow[data-action="back-to-home"], [data-action="readagain"], [data-action="takequiz"], [data-action="retakequiz"], [data-action="viewfinalreport"]',
				function (e) {
					e.preventDefault();
					var action = $(this).data("action");
					if (action === "readagain") {
						var result = confirm(dd.strings.confirm_read_again);
						if (!result) {
							return;
						}
						read.reset_recorder();
						dd.letsshadow = false;
						log.debug("Re-readinglayout");
						dd.doreadinglayout();
						return;
					} else if (action === "takequiz") {
						dd.doquizlayout();
						return;
					} else if (action === "retakequiz") {
						log.debug("Re-taking quiz");
						// we need to call rendermode (not doquizlayout) so that we can pass the retakequiz flag
						// Rendermode puts the html from php on the page again in a promise that resolves a bit slow
						// And we need to hide/show stuff to get retake after that. The flag sets that up
						//dd.renderMode('quiz', {retakequiz: true, quizfinished: false}, true);
						var retake = true;
						dd.doquizlayout(retake);
						return;
					} else if (action === "viewfinalreport") {
						dd.doreportlayout();
						return;
					}
					dd.domenulayout(); // Only call domenulayout for navigation actions.
				},
			);

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
				read.reset_recorder();
				dd.letsshadow = false;
				dd.doreadinglayout();
			});

			dd.controls.readagainbutton.keypress(function (e) {
				if (e.which == 32 || e.which == 13) {
					read.reset_recorder();
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
				if (
					dd.isandroid() &&
					dd.controls.practiceinstructionscontainer.is(":visible")
				) {
					location.reload();
				} else if (
					dd.controls.readingcontainer.is(":visible") &&
					dd.controls.passagecontainer.hasClass("readmode") &&
					dd.controls.passagecontainer.is(":visible")
				) {
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

		refresh_activity_data: async function (
			requestedcontextitems = "all",
			callbackfunction = null,
		) {
			var that = this;
			Ajax.call([
				{
					methodname: "mod_readaloud_fetch_view_data",
					args: {
						cmid: that.cmid,
						requestedcontextitems: requestedcontextitems,
					},
					done: function (ajaxresult) {
						var returneditems = JSON.parse(ajaxresult);
						if (returneditems) {
							// Split requestedcontextitems on commas and loop through them
							var contextitems = requestedcontextitems.split(",");
							for (var i = 0; i < contextitems.length; i++) {
								var requestedcontextitem = contextitems[i];

								// If that item is in returneditems, update activitydata
								if (
									requestedcontextitem === "all" ||
									requestedcontextitem in returneditems
								) {
									switch (requestedcontextitem) {
										case "all":
											that.activitydata.templatecontext = returneditems;
											break;
										case "somethingthatmightneedhandling":
											// Do something specific for this case
											break;
										default:
											// e.g., 'fullreportdata' or 'steps'
											that.activitydata.templatecontext[requestedcontextitem] =
												returneditems[requestedcontextitem];
									}
								}
							}
						}
						// Do callback if we have one.
						if (callbackfunction && typeof callbackfunction === "function") {
							callbackfunction();
						}
					},
					fail: notification.exception,
				},
			]);
		},

		// When a step is completed, we update the activity completion on the server
		// and open the next step
		update_activity_step: function (step) {
			var that = this;
			var isasync = false;
			Ajax.call(
				[
					{
						methodname: "mod_readaloud_report_activitystep_completion",
						args: {
							cmid: that.cmid,
							step: step,
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
									// Update the progress bar
									that.updateProgressBar();

									break;
								case false:
								default:
									log.debug("step " + step + " update failed");
							}
						},
						fail: notification.exception,
					},
				],
				isasync,
			);
		},

		open_next_step: function (oldstep) {
			var that = this;
			var adata = this.activitydata;

			// Loop through adata.steps array.
			// This looks like['step_listen': 1, 'step_practice': 2, 'step_shadow': 4, 'step_read': 8, 'step_quiz': 16].
			var openednextstep = false;
			for (var key in adata.steps) {
				var thestep = adata.steps[key];
				// If the looped step is less than or equal to the old step, skip.
				if (thestep > oldstep) {
					// If the looped step is enabled (present on page), open it.
					var step_chooser = $(
						"#" +
							adata["menubuttonscontainer"] +
							' .mode-chooser[data-step="' +
							thestep +
							'"]',
					);
					log.debug(step_chooser);
					if (step_chooser.length) {
						step_chooser.removeClass("no-click");
						step_chooser[0].removeEventListener("click", disableClick, true);

						// Record the newly opened step as 'open' for client side use.
						that.activitydata.stepsopen[key] = thestep;
						openednextstep = true;
						break;
					}
				}
			}
			// If openednextstep is still false here, then oldstep was the last one.(report is special case and has value 0)
			// So we should open the report step if it is enabled.
			if (!openednextstep) {
				var reportstep = adata.steps.step_report;
				var report_chooser = $(
					"#" +
						adata["menubuttonscontainer"] +
						' .mode-chooser[data-step="' +
						reportstep +
						'"]',
				);
				if (report_chooser.length) {
					report_chooser.removeClass("no-click");
					report_chooser[0].removeEventListener("click", disableClick, true);
					that.activitydata.stepsopen["step_report"] = reportstep;
				}
			}
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
		//     m.controls.readreportcontainer.hide();

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
			if (typeof m.updateBigButtonMenuModeStatus === "function")
				m.updateBigButtonMenuModeStatus();
			modelaudiokaraoke.modeling = true;
		},

		// Listen mode (preview).
		dopreviewlayout: function () {
			var m = this;
			modelaudiokaraoke.modeling = false;
			m.renderMode("listen", null, true);
		},

		// Practice mode.
		dopracticelayout: function () {
			var m = this;
			modelaudiokaraoke.modeling = false;
			m.renderMode("practice", null, true);
		},

		// Read mode (read / shadow).
		doreadinglayout: function () {
			var m = this;
			modelaudiokaraoke.modeling = true;
			var mode = m.letsshadow ? "shadow" : "read";
			m.renderMode(mode, { letsshadow: m.letsshadow }, true);
		},

		// Quiz mode.
		doquizlayout: function (retake = false) {
			if (retake || this.activitydata.templatecontext.quizfinished !== true) {
				this.renderMode("quiz", null, true);
			} else {
				this.renderMode("quizreport", null, true);
			}
		},

		// Report mode.
		doreportlayout: function () {
			this.renderMode("report", null, true);
		},

		// Read report.
		doreadreportlayout: function () {
			// If we are waiting on results, show the placeholder, otherwise show the real report.
			if (!this.activitydata.templatecontext.readreport.ready) {
				this.renderMode("readreportdummy", null, true);
			} else {
				this.renderMode("readreport", null, true);
			}
		},

		// Dummy Read report.
		dodummyreadreportlayout: function () {
			this.renderMode("readreportdummy", null, true);
		},

		getModeFromUrl: function () {
			var params = new URLSearchParams(window.location.search);
			var mode = params.get("mode");
			var allowed = [
				"listen",
				"practice",
				"read",
				"shadow",
				"quiz",
				"report",
				"readreport",
				"readreportdummy",
				"quizreport",
			];
			return allowed.indexOf(mode) >= 0 ? mode : null;
		},

		canAccessMode: function (mode) {
			var dd = this;

			// Map modes to their required step.
			var modeStepMap = {
				listen: "step_listen",
				practice: "step_practice",
				read: "step_read",
				shadow: "step_shadow",
				quiz: "step_quiz",
				report: "step_report",
				readreport: "step_read", // Requires read completion.
				readreportdummy: "step_read", // Requires read completion.
				quizreport: "step_quiz", // Requires quiz completion.
			};

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
				url.searchParams.set("mode", mode);
			} else {
				url.searchParams.delete("mode");
			}
			window.history.pushState({ mode: mode }, "", url.toString());
		},

		replaceModeInUrl: function (mode) {
			var url = new URL(window.location.href);
			if (mode) {
				url.searchParams.set("mode", mode);
			} else {
				url.searchParams.delete("mode");
			}
			window.history.replaceState(
				{
					mode: mode,
				},
				"",
				url.toString(),
			);
		},

		// Map the modes to their corresponding mustache templates.
		getTemplateForMode: function (mode) {
			switch (mode) {
				case "listen":
					return "mod_readaloud/listen";
				case "practice":
					return "mod_readaloud/practice";
				case "read":
					return "mod_readaloud/read";
				case "shadow":
					return "mod_readaloud/listen"; // TEMP: reuse listen for shadow
				case "quiz":
					return "mod_readaloud/quizcontainer";
				case "quizreport":
					return "mod_readaloud/quizreport";
				case "report":
					return "mod_readaloud/finalreport";
				case "readreport":
					return "mod_readaloud/readreport";
				case "readreportdummy":
					return "mod_readaloud/readreportdummy";
				default:
					return null;
			}
		},

		// Map the modes to their corresponding mustache templates.
		getJsPropsForMode: function (mode) {
			var dd = this;
			var props = {};
			switch (mode) {
				case "read":
					props = {
						activitycontroller: dd,
						passagecontainer: dd.activitydata["passagecontainer"],
						modelaudioplayer: dd.activitydata["modelaudioplayer"],
						hider: dd.activitydata["hider"],
						stepshadow_enabled: dd.steps_enabled.step_shadow,
						cmid: dd.cmid,
						passagefinished: dd.passagefinished,
						letsshadow: dd.letsshadow,
					};
					break;

				case "practice":
					props = dd.practice_opts;
					break;

				case "readreport":
					props = dd.activitydata.templatecontext.readreport;
					break;

				case "readreportdummy":
				case "shadow":
				case "quiz":
				case "quizreport":
				case "report":
				case "listen":

				default:
			}
			return props;
		},

		// Hide home, render mode template into modeview.
		renderMode: function (mode, extraContext, isTrustedNavigation) {
			var dd = this;

			// Validate access if this is not trusted nvigation (i.e. from url).
			// Trusted navigation means the user has clicked a button we provided.
			if (!isTrustedNavigation && !dd.canAccessMode(mode)) {
				log.debug("Access denied to mode: " + mode);
				dd.domenulayout(); // Redirect to home.
				return;
			}

			var template = dd.getTemplateForMode(mode);
			if (!template) {
				dd.domenulayout();
				return;
			}

			// Use the template context from PHP which includes all template variables (playbutton, passagehtml, etc.)
			var templatecontext = $.extend(
				true,
				{},
				dd.activitydata.templatecontext || {},
				{ mode: mode },
				extraContext || {},
			);
			var $home = dd.controls.homecontainer;
			var $view = dd.controls.modeview;

			Templates.renderForPromise(template, templatecontext)
				.then(function (out) {
					$view.html(out.html);
					Templates.runTemplateJS(out.js);

					// Show view, then hide home - prevents footer jump.
					$view.removeClass("d-none").attr("hidden", false);
					$home.addClass("d-none").attr("hidden", true);
					dd.controls.activityheader.addClass("d-none").attr("hidden", true);
					// Re-grab dynamic selectors inside the freshly rendered view.
					dd.process_html(dd.activitydata);

					// Re-initialise components that depend on DOM elements after template render.
					modelaudiokaraoke.register_controls();
					modelaudiokaraoke.register_events();

					setTimeout(function () {
						if (typeof dd.updateBigButtonMenuModeStatus === "function")
							dd.updateBigButtonMenuModeStatus();
					}, 0);

					if (mode === "readreport") {
						var readreportprops = dd.getJsPropsForMode("readreport");
						readreporthelper.init(readreportprops);
					}

					if (mode === "practice") {
						var practiceprops = dd.getJsPropsForMode("practice");
						practice.init(practiceprops);
					}
					if (mode === "read" || mode === "shadow") {
						// Initialise the Cloud Poodll recorder after the template is rendered.
						// The recorder div is now in the DOM, so we can initialise it.
						var readprops = dd.getJsPropsForMode("read");
						read.init(readprops);
					}
					if (mode === "quiz") {
						// quizhelper already init'd in setupquiz; it draws into quizcontainer but ..
						// when activitycontroller init calls setupquiz the quiz html is not there yet.
						// So lets try here.
						dd.setupquiz();
					}

					// Scroll/focus like a full page.
					$view.attr("tabindex", "-1")[0].focus({ preventScroll: true });
					window.scrollTo({ top: 0, behavior: "instant" });

					// Update URL.
					dd.pushModeToUrl(mode);
				})
				.catch(notification.exception);
		},

		showHome: function () {
			var dd = this;
			var $home = dd.controls.homecontainer;
			var $view = dd.controls.modeview;

			$view.addClass("d-none").attr("hidden", true).empty();
			$home.removeClass("d-none").attr("hidden", false);
			dd.controls.activityheader.removeClass("d-none").attr("hidden", false);

			// Update the progress bar when returning home
			dd.updateProgressBar();

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
			var stepsOrder = [
				"step_listen",
				"step_practice",
				"step_shadow",
				"step_read",
				"step_quiz",
				"step_report",
			];
			var stepsComplete = dd.activitydata.stepscomplete || {};
			var stepsEnabled = dd.activitydata.stepsenabled || {};
			var stepsMapping = dd.activitydata.steps || {}; // Maps canonical keys to numeric values.

			if (!$container || !$container.length) {
				console.error("Container not found.");
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
				if (step === "step_report") {
					continue;
				}
				if (!(stepsComplete[step] === true || stepsComplete[step] === "true")) {
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
					console.error(
						"No canonical step key found for data-step value:",
						stepNumber,
					);
					return true;
				}
				// Only process if enabled.
				if (!stepsEnabled[stepKey]) {
					return true;
				}
				var status;
				var isComplete =
					stepsComplete[stepKey] === true || stepsComplete[stepKey] === "true";
				if (isComplete) {
					status = "completed";
				} else if (stepKey === firstIncomplete) {
					status = "in-progress";
				} else {
					status = "upcoming";
				}
				renderCallback($elem, status, stepKey);
			});
		},

		d_show: function (els) {
			// If el is a jquery object get the first element.
			if ((!els) instanceof jQuery) {
				els = [els];
			}

			for (var i = 0; i < els.length; i++) {
				var el = els[i];

				if (el.classList.contains("d-none")) {
					el.classList.remove("d-none");
					el.classList.add("d-flex");
				} else if (el.classList.contains("hidden")) {
					el.classList.remove("hidden");
					el.classList.add("visible");
				} else {
					$(el).show();
				}
			}
		},

		d_hide: function (els) {
			// If el is a jquery object get the first element.
			if ((!els) instanceof jQuery) {
				els = [els];
			}

			for (var i = 0; i < els.length; i++) {
				var el = els[i];

				if (el.classList.contains("d-flex")) {
					el.classList.remove("d-flex");
					el.classList.add("d-none");
				} else if (el.classList.contains("visible")) {
					el.classList.remove("visible");
					el.classList.add("hidden");
				} else {
					$(el).hide();
				}
			}
		},

		updateBigButtonMenuModeStatus: function () {
			var dd = this;
			var stepsOpen = dd.activitydata.stepsopen || {};
			dd.updateStepsStatus(
				dd.controls.menubuttonscontainer,
				"li.mode-chooser",
				function ($elem, status, stepKey) {
					var $iconImg = $elem.find(".nav-status-icon");
					if ($iconImg.length) {
						var iconName, altText;
						var isOpen =
							stepsOpen[stepKey] !== undefined && stepsOpen[stepKey] !== false;

						if (status === "completed") {
							iconName = "checked";
							altText = "Complete";
						} else if (isOpen) {
							// If step is open but not complete, show current icon.
							iconName = "current";
							altText = "In progress";
						} else {
							// Step is not open yet, show locked.
							iconName = "locked";
							altText = "Locked";
						}
						var iconUrl = M.util.image_url(iconName, "mod_readaloud");
						$iconImg.attr("src", iconUrl);
						$iconImg.attr("alt", altText);

						// Update CSS classes on the li element to match the icon state.
						if (status === "completed") {
							$elem.removeClass("locked current").addClass("completed");
						} else if (isOpen) {
							$elem.removeClass("locked completed").addClass("current");
						} else {
							$elem.removeClass("current completed").addClass("locked");
						}
					}
				},
			);
		},

		updateProgressBar: function () {
			var dd = this;
			var stepsEnabled = dd.activitydata.stepsenabled || {};
			var stepsComplete = dd.activitydata.stepscomplete || {};
			var hasquizquestions =
				dd.activitydata.quizdata && dd.activitydata.quizdata.length > 0;

			var enabledCount = 0;
			var completedCount = 0;

			// Count enabled steps (excluding step_report)
			for (var stepKey in stepsEnabled) {
				if (
					stepKey === "step_report" ||
					stepsEnabled[stepKey] === 0 ||
					(stepKey === "step_quiz" && !hasquizquestions)
				) {
					continue;
				}
				enabledCount++;

				if (stepsComplete[stepKey] === true) {
					completedCount++;
				}
			}

			// Calculate percentage
			var percentage =
				enabledCount > 0 ? (completedCount / enabledCount) * 100 : 0;
			var percentageRounded = Math.round(percentage * 100) / 100; // Round to 2 decimals
			var percentageLabel = percentageRounded.toFixed(2) + " %";

			// Update the progress bar in the DOM
			var $progressBar = $(
				".mod_readaloud-activity-progress-bar .progress-bar",
			);
			var $percentLabel = $("[data-percent-label]");

			if ($progressBar.length) {
				$progressBar.css("width", percentageRounded + "%");
				$progressBar.attr("aria-valuenow", percentageRounded);
			}

			if ($percentLabel.length) {
				$percentLabel.text(percentageLabel);
			}
		},

		isandroid: function () {
			if (/Android/i.test(navigator.userAgent)) {
				return true;
			} else {
				return false;
			}
		},
	}; // End of returned object.
}); // Total end.
