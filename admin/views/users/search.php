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
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" name="user_ids[]" value=' . $row['user_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'user_name':
                $onclick = $canViewShops && !empty($row['shop_id']) ? 'redirectToShop(' . $row['shop_id'] . ')' : '';
                $title = '';
                if (!empty($row['shop_name'])) {
                    $str = Labels::getLabel('LBL_SHOP:_{SHOP}', $siteLangId);
                    $row['extra_text'] = CommonHelper::replaceStringData($str, ['{SHOP}' => $row['shop_name']]);
                    $title = Labels::getLabel('LBL_CLICK_HERE_TO_VISIT_SHOP_LIST', $siteLangId);
                }
                $str = $this->includeTemplate('_partial/user/user-info-card.php', ['user' => $row, 'addVerifiedBadge' => true, 'siteLangId' => $siteLangId, 'onclick' => $onclick, 'title' => $title, 'emailOnClick' => 'sendMailToUser(' . $row['user_id'] . ')'], false, true);
                $td->appendElement('plaintext', $tdAttr, '<div class="user-profile">' . $str . '</div>', true);
                break;
            case 'credential_active':
                $htm = HtmlHelper::addStatusBtnHtml($canEdit, $row['user_id'], ($row[$key] ?? 0), callback:(!$row['credential_active'] ? 'reloadList()' : ''));
                $td->appendElement('plaintext', $tdAttr, $htm, true);
                break;
            case 'user_regdate':
                $date = HtmlHelper::formatDateTime(
                    $row[$key],
                    true,
                    true,
                    FatApp::getConfig('CONF_TIMEZONE', FatUtility::VAR_STRING, date_default_timezone_get())
                );
                $td->appendElement('plaintext', $tdAttr, $date, true);
                break;
            case 'user_type':
                $str = $this->includeTemplate('users/user-type.php', ['row' => $row, 'siteLangId' => $siteLangId], false, true);
                $td->appendElement('plaintext', $tdAttr, $str, true);
                break;
            case 'user_registered_initially_for':
                $statusHtm = User::getUserTypeHtml($siteLangId, $row[$key]);
                if (0 < $row['user_parent']) {
                    $statusHtm = '<span class="badge badge-info">' . Labels::getLabel('LBL_SUB_USER', $siteLangId) . '</span>';;
                }
                $td->appendElement('plaintext', $tdAttr, $statusHtm, true);
                break;
            case 'credential_verified':
                $class = (applicationConstants::NO == $row[$key]) ? 'is-verified' : '';
                $img = '<div class="verified ' . $class . '"><svg class="svg" >
                            <use
                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-verified">
                            </use>
                        </svg>';
                $td->appendElement('plaintext', $tdAttr, $img, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['user_id']
                ];

                $lbl = Labels::getLabel('LBL_LOGIN_TO_USER_ACCOUNT', $siteLangId);
                if (!$row['credential_active'] && !$row['credential_verified']) {
                    $lbl = Labels::getLabel('LBL_PLEASE_MARK_THIS_USER_AS_ACTIVE_AND_VERIFIED_TO_LOGIN.', $siteLangId);
                } else if (!$row['credential_active']) {
                    $lbl = Labels::getLabel('LBL_PLEASE_MARK_THIS_USER_AS_ACTIVE_TO_LOGIN.', $siteLangId);
                } else if (!$row['credential_verified']) {
                    $lbl = Labels::getLabel('LBL_PLEASE_MARK_THIS_USER_AS_VERIFIED_TO_LOGIN.', $siteLangId);
                }

                if ($canEdit) {
                    $data['editButton'] = [];
                    $data['deleteButton'] = [];

                    $data['dropdownButtons']['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => 'changeUserPassword(' . $row['user_id'] . ')',
                            ],
                            'label' => '<i class="icn">
                                            <svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#password">
                                                </use>
                                            </svg>
                                        </i>' . Labels::getLabel('LBL_CHANGE_PASSWORD', $siteLangId),
                        ],
                        [
                            'attr' => [
                                'href' => UrlHelper::generateUrl('Users', 'login', array($row['user_id'])),
                                'target' => '_blank',
                                'id' => 'redirectJs',
                                'data-bs-toggle' => 'tooltip',
                                'title' => $lbl
                            ],
                            'label' => '<i class="icn">
                                            <svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#login">
                                                </use>
                                            </svg>
                                        </i>' . Labels::getLabel('LBL_LOGIN_TO_USER_ACCOUNT', $siteLangId),
                        ],
                        [
                            'attr' => [
                                'href' => 'javascript::void(0)',
                                'onclick' => 'sendMailToUser(' . $row['user_id'] . ')',
                            ],
                            'label' => '<i class="icn">
                                            <svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-mail">
                                                </use>
                                            </svg>
                                        </i>' . Labels::getLabel('LBL_SEND_EMAIL', $siteLangId),
                        ]
                    ];
                    if (0 ==  $row['credential_verified'] && empty($row['credential_verified'])  && empty($row['credential_verified'])) {
                        $data['dropdownButtons']['otherButtons'][] = [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => 'sendSetPasswordEmail(' . $row['user_id'] . ')',
                            ],
                            'label' => '<i class="icn">
                                            <svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#password-email">
                                                </use>
                                            </svg>
                                        </i>' . Labels::getLabel('LBL_RESEND_SET_PASSWORD_EMAIL', $siteLangId),
                        ];
                    }

                    if (!$row['user_is_buyer']) {
                        $data['dropdownButtons']['otherButtons'][] = [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => 'markSellerAsBuyer(' . $row['user_id'] . ')',
                            ],
                            'label' => '<i class="icn">
                                            <svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-users">
                                                </use>
                                            </svg>
                                        </i>' . Labels::getLabel('LBL_MARK_AS_BUYER', $siteLangId),
                        ];
                    }

                    if (!$row['credential_verified']) {
                        $data['dropdownButtons']['otherButtons'][] = [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => 'markVerified(' . $row['user_id'] . ')',
                            ],
                            'label' => '<i class="icn">                                            
                                            <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="18px" viewBox="0 0 24 24" width="18px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><g><path d="M23,11.99l-2.44-2.79l0.34-3.69l-3.61-0.82L15.4,1.5L12,2.96L8.6,1.5L6.71,4.69L3.1,5.5L3.44,9.2L1,11.99l2.44,2.79 l-0.34,3.7l3.61,0.82L8.6,22.5l3.4-1.47l3.4,1.46l1.89-3.19l3.61-0.82l-0.34-3.69L23,11.99z M19.05,13.47l-0.56,0.65l0.08,0.85 l0.18,1.95l-1.9,0.43l-0.84,0.19l-0.44,0.74l-0.99,1.68l-1.78-0.77L12,18.85l-0.79,0.34l-1.78,0.77l-0.99-1.67l-0.44-0.74 l-0.84-0.19l-1.9-0.43l0.18-1.96l0.08-0.85l-0.56-0.65l-1.29-1.47l1.29-1.48l0.56-0.65L5.43,9.01L5.25,7.07l1.9-0.43l0.84-0.19 l0.44-0.74l0.99-1.68l1.78,0.77L12,5.14l0.79-0.34l1.78-0.77l0.99,1.68l0.44,0.74l0.84,0.19l1.9,0.43l-0.18,1.95l-0.08,0.85 l0.56,0.65l1.29,1.47L19.05,13.47z"/><polygon points="10.09,13.75 7.77,11.42 6.29,12.91 10.09,16.72 17.43,9.36 15.95,7.87"/></g></g></svg>
                                        </i>' . Labels::getLabel('LBL_MARK_AS_VERIFIED', $siteLangId),
                        ];
                    }


                    $data['dropdownButtons']['otherButtons'][] = [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'onclick' => 'redirectfunc(fcom.makeUrl("transactions"),{utxn_user_id:' . $row['user_id'] . '})',
                        ],
                        'label' => '<i class="icn">
                                        <svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#sync-currency">
                                            </use>
                                        </svg>
                                    </i>' . Labels::getLabel('LBL_TRANSACTIONS', $siteLangId),
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
