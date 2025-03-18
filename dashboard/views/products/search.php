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
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="record_ids[]" value=' . $row['product_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'images':
                $str = HtmlHelper::imageListCard(AttachedFile::FILETYPE_PRODUCT_IMAGE, $row['product_name'], $row['product_id'], 0, $row['product_updated_on']);
                $td->appendElement('plaintext', $tdAttr, $str, true);
                break;
            case 'product_identifier':
                $str = '<div class="user-profile">
                            <div class="user-profile_data">
                                <span class="user-profile_title">' . $row['product_name'] . '</span>
                                <span class="text-muted">' . $row[$key] . '</span>
                            </div>
                        </div>';
                $td->appendElement('plaintext', $tdAttr, $str, true);
                break;
            case 'user_name':
                if ($canViewUsers) {
                    !empty($row[$key]) ? $td->appendElement('a', array('href' => 'javascript:void(0)', 'onclick' => 'redirectfunc("' . UrlHelper::generateUrl('Users') . '",{user_id:' . $row['product_seller_id'] . '})'), $row[$key]) : $td->appendElement('plaintext', $tdAttr, (!empty($row[$key]) ? $row[$key] : 'Admin'), true);
                } else {
                    $td->appendElement('plaintext', $tdAttr, (!empty($row[$key]) ? $row[$key] : 'Admin'), true);
                }
                break;
            case 'attrgrp_name':
                $td->appendElement('plaintext', $tdAttr, CommonHelper::displayNotApplicable($siteLangId, $row[$key]), true);
                break;
            case 'product':
                $td->appendElement('plaintext', $tdAttr, ($row['product_seller_id']) ? 'Custom' : 'Catalog');
                break;
            case 'product_approved':
                $statusHtm = Product::getStatusHtml($siteLangId, $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $statusHtm, true);
                break;
            case 'product_active':
                $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['product_id'] . ', ' . ((int) !$row[$key]) . ')' : 'return false;';
                $statusClass = ($canEdit) ? '' : 'disabled';
                $checked = applicationConstants::ACTIVE == $row[$key] ? 'checked' : '';

                $htm = '<span class="switch switch-sm switch-icon">
                            <label>
                                <input type="checkbox" data-old-status="' . $row[$key] . '" value="' . $row['product_id'] . '" ' . $checked . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                                <span class="input-helper"></span>
                            </label>
                        </span>';
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'product_added_on':
                $td->appendElement('plaintext', $tdAttr, HtmlHelper::formatDateTime($row[$key], true), true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['product_id']
                ];

                if ($canEdit) {
                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => UrlHelper::generateUrl('Products', 'form', array($row['product_id'])),
                            'title' => Labels::getLabel('LBL_EDIT', $siteLangId)
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                            </use>
                        </svg>'
                    ];
                    $data['deleteButton'] = [];
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


include (CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
