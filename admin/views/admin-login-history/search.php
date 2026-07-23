<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page > 1) ? $recordCount - (($page - 1) * $pageSize) : $recordCount;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls]);

    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'alh_login_type':
                $td->appendElement('plaintext', $tdAttr, $loginTypes[$row['alh_login_type']] ?? '-', true);
                break;
            case 'alh_logged_at':
            case 'alh_logout_at':
                $value = !empty($row[$key]) ? FatDate::format($row[$key], true) : '-';
                $td->appendElement('plaintext', $tdAttr, $value, true);
                break;
            case 'time_spent':
                $td->appendElement('plaintext', $tdAttr, AdminLoginHistory::formatSessionDuration($row, $siteLangId), true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['alh_id'],
                    'otherButtons' => [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => 'viewLog(' . $row['alh_id'] . ')',
                                'title' => Labels::getLabel('LBL_VIEW', $siteLangId),
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                            </use>
                                        </svg>',
                        ],
                    ],
                ];

                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', $tdAttr, $row[$key] ?? '-', true);
                break;
        }
    }
    $serialNo--;
}

include CONF_THEME_PATH . '_partial/listing/no-record-found.php';

if ($printData) {
    echo $tbody->getHtml();
}
