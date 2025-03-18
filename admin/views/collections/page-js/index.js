$(window).on('load', function () {
    bindSortable();
});

$(document).ajaxComplete(function () {
    bindSortable();
});

$(document).on('change', '.prefDimensionsJs', function () {
    var banner_screen = $(this).val();
    var banner_id = $("input[name='banner_id']").val();
    var collection_id = $("input[name='collection_id']").val();
    var lang_id = $("select[name='lang_id']").val();
    loadBannerImages(collection_id, banner_id, lang_id, banner_screen);
});

(function () {
    bindSortable = function () {
        if (1 > $('[data-field="dragdrop"]').length) {
            return;
        }
        $(".listingTableJs tbody.listingRecordJs").sortable({
            handle: '.handleJs',
            helper: fixWidthHelper,
            start: fixPlaceholderStyle,
            update: function (event, ui) {
                fcom.displayProcessing();
                $('.listingTableJs').prepend(fcom.getLoader());
                var order = $(this).sortable('toArray');
                var data = '';
                const bindData = new Promise((resolve, reject) => {
                    for (let i = 0; i < order.length; i++) {
                        data += 'collectionList[' + (i + 1) + ']=' + order[i];
                        if (i + 1 < order.length) {
                            data += '&';
                        }
                    }
                    resolve(data);
                });
                bindData.then(
                    function (value) {
                        fcom.ajax(fcom.makeUrl(controllerName, 'updateOrder'), value, function (res) {
                            fcom.closeProcessing();
                            fcom.removeLoader();
                            var ans = JSON.parse(res);
                            if (ans.status != 1) {
                                fcom.displayErrorMessage(ans.msg);
                                return;
                            }
                            fcom.displaySuccessMessage(ans.msg);
                            reloadList();
                        });
                    },
                    function (error) {
                        fcom.removeLoader();
                        fcom.closeProcessing();
                    }
                );
            },
        });
    };

    layoutSelectorForm = function () {
        /* Uncheck all if checked. */
        $(".selectAllJs, .selectItemJs").prop("checked", false)

        fcom.updateWithAjax(fcom.makeUrl(controllerName, "layoutSelectorForm"), "", function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, false, "modal-dialog-vertical-md");
            fcom.removeLoader();
        });
    }

    collectionForm = function (type, layoutType, collection_id = 0) {
        fcom.resetEditorInstance();

        /* Uncheck all if checked. */
        $(".selectAllJs, .selectItemJs").prop("checked", false)

        fcom.updateWithAjax(fcom.makeUrl(controllerName, "form", [type, layoutType]), "recordId=" + collection_id, function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, false, "modal-dialog-vertical-md");
            fcom.removeLoader();
        });
    };

    recordForm = function (id, type) {
        fcom.updateWithAjax(fcom.makeUrl('Collections', 'recordForm', [id, type]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, false, "modal-dialog-vertical-md");
            fcom.removeLoader();
        });
    };

    updateRecord = async function (e, collection_id) {
        var record_id = e.params.args.data.id;
        let response = await $.ajax({
            url: fcom.makeUrl(controllerName, 'updateCollectionRecords'),
            type: 'POST',
            data: { collection_id, record_id, fIsAjax: 1 }
        });

        try {
            response = JSON.parse(response);
        } catch (e) { }

        if ('undefined' != response.status && 0 == response.status) {
            fcom.displayErrorMessage(response.msg);
            return;
        }

        var newOption = new Option(e.params.args.data.text, e.params.args.data.id, true, true);
        let currentEl = $(e.currentTarget);
        currentEl.append(newOption).trigger('change');
        currentEl.select2('close');
    };

    removeCollectionRecord = function (collection_id, recordId) {
        /*
        if (!confirm(langLbl.confirmRemoveProduct)) {
            return false;
        }
        */
        fcom.ajax(fcom.makeUrl(controllerName, 'removeCollectionRecord'), 'collection_id=' + collection_id + '&record_id=' + recordId, function (t) { });
    };

    collectionMediaForm = function (collection_id, type) {
        fcom.updateWithAjax(fcom.makeUrl(controllerName, "media", [collection_id, type]), "",
            function (t) {
                fcom.closeProcessing();
                fcom.removeLoader();
                $.ykmodal(t.html, false, "modal-dialog-vertical-md");
                loadImages(collection_id);
                /* if (0 < $(".displayMediaOnlyJs:checked").val()) {
                    $('.mediaElementsJs').show();
                } else {
                    $('.mediaElementsJs').hide();
                } */
            }
        );
    };

    loadImages = function (recordId, langId = 0, screen = 0) {
        if(0 == screen){
            screen = $("select[name='collection_screen']").val();
            screen = 'undefined' == typeof screen ? 0 : screen;
        }
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'images', [recordId, langId, screen]), '', function (t) {
            fcom.closeProcessing();
            fcom.removeLoader();
            var uploadedContentEle = $(".dropzoneContainerJs .dropzoneUploadedJs");
            if (0 < uploadedContentEle.length) {
                uploadedContentEle.remove();
            }

            if ('' != t.html) {
                $(".dropzoneContainerJs").append(t.html);
                $(".dropzoneUploadJs").hide();
            } else {
                $(".dropzoneUploadJs").show();
            }
        });
    };

    displayMediaOnly = function (collectionId, obj) {
        var value = (obj.checked) ? 1 : 0;
        fcom.ajax(fcom.makeUrl(controllerName, 'displayMediaOnly', [collectionId, value]), '', function (t) {
            fcom.removeLoader();
            var ans = $.parseJSON(t);
            if (0 == ans.status) {
                fcom.displayErrorMessage(ans.msg);
                $(obj).prop('checked', false);
                return false
            } /* else {
                (0 < value) ? $('.mediaElementsJs').show() : $('.mediaElementsJs').hide();
            } */
        });
    };

    deleteImage = function (recordId, afile_id, lang_id, slide_screen) {
        var agree = confirm(langLbl.confirmDelete);
        if (!agree) {
            return false;
        }
        fcom.ajax(fcom.makeUrl(controllerName, 'deleteImage', [recordId, afile_id, lang_id, slide_screen]), '', function (t) {
            var ans = $.parseJSON(t);
            if (ans.status == 0) {
                fcom.displayErrorMessage(ans.msg);
                return;
            }

            fcom.displaySuccessMessage(ans.msg);
            loadImages(recordId, lang_id);
        });
    }

    bannerForm = function (collection_id, banner_id = 0) {
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'bannerForm', [collection_id, banner_id]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, false, "modal-dialog-vertical-md");
            fcom.removeLoader();
        });
    };

    bannerLangForm = function (collection_id, banner_id, langId, autoFillLangData = 0) {
        var data = "collection_id=" + collection_id + "&banner_id=" + banner_id + "&langId=" + langId;
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'bannerLangForm', [autoFillLangData]), data, function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, false, "modal-dialog-vertical-md");
            fcom.removeLoader();
        });
    };

    banners = function (collection_id) {
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'searchBanners', [collection_id]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, false, "modal-dialog-vertical-md");
            fcom.removeLoader();
        });
    };

    setupBanners = function (frm) {
        if (!$(frm).validate()) { return; }
        $.ykmodal(fcom.getLoader(), false, "modal-dialog-vertical-md");

        var data = fcom.frmData(frm);
        fcom.ajax(fcom.makeUrl(controllerName, 'setupBanner'), data, function (res) {
            $("." + $.ykmodal.element + ' .submitBtnJs').removeClass('loading');
            fcom.removeLoader();
            var t = JSON.parse(res);
            if (t.status == 0) {
                fcom.displayErrorMessage(t.msg);
                return false;
            }
            fcom.displaySuccessMessage(t.msg);

            if (t.langId > 0) {
                bannerLangForm(t.collectionId, t.bannerId, t.langId);
            } else if ("openMediaForm" in t) {
                bannerMediaForm(t.collectionId, t.bannerId);
            }
            return;
        });
    };

    saveBannerLangData = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        $.ykmodal(fcom.getLoader(), false, "modal-dialog-vertical-md");

        var data = fcom.frmData(frm);
        fcom.ajax(fcom.makeUrl(controllerName, "bannerLangSetup"), data, function (res) {
            fcom.removeLoader();
            var t = JSON.parse(res);
            if (t.status == 0) {
                fcom.displayErrorMessage(t.msg);
                return false;
            }
            fcom.displaySuccessMessage(t.msg);

            if (t.langId > 0) {
                bannerLangForm(t.collectionId, t.recordId, t.langId);
            } else if ("openMediaForm" in t) {
                bannerMediaForm(t.collectionId, t.recordId);
            }
        });
    };

    bannerMediaForm = function (collectionId, bannerId, langId = 0, slide_screen = 1) {
        fcom.updateWithAjax(
            fcom.makeUrl(controllerName, "bannerMedia", [collectionId, bannerId, langId, slide_screen]),
            "",
            function (t) {
                fcom.closeProcessing();
                fcom.removeLoader();
                loadBannerImages(collectionId, bannerId, langId, slide_screen);
                $.ykmodal(t.html, false, "modal-dialog-vertical-md");
            }
        );
    };

    loadBannerImagesCallback = function (res) {
        loadBannerImages(res.collection_id, res.banner_id, res.lang_id, res.slide_screen);
    };

    loadBannerImages = function (collectionId, bannerId = 0, langId = 0, screen = 1) {
        if (1 > screen || 'undefined' == typeof screen) {
            screen = $('.prefDimensionsJs').val();
        }
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'bannerImages', [collectionId, bannerId, langId, screen]), '', function (t) {
            fcom.closeProcessing();
            fcom.removeLoader();
            var uploadedContentEle = $(".dropzoneContainerJs .dropzoneUploadedJs");
            if (0 < uploadedContentEle.length) {
                uploadedContentEle.remove();
            }

            if ('' != t.html) {
                $(".dropzoneContainerJs").append(t.html);
                $(".dropzoneUploadJs").hide();
            } else {
                $(".dropzoneUploadJs").show();
            }
        });
    };

    removeBannerImage = function (collectionId, recordId, afile_id, lang_id, slide_screen) {
        var agree = confirm(langLbl.confirmDelete);
        if (!agree) {
            return false;
        }
        fcom.ajax(fcom.makeUrl(controllerName, 'removeBanner', [recordId, afile_id, lang_id, slide_screen]), '', function (t) {
            var ans = $.parseJSON(t);
            if (ans.status == 0) {
                fcom.displayErrorMessage(ans.msg);
                return;
            }

            fcom.displaySuccessMessage(ans.msg);
            loadBannerImages(collectionId, recordId);
        });
    }

    toggleBannerStatus = function (e, obj, recordId, status, callback = "") {
        e.stopPropagation();

        var oldStatus = $(obj).attr("data-old-status");
        if (1 > recordId) {
            $(obj).prop("checked", 1 == oldStatus);
            fcom.displayErrorMessage(langLbl.invalidRequest);
            fcom.removeLoader();
            return false;
        }

        data = "recordId=" + recordId + "&status=" + status;
        fcom.ajax(fcom.makeUrl('Banners', "updateStatus"), data,
            function (res) {
                $(obj).prop("checked", 1 == status);
                var ans = JSON.parse(res);
                if (ans.status == 1) {
                    fcom.displaySuccessMessage(ans.msg);
                    $(obj).attr({ onclick: "toggleBannerStatus(event, this, " + recordId + ", " + oldStatus + ")", "data-old-status": status });
                    if ("" != callback) {
                        eval(callback);
                    }
                } else {
                    $(obj).prop("checked", 1 == oldStatus);
                    fcom.displayErrorMessage(ans.msg);
                }
                fcom.removeLoader();
            }
        );
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
            fcom.removeLoader();
            fcom.displaySuccessMessage(t.msg);

            if (t.langId == langLbl.defaultFormLangId) {
                reloadList();
            }
            if (t.langId > 0) {
                editLangData(t.recordId, t.langId);
            } else if ("banners" in t) {
                banners(t.recordId);
            } else if ("recordForm" in t) {
                recordForm(t.recordId, frm.collection_type.value);
            }
        });
    };

    editLangData = function (recordId, langId, autoFillLangData = 0) {
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
                $.ykmodal(t.html, isPopupView, "modal-dialog-vertical-md");
                fcom.removeLoader();
            }
        );
    };
})();