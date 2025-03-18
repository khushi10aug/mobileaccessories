<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php $arr_flds = array(
        'ocrequest_id'    =>    Labels::getLabel('LBL_ID', $siteLangId),
        'ocrequest_date'    =>    Labels::getLabel('LBL_Date', $siteLangId),
        'op_invoice_number'        =>    Labels::getLabel('LBL_Order_Invoice_Number', $siteLangId),
        'ocreason_title'    =>    Labels::getLabel('LBL_Request_Details', $siteLangId),
        'ocrequest_status'    =>    Labels::getLabel('LBL_Status', $siteLangId),
    );

    $tbl = new HtmlElement('table', array('class' => 'table table-justified'));
    $th = $tbl->appendElement('thead')->appendElement('tr', array('class' => ''));
    foreach ($arr_flds as $val) {
        $e = $th->appendElement('th', array(), $val);
    }

    $sr_no = 0;
    foreach ($requests as $sn => $row) {
        $sr_no++;
        $tr = $tbl->appendElement('tr', array('class' => ''));

        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'ocrequest_id':
                    $listSerial = '#C' . str_pad($row[$key], 5, '0', STR_PAD_LEFT);
                    $td->appendElement('plaintext', array(), $listSerial, true);
                    break;
                case 'op_invoice_number':
                    $url = UrlHelper::generateUrl('Buyer', 'viewOrder', array($row['order_id'], $row['op_id']));
                    if (isset($isSeller) && true == $isSeller) {
                        $url = UrlHelper::generateUrl('Seller', 'viewOrder', array($row['op_id']));
                    }
                    $td->appendElement('a', array('target' => '_blank', 'href' => $url), $row[$key], true);
                    break;
                case 'ocrequest_date':
                    $td->appendElement('plaintext', array(), FatDate::format($row[$key]), true);
                    break;
                case 'ocreason_title':
                    $txt = '<strong>' . Labels::getLabel('LBL_Reason', $siteLangId) . ': </strong>';
                    $txt .= CommonHelper::displayNotApplicable($siteLangId, $row['ocreason_title']);
                    $txt .= '<br/><strong>' . Labels::getLabel('LBL_Comments', $siteLangId) . ': </strong>';
                    if (strlen((string)$row['ocrequest_message']) > 25) {
                        $txt .= nl2br(substr($row['ocrequest_message'], 0, 25)). '<button class="btn btn-view" data-bs-toggle="tooltip" data-placement="top" data-bs-original-title="' . Labels::getLabel('LBL_VIEW_MORE', $siteLangId) . '" onclick="getCancellationRequestComment(' . $row['ocrequest_id'] . ')"><svg class="svg" width="10" height="10"><use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#more"></use></svg></button>';
                    } else {
                        $txt .= nl2br(CommonHelper::displayNotApplicable($siteLangId, $row['ocrequest_message']));
                    }
                    $td->appendElement('plaintext', array(), $txt, true);
                    break;
                case 'ocrequest_status':
                    $td->appendElement('span', array('class' => 'badge badge-inline ' . $cancelReqStatusClassArr[$row[$key]]), $OrderCancelRequestStatusArr[$row[$key]], true);
                    break;
                default:
                    $td->appendElement('plaintext', array(), $row[$key], true);
                    break;
            }
        }
    }
    echo $tbl->getHtml();
    if (count($requests) == 0) {
        $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    } ?>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmOrderCancellationRequestSrchPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToOrderCancelRequestSearchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
