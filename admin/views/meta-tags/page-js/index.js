$(document).on('blur', '.metaUrlJs', function () {
    if (1 > $(this).val().trim().length) {
        return false;
    }
    var data = 'url=' + $(this).val();
    fcom.updateWithAjax(fcom.makeUrl('Home', 'segregateUrl'), data, function (t) {
        fcom.closeProcessing();
        $('.metaControllerJs').val(t.controller);
        $('.metaActionJs').val(t.action);
        $('.metaRecordIdJs').val(parseInt(t.recordId));
        $('.metaSubRecordIdJs').val(parseInt(t.subRecordId));
    });
});

(function () {
    var dv = '#metaTagsListing';
    var listingTableJs = '.listingTableJs';
    /* Always hit MetaTags; do not use global controllerName (other admin pages embed this script). */
    var metaTagsCtrl = 'MetaTags';

    tabSearchRecords = function (object) {
        $(':input', document.frmRecordSearch).not(':hidden').val('');
        metaTagsSearchRecords(object);
    };

    setTabActive = function (type) {
        $('ul.metaTypesJs li.is-active').removeClass('is-active');
        $('ul.metaTypesJs li.tabJs-' + type).addClass('is-active');
    }

    /* Named metaTagsSearchRecords so we do not overwrite listing.js searchRecords (reloadList depends on it). */
    metaTagsSearchRecords = function (object, replaceRowsOnly = false) {
        if (true === replaceRowsOnly) {
            $(listingTableJs).prepend(fcom.getLoader());
        } else {
            $(dv).prepend(fcom.getLoader());
        }

        if (isElement(object)) {
            var frm = object;
        } else {
            var frm = document.frmRecordSearch;
        }
        var metaType = frm.metaType.value;

        var type = metaType;
        if (typeof object === 'string' || object instanceof String) {
            frm.metaType.value = object;
            var type = object;

            if (metaType != type) {
                frm.page.value = 1;
                frm.sortBy.value = '';
                frm.sortOrder.value = '';
                frm.pageSize.value = '';
            }
        }

        data = fcom.frmData(frm);
        if (true === replaceRowsOnly) {
            data += '&loadRows=' + 1;
        }

        fcom.updateWithAjax(fcom.makeUrl(metaTagsCtrl, 'search'), data, function (res) {
            fcom.closeProcessing();
            fcom.removeLoader();
            setTabActive(type);

            if (true === replaceRowsOnly) {
                $(listingTableJs).html(res.listingHtml);
            } else {
                $(dv).replaceWith(res.listingHtml);
            }
        });
    };


    metaTagForm = function (id, metaType, metaTagRecordId) {
        fcom.updateWithAjax(fcom.makeUrl(metaTagsCtrl, 'form', [id, metaType, metaTagRecordId]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html);
            fcom.removeLoader();
        });
    };

    editMetaTagForm = function (id, metaType, metaTagRecordId) {
        fcom.updateWithAjax(fcom.makeUrl(metaTagsCtrl, 'form', [id, metaType, metaTagRecordId]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html);
            fcom.removeLoader();
        });
    };

    setupMetaTag = function (frm) {
        var el = frm;
        if (!el || !el.nodeName || el.nodeName.toLowerCase() !== 'form') {
            el = document.getElementById('frmMetaTag') || ($('.' + $.ykmodal.element + ' form.modalFormJs')[0]) || ($('.' + $.ykmodal.element + ' form')[0]);
        }
        if (!el) {
            return;
        }
        var $frm = $(el);
        if (!$.data(el, 'validator')) {
            $frm.validation({ errordisplay: 3 });
        }
        if (!$frm.validate()) {
            return;
        }
        var data = fcom.frmData(el);
        fcom.updateWithAjax(fcom.makeUrl(metaTagsCtrl, 'setup'), data, function (t) {
            fcom.displaySuccessMessage(t.msg);
            reloadList();
            if (t.langId > 0) {
                editMetaTagLangForm(t.metaId, t.langId, t.metaType, t.metaTagRecordId);
                return;
            }
        });
    };

    editMetaTagLangForm = function (metaId, langId, metaType, metaTagRecordId, autoFillLangData = 0, sellerInvContext = 0) {
        var data = 0 < sellerInvContext ? 'sellerInvContext=1' : '';
        fcom.updateWithAjax(fcom.makeUrl(metaTagsCtrl, 'langForm', [metaId, langId, metaType, metaTagRecordId, autoFillLangData]), data, function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html);
            fcom.removeLoader();
        });
    };

    setupLangMetaTag = function (frm, metaType) {
        var el = frm;
        if (!el || !el.nodeName || el.nodeName.toLowerCase() !== 'form') {
            el = document.getElementById('frmMetaTagLang') || ($('.' + $.ykmodal.element + ' form.modalFormJs')[0]) || ($('.' + $.ykmodal.element + ' form')[0]);
        }
        if (!el) {
            return;
        }
        var $frm = $(el);
        if (!$.data(el, 'validator')) {
            $frm.validation({ errordisplay: 3 });
        }
        if (!$frm.validate()) {
            return;
        }
        var data = fcom.frmData(el);
        fcom.updateWithAjax(fcom.makeUrl(metaTagsCtrl, 'langSetup'), data, function (t) {
            fcom.displaySuccessMessage(t.msg);
            reloadList();
            if (t.langId > 0) {
                if (0 < t.sellerInvContext && 0 < t.metaTagRecordId) {
                    editMetaTagLangForm(t.metaId, t.langId, metaType, t.metaTagRecordId, 0, 1);
                } else {
                    editMetaTagLangForm(t.metaId, t.langId, metaType);
                }
                return;
            }
        });
    };
})();