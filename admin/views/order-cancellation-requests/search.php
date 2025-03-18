<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}
foreach ($arrListing as $sn => $row) {

    $cls = (($row["ocrequest_id"] % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $row["ocrequest_id"]]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'listSerial':
                $listSerial = '#C' . str_pad($row["ocrequest_id"], 5, '0', STR_PAD_LEFT);
                $td->appendElement('plaintext', $tdAttr,  $listSerial);
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
                    'credential_email' => $row['seller_email'],

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
            case 'reuqest_detail':
                $html = $this->includeTemplate('_partial/product/order-product-info-card.php', ['order' => $row, 'siteLangId' => $siteLangId, 'horizontalAlignOptions' => true], false, true);
                $td->appendElement('plaintext', $tdAttr, $html, true);
                break;
            case 'amount':
                $amt = CommonHelper::displayMoneyFormat(CommonHelper::orderProductAmount($row, 'netamount'), true, true);
                $td->appendElement('plaintext', $tdAttr, $amt, true);
                break;
            case 'ocrequest_date':
                $td->appendElement('plaintext', $tdAttr, HtmlHelper::formatDateTime($row[$key], true), true);
                break;
            case 'orderstatus_name':
                $orderStatus = ucwords($row['orderstatus_name']);
                if (Orders::ORDER_PAYMENT_CANCELLED == $row["order_payment_status"]) {
                    $orderStatus = Labels::getLabel('LBL_CANCELLED', $siteLangId);
                } else {
                    if(0 < $row['order_pmethod_id']){
                        $pluginData = Plugin::getAttributesByLangId($siteLangId, $row['order_pmethod_id'], ['plugin_name', 'plugin_code'],true);
                        if ($pluginData && (isset($pluginData['plugin_code']) && in_array(strtolower($pluginData['plugin_code']), ['cashondelivery', 'payatstore']))) {                                              
                            if ($orderStatus != $pluginData['plugin_name']) {
                                $orderStatus .= " - " . $pluginData['plugin_name'];
                            }
                        }
                    }                    
                }

                $orderStatus = OrderProduct::getStatusHtml((int)$row["orderstatus_color_class"], $orderStatus);
                $td->appendElement('plaintext', $tdAttr, $orderStatus, true);
                break;
            case 'ocrequest_status':
                $reqStatus = OrderCancelRequest::getStatusHtml($siteLangId, $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $reqStatus, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['ocrequest_id']
                ];

                $data['otherButtons'] = [
                    [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'viewComment(' . $row['ocrequest_id'] . ')',
                            'title' => Labels::getLabel('MSG_CLICK_TO_VIEW_EXTRA_INFO', $siteLangId),
                        ],
                        'label' => '<i class="icn">
                                        <svg class="svg" width="18" height="18">
                                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#comment">
                                            </use>
                                        </svg>
                                    </i>',
                    ],
                ];

                if ($canEdit && $row['ocrequest_status'] == OrderCancelRequest::CANCELLATION_REQUEST_STATUS_PENDING) {
                    $arr = [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'editRecord(' . $row['ocrequest_id'] . ',true)',
                            'title' => Labels::getLabel('MSG_UPDATE_STATUS', $siteLangId),
                        ],
                        'label' => '<i class="icn">
                                        <svg class="svg" width="18" height="18">
                                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#form">
                                            </use>
                                        </svg>
                                    </i>',
                    ];
                    array_unshift($data['otherButtons'], $arr);
                } else if ($row['ocrequest_status'] == OrderCancelRequest::CANCELLATION_REQUEST_STATUS_APPROVED && !empty($row['ocrequest_admin_comment'])) {
                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'viewAdminComment(' . $row['ocrequest_id'] . ')',
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

                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                break;
        }
    }
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
