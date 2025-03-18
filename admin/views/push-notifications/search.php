<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}

$serialNo = ($page - 1) * $pageSize + 1;
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['pnotification_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo);
                break;
            case 'pnotification_title':
                $body = $row['pnotification_description'];
                $htm =  '<strong>' . $row['pnotification_title'] . '</strong><br>';
                $htm .= strlen((string)$body) > 50 ? substr($body, 0, 50) . "..." : $body;
                $htm = '<span title="' . $body . '" data-bs-toggle="tooltip" data-placement="top">' . $htm . '</span>';
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'pnotification_user_auth_type':
                $authTypeHtm = PushNotification::getAuthTypeHtml($siteLangId, $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $authTypeHtm, true);
                break;
            case 'pnotification_device_os':
                $deviceTypeHtm = PushNotification::getDeviceTypeHtml($siteLangId, $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $deviceTypeHtm, true);
                break;
            case 'pnotification_notified_on':
                $td->appendElement('plaintext', $tdAttr, HtmlHelper::formatDateTime(
                    $row[$key],
                    true,
                    true,
                    FatApp::getConfig('CONF_TIMEZONE', FatUtility::VAR_STRING, date_default_timezone_get())
                ), true);
                break;
            case 'pnotification_status':
                $statusHtm = PushNotification::getStatusHtml($siteLangId, $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $statusHtm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['pnotification_id']
                ];

                if ($canEdit) {
                    $onclick = '';
                    $class = 'disabled';
                    $title = Labels::getLabel('LBL_NOT_ALLOWED_TO_EDIT_THIS_RECORD', $siteLangId);
                    if (PushNotification::STATUS_PENDING == $row['pnotification_status']) {
                        $onclick = 'editPushNotification(' . $row['pnotification_id'] . ', ' . $row['pnotification_lang_id'] . ');';
                        $class = $title = '';
                    }
                    $data['editButton'] = [
                        'onclick' => $onclick,
                        'class' => $class,
                        'title' => $title,
                    ];

                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0);',
                                'onclick' => 'view(' . $row['pnotification_id'] . ')',
                                'title' => Labels::getLabel('LBL_VIEW', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                                </use>
                                            </svg>'
                        ],
                        [
                            'attr' => [
                                'href' => 'javascript:void(0);',
                                'onclick' => 'clone(' . $row['pnotification_id'] . ', ' . $row['pnotification_lang_id'] . ');',
                                'title' => Labels::getLabel('LBL_CLONE_RECORD', $siteLangId)
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#clone">
                                                </use>
                                            </svg>'
                        ]
                    ];
                }

                $actionItems = $this->includeTemplate('_partial/listing/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
        }
    }
    $serialNo++;
}

include (CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
