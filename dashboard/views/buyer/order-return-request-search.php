<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php $arr_flds = array(
        'orrequest_id'    =>    Labels::getLabel('LBL_ID', $siteLangId),
        'orrequest_date'    =>    Labels::getLabel('LBL_Date', $siteLangId),
        'op_invoice_number'        =>    Labels::getLabel('LBL_ORDER_INVOICE_NUMBER', $siteLangId),
        'products'            => Labels::getLabel('LBL_PRODUCT_NAME', $siteLangId),
        'orrequest_qty'        =>    Labels::getLabel('LBL_Return_Qty', $siteLangId),
        'orrequest_status'    =>    Labels::getLabel('LBL_Status', $siteLangId),
        'action'            =>    '',
    );
    $tableClass = '';
    if (0 < count($requests)) {
        $tableClass = "table-justified";
    }
    $tbl = new HtmlElement('table', array('class' => 'table ' . $tableClass));
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
                case 'orrequest_id':
                    $td->appendElement('plaintext', array(), $row['orrequest_reference'], true);
                    break;
                case 'orrequest_date':
                    $td->appendElement('plaintext', array(), FatDate::format($row[$key]), true);
                    break;
                case 'orrequest_type':
                    $td->appendElement('plaintext', array(), $returnRequestTypeArr[$row[$key]], true);
                    break;
                case 'products':
                    $txt = $this->includeTemplate('_partial/product/product-info-html.php', ['order' => $row, 'siteLangId' => $siteLangId], false, true);
                    $td->appendElement('plaintext', array(), $txt, true);
                    break;
                case 'orrequest_status':
                    $td->appendElement('span', array('class' => 'badge badge-inline ' . $OrderRetReqStatusClassArr[$row[$key]]), $OrderReturnRequestStatusArr[$row[$key]], true);
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions"), '', true);

                    if ($buyerPage) {
                        $url = UrlHelper::generateUrl('Buyer', 'ViewOrderReturnRequest', array($row['orrequest_id']));
                    }
                    if ($sellerPage) {
                        $url = UrlHelper::generateUrl('Seller', 'ViewOrderReturnRequest', array($row['orrequest_id']));
                    }
                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array(
                            'href' => $url, 'class' => '',
                            'title' => Labels::getLabel('LBL_View_Return_Order_Request', $siteLangId)
                        ),
                        '<i class="icn">
                        <svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                            </use>
                        </svg>
                    </i>',
                        true
                    );
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
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmOrderReturnRequestSrchPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToOrderReturnRequestSearchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
