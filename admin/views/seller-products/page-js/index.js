(function () {

    editSellerProductInventory = function (recordId, displayInPopup, dialogClass) {
        recordId = parseInt(recordId, 10) || 0;
        if (1 > recordId) {
            return false;
        }
        if (typeof displayInPopup === 'undefined') {
            displayInPopup = false;
        }
        if (typeof dialogClass === 'undefined') {
            dialogClass = '';
        }
        fcom.resetEditorInstance();
        fcom.updateWithAjax(fcom.makeUrl('SellerProducts', 'form'), 'recordId=' + recordId, function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, displayInPopup, dialogClass);
            fcom.removeLoader();
        });
    };

    sellerProductDownloadFrm = function (selprod_id) {
        if (false === checkControllerName()) {
            return false;
        }
        fcom.resetEditorInstance();
        fcom.updateWithAjax(fcom.makeUrl(controllerName, "sellerProductDownloadFrm", [selprod_id]), '', function (t) {
            fcom.closeProcessing();
            $.ykmodal(t.html, false, 'modal-dialog-vertical-md');
            getDigitalDownloads();
            fcom.removeLoader();
        });
    };

    getDigitalDownloads = function () {
        if (false === checkControllerName()) {
            return false;
        }
        var recordId = $('#frmDownload input[name=record_id]').val();
        var downloadType = $("#frmDownload select[name='download_type']").val();
        var langId = $("#frmDownload select[name='lang_id']").val();
        var optionCombi = "";
        if (0 < $("#frmDownload select[name='option_comb_id']").length) {
            optionCombi = $("#frmDownload select[name='option_comb_id']").val();
        }

        if (optionCombi == '') {
            optionCombi = '0';
        }
        var data = { recordId, download_type: downloadType, option_comb: optionCombi, langId: langId };
        fcom.updateWithAjax(fcom.makeUrl(controllerName, 'getInventoryDigitalDownloads'), data, function (res) {
            fcom.closeProcessing();
            $("#digital_download_list").html(res.html);
        });
    }
    getUniqueSlugUrl = function(obj,str,recordId){
        if(str == ''){
            return;
        }
        var data = {url_keyword:str,recordId:recordId}
        fcom.ajax(fcom.makeUrl('SellerProducts', 'isProductRewriteUrlUnique'), data, function(t) { 
            var ans = $.parseJSON(t);
            $(obj).next().html(ans.msg);
            if(ans.status == 0){
                $(obj).next().removeClass('text-muted').addClass('text-danger');
            }else{
                $(obj).next().addClass('text-muted').removeClass('text-danger');
            }
        });
    }
    productMissingInfo = function(selProdId){     
        fcom.updateWithAjax(fcom.makeUrl('SellerProducts', 'productMissingInfo'), {recordId: selProdId}, function(t) { 
            fcom.closeProcessing();
            $.ykmodal(t.html);
            fcom.removeLoader();
            
        });
    }

    selprodInventoryMetaLangForm = function (metaId, langId, selprodId, autoFillLangData) {
        if (false === checkControllerName()) {
            return false;
        }
        autoFillLangData = autoFillLangData || 0;
        fcom.resetEditorInstance();
        fcom.updateWithAjax(
            fcom.makeUrl('MetaTags', 'langForm', [metaId, langId, 'product_view', selprodId, autoFillLangData]),
            'sellerInvContext=1',
            function (t) {
                fcom.closeProcessing();
                $.ykmodal(t.html);
                fcom.removeLoader();
            }
        );
    };

})();

$(function () {
    $(document).on('change', "select[name='download_type']", function () {
        getDigitalDownloads();
    });
    $(document).on('change', "select[name='lang_id']", function () {
        getDigitalDownloads();
    });
});




