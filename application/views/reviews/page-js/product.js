$("document").ready(function () {
	reviews(document.frmReviewSearch);
});
function reviewAbuse(reviewId) {
	if (reviewId) {
		$.facebox(function () {
			fcom.ajax(fcom.makeUrl('Reviews', 'reviewAbuse', [reviewId]), '', function (t) {
				$.facebox(t);
			});
		});
	}
}

function setupReviewAbuse(frm) {
	if (!$(frm).validate()) return;
	var data = fcom.frmData(frm);
	fcom.updateWithAjax(fcom.makeUrl('Reviews', 'setupReviewAbuse'), data, function (t) {
		fcom.closeProcessing();
		fcom.removeLoader();
		$.facebox.close();
	});
	return false;
}

(function () {
	/* reviews section[ */

	var dv = '#itemRatings .reviewListJs';
	var currPage = 1;

	reviews = function (frm, append) {
		if (typeof append == undefined || append == null) {
			append = 0;
		}

		var data = fcom.frmData(frm);
		if (append == 1) {
			$(dv).prepend(fcom.getLoader());
		} else {
			$(dv).html(fcom.getLoader());
		}

		//
		fcom.updateWithAjax(fcom.makeUrl('Reviews', 'searchForProduct'), data, function (ans) {
			fcom.removeLoader();
			if (ans.status == 1) {
				fcom.closeProcessing();
			}
			if (ans.totalRecords) {
				$('#reviews-pagination-strip--js').show();
			}
			if (append == 1) {
				$(dv).find('.loader-yk').remove();
				$(dv).find('form[name="frmSearchReviewsPaging"]').remove();
				$(dv).append(ans.html);

				$('#reviewEndIndex').html((Number($('#reviewEndIndex').html()) + ans.recordsToDisplay));
			} else {
				$(dv).html(ans.html);
				$('#reviewStartIndex').html(ans.startRecord);
				$('#reviewEndIndex').html(ans.recordsToDisplay);
			}
			$('#reviewsTotal').html(ans.totalRecords);

			$("#loadMoreReviewsBtnDiv").html(ans.loadMoreBtnHtml);
			$('a.yes').toggleClass("is-active");
			fcom.removeLoader();
		});
	};

	goToLoadMoreReviews = function (page) {
		if (typeof page == undefined || page == null) {
			page = 1;
		}
		currPage = page;
		var frm = document.frmSearchReviewsPaging;
		$(frm.page).val(page);
		reviews(frm, 1);
	};

	/*] */

	markReviewHelpful = function (reviewId, isHelpful) {
		if (isUserLogged() == 0) {
			loginPopUpBox();
			return false;
		}
		isHelpful = (isHelpful) ? isHelpful : 0;
		var data = 'reviewId=' + reviewId + '&isHelpful=' + isHelpful;
		fcom.updateWithAjax(fcom.makeUrl('Reviews', 'markHelpful'), data, function (ans) {
			fcom.closeProcessing();
			fcom.removeLoader();
			setTimeout(function () {
				reviews(document.frmReviewSearch);
			}, 3000);

		});
	}

	rateAndReviewProduct = function (product_id, selprod_id) {
		if (isUserLogged() == 0) {
			loginPopUpBox();
			return false;
		}
		selprod_id = selprod_id || 0;
		window.location = fcom.makeUrl('Reviews', 'write', [product_id, selprod_id]);
	}
})();


function getSortedReviews(elm) {
	if ($(elm).length) {
		var sortBy = $(elm).data('sort');
		if (sortBy) {
			document.frmReviewSearch.orderBy.value = $(elm).data('sort');
			$(elm).parent().siblings().removeClass('is-active');
			$(elm).parent().addClass('is-active');
		}
	}
	$('.sortByTxtJs').text($(elm).text());
	$('.sortByEleJs').removeClass('active');
	$(elm).addClass('active');
	reviews(document.frmReviewSearch);
}