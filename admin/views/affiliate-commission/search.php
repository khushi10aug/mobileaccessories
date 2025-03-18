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
                if (1 > $row['afcommsetting_is_mandatory']) {
                    $disabled = '';
                    $disabledCls = '';
                    if (0 == $row['afcommsetting_prodcat_id'] && 0 == $row['afcommsetting_user_id']) {
                        $disabledCls = 'disabled';
                        $disabled = 'disabled="disabled"';
                    }
                    $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs ' . $disabledCls . '" type="checkbox" ' . $disabled . ' name="afcommsetting_ids[]" value=' . $row['afcommsetting_id'] . '><i class="input-helper"></i></label>', true);
                }
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'afcommsetting_prodcat_id':
                $td->appendElement('plaintext', $tdAttr, CommonHelper::displayText($row['prodcat_name']), true);
                break;
            case 'afcommsetting_user_id':
                $td->appendElement('plaintext', $tdAttr, CommonHelper::displayText($row['credential_username']), true);
                break;
            case 'afcommsetting_fees':
                $td->appendElement('plaintext', $tdAttr, CommonHelper::numberFormat($row[$key]), true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['afcommsetting_id']
                ];

                if ($canEdit) {
                    $data['editButton'] = [];

                    if ($row['afcommsetting_is_mandatory'] != 1) {
                        $data['deleteButton'] = [];
                    }

                    if (0 == $row['afcommsetting_prodcat_id'] && 0 == $row['afcommsetting_user_id']) {
                        $data['deleteButton'] =  [
                            'class' => 'disabled',
                            'title' => Labels::getLabel('ERR_NOT_AUTHORIZED', $siteLangId),
                            'onclick' => 'javascript:void(0);',
                        ];
                    }

                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => "viewLog(" . $row['afcommsetting_id'] . ")",
                                'title' => Labels::getLabel('LBL_VIEW_LOG', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#history">
                                            </use>
                                        </svg>'
                        ]
                    ];
                }
                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', $tdAttr, CommonHelper::displayText($row[$key]), true);
                break;
        }
    }
    $serialNo++;
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
