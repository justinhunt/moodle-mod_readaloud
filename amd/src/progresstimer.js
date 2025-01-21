/* jshint ignore:start */
// Uses CommonJS, AMD or browser globals to create a jQuery plugin.
(function(factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof module === 'object' && module.exports) {
        // Node/CommonJS
        module.exports = function(root, jQuery) {
            if (jQuery === undefined) {
                // require('jQuery') returns a factory that requires window to
                // build a jQuery instance, we normalize how we use modules
                // that require this pattern but the window provided is a noop
                // if it's defined (how jquery works)
                if (typeof window !== 'undefined') {
                    jQuery = require('jquery');
                }
                else {
                    jQuery = require('jquery')(root);
                }
            }
            factory(jQuery);
            return jQuery;
        };
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function ($) {
    $.fn.progressTimer = function (options) {
        var settings = $.extend({}, $.fn.progressTimer.defaults, options);

        this.each(function () {
            $(this).empty();
            var barContainer = $("<div>").addClass("progress active").css('height', settings.height);
            var bar = $("<div>").addClass("progress-bar").addClass(settings.baseStyle)
                .attr("role", "progressbar")
                .attr("aria-valuenow", "0")
                .attr("aria-valuemin", "0")
                .attr("aria-valuemax", settings.timeLimit);

            bar.appendTo(barContainer);
            barContainer.appendTo($(this));

            var start = new Date();
            var limit = settings.timeLimit * 1000;
            var interval = window.setInterval(function () {
                var elapsed = new Date() - start;
                bar.width(((elapsed / limit) * 100) + "%");

                if (limit - elapsed <= 5000) {
                    bar.removeClass(settings.baseStyle)
                        .removeClass(settings.completeStyle)
                        .addClass(settings.warningStyle);
                }

                if (elapsed >= limit) {
                    window.clearInterval(interval);

                    bar.removeClass(settings.baseStyle)
                        .removeClass(settings.warningStyle)
                        .addClass(settings.completeStyle);

                    settings.onFinish.call(this);
                }

            }, 250);

            $(this).attr('timer', interval);
        });

        return this;
    };

    $.fn.progressTimer.defaults = {
        action: '',
        height: '5px',
        timeLimit: 60,// Total number of seconds
        warningThreshold: 5,// Seconds remaining triggering switch to warning color
        onFinish: function() {},// Invoked once the timer expires
        baseStyle: 'bg-danger progress-bar progress-bar-animated',// Bootstrap progress bar style at the beginning of the timer
        warningStyle: 'bg-danger progress-bar progress-bar-animated', // Bootstrap progress bar style in the warning phase
        completeStyle: 'bg-danger progress-bar progress-bar-animated progress-bar-complete'// Bootstrap progress bar style at completion of timer
    };
}));
/* jshint ignore:end */
