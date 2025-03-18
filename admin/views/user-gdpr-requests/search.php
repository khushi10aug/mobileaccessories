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
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo, 'id' => $row['ureq_id']]);
    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'user_name':
                $str = $this->includeTemplate('_partial/user/user-info-card.php', ['user' => $row, 'siteLangId' => $siteLangId], false, true);
                $td->appendElement('plaintext', $tdAttr, '<div class="user-profile">' . $str . '</div>', true);
                break;
            case 'ureq_date':
                $td->appendElement('plaintext', array(), HtmlHelper::formatDateTime($row[$key], true, true, FatApp::getConfig('CONF_TIMEZONE', FatUtility::VAR_STRING, date_default_timezone_get())), true);
                break;
            case 'ureq_type':
                $str = ($row['ureq_type'] == UserGdprRequest::TYPE_TRUNCATE) ? UserGdprRequest::TYPE_TRUNCATE : UserGdprRequest::TYPE_DATA_REQUEST;
                $td->appendElement('plaintext', array(), $userRequestTypeArr[$str], true);
                break;
            case 'ureq_status':
                $statusHtm = UserGdprRequest::getStatusHtml($siteLangId, $row[$key]);
                $td->appendElement('plaintext', $tdAttr, $statusHtm, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['ureq_id']
                ];

                if ($canEdit && $row['ureq_status'] == UserGdprRequest::STATUS_PENDING) {
                    $data['otherButtons'] = [];
                    $pending = [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'updateRequestStatus(' . $row['ureq_id'] . ',' . UserGdprRequest::STATUS_COMPLETE . ')',
                            'title' => Labels::getLabel('LBL_COMPLETE', $siteLangId)
                        ],
                        'label' => '<i class="icn">
                        <svg class="svg" width="18" height="18">
                        <use
                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-completed">
                        </use>
                    </svg>
                </i>'
                    ];
                    $data['otherButtons'][] = $pending;

                    $truncate = [];
                    if ($row['ureq_type'] == UserGdprRequest::TYPE_TRUNCATE) {
                        $truncate = [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => 'truncateUserData(' . $row['user_id'] . ',' . $row['ureq_id'] . ')',
                                'title' => Labels::getLabel('LBL_Truncate_User_Data', $siteLangId)
                            ],
                            'label' => '<i class="icn">
                                <svg class="svg" width="18" height="18">
                                <use
                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-user-truncate">
                                </use>
                            </svg>
                        </i>'
                        ];
                        $data['otherButtons'][] = $truncate;
                    }
                    $requestData = [];
                    if ($row['ureq_type'] == UserGdprRequest::TYPE_DATA_REQUEST) {
                        $requestData = [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => 'viewRequestPurpose(' . $row['ureq_id'] . ')',
                                'title' => Labels::getLabel('LBL_View', $siteLangId)
                            ],
                            'label' => "<i class='far fa-eye icon'></i>"
                        ];
                        $data['otherButtons'][] = $requestData;
                    }
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
