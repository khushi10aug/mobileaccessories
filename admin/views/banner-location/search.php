<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['blocation_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="record_ids[]" value=' . $row['blocation_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'blocation_promotion_cost':
                $td->appendElement('plaintext', $tdAttr, CommonHelper::displayMoneyFormat($row[$key]), true);
                break;
            case 'blocation_active':
                $statusAct = ($canEdit) ? 'updateStatus(event, this, ' . $row['blocation_id'] . ', ' . ((int) !$row[$key]) . ')' : 'return false;';
                $statusClass = ($canEdit) ? '' : 'disabled';
                $checked = applicationConstants::ACTIVE == $row[$key] ? 'checked' : '';
                $htm = '<span class="switch switch-sm switch-icon">
                    <label>
                        <input type="checkbox" data-old-status="' . $row[$key] . '" value="' . $row['blocation_id'] . '" ' . $checked . ' onclick="' . $statusAct . '" ' . $statusClass . '>
                        <span class="input-helper"></span>
                    </label>
                </span>';
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['blocation_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = [];
                }
                $data['otherButtons'] = [
                    [
                        'attr' => [
                            'href' =>  'javascript:void(0);',
                            'onclick' => "displayImageInFacebox('" . CONF_WEBROOT_URL . "images/banner_layouts/layout-3.jpg');",
                            'title' => Labels::getLabel('LBL_PRODUCT_DETAIL_PAGE_LAYOUT', $siteLangId),
                        ],
                        'label' => '<i class="icn">
                            <svg class="svg" width="18" height="18">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#form">
                                </use>
                            </svg>
                        </i>'
                    ],
                    [
                        'attr' => [
                            'href' =>   UrlHelper::generateUrl('Banners', 'list', [$row['blocation_id']]),
                            'title' => Labels::getLabel('LBL_BANNERS', $siteLangId),
                        ],
                        'label' => '<i class="icn">
                            <svg class="svg" width="18" height="18">
                                <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#list">
                                </use>
                            </svg>
                        </i>'
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

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
