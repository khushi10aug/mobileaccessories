<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page > 1) ? $recordCount - (($page - 1) * $pageSize) : $recordCount;
foreach ($arrListing as $sn => $row) {

    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo]);

    $cartData = !empty($row['order_cart_data']) ? json_decode(trim($row['order_cart_data']), true) : [];
    $checkoutType = !empty($cartData) ? $cartData['shopping_cart']['checkout_type'] : Shipping::FULFILMENT_SHIP;
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $class = 'disabled';
                $disabled = "disabled='disabled'";
                $twoDaysAfter = date('Y-m-d H:i:s', strtotime($row['order_date_added'] . ' + 2 days'));
                if (!$row['order_deleted'] && $row['order_payment_status'] == Orders::ORDER_PAYMENT_PENDING && $twoDaysAfter < date('Y-m-d H:i:s')) {
                    $disabled = $class = "";
                }
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs ' . $class . '" type="checkbox" ' . $disabled . ' name="order_ids[]" value=' . $row['order_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'order_number':
                $td->appendElement('a', array('target' => '_blank', 'href' => UrlHelper::generateUrl('giftCardOrders', 'view', array($row['order_id']))), $row[$key], true);
                break;
            case 'ogcards_receiver_name':
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
            case 'ogcards_receiver_email':
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
            case 'buyer_user_name':
                $href = "javascript:void(0)";
                $onclick = ($canViewUsers ? 'redirectUser(' . $row['user_id'] . ')' : '');
                $str = $this->includeTemplate('_partial/user/user-info-card.php', [
                    'user' => $row,
                    'siteLangId' => $siteLangId,
                    'href' => $href,
                    'onclick' => $onclick,
                ], false, true);
                $td->appendElement('plaintext', $tdAttr, '<div class="user-profile">' . $str . '</div>', true);
                break;
            case 'order_net_amount':
                $td->appendElement('plaintext', $tdAttr, CommonHelper::displayMoneyFormat($row['order_net_amount'], true, true));
                break;
            case 'order_date_added':
                $td->appendElement('plaintext', $tdAttr, HtmlHelper::formatDateTime(
                    $row[$key],
                    true,
                    true,
                    FatApp::getConfig('CONF_TIMEZONE', FatUtility::VAR_STRING, date_default_timezone_get())
                ), true);
                break;
            case 'order_payment_status':
                $statusHtm = Orders::getPaymentStatusHtml($siteLangId, $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $statusHtm, true);
                break;

            case 'order_payment_status':
                $cls = applicationConstants::CLASS_INFO;
                switch ($row[$key]) {
                    case Orders::ORDER_PAYMENT_PENDING:
                        $cls = applicationConstants::CLASS_INFO;
                        break;
                    case Orders::ORDER_PAYMENT_PAID:
                        $cls = applicationConstants::CLASS_SUCCESS;
                        break;
                    case Orders::ORDER_PAYMENT_CANCELLED:
                        $cls = applicationConstants::CLASS_DANGER;
                        break;
                }
                if (Orders::ORDER_PAYMENT_CANCELLED == $row["order_payment_status"]) {
                    $value = Labels::getLabel('LBL_CANCELLED', $siteLangId);
                } else {
                    $value = Orders::getOrderPaymentStatusArr($siteLangId)[$row[$key]];
                }

                if (isset($row['plugin_code']) && in_array(strtolower($row['plugin_code']), ['cashondelivery', 'payatstore'])) {
                    $value .= ' (' . $row['plugin_name'] . ' )';
                }

                $td->appendElement('span', array('class' => 'label ' . $cls), $value);
                break;

            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['order_id']
                ];

                if ($canEdit) {
                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => UrlHelper::generateUrl('GiftCardOrders', 'view', array($row['order_id'])),
                                'title' => Labels::getLabel('LBL_VIEW_ORDER', $siteLangId),
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                            </use>
                                        </svg>',
                        ]
                    ];
                }

                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
        }
    }
    $serialNo--;
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
