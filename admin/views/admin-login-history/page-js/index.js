(function () {
    viewLog = function (id) {
        var data = 'recordId=' + id;
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'viewLog', []), data, function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html);
            fcom.removeLoader();
        });
    };
})();
