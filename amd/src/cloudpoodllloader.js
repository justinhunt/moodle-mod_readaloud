define(['jquery', 'core/log', 'mod_readaloud/cloudpoodll'], function ($, log, cloudpoodll) {

    return {
        callbacks: [],
        init: function (recorderid, thecallback) {
            var that = this;
            cloudpoodll.createRecorder(recorderid);

            this.callbacks.push({recorderid: recorderid, callback: thecallback});
            if(this.callbacks.length===1) {
                cloudpoodll.initEvents();
                cloudpoodll.theCallback = function (m) {
                   for (var i = 0; i < that.callbacks.length; i++) {
                       if (m.id === that.callbacks[i].recorderid) {
                           that.callbacks[i].callback(m);
                       }
                   }
                };
            }

            $("iframe").on("load", function () {
                $(".mod_readaloud_recording_cont").css('background-image', 'none');
            });
        }
    }
});