/* Refresh page on on browser page. */
var perfEntries = performance.getEntriesByType("navigation");
if ('back_forward' === perfEntries[0].type) {
    location.reload(true);
}

$(document).on("click", ".headerColumnJs", function (e) {
    if (1 == $('.listingRecordJs tr').length) {
        return;
    }

    var fld = $(this).attr("data-field");
    var frm = document.frmRecordSearchPaging;
    var sortingUp =
        '<svg class="svg" width="18" height="18"><use xlink: href = "' +
        siteConstants.webroot +
        'images/retina/sprite-actions.svg#arrow-up"></use > </svg>';
    var sortingDown =
        '<svg class="svg" width="18" height="18"><use xlink: href = "' +
        siteConstants.webroot +
        'images/retina/sprite-actions.svg#arrow-down"></use > </svg>';
    var sortIcn = "";
    var sortcls = "";

    document.getElementById("sortBy").value = fld;
    if ("undefined" != typeof frm) {
        $(frm.sortBy).val(fld);
        $(frm.page).val(1);
    }

    $(".headerColumnJs").removeClass("sorting_asc sorting_desc");
    if (document.getElementById("sortOrder").value == "ASC") {
        if ("undefined" != typeof frm) {
            $(frm.sortOrder).val("DESC");
        }
        document.getElementById("sortOrder").value = "DESC";
        sortIcn = sortingUp;
        sortcls = "sorting_asc";
    } else {
        if ("undefined" != typeof frm) {
            $(frm.sortOrder).val("ASC");
        }
        document.getElementById("sortOrder").value = "ASC";
        sortIcn = sortingDown;
        sortcls = "sorting_desc";
    }

    $(this).addClass(sortcls);
    searchRecords(frm);
});

$(function () {
    $("#sortable").sortable({
		handle: ".handleJs",
        stop: function () {
            reloadList();
        }
    });
});

/* Reset result on clear(cross) icon on keyword search field. */
$(document).on("search", "input[type='search']", function () {
    if ("" == $(this).val()) {
        searchRecords(document.frmRecordSearch);
    }
});
/* Reset result on clear(cross) icon on keyword search field. */

$(document).on("click", ".advSrchToggleJs", function (e) {
    var elm = $('.advSrchBtnJs').find('.submitBtnJs');
    if ($(this).attr("aria-expanded") == 'true') {
        elm.attr("disabled", true);
    } else {
        elm.attr("disabled", false);
    }
});

$(document).on("click", ".resetModalFormJs", function (e) {
    if ($.ykmodal.isSideBarView()) {
        $.ykmodal(fcom.getLoader());
    }

    var onClear = $(".modalFormJs").data("onclear");
    if ('undefined' != typeof onClear) {
        eval(onClear);
    } else if (0 < $("." + $.ykmodal.element + " .navTabsJs .nav-link").length) {
        $("." + $.ykmodal.element + " .navTabsJs .nav-link.active").click();
    }
});

$(document).on("click", ".clearFormJs", function (e) {
    var form = $(this).closest('form');
    form[0].reset();
});

$(document).on("click", ".submitFormBtnJs", function (e) {
    $(this).closest('form').submit();
});

/* Sidebar auto open if accidently close modal popup. Retain previous position. */
var autoOpenSideBar = true;
$(document).on("hidden.bs.modal", "#modalBoxJs", function () {
    if (autoOpenSideBar) {
        $.ykmodal.show();
    }
});

(function () {
    let dv = ".listingRecordJs";
    let paginationDv = ".listingPaginationJs";
    let listingTableJs = ".listingTableJs";
    let isAjaxRunning = false;

    checkControllerName = function () {
        if ("undefined" == typeof controllerName || "" == controllerName) {
            fcom.displayErrorMessage(langLbl.controllerNameRequired);
            return false;
        }
        return true;
    };

    goToSearchPage = function (page) {
        if (typeof page == undefined || page == null) {
            page = 1;
        }
        var frm = document.frmRecordSearchPaging;
        $(frm.page).val(page);
        /* if ("undefined" != typeof document.frmRecordSearch.page) {
            document.frmRecordSearch.page.value = 1;
        } */
        searchRecords(frm, page);
    };

    loadMore = function (callback = '') {
        if (false === checkControllerName()) {
            return false;
        }

        var frm = document.frmLoadMoreRecordsPaging;
        var page = 1;
        if ("undefined" != typeof frm.page.value && "" != frm.page.value && 0 < frm.page.value) {
            page += parseInt(frm.page.value);
        }

        $(frm.page).val(page);
        var reference = $(".appendRowsJs .rowJs:last").data("reference");
        if ("undefined" != typeof reference && "undefined" != typeof frm.reference) {
            $(frm.reference).val(reference);
        }

        var data = fcom.frmData(frm);

        var loadMoreBtn = $('.loadMoreBtnJs');
        var btnText = loadMoreBtn.text();
        loadMoreBtn.html(fcom.getRowSpinner());

        fcom.updateWithAjax(fcom.makeUrl(controllerName, "getRows"), data, function (rows) {
            fcom.closeProcessing();
            $(".appendRowsJs").append(rows.html);
            loadMoreBtn.html(btnText);

            var similarElement = '.appendRowsJs [data-reference="' + reference + '"]';
            var lastSimilar = $(similarElement + ':last');
            if (1 < $(similarElement).length) {
                var li = lastSimilar.find('.ulJs').html();
                lastSimilar.remove();
                $(similarElement + ':last ul.ulJs').append(li);
            }

            if (page == frm.pageCount.value) {
                $(".loadMorePaginationJs").remove();
            }

            if ("" != callback) {
                window[callback]();
            }
        });
    };

    setPageSize = function (pageSize) {
        var frm = document.frmRecordSearch;
        $(frm.pageSize).val(pageSize);
        searchRecords(frm);
    };

    redirectBack = function (redirecrt) {
        window.location = redirecrt;
    };

    reloadList = function () {
        searchRecords(document.frmRecordSearchPaging);
    };

    searchRecords = function (frm, page) {
        var arr = ['ThemeColor', 'Configurations'];
        if (false === checkControllerName() || arr.indexOf(controllerName) > -1) {
            return false;
        }

        setColumnsData(frm);
        var data = "";
        if (frm) {
            data = fcom.frmData(frm);
        }
        data = data + '&loadPagination=0';

        $(listingTableJs).prepend(fcom.getLoader());

        fcom.ajax(fcom.makeUrl(controllerName, "search"), data, function (res) {
            if (0 == res.status) {
                fcom.displayErrorMessage(res.msg);
                return;
            }
            if (res.headSection) {
                $('.tableHeadJs').replaceWith(res.headSection);
            }
            $(dv).replaceWith(res.listingHtml);
            $(paginationDv).replaceWith(res.paginationHtml);
            fcom.removeLoader();

            var pageVal = $(document.frmRecordSearchPaging.page).val();
            if (typeof pageVal == 'undefined' || pageVal != page) {
                loadPagination(document.frmRecordSearchPaging, page);
            }
        }, { fOutMode: 'json' });
    };

    loadPagination = function (frm, page) {
        if (typeof page == undefined) {
            page = 1;
        }
        $(frm.page).val(page);

        var arr = ['ThemeColor', 'Configurations'];
        if (false === checkControllerName() || arr.indexOf(controllerName) > -1) {
            return false;
        }
        setColumnsData(frm);
        var data = "";
        if (frm) {
            data = fcom.frmData(frm);
        }
        data = data + '&loadPagination=1';
        $(paginationDv).html('<div class="card-foot">Processing...</div>');
        fcom.ajax(fcom.makeUrl(controllerName, "search"), data, function (res) {
            if (0 == res.status) {
                return;
            }
            fcom.removeLoader();
            $(paginationDv).replaceWith(res.paginationHtml);
            $(".selectAllJs").prop("checked", false);
            if (0 < $(".listingRecordJs .noRecordFoundJs").length) {
                $(".selectAllJs").prop("disabled", "disabled");
            } else {
                $(".selectAllJs").removeAttr("disabled");
            }
        }, { fOutMode: 'json' });
    };

    exportRecords = function () {
        if (false === checkControllerName()) {
            return false;
        }
        if (!$('#frmExport').validate()) { return; }
        setColumnsData(document.frmRecordSearch);
        setBatch(document.frmRecordSearch);
        document.frmRecordSearch.action = fcom.makeUrl(controllerName, "search", [
            "export",
        ]);
        document.frmRecordSearch.submit();
    };

    exportForm = function (displayInPopup = false, dialogClass = ''){
        if (false === checkControllerName()) {
            return false;
        }
        fcom.updateWithAjax(fcom.makeUrl(controllerName, "form"), "", function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, displayInPopup, dialogClass);
            fcom.removeLoader();
        });
    }

    setBatch = function (frm){
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

    clearSearch = function (loadRowsOnly = false) {
        document.frmRecordSearch.reset();
        $('input, select', document.frmRecordSearch).not(':hidden').val('');
        $("input:checkbox[name=listingFld]:checked").each(function () {
            if ($(this).attr("disabled") != "disabled") {
                $(this).prop("checked", false);
            }
        });
        $('.select2-hidden-accessible').val('').trigger('change');
        searchRecords(document.frmRecordSearch, 0);

    };

    setColumnsData = function (frm) {
        if ("undefined" == typeof frm) {
            return;
        }

        listingColumns = [];
        $("input:checkbox[name=listingFld]:checked").each(function () {
            listingColumns.push($(this).val());
        });

        $(frm.listingColumns).val(JSON.stringify(listingColumns));
    };

    deleteRecord = function (recordId) {
        if (false === checkControllerName()) {
            return false;
        }

        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        data = "recordId=" + recordId;
        fcom.updateWithAjax(
            fcom.makeUrl(controllerName, "deleteRecord"),
            data,
            function (t) {
                fcom.displaySuccessMessage(t.msg);
                reloadList();
            }
        );
    };

    deleteSelected = function () {
        if (!confirm(langLbl.confirmDelete)) {
            return false;
        }
        $("form.actionButtonsJs")
            .attr("action", fcom.makeUrl(controllerName, "deleteSelected"))
            .submit();
    };

    addNew = function (displayInPopup = false, dialogClass = '') {
        if (false === checkControllerName()) {
            return false;
        }
        fcom.resetEditorInstance();

        /* Uncheck all if checked. */
        $(".selectAllJs, .selectItemJs").prop("checked", false)

        fcom.updateWithAjax(fcom.makeUrl(controllerName, "form"), "", function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, displayInPopup, dialogClass);
            fcom.removeLoader();
        });
    };

    editRecord = function (recordId, displayInPopup = false, dialogClass = '') {
        if (false === checkControllerName()) {
            return false;
        }
        fcom.resetEditorInstance();
        data = "recordId=" + recordId;
        fcom.updateWithAjax(fcom.makeUrl(controllerName, "form"), data, function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, displayInPopup, dialogClass);
            fcom.removeLoader();
        });
    };

    editLangData = function (recordId, langId, autoFillLangData = 0, dialogClass = '') {
        if (false === checkControllerName()) {
            return false;
        }
        fcom.resetEditorInstance();
        data = "recordId=" + recordId + "&langId=" + langId;
        var isPopupView = ($.ykmodal.isAdded() && !$.ykmodal.isSideBarView());

        $.ykmodal(fcom.getLoader(), isPopupView);
        fcom.updateWithAjax(
            fcom.makeUrl(controllerName, "langForm", [autoFillLangData]),
            data,
            function (t) {
                fcom.closeProcessing();
                $.ykmodal(t.html, isPopupView, dialogClass);
                fcom.removeLoader();
            }
        );
    };

    updateStatus = function (e, obj, recordId, status, callback = "") {
        if (false === checkControllerName()) {
            return false;
        }
        fcom.displayProcessing();
        e.stopPropagation();

        var oldStatus = $(obj).attr("data-old-status");
        $(listingTableJs).prepend(fcom.getLoader());

        if (1 > recordId) {
            $(obj).prop("checked", 1 == oldStatus);
            fcom.displayErrorMessage(langLbl.invalidRequest);
            fcom.removeLoader();
            return false;
        }

        data = "recordId=" + recordId + "&status=" + status;
        fcom.ajax(fcom.makeUrl(controllerName, "updateStatus"), data,
            function (ans) {
                fcom.removeLoader();
                fcom.closeProcessing();
                $(obj).prop("checked", 1 == status);
                if (ans.status == 1) {
                    fcom.displaySuccessMessage(ans.msg);
                    $(obj).attr({ onclick: "updateStatus(event, this, " + recordId + ", " + oldStatus + ", '" + callback + "')", "data-old-status": status });
                    if ("" != callback) {
                        eval(callback);
                    }
                } else {
                    $(obj).prop("checked", 1 == oldStatus);
                    fcom.displayErrorMessage(ans.msg);
                }
                fcom.removeLoader();
            }, { 'fOutMode': 'json' }
        );
    };

    saveRecord = function (frm, callback = '') {
        if (false === checkControllerName() || true === isAjaxRunning) {
            return false;
        }
        if (!$(frm).validate()) { return; }
        $.ykmodal(fcom.getLoader(), !$.ykmodal.isSideBarView());
        let onSubmitFn = $(frm).attr('onsubmit');
        $(frm).attr('onsubmit', 'return false;');
        isAjaxRunning = true;
        $("." + $.ykmodal.element + ' .submitBtnJs').attr('disabled', 'disabled');
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'setup'), data, function (t) {
            isAjaxRunning = false;
            $("." + $.ykmodal.element + ' .submitBtnJs').removeClass('loading').removeAttr('disabled');
            fcom.removeLoader();
            if ('undefined' != typeof t.msg) {
                fcom.displaySuccessMessage(t.msg);
            }

            reloadList();
            if (t.langId > 0) {
                let extraLangTabClass = $('.modalDialogJs').hasClass('modal-dialog-vertical-md') ? 'modal-dialog-vertical-md' : '';
                editLangData(t.recordId, t.langId, 0, extraLangTabClass);
            } else if ("openMediaForm" in t) {
                mediaForm(t.recordId);
            } else if("banners" in t){
                banners(t.recordId);
            } else if ("recordForm" in t) {
                recordForm(t.recordId, frm.collection_type.value);
            } else if ('' != callback) {
                window[callback](t.recordId);
            } else {
                $(frm).attr('onsubmit', onSubmitFn);
            }
        });

        setTimeout(() => {
            if (isAjaxRunning) {
                $(frm).attr('onsubmit', onSubmitFn);
                $("." + $.ykmodal.element + ' .submitBtnJs').removeClass('loading').removeAttr('disabled');
            }
            isAjaxRunning = false;
            $(frm).attr('onsubmit', onSubmitFn);
        }, 5000);
    };

    saveLangData = function (frm) {
        if (false === checkControllerName()) {
            return false;
        }
        if (!$(frm).validate()) {
            return;
        }
        $.ykmodal(fcom.getLoader(), !$.ykmodal.isSideBarView());

        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl(controllerName, "langSetup"), data, function (t) {
            fcom.displaySuccessMessage(t.msg);
            fcom.removeLoader();

            if (t.langId == langLbl.defaultFormLangId) {
                reloadList();
            }

            if (t.langId > 0) {
                let extraLangTabClass = $('.modalDialogJs').hasClass('modal-dialog-vertical-md') ? 'modal-dialog-vertical-md' : '';
                editLangData(t.recordId, t.langId, 0 , extraLangTabClass);
            } else if ("openMediaForm" in t) {
                mediaForm(t.recordId);
            }
        });
    };

    selectAll = function (element) {
        var obj = $(element);
        if (1 > $(".listingRecordJs .selectItemJs:not(:disabled)").length) {
            obj.prop("disabled", "disabled");
            obj.prop("checked", false);
            return false;
        } else {
            obj.removeAttr("disabled");
        }

        $(".listingRecordJs .selectItemJs").each(function () {
            var tr = $(this).closest('tr');
            if (obj.prop("checked") == false) {
                $(this).prop("checked", false);
                tr.removeClass('selected');
            } else if (!$(this).hasClass('disabled')) {
                $(this).prop("checked", true);
                tr.addClass('selected');
            }
        });

        showActionsBtns();
    };

    formAction = function (frm, callback) {
        if (typeof $(".selectItemJs:checked").val() === "undefined") {
            fcom.displayErrorMessage(langLbl.atleastOneRecord);
            return false;
        }

        if (0 < $(listingTableJs).length) {
            $(listingTableJs).prepend(fcom.getLoader());
        }

        data = fcom.frmData(frm);

        fcom.updateWithAjax(frm.action, data, function (t) {
            fcom.displaySuccessMessage(t.msg);
            fcom.removeLoader();
            $(".selectAllJs").prop("checked", false);
            callback();
            showActionsBtns();
            $(".toolbarBtnJs").addClass("btn-outline-gray disabled").removeClass("btn-outline-brand selected");
        });
    };

    toggleBulkStatues = function (status, confirmMsg = "") {
        var element = "form.actionButtonsJs";
        if (1 > $(element).length) {
            fcom.displayErrorMessage(langLbl.actionButtonsClass);
            return false;
        }

        if ('' != confirmMsg && !confirm(confirmMsg)) {
            return false;
        }

        $(element).attr("action", fcom.makeUrl(controllerName, "toggleBulkStatuses"));
        $(element + " input[name='status']").val(status);
        $(element).submit();
    };

    showActionsBtns = function () {
        if (typeof $(".selectItemJs:checked").val() === "undefined") {
            $(".toolbarBtnJs").addClass("btn-outline-gray disabled").removeClass("btn-outline-brand selected");
        } else {
            $(".toolbarBtnJs").removeClass("btn-outline-gray disabled").addClass("btn-outline-brand selected");
        }
    };

    /* Media Form & Image Management */
    loadImages = function (recordId, fileType, slide_screen, langId) {
        if (false === checkControllerName()) {
            return false;
        }

        fcom.updateWithAjax(
            fcom.makeUrl(controllerName, "images", [recordId, fileType, langId, slide_screen]), "",
            function (t) {
                fcom.closeProcessing();
                fcom.removeLoader();
                if (fileType == "logo") {
                    $("#logoListingJs").html(t.html);
                    return;
                }

                $("#imageListingJs").html(t.html);
            }
        );
    };

    mediaForm = function (recordId, langId = 0, slide_screen = 1) {
        if (false === checkControllerName()) {
            return false;
        }

        fcom.updateWithAjax(fcom.makeUrl(controllerName, "media", [recordId, langId, slide_screen]), "",
            function (t) {
                fcom.closeProcessing();
                fcom.removeLoader();
                loadImages(recordId, "logo", slide_screen, langId);
                loadImages(recordId, "image", slide_screen, langId);
                $.ykmodal(t.html, !$.ykmodal.isSideBarView());
            }
        );
    };

    deleteMedia = function (recordId, fileType, afileId, slide_screen = 0, langId = 0) {
        if (false === checkControllerName()) {
            return false;
        }

        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(
            fcom.makeUrl(controllerName, "removeMedia", [
                recordId,
                fileType,
                afileId,
            ]),
            "",
            function (t) {
                fcom.displaySuccessMessage(t.msg);
                loadImages(recordId, fileType, slide_screen, langId);
                reloadList();
            }
        );
    };

    loadCropperSkeleton = function (reopenSideBarOnClose = true) {
        autoOpenSideBar = reopenSideBarOnClose;
        $("#modalBoxJs").remove();
        $("body").append(fcom.getModalBody());
        $("#modalBoxJs").modal("show");
        $.ykmodal.close();
    };

    loadImageCropper = function (inputBtn) {
        if (false === checkControllerName()) {
            return false;
        }
        if (inputBtn.files && inputBtn.files[0]) {
            var file = inputBtn.files[0];
            if (!validateFileUpload(file)) {
                return;
            }
            loadCropperSkeleton();
            $("#modalBoxJs .modal-title").text($(inputBtn).attr('data-name'));
            fcom.updateWithAjax(fcom.makeUrl(controllerName, "imgCropper"), "", function (t) {
                fcom.closeProcessing();
                $("#modalBoxJs .modal-body").html(t.body);
                $("#modalBoxJs .modal-footer").html(t.footer);

                var frmName = $(inputBtn).closest('form').attr('name');
                var options = {
                    toggleDragModeOnDblclick: false,
                    imageSmoothingQuality: "high",
                    imageSmoothingEnabled: true,
                };

                if (document[frmName].min_width != undefined && document[frmName].min_height != undefined) {
                    var minWidth = document[frmName].min_width.value;
                    var minHeight = document[frmName].min_height.value;
                    options['aspectRatio'] = minWidth / minHeight;
                    options['minCropBoxWidth'] = minWidth;
                    options['minCropBoxHeight'] = minHeight;
                    options['data'] = {
                        width: minWidth,
                        height: minHeight,
                    };
                } else {
                    options['initialAspectRatio'] = 1;
                }

                $(inputBtn).val("");
                setTimeout(function () {
                    cropImage(file, options, "uploadImages", inputBtn);
                }, 100);
                return;
            });
        }
    };

    uploadImages = function (formData) {
        if (false === checkControllerName()) {
            return false;
        }

        var frmName = formData.get("frmName");
        var frm = document.forms[frmName];
        var langId = 0;
        if ('undefined' != typeof frm.lang_id) {
            langId = frm.lang_id.value;
        }
        var slideScreen = 0;
        if ("undefined" != typeof frm.slide_screen) {
            slideScreen = frm.slide_screen.value;
        }

        var action = 'uploadMedia';
        if ("undefined" != typeof frm.dataset.action) {
            action = frm.dataset.action;
        }

        var other_data = $('form[name="' + frmName + '"]').serializeArray();
        $.each(other_data, function (key, input) {
            formData.append(input.name, input.value);
        });

        formData.append('fOutMode', 'json');
        $.ajax({
            url: fcom.makeUrl(controllerName, action),
            type: "post",
            dataType: "json",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $("#modalBoxJs .modal-body").prepend(fcom.getLoader());
            },
            success: function (ans) {
                fcom.removeLoader();
                if (ans.status == 0) {
                    fcom.displayErrorMessage(ans.msg);
                    return;
                }
                fcom.displaySuccessMessage(ans.msg);
                if (true === $.ykmodal.isAdded()) {
                    $.ykmodal.show();
                    $("#modalBoxJs").modal("hide");
                    if ("undefined" != typeof frm.dataset.callback) {
                        eval(frm.dataset.callback);
                    } else if ("undefined" != typeof frm.dataset.callbackfn) {
                        window[frm.dataset.callbackfn](ans); /* callback function */
                    } else if (0 < $(".navTabsJs").length && 0 < $("." + $.ykmodal.element + " form[name='" + frm['name'] + "'] select[name='lang_id']").length) {
                        $("." + $.ykmodal.element + " form[name='" + frm['name'] + "'] select[name='lang_id']").val(langId).change();
                    } else if (0 < $(".navTabsJs").length && 0 < $("." + $.ykmodal.element + " form[name='" + frm['name'] + "'] select[name='slide_screen']").length) {
                        $("." + $.ykmodal.element + " form[name='" + frm['name'] + "'] select[name='slide_screen']").change();
                    } else {
                        mediaForm(ans.recordId, frm.file_type.value, langId, slideScreen);
                    }
                } else {
                    mediaForm(ans.recordId, frm.file_type.value, langId, slideScreen);
                }
                reloadList();
                fcom.removeLoader();
            },
            error: function (xhr, ajaxOptions, thrownError) {
                fcom.displayErrorMessage(
                    thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText
                );
            },
        });
    };

    isInViewport = function (el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <=
            (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    };

    isElement = function (obj) {
        try {
            //Using W3 DOM2 (works for FF, Opera and Chrome)
            return obj instanceof HTMLElement;
        } catch (e) {
            //Browsers not supporting W3 DOM2 don't have HTMLElement and
            //an exception is thrown and we end up here. Testing some
            //properties that all elements have (works on IE7)
            return (
                typeof obj === "object" &&
                obj.nodeType === 1 &&
                typeof obj.style === "object" &&
                typeof obj.ownerDocument === "object"
            );
        }
    };

    closeForm = function () {
        $.ykmodal.close();
    }

    editDropZoneImages = function (obj) {
        $(obj).closest(".dropzoneContainerJs").find(".dropzoneInputJs").click();
    }

    /* Fix width of table headings. */
    fixTableColumnWidth = function () {
        if (0 < $('.listingTableJs').length) {
            $('.listingTableJs').each(function () {
                let autoColumnWidth = $(this).attr('data-auto-column-width');
                if ('undefined' == typeof autoColumnWidth || 0 < autoColumnWidth || '' == autoColumnWidth) {
                    fixWidth($(this));
                }
            });
        } else {
            let autoTableColumWidth = $('.listingTableJs').data('autoColumnWidth');
            if (1 > autoTableColumWidth) {
                return false;
            }
            fixWidth($('.listingTableJs'));
        }
    }

    fixWidth = function (formObj) {
        var thWidthArr = [];

        $('.tableHeadJs th', formObj).each(function () {
            var arr = {
                'width': $(this).outerWidth(true),
                'element': $(this)
            };
            thWidthArr.push(arr);
        });
        /* Sort By width */
        thWidthArr.sort((a, b) => (a.width > b.width) ? 1 : -1)
        /* Sort By width */

        $.each(thWidthArr, function (index, value) {
            var width = value.width;
            var element = value.element;
            $(element).attr('width', width);
        });
    }

    fixWidthHelper = function (e, ui) {
        ui.children().each(function () {
            $(this).width($(this).width());
        });
        return ui;
    }

    fixPlaceholderStyle = function (e, ui) {
        ui.placeholder.height(ui.item.height());
        ui.placeholder.css("visibility", "visible");
        ui.placeholder.css('background-color', '#f3f6f9');
    }

    validateFileUpload = function (file) {
        if (file.size >= langLbl.allowedFileSize) {
            let msg = langLbl.fileSizeExceeded;
            msg = msg.replace("{size-limit}", bytesToSize(langLbl.allowedFileSize));
            fcom.displayErrorMessage(msg);
            return false;
        }
        return true;
    }

    updateCurrencyRates = function (converterClass) {
        if (!confirm(langLbl.updateCurrencyRates)) {
            return false;
        }
        fcom.updateWithAjax(fcom.makeUrl(converterClass, 'update'), '', function (res) {
            fcom.displaySuccessMessage(res.msg);
        });
    };
})();

$(document).on("click", ".selectItemJs", function () {
    var parentForm = $(this.form).attr("id");
    var tr = $(this).closest('tr');
    if ($(this).prop("checked") == false) {
        $("#" + parentForm + " .selectAllJs").prop("checked", false);
        tr.removeClass('selected');
    } else {
        tr.addClass('selected');
    }

    if ($("#" + parentForm + " .selectItemJs").length == $("#" + parentForm + " .selectItemJs:checked").length) {
        $("#" + parentForm + " .selectAllJs").prop("checked", true);
    }
    showActionsBtns();
});

/* $(document).ready(function () {
    if (typeof controllerName != 'undefined') {
        getHelpCenterContent(controllerName);
    }
}); */

$(window).on('load', function () {
    fixTableColumnWidth();
    frm = document.frmRecordSearchPaging;
    if (typeof frm != 'undefined' && frm != null) {
        var pageRecordCount = $(frm.total_record_count).val();
        if (typeof pageRecordCount == 'undefined' || pageRecordCount <= 0) {
            loadPagination(document.frmRecordSearchPaging);
        }
    }
});