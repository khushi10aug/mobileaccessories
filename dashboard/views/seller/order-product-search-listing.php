<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php
    $arr_flds = array(
        'order_id'  =>    Labels::getLabel('LBL_Order_Id_Date', $siteLangId),
        'product'   =>    Labels::getLabel('LBL_Ordered_Product', $siteLangId),
        'op_qty'    =>    Labels::getLabel('LBL_Qty', $siteLangId),
        'total'     =>    Labels::getLabel('LBL_Total', $siteLangId),
        'opshipping_by_seller_user_id'     =>    Labels::getLabel('LBL_FULLFILED_BY', $siteLangId),
        'status'    =>    Labels::getLabel('LBL_Status', $siteLangId),
        'action'    =>    '',
    );
    $tableClass = '';
    if (0 < count($orders)) {
        $tableClass = "table-justified";
    }
    $tbl = new HtmlElement('table', array('class' => 'table ' . $tableClass));
    $th = $tbl->appendElement('thead')->appendElement('tr', array('class' => ''));
    foreach ($arr_flds as $val) {
        $e = $th->appendElement('th', array(), $val);
    }
    $sr_no = 0;
    $orderObj = new Orders();
    $notAllowedCancelStatuses = $orderObj->getNotAllowedOrderCancellationStatuses();
    foreach ($orders as $sn => $order) {
        $sr_no++;
        $tr = $tbl->appendElement('tr', array('class' => ''));
        $orderDetailUrl = UrlHelper::generateUrl('seller', 'viewOrder', array($order['op_id']));

        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'order_id':
                    $txt = '<a title="' . Labels::getLabel('LBL_VIEW_ORDER_DETAIL', $siteLangId) . '" href="' . $orderDetailUrl . '">';
                    $txt .= $order['op_invoice_number'];
                    $txt .= '</a><br/>' . FatDate::format($order['order_date_added']);
                    $td->appendElement('plaintext', array(), $txt, true);
                    break;
                case 'product':
                    $txt = $this->includeTemplate('_partial/product/product-info-html.php', ['order' => $order, 'siteLangId' => $siteLangId], false, true);
                    $td->appendElement('plaintext', array(), $txt, true);
                    break;
                case 'total':
                    $txt = CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($order, 'netamount', false, User::USER_TYPE_SELLER));
                    $td->appendElement('plaintext', array(), $txt, true);
                    break;
                case 'opshipping_by_seller_user_id':
                    $label = (0 == $order[$key] ? Labels::getLabel('LBL_ADMIN', $siteLangId) : Labels::getLabel('LBL_ME', $siteLangId));
                    $class = (0 == $order[$key] ? 'badge-warning' : 'badge-success');
                    if (Product::PRODUCT_TYPE_DIGITAL == $order['op_product_type']) {
                        $label = Labels::getLabel('LBL_N/A', $siteLangId);
                        $class = 'badge-danger';
                    } else if (Product::PRODUCT_TYPE_SERVICE == $order['op_product_type']) {
                        $label = Labels::getLabel('LBL_ME', $siteLangId);
                        $class = 'badge-success';
                    }
                    $htm = '<span class="badge ' . $class . '">' . $label . '</span>';

                    $td->appendElement('plaintext', array(), $htm, true);
                    break;
                case 'status':
                    if (Orders::ORDER_PAYMENT_CANCELLED == $order["order_payment_status"]) {
                        $txt = Labels::getLabel('LBL_CANCELLED', $siteLangId);
                        $labelClass = 'label-danger';
                    } else {
                        $txt = $order['orderstatus_name'];
                        if (FatApp::getConfig("CONF_DEFAULT_CANCEL_ORDER_STATUS") != $order['orderstatus_id'] && isset($order['plugin_code']) && (isset($order['plugin_code']) && in_array(strtolower($order['plugin_code']), ['cashondelivery', 'payatstore']))) {
                            $txt .= ' (' . $order['plugin_name']  . ')';
                        }
                        $labelClass = isset($classArr[$order['orderstatus_color_class']]) ? $classArr[$order['orderstatus_color_class']] : 'badge-info';
                    }
                    $td->appendElement('span', array('class' => 'badge badge-inline ' . $labelClass), $txt . '<br>', true);
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions"), '', true);

                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array(
                            'href' => $orderDetailUrl,
                            'class' => '',
                            'title' => Labels::getLabel('LBL_View_Order', $siteLangId)
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

                    if (!in_array($order['orderstatus_id'], $notAllowedCancelStatuses) && $canEdit) {
                        $li = $ul->appendElement("li");
                        $li->appendElement(
                            'a',
                            array(
                                'href' => UrlHelper::generateUrl('seller', 'cancelOrder', array($order['op_id'])),
                                'class' => '',
                                'title' => Labels::getLabel('LBL_Cancel_Order', $siteLangId)
                            ),
                            '<i class="icn">
                            <svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#close">
                                </use>
                            </svg>
                        </i>',
                            true
                        );
                    }
                    break;
                default:
                    $td->appendElement('plaintext', array(), '' . $order[$key], true);
                    break;
            }
        }
    }
    echo $tbl->getHtml();
    if (count($orders) == 0) {
        $message = Labels::getLabel('LBL_No_Records_Found', $siteLangId);
        $this->includeTemplate('_partial/no-record-found.php', array('siteLangId' => $siteLangId, 'message' => $message));
    } ?>
</div>
<?php $postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmOrderSrchPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToOrderSearchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
