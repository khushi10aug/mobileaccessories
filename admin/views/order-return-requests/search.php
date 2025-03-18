<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo]);

    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'orrequest_reference':
                $td->appendElement('plaintext', $tdAttr, '<span class="text-nowrap">'.$row['orrequest_reference'].'</span>', true);
                break;
            case 'buyer_detail':
                $href = "javascript:void(0)";
                $onclick = ($canViewUsers ? 'redirectUser(' . $row['user_id'] . ')' : '');
                $str = $this->includeTemplate('_partial/user/user-info-card.php', [
                    'user' => $row,
                    'siteLangId' => $siteLangId,
                    'href' => $href,
                    'onclick' => $onclick,
                    'extraClass' => 'user-profile-sm'
                ], false, true);
                $td->appendElement('plaintext', $tdAttr, '<div class="user-profile">' . $str . '</div>', true);
                break;
            case 'vendor_detail':
                $onclick = $canViewShops && !empty($row['op_shop_id']) ? 'redirectToShop(' . $row['op_shop_id'] . ')' : '';
                $data = [
                    'user_updated_on' => $row['seller_updated_on'],
                    'user_id' => $row['seller_id'],
                    'user_name' => $row['seller_name'],
                    'credential_username' => $row['seller_username'],
                    'credential_email' => $row['seller_email']
                ];
                $title = '';
                if (!empty($row['op_shop_name'])) {
                    $str = Labels::getLabel('LBL_SHOP:_{SHOP}', $siteLangId);
                    $data['extra_text'] = CommonHelper::replaceStringData($str, ['{SHOP}' => $row['op_shop_name']]);
                    $title = Labels::getLabel('LBL_CLICK_HERE_TO_VISIT_SHOP_LIST', $siteLangId);
                }

                $str = $this->includeTemplate('_partial/user/user-info-card.php', ['user' => $data, 'siteLangId' => $siteLangId, 'onclick' => $onclick, 'title' => $title, 'extraClass' => 'user-profile-sm', 'displayProfileImage' => false], false, true);
                $td->appendElement('plaintext', $tdAttr, '<div class="user-profile">' . $str . '</div>', true);
                break;
            case 'product':
                $html = $this->includeTemplate('_partial/product/order-product-info-card.php', ['order' => $row, 'siteLangId' => $siteLangId], false, true);
                $td->appendElement('plaintext', $tdAttr, $html, true);
                break;
            case 'orrequest_type':
                $td->appendElement('plaintext', $tdAttr, isset($requestTypeArr[$row[$key]]) ? $requestTypeArr[$row[$key]] : '', true);
                break;
            case 'orrequest_date':
                $td->appendElement('plaintext', $tdAttr, HtmlHelper::formatDateTime($row[$key], true), true);
                break;
            case 'amount':
                $amt = '';
                $priceTotalPerItem = CommonHelper::orderProductAmount($row, 'netamount', true);
                $price = 0;
                if ($row['orrequest_status'] != OrderReturnRequest::RETURN_REQUEST_STATUS_REFUNDED) {
                    if (FatApp::getConfig('CONF_RETURN_SHIPPING_CHARGES_TO_CUSTOMER', FatUtility::VAR_INT, 0)) {
                        $shipCharges = isset($row['charges'][OrderProduct::CHARGE_TYPE_SHIPPING][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount']) ? $row['charges'][OrderProduct::CHARGE_TYPE_SHIPPING][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount'] : 0;
                        $unitShipCharges = round(($shipCharges / $row['op_qty']), 2);
                        $priceTotalPerItem = $priceTotalPerItem + $unitShipCharges;
                        $price = $priceTotalPerItem * $row['orrequest_qty'];
                    }
                }

                if (!$price) {
                    $price = $priceTotalPerItem * $row['orrequest_qty'];
                    $price = $price + $row['op_refund_shipping'];
                }

                $amt = CommonHelper::displayMoneyFormat($price, true, true);
                $td->appendElement('plaintext', $tdAttr, $amt, true);
                break;
            case 'orrequest_status':
                $statusHtml = OrderReturnRequest::getStatusHtml($siteLangId, $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $statusHtml, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['orrequest_id']
                ];

                if ($row['orrequest_status'] == OrderReturnRequest::RETURN_REQUEST_STATUS_REFUNDED && !empty($row['orrequest_admin_comment'])) {
                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'viewAdminComment(' . $row['orrequest_id'] . ')',
                            'title' => Labels::getLabel('MSG_CLICK_TO_VIEW_ADMIN_COMMENTS', $siteLangId),
                        ],
                        'label' => '<i class="icn">
                                        <svg class="svg" width="18" height="18">
                                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#admin-reply">
                                            </use>
                                        </svg>
                                    </i>',
                    ];
                }

                if ($canEdit) {
                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => UrlHelper::generateUrl('OrderReturnRequests', 'view', [$row['orrequest_id']]),
                            'title' => Labels::getLabel('LBL_VIEW_DETAIL', $siteLangId)
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                        </use>
                                    </svg>'
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
    $serialNo++;
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
