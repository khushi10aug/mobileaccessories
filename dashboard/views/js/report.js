$(document).ready(function () {
    searchRecords(document.frmRecordSearch);
});

$(document).on("click", ".headerColumnJs", function (e) {
    var fld = $(this).attr('data-field');
    var frm = document.frmReportSearchPaging;
    document.getElementById("sortBy").value = fld;
    $(frm.sortBy).val(fld);
    if (document.getElementById("sortOrder").value == 'ASC') {
        $(frm.sortOrder).val('DESC');
        document.getElementById("sortOrder").value = 'DESC';
    } else {
        $(frm.sortOrder).val('ASC');
        document.getElementById("sortOrder").value = 'ASC';
    }
    searchRecords(frm, false);
});

$(function () {
    $("#sortable").sortable({
        helper: fixWidthHelper,
        handle: ".handleJs",
        start: fixPlaceholderStyle,
        stop: function () {
            reloadList(false);
        }
    }).disableSelection();
});

(function () {
    var dv = '#listingDiv';

    goToSearchPage = function (page) {
        if (typeof page == undefined || page == null) {
            page = 1;
        }
        var frm = document.frmReportSearchPaging;
        $(frm.page).val(page);
        searchRecords(frm);
    };
    redirectBack = function (redirecrt) {
        window.location = redirecrt;
    }
    reloadList = function (withloader) {
        var frm = document.frmReportSearchPaging;
        searchRecords(frm, withloader);
    };

    searchRecords = function (frm, withloader) {
        setColumnsData(frm);
        var data = '';
        if (frm) {
            data = fcom.frmData(frm);
        }

        if (typeof withloader == 'undefined' || withloader != false) {
            $(dv).prepend(fcom.getLoader());
        }

        fcom.ajax(fcom.makeUrl(controllerName, 'search'), data, function (res) {
            fcom.removeLoader();
            $(dv).html(res);
        });
    };

    exportReport = function () {
        if (!$('#frmExport').validate()) { return; }
        setColumnsData(document.frmRecordSearch);
        setBatch(document.frmRecordSearch);
        document.frmRecordSearch.action = fcom.makeUrl(controllerName, 'search', ['export']);
        document.frmRecordSearch.submit();
    }

    exportForm = function (displayInPopup = false, dialogClass = '') {
        if (false === checkControllerName()) {
            return false;
        }
        fcom.updateWithAjax(fcom.makeUrl(controllerName, "form"), "", function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, displayInPopup, dialogClass);
            fcom.removeLoader();
        });
    }

    setBatch = function (frm) {
        if ("undefined" == typeof frm) {
            return;
        }
        $('<input>').attr({
            type: 'hidden',
            name: 'batch_count',
            value: $('#batch_count').val()
        }).appendTo(frm);
        $('<input>').attr({
            type: 'hidden',
            name: 'batch_number',
            value: $('#batch_number').val()
        }).appendTo(frm);
    }

    clearSearch = function () {
        document.frmRecordSearch.reset();
        $("input:checkbox[name=reportColumns]:checked").each(function () {
            if ($(this).attr('disabled') != 'disabled') {
                $(this).prop('checked', false);
            }
        });
        searchRecords(document.frmRecordSearch);
    };

    setColumnsData = function (frm) {
        reportColumns = [];
        $("input:checkbox[name=reportColumns]:checked").each(function () {
            reportColumns.push($(this).val());
        });

        $(frm.reportColumns).val(JSON.stringify(reportColumns));
    };
})();