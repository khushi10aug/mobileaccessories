$(function () {
	searchShops(document.frmSearchShops);
});

(function () {
	var dv = '#listing';
	var currPage = 1;

	reloadListing = function () {
		searchShops(document.frmSearchShops);
	};

	searchShops = function (frm, append) {
		if (typeof append == undefined || append == null) {
			append = 0;
		}

		var data = fcom.frmData(frm);
		$(dv).prepend(fcom.getLoader());

		fcom.updateWithAjax(fcom.makeUrl('Shops', 'search'), data, function (ans) {
			fcom.closeProcessing();
			fcom.removeLoader();
			if (append == 1) {
				$(document.frmSearchShopsPaging).remove();
				$(dv).find('.loader-yk').remove();
				$(dv).append(ans.html);
			} else {
				$(dv).html(ans.html);
			}
				$("#loadMoreBtnDiv").html(ans.loadMoreBtnHtml);
				$("#favShopCount").html(ans.totalRecords);
		});
	};

	goToShopSearchPage = function (page) {
		if (typeof page == 'undefined' || page == null) {
			page = 1;
		}
		currPage = page;
		var frm = document.frmSearchShopsPagingMap;
		$(frm.page).val(page);
		var data = fcom.frmData(frm);

		fcom.updateWithAjax(fcom.makeUrl('Shops', 'search'), data, function (ans) {
			fcom.closeProcessing();
			fcom.removeLoader();
				$('#mapShops--js').html(ans.html);
				clearMarkers();
				createMarkers(markers);
		});
	}

	goToLoadMore = function (page, append = 1) {
		if (typeof page == 'undefined' || page == null) {
			page = 1;
		}
		append = 'undefined' == typeof append ? 1 : append;
		
		currPage = page;
		var frm = document.frmSearchShopsPaging;
		$(frm.page).val(page);
		searchShops(frm, append);
	};

	unFavoriteShopFavorite = function (shopId, e) {
		toggleShopFavorite(shopId);
		$(e).attr('onclick', 'markShopFavorite(' + shopId + ',this)');
		$(e).html(langLbl.favoriteToShop);
		//reloadListing();
	};

	markShopFavorite = function (shopId, e) {
		toggleShopFavorite(shopId);
		$(e).attr('onclick', 'unFavoriteShopFavorite(' + shopId + ',this)');
		console.log(e);
		$(e).html(langLbl.unfavoriteToShop);
		//reloadListing();
	};

	$(document).on("change", "select[name=pageSizeSelectMap]", function () {
        var selectedVal = $(this).val();
        $("form[name=frmSearchShopsPagingMap] input[name=viewType]").val(
            "popupShops"
        );
        $("form[name=frmSearchShopsPagingMap] input[name=pageSize]").val(
            selectedVal
        );
		$("form[name=frmSearchShopsPagingMap] input[name=featured]").val(
            "1"
        );
        goToShopSearchPage();
    });

	dragCallback = function (dragendMap) {
		canSetCookie = true;
		codeLatLng(dragendMap.getCenter().lat(), dragendMap.getCenter().lng(), function (data) {
			displayGeoAddress(setGeoAddress(data));
			if (typeof dragTimeOutEvent != "undefined") {
				clearTimeout(dragTimeOutEvent);
			}
			dragTimeOutEvent = setTimeout(function () { goToShopSearchPage(); }, 1000);
		});
	};

	toogleMapView = function () {
		fcom.displayProcessing();
		var data = {viewType: 'popup',featured: 1};
		fcom.updateWithAjax(fcom.makeUrl('Shops', 'search'), data, function (ans) {
			$.facebox(ans.html, "modal-fullscreen modal-map");
			fcom.displaySuccessMessage();
		});
	};

})();

$(document).on('mouseover mouseout', '#mapShops--js > li', function (e) {
	let shopId = $(this).data('shopid');
	$.each(mapMarker, function (index, marker) {
		if (typeof marker != 'undefined') {
			let iconImage = fcom.makeUrl() + 'images/pin.png';
			if (marker['refId'] == shopId && e.type == 'mouseover') {
				iconImage = fcom.makeUrl() + 'images/pin2.png';
			}
			marker.setIcon(iconImage);
		}
	});
})