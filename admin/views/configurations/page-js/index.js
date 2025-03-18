$(document).ready(function () {
    if ($('.settings-inner').length) {
        $('.settings-inner').scrollTop($('.settings-inner li.is-active').offset().top - $('.settings-inner').offset().top);
    }
    $(document).on("click", "#testMail-js", function () {
        fcom.ajax(fcom.makeUrl('Configurations', 'testEmail'), '', function (t) {
            var ans = $.parseJSON(t);
            if (ans.status == 1) {
                fcom.displaySuccessMessage(ans.msg);
                return false;
            }
            fcom.displayErrorMessage(ans.msg);
        });
    });

    $(document).on("change", "select[name='CONF_TIMEZONE']", function () {
        var timezone = $("select[name='CONF_TIMEZONE']").val();
        fcom.ajax(fcom.makeUrl('Configurations', 'displayDateTime'), 'time_zone=' + timezone, function (t) {
            var ans = $.parseJSON(t);
            $('#currentDate').html(ans.dateTime);
        });
    });

    $(document).on("click", ".submitBtnJs", function () {
        $('.formBodyJs form').submit();
    });

    $(document).on('change', '.prefRatio-js', function () {
        var inputElement = $(this).closest('.form-group').find('input[type="file"]');
        var selectedVal = $(this).val();
        if (selectedVal == ratioTypeSquare) {
            inputElement.attr('data-min_width', 150)
            inputElement.attr('data-min_height', 150)
        } else {
            inputElement.attr('data-min_width', 150)
            inputElement.attr('data-min_height', 85)
        }
    });
    $(document).on('change', 'input[name="CONF_DEFAULT_GEO_LOCATION"]', function () {
        if ($(this).prop("checked")) {
            $('select[name="CONF_GEO_DEFAULT_COUNTRY"]').prop('disabled', false); // enable
            $('select[name="CONF_GEO_DEFAULT_STATE"]').prop('disabled', false); // enable
            $('input[name="CONF_GEO_DEFAULT_ZIPCODE"]').prop('disabled', false); // enable
        } else {
            $('select[name="CONF_GEO_DEFAULT_COUNTRY"]').prop('disabled', true); // enable
            $('select[name="CONF_GEO_DEFAULT_STATE"]').prop('disabled', true); // enable
            $('input[name="CONF_GEO_DEFAULT_ZIPCODE"]').prop('disabled', true); // enable
        }
    });
    $(document).on('keyup', 'form[name="frmConfiguration"]', function (e) {
        e.stopImmediatePropagation();
        if (e.keyCode === 13) {
            $('.formBodyJs form').submit();
        }
    });
    $(document).on('change', '.discountInJs', function () {
        showHideMaxDiscountVal();
    });

    $(document).on("keyup", "input[name='CONF_REFERRER_URL_VALIDITY']", function () {
        let val = $(this).val();
        if ('' == val || 1 > val) {
            $(this).val(1);
        }
    });

    $(document).on('change', '.ga4ToggleEleJs', function () {
        if ($(this).prop("checked")) {
            $('.gaAccessTokenJs').hide();
        } else {
            $('.gaAccessTokenJs').show();
        }
    });

    $(document).on('click', '.svgFileTypeJs', function () {
        var logoType = $(this).closest('.svgFileTypeSectionJs').data('logoType');
        if (1 == $(this).val()) {
            $('.prefRatioSectionJs' + logoType).hide();
        } else {
            $('.prefRatioSectionJs' + logoType).show();
        }
    });
    $('.svgFileTypeJs:checked').trigger('click');
});

(function () {
    var dv = '#frmBlockJs';
    getForm = function (frmType, langId = 0) {
        var formUrl = fcom.makeUrl('Configurations', 'index', [frmType]) + ('' != tourStepUrl ? '?' + tourStepUrl : '');
        fcom.resetEditorInstance();
        $(dv).prepend(fcom.getLoader());
        fcom.updateWithAjax(fcom.makeUrl('Configurations', 'form', [frmType, langId]), '', function (t) {
            fcom.closeProcessing();
            fcom.removeLoader();
            $(dv).replaceWith(t.html);
            setTabActive(frmType);
            showHideMaxDiscountVal();
            window.history.pushState('', '', formUrl);
        });
    };

    showHideMaxDiscountVal = function () {
        if (0 < $('.discountInJs').length) {
            if (FLAT == $('.discountInJs').val()) {
                $('.maxDisValJs').hide();
                $('.maxDisValJs').find('input').val('');
            } else {
                $('.maxDisValJs').show();
            }
        }
    }

    setTabActive = function (type) {
        $('ul.confTypesJs li.is-active').removeClass('is-active');
        $('ul.confTypesJs li.tabJs-' + type).addClass('is-active');
        /* $('html, body').animate({
            scrollTop: $("#frmBlockJs").offset().top
        }); */
    }
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        $(dv).prepend(fcom.getLoader());
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('Configurations', 'setup'), data, function (t) {
            fcom.displaySuccessMessage(t.msg);
            fcom.removeLoader();
            if ('undefined' != typeof t.form_type && 'undefined' != typeof t.lang_id) {
                getForm(t.form_type, t.lang_id);
            }
        });
    }

    removeMediaImage = function (file_type, lang_id) {
        if (!confirm(langLbl.confirmDeleteImage)) {
            return;
        }
        $(dv).prepend(fcom.getLoader());
        fcom.updateWithAjax(fcom.makeUrl('Configurations', 'removeMediaImage', [file_type, lang_id]), '', function (t) {
            fcom.displaySuccessMessage(t.msg);
            fcom.removeLoader();
            getForm(document.frmConfiguration.form_type.value, lang_id);
        });
    };

    changedMessageAutoCloseSetting = function (val) {
        if (val == YES) {
            $("input[name='CONF_TIME_AUTO_CLOSE_SYSTEM_MESSAGES']").val(MESSAGE_AUTOCLOSE_TIME);
        }

        if (val == NO) {
            $("input[name='CONF_TIME_AUTO_CLOSE_SYSTEM_MESSAGES']").val(0);
        }
    };

    popupImage = function (inputBtn) {
        if (inputBtn.files && inputBtn.files[0]) {
            if (!validateFileUpload(inputBtn.files[0])) {
                return;
            }
            var file = inputBtn.files[0];
            var fileType = $(inputBtn).attr('data-file_type');
            fldName = "afile_attachment_type_" + fileType;
            var afile_attachment_type = $('input[name="' + fldName + '"]:checked').val();
            /* 1: Svg */
            if ('undefined' != typeof afile_attachment_type && 1 == afile_attachment_type) {
                var formData = new FormData();
                formData.append('file_type', fileType);
                formData.append('cropped_image', file);
                uploadConfImages(formData);
            } else {
                loadCropperSkeleton();
                $("#modalBoxJs .modal-title").text($(inputBtn).attr('data-name'));
                fcom.updateWithAjax(fcom.makeUrl('Configurations', 'imgCropper'), '', function (t) {
                    fcom.closeProcessing();
                    $("#modalBoxJs .modal-body").html(t.body);
                    $("#modalBoxJs .modal-footer").html(t.footer);

                    var file = inputBtn.files[0];
                    var minWidth = $(inputBtn).attr('data-min_width');
                    var minHeight = $(inputBtn).attr('data-min_height');
                    var options = {
                        // minContainerHeight: 350,
                        toggleDragModeOnDblclick: false,
                        imageSmoothingQuality: 'high',
                        imageSmoothingEnabled: true,
                    };

                    if (minWidth != undefined && minHeight != undefined) {
                        options['aspectRatio'] = minWidth / minHeight;
                        options['minCropBoxWidth'] = minWidth;
                        options['minCropBoxHeight'] = minHeight;
                        options['data'] = {
                            width: minWidth,
                            height: minHeight,
                        };
                    }
                    $(inputBtn).val('');
                    setTimeout(function () { cropImage(file, options, 'uploadConfImages', inputBtn) }, 200);
                    return;
                });
            }
        }
    };

    uploadConfImages = function (formData) {
        var langId = document.frmConfiguration.lang_id.value;
        var formType = document.frmConfiguration.form_type.value;
        var fileType = formData.get('file_type');
        var fldName = "ratio_type_" + fileType;
        var ratio_type = $('input[name="' + fldName + '"]:checked').val();

        fldName = "afile_attachment_type_" + fileType;
        var afile_attachment_type = $('input[name="' + fldName + '"]:checked').val();

        formData.append('lang_id', langId);
        formData.append('form_type', formType);
        formData.append('ratio_type', ratio_type);
        formData.append('afile_attachment_type', afile_attachment_type);
        $.ajax({
            url: fcom.makeUrl('Configurations', 'uploadMedia'),
            type: 'post',
            dataType: 'json',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $('#loader-js').html(fcom.getLoader());
            },
            complete: function () {
                $('#loader-js').html(fcom.getLoader());
            },
            success: function (ans) {
                fcom.removeLoader();
                if (!ans.status) {
                    fcom.displayErrorMessage(ans.msg);
                    return false;
                }
                fcom.displaySuccessMessage(ans.msg);
                getForm(formType, langId);
                $("#modalBoxJs").modal("hide");
            },
            error: function (xhr, ajaxOptions, thrownError) {
                if (xhr.responseText) {
                    fcom.displayErrorMessage(xhr.responseText);
                    return;
                }
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }

    deleteVerificationFile = function (fileType) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Configurations', 'deleteVerificationFile', [fileType]), '', function (t) {
            fcom.displaySuccessMessage(t.msg);
            getForm(document.frmConfiguration.form_type.value, document.frmConfiguration.lang_id.value);
        });
    };

    editDropZoneImages = function (obj) {
        $(obj).closest(".dropzoneContainerJs").find(".dropzoneInputJs").click();
    }

    searhSettings = function (e) {
        var value = e.val().toLowerCase();
        $(".confTypesJs li").each(function () {
            if ($(this).find('h6').text().toLowerCase().search(value) > -1 || $(this).find('span').text()
                .toLowerCase().search(value) > -1) {
                $(this).show();
                $('.confTypesJs').show();
            } else {
                $(this).hide();
                $('.confTypesJs').show();
            }

            $('.noRecordFoundJs').parent().hide();
            if (1 > $('.confTypesJs .settings-inner-link:visible').length) {
                $('.noRecordFoundJs').parent().show();
            }

            // $(".confTypesJs .is-active").removeClass('is-active');
            // $(".confTypesJs li:visible:first").addClass('is-active');
        });
    };

})();

$(document).on("search", "#navSearch", function (e) {
    searhSettings($(this));
});

$(document).on("keyup", "#navSearch", function (e) {
    searhSettings($(this));
});

$(document).on("search", "input[name='search']", function () {
    if ("" == $(this).val()) {
        searhSettings($(this));
    }
});


form = function (form_type) {
    if (typeof form_type == undefined || form_type == null) {
        form_type = 1;
    }
    jQuery.ajax({
        type: "POST",
        data: {
            form: form_type,
            fIsAjax: 1
        },
        url: fcom.makeUrl("configurations", "form"),
        success: function (json) {
            json = $.parseJSON(json);
            if ("1" == json.status) {
                $("#tabs_0" + form_type).html(json.msg);
            } else {
                jsonErrorMessage(json.msg)
            }
        }
    });
}

submitForm = function (form, v) {
    $(form).ajaxSubmit({
        delegation: true,
        beforeSubmit: function () {
            v.validate();
            if (!v.isValid()) {
                return false;
            }
        },
        success: function (json) {
            json = $.parseJSON(json);

            if (json.status == "1") {
                jsonSuccessMessage(json.msg)

            } else {
                jsonErrorMessage(json.msg);
            }
        }
    });
    return false;
}

updateVerificationFile = function (inputBtn, fileType) {
    var formData = new FormData();
    formData.append('fileType', fileType);
    var file = inputBtn.files[0];
    if (inputBtn.files && inputBtn.files[0]) {
        var file = inputBtn.files[0];
        fcom.displayProcessing(langLbl.processing, ' ', true);
        formData.append('verification_file', file);
        $.ajax({
            url: fcom.makeUrl('Configurations', 'updateVerificationFile'),
            type: 'post',
            dataType: 'json',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            success: function (ans) {
                if (!ans.status) {
                    fcom.displayErrorMessage(ans.msg);
                    return false;
                    return;
                }
                fcom.displaySuccessMessage(ans.msg);
                getForm(document.frmConfiguration.form_type.value);

            },
            error: function (xhr, ajaxOptions, thrownError) {
                if (xhr.responseText) {
                    fcom.displayErrorMessage(xhr.responseText);
                    return;
                }
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
}

$(document).on('change', 'input[name="CONF_HIDE_PRICES"]', function () {
    if (this.checked) {
        if (!$('input[name="CONF_RFQ_MODULE"]').is(":checked")) {
            $(this).prop('checked', false);
            alert(langLbl.enableRfqModule);
            return false;
        }
    }
});

$(document).on('change', 'input[name="CONF_GLOBAL_RFQ_MODULE"]', function () {
    if (this.checked) {
        if (!$('input[name="CONF_RFQ_MODULE"]').is(":checked")) {
            $(this).prop('checked', false);
            alert(langLbl.enableRfqModule);
            return false;
        }
    }
});

$(document).on('change', 'input[name="CONF_RFQ_MODULE"]', function () {
    if (false == this.checked) {
        if ($('input[name="CONF_HIDE_PRICES"]').is(":checked")) {
            $(this).prop('checked', true);
            alert(langLbl.disableHidePriceSettings);
            return false;
        }
    }
    if (false == this.checked) {
        if ($('input[name="CONF_GLOBAL_RFQ_MODULE"]').is(":checked")) {
            if (!confirm(langLbl.confirmDisableRfq)) {
                $(this).prop('checked', true);
                return false;
            }
            $(this).prop('checked', false);
            $('input[name="CONF_GLOBAL_RFQ_MODULE"]').prop('checked', false);
        }
    }
});