(function () {
    /* Always hit ImageAttributes; do not use global controllerName (e.g. Products listing embeds this script). */
    var imageAttributesCtrl = 'ImageAttributes';

    openImageAttributeForm = function (recordId, moduleType) {
        if (typeof moduleType === 'undefined' || moduleType === '' || moduleType === null) {
            moduleType = $('select[name=select_module] option').filter(':selected').val();
        }
        fcom.updateWithAjax(fcom.makeUrl(imageAttributesCtrl, 'form', [recordId, moduleType]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html);
            fcom.removeLoader();
        });
    };

    attributeForm = function (recordId) {
        openImageAttributeForm(recordId);
    };

    saveImageAttributes = function (frm) {
        if (!$(frm).validate()) {
            return false;
        }
        $.ykmodal(fcom.getLoader());
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl(imageAttributesCtrl, 'setup'), data, function (t) {
            fcom.closeProcessing();
            fcom.removeLoader();
            if ('undefined' != typeof t.msg) {
                fcom.displaySuccessMessage(t.msg);
            }
        });
        return false;
    };

    /* Alias for legacy onsubmit handlers. */
    setup = saveImageAttributes;

    $(document).on('click', '.submitBtnJs', function (e) {
        var $form = $('.' + $.ykmodal.element + ' form#frmImgAttributeJs');
        if (1 > $form.length) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        saveImageAttributes($form[0]);
    });

    $(document).on('change', '.languageJs', function () {
        var langId = $(this).val() || 0;
        var recordId = $('#frmImgAttributeJs input[name=record_id]').val();
        var module = $('#frmImgAttributeJs input[name=module_type]').val();
        var option_id = $('.optionJs').length ? $('.optionJs').val() : 0;
        fcom.updateWithAjax(fcom.makeUrl(imageAttributesCtrl, 'form', [recordId, module, langId, option_id]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html);
            $('#frmImgAttributeJs input[name=lang_id]').val(langId);
            fcom.removeLoader();
        });
    });

    $(document).on('change', '.optionJs', function () {
        var option_id = $(this).val();
        var recordId = $('#frmImgAttributeJs input[name=record_id]').val();
        var module = $('#frmImgAttributeJs input[name=module_type]').val();
        var langId = $('.languageJs').val() || 0;
        fcom.updateWithAjax(fcom.makeUrl(imageAttributesCtrl, 'form', [recordId, module, langId, option_id]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html);
            $('#frmImgAttributeJs input[name=lang_id]').val(langId);
            fcom.removeLoader();
        });
    });
})();
