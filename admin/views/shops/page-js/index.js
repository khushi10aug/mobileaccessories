(function () {
    mediaForm = function (shopId, langId = 0, slide_screen = 1) {
        fcom.updateWithAjax(fcom.makeUrl('Shops', 'media', [shopId, langId, slide_screen]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html);
            shopImages(shopId, 'logo', slide_screen, langId);
            shopImages(shopId, 'image', slide_screen, langId);
            fcom.removeLoader();
        });
    };

    shopImages = function (shopId, fileType, slide_screen, langId) {
        fcom.updateWithAjax(fcom.makeUrl('Shops', 'images', [shopId, fileType, langId, slide_screen]), '', function (t) {
            fcom.closeProcessing();
            fcom.removeLoader();
            if (fileType == 'logo') {
                $('#logoListingJs').html(t.html);
            } else {
                $('#imageListingJs').html(t.html);
            }
        });
    };

    deleteMedia = function (shopId, fileType, afileId, langId, slide_screen) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Shops', 'removeMedia', [shopId, fileType, afileId]), '', function (t) {
            fcom.displaySuccessMessage(t.msg);
            shopImages(shopId, fileType, slide_screen, langId);
            reloadList();
        });
    };

    $(document).on('change', '#logoLanguageJs', function () {
        var lang_id = $(this).val();
        var shop_id = $(this).closest("form").find('input[name="shop_id"]').val();
        shopImages(shop_id, 'logo', 1, lang_id);
    });

    $(document).on('change', '#imageLanguageJs', function () {
        var lang_id = $(this).val();
        var shop_id = $(this).closest("form").find('input[name="shop_id"]').val();
        var slide_screen = $("#slideScreenJs").val();
        shopImages(shop_id, 'image', slide_screen, lang_id);
    });
    shopMissingInfo = function(shopId){     
        fcom.updateWithAjax(fcom.makeUrl('Shops', 'shopMissingInfo'), {recordId: shopId}, function(t) { 
            fcom.closeProcessing();
            $.ykmodal(t.html);
            fcom.removeLoader();
            
        });
    }
})();

