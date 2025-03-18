<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="js-scrollable table-wrap table-responsive">
    <?php $arr_flds = array(
        'order_id' => Labels::getLabel('LBL_Order_Id_Date', $siteLangId),
        'ossubs_subscription_name' => Labels::getLabel('LBL_PACKAGE_NAME', $siteLangId),
        'total' => Labels::getLabel('LBL_Total', $siteLangId),
        'ossubs_frequency' => Labels::getLabel('LBL_FREQUENCY', $siteLangId),
        'ossubs_status_id' => Labels::getLabel('LBL_Status', $siteLangId),
        'ossubs_till_date' => Labels::getLabel('LBL_Subscription_Valid_till', $siteLangId),
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
        $orderDetailUrl = UrlHelper::generateUrl('seller', 'viewSubscriptionOrder', array($order['ossubs_id']));

        foreach ($arr_flds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'order_id':
                    $txt = '<a title="' . Labels::getLabel('LBL_View_Order_Detail', $siteLangId) . '" href="' . $orderDetailUrl . '">';
                    $txt .= $order['order_number'];
                    $txt .= '</a><br/>' . FatDate::format($order['order_date_added']);
                    $td->appendElement('plaintext', array(), $txt, true);
                    break;
                case 'ossubs_subscription_name':
                    $td->appendElement('plaintext', array(), $order[$key], true);
                    break;
                case 'ossubs_frequency':
                    $subcriptionPeriodArr = SellerPackagePlans::getSubscriptionPeriods($siteLangId);
                    $td->appendElement('plaintext', array(), (($order['ossubs_interval'] > 0) ? $order['ossubs_interval'] . ' ' : '') . $subcriptionPeriodArr[$order[$key]], true);
                    break;
                case 'ossubs_status_id':
                    $txt = '';
                    if ($order['ossubs_status_id'] == FatApp::getConfig('CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS') && $order['ossubs_till_date'] < date("Y-m-d")) {
                        $txt .= Labels::getLabel('LBL_Expired', $siteLangId);
                    } else {
                        $txt .= $orderStatuses[$order['ossubs_status_id']];
                    }

                    $bannerClass = applicationConstants::CLASS_WARNING;
                    if ($order['ossubs_status_id'] == OrderSubscription::ACTIVE_SUBSCRIPTION) {
                        $bannerClass = applicationConstants::CLASS_SUCCESS;
                    } elseif ($order['ossubs_status_id'] == OrderSubscription::CANCELLED_SUBSCRIPTION) {
                        $bannerClass = applicationConstants::CLASS_DANGER;
                    }
                    $td->appendElement('span', array('class' => 'badge badge-inline ' . $bannerClass), $txt, true);
                    break;
                case 'total':
                    $txt = CommonHelper::displayMoneyFormat(CommonHelper::orderSubscriptionAmount($order));
                    $td->appendElement('plaintext', array(), $txt, true);
                    break;
                case 'status':
                    $td->appendElement('plaintext', array(), $order['orderstatus_name'], true);
                    break;
                case 'ossubs_till_date':
                    if(SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED == $order['ossubs_frequency']) {
                        $subcriptionPeriodArr = SellerPackagePlans::getSubscriptionPeriods($siteLangId);
                        $td->appendElement('plaintext', array(), (($order['ossubs_interval'] > 0) ? $order['ossubs_interval'] . ' ' : '') . $subcriptionPeriodArr[$order['ossubs_frequency']], true);
                    } else {
                        if ($order['ossubs_from_date'] == 0 || $order['ossubs_till_date'] == 0) {
                            $subscritpionValidTill = Labels::getLabel('LBL_N/A', $siteLangId);
                        } else {
                            $subscritpionValidTill = FatDate::format($order['ossubs_from_date']) . " - " . FatDate::format($order['ossubs_till_date']);
                        }
                        $txt = $subscritpionValidTill;
                        $td->appendElement('plaintext', array(), $txt, true);
                    }

                    break;
                case 'action':
                    $ul = $td->appendElement("ul", array("class" => "actions"), '', true);

                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        array('href' => $orderDetailUrl, 'class' => '', 'title' => Labels::getLabel('LBL_View_Order', $siteLangId)),
                        '<i class="icn">
                            <svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                </use>
                            </svg>
                        </i>',
                        true
                    );

                    if ($canEdit && /* date("Y-m-d") >= $order['ossubs_till_date'] && */ $order['ossubs_status_id'] == FatApp::getConfig('CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS') && $order['ossubs_type'] == SellerPackages::PAID_TYPE) {
                        $li = $ul->appendElement("li");
                        $li->appendElement('a', array('href' => 'javascript:void(0);', 'title' => Labels::getLabel('LBL_RENEW_SUBSCRIPTION', $siteLangId), 'onclick' => 'renewSubscription(' . $order['ossubs_id'] . ')'), '<svg class="svg" width="18" height="18">
                        <use
                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#counterclockwise">
                        </use>
                    </svg', true);
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
