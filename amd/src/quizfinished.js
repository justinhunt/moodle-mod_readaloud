define(['jquery', 'core/log','core/str','core/modal_events',
        'mod_readaloud/definitions','core/templates', 'mod_readaloud/correctionsmarkup'],
    function($, log, str, ModalEvents, def, templates, correctionsmarkup) {
  "use strict"; // jshint ;_;

  /*
  This file is to manage the quiz stage
   */

  log.debug('Poodll Readaloud Quiz Finished page: initialising');

  return {

    strings: {},
    controls: {},

    //for making multiple instances
      clone: function () {
          return $.extend(true, {}, this);
     },

    init: function() {
        this.init_strings();
        this.register_controls();
        this.register_events();
        this.reset_instructions_header(this.strings.quizfinishedhelp);

    },

    register_controls: function(){
        this.controls.quizfinishedcontainer = $('#mod_readaloud_quiz_finished');
        this.controls.quizitemscontainer = $('#mod_readaloud_quiz_items_cont');
        this.controls.togglemodalbutton = $('#mod_readaloud_showquiztryagainmodal');
        this.controls.instructionsheader = $("#mod_readaloud_quizmode .mod_readaloud-activity-header-inpage .mod_readaloud_instructions");
    },

    reset_instructions_header: function(instructions) {
        // Change the instructions header to quiz finished instructions
        this.controls.instructionsheader.text(instructions);
    },

    register_events: function(){
        var that = this;

        $('body').on('click','.mod_readaloud_quiztryagainmodal .mrq_doreattempt',function(e) {

            e.preventDefault();
            log.debug('Re-attempting quiz');
            that.controls.quizfinishedcontainer.hide();
            that.controls.quizitemscontainer.show();
            // Hide the modal by simulating a click on the toggle button
            var togglemodalbutton = $('#mod_readaloud_showquiztryagainmodal');
            togglemodalbutton.click();

        });

        $('body').on('click','.mod_readaloud_quiztryagainmodal .mrq_docancelreattempt',function(e) {

            e.preventDefault();
            log.debug('Not Re-attempting quiz');
            // Do nothing.. just close the modal by simulating an esc keypress.
            // Hide the modal by simulating a click on the toggle button
            // Hide the modal by simulating a click on the toggle button
            var togglemodalbutton = $('#mod_readaloud_showquiztryagainmodal');
            togglemodalbutton.click();

        });


      $('body').on('click','.mod_readaloud_finishedanswerdetailslink',function(e) {
          e.preventDefault();
          var type = $(this).data('type');
          var resultstemplate = $(this).data('resultstemplate');
          var resultsdata = $(this).data('resultsdata');
          var thetarget = $(this).data('target');
          if(thetarget === undefined){return;}
          var resultsbox = $('#' + thetarget);
          if(resultsbox === undefined){return;}
          if(resultsbox.is(':visible')){
              resultsbox.hide();
              return;
          }
          if(!resultsbox.is(':visible') && resultsbox.html().length > 0){
              resultsbox.show();
              return;
          }
          //otherwise load the results and show the box
          templates.render('mod_readaloud/' + resultstemplate, resultsdata).then(
              function(html,js){
                  resultsbox.html(html);
                  //do corrections markup .. if we have them
                  if(resultsdata.hasOwnProperty('grammarerrors')){
                      correctionsmarkup.init({ "correctionscontainer": resultsbox,
                          "grammarerrors": resultsdata.grammarerrors,
                          "grammarmatches": resultsdata.grammarmatches,
                          "insertioncount": resultsdata.insertioncount});
                  }
                  //do passage results
                  if(resultsdata.hasOwnProperty('unreached')){
                      passagereading.doComparisonMarkup(resultsdata.comparison,thetarget);
                  }

                  //show and hide
                  resultsbox.show();
                  templates.runTemplateJS(js);
              }
          );// End of templates
      });
    },

    init_strings: function(){
        var that = this;
        // set up strings
        str.get_strings([
            {"key": "reattempttitle",       "component": 'mod_readaloud'},
            {"key": "reattemptbody",        "component": 'mod_readaloud'},
            {"key": "reattempt",           "component": 'mod_readaloud'},
            {"key": "quizfinishedhelp",    "component": 'mod_readaloud'},
            {"key": "quizhelp",    "component": 'mod_readaloud'},

        ]).done(function(s) {
            var i = 0;
            that.strings.reattempttitle = s[i++];
            that.strings.reattemptbody = s[i++];
            that.strings.reattempt = s[i++];
            that.strings.quizfinishedhelp = s[i++];
            that.strings.quizhelp = s[i++];
        });
    }
  };
});