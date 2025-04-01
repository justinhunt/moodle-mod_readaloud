define(['jquery', 'core/log','mod_readaloud/definitions'],
    function ($, log, def) {
    "use strict"; // jshint ;_;
    /*
    This file does small report
     */

    log.debug('Click to hear: initialising');

    return {
        //controls
        controls: {},
        //the passage (jquery object)
        passage: null,
        allwords: null,
        allspaces: null,

        //class definitions
        cd: {
            wordclass: def.wordclass,
            spaceclass: def.spaceclass,
            badwordclass: def.badwordclass,
            endspaceclass: def.endspaceclass,
            unreadwordclass: def.unreadwordclass,
            unreadspaceclass: def.unreadspaceclass,
            aiunmatched: def.aiunmatched,
            passagecontainer: def.passagecontainer,
            fullreportcontainer: def.fullreportcontainer,
        },

        //init the module
        init: function(passage){
            this.passage=passage;
            this.allwords = this.passage.find('.' + this.cd.wordclass);
            this.allspaces = this.passage.find('.' + this.cd.spaceclass);
        },

        clear_markup: function () {
            var that = this;
            that.allspaces.removeClass(that.cd.endspaceclass);
            that.allspaces.removeClass(that.cd.unreadspaceclass);
            that.allwords.removeClass(that.cd.aiunmatched);
            that.allwords.removeClass(that.cd.unreadwordclass);
            that.allwords.removeClass(that.cd.badwordclass);
        },

        get_word: function (wordnumber) {
            return this.passage.find('.' + this.cd.wordclass + '[data-wordnumber=' + wordnumber + ']');
        },

        get_space: function (wordnumber) {
            return this.passage.find('.' + this.cd.spaceclass + '[data-wordnumber=' + wordnumber + ']');
        },

        markup_passage: function (sessionmatches,sessionerrors, sessionendword) {
           // this.markup_aiunmatchedwords(sessionmatches, sessionendword);
            this.markup_badwords(sessionerrors);
            this.markup_endword(sessionendword);
            this.mark_unreadwords(sessionendword);
        },

        markup_badwords: function(errorwords) {
            log.debug('doing bad words');
            var that = this;
            $.each(errorwords, function (index) {
                log.debug(' bad word: index: ' + index + ' wordnumber: ' + errorwords[index].wordnumber);
                that.get_word(errorwords[index].wordnumber).addClass(that.cd.badwordclass);
            });
        },

        markup_endword: function (endwordnumber) {
            var that = this;
            that.get_space(endwordnumber).addClass(this.cd.endspaceclass);
        },

        //mark up all unmatched words as aiunmatched
        markup_aiunmatchedwords: function (sessionmatches, endwordnumber) {
            var that = this;
            if (sessionmatches) {
                var prevmatch = 0;
                $.each(sessionmatches, function (index, match) {
                    var unmatchedcount = index - prevmatch - 1;
                    if (unmatchedcount > 0) {
                        for (var errorword = 1; errorword < unmatchedcount + 1; errorword++) {
                            var wordnumber = prevmatch + errorword;
                            that.get_word(wordnumber).addClass(that.cd.aiunmatched);
                        }
                    }
                    prevmatch = parseInt(index);
                });

                //mark all words from last matched word to the end as aiunmatched
                for (var errorwordnumber = prevmatch + 1; errorwordnumber <= endwordnumber; errorwordnumber++) {
                    that.get_word(errorwordnumber).addClass(that.cd.aiunmatched);
                }
            }
        },

        markup_aiunmatchedspaces: function () {
            var that = this;
            $('.' + this.cd.wordclass + '.' + this.cd.aiunmatched).each(function (index) {
                var wordnumber = parseInt($(this).attr('data-wordnumber'));
                if (that.get_word(wordnumber + 1).hasClass(that.cd.aiunmatched)) {
                    that.get_word(wordnumber).addClass(that.cd.aiunmatched);
                }
            });
        },

        mark_unreadwords: function (endwordnumber) {
            var that = this;
            that.allwords.each(function (index) {
                var wordnumber = $(this).attr('data-wordnumber');
                var thespace = that.get_space(wordnumber);

                if (Number(wordnumber) > Number(endwordnumber)) {
                    $(this).addClass(that.cd.unreadwordclass);
                    thespace.addClass(that.cd.unreadspaceclass);

                    //this will clear badwords after the endmarker
                    $(this).removeClass(that.cd.badwordclass);

                } else {
                    $(this).removeClass(that.cd.unreadwordclass);
                    thespace.removeClass(that.cd.unreadspaceclass);
                }
            });
        },
    };//end of return value
});