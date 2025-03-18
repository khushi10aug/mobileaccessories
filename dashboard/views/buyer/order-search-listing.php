<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php
    $arr_flds = array(
        'order_id' => Labels::getLabel('LBL_Order_ID_Date', $siteLangId),
        'product' => Labels::getLabel('LBL_Details', $siteLangId),
        'total' => Labels::getLabel('LBL_Total', $siteLangId),
        'status' => Labels::getLabel('LBL_Status', $siteLangId),
        'action' => '',
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

    foreach ($orders as $sn => $order) {
        $sr_no++;
        $tr = $tbl->appendElement('tr', array('class' => ''));
        $orderDetailUrl = UrlHelper::generateUrl('Buyer', 'viewOrder', array($order['order_id'], $order['op_id']));

        if ($order['op_product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
            $canCancelOrder = true;
        } else {
            $canCancelOrder = false;
        }
        $canCancelOrder = (in_array($order["op_status_id"], (array) Orders::getBuyerAllowedOrderCancellationStatuses($canCancelOrder)));
        $canReturnRefund = (in_array($order["op_status_id"], (array) Orders::getBuyerAllowedOrderReturnStatuses($order['op_product_type'])));

        $isValidForReview = false;
        if (in_array($order["op_status_id"], SelProdReview::getBuyerAllowedOrderReviewStatuses())) {
            $isValidForReview = true;
        }
        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'order_id':
                    $txt = '<a title="' . Labels::getLabel('LBL_View_Order_Detail', $siteLangId) . '" href="' . $orderDetailUrl . '">';
                    if ($order['totOrders'] > 1) {
                        $txt .= $order['op_invoice_number'];
                    } else {
                        $txt .= $order['order_number'];
                    }
                    $txt .= '</a><br/>' . FatDate::format($order['order_date_added']);
                    $td->appendElement('plaintext', array(), $txt, true);
                    break;
                case 'product':
                    $txt = $this->includeTemplate('_partial/product/product-info-html.php', ['order' => $order, 'siteLangId' => $siteLangId], false, true);
                    $td->appendElement('plaintext', array(), $txt, true);
                    break;
                case 'total':
                    $txt = CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($order));
                    $td->appendElement('plaintext', array(), $txt, true);
                    break;
                case 'status':
                    $orderStatus = ucwords($order['orderstatus_name']);
                    if (Orders::ORDER_PAYMENT_CANCELLED == $order["order_payment_status"]) {
                        $orderStatus = Labels::getLabel('LBL_CANCELLED', $siteLangId);
                        $labelClass = 'label-danger';
                    } else {
                        $pMethod = '';
                        $paymentMethodCode = Plugin::getAttributesById($order['order_pmethod_id'], 'plugin_code');
                        if (isset($paymentMethodCode) && in_array(strtolower($paymentMethodCode), ['cashondelivery', 'payatstore'])) {
                            if ($orderStatus != $order['plugin_name']) {
                                $orderStatus .= " - " . $order['plugin_name'];
                            }
                        }

                        $labelClass = isset($classArr[$order['orderstatus_color_class']]) ? $classArr[$order['orderstatus_color_class']] : 'badge-info';
                    }

                    $td->appendElement('span', array('class' => 'badge badge-inline ' . $labelClass), $orderStatus . '<br>', true);
                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions"), '', true);

                    $opCancelUrl = UrlHelper::generateUrl('Buyer', 'orderCancellationRequest', array($order['op_id']));
                    $datediff = time() - strtotime($order['order_date_added']);
                    $daysSpent = $datediff / (60 * 60 * 24);
                    $returnAge = $order['op_selprod_return_age'];
                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array(
                            'href' => $orderDetailUrl,
                            'class' => '',
                            'title' => Labels::getLabel('LBL_View_Order', $siteLangId)
                        ),
                        '<svg class="svg" width="18" height="18">
                        <use
                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                        </use>
                    </svg>',
                        true
                    );

                    if ($canCancelOrder && false === OrderCancelRequest::getCancelRequestById($order['op_id']) && $order['op_selprod_cancellation_age'] > $daysSpent) {
                        $li = $ul->appendElement("li");
                        $li->appendElement(
                            'a',
                            array(
                                'href' => $opCancelUrl,
                                'class' => '',
                                'title' => Labels::getLabel('LBL_Cancel_Order', $siteLangId)
                            ),
                            '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#close">
                            </use>
                        </svg>',
                            true
                        );
                    }
                    $canSubmitFeedback = Orders::canSubmitFeedback($order['order_user_id'], $order['order_id'], $order['op_selprod_id']);
                    if ($canSubmitFeedback && $isValidForReview) {
                        $opFeedBackUrl = UrlHelper::generateUrl('Buyer', 'orderFeedback', array($order['op_id']));
                        $li = $ul->appendElement("li");
                        $li->appendElement(
                            'a',
                            array(
                                'href' => $opFeedBackUrl,
                                'class' => '',
                                'title' => Labels::getLabel('LBL_Feedback', $siteLangId)
                            ),
                            '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#comment">
                            </use>
                        </svg>',
                            true
                        );
                    }

                    if ($canReturnRefund && ($order['return_request'] == 0 && $order['cancel_request'] == 0) && $returnAge > $daysSpent) {
                        $opRefundRequestUrl = UrlHelper::generateUrl('Buyer', 'orderReturnRequest', array($order['op_id']));
                        $li = $ul->appendElement("li");
                        $li->appendElement(
                            'a',
                            array(
                                'href' => $opRefundRequestUrl,
                                'class' => '',
                                'title' => Labels::getLabel('LBL_Refund', $siteLangId)
                            ),
                            '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#order-return">
                            </use>
                        </svg>',
                            true
                        );
                    }
                    
                    if (SellerProduct::CART_TYPE_RFQ_ONLY != $order['selprod_cart_type'] && (is_null($order['selprod_hide_price']) || false == SellerProduct::isPriceHidden($order['selprod_hide_price'], $order['shop_rfq_enabled']))) {
                        $li = $ul->appendElement("li");
                        $li->appendElement(
                            'a',
                            array(
                                'href' => 'javascript:void(0)', 'onclick' => 'return addItemsToCart("' . $order['order_id'] . '");',
                                'title' => Labels::getLabel('LBL_Re-Order', $siteLangId)
                            ),
                            '<svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#cart">
                                </use>
                            </svg>',
                            true
                        );
                    }

                    if (!$order['order_deleted'] && !$order["order_payment_status"] && 'TransferBank' == $order['plugin_code']) {
                        $li = $ul->appendElement("li");
                        $li->appendElement(
                            'a',
                            array(
                                'href' => UrlHelper::generateUrl('Buyer', 'viewOrder', [$order['order_id']]),
                                'title' => Labels::getLabel('LBL_ADD_PAYMENT_DETAIL', $siteLangId)
                            ),
                            '<i class="fas fa-box-open"></i>',
                            true
                        );
                    }
                    break;
                default:
                    $td->appendElement('plaintext', array(), $order[$key], true);
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
echo FatUtility::createHiddenFormFromData($postedData, array('name' => 'frmRecordSearchPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToSearchPage');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
