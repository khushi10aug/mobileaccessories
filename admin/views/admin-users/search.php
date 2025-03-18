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
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $disabled = ($row['admin_id'] > 1) ? '' : 'disabled';
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs  ' . $disabled . '" type="checkbox" ' . $disabled . ' name="record_ids[]" value=' . $row['admin_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'admin_active':
                $htm = HtmlHelper::addStatusBtnHtml($canEdit, $row['admin_id'], $row[$key], (1 == $row['admin_id']));
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['admin_id']
                ];
                if ($canEdit) {
                    $data['editButton'] = [];
                    if ($row['admin_id'] > 1 && $row['admin_id'] != $adminLoggedInId) {
                        $data['otherButtons'][] = [
                            'attr' => [
                                'href' => UrlHelper::generateUrl('AdminPermissions', 'list', [$row['admin_id']]),
                                'title' => Labels::getLabel('LBL_PERMISSIONS', $siteLangId),
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#user-permission">
                                                </use>
                                            </svg>',
                        ];
                    }

                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'changeUserPassword(' . $row['admin_id'] . ')',
                            'title' => Labels::getLabel('LBL_CHANGE_PASSWORD', $siteLangId),
                        ],
                        'label' => '<svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#password">
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

include (CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
