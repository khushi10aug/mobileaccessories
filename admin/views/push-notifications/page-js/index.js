(function () {
    view = function (recordId) {
        data = "recordId=" + recordId;
        fcom.updateWithAjax(fcom.makeUrl(controllerName, "view"), data, function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, false);
            fcom.removeLoader();
        });
    };

    editPushNotification = function (recordId, langId) {
        data = "recordId=" + recordId + "&langId=" + langId;
        fcom.updateWithAjax(fcom.makeUrl(controllerName, "form"), data, function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, false);
            fcom.removeLoader();
        });
    };

    loadImages = function (recordId, lang_id) {
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'images', [recordId, lang_id]), '', function (t) {
            fcom.closeProcessing();
            fcom.removeLoader();
            var uploadedContentEle = $(".dropzoneContainerJs .dropzoneUploadedJs");
            if (0 < uploadedContentEle.length) {
                uploadedContentEle.remove();
            }

            if ('' != t) {
                $(".dropzoneContainerJs").append(t.html);
                $(".dropzoneUploadJs").hide();
            } else {
                $(".dropzoneUploadJs").show();
            }
        });
    };

    clone = function (recordId, langId) {
        if (!confirm(langLbl.cloneNotification)) {
            return false;
        }
        data = "recordId=" + recordId + "&langId=" + langId;
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'clone'), data, function (t) {
            fcom.displaySuccessMessage(t.msg);
            reloadList();
            $.ykmodal(t.html, false);
            fcom.removeLoader();
        });
    };

    notifyUsersForm = function (pNotificationId) {
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'notifyUsersForm', [pNotificationId]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html);
            fcom.removeLoader();
        });
    };

    /* Bind Tagify */
    bindItem = function (e) {
        var recordId = e.detail.data.recordId;
        if ('undefined' == typeof recordId) {
            return false;
        }

        var itemId = e.detail.data.id;
        if ('' == itemId) {
            e.detail.tag.remove();
            return false;
        }
        fcom.ajax(fcom.makeUrl(controllerName, "bindUser", [recordId, itemId]), '', function (t) { });
    }

    removeItem = function (tag) {
        var recordId = tag.data.recordId;
        var itemId = tag.data.id;
        if ('undefined' == typeof recordId || 'undefined' == typeof itemId) {
            return false;
        }

        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'unlinkUser', [recordId, itemId]), '', function (t) {
            fcom.closeProcessing();            
        });
    }

    getItem = function (e) {
        e.stopPropagation();
        var keyword = e.detail.value;
        var element = e.detail.tagify.DOM.originalInput;
        var recordId = element.dataset.recordId;

        var userSelector = "input[name='users']";
        var buyers = $(userSelector).data("buyers");
        var sellers = $(userSelector).data("sellers");

        var list = [];
        fcom.ajax(fcom.makeUrl('Users', 'autoComplete'), {
            keyword: keyword,
            recordId: recordId,
            doNotLimitRecords: 1,
            user_is_buyer: buyers,
            user_is_supplier: sellers,
        }, function (t) {
            var ans = JSON.parse(t);
            for (i = 0; i < ans.results.length; i++) {
                var results = ans.results;
                list.push({
                    "id": results[i].id,
                    "value": results[i].text,
                    "recordId": recordId,
                });
            }
            e.detail.tagify.settings.whitelist = list;
            e.detail.tagify.loading(false).dropdown.show.call(tagify, keyword);
        });
    }

    let isDeletedConfirmed = false;
    bindTagify = function () {
        var input = document.querySelectorAll('.tagifyJs');
        input.forEach(function (element) {
            tagify = new Tagify(element, {
                whitelist: [],
                dropdown: {
                    position: 'text',
                    enabled: 0 // show suggestions dropdown after 1 typed character
                },
                hooks: {
                    beforeRemoveTag: function (tags) {
                        return new Promise((resolve, reject) => {
                            if (isDeletedConfirmed == false && !confirm(langLbl.confirmRemove)) {
                                return false;
                            }
                            isDeletedConfirmed = true;
                            removeItem(tags[0]);
                            resolve();
                        })
                    }
                }
            }).on('input', getItem).on('focus', getItem).on('dropdown:select', bindItem);
        });
    };

    deleteImage = function (recordId, afile_id, lang_id, slide_screen) {
        var agree = confirm(langLbl.confirmDelete);
        if (!agree) {
            return false;
        }
        fcom.ajax(fcom.makeUrl(controllerName, 'removeMedia', [recordId, afile_id, lang_id, slide_screen]), '', function (t) {
            var ans = $.parseJSON(t);
            if (ans.status == 0) {
                fcom.displayErrorMessage(ans.msg);
                return;
            }
            
            fcom.displaySuccessMessage(ans.msg);
            loadImages(recordId, lang_id);
        });
    }
})();
