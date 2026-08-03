$(document).on('change', '.selprodoption_optionvalue_id', function () {
	var frm = document.frmSellerProduct;
	var data = fcom.frmData(frm);
	fcom.ajax(fcom.makeUrl('Seller', 'checkSellProdAvailableForUser'), data, function (t) {
		var ans = $.parseJSON(t);
		if (ans.status == 0) {
			fcom.displayErrorMessage(ans.msg);
			return;
		}
		$.ykmsg.close();
	});
});

(function () {
	var runningAjaxReq = false;
	var runningAjaxMsg = 'some requests already running or this stucked into runningAjaxReq variable value, so try to relaod the page and update the same to WebMaster. ';
	//var dv = '#sellerProductsForm';
	var dv = '#listing';

	checkRunningAjax = function () {
		if (runningAjaxReq == true) {
			console.log(runningAjaxMsg);
			return;
		}
		runningAjaxReq = true;
	};

	loadSellerProducts = function (frm) {
		sellerProducts($(frm.product_id).val());
	};


	sellerProductForm = function (product_id, selprod_id) {
		$("#tabs_001").prepend(fcom.getLoader());
		fcom.ajax(fcom.makeUrl('Seller', 'sellerProductGeneralForm', [product_id, selprod_id]), '', function (t) {
			fcom.removeLoader();
			$(".tabs_panel").html('');
			$(".tabs_panel").hide();
			$("#tabs_001").show();
			$(".navTabsJs > a").removeClass('active');
			$("a[rel='tabs_001']").addClass('active');
			$("#tabs_001").html(t);
		});
	};

	translateData = function (item, defaultLang, toLangId) {
		var autoTranslate = $("input[name='auto_update_other_langs_data']:checked").length;
		var prodName = $("input[name='selprod_title" + defaultLang + "']").val();
		var prodDesc = $("textarea[name='selprod_comments" + defaultLang + "']").val();
		var alreadyOpen = $('.collapse-js-' + toLangId).hasClass('show');
		if (autoTranslate == 0 || prodName == "" || alreadyOpen == true) {
			return false;
		}
		var data = "product_name=" + prodName + '&product_description=' + prodDesc + "&toLangId=" + toLangId;
		fcom.updateWithAjax(fcom.makeUrl('Seller', 'translatedProductData'), data, function (t) {
			if (t.status == 1) {
				$("input[name='selprod_title" + toLangId + "']").val(t.productName);
				$("textarea[name='selprod_comments" + toLangId + "']").val(t.productDesc);
			}
		});
	}

	setUpSellerProduct = function (frm) {
		if (!$(frm).validate()) { return; }
		ykevents.customizeProduct();
		runningAjaxReq = true;
		var data = fcom.frmData(frm);
		fcom.updateWithAjax(fcom.makeUrl('Seller', 'setUpSellerProduct'), data, function (t) {
			runningAjaxReq = false;
			if (productType === PRODUCT_TYPE_DIGITAL) {
				sellerProductDownloadFrm(t.product_id, t.selprod_id);
				return;
			}
		});
	};

	optionsAssocArr = function (formData) {
		var data = {};
		$.each(formData, function (key, obj) {
			if ('' != obj.value) {
				var a = obj.name.match(/(.*?)\[(.*?)\]\[(.*?)\]/);
				if (a !== null) {
					var subName = a[1];
					var subKey = a[2];
					var options = a[3];

					if (!data[subName]) {
						data[subName] = [];
					}

					if (!data[subName][subKey]) {
						data[subName][subKey] = [];
					}

					if (data[subName][subKey][options]) {
						if ($.isArray(data[subName][subKey][options])) {
							data[subName][subKey][options] = obj.value;
						} else {
							data[subName][subKey][options] = obj.value;
						}
					} else {
						data[subName][subKey][options] = obj.value;
					}
				} else {
					if (data[obj.name]) {
						if ($.isArray(data[obj.name])) {
							data[obj.name].push(obj.value);
						} else {
							data[obj.name] = [];
							data[obj.name].push(obj.value);
						}
					} else {
						data[obj.name] = obj.value;
					}
				}
			}
		});
		return data;
	};

	setUpMultipleSellerProducts = function (frm, i = 0, orignalData = []) {
		if (!$(frm).validate()) { return; }

		if (1 > orignalData.length) {
			orignalData = optionsAssocArr($(frm).serializeArray());
		}
		var data = orignalData;
		var varients = data.varients;
		if (data.varients === undefined) {
			fcom.displayErrorMessage(LBL_MANDATORY_OPTION_FIELDS);
			return false;
		}
		varients = varients.filter(function () { return true; });


		if (i < varients.length) {
			var chunk = varients[i];
			var final = {};
			$.extend(final, data, chunk);
			final.varients = [];
			var data = jQuery.param(final);

			$('.optionFld-js').each(function () {
				var $this = $(this);
				var errorInRow = false;
				$this.find('input').each(function () {
					if ($(this).parent().hasClass('fldSku') && CONF_PRODUCT_SKU_MANDATORY != 1) {
						return;
					}
					if ($(this).val().length == 0 || $(this).val() == 0) {
						errorInRow = true;
						return false;
					}
				});
				if (errorInRow) {
					$this.parent().addClass('invalid');
				} else {
					$this.parent().removeClass('invalid');
				}
			});
			if ($("#optionsTable-js > tbody > tr.invalid").length == $("#optionsTable-js > tbody > tr").length) {
				fcom.displayErrorMessage(LBL_MANDATORY_OPTION_FIELDS);
				return false;
			}

			fcom.ajax(fcom.makeUrl('Seller', 'setUpMultipleSellerProducts'), data, function (t) {
				i++;
				if (0 == t.status) {
					fcom.displayErrorMessage(t.msg);
					setTimeout(function () { location.reload(); }, 3000);
					return;
				}
				if (i < varients.length) {
					setUpMultipleSellerProducts(frm, i, orignalData);
				}
				if (i == varients.length) {
					if (productType === PRODUCT_TYPE_DIGITAL) {
						sellerProductDownloadFrm(t.product_id, 0);
						return;
					}
					fcom.displaySuccessMessage(t.msg);
					setTimeout(function () { location.reload(); }, 1000);
				}
			}, { fOutMode: 'json' });

			var counterString = langLbl.processing_counter.replace("{counter}", (i + 1));
			counterString = counterString.replace("{count}", varients.length);
			counterString = langLbl.processing + " " + counterString;
			$.ykmsg.info(counterString, -1);
		}
	};

	sellerProductDownloadFrm = function (product_id, selprod_id) {
		$("#tabs_002").prepend(fcom.getLoader());
		$(".tabs_panel").html('');
		$(".tabs_panel").hide();
		$("#tabs_002").show();
		$(".navTabsJs  > a").removeClass('active');
		$("a[rel='tabs_002']").addClass('active');
		$("#tabs_002").html('<div class="col-md-12" id="digital_download_form"></div> <div class="col-md-12" class="dd-list"><div class="row" id="digital_download_list"></div></div>');
		$(".downloadType-js").each(function () {
			$(this).trigger("change");
		});
		downloadsForm(product_id, selprod_id, true);
	};

	downloadsForm = function (product_id, selprod_id, callback) {
		fcom.displayProcessing(langLbl.requestProcessing);
		fcom.ajax(fcom.makeUrl('Seller', 'sellerProductDownloadFrm', [product_id, selprod_id]), '', function (res) {
			fcom.removeLoader();
			fcom.closeProcessing();
			$("#digital_download_form").html(res);
			if (typeof callback == 'function') {
				callback();
			} else {
				getDigitalDownloads();
			}

		});
	}

	setUpSellerProductDownloads = function (type, product_id, selprod_id) {
		var data = new FormData();
		$inputs = $('#frmDownload input[type=text],#frmDownload input[type=textarea],#frmDownload select,#frmDownload input[type=hidden]');
		$inputs.each(function () { data.append(this.name, $(this).val()); });
		data.append('selprod_id', selprod_id);
		if (DIGITAL_DOWNLOAD_FILE == type) {
			$.each($('#downloadable_file' + selprod_id)[0].files, function (i, file) {
				$(dv).prepend(fcom.getLoader());
				data.append('downloadable_file', file);
				$.ajax({
					url: fcom.makeUrl('Seller', 'uploadDigitalFile'),
					type: "POST",
					data: data,
					processData: false,
					contentType: false,
					success: function (t) {
						fcom.removeLoader();
						var ans = $.parseJSON(t);
						$('.downloadable_file').val('');
						if (ans.status == 0) {
							fcom.displayErrorMessage(ans.msg);
							sellerProductDownloadFrm(product_id, selprod_id);
							return;
						}
						sellerProductDownloadFrm(product_id, selprod_id);
					},
					error: function (jqXHR, textStatus, errorThrown) {
						alert("Error Occurred.");
					}
				});
			});
		} else {
			var data = fcom.frmData(document.frmDownload);
			data = data + '&' + 'selprod_id' + "=" + selprod_id;
			/*if (!$('#frmDownload').validate()) return;*/
			$(dv).prepend(fcom.getLoader());
			fcom.ajax(fcom.makeUrl('Seller', 'uploadDigitalFile'), data, function (t) {
				fcom.removeLoader();
				var ans = $.parseJSON(t);
				if (ans.status == 0) {
					fcom.displayErrorMessage(ans.msg);
					return;
				}
				fcom.displaySuccessMessage(ans.msg);
				sellerProductDownloadFrm(product_id, selprod_id);
			});
		}
	};

	deleteDigitalFile = function (selprod_id, afile_id) {
		var agree = confirm(langLbl.confirmDelete);
		if (!agree) { return false; }
		fcom.updateWithAjax(fcom.makeUrl('seller', 'deleteDigitalFile', [selprod_id, afile_id]), '', function (t) {
			sellerProductDownloadFrm(product_id, selprod_id, 0);
		});
	}

	updateDiscountString = function () {
		var splprice_display_list_price = 0;
		var splprice_display_dis_val = 0;
		var splprice_display_dis_type = 0;

		splprice_display_list_price = $("input[name='splprice_display_list_price']").val();
		if (splprice_display_list_price == '' || typeof splprice_display_list_price == undefined) {
			splprice_display_list_price = 0;
		}

		splprice_display_dis_val = $("input[name='splprice_display_dis_val']").val();
		if (splprice_display_dis_val == '' || typeof splprice_display_dis_val == undefined) {
			splprice_display_dis_val = 0;
		}

		splprice_display_dis_type = $("select[name='splprice_display_dis_type']").val();
		if (splprice_display_dis_type == 0 || typeof splprice_display_dis_type == undefined || typeof splprice_display_dis_type == '') {
			splprice_display_dis_type = FLAT;
		}
		var data = 'splprice_display_list_price=' + splprice_display_list_price + '&splprice_display_dis_val=' + splprice_display_dis_val + '&splprice_display_dis_type=' + splprice_display_dis_type;
		$("#special-price-discounted-string").prepend(fcom.getLoader());
		fcom.ajax(fcom.makeUrl('Seller', 'getSpecialPriceDiscountString'), data, function (res) {
			fcom.removeLoader();
			$("#special-price-discounted-string").html(res);
		});
	}

	gotToProucts = function () {
		window.location.href = fcom.makeUrl('seller', 'Products');
	};

	getUniqueSlugUrl = function (obj, str, recordId) {
		if (str == '') {
			return;
		}
		var data = { url_keyword: str, recordId: recordId }
		fcom.ajax(fcom.makeUrl('Seller', 'isProductRewriteUrlUnique'), data, function (t) {
			var ans = $.parseJSON(t);
			$(obj).next().html(ans.msg);
			if (ans.status == 0) {
				$(obj).next().addClass('text-danger').removeClass('text-muted');
			} else {
				$(obj).next().removeClass('text-danger').addClass('text-muted');
			}
		});
	};
})();

$(document).on('click', '.tabs_001', function () {
	if (selprod_id > 0) {
		sellerProductForm(product_id, selprod_id);
	}
});

$(document).on('click', '.tabs_002', function () {
	if (selprod_id > 0) {
		sellerProductDownloadFrm(product_id, selprod_id);
	} else {
		$(this).removeClass('active');
		$('.tabs_001').addClass('active');
		return false;
	}
});


(function () {
	getDigitalDownloads = function () {
		var recordId = $('#frmDownload input[name=record_id]').val();
		var downloadType = $("#frmDownload select[name='download_type']").val();
		var langId = $("#frmDownload select[name='lang_id']").val();
		var optionCombi = "";
		if (0 < $("#frmDownload select[name='option_comb_id']").length) {
			optionCombi = $("#frmDownload select[name='option_comb_id']").val();
			recordId = $("#frmDownload select[name='option_comb_id']").val();
		}

		if (optionCombi == '') {
			optionCombi = '0';
		}

		var data = 'record_id=' + recordId + '&download_type=' + downloadType;
		data = data + '&option_comb=' + optionCombi + '&langId=' + langId;

		fcom.ajax(fcom.makeUrl('Seller', 'getInventoryDigitalDownloads'), data, function (res) {
			$("#digital_download_list").html(res);
		});
	}

	saveDownloadLinks = function () {
		if (!$('#frmDownload').validate()) return;

		var data = fcom.frmData(document.frmDownload);
		var optionCombi = "";
		if (0 < $("#frmDownload select[name='option_comb_id']").length) {
			optionCombi = $("#frmDownload select[name='option_comb_id']").val();
		}

		if (optionCombi == '') {
			data += "&option_comb_id=0";
		} else {
			data += "&record_id=" + optionCombi;
		}

		fcom.displayProcessing();
		fcom.ajax(fcom.makeUrl('Seller', 'setupDigitalDownload'), data, function (t) {
			var ans = $.parseJSON(t);
			if (ans.status == 0) {
				fcom.displayErrorMessage(ans.msg);
				return;
			}
			fcom.displaySuccessMessage(ans.msg);
			$('.product_downloadable_link').val('');
			$('.product_preview_link').val('');
			$('input[name="dd_link_id"]').val('');
			getDigitalDownloads();
		});
	}

	saveDownloadFiles = function () {
		$('#digital_download_form').prepend(fcom.getLoader())
		var data = new FormData();
		$inputs = $('#frmDownload select,#frmDownload input[type=hidden]');
		$inputs.each(function () { data.append(this.name, $(this).val()); });

		var optionCombi = "";
		if (0 < $("#frmDownload select[name='option_comb_id']").length) {
			optionCombi = $("#frmDownload select[name='option_comb_id']").val();
		}

		$.each($('#downloadable_file')[0].files, function (i, file) {
			data.append('downloadable_file', file);
		});
		$.each($('#preview_file')[0].files, function (i, file) {
			data.append('preview_file', file);
		});

		if (optionCombi == '') {
			data.append('option_comb_id', 0);
		} else {
			data.append('record_id', optionCombi);
		}

		data.append('prod_ref_type', 1);

		fcom.displayProcessing();
		var recordId = $("input[name='record_id']").val();
		// var productId = $("input[name='product_id']").val();
		// var selProdId = $("input[name='selprod_id']").val();

		$.ajax({
			url: fcom.makeUrl('Seller', 'setupDigitalDownload'),
			type: "POST",
			data: data,
			processData: false,
			contentType: false,
			dataType: "json",
			success: function (ans) {
				fcom.removeLoader();
				$('.downloadable_file').val('');
				if (ans.status != 1) {
					fcom.displayErrorMessage(ans.msg);
					return;
				}
				$('#frmDownload').get(0).reset();
				fcom.displaySuccessMessage(ans.msg);
				downloadsForm(ans.productId, recordId, function () {
					$(".option-comb-id-js").val(ans.recordId);
					$(".file-language-js").val(ans.langId);
					getDigitalDownloads();
				});

			},
			error: function (jqXHR, textStatus, errorThrown) {
				fcom.removeLoader();
				alert("Error Occurred.");
			}
		});
	}

	attachDigitalPreviewFile = function (option, langId, refId, subRefId) {
		$(".file-language-js").val(langId);
		$('#frmDownload input[name=dd_link_id]').val(refId);
		$('#frmDownload input[name=dd_link_ref_id]').val(subRefId);

		$(".downloadable_file_input").hide();

		$('#frmDownload input[name=is_preview]').val(1);
		$('#frmDownload input[name=ref_file_id]').val(subRefId);

		$("#attachement_upload_btn").attr('onclick', 'saveDownloadFiles(); return false;');
		fcom.displayInfoMessage(lblAddFileInfo);

	}

	saveDigitalPreviewFile = function () {
		var data = new FormData();
		$inputs = $('#frmDownload select,#frmDownload input[type=hidden]');
		$inputs.each(function () { data.append(this.name, $(this).val()); });
		// var prodId = $("input[name='product_id']").val();
		// var selProdId = $("input[name='selprod_id']").val();

		$.each($('#preview_file')[0].files, function (i, file) {
			data.append('preview_file', file);
		});

		var optionCombi = "";
		if (0 < $("#frmDownload select[name='option_comb_id']").length) {
			optionCombi = $("#frmDownload select[name='option_comb_id']").val();
		}

		if (optionCombi == '') {
			data.append('option_comb_id', 0);
		} else {
			data.append('record_id', optionCombi);
		}


		fcom.displayProcessing();

		$.ajax({
			url: fcom.makeUrl('Seller', 'setupDigitalDownload'),
			type: "POST",
			data: data,
			processData: false,
			contentType: false,
			success: function (t) {
				var ans = $.parseJSON(t);
				if (ans.status == 0) {
					fcom.displayErrorMessage(ans.msg);
					return;
				}
				fcom.displaySuccessMessage(ans.msg);
				//downloadsForm(prodId, selProdId, true);
				getDigitalDownloads();
			},
			error: function (jqXHR, textStatus, errorThrown) {
				alert("Error Occurred.");
			}
		});
	}

	deleteDigitallink = function (linkId, refId) {
		var agree = confirm(langLbl.confirmDelete);
		if (!agree) {
			return false;
		}

		fcom.updateWithAjax(fcom.makeUrl('Seller', 'deleteDigitalLink', [linkId, refId]), '', function (res) {
			if (res.status == 1) {
				getDigitalDownloads();
			}
		}, {}, true);
	}

	deleteDigitalFile = function (afile_id, prod_id, isPreview, fullRow) {
		var agree = confirm(langLbl.confirmDelete);
		if (!agree) { return false; }

		var isPreview = isPreview || 0;
		var fullRow = fullRow || 0;

		var data = '&afile_id=' + afile_id + '&ref_id=' + prod_id;

		if (1 == isPreview) {
			data += '&is_preview=1'
		}
		data += '&frow=' + fullRow;

		fcom.updateWithAjax(fcom.makeUrl('Seller', 'deleteDigitalFile'), data, function (res) {
			if (res.status == 1) {
				getDigitalDownloads();
			}
		}, {}, true);
	};

	optionImageForm = function (productId, optionKey) {
		optionKey = (optionKey === undefined || optionKey === null || optionKey === '') ? '0' : optionKey;
		$.ykmodal(fcom.getLoader());
		fcom.updateWithAjax(fcom.makeUrl('Seller', 'optionImageForm', [productId, optionKey]), '', function (t) {
			fcom.closeProcessing();
			fcom.removeLoader();
			$.ykmodal(t.html, false, 'modal-dialog-vertical-md');
			$.ykmsg.close();
		});
	};

	loadOptionImages = function (productId, optionKey, langId) {
		optionKey = (optionKey === undefined || optionKey === null || optionKey === '') ? '0' : optionKey;
		langId = langId || 0;
		fcom.updateWithAjax(fcom.makeUrl('Seller', 'optionImages', [productId, optionKey, langId]), '', function (t) {
			fcom.closeProcessing();
			fcom.removeLoader();
			$('#optionProductImagesJs').html(t.html || '');
			$.ykmsg.close();
		});
	};

	loadOptionImageCropper = function (inputBtn) {
		if (!(inputBtn.files && inputBtn.files[0])) {
			return;
		}
		if (typeof validateFileUpload === 'function' && !validateFileUpload(inputBtn.files[0])) {
			return;
		}
		loadCropperSkeleton(false);
		$("#modalBoxJs .modal-title").text($(inputBtn).attr('data-name'));
		fcom.updateWithAjax(fcom.makeUrl('Seller', 'imgCropper'), '', function (t) {
			fcom.closeProcessing();
			fcom.removeLoader();
			$.ykmsg.close();
			$("#modalBoxJs .modal-body").html(t.body);
			$("#modalBoxJs .modal-footer").html(t.footer);
			var file = inputBtn.files[0];
			var frmName = $(inputBtn).closest('form').attr('name') || 'optionImageFrm';
			var minWidth = document[frmName].min_width.value;
			var minHeight = document[frmName].min_height.value;
			var options = {
				aspectRatio: minWidth / minHeight,
				data: {
					width: minWidth,
					height: minHeight,
				},
				minCropBoxWidth: minWidth,
				minCropBoxHeight: minHeight,
				toggleDragModeOnDblclick: false,
				imageSmoothingQuality: 'high',
				imageSmoothingEnabled: true,
			};
			$(inputBtn).val('');
			setTimeout(function () { cropImage(file, options, 'uploadOptionMedia', inputBtn); }, 100);
		});
	};

	uploadOptionMedia = function (formData) {
		var frmName = formData.get('frmName') || 'optionImageFrm';
		var otherData = $('form[name="' + frmName + '"]').serializeArray();
		$.each(otherData, function (key, input) {
			formData.append(input.name, input.value);
		});

		$.ajax({
			url: fcom.makeUrl('Seller', 'uploadOptionMedia'),
			type: 'post',
			dataType: 'json',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,
			beforeSend: function () {
				$("#modalBoxJs .modal-body").prepend(fcom.getLoader());
			},
			success: function (ans) {
				fcom.removeLoader();
				fcom.closeProcessing();
				$.ykmsg.close();
				$(document.body).css({ cursor: 'default' });
				if (ans.status == 0) {
					fcom.displayErrorMessage(ans.msg);
					return;
				}
				fcom.displaySuccessMessage(ans.msg);
				$("#modalBoxJs").modal('hide');
				/* Cropper closes the media modal; reopen so uploaded images are visible */
				optionImageForm(ans.product_id, ans.option_id);
			},
			error: function (xhr, ajaxOptions, thrownError) {
				fcom.removeLoader();
				fcom.closeProcessing();
				$(document.body).css({ cursor: 'default' });
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	};

	deleteOptionImage = function (productId, imageId) {
		var agree = confirm(langLbl.confirmDelete);
		if (!agree) { return false; }
		var optionKey = $('#option_image_option_id').val() || '0';
		var langId = $('#option_image_lang_id').val() || 0;
		fcom.updateWithAjax(fcom.makeUrl('Seller', 'deleteOptionImage', [productId, imageId]), '', function (t) {
			fcom.closeProcessing();
			fcom.removeLoader();
			fcom.displaySuccessMessage(t.msg);
			loadOptionImages(productId, optionKey, langId);
		});
	};

})();

$(document).on('change', '#option_image_lang_id', function () {
	var langId = $(this).val() || 0;
	var productId = $('#option_image_record_id').val();
	var optionKey = $('#option_image_option_id').val() || '0';
	if (productId) {
		loadOptionImages(productId, optionKey, langId);
	}
});