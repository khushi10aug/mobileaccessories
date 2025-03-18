<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$frmSrch->setFormTagAttribute('onSubmit', 'searchBuyerDownloadLinks(this); return false;');
$frmSrch->setFormTagAttribute('class', 'form');
$frmSrch->developerTags['colClassPrefix'] = 'col-md-';
$frmSrch->developerTags['fld_default_col'] = 12;

$keyFld = $frmSrch->getField('keyword');
$keyFld->setFieldTagAttribute('placeholder', Labels::getLabel('FRM_SEARCH_BY_INVOICE_NUMBER', $siteLangId));
$keyFld->setFieldtagAttribute('autocomplete', 'off');
$keyFld->setWrapperAttribute('class', 'col-lg-8');
$keyFld->developerTags['col'] = 8;
$keyFld->developerTags['noCaptionTag'] = true;

$submitBtnFld = $frmSrch->getField('btn_submit');
$submitBtnFld->setFieldTagAttribute('class', 'btn btn-brand btn-block');
$submitBtnFld->setWrapperAttribute('class', 'col-lg-2');
$submitBtnFld->developerTags['col'] = 2;
$submitBtnFld->developerTags['noCaptionTag'] = true;

$clearFld = $frmSrch->getField('btn_clear');
$clearFld->setFieldTagAttribute('onclick', 'clearSearch(1)');
$clearFld->setFieldTagAttribute('class', 'btn btn-outline-gray btn-block');
$clearFld->setWrapperAttribute('class', 'col-lg-2');
$clearFld->developerTags['col'] = 2;
$clearFld->developerTags['noCaptionTag'] = true;

$fld = $frmSrch->getField('op_id');
if (null != $fld) {
    $fld->setFieldTagAttribute('class', 'opId--js');
}
?>

<div class="card-body">
    <?php echo $frmSrch->getFormHtml(); ?>
    <?php echo $frmSrch->getExternalJS(); ?>
</div>

<div class="js-scrollable table-wrap table-responsive card-table">
    <?php $arr_flds = array(
        'op_invoice_number' => Labels::getLabel('LBL_Invoice', $siteLangId),
        'linksCount' => Labels::getLabel('LBL_LINKS_INSIDE', $siteLangId),
        'opddl_downloadable_link' => Labels::getLabel('LBL_Link', $siteLangId),
        'downloadable_count' => Labels::getLabel('LBL_Download_times', $siteLangId),
        'opddl_downloaded_times' => Labels::getLabel('LBL_Downloaded_Count', $siteLangId),
        'expiry_date' => Labels::getLabel('LBL_Expired_on', $siteLangId),
        'action' => '',
    );

    if (0 < $opId) {
        unset($arr_flds['linksCount'], $arr_flds['action']);
    } else {
        unset($arr_flds['opddl_downloadable_link'], $arr_flds['opddl_downloaded_times']);
    }

    $tbl = new HtmlElement('table', array('class' => 'table'));
    $th = $tbl->appendElement('thead')->appendElement('tr', array('class' => ''));
    foreach ($arr_flds as $val) {
        $e = $th->appendElement('th', array(), $val);
    }

    $sr_no = 0;
    $canCancelOrder = true;
    $canReturnRefund = true;
    foreach ($digitalDownloadLinks as $sn => $row) {
        $sr_no++;
        $tr = $tbl->appendElement('tr', array('class' => ''));

        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'linksCount':
                    if ($row['downloadable']) {
                        $fileName = '<a href="javascript:void(0);" class="link-dotted" onclick="showLinks(' . $row['op_id'] . ');">' . $row[$key] . '</a>';
                    } else {
                        $fileName = $row[$key];
                    }
                    $td->appendElement('div', ['class' => "text-break"], $fileName, true);
                    break;
                case 'opddl_downloadable_link':
                    if ($row['downloadable'] != 1) {
                        $td->appendElement('plaintext', array(), Labels::getLabel('LBL_N/A', $siteLangId), true);
                    } else {
                        $linkOnClick = ($row['downloadable'] != 1) ? '' : 'return increaseDownloadedCount(' . $row['opddl_link_id'] . ',' . $row['op_id'] . ')';
                        $ul = $td->appendElement("ul", array("class" => "actions"), '', true);
                        $li = $ul->appendElement("li");

                        $li->appendElement('a', array('href' => $row['opddl_downloadable_link'], 'target' => '_blank', 'onclick' => $linkOnClick, 'class' => '', 'title' => Labels::getLabel('LBL_Click_to_download', $siteLangId)), '<svg class="svg" width="18" height="18">
                        <use
                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#download">
                        </use>
                    </svg>', true);
                    }
                    break;
                case 'downloadable_count':
                    $downloadableCount = Labels::getLabel('LBL_N/A', $siteLangId);
                    if ($row['downloadable_count'] != -1) {
                        $downloadableCount = $row['downloadable_count'];
                    }
                    $td->appendElement('plaintext', array(), $downloadableCount, true);
                    break;
                case 'expiry_date':
                    $expiry = Labels::getLabel('LBL_N/A', $siteLangId);
                    if ($row['expiry_date'] != '') {
                        $expiry = FatDate::Format($row['expiry_date']);
                    }
                    $td->appendElement('plaintext', array(), $expiry, true);
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions"), '', true);
                    if ($row['downloadable']) {
                        if (1 > $opId) {
                            $li = $ul->appendElement("li");
                            $li->appendElement('a', array('href' => UrlHelper::generateUrl('Buyer', 'downloadDigitalLinksFile', array($row['op_id'])), 'class' => '', 'title' => Labels::getLabel('LBL_DOWNLOAD_LINKS', $siteLangId)), '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#download">
                            </use>
                        </svg>', true);
                        }
                    }
                    break;
                default:
                    $td->appendElement('plaintext', array(), $row[$key], true);
                    break;
            }
        }
    }
    echo $tbl->getHtml();
    if (count($digitalDownloadLinks) == 0) {
        $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    } ?>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmSrchPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToLinksSearchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>
<script>
    function increaseDownloadedCount(linkId, opId) {
        fcom.ajax(fcom.makeUrl('buyer', 'downloadDigitalProductFromLink', [linkId, opId]), '', function(t) {
            var ans = $.parseJSON(t);
            if (ans.status == 0) {
                fcom.displayErrorMessage(ans.msg);
                return false;
            }
            searchBuyerDownloadLinks(document.frmSrch);
            return true;
        });
    }

    function copyToClipboard(element) {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($('#dataLink').attr("data-link")).select();
        document.execCommand("copy");
        $temp.remove();
        alert('<?php echo Labels::getLabel('LBL_Your_link_is_copied_to_clipboard', $siteLangId); ?>');
    }
</script>