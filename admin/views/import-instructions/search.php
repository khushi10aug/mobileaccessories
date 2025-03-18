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
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'epage_identifier':
                if ($row['epage_label'] != '') {
                    $td->appendElement('plaintext', $tdAttr, $row['epage_label'], true);
                } else {
                    $td->appendElement('plaintext', $tdAttr, $row[$key], true);
                }
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['epage_id']
                ];

                if ($canEdit) {
                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => "editLangData(" . $row['epage_id'] . "," . CommonHelper::getDefaultFormLangId() . ");",
                                'title' => Labels::getLabel('LBL_EDIT', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#edit">
                                            </use>
                                        </svg>'
                        ]
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
