<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
$page = $page ?? 0;
$pageSize = $pageSize ?? 0;
$recordCount = $recordCount ?? 0;
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
            case 'listSerial':
                $td->appendElement('plaintext', array(), $serialNo);
                break;
            case 'abandonedcart_action':
                if ($row[AbandonedCart::DB_TBL_PREFIX . 'discount_notification'] == 1 && $row[$key] != AbandonedCart::ACTION_PURCHASED) {
                    $lbl = Labels::getLabel('LBL_DISCOUNT_COUPON_SENT', $siteLangId);
                    $statusHtm = '<span class="badge badge-primary">' . $lbl . '</span>';
                } else {
                    $statusHtm = AbandonedCart::getActionLabelHtml($siteLangId, $row[$key]);
                }

                $td->appendElement('plaintext', $tdAttr, $statusHtm, true);
                break;
            case 'abandonedcart_added_on':
                $td->appendElement('plaintext', array(), HtmlHelper::formatDateTime($row[$key], true, true, FatApp::getConfig('CONF_TIMEZONE', FatUtility::VAR_STRING, date_default_timezone_get())), true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['abandonedcart_id']
                ];

                if ($canEdit && $row['abandonedcart_action'] < AbandonedCart::ACTION_PURCHASED && $row[AbandonedCart::DB_TBL_PREFIX . 'discount_notification'] == 0) {
                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => 'javascript:void(0);',
                            'title' => Labels::getLabel('LBL_SEND_DISCOUNT_NOTIFICATION', $siteLangId),
                            'onclick' => 'discountNotification(' . $row['abandonedcart_id'] . ',' . $row['abandonedcart_user_id'] . ',' . $row['selprod_product_id'] . ')'
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#percent">
                                            </use>
                                        </svg>',
                    ];
                }

                if ($row['abandonedcart_action'] == AbandonedCart::ACTION_PURCHASED) {
                    $td->appendElement('plaintext', $tdAttr, CommonHelper::displayMoneyFormat($row['abandonedcart_amount']));
                } else {
                    $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                    $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                }
                break;
            default:
                $td->appendElement('plaintext', array(), $row[$key], true);
                break;
        }
    }
    $serialNo++;
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
