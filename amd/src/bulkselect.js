define([], function () {

    var table = document.querySelector('table#mod_readaloud_qpanel');

    table.addEventListener('change', function () {
        var btn = document.querySelector('#mod_readaloud_deleteconfirmation');
        if (table.querySelectorAll(':checked').length > 0) {
            btn.classList.remove('d-none');
        } else {
            btn.classList.add('d-none');
        }
    });
});