<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$printData = false;
if (!isset($tbody)) {
    $printData = true;
    $tbody = new HtmlElement('tbody', ['class' => 'listingRecordJs']);
}
$serialNo = ($page > 1) ? $recordCount - (($page - 1) * $pageSize) : $recordCount;
$moduleType = FatApp::getConfig('CONF_RFQ_MODULE_TYPE', FatUtility::VAR_INT, 0);
foreach ($arrListing as $sn => $row) {
    $cls = (($serialNo % 2) == 0) ? 'even' : 'odd';
    $tr = $tbody->appendElement('tr', ['class' => $cls, 'data-row' => $serialNo]);

    foreach ($fields as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : (('select_all' == $key) ? ['class' => 'col-check'] : []);
        $td = $tr->appendElement('td', $tdAttr);
        switch ($key) {
            case 'select_all':
                $disabled = (0 < $row['acceptedOffers']) ? 'disabled' : '';
                $name = (0 < $row['acceptedOffers']) ? '' : 'name="rfq_ids[]"';
                $value = (0 < $row['acceptedOffers']) ? '' : 'value=' . $row['rfq_id'];
                $td->appendElement('plaintext', $tdAttr, '<label class="checkbox"><input class="selectItemJs" type="checkbox" ' . $name . ' ' . $value . ' ' . $disabled . '><i class="input-helper"></i></label>', true);
                break;
            case 'listSerial':
                $td->appendElement('plaintext', $tdAttr, $serialNo, true);
                break;
            case 'acceptedOffers':
            case 'rejectedOffers':
                $offerCount = ($row['totalOffers'] > 0) ? $row[$key] . '/' . $row['totalOffers'] : 0;
                $td->appendElement('plaintext', $tdAttr, $offerCount, true);
                break;
            case 'rfq_title':
                $htm = '<div>
                            <span>' . Labels::getLabel('LBL_RFQ_NO.') . ': ' . $row['rfq_number'] . '</span>
                            <span>' . Labels::getLabel('LBL_TITLE') . ': ' . $row[$key] . '</span>
                            <span>' . Labels::getLabel('LBL_QTY') . ': ' . $row['rfq_quantity'] . ' ' . applicationConstants::getWeightUnitName($siteLangId, $row['rfq_quantity_unit'], true) . '</span>
                        </div>';
                $td->appendElement('plaintext', array(), $htm, true);
                break;
            case 'rfq_status':
                $html = RequestForQuote::getStatusHtml($row['rfq_status'], $siteLangId);
                $td->appendElement('plaintext', array(), $html, true);
                break;
            case 'rfq_approved':
                if (RequestForQuote::PENDING == $row[$key]) {
                    $html = '<select class="form-select form-select-sm custom-select" onchange="approval(event, this, ' . $row['rfq_id'] . ', this.value)">
                            <option value="' . RequestForQuote::PENDING . '" selected>' . $approvalStatusArr[RequestForQuote::PENDING] . '</option>
                            <option value="' . RequestForQuote::APPROVED . '">' . $approvalStatusArr[RequestForQuote::APPROVED] . '</option>
                            <option value="' . RequestForQuote::REJECTED . '">' . $approvalStatusArr[RequestForQuote::REJECTED] . '</option>
                        </select>';
                } else {
                    $html = '<span class="' . RequestForQuote::getApprovalStatusBadge($row[$key]) . '">' . $approvalStatusArr[$row[$key]] . '</span>';
                }
                $td->appendElement('plaintext', $tdAttr, $html, true);
                break;
            case 'rfq_delivery_date':
            case 'rfq_added_on':
                $td->appendElement('plaintext', array(), HtmlHelper::formatDateTime($row[$key]), true);
                break;
            case 'credential_username':
                $global = '';
                if (RequestForQuote::VISIBILITY_TYPE_OPEN == $row['rfq_visibility_type']) {
                    $global = HtmlHelper::getStatusHtml(HtmlHelper::INFO, Labels::getLabel('LBL_PUBLIC', $siteLangId));
                }
                $href = "javascript:void(0)";
                $onclick = ($canViewUsers ? 'redirectUser(' . $row['user_id'] . ')' : '');
                $htm = '<div class="rfq-info">
                            <span>' . Labels::getLabel('LBL_RFQ_NO.') . ': ' . $row['rfq_number'] . '' . $global . '</span><br>
                            <span>' . Labels::getLabel('LBL_TITLE') . ': ' . $row['rfq_title'] . '</span><br>
                            <span>' . Labels::getLabel('LBL_QTY') . ': ' . $row['rfq_quantity'] . ' ' . applicationConstants::getWeightUnitName($siteLangId, $row['rfq_quantity_unit'], true) . '</span>
                        </div>';
                $str = $this->includeTemplate('_partial/user/user-info-card.php', [
                    'user' => $row,
                    'siteLangId' => $siteLangId,
                    'href' => $href,
                    'onclick' => $onclick,
                    'extraHtml' => $htm,
                ], false, true);
                $td->appendElement('plaintext', $tdAttr, $str, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['rfq_id']
                ];

                if ($row['rfq_approved'] == RequestForQuote::APPROVED) {
                    $data['otherButtons'][] = [
                        'attr' => [
                            'href' => UrlHelper::generateUrl('RfqOffers', 'listing', [$row['rfq_id']]),
                            'title' => Labels::getLabel('LBL_OFFERS', $siteLangId),
                        ],
                        'label' => '<i class="icn"><svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#list">
                                        </use>
                                    </svg></i>',
                    ];
                }

                $data['otherButtons'][] =
                    [
                        'attr' => [
                            'href' => 'javascript:void(0)',
                            'title' => Labels::getLabel('LBL_VIEW', $siteLangId),
                            'onclick' => "view(" . $row['rfq_id'] . ")"
                        ],
                        'label' => '<i class="icn"><svg class="svg" width="18" height="18">
                                        <use
                                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#view">
                                        </use>
                                    </svg></i>',
                    ];


                if ($canEdit) {
                    if (RequestForQuote::APPROVED == $row['rfq_approved']) {
                        if (1 > $row['acceptedOffers']) {
                            $data['dropdownButtons']['deleteButton'] = [];
                        }
                        if (RequestForQuote::TYPE_INDIVIDUAL != $moduleType) {
                            $data['dropdownButtons']['otherButtons'][] = [
                                'attr' => [
                                    'href' => 'javascript:void(0)',
                                    'title' => Labels::getLabel('LBL_ASSIGN_SELLER', $siteLangId),
                                    'onclick' => "assignSellerForm(" . $row['rfq_id'] . ")"
                                ],
                                'label' => '<i class="icn"><svg class="svg" width="18" height="18">
                                            <use
                                                xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#group-add">
                                            </use>
                                        </svg></i>' . Labels::getLabel('LBL_ASSIGN_SELLER', $siteLangId),
                            ];
                        }
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
    $serialNo--;
}

include(CONF_THEME_PATH . '_partial/listing/no-record-found.php');

if ($printData) {
    echo $tbody->getHtml();
}
