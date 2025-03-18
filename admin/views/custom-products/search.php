<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
$page = $page ?? 0;
$pageSize = $pageSize ?? 0;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;

foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="record_ids[]" value=' . $row['preq_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'images':
                $str = HtmlHelper::imageListCard(AttachedFile::FILETYPE_CUSTOM_PRODUCT_IMAGE, $row['product_name'], $row['preq_id']);
                $td->appendElement('plaintext', $tdAttr, $str, true);
                break;
            case 'product_identifier':
                $str = '<div class="user-profile">
                            <div class="user-profile_data">
                                <span class="user-profile_title">' . $row['product_name'] . '</span>';
                if ($row['product_name'] != $row[$key]) {
                    $str .= '<span class="text-muted">' . $row[$key] . '</span>';
                }
                $str .= '</div>
                        </div>';
                $td->appendElement('plaintext', $tdAttr, $str, true);
                break;
            case 'shop_name':
                $str = $this->includeTemplate('_partial/shop/shop-info-card.php', ['shop' => $row, 'siteLangId' => $siteLangId], false, true);
                $td->appendElement('plaintext', $tdAttr, $str, true);
                break;
            case 'preq_status':
                $td->appendElement('plaintext', $tdAttr, ProductRequest::getPaymentStatusHtml($siteLangId, $row[$key]), true);
                break;
            case 'preq_requested_on':
            case 'preq_added_on':
                $td->appendElement('plaintext', $tdAttr, HtmlHelper::formatDateTime($row[$key], true), true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['preq_id']
                ];
                if ($canEdit && $row['preq_status'] == ProductRequest::STATUS_PENDING) {
                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => UrlHelper::generateUrl('CustomProducts', 'form', array($row['preq_id'])),
                            'title' => Labels::getLabel('LBL_EDIT', $siteLangId)
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                            </use>
                        </svg>'
                    ];

                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'requestStatusForm(' . $row['preq_id'] . ')',
                            'title' => Labels::getLabel('MSG_UPDATE_STATUS', $siteLangId),
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#form">
                                        </use>
                                    </svg>',
                    ];

                    $data['deleteButton'] = false;
                }
                if ($canEdit && !empty($row['preq_comment']) && $row['preq_status'] == ProductRequest::STATUS_CANCELLED) {
                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'getComments(' . $row['preq_id'] . ')',
                            'title' => Labels::getLabel('MSG_COMMENTS', $siteLangId),
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#eye">
                                        </use>
                                    </svg>',
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
