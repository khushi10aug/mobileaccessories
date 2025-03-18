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
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['selprod_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="selprod_ids[]" value=' . $row['selprod_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'selprod_title':
                $str = $this->includeTemplate('_partial/product/product-info-card.php', ['product' => $row, 'options' => $row['options'], 'displayProductName' => true, 'canViewProducts' => $canViewProducts, 'siteLangId' => $siteLangId ,'redirectSelprod' => false], false, true);
                $td->appendElement('plaintext', array(), $str, true);
                break;
            case 'user_name':
                if ($canViewUsers) {
                    $td->appendElement('a', array('href' => 'javascript:void(0)', 'onclick' => 'redirectUser(' . $row['selprod_user_id'] . ')'), $row['user_name'], true);
                } else {
                    $td->appendElement('plaintext', array(), $row['user_name'], true);
                }
                $userDetail = '<br/>' . $row['credential_email'];
                $td->appendElement('plaintext', array(), $userDetail, true);
                break;
            case 'selprod_price':
                $td->appendElement('plaintext', array(), CommonHelper::displayMoneyFormat($row[$key], true, true), true);
                break;
            
            case 'selprod_active':
                $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['selprod_id'] . ', ' . ((int) !$row[$key]) . ')' : 'return false;';
                $statusClass = ($canEdit) ? '' : 'disabled';
                $checked = applicationConstants::ACTIVE == $row[$key] ? 'checked' : '';

                $htm = '<label class="switch switch-sm switch-icon switch-inline">
                                        <input type="checkbox" data-old-status="' . $row[$key] . '" value="' . $row['selprod_id'] . '" ' . $checked . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                                        <span class="input-helper"></span>
                                    
                                </label>';
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['selprod_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = [];
                    $data['deleteButton'] = [];
                }
                if ($row['product_type'] == Product::PRODUCT_TYPE_DIGITAL) {
                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => "sellerProductDownloadFrm(" . $row['selprod_id'] . ")",
                                'title' => Labels::getLabel('LBL_DOWNLOADS', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-download">
                                            </use>
                                        </svg>'
                        ]
                    ];
                }

                $data['otherButtons'] = [
                    [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => "productMissingInfo(" . $row['selprod_id'] . ")",
                            'title' => Labels::getLabel('LBL_PRODUCT_MISSING_INFO', $siteLangId)
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
