<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['shop_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="record_ids[]" value=' . $row['shop_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'shop_name':
                $str = $this->includeTemplate('_partial/shop/shop-info-card.php', ['shop' => $row, 'siteLangId' => $siteLangId, 'onclick' => 'return false;', 'useFeatherLightJs' => 1], false, true);
                $td->appendElement('plaintext', array(), $str, true);
                break;
            case 'shop_featured':
                $td->appendElement('plaintext', array(), applicationConstants::getYesNoArr($siteLangId)[$row[$key]], true);
                break;
            case 'shop_supplier_display_status':
                $td->appendElement('plaintext', array(), applicationConstants::getOnOffArr($siteLangId)[$row[$key]], true);
                break;
            case 'shop_created_on':
                $dispDate = HtmlHelper::formatDateTime($row[$key], true);
                $td->appendElement('plaintext', $tdAttr, $dispDate, true);
                break;
            case 'shop_active':
                $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['shop_id'] . ', ' . ((int) !$row[$key]) . ')' : 'return false;';
                $statusClass = ($canEdit) ? '' : 'disabled';
                $checked = applicationConstants::ACTIVE == $row[$key] ? 'checked' : '';

                $htm = '<span class="switch switch-sm switch-icon">
                                    <label>
                                        <input type="checkbox" data-old-status="' . $row[$key] . '" value="' . $row['shop_id'] . '" ' . $checked . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                                        <span class="input-helper"></span>
                                    </label>
                                </span>';
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'numOfReports':
                if ($canViewShopReports && 0 < $row[$key]) {
                    $fn = 'redirectToShopReport(' . $row['shop_id'] . ')';
                    $td->appendElement('a', array('href' => 'javascript:void(0)', 'class'=>'link-text', 'onclick' => $fn), $row[$key]);
                } else {
                    $td->appendElement('plaintext', array(), $row[$key], true);
                }
                break;
            case 'numOfReviews':
                if ($canViewShopReports && 0 < $row[$key]) {
                    $fn = 'redirectToProductReviews(' . $row['shop_user_id'] . ')';
                    $td->appendElement('a', array('href' => 'javascript:void(0)', 'class'=>'link-text', 'onclick' => $fn), $row[$key]);
                } else {
                    $td->appendElement('plaintext', array(), $row[$key], true);
                }
                break;
            case 'numOfProducts':
                if ($canViewSellerProducts && 0 < $row[$key]) {
                    $fn = 'redirectToSellerProduct(0, {"user_id" : ' . $row['shop_user_id'] . ', "product_approved" : 1, "product_active" : 1})';
                    $td->appendElement('a', array('href' => 'javascript:void(0)', 'class'=>'link-text', 'onclick' => $fn), $row[$key]);
                } else {
                    $td->appendElement('plaintext', array(), $row[$key], true);
                }
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['shop_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = ['onclick' => 'editRecord(' . $row['shop_id'] . ', false, "modal-dialog-vertical-md")'];
                }
                $data['otherButtons'] = [
                    [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => "shopMissingInfo(" . $row['shop_id'] . ")",
                            'title' => Labels::getLabel('LBL_SHOP_MISSING_INFO', $siteLangId)
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#warning">
                                        </use>
                                    </svg>'
                    ]
                ];
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

include (CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
