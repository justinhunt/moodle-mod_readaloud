define(['jquery', 'core/log', 'mod_readaloud/tether'], function ($, log, Tether) {
    "use strict"; // jshint ;_;

    /*
    This file is a dependency of loader that is called by popover to ensure correct things are loaded in the right sequence
     */

    log.debug('Readaloud deps: initialising');
    //from moodle 3,9 we no longer need to do this
    if(M.cfg.version<2020061500) {
        window.Tether = Tether;
    }
    return {};//end of return value
});